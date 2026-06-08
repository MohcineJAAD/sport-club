<?php
class MemberEvent {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getBySeason(string $identifier, string $season): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM member_events
            WHERE identifier = ? AND season = ?
            ORDER BY type, event_date, id
        ");
        $stmt->bind_param("ss", $identifier, $season);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getAllForBlackList(): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM member_events
            WHERE paid_amount < due_amount - 0.001
            ORDER BY identifier, type, event_date
        ");
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $byIdentifier = [];
        foreach ($rows as $row) {
            $byIdentifier[$row['identifier']][] = $row;
        }
        return $byIdentifier;
    }

    public function upsert(int $id = 0, string $identifier, string $type, string $label,
                           float $due, float $paid, ?string $date, string $season): int {
        if ($id > 0) {
            $stmt = $this->conn->prepare("
                UPDATE member_events
                SET label=?, due_amount=?, paid_amount=?, event_date=?
                WHERE id=? AND identifier=?
            ");
            $stmt->bind_param("sddsis", $label, $due, $paid, $date, $id, $identifier);
            $stmt->execute();
            $stmt->close();
            return $id;
        }
        $stmt = $this->conn->prepare("
            INSERT INTO member_events (identifier, type, label, due_amount, paid_amount, event_date, season)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssddss", $identifier, $type, $label, $due, $paid, $date, $season);
        $stmt->execute();
        $newId = (int)$this->conn->insert_id;
        $stmt->close();
        return $newId;
    }

    public function delete(int $id, string $identifier): void {
        $stmt = $this->conn->prepare("DELETE FROM member_events WHERE id=? AND identifier=?");
        $stmt->bind_param("is", $id, $identifier);
        $stmt->execute();
        $stmt->close();
    }

    public function saveAll(string $identifier, string $season, array $events): void {
        // Delete all events for this season and re-insert
        $stmt = $this->conn->prepare("DELETE FROM member_events WHERE identifier=? AND season=?");
        $stmt->bind_param("ss", $identifier, $season);
        $stmt->execute();
        $stmt->close();

        foreach ($events as $ev) {
            $this->upsert(
                0,
                $identifier,
                $ev['type'],
                $ev['label'],
                (float)($ev['due']  ?? 0),
                (float)($ev['paid'] ?? 0),
                $ev['date'] ?: null,
                $season
            );
        }
    }
}