<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header mb-4">
  <h1>Reports</h1>
  <p class="text-muted mb-0">Current system metrics and statistics.</p>
</div>

<!-- 6 stat tiles -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-blue">
      <div class="stat-label">Persons</div>
      <div class="stat-value"><?= (int)($stats['persons'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-green">
      <div class="stat-label">Living</div>
      <div class="stat-value"><?= (int)($stats['living'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-amber">
      <div class="stat-label">Deceased</div>
      <div class="stat-value"><?= (int)($stats['deceased'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-purple">
      <div class="stat-label">Marriages</div>
      <div class="stat-value"><?= (int)($stats['marriages'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-blue">
      <div class="stat-label">Families</div>
      <div class="stat-value"><?= (int)($stats['families'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card sc-green">
      <div class="stat-label">Branches</div>
      <div class="stat-value"><?= (int)($stats['branches'] ?? 0) ?></div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Average age + oldest living -->
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Key Figures</h6>
        <?php if (($stats['avg_age'] ?? null) !== null): ?>
        <div class="mb-3">
          <div class="text-muted small">Avg. age (living, with DOB)</div>
          <div class="h3 mb-0"><?= number_format((float)$stats['avg_age'], 1) ?> yrs</div>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['oldest_living'])): ?>
        <div>
          <div class="text-muted small">Oldest living member</div>
          <div class="fw-semibold">
            <a href="/index.php?route=admin/person-view&id=<?= (int)$stats['oldest_living']['id'] ?>">
              <?= htmlspecialchars((string)$stats['oldest_living']['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </div>
          <div class="text-muted small"><?= (int)$stats['oldest_living']['age'] ?> years old</div>
        </div>
        <?php endif; ?>
        <hr class="my-3">
        <div class="text-muted small">Registered Users</div>
        <div class="h5 mb-0"><?= (int)($stats['users'] ?? 0) ?></div>
      </div>
    </div>
  </div>

  <!-- Gender distribution -->
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Gender Distribution</h6>
        <?php if (!empty($stats['gender_map'])): ?>
        <canvas id="genderChart" height="160"></canvas>
        <?php else: ?>
        <p class="text-muted">No data.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Top 5 first names -->
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Top 5 First Names</h6>
        <?php if (!empty($stats['top_names'])): ?>
        <table class="table table-sm mb-0">
          <thead><tr><th>Name</th><th class="text-end">Count</th></tr></thead>
          <tbody>
            <?php foreach ($stats['top_names'] as $tn): ?>
            <tr>
              <td><?= htmlspecialchars((string)$tn['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end"><?= (int)$tn['count'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">No data.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Birth decade chart -->
<?php if (!empty($stats['decade_data'])): ?>
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Births by Decade</h6>
    <canvas id="decadeChart" height="80"></canvas>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
  <?php if (!empty($stats['gender_map'])): ?>
  var genderLabels = <?= json_encode(array_keys($stats['gender_map']), JSON_UNESCAPED_UNICODE) ?>;
  var genderData   = <?= json_encode(array_values($stats['gender_map'])) ?>;
  new Chart(document.getElementById('genderChart'), {
    type: 'bar',
    data: {
      labels: genderLabels,
      datasets: [{
        label: 'Count',
        data: genderData,
        backgroundColor: ['#4f46e5','#e879a0','#22c55e','#94a3b8'],
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
  <?php endif; ?>

  <?php if (!empty($stats['decade_data'])): ?>
  var decadeLabels = <?= json_encode(array_map(static fn($d) => $d . 's', array_keys($stats['decade_data'])), JSON_UNESCAPED_UNICODE) ?>;
  var decadeData   = <?= json_encode(array_values($stats['decade_data'])) ?>;
  new Chart(document.getElementById('decadeChart'), {
    type: 'bar',
    data: {
      labels: decadeLabels,
      datasets: [{
        label: 'Births',
        data: decadeData,
        backgroundColor: '#4f46e5',
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
  <?php endif; ?>
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
