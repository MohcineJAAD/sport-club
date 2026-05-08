<?php
class Evaluation {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByAdherent($identifier) {
        $stmt = $this->conn->prepare("
            SELECT * FROM evaluations
            WHERE identifier = ?
            ORDER BY year DESC, month DESC
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByMonth($month, $year) {
        $stmt = $this->conn->prepare("
            SELECT evaluations.*, adherents.nom, adherents.prenom
            FROM evaluations
            JOIN adherents ON evaluations.identifier = adherents.identifier
            WHERE evaluations.month = ? AND evaluations.year = ?
            ORDER BY adherents.nom ASC
        ");
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function save($identifier, $month, $year, $discipline, $performance, $behavior) {
        $stmt = $this->conn->prepare("
            INSERT INTO evaluations (identifier, month, year, discipline, performance, behavior)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                discipline = VALUES(discipline),
                performance = VALUES(performance),
                behavior = VALUES(behavior)
        ");
        $stmt->bind_param("siiiii", $identifier, $month, $year, $discipline, $performance, $behavior);
        $stmt->execute();
        $stmt->close();
    }

    public function getAverage($identifier) {
        $stmt = $this->conn->prepare("
            SELECT
                ROUND(AVG(discipline), 1) as avg_discipline,
                ROUND(AVG(performance), 1) as avg_performance,
                ROUND(AVG(behavior), 1) as avg_behavior
            FROM evaluations
            WHERE identifier = ?
        ");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }
}
