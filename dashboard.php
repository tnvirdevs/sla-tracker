<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_check.php';
(new Settings())->applyTimezone();

$report = new Report();
$recordModel = new RecordModel();

$stats = [
    'policies'     => $report->totalPolicies(),
    'total'        => $report->totalRecords(),
    'active'       => $report->activeRecords(),
    'completed'    => $report->completedRecords(),
    'breached'     => $report->breachedRecords(),
    'due_today'    => $report->dueToday(),
    'compliance'   => $report->complianceRate(),
    'avg_seconds'  => $report->averageCompletionSeconds(),
];

$recentRecords = $recordModel->recent(6);
$latestBreaches = $recordModel->latestBreaches(6);

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<?php if (Auth::isAdmin() && $currentUser['username'] === 'admin'): ?>
  <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-shield-exclamation fs-5"></i>
    <div>
      <strong>Security reminder:</strong> you're logged in as the default <code>admin</code> account.
      For production use, create a personal administrator account from the
      <a href="<?= e(BASE_URL) ?>/users.php" class="alert-link">Users</a> page, sign in with it, then
      disable or rename this default account so it isn't a well-known target for attackers.
    </div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Dashboard</h4>
  <span class="text-muted small">Welcome back, <?= e($currentUser['full_name']) ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Total SLA Policies</div>
        <div class="fs-3 fw-bold"><?= (int) $stats['policies'] ?></div>
        <i class="bi bi-hourglass-split stat-icon text-primary"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Total Records</div>
        <div class="fs-3 fw-bold"><?= (int) $stats['total'] ?></div>
        <i class="bi bi-card-checklist stat-icon text-info"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Active Records</div>
        <div class="fs-3 fw-bold"><?= (int) $stats['active'] ?></div>
        <i class="bi bi-play-circle stat-icon text-primary"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Completed Records</div>
        <div class="fs-3 fw-bold"><?= (int) $stats['completed'] ?></div>
        <i class="bi bi-check-circle stat-icon text-secondary"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">SLA Breached</div>
        <div class="fs-3 fw-bold text-danger"><?= (int) $stats['breached'] ?></div>
        <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Due Today</div>
        <div class="fs-3 fw-bold text-warning"><?= (int) $stats['due_today'] ?></div>
        <i class="bi bi-calendar-event stat-icon text-warning"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">SLA Compliance %</div>
        <div class="fs-3 fw-bold text-success"><?= e(number_format($stats['compliance'], 1)) ?>%</div>
        <i class="bi bi-graph-up-arrow stat-icon text-success"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Avg. Completion Time</div>
        <div class="fs-5 fw-bold"><?= e(format_duration($stats['avg_seconds'])) ?></div>
        <i class="bi bi-stopwatch stat-icon text-info"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Recent Records</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>Reference</th><th>Title</th><th>Status</th><th>SLA</th></tr>
          </thead>
          <tbody>
            <?php if (!$recentRecords): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentRecords as $rec): $state = RecordModel::computeState($rec); ?>
              <tr>
                <td><a href="<?= e(BASE_URL) ?>/admin/record_view.php?id=<?= (int) $rec['id'] ?>"><?= e($rec['reference_number']) ?></a></td>
                <td><?= e($rec['title']) ?></td>
                <td><span class="badge <?= status_badge_class($rec['status']) ?>"><?= e($rec['status']) ?></span></td>
                <td><span class="badge bg-<?= sla_color_class($state['color']) ?>"><?= e($state['label']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-danger">Latest SLA Breaches</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>Reference</th><th>Title</th><th>Due</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (!$latestBreaches): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No SLA breaches. Great job!</td></tr>
            <?php endif; ?>
            <?php foreach ($latestBreaches as $rec): ?>
              <tr>
                <td><a href="<?= e(BASE_URL) ?>/admin/record_view.php?id=<?= (int) $rec['id'] ?>"><?= e($rec['reference_number']) ?></a></td>
                <td><?= e($rec['title']) ?></td>
                <td><?= format_datetime($rec['due_at']) ?></td>
                <td><span class="badge <?= status_badge_class($rec['status']) ?>"><?= e($rec['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
