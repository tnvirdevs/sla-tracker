<?php
require_once __DIR__ . '/config/config.php';
$requiredRoles = ['Administrator', 'Manager'];
require_once __DIR__ . '/includes/auth_check.php';
(new Settings())->applyTimezone();

$slaModel = new SlaPolicy();
$policies = $slaModel->all();

$pageTitle = 'SLA Policies';
$activeMenu = 'sla';
include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">SLA Policies</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">SLA Policies</h4>
  <?php if (Auth::isAdmin()): ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#slaModal" data-mode="create">
    <i class="bi bi-plus-lg"></i> New SLA Policy
  </button>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Policy Name</th>
          <th>Description</th>
          <th>Duration</th>
          <th>Warning</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$policies): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No SLA policies created yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($policies as $p): ?>
          <tr>
            <td class="fw-semibold"><?= e($p['policy_name']) ?></td>
            <td class="text-muted small"><?= e($p['description']) ?></td>
            <td><?= (int) $p['hours'] ?>h <?= (int) $p['minutes'] ?>m</td>
            <td><?= (int) $p['warning_before_minutes'] ?> min before due</td>
            <td>
              <?php if ((int) $p['status'] === 1): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if (Auth::isAdmin()): ?>
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-sla"
                data-id="<?= (int) $p['id'] ?>"
                data-policy_name="<?= e($p['policy_name']) ?>"
                data-description="<?= e($p['description']) ?>"
                data-hours="<?= (int) $p['hours'] ?>"
                data-minutes="<?= (int) $p['minutes'] ?>"
                data-warning_before_minutes="<?= (int) $p['warning_before_minutes'] ?>"
                data-status="<?= (int) $p['status'] ?>"
                data-bs-toggle="modal" data-bs-target="#slaModal">
                <i class="bi bi-pencil"></i>
              </button>
              <form action="<?= e(BASE_URL) ?>/admin/sla_toggle.php" method="post" class="d-inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Activate/Deactivate">
                  <i class="bi bi-toggle2-<?= (int) $p['status'] === 1 ? 'on' : 'off' ?>"></i>
                </button>
              </form>
              <form action="<?= e(BASE_URL) ?>/admin/sla_delete.php" method="post" class="d-inline confirm-delete" data-message="Delete SLA policy '<?= e($p['policy_name']) ?>'?">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SLA Modal -->
<div class="modal fade" id="slaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="<?= e(BASE_URL) ?>/admin/sla_save.php" method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="sla_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="slaModalTitle">New SLA Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Policy Name</label>
          <input type="text" name="policy_name" id="sla_policy_name" class="form-control" required maxlength="100">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" id="sla_description" class="form-control" rows="2"></textarea>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Hours</label>
            <input type="number" name="hours" id="sla_hours" class="form-control" min="0" max="999" required value="0">
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Minutes</label>
            <input type="number" name="minutes" id="sla_minutes" class="form-control" min="0" max="59" required value="0">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Warning Before (minutes)</label>
          <input type="number" name="warning_before_minutes" id="sla_warning_before_minutes" class="form-control" min="0" max="999" required value="30">
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" id="sla_status" class="form-select">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Policy</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScripts = '<script>
document.getElementById("slaModal").addEventListener("show.bs.modal", function (event) {
  var button = event.relatedTarget;
  var mode = button.classList.contains("btn-edit-sla") ? "edit" : "create";
  var title = document.getElementById("slaModalTitle");
  if (mode === "create") {
    title.textContent = "New SLA Policy";
    document.getElementById("sla_id").value = "";
    document.getElementById("sla_policy_name").value = "";
    document.getElementById("sla_description").value = "";
    document.getElementById("sla_hours").value = 0;
    document.getElementById("sla_minutes").value = 0;
    document.getElementById("sla_warning_before_minutes").value = 30;
    document.getElementById("sla_status").value = "1";
  } else {
    title.textContent = "Edit SLA Policy";
    document.getElementById("sla_id").value = button.dataset.id;
    document.getElementById("sla_policy_name").value = button.dataset.policy_name;
    document.getElementById("sla_description").value = button.dataset.description;
    document.getElementById("sla_hours").value = button.dataset.hours;
    document.getElementById("sla_minutes").value = button.dataset.minutes;
    document.getElementById("sla_warning_before_minutes").value = button.dataset.warning_before_minutes;
    document.getElementById("sla_status").value = button.dataset.status;
  }
});
</script>';
include __DIR__ . '/includes/footer.php';
?>
