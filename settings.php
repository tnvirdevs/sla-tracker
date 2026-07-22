<?php
require_once __DIR__ . '/config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/includes/auth_check.php';

$settingsObj = new Settings();
$appSettings = $settingsObj->get();

$timezones = DateTimeZone::listIdentifiers();

$pageTitle = 'Settings';
$activeMenu = 'settings';
include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Settings</li>
  </ol>
</nav>

<h4 class="mb-3">Application Settings</h4>

<div class="card border-0 shadow-sm" style="max-width: 640px;">
  <div class="card-body">
    <form method="post" action="<?= e(BASE_URL) ?>/admin/settings_save.php">
      <?= Csrf::field() ?>
      <div class="mb-3">
        <label class="form-label">Site Name</label>
        <input type="text" name="site_name" class="form-control" required maxlength="100" value="<?= e($appSettings['site_name']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Company Name</label>
        <input type="text" name="company_name" class="form-control" maxlength="150" value="<?= e($appSettings['company_name']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Timezone</label>
        <select name="timezone" class="form-select">
          <?php foreach ($timezones as $tz): ?>
            <option value="<?= e($tz) ?>" <?= $appSettings['timezone'] === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
