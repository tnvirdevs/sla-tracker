<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('sla.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);
if ($id > 0) {
    (new SlaPolicy())->toggleStatus($id);
    set_flash('success', 'SLA policy status updated.');
}

redirect('sla.php');
