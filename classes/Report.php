<?php
/**
 * Report
 * Aggregate statistics used by the dashboard and reports module.
 */
class Report
{
    private PDO $db;

    private const BREACHED_CONDITION = "
        ((r.status NOT IN ('Completed','Cancelled') AND r.due_at < NOW())
         OR (r.status = 'Completed' AND r.completed_at > r.due_at))
    ";

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function totalPolicies(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM sla_policies')->fetchColumn();
    }

    public function totalRecords(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM records')->fetchColumn();
    }

    public function activeRecords(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM records WHERE status IN ('Open','In Progress')")->fetchColumn();
    }

    public function completedRecords(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM records WHERE status = 'Completed'")->fetchColumn();
    }

    public function breachedRecords(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM records r WHERE ' . self::BREACHED_CONDITION)->fetchColumn();
    }

    public function dueToday(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM records WHERE status IN ('Open','In Progress') AND DATE(due_at) = CURDATE()"
        )->fetchColumn();
    }

    public function complianceRate(): float
    {
        $total = (int) $this->db->query("SELECT COUNT(*) FROM records WHERE status IN ('Completed','Cancelled')")->fetchColumn();
        if ($total === 0) {
            return 0.0;
        }
        $onTime = (int) $this->db->query(
            "SELECT COUNT(*) FROM records WHERE status = 'Completed' AND completed_at <= due_at"
        )->fetchColumn();
        return round(($onTime / $total) * 100, 1);
    }

    public function averageCompletionSeconds(): int
    {
        $result = $this->db->query(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) AS avg_seconds
             FROM records WHERE status = 'Completed' AND completed_at IS NOT NULL"
        )->fetch();
        return (int) round($result['avg_seconds'] ?? 0);
    }

    public function departmentPerformance(): array
    {
        $sql = "
            SELECT r.department AS department,
                   COUNT(*) AS total,
                   SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN " . self::BREACHED_CONDITION . " THEN 1 ELSE 0 END) AS breached
            FROM records r
            WHERE r.department IS NOT NULL AND r.department != ''
            GROUP BY r.department
            ORDER BY total DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function userPerformance(): array
    {
        $sql = "
            SELECT u.full_name,
                   COUNT(r.id) AS total,
                   SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN " . self::BREACHED_CONDITION . " THEN 1 ELSE 0 END) AS breached
            FROM records r
            INNER JOIN users u ON u.id = r.assigned_to
            GROUP BY u.id, u.full_name
            ORDER BY total DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function monthlyStatistics(int $months = 6): array
    {
        $sql = "
            SELECT DATE_FORMAT(r.created_at, '%Y-%m') AS ym,
                   COUNT(*) AS total,
                   SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN " . self::BREACHED_CONDITION . " THEN 1 ELSE 0 END) AS breached
            FROM records r
            WHERE r.created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll();
    }

    public function statusBreakdown(): array
    {
        return $this->db->query(
            "SELECT status, COUNT(*) AS total FROM records GROUP BY status"
        )->fetchAll();
    }
}
