<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Family List</h1>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Gender</th>
        <th>Age</th>
        <th>Birth Year</th>
        <th>Relationship</th>
        <th>Father</th>
        <th>Mother</th>
        <th>Spouse</th>
        <th>Birth Order</th>
        <th>Created By</th>
        <th>Locked</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $item): ?>
      <tr>
        <td><?= (int)$item['person_id'] ?></td>
        <td>
          <a href="/index.php?route=admin/person-view&id=<?= (int)$item['person_id'] ?>">
            <?= htmlspecialchars((string)$item['full_name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </td>
        <td><?= htmlspecialchars((string)$item['gender'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= $item['age'] === null ? '-' : (int)$item['age'] ?></td>
        <td><?= htmlspecialchars((string)($item['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['relationship_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['father_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['mother_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['spouse_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['birth_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int)($item['created_by'] ?? 0) ?></td>
        <td><?= (int)($item['is_locked'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary mb-1" href="/index.php?route=admin/edit-person&id=<?= (int)$item['person_id'] ?>">Edit</a>
          <form method="post" action="/index.php?route=admin/delete-person" onsubmit="return confirm('Delete this person?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="person_id" value="<?= (int)$item['person_id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items ?? [])): ?>
      <tr><td colspan="8" class="text-muted">No persons found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
