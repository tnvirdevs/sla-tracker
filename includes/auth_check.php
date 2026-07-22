<?php
/**
 * auth_check.php
 * Include at the top of every protected page (after config.php) to
 * enforce authentication. Optionally set $requiredRoles before including
 * this file to additionally restrict by role.
 */

if (!isset($requiredRoles)) {
    Auth::requireLogin();
} else {
    Auth::requireRole($requiredRoles);
}
