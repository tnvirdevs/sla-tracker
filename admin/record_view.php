<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
(new Settings())->applyTimezone();

$id = (int) input('id', 0);
$recordModel = new RecordModel();
$record = $id > 0 ? $recordModel->find($id) : null;

if (!$record) {
    http_response_code(404);
    set_flash('danger', 'Record not found.');
    redirect('records.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'add_remark') {
    Csrf::verifyRequest();
    $remark = trim((string) input('remark', ''));
    if ($remark !== '') {
        (new ActivityLog())->log($id, Auth::id(), 'Comment', $remark);
        set_flash('success', 'Remark added.');
    }
    redirect('admin/record_view.php?id=' . $id);
}

$activity = (new ActivityLog())->forRecord($id);
$state = RecordModel::computeState($record);

$pageTitle = 'Record: ' . $record['reference_number'];
$activeMenu = 'records';
include __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/records.php">Records</a></li>
    <li class="breadcrumb-item active"><?= e($record['reference_number']) ?></li>
  </ol>
</nav>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= e($record['title']) ?></h5>
        <span class="badge <?= status_badge_class($record['status']) ?> fs-6"><?= e($record['status']) ?></span>
      </div>
      <div class="card-body">
        <p class="text-muted"><?= nl2br(e($record['description'])) ?: '<em>No description provided.</em>' ?></p>
        <dl class="row mb-0">
          <dt class="col-sm-4">Reference</dt><dd class="col-sm-8"><?= e($record['reference_number']) ?></dd>
          <dt class="col-sm-4">Customer</dt><dd class="col-sm-8"><?= e($record['customer_name']) ?></dd>
          <dt class="col-sm-4">Department</dt><dd class="col-sm-8"><?= e($record['department']) ?></dd>
          <dt class="col-sm-4">Priority</dt><dd class="col-sm-8"><span class="badge <?= priority_badge_class($record['priority']) ?>"><?= e($record['priority']) ?></span></dd>
          <dt class="col-sm-4">Assigned To</dt><dd class="col-sm-8"><?= e($record['assigned_to_name'] ?? 'Unassigned') ?></dd>
          <dt class="col-sm-4">SLA Policy</dt><dd class="col-sm-8"><?= e($record['sla_policy_name']) ?> (<?= (int) $record['sla_hours'] ?>h <?= (int) $record['sla_minutes'] ?>m)</dd>
          <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?= format_datetime($record['created_at']) ?></dd>
          <dt class="col-sm-4">Due</dt><dd class="col-sm-8"><?= format_datetime($record['due_at']) ?></dd>
          <dt class="col-sm-4">Completed</dt><dd class="col-sm-8"><?= format_datetime($record['completed_at']) ?></dd>
        </dl>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white">Activity Log</div>
      <ul class="list-group list-group-flush">
        <?php if (!$activity): ?>
          <li class="list-group-item text-muted">No activity recorded yet.</li>
        <?php endif; ?>
        <?php foreach ($activity as $log): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <strong><?= e($log['action']) ?></strong>
              <span class="text-muted small"><?= format_datetime($log['created_at']) ?></span>
            </div>
            <?php if ($log['remarks']): ?><div class="small"><?= e($log['remarks']) ?></div><?php endif; ?>
            <div class="text-muted small">by <?= e($log['user_name'] ?? 'System') ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="card-body">
        <form method="post" class="d-flex gap-2">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="add_remark">
          <input type="text" name="remark" class="form-control" placeholder="Add a remark..." required maxlength="255">
          <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i></button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm text-center">
      <div class="card-body">
        <div class="text-muted small mb-1">SLA Status</div>
        <?php if ($state['seconds'] !== null): ?>
          <div class="display-6 fw-bold text-<?= sla_color_class($state['color']) ?> sla-timer" data-due="<?= (int) strtotime($record['due_at']) ?>" data-warning="<?= (int) ($record['sla_warning_minutes'] * 60) ?>" data-record-timer="1">
            <?= $state['seconds'] < 0 ? 'Overdue ' . e(format_duration($state['seconds'])) : 'Remaining ' . e(format_duration($state['seconds'])) ?>
          </div>
        <?php else: ?>
          <div class="display-6 fw-bold text-<?= sla_color_class($state['color']) ?>"><?= e($state['label']) ?></div>
        <?php endif; ?>
        <div class="mt-3">
          <span class="badge bg-<?= sla_color_class($state['color']) ?> fs-6"><?= e($state['label']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
