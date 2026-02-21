<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4">Branches</h1>
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
<p class="text-muted">Branch metadata is for grouping only. No relation inference uses branch data.</p>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>