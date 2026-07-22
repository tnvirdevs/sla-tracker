<?php
require_once __DIR__ . '/../config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('settings.php');
}
Csrf::verifyRequest();

$data = [
    'site_name'    => str_limit(trim((string) input('site_name', '')), 100),
    'company_name' => str_limit(trim((string) input('company_name', '')), 150),
    'timezone'     => input('timezone', 'UTC'),
];

if ($data['site_name'] === '' || !in_array($data['timezone'], DateTimeZone::listIdentifiers(), true)) {
    set_flash('danger', 'Please provide a valid site name and timezone.');
    redirect('settings.php');
}

try {
    (new Settings())->update($data);
    set_flash('success', 'Settings updated successfully.');
} catch (Throwable $e) {
    error_log('settings_save.php: ' . $e->getMessage());
    set_flash('danger', 'Unable to save settings.');
}

redirect('settings.php');
