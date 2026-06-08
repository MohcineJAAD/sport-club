<?php
class Payment {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT payments.*, adherents.nom, adherents.prenom
            FROM payments
            JOIN adherents ON payments.identifier = adherents.identifier
            ORDER BY payments.payment_date DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByAdherent($identifier) {
        $stmt = $this->conn->prepare("
            SELECT * FROM payments WHERE identifier = ?
            ORDER BY payment_date DESC
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByDate($date) {
        $stmt = $this->conn->prepare("
            SELECT payments.*, adherents.nom, adherents.prenom
            FROM payments
            JOIN adherents ON payments.identifier = adherents.identifier
            WHERE payments.payment_date = ?
            ORDER BY payments.payment_date DESC
        ");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByMonth($month) {
        $stmt = $this->conn->prepare("
            SELECT payments.*, adherents.nom, adherents.prenom
            FROM payments
            JOIN adherents ON payments.identifier = adherents.identifier
            WHERE DATE_FORMAT(payments.payment_date, '%Y-%m') = ?
            ORDER BY payments.payment_date DESC
        ");
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function hasPaidThisMonth($identifier) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total FROM payments
            WHERE identifier = ? AND type = 'mois'
            AND MONTH(payment_date) = MONTH(CURDATE())
            AND YEAR(payment_date) = YEAR(CURDATE())
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['total'] > 0;
    }

    public function save($identifier, $amount, $type) {
        $stmt = $this->conn->prepare("
            INSERT INTO payments (identifier, amount, type, payment_date)
            VALUES (?, ?, ?, CURDATE())
        ");
        $stmt->bind_param("sds", $identifier, $amount, $type);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Sport year: September $sportYear – August ($sportYear+1).
     *
     * Returns:
     *   monthsPaid       [month => amount]   – months with a recorded payment
     *   assuranceAmount  float               – 0 if unpaid
     *   adhesionAmount   float               – 0 if unpaid
     *   examJanAmount    float               – 0 if not taken / unpaid
     *   examJunAmount    float               – 0 if not taken / unpaid
     *   monthlyDue       float               – special price or plan price
     *   assurancePrice   float               – plan assurance price
     *   adhesionPrice    float               – plan adhesion price
     *   examPrice        float               – plan exam price
     *   attendanceCounts [month => count]
     *   totalSessions    int                 – all-time attendance count
     */
    public function getCardData(string $identifier, int $sportYear): array {
        $nextYear = $sportYear + 1;

        // Payments within the sport year (including exam types)
        $stmt = $this->conn->prepare("
            SELECT type, payment_date, amount, due_amount FROM payments
            WHERE identifier = ?
              AND ((YEAR(payment_date) = ? AND MONTH(payment_date) >= 9)
                OR (YEAR(payment_date) = ? AND MONTH(payment_date) <= 8))
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Attendance per month within the sport year
        $stmt = $this->conn->prepare("
            SELECT MONTH(`date`) AS mon, COUNT(*) AS cnt
            FROM attendance
            WHERE identifier = ?
              AND ((YEAR(`date`) = ? AND MONTH(`date`) >= 9)
                OR (YEAR(`date`) = ? AND MONTH(`date`) <= 8))
            GROUP BY MONTH(`date`)
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $attRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Total all-time sessions (for trial period indicator)
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $totalSessions = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Pricing from adherent profile + plan
        $stmt = $this->conn->prepare("
            SELECT a.monthly_price, p.price,
                   p.assurance AS assurance_price,
                   p.adherence AS adhesion_price,
                   p.exam_price
            FROM adherents a
            LEFT JOIN plans p ON a.type = p.name
            WHERE a.identifier = ?
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $pricing = $stmt->get_result()->fetch_assoc() ?? [];
        $stmt->close();

        $monthlyDue = ($pricing['monthly_price'] !== null && $pricing['monthly_price'] !== '')
            ? (float)$pricing['monthly_price']
            : (float)($pricing['price'] ?? 0);

        $monthsPaid       = [];
        $monthsDue        = [];   // saved due_amount per month
        $assuranceAmount  = 0.0;
        $assuranceDue     = 0.0;
        $adhesionAmount   = 0.0;
        $adhesionDue      = 0.0;
        $examJanAmount    = 0.0;
        $examJanDue       = 0.0;
        $examJunAmount    = 0.0;
        $examJunDue       = 0.0;

        foreach ($rows as $row) {
            $m   = (int)date('n', strtotime($row['payment_date']));
            $amt = (float)$row['amount'];
            $due = $row['due_amount'] !== null ? (float)$row['due_amount'] : 0.0;
            switch ($row['type']) {
                case 'assurance':
                    $assuranceAmount = $amt;
                    $assuranceDue    = $due;
                    break;
                case 'adhesion':
                    $adhesionAmount  = $amt;
                    $adhesionDue     = $due;
                    break;
                case 'exam':
                    if ($m === 1) { $examJanAmount = $amt; $examJanDue = $due; }
                    if ($m === 6) { $examJunAmount = $amt; $examJunDue = $due; }
                    break;
                default: // 'mois'
                    $monthsPaid[$m] = $amt;
                    if ($due > 0) $monthsDue[$m] = $due;
            }
        }

        $attendanceCounts = [];
        foreach ($attRows as $row) {
            $attendanceCounts[(int)$row['mon']] = (int)$row['cnt'];
        }

        return [
            'monthsPaid'       => $monthsPaid,
            'monthsDue'        => $monthsDue,
            'assuranceAmount'  => $assuranceAmount,
            'assuranceDue'     => $assuranceDue,
            'adhesionAmount'   => $adhesionAmount,
            'adhesionDue'      => $adhesionDue,
            'examJanAmount'    => $examJanAmount,
            'examJanDue'       => $examJanDue,
            'examJunAmount'    => $examJunAmount,
            'examJunDue'       => $examJunDue,
            'monthlyDue'       => $monthlyDue,
            'assurancePrice'   => (float)($pricing['assurance_price'] ?? 0),
            'adhesionPrice'    => (float)($pricing['adhesion_price']  ?? 0),
            'examPrice'        => (float)($pricing['exam_price']      ?? 0),
            'attendanceCounts' => $attendanceCounts,
            'totalSessions'    => $totalSessions,
        ];
    }

    /**
     * Save payment card for a sport year.
     * Deletes all records for the sport year then re-inserts only those with amount > 0.
     */
    public function saveCard(
        string $identifier,
        int    $sportYear,
        array  $monthAmounts,
        array  $monthDueAmounts,
        float  $assuranceAmount,
        float  $assuranceDue,
        float  $adhesionAmount,
        float  $adhesionDue,
        float  $examJanAmount,
        float  $examJanDue,
        float  $examJunAmount,
        float  $examJunDue
    ): void {
        $nextYear = $sportYear + 1;

        // Wipe the full sport year
        $stmt = $this->conn->prepare("
            DELETE FROM payments WHERE identifier = ?
              AND ((YEAR(payment_date) = ? AND MONTH(payment_date) >= 9)
                OR (YEAR(payment_date) = ? AND MONTH(payment_date) <= 8))
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $stmt->close();

        $ins = $this->conn->prepare(
            "INSERT INTO payments (identifier, amount, due_amount, type, payment_date) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$ins) {
            error_log("Payment saveCard prepare failed: " . $this->conn->error);
            return;
        }

        foreach ($monthAmounts as $month => $amount) {
            $month = (int)$month;
            if ($amount <= 0) continue;
            $due        = (float)($monthDueAmounts[$month] ?? 0);
            $actualYear = $month >= 9 ? $sportYear : $nextYear;
            $date       = sprintf('%04d-%02d-01', $actualYear, $month);
            $type       = 'mois';
            $ins->bind_param("sddss", $identifier, $amount, $due, $type, $date);
            $ins->execute();
        }

        if ($assuranceAmount > 0) {
            $date = sprintf('%04d-09-01', $sportYear); $type = 'assurance';
            $due  = (float)$assuranceDue;
            $ins->bind_param("sddss", $identifier, $assuranceAmount, $due, $type, $date); $ins->execute();
        }
        if ($adhesionAmount > 0) {
            $date = sprintf('%04d-09-01', $sportYear); $type = 'adhesion';
            $due  = (float)$adhesionDue;
            $ins->bind_param("sddss", $identifier, $adhesionAmount, $due, $type, $date); $ins->execute();
        }
        if ($examJanAmount > 0) {
            $date = sprintf('%04d-01-01', $nextYear); $type = 'exam';
            $due  = (float)$examJanDue;
            $ins->bind_param("sddss", $identifier, $examJanAmount, $due, $type, $date); $ins->execute();
        }
        if ($examJunAmount > 0) {
            $date = sprintf('%04d-06-01', $nextYear); $type = 'exam';
            $due  = (float)$examJunDue;
            $ins->bind_param("sddss", $identifier, $examJunAmount, $due, $type, $date); $ins->execute();
        }

        $ins->close();
    }

    public function getByYearAndType($year, $type) {
        $stmt = $this->conn->prepare("
            SELECT payments.*, adherents.nom, adherents.prenom, adherents.type AS sport_type
            FROM payments
            JOIN adherents ON payments.identifier = adherents.identifier
            WHERE YEAR(payments.payment_date) = ? AND payments.type = ?
            ORDER BY adherents.nom, adherents.prenom
        ");
        $stmt->bind_param("is", $year, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUnpaidByMonth($month) {
        $stmt = $this->conn->prepare("
            SELECT a.identifier, a.nom, a.prenom, a.type AS sport_type,
                a.guardian_name, a.guardian_phone
            FROM adherents a
            WHERE a.status = 'active'
            AND a.identifier NOT IN (
                SELECT p.identifier FROM payments p
                WHERE DATE_FORMAT(p.payment_date, '%Y-%m') = ?
                    AND p.type = 'mois'
            )
            ORDER BY a.nom, a.prenom
        ");
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Members who have 5+ attendance sessions in the given month but have NOT paid.
     * $month = 'YYYY-MM'
     */
    public function getUnpaidWithAttendance($month) {
        [$y, $m] = explode('-', $month);
        $stmt = $this->conn->prepare("
            SELECT a.identifier, a.nom, a.prenom, a.type AS sport_type,
                   a.guardian_name, a.guardian_phone,
                   a.monthly_price, p.price AS plan_price,
                   COUNT(att.id) AS session_count
            FROM adherents a
            JOIN attendance att ON att.identifier = a.identifier
                AND YEAR(att.`date`) = ? AND MONTH(att.`date`) = ?
            LEFT JOIN plans p ON a.type = p.name
            WHERE a.status = 'active'
              AND a.identifier NOT IN (
                  SELECT p2.identifier FROM payments p2
                  WHERE DATE_FORMAT(p2.payment_date, '%Y-%m') = ?
                    AND p2.type = 'mois'
              )
            GROUP BY a.identifier, a.nom, a.prenom, a.type, a.guardian_name, a.guardian_phone, a.monthly_price, p.price
            HAVING COUNT(att.id) >= 5
            ORDER BY a.nom, a.prenom
        ");
        $stmt->bind_param("iss", $y, $m, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUnpaidByYearAndType($year, $type) {
        $stmt = $this->conn->prepare("
            SELECT a.identifier, a.nom, a.prenom, a.type AS sport_type,
                a.guardian_name, a.guardian_phone
            FROM adherents a
            WHERE a.status = 'active'
            AND a.identifier NOT IN (
                SELECT p.identifier FROM payments p
                WHERE YEAR(p.payment_date) = ? AND p.type = ?
            )
            ORDER BY a.nom, a.prenom
        ");
        $stmt->bind_param("is", $year, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Black list: all active adherents with unpaid/partial monthly fees,
     * missing assurance, or missing adhesion — from adhesion date to now.
     * Exam payments are voluntary so not checked here.
     */
    public function getBlackListData(): array {
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');

        $stmt = $this->conn->prepare("
            SELECT a.identifier, a.nom, a.prenom, a.type AS sport_type,
                   a.monthly_price, a.date_adhesion,
                   p.price AS plan_price,
                   p.assurance AS assurance_price,
                   p.adherence AS adhesion_price
            FROM adherents a
            LEFT JOIN plans p ON a.type = p.name
            WHERE a.status = 'active'
            ORDER BY a.nom, a.prenom
        ");
        $stmt->execute();
        $adherents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->conn->prepare(
            "SELECT identifier, type, amount, payment_date FROM payments WHERE type != 'exam'"
        );
        $stmt->execute();
        $allPayments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $paysByAdherent = [];
        foreach ($allPayments as $p) {
            $paysByAdherent[$p['identifier']][] = $p;
        }

        // Attendance counts per identifier per YYYY-MM
        $stmt = $this->conn->prepare(
            "SELECT identifier, DATE_FORMAT(`date`, '%Y-%m') AS ym, COUNT(*) AS cnt FROM attendance GROUP BY identifier, ym"
        );
        $stmt->execute();
        $attRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $attByAdherent = [];
        foreach ($attRows as $row) {
            $attByAdherent[$row['identifier']][$row['ym']] = (int)$row['cnt'];
        }

        $monthNames = [
            1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',
            5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'غشت',
            9=>'شتنبر',10=>'أكتوبر',11=>'نونبر',12=>'دجنبر',
        ];

        $blackList = [];

        foreach ($adherents as $adh) {
            $monthlyDue = ($adh['monthly_price'] !== null && $adh['monthly_price'] !== '')
                ? (float)$adh['monthly_price']
                : (float)($adh['plan_price'] ?? 0);

            $payments = $paysByAdherent[$adh['identifier']] ?? [];
            $paidYM         = [];
            $assuranceYears = [];
            $adhesionYears  = [];

            foreach ($payments as $p) {
                $y = (int)substr($p['payment_date'], 0, 4);
                if ($p['type'] === 'assurance') {
                    $assuranceYears[$y] = true;
                } elseif ($p['type'] === 'adhesion') {
                    $adhesionYears[$y] = true;
                } else {
                    $ym = substr($p['payment_date'], 0, 7);
                    $paidYM[$ym] = (float)$p['amount'];
                }
            }

            $startDate        = !empty($adh['date_adhesion']) ? new DateTime($adh['date_adhesion']) : new DateTime('2020-09-01');
            $startYear        = (int)$startDate->format('Y');
            $startMonth       = (int)$startDate->format('n');
            $firstSportYear   = $startMonth >= 9 ? $startYear : $startYear - 1;
            $currentSportYear = $currentMonth >= 9 ? $currentYear : $currentYear - 1;

            $issues    = [];
            $totalRest = 0;

            if ($monthlyDue > 0) {
                for ($sy = $firstSportYear; $sy <= $currentSportYear; $sy++) {
                    foreach ([9,10,11,12,1,2,3,4,5,6,7,8] as $m) {
                        $actualYear = $m >= 9 ? $sy : $sy + 1;
                        if ($actualYear > $currentYear) continue;
                        if ($actualYear === $currentYear && $m > $currentMonth) continue;
                        $checkDate = new DateTime(sprintf('%04d-%02d-01', $actualYear, $m));
                        if ($checkDate < $startDate) continue;

                        $ym       = sprintf('%04d-%02d', $actualYear, $m);
                        $sessions = $attByAdherent[$adh['identifier']][$ym] ?? 0;

                        if (!isset($paidYM[$ym])) {
                            // Only flag as debt if 5+ sessions attended
                            if ($sessions >= 5) {
                                $totalRest += $monthlyDue;
                                $issues[]   = [
                                    'type'  => 'month_unpaid',
                                    'label' => $monthNames[$m] . ' ' . $actualYear,
                                    'due'   => $monthlyDue, 'paid' => 0, 'rest' => $monthlyDue,
                                ];
                            }
                        } elseif ($paidYM[$ym] < $monthlyDue - 0.01) {
                            $rest       = $monthlyDue - $paidYM[$ym];
                            $totalRest += $rest;
                            $issues[]   = [
                                'type'  => 'month_partial',
                                'label' => $monthNames[$m] . ' ' . $actualYear,
                                'due'   => $monthlyDue, 'paid' => $paidYM[$ym], 'rest' => $rest,
                            ];
                        }
                    }
                }
            }

            $assurancePrice = (float)($adh['assurance_price'] ?? 0);
            $adhesionPrice  = (float)($adh['adhesion_price']  ?? 0);

            for ($y = $startYear; $y <= $currentYear; $y++) {
                if ($assurancePrice > 0 && !isset($assuranceYears[$y])) {
                    $totalRest += $assurancePrice;
                    $issues[] = ['type'=>'assurance','label'=>'التأمين '.$y,
                                 'due'=>$assurancePrice,'paid'=>0,'rest'=>$assurancePrice];
                }
                if ($adhesionPrice > 0 && !isset($adhesionYears[$y])) {
                    $totalRest += $adhesionPrice;
                    $issues[] = ['type'=>'adhesion','label'=>'الانخراط '.$y,
                                 'due'=>$adhesionPrice,'paid'=>0,'rest'=>$adhesionPrice];
                }
            }

            if (!empty($issues)) {
                $blackList[] = array_merge($adh, [
                    'issues'      => $issues,
                    'total_rest'  => $totalRest,
                    'monthly_due' => $monthlyDue,
                ]);
            }
        }

        return $blackList;
    }

    /**
     * Current month: active members with 5+ sessions who haven't paid.
     * Same structure as getBlackListData() rows but only this month's debt.
     */
    /**
     * All debt (same as black list) but only for members who attended 5+ sessions this month.
     */
    public function getUnpaidMonthData(): array {
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');

        // Get identifiers with 5+ sessions this month
        $stmt = $this->conn->prepare("
            SELECT identifier FROM attendance
            WHERE YEAR(`date`) = ? AND MONTH(`date`) = ?
            GROUP BY identifier HAVING COUNT(*) >= 5
        ");
        $stmt->bind_param("ii", $currentYear, $currentMonth);
        $stmt->execute();
        $activeRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $activeIds = array_column($activeRows, 'identifier');
        if (empty($activeIds)) return [];

        // Get full black list and filter to active members only
        $blackList = $this->getBlackListData();
        return array_values(array_filter($blackList, fn($a) => in_array($a['identifier'], $activeIds)));
    }
}
