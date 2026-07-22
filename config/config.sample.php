<?php
/**
 * Application Configuration
 * SLA Tracker - Raw PHP
 *
 * Update the database credentials below after creating your database
 * and importing database.sql.
 */

// ---------------------------------------------------------------------
// Database credentials — UPDATE THESE for your hosting environment
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'sla_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// Application settings
// ---------------------------------------------------------------------
define('APP_NAME', 'SLA Tracker');
define('APP_VERSION', '1.0.0');

// Base URL of the application (no trailing slash).
// Example: https://example.com/sla-tracker
//
// The Host header is client-controlled, so it is validated against a strict
// hostname[:port] pattern before use (defense against Host header
// injection / poisoning). Anything that doesn't match falls back to
// SERVER_NAME (set by Apache from the vhost config, not the client).
$scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$rawHost    = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$host       = preg_match('/^[a-zA-Z0-9.\-]+(:\d{1,5})?$/', $rawHost) ? $rawHost : ($_SERVER['SERVER_NAME'] ?? 'localhost');
$scriptDir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
// If included from /admin/ subfolder, strip it so BASE_URL always points to app root.
if (substr($scriptDir, -6) === '/admin') {
    $scriptDir = substr($scriptDir, 0, -6);
}
define('BASE_URL', $scheme . $host . $scriptDir);

// Session lifetime in seconds (auto logout after inactivity)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Default timezone (overridden by value stored in settings table once loaded)
define('DEFAULT_TIMEZONE', 'UTC');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Pagination
define('RECORDS_PER_PAGE', 15);

// ---------------------------------------------------------------------
// Error reporting — disable display_errors in production
// ---------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Send PHP error logs to a folder that is blocked from direct HTTP access
// (storage/logs/.htaccess denies all requests) instead of leaving them at
// whatever default path the host provides, which is sometimes a
// world-readable file inside the web root (e.g. "error_log") that can be
// downloaded directly and leak file paths, query fragments, or stack traces.
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}

// ---------------------------------------------------------------------
// Secure session cookie parameters — must be set before session_start()
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('sla_tracker_sess');
    session_start();
}

// ---------------------------------------------------------------------
// Autoload core classes
// ---------------------------------------------------------------------
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../classes/' . $class . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/../includes/functions.php';
