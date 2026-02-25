<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Reports</h1>
<p class="text-muted">Current system metrics.</p>

<div class="row g-3">
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
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
