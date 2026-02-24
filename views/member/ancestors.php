<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Ancestors</h1>
<form method="get" action="/index.php" class="row g-3 mb-3">
  <?php $routePrefix = (string)($route_prefix ?? 'member'); ?>
  <input type="hidden" name="route" value="<?= htmlspecialchars($routePrefix . '/ancestors', ENT_QUOTES, 'UTF-8') ?>">
  <div class="col-md-8 position-relative">
    <label class="form-label">Person</label>
    <input class="form-control person-search" data-target="person_id" placeholder="Type name (2+ chars)" value="<?= htmlspecialchars((string)($person_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" id="person_id" name="person_id" value="<?= (int)($person_id ?? 0) ?>">
    <div class="list-group mt-1 search-results"></div>
  </div>
  <div class="col-md-2">
    <label class="form-label">Line</label>
    <?php $selectedSide = (string)($side ?? 'any'); ?>
    <select class="form-select" name="side">
      <option value="any" <?= $selectedSide === 'any' ? 'selected' : '' ?>>Any</option>
      <option value="paternal" <?= $selectedSide === 'paternal' ? 'selected' : '' ?>>Paternal</option>
      <option value="maternal" <?= $selectedSide === 'maternal' ? 'selected' : '' ?>>Maternal</option>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary" type="submit">Load Ancestors</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead>
      <tr><th>Generation</th><th>Link</th><th>ID</th><th>Name</th><th>Gender</th></tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
      <tr>
        <td><?= (int)$r['generation'] ?></td>
        <td><?= htmlspecialchars((string)$r['link'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int)$r['person_id'] ?></td>
        <td><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)$r['gender'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rows ?? [])): ?>
      <tr><td colspan="5" class="text-muted">No ancestor data for selected person.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
