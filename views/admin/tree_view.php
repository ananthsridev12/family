<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Tree View</h1>
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label">Root Person ID</label>
    <input id="treeRootId" class="form-control" type="number" min="1" placeholder="Enter person id">
  </div>
  <div class="col-md-8 d-flex align-items-end">
    <button id="loadTreeBtn" class="btn btn-primary">Load Tree (AJAX)</button>
  </div>
</div>
<div id="treeContainer" class="mt-4"></div>
<p class="text-muted mb-0">Loads descendants asynchronously, expands/collapses nodes, and avoids full reload.</p>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>