<?php
class Plan {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT plans.*, COUNT(adherents.identifier) AS adherents_count
            FROM plans
            LEFT JOIN adherents ON adherents.type = plans.name AND adherents.status = 'active'
            GROUP BY plans.id
            ORDER BY plans.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function getNames() {
        $stmt = $this->conn->prepare("SELECT name FROM plans ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function add($name, $price, $description, $assurance, $adherence) {
        $stmt = $this->conn->prepare("
            INSERT INTO plans (name, price, description, assurance, adherence)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsdd", $name, $price, $description, $assurance, $adherence);
        $stmt->execute();
        $stmt->close();
    }

    public function update($id, $name, $price, $description, $assurance, $adherence) {
        $stmt = $this->conn->prepare("
            UPDATE plans SET name=?, price=?, description=?, assurance=?, adherence=?
            WHERE id=?
        ");
        $stmt->bind_param("sdsddi", $name, $price, $description, $assurance, $adherence, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
