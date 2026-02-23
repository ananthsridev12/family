<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Users</h1>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card card-body shadow-sm mb-4">
  <h2 class="h6">Create User</h2>
  <form method="post" action="/index.php?route=admin/users" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="action" value="create">

    <div class="col-md-4">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" type="email" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Username (optional)</label>
      <input class="form-control" name="username">
    </div>
    <div class="col-md-4">
      <label class="form-label">Password</label>
      <input class="form-control" name="password" type="password" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Role</label>
      <select class="form-select" name="role">
        <option value="limited_member">Limited Member</option>
        <option value="full_editor">Full Editor</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
        <label class="form-check-label" for="is_active">Active</label>
      </div>
    </div>
    <div class="col-12">
      <button class="btn btn-primary" type="submit">Create User</button>
    </div>
  </form>
</div>

<div class="card card-body shadow-sm">
  <h2 class="h6">Existing Users</h2>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $row): ?>
        <?php $isSelf = (int)($row['user_id'] ?? 0) === (int)(app_user()['user_id'] ?? 0); ?>
        <?php $formId = 'update-user-' . (int)$row['user_id']; ?>
        <tr>
          <td><?= (int)$row['user_id'] ?></td>
          <td><?= htmlspecialchars((string)($row['name'] ?? $row['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php $role = (string)($row['role'] ?? 'limited_member'); ?>
            <select class="form-select form-select-sm" name="role" form="<?= $formId ?>" <?= $isSelf ? 'disabled' : '' ?>>
              <option value="limited_member" <?= $role === 'limited_member' ? 'selected' : '' ?>>Limited Member</option>
              <option value="full_editor" <?= $role === 'full_editor' ? 'selected' : '' ?>>Full Editor</option>
              <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
          </td>
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" form="<?= $formId ?>" <?= (int)($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
              <label class="form-check-label">Active</label>
            </div>
          </td>
          <td>
            <form method="post" action="/index.php?route=admin/users" id="<?= $formId ?>" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
              <button class="btn btn-sm btn-outline-primary" type="submit" <?= $isSelf ? 'disabled' : '' ?>>Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
