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

<?php if ((int)($pending_add_proposals ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
  <span style="font-size:1.3rem;">&#10010;</span>
  <div class="flex-grow-1">
    <strong><?= (int)$pending_add_proposals ?> new person submission<?= $pending_add_proposals > 1 ? 's' : '' ?></strong> awaiting approval.
  </div>
  <a href="/index.php?route=admin/add-proposals" class="btn btn-sm btn-warning btn-pill">Review</a>
</div>
<?php endif; ?>
<?php if ((int)($pending_proposals ?? 0) > 0): ?>
<div class="alert alert-info d-flex align-items-center gap-3 mb-4">
  <span style="font-size:1.3rem;">&#128196;</span>
  <div class="flex-grow-1">
    <strong><?= (int)$pending_proposals ?> pending edit proposal<?= $pending_proposals > 1 ? 's' : '' ?></strong> awaiting your review.
  </div>
  <a href="/index.php?route=admin/proposals" class="btn btn-sm btn-primary btn-pill">Review now</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <!-- Announcements -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">&#128227; Announcements</h6>
          <a href="/index.php?route=admin/announcements" class="btn btn-sm btn-outline-primary btn-pill">Manage</a>
        </div>
        <?php if (empty($announcements)): ?>
          <p class="text-muted mb-0" style="font-size:.85rem;">No announcements yet.</p>
        <?php else: ?>
          <?php foreach (($announcements ?? []) as $ann): ?>
          <div class="mb-2 pb-2" style="border-bottom:1px solid var(--ft-border);">
            <div class="fw-600" style="font-size:.88rem;">
              <?= (int)$ann['is_pinned'] ? '&#128204; ' : '' ?><?= htmlspecialchars((string)$ann['title'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$ann['body']),0,80,'…'), ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Upcoming Events -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3">&#127874; Upcoming (next 30 days)</h6>
        <?php if (empty($upcoming_events)): ?>
          <p class="text-muted mb-0" style="font-size:.85rem;">No upcoming birthdays or anniversaries.</p>
        <?php else: ?>
          <?php foreach (($upcoming_events ?? []) as $ev): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span><?= $ev['icon'] ?></span>
            <div>
              <div style="font-size:.85rem; font-weight:600;"><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.75rem; color:var(--ft-muted);"><?= htmlspecialchars((string)$ev['date'], ENT_QUOTES, 'UTF-8') ?> · <?= $ev['type'] === 'birthday' ? 'Birthday' : 'Anniversary' ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Quick Actions</h6>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-primary btn-sm btn-pill" href="/index.php?route=admin/add-person">Add Person</a>
      <a class="btn btn-outline-primary btn-sm btn-pill" href="/index.php?route=admin/family-list">Family List</a>
      <a class="btn btn-outline-primary btn-sm btn-pill" href="/index.php?route=member/add-marriage">Add Marriage</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/timeline">Timeline</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/memorial">Memorial</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/family-events">Family Events</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/reports">Reports</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/invite-links">Invite Links</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/export-persons">&#8659; Export CSV</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/export-gedcom">&#8659; GEDCOM</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/svg-tree">&#128065; Visual Tree</a>
      <a class="btn btn-outline-secondary btn-sm btn-pill" href="/index.php?route=admin/map-view">&#127758; Family Map</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
