<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('sla.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);
$data = [
    'policy_name'            => str_limit(trim((string) input('policy_name', '')), 100),
    'description'            => trim((string) input('description', '')),
    'hours'                  => max(0, (int) input('hours', 0)),
    'minutes'                => max(0, min(59, (int) input('minutes', 0))),
    'warning_before_minutes' => max(0, (int) input('warning_before_minutes', 0)),
    'status'                 => (int) input('status', 1) === 1 ? 1 : 0,
];

if ($data['policy_name'] === '') {
    set_flash('danger', 'Policy name is required.');
    redirect('sla.php');
}

if ($data['hours'] === 0 && $data['minutes'] === 0) {
    set_flash('danger', 'SLA duration must be greater than zero.');
    redirect('sla.php');
}

$slaModel = new SlaPolicy();

try {
    if ($id > 0) {
        $slaModel->update($id, $data);
        set_flash('success', 'SLA policy updated successfully.');
    } else {
        $slaModel->create($data);
        set_flash('success', 'SLA policy created successfully.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    set_flash('danger', 'Unable to save SLA policy.');
}

redirect('sla.php');
