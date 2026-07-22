<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}
Csrf::verifyRequest();

$id = (int) input('id', 0);
$data = [
    'full_name' => str_limit(trim((string) input('full_name', '')), 100),
    'username'  => str_limit(trim((string) input('username', '')), 50),
    'email'     => str_limit(trim((string) input('email', '')), 150),
    'password'  => (string) input('password', ''),
    'role'      => input('role', 'User'),
    'status'    => (int) input('status', 1) === 1 ? 1 : 0,
];

if ($data['full_name'] === '' || $data['username'] === '' || $data['email'] === '') {
    set_flash('danger', 'Please fill in all required fields.');
    redirect('users.php');
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    redirect('users.php');
}

if (!in_array($data['role'], User::ROLES, true)) {
    $data['role'] = 'User';
}

$userModel = new User();

if ($userModel->usernameExists($data['username'], $id ?: null)) {
    set_flash('danger', 'That username is already taken.');
    redirect('users.php');
}
if ($userModel->emailExists($data['email'], $id ?: null)) {
    set_flash('danger', 'That email is already registered.');
    redirect('users.php');
}

try {
    if ($id > 0) {
        if ($id === Auth::id() && $data['role'] !== 'Administrator') {
            set_flash('danger', 'You cannot change your own role away from Administrator.');
            redirect('users.php');
        }
        $userModel->update($id, $data);
        set_flash('success', 'User updated successfully.');
    } else {
        if ($data['password'] === '' || strlen($data['password']) < 8) {
            set_flash('danger', 'Password must be at least 8 characters for new users.');
            redirect('users.php');
        }
        $userModel->create($data);
        set_flash('success', 'User created successfully.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    set_flash('danger', 'Unable to save user.');
}

redirect('users.php');
