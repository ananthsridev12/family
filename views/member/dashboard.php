<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Member Dashboard</h1>
<p class="text-muted">Use add, edit, and marriage workflows from here.</p>
<div class="d-flex gap-2 flex-wrap">
  <a class="btn btn-primary btn-sm" href="/index.php?route=member/add-person">Add Person</a>
  <a class="btn btn-outline-primary btn-sm" href="/index.php?route=member/family-list">Family List</a>
  <a class="btn btn-outline-primary btn-sm" href="/index.php?route=member/add-marriage">Add Marriage</a>
  <a class="btn btn-outline-secondary btn-sm" href="/index.php?route=member/relationship-finder">Relationship Finder</a>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
