<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

$filters = [
    'search'        => input('search', ''),
    'department'    => input('department', ''),
    'assigned_to'   => input('assigned_to', ''),
    'status'        => input('status', ''),
    'priority'      => input('priority', ''),
    'sla_policy_id' => input('sla_policy_id', ''),
    'date_from'     => input('date_from', ''),
    'date_to'       => input('date_to', ''),
];

$records = (new RecordModel())->all($filters);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sla-records-' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Reference', 'Title', 'Customer', 'Department', 'Priority', 'Assigned To', 'SLA Policy', 'Status', 'Created At', 'Due At', 'Completed At', 'SLA Result']);

foreach ($records as $rec) {
    $state = RecordModel::computeState($rec);
    fputcsv($out, [
        csv_safe($rec['reference_number']),
        csv_safe($rec['title']),
        csv_safe($rec['customer_name']),
        csv_safe($rec['department']),
        csv_safe($rec['priority']),
        csv_safe($rec['assigned_to_name'] ?? ''),
        csv_safe($rec['sla_policy_name'] ?? ''),
        csv_safe($rec['status']),
        csv_safe($rec['created_at']),
        csv_safe($rec['due_at']),
        csv_safe($rec['completed_at'] ?? ''),
        csv_safe($state['label']),
    ]);
}

fclose($out);
exit;
