<?php
require_once __DIR__ . '/config/config.php';

if (Auth::check()) {
    (new ActivityLog())->log(null, Auth::id(), 'Logout', 'User logged out.');
}

Auth::logout();
redirect('login.php');
