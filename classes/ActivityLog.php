<?php
/**
 * ActivityLog
 * Records an audit trail of actions taken on records.
 */
class ActivityLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log(?int $recordId, ?int $userId, string $action, string $remarks = ''): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (record_id, user_id, action, remarks, created_at) VALUES (:record_id, :user_id, :action, :remarks, NOW())'
        );
        $stmt->execute([
            'record_id' => $recordId,
            'user_id'   => $userId,
            'action'    => str_limit($action, 50),
            'remarks'   => str_limit($remarks, 255),
        ]);
    }

    public function forRecord(int $recordId): array
    {
        $stmt = $this->db->prepare(
            'SELECT al.*, u.full_name AS user_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.record_id = :record_id
             ORDER BY al.created_at DESC, al.id DESC'
        );
        $stmt->execute(['record_id' => $recordId]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, (int) $limit);
        $stmt = $this->db->query(
            "SELECT al.*, u.full_name AS user_name, r.reference_number
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN records r ON r.id = al.record_id
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }
}
