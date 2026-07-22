<?php
/**
 * header.php
 * Top of the HTML document for all authenticated pages.
 * Expects optional $pageTitle and $activeMenu to be set before include.
 */
$settingsObj = new Settings();
$appSettings = $settingsObj->get();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> &mdash; <?= e($appSettings['site_name']) ?></title>
<link rel="icon" href="data:,">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="app-main">
<nav class="topbar navbar navbar-expand navbar-light bg-white border-bottom px-3">
  <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>
  <span class="navbar-text ms-2 fw-semibold d-none d-sm-inline"><?= e($appSettings['company_name'] ?: $appSettings['site_name']) ?></span>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="text-muted small d-none d-md-inline"><i class="bi bi-clock"></i> <span id="liveClock"></span></span>
    <div class="dropdown">
      <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle fs-5"></i>
        <span><?= e($currentUser['full_name'] ?? 'User') ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text text-muted small"><?= e($currentUser['role'] ?? '') ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="app-content p-3 p-md-4">
<?php $flash = get_flash(); if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
