<?php
require_once __DIR__ . '/config/config.php';
$requiredRoles = ['Administrator'];
require_once __DIR__ . '/includes/auth_check.php';
(new Settings())->applyTimezone();

$userModel = new User();
$users = $userModel->all();

$pageTitle = 'Users';
$activeMenu = 'users';
include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Users</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">User Management</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" data-mode="create">
    <i class="bi bi-plus-lg"></i> New User
  </button>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="fw-semibold"><?= e($u['full_name']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge bg-info text-dark"><?= e($u['role']) ?></span></td>
            <td>
              <?php if ((int) $u['status'] === 1): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= format_datetime($u['created_at'], 'M d, Y') ?></td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-user"
                data-id="<?= (int) $u['id'] ?>"
                data-full_name="<?= e($u['full_name']) ?>"
                data-username="<?= e($u['username']) ?>"
                data-email="<?= e($u['email']) ?>"
                data-role="<?= e($u['role']) ?>"
                data-status="<?= (int) $u['status'] ?>"
                data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="bi bi-pencil"></i>
              </button>
              <form action="<?= e(BASE_URL) ?>/admin/user_toggle.php" method="post" class="d-inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary" <?= (int) $u['id'] === Auth::id() ? 'disabled' : '' ?>>
                  <i class="bi bi-toggle2-<?= (int) $u['status'] === 1 ? 'on' : 'off' ?>"></i>
                </button>
              </form>
              <form action="<?= e(BASE_URL) ?>/admin/user_delete.php" method="post" class="d-inline confirm-delete" data-message="Delete user '<?= e($u['full_name']) ?>'?">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" <?= (int) $u['id'] === Auth::id() ? 'disabled' : '' ?>><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="<?= e(BASE_URL) ?>/admin/user_save.php" method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="user_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" id="user_full_name" class="form-control" required maxlength="100">
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" id="user_username" class="form-control" required maxlength="50" pattern="[A-Za-z0-9_\.]+">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="user_email" class="form-control" required maxlength="150">
        </div>
        <div class="mb-3">
          <label class="form-label">Password <span class="text-muted small" id="passwordHint">(leave blank to keep current)</span></label>
          <input type="password" name="password" id="user_password" class="form-control" minlength="8" autocomplete="new-password">
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Role</label>
            <select name="role" id="user_role" class="form-select">
              <?php foreach (User::ROLES as $r): ?>
                <option value="<?= e($r) ?>"><?= e($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="user_status" class="form-select">
              <option value="1">Active</option>
              <option value="0">Disabled</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save User</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScripts = '<script>
var userModalEl = document.getElementById("userModal");
userModalEl.addEventListener("show.bs.modal", function (event) {
  var button = event.relatedTarget;
  var isEdit = button.classList.contains("btn-edit-user");
  var title = document.getElementById("userModalTitle");
  var pwd = document.getElementById("user_password");
  if (!isEdit) {
    title.textContent = "New User";
    document.getElementById("user_id").value = "";
    document.getElementById("user_full_name").value = "";
    document.getElementById("user_username").value = "";
    document.getElementById("user_email").value = "";
    pwd.value = ""; pwd.required = true;
    document.getElementById("passwordHint").style.display = "none";
    document.getElementById("user_role").value = "User";
    document.getElementById("user_status").value = "1";
  } else {
    title.textContent = "Edit User";
    document.getElementById("user_id").value = button.dataset.id;
    document.getElementById("user_full_name").value = button.dataset.full_name;
    document.getElementById("user_username").value = button.dataset.username;
    document.getElementById("user_email").value = button.dataset.email;
    pwd.value = ""; pwd.required = false;
    document.getElementById("passwordHint").style.display = "inline";
    document.getElementById("user_role").value = button.dataset.role;
    document.getElementById("user_status").value = button.dataset.status;
  }
});
</script>';
include __DIR__ . '/includes/footer.php';
?>
