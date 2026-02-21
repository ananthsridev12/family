<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4">Branches</h1>
<form method="post" action="/index.php?route=admin/branches" class="card card-body mb-3">
  <input type="hidden" name="action" value="add">
  <div class="row g-2">
    <div class="col-md-8">
      <input type="text" name="branch_name" class="form-control" placeholder="New branch name">
    </div>
    <div class="col-md-4">
      <button class="btn btn-primary w-100" type="submit">Add Branch</button>
    </div>
  </div>
</form>
<table class="table table-sm">
  <thead><tr><th>Branch</th><th>Members</th><th>Edit</th></tr></thead>
  <tbody>
  <?php foreach (($rows ?? []) as $row): ?>
    <tr>
      <td><?= htmlspecialchars((string)$row['branch_name'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= (int)$row['members'] ?></td>
      <td>
        <form method="post" action="/index.php?route=admin/branches" class="d-flex gap-2">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="branch_id" value="<?= (int)$row['branch_id'] ?>">
          <input type="text" name="branch_name" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$row['branch_name'], ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p class="text-muted">Branch metadata is for grouping only. No relation inference uses branch data.</p>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
