<?php
class Adherent
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll($status='active')
    {
        $stmt = $this->conn->prepare("select * from adherents where status = ?");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($identifier)
    {
        $stmt = $this->conn->prepare("select * from adherents where identifier = ?");
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPending()
    {
        return $this->getAll('pending');
    }

    public function approve($identifier)
    {
        $stmt = $this->conn->prepare("update adherents set status = 'active' where identifier = ?");
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }

    public function reject($identifier)
    {
        $stmt = $this->conn->prepare("update adherents set status = 'rejected' where identifier = ?");
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }

    public function delete($identifier)
    {
        $stmt = $this->conn->prepare("delete from adherents where identifier = ?");
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    }

    public function create($data, $imagePath, $bcPath)
    {
        $identifier = $this->generateIdentifier();
        $stmt = $this->conn->prepare("
            INSERT INTO adherents
            (identifier, nom, prenom, date_naissance, poids, type, image_path, BC_path,
             guardian_name, guardian_phone, second_guardian_phone, address, health_status, blood_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssdsssssssss",
            $identifier,
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['poids'],
            $data['type'],
            $imagePath,
            $bcPath,
            $data['guardian_name'],
            $data['guardian_phone'],
            $data['second_guardian_phone'],
            $data['address'],
            $data['health_status'],
            $data['blood_type']
        );
        $stmt->execute();
        $stmt->close();
        return $identifier;
    }

    public function update($identifier, $data, $imagePath = '')
    {
        $sql = "UPDATE adherents SET
            nom=?, prenom=?, date_naissance=?, date_adhesion=?, poids=?,
            guardian_name=?, guardian_phone=?, second_guardian_phone=?, address=?,
            health_status=?, blood_type=?, current_belt=?, next_belt=?, licence=?, note=?";

        $types  = "ssssdssssssssss";
        $params = [
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'] ?: null,
            $data['date_adhesion']  ?: null,
            (float)($data['poids'] ?? 0),
            $data['guardian_name']          ?? '',
            $data['guardian_phone']         ?? '',
            $data['second_guardian_phone']  ?? '',
            $data['address']                ?? '',
            $data['health_status']          ?? '',
            $data['blood_type']             ?? '',
            $data['current_belt']           ?? '',
            $data['next_belt']              ?? '',
            $data['licence']                ?? '',
            $data['note']                   ?? '',
        ];

        if ($imagePath) {
            $sql    .= ", image_path=?";
            $types  .= "s";
            $params[] = $imagePath;
        }

        if (!empty($data['BC_path'])) {
            $sql    .= ", BC_path=?";
            $types  .= "s";
            $params[] = $data['BC_path'];
        }

        $sql .= " WHERE identifier=?";
        $types  .= "s";
        $params[] = $identifier;

        $stmt = $this->conn->prepare($sql);

        // bind_param requires references — splat operator does NOT work here
        $refs = [&$types];
        foreach ($params as &$p) {
            $refs[] = &$p;
        }
        unset($p);
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $stmt->close();
    }

    private function generateIdentifier()
    {
        $stmt = $this->conn->prepare("SELECT identifier FROM adherents ORDER BY identifier DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $last = $result->fetch_assoc();
        if (!$last) {
            return 'A000000001';
        }
        $number = (int)substr($last['identifier'], 1) + 1;
        return 'A' . str_pad($number, 9, '0', STR_PAD_LEFT);
    }
}