<?php
class Schedule {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT * FROM schedule
            ORDER BY FIELD(day, 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'), timeslot
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByDay($day) {
        $stmt = $this->conn->prepare("
            SELECT * FROM schedule
            WHERE day = ?
            ORDER BY timeslot
        ");
        $stmt->bind_param("s", $day);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function add($day, $timeslot, $sport_type) {
        $stmt = $this->conn->prepare("
            INSERT INTO schedule (day, timeslot, sport_type)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $day, $timeslot, $sport_type);
        $stmt->execute();
        $stmt->close();
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM schedule WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    public function replaceAll($grid)
    {
        $stmt = $this->conn->prepare("DELETE FROM schedule");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO schedule (day, timeslot, sport_type) VALUES (?, ?, ?)");
        foreach ($grid as $day => $slots) {
            foreach ($slots as $timeslot => $sport_type) {
                if (!empty($sport_type)) {
                    $stmt->bind_param("sss", $day, $timeslot, $sport_type);
                    $stmt->execute();
                }
            }
        }
        $stmt->close();
    }
}
