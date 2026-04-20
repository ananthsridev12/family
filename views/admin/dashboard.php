<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>Admin Dashboard</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm btn-pill" href="/index.php?route=admin/add-person">+ Add Person</a>
    <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/reports">Reports</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card sc-purple">
      <div class="stat-label">Registered Users</div>
      <div class="stat-value"><?= (int)($stats['users'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card sc-blue">
      <div class="stat-label">Persons</div>
      <div class="stat-value"><?= (int)($stats['persons'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card sc-amber">
      <div class="stat-label">Marriages</div>
      <div class="stat-value"><?= (int)($stats['marriages'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card sc-green">
      <div class="stat-label">Families (with kids)</div>
      <div class="stat-value"><?= (int)($stats['families'] ?? 0) ?></div>
    </div>
  </div>
</div>

<?php if ((int)($pending_proposals ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
  <span style="font-size:1.3rem;">&#128196;</span>
  <div class="flex-grow-1">
    <strong><?= (int)$pending_proposals ?> pending edit proposal<?= $pending_proposals > 1 ? 's' : '' ?></strong> awaiting your review.
  </div>
  <a href="/index.php?route=admin/proposals" class="btn btn-sm btn-warning btn-pill">Review now</a>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Quick Actions</h6>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-primary btn-sm btn-pill" href="/index.php?route=admin/add-person">Add Person</a>
      <a class="btn btn-outline-primary btn-sm btn-pill" href="/index.php?route=admin/family-list">Family List</a>
      <a class="btn btn-outline-primary btn-sm btn-pill" href="/index.php?route=member/add-marriage">Add Marriage</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/reports">Reports</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/proposals">Edit Proposals</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
