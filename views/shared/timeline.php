<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>Family Timeline</h1>
</div>

<!-- Filter bar -->
<div class="d-flex flex-wrap gap-2 mb-4" id="timelineFilters">
  <button class="btn btn-primary btn-sm btn-pill active" data-filter="all">All</button>
  <button class="btn btn-outline-secondary btn-sm btn-pill" data-filter="birthday">Births</button>
  <button class="btn btn-outline-secondary btn-sm btn-pill" data-filter="death">Deaths</button>
  <button class="btn btn-outline-secondary btn-sm btn-pill" data-filter="wedding">Weddings</button>
  <button class="btn btn-outline-secondary btn-sm btn-pill" data-filter="anniversary">Anniversaries</button>
  <button class="btn btn-outline-secondary btn-sm btn-pill" data-filter="family_event">Family Events</button>
</div>

<?php
$typeColors = [
    'birthday'     => '#10b981',
    'death'        => '#64748b',
    'wedding'      => '#f59e0b',
    'anniversary'  => '#f59e0b',
    'family_event' => '#4f46e5',
];

// Group events by year
$byYear = [];
foreach (($events ?? []) as $ev) {
    $byYear[(int)$ev['year']][] = $ev;
}
krsort($byYear);
?>

<?php if (empty($byYear)): ?>
<div class="card">
  <div class="card-body text-center py-5">
    <span class="text-muted fs-5">No events to display.</span>
  </div>
</div>
<?php else: ?>

<div class="timeline-wrap" id="timelineWrap">
  <?php foreach ($byYear as $year => $yearEvents): ?>
  <div class="tl-year-group" data-year="<?= (int)$year ?>">
    <div class="tl-year-label">
      <span><?= (int)$year ?></span>
    </div>
    <div class="tl-events-col">
      <?php foreach ($yearEvents as $ev): ?>
      <?php
        $type   = htmlspecialchars((string)($ev['type'] ?? 'family_event'), ENT_QUOTES, 'UTF-8');
        $color  = $typeColors[$ev['type'] ?? ''] ?? '#4f46e5';
        $label  = htmlspecialchars((string)($ev['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $icon   = htmlspecialchars((string)($ev['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
        $name   = htmlspecialchars((string)($ev['person_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $detail = htmlspecialchars((string)($ev['detail'] ?? ''), ENT_QUOTES, 'UTF-8');
        $pid    = (int)($ev['person_id'] ?? 0);
        $month  = (int)($ev['month'] ?? 0);
        $monthStr = $month > 0 ? date('M', mktime(0, 0, 0, $month, 1)) . ' ' . (int)$year : (string)(int)$year;
      ?>
      <div class="tl-event-card" data-type="<?= $type ?>">
        <div class="tl-event-inner" style="border-left:4px solid <?= $color ?>;">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="tl-dot" style="background:<?= $color ?>;"></span>
            <?php if ($icon): ?>
              <span class="tl-icon"><?= $icon ?></span>
            <?php endif; ?>
            <span class="fw-600 tl-event-label"><?= $label ?></span>
            <span class="ms-auto tl-month text-muted" style="font-size:.78rem;"><?= $monthStr ?></span>
          </div>
          <?php if ($name): ?>
          <div class="tl-person-name">
            <?php if ($pid > 0): ?>
              <a href="/index.php?route=admin/person-view&id=<?= $pid ?>" class="text-decoration-none" style="color:var(--ft-primary);"><?= $name ?></a>
            <?php else: ?>
              <?= $name ?>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if ($detail): ?>
          <div class="tl-detail text-muted" style="font-size:.83rem;"><?= $detail ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
.timeline-wrap {
  position: relative;
}
.tl-year-group {
  display: grid;
  grid-template-columns: 72px 1fr;
  gap: 0 1rem;
  margin-bottom: 1.5rem;
}
.tl-year-label {
  display: flex;
  align-items: flex-start;
  padding-top: .35rem;
}
.tl-year-label span {
  font-size: .78rem;
  font-weight: 700;
  color: var(--ft-muted, #6c757d);
  letter-spacing: .03em;
  white-space: nowrap;
}
.tl-events-col {
  border-left: 2px solid var(--ft-border, #e5e7eb);
  padding-left: 1rem;
  display: flex;
  flex-direction: column;
  gap: .75rem;
}
.tl-event-card {
  transition: opacity .2s;
}
.tl-event-card.tl-hidden {
  display: none;
}
.tl-event-inner {
  background: var(--ft-bg, #fff);
  border-radius: 10px;
  padding: .65rem .9rem;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.tl-dot {
  display: inline-block;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  flex-shrink: 0;
}
.tl-icon {
  font-size: 1rem;
  line-height: 1;
}
.tl-event-label {
  font-size: .9rem;
}
.tl-person-name {
  font-size: .85rem;
  margin-top: .1rem;
}
.tl-detail {
  margin-top: .2rem;
}
@media (max-width: 480px) {
  .tl-year-group {
    grid-template-columns: 52px 1fr;
  }
}
</style>

<script>
(function () {
  var filterBtns = document.querySelectorAll('#timelineFilters button');
  var cards      = document.querySelectorAll('.tl-event-card');
  var yearGroups = document.querySelectorAll('.tl-year-group');

  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
      });
      btn.classList.add('active', 'btn-primary');
      btn.classList.remove('btn-outline-secondary');

      var filter = btn.getAttribute('data-filter');
      cards.forEach(function (card) {
        if (filter === 'all' || card.getAttribute('data-type') === filter) {
          card.classList.remove('tl-hidden');
        } else {
          card.classList.add('tl-hidden');
        }
      });

      // Hide year group rows that have no visible cards
      yearGroups.forEach(function (grp) {
        var visible = grp.querySelectorAll('.tl-event-card:not(.tl-hidden)');
        grp.style.display = visible.length === 0 ? 'none' : '';
      });
    });
  });
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
