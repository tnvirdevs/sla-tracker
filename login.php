<?php
require_once __DIR__ . '/config/config.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

$error = '';
$timeout = isset($_GET['timeout']);
$throttle = new LoginThrottle();
$clientId = LoginThrottle::clientIdentifier();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedSeconds = $throttle->secondsUntilUnlocked($clientId);

    if ($lockedSeconds > 0) {
        $minutes = (int) ceil($lockedSeconds / 60);
        $error = "Too many failed login attempts. Please try again in about {$minutes} minute(s).";
    } elseif (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } elseif (Auth::attempt($username, $password)) {
            $throttle->clear($clientId);
            (new ActivityLog())->log(null, Auth::id(), 'Login', 'User logged in.');
            redirect('dashboard.php');
        } else {
            $throttle->registerFailure($clientId);
            $error = 'Invalid credentials or inactive account.';
        }
    }
}

$settingsObj = new Settings();
$appSettings = $settingsObj->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login &mdash; <?= e($appSettings['site_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body d-flex align-items-center justify-content-center vh-100">
  <div class="card login-card shadow-lg border-0">
    <div class="card-body p-4 p-md-5">
      <div class="text-center mb-4">
        <i class="bi bi-shield-check display-4 text-primary"></i>
        <h4 class="mt-2 mb-0"><?= e($appSettings['site_name']) ?></h4>
        <p class="text-muted small">SLA Management System</p>
      </div>

      <?php if ($timeout): ?>
        <div class="alert alert-warning py-2 small">Your session timed out. Please log in again.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label">Username or Email</label>
          <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
    </div>
  </div>
</body>
</html>
