<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Tree View</h1>
<div class="row g-3">
  <div class="col-md-8 position-relative">
    <label class="form-label">Search Root Person</label>
    <input class="form-control person-search" data-target="treeRootId" id="treeRootDisplay" placeholder="Type name (2+ chars)" value="<?= htmlspecialchars((string)($root_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input id="treeRootId" type="hidden" value="<?= (int)($root_id ?? 0) ?>">
    <div class="list-group mt-1 search-results"></div>
  </div>
  <div class="col-md-4">
    <label class="form-label d-block">&nbsp;</label>
    <button id="loadTreeBtn" class="btn btn-primary">Load Tree (AJAX)</button>
  </div>
</div>
<?php $routePrefix = (string)($route_prefix ?? 'member'); ?>
<div id="treeContainer" class="mt-4"
     data-children-route="/index.php?route=<?= htmlspecialchars($routePrefix . '/person-children', ENT_QUOTES, 'UTF-8') ?>"
     data-profile-route="/index.php?route=<?= htmlspecialchars($routePrefix . '/person-view', ENT_QUOTES, 'UTF-8') ?>"></div>
<p class="text-muted mb-0">Tip: choose person from search and click load. Expand nodes with +. Data loads via AJAX.</p>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
