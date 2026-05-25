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

    public function getCardData(string $identifier, int $year): array {
        $stmt = $this->conn->prepare("
            SELECT type, payment_date FROM payments
            WHERE identifier = ? AND YEAR(payment_date) = ?
        ");
        $stmt->bind_param("si", $identifier, $year);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $monthsPaid    = [];
        $assurancePaid = false;
        $adhesionPaid  = false;

        foreach ($rows as $row) {
            if ($row['type'] === 'assurance') {
                $assurancePaid = true;
            } elseif ($row['type'] === 'adhesion') {
                $adhesionPaid = true;
            } else {
                $monthsPaid[] = (int) date('n', strtotime($row['payment_date']));
            }
        }

        return compact('monthsPaid', 'assurancePaid', 'adhesionPaid');
    }

    public function saveCard(string $identifier, int $year, array $months, bool $assurance, bool $adhesion): void {
        $stmt = $this->conn->prepare("
            SELECT p.price, p.assurance AS assurance_price, p.adherence AS adhesion_price
            FROM plans p JOIN adherents a ON a.type = p.name
            WHERE a.identifier = ?
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $monthPrice     = (float)($plan['price']           ?? 0);
        $assurancePrice = (float)($plan['assurance_price'] ?? 0);
        $adhesionPrice  = (float)($plan['adhesion_price']  ?? 0);

        $stmt = $this->conn->prepare("DELETE FROM payments WHERE identifier = ? AND YEAR(payment_date) = ?");
        $stmt->bind_param("si", $identifier, $year);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO payments (identifier, amount, type, payment_date) VALUES (?, ?, ?, ?)");

        foreach ($months as $month) {
            $date = sprintf('%04d-%02d-01', $year, (int)$month);
            $type = 'mois';
            $stmt->bind_param("sdss", $identifier, $monthPrice, $type, $date);
            $stmt->execute();
        }

        if ($assurance) {
            $date = sprintf('%04d-01-01', $year);
            $type = 'assurance';
            $stmt->bind_param("sdss", $identifier, $assurancePrice, $type, $date);
            $stmt->execute();
        }

        if ($adhesion) {
            $date = sprintf('%04d-01-01', $year);
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
}