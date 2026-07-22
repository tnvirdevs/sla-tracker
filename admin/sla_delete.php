<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('sla.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);
$slaModel = new SlaPolicy();

if ($id > 0) {
    if ($slaModel->isInUse($id)) {
        set_flash('danger', 'This SLA policy is assigned to existing records and cannot be deleted. Deactivate it instead.');
    } else {
        $slaModel->delete($id);
        set_flash('success', 'SLA policy deleted.');
    }
}

redirect('sla.php');
