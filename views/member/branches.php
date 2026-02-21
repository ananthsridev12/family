<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4">Branches</h1>
<p class="text-muted">Branch is a grouping label only. Relationship logic ignores branch values.</p>
<p class="text-muted">Members can view branches; branch add/edit is available in Admin panel.</p>
<table class="table table-sm">
  <thead><tr><th>Branch</th><th>Members</th></tr></thead>
  <tbody>
  <?php foreach (($rows ?? []) as $row): ?>
    <tr>
      <td><?= htmlspecialchars((string)$row['branch_name'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= (int)$row['members'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
