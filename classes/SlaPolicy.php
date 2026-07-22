<?php
/**
 * SlaPolicy
 * CRUD operations for sla_policies table.
 */
class SlaPolicy
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM sla_policies';
        if ($activeOnly) {
            $sql .= ' WHERE status = 1';
        }
        $sql .= ' ORDER BY hours ASC, minutes ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sla_policies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sla_policies (policy_name, description, hours, minutes, warning_before_minutes, status, created_at)
             VALUES (:policy_name, :description, :hours, :minutes, :warning_before_minutes, :status, NOW())'
        );
        $stmt->execute([
            'policy_name'            => $data['policy_name'],
            'description'            => $data['description'],
            'hours'                  => $data['hours'],
            'minutes'                => $data['minutes'],
            'warning_before_minutes' => $data['warning_before_minutes'],
            'status'                 => $data['status'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sla_policies SET policy_name = :policy_name, description = :description, hours = :hours,
             minutes = :minutes, warning_before_minutes = :warning_before_minutes, status = :status WHERE id = :id'
        );
        return $stmt->execute([
            'policy_name'            => $data['policy_name'],
            'description'            => $data['description'],
            'hours'                  => $data['hours'],
            'minutes'                => $data['minutes'],
            'warning_before_minutes' => $data['warning_before_minutes'],
            'status'                 => $data['status'],
            'id'                     => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sla_policies WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function toggleStatus(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE sla_policies SET status = 1 - status WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function isInUse(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM records WHERE sla_policy_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM sla_policies')->fetchColumn();
    }
}
