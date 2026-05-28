<?php
class Attendance
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getByAdherent($identifier)
    {
        $stmt = $this->conn->prepare(
            "
            select * from attendance
            where identifier = ?
            order by date desc
            "
        );
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function countThisMonth($identifier)
    {
        $stmt = $this->conn->prepare(
            "
            select count(*) as total from attendance
            where identifier = ?
            and month(date) = month(curdate())
            and year(date) = year(curdate())
            "
        );
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'];
    }

    public function save($identifiers, $date)
    {
        $stmt = $this->conn->prepare("DELETE FROM attendance WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();

        if (!empty($identifiers)) {
            $stmt = $this->conn->prepare("INSERT INTO attendance (identifier, date) VALUES (?, ?)");
            foreach ($identifiers as $identifier) {
                $stmt->bind_param("ss", $identifier, $date);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    public function getMonthlySummary()
    {
        $stmt = $this->conn->prepare("
            SELECT
                a.identifier,
                a.nom,
                a.prenom,
                a.type,
                (SELECT COUNT(*) FROM payments p
                 WHERE p.identifier = a.identifier
                 AND p.type = 'mois'
                 AND MONTH(p.Date) = MONTH(CURDATE())
                 AND YEAR(p.Date) = YEAR(CURDATE())) AS paid,
                (SELECT COUNT(*) FROM attendance att
                 WHERE att.identifier = a.identifier
                 AND MONTH(att.date) = MONTH(CURDATE())
                 AND YEAR(att.date) = YEAR(CURDATE())) AS sessions
            FROM adherents a
            WHERE a.status = 'active'
            ORDER BY paid ASC, sessions ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStatus($paid, $sessions) {
        if ($paid === 0) {
            return ['label' => 'لم يدفع',          'color' => '#c00',    'bg' => '#ffecec'];
        } elseif ($sessions < 5) {
            return ['label' => 'دفع / حضور ضعيف', 'color' => '#b36b00', 'bg' => '#fff7e6'];
        } else {
            return ['label' => 'منتظم',             'color' => '#1a7a3a', 'bg' => '#eafaf1'];
        }
    }

    public function getByDate($date)
    {
        $stmt = $this->conn->prepare("SELECT identifier FROM attendance WHERE date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'identifier');
    }
}