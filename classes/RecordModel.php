<?php
/**
 * RecordModel
 * CRUD + querying for the records table (the SLA-tracked tickets/items).
 */
class RecordModel
{
    private PDO $db;

    public const STATUSES  = ['Open', 'In Progress', 'Completed', 'Cancelled'];
    public const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];

    private const BASE_SELECT = "
        SELECT r.*,
               sp.policy_name AS sla_policy_name,
               sp.hours AS sla_hours,
               sp.minutes AS sla_minutes,
               sp.warning_before_minutes AS sla_warning_minutes,
               u.full_name AS assigned_to_name
        FROM records r
        LEFT JOIN sla_policies sp ON sp.id = r.sla_policy_id
        LEFT JOIN users u ON u.id = r.assigned_to
    ";

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE r.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function referenceExists(string $reference, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM records WHERE reference_number = :ref';
        $params = ['ref' => $reference];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function generateReference(): string
    {
        do {
            $ref = 'REC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while ($this->referenceExists($ref));
        return $ref;
    }

    public function create(array $data): int
    {
        $policy = (new SlaPolicy())->find((int) $data['sla_policy_id']);
        if (!$policy) {
            throw new InvalidArgumentException('Invalid SLA policy selected.');
        }

        $createdAt = date('Y-m-d H:i:s');
        $dueAt = date('Y-m-d H:i:s', strtotime($createdAt) + ((int) $policy['hours'] * 3600) + ((int) $policy['minutes'] * 60));

        $stmt = $this->db->prepare(
            'INSERT INTO records (reference_number, title, description, customer_name, department, assigned_to, priority, sla_policy_id, created_at, due_at, completed_at, status)
             VALUES (:reference_number, :title, :description, :customer_name, :department, :assigned_to, :priority, :sla_policy_id, :created_at, :due_at, NULL, :status)'
        );
        $stmt->execute([
            'reference_number' => $data['reference_number'],
            'title'            => $data['title'],
            'description'      => $data['description'],
            'customer_name'    => $data['customer_name'],
            'department'       => $data['department'],
            'assigned_to'      => $data['assigned_to'] ?: null,
            'priority'         => $data['priority'],
            'sla_policy_id'    => $data['sla_policy_id'],
            'created_at'       => $createdAt,
            'due_at'           => $dueAt,
            'status'           => $data['status'] ?? 'Open',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }

        // Recalculate due date if the SLA policy changed.
        $dueAt = $existing['due_at'];
        if ((int) $existing['sla_policy_id'] !== (int) $data['sla_policy_id']) {
            $policy = (new SlaPolicy())->find((int) $data['sla_policy_id']);
            if (!$policy) {
                throw new InvalidArgumentException('Invalid SLA policy selected.');
            }
            $dueAt = date('Y-m-d H:i:s', strtotime($existing['created_at']) + ((int) $policy['hours'] * 3600) + ((int) $policy['minutes'] * 60));
        }

        // Manage completed_at based on status transitions.
        $completedAt = $existing['completed_at'];
        if ($data['status'] === 'Completed' && $existing['status'] !== 'Completed') {
            $completedAt = date('Y-m-d H:i:s');
        } elseif ($data['status'] !== 'Completed') {
            $completedAt = null;
        }

        $stmt = $this->db->prepare(
            'UPDATE records SET title = :title, description = :description, customer_name = :customer_name,
             department = :department, assigned_to = :assigned_to, priority = :priority, sla_policy_id = :sla_policy_id,
             due_at = :due_at, completed_at = :completed_at, status = :status WHERE id = :id'
        );

        return $stmt->execute([
            'title'         => $data['title'],
            'description'   => $data['description'],
            'customer_name' => $data['customer_name'],
            'department'    => $data['department'],
            'assigned_to'   => $data['assigned_to'] ?: null,
            'priority'      => $data['priority'],
            'sla_policy_id' => $data['sla_policy_id'],
            'due_at'        => $dueAt,
            'completed_at'  => $completedAt,
            'status'        => $data['status'],
            'id'            => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM records WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Build a WHERE clause + params array from a filters array.
     * Only whitelisted keys are honoured.
     */
    private function buildFilters(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(r.reference_number LIKE :search1 OR r.customer_name LIKE :search2 OR r.title LIKE :search3)';
            $like = '%' . $filters['search'] . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }
        if (!empty($filters['department'])) {
            $where[] = 'r.department = :department';
            $params['department'] = $filters['department'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'r.assigned_to = :assigned_to';
            $params['assigned_to'] = $filters['assigned_to'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 'r.priority = :priority';
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['sla_policy_id'])) {
            $where[] = 'r.sla_policy_id = :sla_policy_id';
            $params['sla_policy_id'] = $filters['sla_policy_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(r.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(r.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['breached_only'])) {
            $where[] = "((r.status = 'Completed' AND r.completed_at > r.due_at) OR (r.status NOT IN ('Completed','Cancelled') AND r.due_at < NOW()))";
        }

        return [$where, $params];
    }

    public function paginate(array $filters, int $page = 1, int $perPage = RECORDS_PER_PAGE): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM records r {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = self::BASE_SELECT . " {$whereSql} ORDER BY r.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function all(array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->db->prepare(self::BASE_SELECT . " {$whereSql} ORDER BY r.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        return $this->db->query(self::BASE_SELECT . " ORDER BY r.created_at DESC LIMIT {$limit}")->fetchAll();
    }

    public function latestBreaches(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        $sql = self::BASE_SELECT . "
            WHERE (r.status NOT IN ('Completed','Cancelled') AND r.due_at < NOW())
               OR (r.status = 'Completed' AND r.completed_at > r.due_at)
            ORDER BY r.due_at DESC
            LIMIT {$limit}
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function distinctDepartments(): array
    {
        return $this->db->query('SELECT DISTINCT department FROM records WHERE department IS NOT NULL AND department != "" ORDER BY department ASC')->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Compute the live SLA state for a record: color, label, seconds remaining.
     * Used both server-side (initial render) and mirrored client-side (JS ticking).
     */
    public static function computeState(array $record): array
    {
        $now = time();
        $due = strtotime($record['due_at']);
        $warningSeconds = ((int) ($record['sla_warning_minutes'] ?? 0)) * 60;

        if ($record['status'] === 'Cancelled') {
            return ['color' => 'gray', 'label' => 'Cancelled', 'seconds' => null];
        }

        if ($record['status'] === 'Completed') {
            $completed = strtotime($record['completed_at']);
            $onTime = $completed <= $due;
            return [
                'color' => 'gray',
                'label' => $onTime ? 'Completed (On Time)' : 'Completed (Late)',
                'seconds' => null,
            ];
        }

        $diff = $due - $now;

        if ($diff < 0) {
            return ['color' => 'red', 'label' => 'Breached', 'seconds' => $diff];
        }

        if ($warningSeconds > 0 && $diff <= $warningSeconds) {
            return ['color' => 'yellow', 'label' => 'Approaching', 'seconds' => $diff];
        }

        return ['color' => 'green', 'label' => 'On Track', 'seconds' => $diff];
    }
}
