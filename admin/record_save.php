<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('records.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);
$data = [
    'title'         => str_limit(trim((string) input('title', '')), 150),
    'description'   => trim((string) input('description', '')),
    'customer_name' => str_limit(trim((string) input('customer_name', '')), 150),
    'department'    => str_limit(trim((string) input('department', '')), 100),
    'assigned_to'   => input('assigned_to', '') !== '' ? (int) input('assigned_to', 0) : null,
    'priority'      => input('priority', 'Medium'),
    'sla_policy_id' => (int) input('sla_policy_id', 0),
    'status'        => input('status', 'Open'),
];

if ($data['title'] === '' || $data['customer_name'] === '' || $data['department'] === '' || $data['sla_policy_id'] <= 0) {
    set_flash('danger', 'Please fill in all required fields.');
    redirect('records.php');
}

if (!in_array($data['priority'], RecordModel::PRIORITIES, true)) {
    $data['priority'] = 'Medium';
}
if (!in_array($data['status'], RecordModel::STATUSES, true)) {
    $data['status'] = 'Open';
}

$recordModel = new RecordModel();
$activityLog = new ActivityLog();

try {
    if ($id > 0) {
        $before = $recordModel->find($id);
        $recordModel->update($id, $data);
        $remarks = 'Updated record details.';
        if ($before && $before['status'] !== $data['status']) {
            $remarks = "Status changed from {$before['status']} to {$data['status']}.";
        }
        $activityLog->log($id, Auth::id(), 'Updated', $remarks);
        set_flash('success', 'Record updated successfully.');
    } else {
        $data['reference_number'] = $recordModel->generateReference();
        $newId = $recordModel->create($data);
        $activityLog->log($newId, Auth::id(), 'Created', 'Record created with SLA policy assigned.');
        set_flash('success', 'Record created successfully. Reference: ' . $data['reference_number']);
    }
} catch (InvalidArgumentException $e) {
    // Safe, developer-authored validation message (e.g. "Invalid SLA policy
    // selected.") — fine to show to the user.
    set_flash('danger', $e->getMessage());
} catch (Throwable $e) {
    // Never surface raw exception/database details (table names, SQL
    // state, file paths) to the end user — log server-side only.
    error_log('record_save.php: ' . $e->getMessage());
    set_flash('danger', 'Unable to save record. Please check your input and try again.');
}

redirect('records.php');
