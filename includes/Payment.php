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
            ORDER BY payments.Date DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByAdherent($identifier) {
        $stmt = $this->conn->prepare("
            SELECT * FROM payments
            WHERE identifier = ?
            ORDER BY Date DESC
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
            WHERE payments.Date = ?
            ORDER BY payments.Date DESC
        ");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function hasPaidThisMonth($identifier) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total FROM payments
            WHERE identifier = ?
            AND type = 'mois'
            AND MONTH(Date) = MONTH(CURDATE())
            AND YEAR(Date) = YEAR(CURDATE())
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'] > 0;
    }

    public function save($identifier, $amount, $type) {
        $stmt = $this->conn->prepare("
            INSERT INTO payments (identifier, amount, type)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sds", $identifier, $amount, $type);
        $stmt->execute();
        $stmt->close();
    }
}
