<?php
$activeMenu = $activeMenu ?? '';
$menuItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => 'dashboard.php', 'roles' => ['Administrator', 'Manager', 'User']],
    'records'   => ['label' => 'Records', 'icon' => 'bi-card-checklist', 'href' => 'records.php', 'roles' => ['Administrator', 'Manager', 'User']],
    'sla'       => ['label' => 'SLA Policies', 'icon' => 'bi-hourglass-split', 'href' => 'sla.php', 'roles' => ['Administrator', 'Manager']],
    'reports'   => ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'href' => 'reports.php', 'roles' => ['Administrator', 'Manager']],
    'users'     => ['label' => 'Users', 'icon' => 'bi-people', 'href' => 'users.php', 'roles' => ['Administrator']],
    'settings'  => ['label' => 'Settings', 'icon' => 'bi-gear', 'href' => 'settings.php', 'roles' => ['Administrator']],
];
$role = Auth::role();
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-brand">
    <i class="bi bi-shield-check fs-4"></i>
    <span><?= e($appSettings['site_name'] ?? APP_NAME) ?></span>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($menuItems as $key => $item): ?>
      <?php if (!in_array($role, $item['roles'], true)) { continue; } ?>
      <a href="<?= e(BASE_URL) ?>/<?= e($item['href']) ?>" class="sidebar-link <?= $activeMenu === $key ? 'active' : '' ?>">
        <i class="bi <?= e($item['icon']) ?>"></i>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer text-center small text-muted">
    v<?= e(APP_VERSION) ?>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
