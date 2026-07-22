<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);

if ($id === Auth::id()) {
    set_flash('danger', 'You cannot delete your own account.');
    redirect('users.php');
}

if ($id > 0) {
    (new User())->delete($id);
    set_flash('success', 'User deleted.');
}

redirect('users.php');
