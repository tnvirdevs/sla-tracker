<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_check.php';
(new Settings())->applyTimezone();

$recordModel = new RecordModel();
$slaModel = new SlaPolicy();
$userModel = new User();

$filters = [
    'search'        => input('search', ''),
    'department'    => input('department', ''),
    'assigned_to'   => input('assigned_to', ''),
    'status'        => input('status', ''),
    'priority'      => input('priority', ''),
    'sla_policy_id' => input('sla_policy_id', ''),
    'date_from'     => input('date_from', ''),
    'date_to'       => input('date_to', ''),
];
$page = max(1, (int) input('page', 1));

$result = $recordModel->paginate($filters, $page);
$departments = $recordModel->distinctDepartments();
$activeUsers = $userModel->activeUsers();
$slaPolicies = $slaModel->all();

$pageTitle = 'Records';
$activeMenu = 'records';
include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Records</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0">Records</h4>
  <div class="d-flex gap-2">
    <a href="<?= e(BASE_URL) ?>/admin/export_csv.php?<?= e(http_build_query($filters)) ?>" class="btn btn-outline-success">
      <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-mode="create">
      <i class="bi bi-plus-lg"></i> New Record
    </button>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Search</label>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Reference, customer, title" value="<?= e($filters['search']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach (RecordModel::STATUSES as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Priority</label>
        <select name="priority" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach (RecordModel::PRIORITIES as $p): ?>
            <option value="<?= e($p) ?>" <?= $filters['priority'] === $p ? 'selected' : '' ?>><?= e($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Department</label>
        <select name="department" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= e($d) ?>" <?= $filters['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Assigned User</label>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($activeUsers as $u): ?>
            <option value="<?= (int) $u['id'] ?>" <?= (string) $filters['assigned_to'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">SLA Policy</label>
        <select name="sla_policy_id" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($slaPolicies as $sp): ?>
            <option value="<?= (int) $sp['id'] ?>" <?= (string) $filters['sla_policy_id'] === (string) $sp['id'] ? 'selected' : '' ?>><?= e($sp['policy_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
        <a href="<?= e(BASE_URL) ?>/records.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Reference</th>
          <th>Title</th>
          <th>Customer</th>
          <th>Department</th>
          <th>Priority</th>
          <th>Assigned</th>
          <th>SLA</th>
          <th>Status</th>
          <th>Time</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result['data']): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">No records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['data'] as $rec): $state = RecordModel::computeState($rec); ?>
          <tr>
            <td><a href="<?= e(BASE_URL) ?>/admin/record_view.php?id=<?= (int) $rec['id'] ?>" class="fw-semibold"><?= e($rec['reference_number']) ?></a></td>
            <td><?= e($rec['title']) ?></td>
            <td><?= e($rec['customer_name']) ?></td>
            <td><?= e($rec['department']) ?></td>
            <td><span class="badge <?= priority_badge_class($rec['priority']) ?>"><?= e($rec['priority']) ?></span></td>
            <td><?= e($rec['assigned_to_name'] ?? '—') ?></td>
            <td><?= e($rec['sla_policy_name'] ?? '—') ?></td>
            <td><span class="badge <?= status_badge_class($rec['status']) ?>"><?= e($rec['status']) ?></span></td>
            <td>
              <?php if ($state['seconds'] !== null): ?>
                <span class="sla-timer badge bg-<?= sla_color_class($state['color']) ?>" data-due="<?= (int) strtotime($rec['due_at']) ?>" data-warning="<?= (int) (($rec['sla_warning_minutes'] ?? 0) * 60) ?>">
                  <?= $state['seconds'] < 0 ? 'Overdue ' . e(format_duration($state['seconds'])) : 'Remaining ' . e(format_duration($state['seconds'])) ?>
                </span>
              <?php else: ?>
                <span class="badge bg-<?= sla_color_class($state['color']) ?>"><?= e($state['label']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-record"
                data-id="<?= (int) $rec['id'] ?>"
                data-title="<?= e($rec['title']) ?>"
                data-description="<?= e($rec['description']) ?>"
                data-customer_name="<?= e($rec['customer_name']) ?>"
                data-department="<?= e($rec['department']) ?>"
                data-assigned_to="<?= (int) $rec['assigned_to'] ?>"
                data-priority="<?= e($rec['priority']) ?>"
                data-sla_policy_id="<?= (int) $rec['sla_policy_id'] ?>"
                data-status="<?= e($rec['status']) ?>"
                data-bs-toggle="modal" data-bs-target="#recordModal">
                <i class="bi bi-pencil"></i>
              </button>
              <a href="<?= e(BASE_URL) ?>/admin/record_view.php?id=<?= (int) $rec['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
              <?php if (Auth::isAdmin()): ?>
              <form action="<?= e(BASE_URL) ?>/admin/record_delete.php" method="post" class="d-inline confirm-delete" data-message="Delete record '<?= e($rec['reference_number']) ?>'?">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $rec['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">
    <?= pagination_links($result['page'], $result['total_pages'], $filters) ?>
    <div class="text-center text-muted small mt-2"><?= (int) $result['total'] ?> total record(s)</div>
  </div>
</div>

<!-- Record Modal -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?= e(BASE_URL) ?>/admin/record_save.php" method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="rec_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="recordModalTitle">New Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="rec_title" class="form-control" required maxlength="150">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Customer</label>
            <input type="text" name="customer_name" id="rec_customer_name" class="form-control" required maxlength="150">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" id="rec_description" class="form-control" rows="2"></textarea>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" id="rec_department" class="form-control" list="departmentList" maxlength="100" required>
            <datalist id="departmentList">
              <?php foreach ($departments as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?>
            </datalist>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Priority</label>
            <select name="priority" id="rec_priority" class="form-select" required>
              <?php foreach (RecordModel::PRIORITIES as $p): ?>
                <option value="<?= e($p) ?>"><?= e($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Assigned User</label>
            <select name="assigned_to" id="rec_assigned_to" class="form-select">
              <option value="">Unassigned</option>
              <?php foreach ($activeUsers as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= e($u['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">SLA Policy</label>
            <select name="sla_policy_id" id="rec_sla_policy_id" class="form-select" required>
              <?php foreach ($slaPolicies as $sp): ?>
                <option value="<?= (int) $sp['id'] ?>"><?= e($sp['policy_name']) ?> (<?= (int) $sp['hours'] ?>h <?= (int) $sp['minutes'] ?>m)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-text" id="rec_sla_help">Changing the SLA on an existing record recalculates its due date from the original creation time.</div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="rec_status" class="form-select" required>
              <?php foreach (RecordModel::STATUSES as $s): ?>
                <option value="<?= e($s) ?>"><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Record</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScripts = '<script>
var recordModalEl = document.getElementById("recordModal");
recordModalEl.addEventListener("show.bs.modal", function (event) {
  var button = event.relatedTarget;
  var isEdit = button.classList.contains("btn-edit-record");
  var title = document.getElementById("recordModalTitle");
  if (!isEdit) {
    title.textContent = "New Record";
    document.getElementById("rec_id").value = "";
    document.getElementById("rec_title").value = "";
    document.getElementById("rec_description").value = "";
    document.getElementById("rec_customer_name").value = "";
    document.getElementById("rec_department").value = "";
    document.getElementById("rec_priority").value = "Medium";
    document.getElementById("rec_assigned_to").value = "";
    document.getElementById("rec_sla_policy_id").selectedIndex = 0;
    document.getElementById("rec_status").value = "Open";
  } else {
    title.textContent = "Edit Record";
    document.getElementById("rec_id").value = button.dataset.id;
    document.getElementById("rec_title").value = button.dataset.title;
    document.getElementById("rec_description").value = button.dataset.description;
    document.getElementById("rec_customer_name").value = button.dataset.customer_name;
    document.getElementById("rec_department").value = button.dataset.department;
    document.getElementById("rec_priority").value = button.dataset.priority;
    document.getElementById("rec_assigned_to").value = button.dataset.assigned_to || "";
    document.getElementById("rec_sla_policy_id").value = button.dataset.sla_policy_id;
    document.getElementById("rec_status").value = button.dataset.status;
  }
});
</script>';
include __DIR__ . '/includes/footer.php';
?>
