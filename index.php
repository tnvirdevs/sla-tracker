<?php
require_once __DIR__ . '/config/config.php';

if (Auth::check()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
