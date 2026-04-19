<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Admin Dashboard</h1>
<p class="text-muted">Overview of registered users and family growth.</p>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card card-body shadow-sm">
      <div class="text-muted small">Registered Users</div>
      <div class="h4 mb-0"><?= (int)($stats['users'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-body shadow-sm">
      <div class="text-muted small">Persons</div>
      <div class="h4 mb-0"><?= (int)($stats['persons'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-body shadow-sm">
      <div class="text-muted small">Marriages</div>
      <div class="h4 mb-0"><?= (int)($stats['marriages'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-body shadow-sm">
      <div class="text-muted small">Families (with kids)</div>
      <div class="h4 mb-0"><?= (int)($stats['families'] ?? 0) ?></div>
    </div>
  </div>
</div>

<?php if ((int)($pending_proposals ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
  <strong><?= (int)$pending_proposals ?> pending edit proposal<?= $pending_proposals > 1 ? 's' : '' ?></strong> awaiting review.
  <a href="/index.php?route=admin/proposals" class="btn btn-sm btn-warning ms-2">Review now</a>
</div>
<?php endif; ?>

<div class="d-flex gap-2 flex-wrap">
  <a class="btn btn-primary btn-sm" href="/index.php?route=admin/add-person">Add Person</a>
  <a class="btn btn-outline-primary btn-sm" href="/index.php?route=admin/family-list">Family List</a>
  <a class="btn btn-outline-primary btn-sm" href="/index.php?route=member/add-marriage">Add Marriage</a>
  <a class="btn btn-outline-secondary btn-sm" href="/index.php?route=admin/reports">Reports</a>
  <a class="btn btn-outline-secondary btn-sm" href="/index.php?route=admin/proposals">Edit Proposals</a>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
