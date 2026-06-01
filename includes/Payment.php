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

    public function getByMonth($month)
    {
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
     * Returns monthly amounts paid (keyed by month number), attendance counts, and pricing.
     */
    public function getCardData(string $identifier, int $sportYear): array {
        $nextYear = $sportYear + 1;

        // Payments within the sport year
        $stmt = $this->conn->prepare("
            SELECT type, payment_date, amount FROM payments
            WHERE identifier = ?
              AND ((YEAR(payment_date) = ? AND MONTH(payment_date) >= 9)
                OR (YEAR(payment_date) = ? AND MONTH(payment_date) <= 8))
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Attendance counts per month within the sport year
        $stmt = $this->conn->prepare("
            SELECT MONTH(date) AS mon, COUNT(*) AS cnt
            FROM attendance
            WHERE identifier = ?
              AND ((YEAR(date) = ? AND MONTH(date) >= 9)
                OR (YEAR(date) = ? AND MONTH(date) <= 8))
            GROUP BY MONTH(date)
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $attRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Pricing: adherent special price or plan price
        $stmt = $this->conn->prepare("
            SELECT a.monthly_price, p.price,
                   p.assurance AS assurance_price, p.adherence AS adhesion_price
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

        $monthsPaid    = [];
        $assurancePaid = false;
        $adhesionPaid  = false;

        foreach ($rows as $row) {
            if ($row['type'] === 'assurance') {
                $assurancePaid = true;
            } elseif ($row['type'] === 'adhesion') {
                $adhesionPaid = true;
            } else {
                $m = (int)date('n', strtotime($row['payment_date']));
                $monthsPaid[$m] = (float)$row['amount'];
            }
        }

        $attendanceCounts = [];
        foreach ($attRows as $row) {
            $attendanceCounts[(int)$row['mon']] = (int)$row['cnt'];
        }

        return [
            'monthsPaid'       => $monthsPaid,
            'assurancePaid'    => $assurancePaid,
            'adhesionPaid'     => $adhesionPaid,
            'monthlyDue'       => $monthlyDue,
            'assurancePrice'   => (float)($pricing['assurance_price'] ?? 0),
            'adhesionPrice'    => (float)($pricing['adhesion_price']  ?? 0),
            'attendanceCounts' => $attendanceCounts,
        ];
    }

    /**
     * Save payment card for sport year.
     * $monthAmounts: [month_number => amount_paid]
     */
    public function saveCard(string $identifier, int $sportYear, array $monthAmounts, bool $assurance, bool $adhesion): void {
        $nextYear = $sportYear + 1;

        // Get plan prices for assurance/adhesion
        $stmt = $this->conn->prepare("
            SELECT p.assurance AS assurance_price, p.adherence AS adhesion_price
            FROM plans p JOIN adherents a ON a.type = p.name
            WHERE a.identifier = ?
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc() ?? [];
        $stmt->close();

        $assurancePrice = (float)($plan['assurance_price'] ?? 0);
        $adhesionPrice  = (float)($plan['adhesion_price']  ?? 0);

        // Delete all payments for this sport year
        $stmt = $this->conn->prepare("
            DELETE FROM payments WHERE identifier = ?
              AND ((YEAR(payment_date) = ? AND MONTH(payment_date) >= 9)
                OR (YEAR(payment_date) = ? AND MONTH(payment_date) <= 8))
        ");
        $stmt->bind_param("sii", $identifier, $sportYear, $nextYear);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO payments (identifier, amount, type, payment_date) VALUES (?, ?, ?, ?)");

        foreach ($monthAmounts as $month => $amount) {
            $month      = (int)$month;
            $actualYear = ($month >= 9) ? $sportYear : $nextYear;
            $date       = sprintf('%04d-%02d-01', $actualYear, $month);
            $type       = 'mois';
            $stmt->bind_param("sdss", $identifier, $amount, $type, $date);
            $stmt->execute();
        }

        if ($assurance) {
            $date = sprintf('%04d-09-01', $sportYear);
            $type = 'assurance';
            $stmt->bind_param("sdss", $identifier, $assurancePrice, $type, $date);
            $stmt->execute();
        }

        if ($adhesion) {
            $date = sprintf('%04d-09-01', $sportYear);
            $type = 'adhesion';
            $stmt->bind_param("sdss", $identifier, $adhesionPrice, $type, $date);
            $stmt->execute();
        }

        $stmt->close();
    }

    public function getByYearAndType($year, $type)
    {
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

    public function getUnpaidByMonth($month)
    {
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

    public function getUnpaidByYearAndType($year, $type)
    {
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
     * Returns all active adherents with outstanding debts:
     * unpaid/partial monthly fees, missing assurance, missing adhesion.
     * Monthly fees checked from adherent start date to now (sport years).
     * Assurance/adhesion checked per calendar year.
     */
    public function getBlackListData(): array {
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');

        // All active adherents with pricing
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

        // All payments ever
        $stmt = $this->conn->prepare("SELECT identifier, type, amount, payment_date FROM payments");
        $stmt->execute();
        $allPayments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $paysByAdherent = [];
        foreach ($allPayments as $p) {
            $paysByAdherent[$p['identifier']][] = $p;
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

            // Index monthly payments by "YYYY-MM"
            $paidYM = [];
            // Index assurance/adhesion by calendar year
            $assuranceYears = [];
            $adhesionYears  = [];

            foreach ($payments as $p) {
                $y = (int)substr($p['payment_date'], 0, 4);
                $m = (int)substr($p['payment_date'], 5, 2);
                if ($p['type'] === 'assurance') {
                    $assuranceYears[$y] = true;
                } elseif ($p['type'] === 'adhesion') {
                    $adhesionYears[$y] = true;
                } else {
                    $ym = substr($p['payment_date'], 0, 7);
                    $paidYM[$ym] = (float)$p['amount'];
                }
            }

            // Determine first sport year from adhesion date
            $startDate  = !empty($adh['date_adhesion']) ? new DateTime($adh['date_adhesion']) : new DateTime('2020-09-01');
            $startYear  = (int)$startDate->format('Y');
            $startMonth = (int)$startDate->format('n');
            $firstSportYear   = $startMonth >= 9 ? $startYear : $startYear - 1;
            $currentSportYear = $currentMonth >= 9 ? $currentYear : $currentYear - 1;

            $issues    = [];
            $totalRest = 0;

            // Check monthly fees across all sport years
            if ($monthlyDue > 0) {
                for ($sy = $firstSportYear; $sy <= $currentSportYear; $sy++) {
                    foreach ([9,10,11,12,1,2,3,4,5,6,7,8] as $m) {
                        $actualYear = $m >= 9 ? $sy : $sy + 1;
                        if ($actualYear > $currentYear) continue;
                        if ($actualYear === $currentYear && $m > $currentMonth) continue;
                        $checkDate = new DateTime(sprintf('%04d-%02d-01', $actualYear, $m));
                        if ($checkDate < $startDate) continue;

                        $ym = sprintf('%04d-%02d', $actualYear, $m);
                        if (!isset($paidYM[$ym])) {
                            $rest       = $monthlyDue;
                            $totalRest += $rest;
                            $issues[]   = [
                                'type'       => 'month_unpaid',
                                'label'      => $monthNames[$m] . ' ' . $actualYear,
                                'sport_year' => $sy,
                                'due'        => $monthlyDue,
                                'paid'       => 0,
                                'rest'       => $rest,
                            ];
                        } elseif ($paidYM[$ym] < $monthlyDue - 0.01) {
                            $rest       = $monthlyDue - $paidYM[$ym];
                            $totalRest += $rest;
                            $issues[]   = [
                                'type'       => 'month_partial',
                                'label'      => $monthNames[$m] . ' ' . $actualYear,
                                'sport_year' => $sy,
                                'due'        => $monthlyDue,
                                'paid'       => $paidYM[$ym],
                                'rest'       => $rest,
                            ];
                        }
                    }
                }
            }

            // Check assurance and adhesion per calendar year from start to current
            $assurancePrice = (float)($adh['assurance_price'] ?? 0);
            $adhesionPrice  = (float)($adh['adhesion_price']  ?? 0);

            for ($y = $startYear; $y <= $currentYear; $y++) {
                if ($assurancePrice > 0 && !isset($assuranceYears[$y])) {
                    $totalRest += $assurancePrice;
                    $issues[]   = [
                        'type'  => 'assurance',
                        'label' => 'التأمين ' . $y,
                        'due'   => $assurancePrice,
                        'paid'  => 0,
                        'rest'  => $assurancePrice,
                    ];
                }
                if ($adhesionPrice > 0 && !isset($adhesionYears[$y])) {
                    $totalRest += $adhesionPrice;
                    $issues[]   = [
                        'type'  => 'adhesion',
                        'label' => 'الانخراط ' . $y,
                        'due'   => $adhesionPrice,
                        'paid'  => 0,
                        'rest'  => $adhesionPrice,
                    ];
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
}
