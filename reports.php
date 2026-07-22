<?php
require_once __DIR__ . '/config/config.php';
$requiredRoles = ['Administrator', 'Manager'];
require_once __DIR__ . '/includes/auth_check.php';
(new Settings())->applyTimezone();

$report = new Report();

$compliance   = $report->complianceRate();
$breached     = $report->breachedRecords();
$completed    = $report->completedRecords();
$avgSeconds   = $report->averageCompletionSeconds();
$deptPerf     = $report->departmentPerformance();
$userPerf     = $report->userPerformance();
$monthly      = $report->monthlyStatistics(6);
$statusBreak  = $report->statusBreakdown();

$monthlyLabels = array_map(fn($m) => date('M Y', strtotime($m['ym'] . '-01')), $monthly);
$monthlyTotals = array_map(fn($m) => (int) $m['total'], $monthly);
$monthlyCompleted = array_map(fn($m) => (int) $m['completed'], $monthly);
$monthlyBreached = array_map(fn($m) => (int) $m['breached'], $monthly);

$deptLabels = array_column($deptPerf, 'department');
$deptTotals = array_map('intval', array_column($deptPerf, 'total'));
$deptBreached = array_map('intval', array_column($deptPerf, 'breached'));

$statusLabels = array_column($statusBreak, 'status');
$statusTotals = array_map('intval', array_column($statusBreak, 'total'));

$pageTitle = 'Reports';
$activeMenu = 'reports';
include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Reports</li>
  </ol>
</nav>

<h4 class="mb-3">Reports &amp; Compliance</h4>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body">
      <div class="text-muted small">SLA Compliance %</div>
      <div class="fs-3 fw-bold text-success"><?= e(number_format($compliance, 1)) ?>%</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body">
      <div class="text-muted small">Breached Records</div>
      <div class="fs-3 fw-bold text-danger"><?= (int) $breached ?></div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body">
      <div class="text-muted small">Completed Records</div>
      <div class="fs-3 fw-bold"><?= (int) $completed ?></div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body">
      <div class="text-muted small">Avg. Completion Time</div>
      <div class="fs-5 fw-bold"><?= e(format_duration($avgSeconds)) ?></div>
    </div></div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm"><div class="card-header bg-white">Monthly Statistics</div>
      <div class="card-body"><canvas id="monthlyChart" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm"><div class="card-header bg-white">Status Breakdown</div>
      <div class="card-body"><canvas id="statusChart" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm"><div class="card-header bg-white">Department Performance</div>
      <div class="card-body"><canvas id="deptChart" height="180"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Department Performance</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Department</th><th>Total</th><th>Completed</th><th>Breached</th></tr></thead>
          <tbody>
          <?php if (!$deptPerf): ?><tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr><?php endif; ?>
          <?php foreach ($deptPerf as $d): ?>
            <tr><td><?= e($d['department']) ?></td><td><?= (int) $d['total'] ?></td><td><?= (int) $d['completed'] ?></td><td class="text-danger"><?= (int) $d['breached'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">User Performance</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>User</th><th>Total</th><th>Completed</th><th>Breached</th></tr></thead>
          <tbody>
          <?php if (!$userPerf): ?><tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr><?php endif; ?>
          <?php foreach ($userPerf as $u): ?>
            <tr><td><?= e($u['full_name']) ?></td><td><?= (int) $u['total'] ?></td><td><?= (int) $u['completed'] ?></td><td class="text-danger"><?= (int) $u['breached'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$chartData = [
    'monthlyLabels'    => $monthlyLabels,
    'monthlyTotals'    => $monthlyTotals,
    'monthlyCompleted' => $monthlyCompleted,
    'monthlyBreached'  => $monthlyBreached,
    'deptLabels'       => $deptLabels,
    'deptTotals'       => $deptTotals,
    'deptBreached'     => $deptBreached,
    'statusLabels'     => $statusLabels,
    'statusTotals'     => $statusTotals,
];
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
var chartData = ' . json_encode($chartData) . ';

new Chart(document.getElementById("monthlyChart"), {
  type: "line",
  data: {
    labels: chartData.monthlyLabels,
    datasets: [
      { label: "Total", data: chartData.monthlyTotals, borderColor: "#0d6efd", tension: 0.3 },
      { label: "Completed", data: chartData.monthlyCompleted, borderColor: "#198754", tension: 0.3 },
      { label: "Breached", data: chartData.monthlyBreached, borderColor: "#dc3545", tension: 0.3 }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});

new Chart(document.getElementById("statusChart"), {
  type: "doughnut",
  data: {
    labels: chartData.statusLabels,
    datasets: [{ data: chartData.statusTotals, backgroundColor: ["#0d6efd", "#0dcaf0", "#6c757d", "#212529"] }]
  },
  options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});

new Chart(document.getElementById("deptChart"), {
  type: "bar",
  data: {
    labels: chartData.deptLabels,
    datasets: [
      { label: "Total", data: chartData.deptTotals, backgroundColor: "#0d6efd" },
      { label: "Breached", data: chartData.deptBreached, backgroundColor: "#dc3545" }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});
</script>';
include __DIR__ . '/includes/footer.php';
?>
