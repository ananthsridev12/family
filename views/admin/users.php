<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<style>
.scope-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:600;
               padding:.25rem .65rem; border-radius:999px; }
.scope-none  { background:#fee2e2; color:#991b1b; }
.scope-self  { background:#fef3c7; color:#92400e; }
.scope-children { background:#dbeafe; color:#1e40af; }
.scope-grandchildren { background:#ede9fe; color:#5b21b6; }
.scope-all   { background:#d1fae5; color:#065f46; }
</style>

<div class="page-header">
  <h1>Users</h1>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Create user -->
<div class="card mb-4">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Create User</h6>
    <form method="post" action="/index.php?route=admin/users" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="create">
      <div class="col-md-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" placeholder="optional">
      </div>
      <div class="col-md-2">
        <label class="form-label">Password</label>
        <input class="form-control" name="password" type="password" required>
      </div>
      <div class="col-md-2 position-relative">
        <label class="form-label">Link Person</label>
        <input class="form-control person-search" data-target="person_id_new" data-results="person_results_new" placeholder="Search…">
        <input type="hidden" name="person_id" id="person_id_new">
        <div id="person_results_new" class="list-group position-absolute w-100" style="z-index:50;"></div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Role</label>
        <select class="form-select" name="role">
          <option value="limited_member">Limited Member</option>
          <option value="full_editor">Full Editor</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" checked>
          <label class="form-check-label">Active</label>
        </div>
      </div>
      <div class="col-12">
        <button class="btn btn-primary btn-sm btn-pill" type="submit">Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- Users table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Linked Person</th>
            <th>Edit Access</th>
            <th>Can Add</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($rows ?? []) as $row):
            $isSelf   = (int)($row['user_id'] ?? 0) === (int)(app_user()['user_id'] ?? 0);
            $linkedId = (int)($row['person_id'] ?? 0);
            $linkedName = $linkedId > 0 ? (string)($person_map[$linkedId] ?? '#'.$linkedId) : '—';
            $fid = 'uf-'.(int)$row['user_id'];
            $sid = 'ps-'.(int)$row['user_id'];
            $hid = 'ph-'.(int)$row['user_id'];
            $rid = 'pr-'.(int)$row['user_id'];
            $role = (string)($row['role'] ?? 'limited_member');
            $permsRaw = (string)($row['permissions'] ?? '{}');
            $perms = json_decode($permsRaw, true);
            if (!is_array($perms)) $perms = [];
            $editScope = (string)($perms['edit_scope'] ?? 'self');
            $canAdd = (bool)($perms['can_add_person'] ?? true);
            $scopeLabels = ['none'=>'None','self'=>'Own only','children'=>'+ Children','grandchildren'=>'+ Grandchildren','all'=>'All'];
            $scopeLabel = $scopeLabels[$editScope] ?? $editScope;
          ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars((string)($row['name'] ?? $row['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.75rem;color:var(--ft-muted);">#<?= (int)$row['user_id'] ?></div>
            </td>
            <td style="font-size:.85rem;"><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <select class="form-select form-select-sm" name="role" form="<?= $fid ?>" <?= $isSelf ? 'disabled' : '' ?>>
                <option value="limited_member" <?= $role === 'limited_member' ? 'selected' : '' ?>>Limited</option>
                <option value="full_editor"    <?= $role === 'full_editor'    ? 'selected' : '' ?>>Full Editor</option>
                <option value="admin"          <?= $role === 'admin'          ? 'selected' : '' ?>>Admin</option>
              </select>
            </td>
            <td class="position-relative" style="min-width:180px;">
              <input class="form-control form-control-sm person-search" id="<?= $sid ?>"
                     data-target="<?= $hid ?>" data-results="<?= $rid ?>"
                     value="<?= $linkedId > 0 ? htmlspecialchars($linkedName . ' (#'.$linkedId.')', ENT_QUOTES, 'UTF-8') : '' ?>"
                     placeholder="Search…" <?= $isSelf ? 'disabled' : '' ?>>
              <input type="hidden" name="person_id" id="<?= $hid ?>" form="<?= $fid ?>" value="<?= $linkedId > 0 ? $linkedId : '' ?>">
              <div id="<?= $rid ?>" class="list-group position-absolute w-100" style="z-index:50;"></div>
            </td>
            <td>
              <?php if ($isSelf || in_array($role, ['admin','full_editor'], true)): ?>
                <span class="scope-badge scope-all">Full</span>
              <?php else: ?>
                <span class="scope-badge scope-<?= htmlspecialchars($editScope, ENT_QUOTES) ?>"><?= $scopeLabel ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isSelf || in_array($role, ['admin','full_editor'], true)): ?>
                <span class="badge bg-success">Yes</span>
              <?php else: ?>
                <span class="badge <?= $canAdd ? 'bg-success' : 'bg-secondary' ?>"><?= $canAdd ? 'Yes' : 'No' ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" form="<?= $fid ?>"
                       <?= (int)($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
              </div>
            </td>
            <td>
              <form method="post" action="/index.php?route=admin/users" id="<?= $fid ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
              </form>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-pill" type="submit" form="<?= $fid ?>" <?= $isSelf ? 'disabled' : '' ?>>Save</button>
                <?php if (!$isSelf): ?>
                <button class="btn btn-sm btn-outline-secondary btn-pill"
                        onclick="openPerms(<?= (int)$row['user_id'] ?>, '<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES) ?>', '<?= $editScope ?>', <?= $canAdd ? 'true' : 'false' ?>)">
                  Permissions
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/update-user-permissions">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="user_id" id="permUserId">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Permissions — <span id="permUserName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <div class="mb-4">
            <label class="form-label fw-bold">Edit access</label>
            <p class="text-muted mb-2" style="font-size:.82rem;">Which person records can this member edit?</p>
            <div class="d-flex flex-column gap-2" id="scopeOpts">
              <?php
              $scopeOpts = [
                'none'          => ['None — cannot edit any records', 'scope-none'],
                'self'          => ['Own profile only', 'scope-self'],
                'children'      => ['Own profile + direct children', 'scope-children'],
                'grandchildren' => ['Own profile + children + grandchildren', 'scope-grandchildren'],
                'all'           => ['All persons in the tree', 'scope-all'],
              ];
              foreach ($scopeOpts as $val => [$label, $cls]): ?>
              <label class="d-flex align-items-center gap-2 p-2 rounded" style="cursor:pointer;border:1px solid var(--ft-border);transition:background .1s;" onmouseover="this.style.background='var(--ft-bg)'" onmouseout="this.style.background=''">
                <input type="radio" name="edit_scope" value="<?= $val ?>" class="form-check-input m-0">
                <span class="scope-badge <?= $cls ?>"><?= $val === 'none' ? 'None' : ucfirst($val) ?></span>
                <span style="font-size:.85rem;"><?= $label ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label fw-bold">Submissions</label>
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" name="can_add_person" id="permCanAdd" value="1">
              <label class="form-check-label" for="permCanAdd">
                Can submit new persons for approval
              </label>
            </div>
            <div class="text-muted mt-1" style="font-size:.78rem;">Their submissions still go through the admin approval queue.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-pill">Save Permissions</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openPerms(uid, name, scope, canAdd) {
  document.getElementById('permUserId').value = uid;
  document.getElementById('permUserName').textContent = name;
  document.querySelectorAll('#permsModal [name="edit_scope"]').forEach(function(r) {
    r.checked = (r.value === scope);
  });
  document.getElementById('permCanAdd').checked = canAdd;
  new bootstrap.Modal(document.getElementById('permsModal')).show();
}

// Person search
(function() {
  function attachSearch(input, hidden, results) {
    var timer;
    input.addEventListener('input', function() {
      clearTimeout(timer);
      hidden.value = '';
      var q = input.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      timer = setTimeout(function() {
        fetch('/index.php?route=person/search&q=' + encodeURIComponent(q))
          .then(function(r) { return r.json(); })
          .then(function(data) {
            results.innerHTML = '';
            data.forEach(function(item) {
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'list-group-item list-group-item-action';
              btn.style.fontSize = '.83rem';
              btn.textContent = (item.full_name) + ' (#' + item.person_id + ')';
              btn.addEventListener('click', function() {
                input.value = item.full_name + ' (#' + item.person_id + ')';
                hidden.value = item.person_id;
                results.innerHTML = '';
              });
              results.appendChild(btn);
            });
          });
      }, 300);
    });
    document.addEventListener('click', function(e) {
      if (!results.contains(e.target) && e.target !== input) results.innerHTML = '';
    });
  }

  document.querySelectorAll('.person-search').forEach(function(input) {
    var hidden  = document.getElementById(input.dataset.target);
    var results = document.getElementById(input.dataset.results);
    if (hidden && results) attachSearch(input, hidden, results);
  });
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
