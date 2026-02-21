<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4">Relationship Finder</h1>
<form method="post" class="row g-3" id="relationshipForm">
  <div class="col-md-6">
    <label class="form-label">Person A</label>
    <input class="form-control person-search" data-target="person_a_id" placeholder="Type 2+ letters">
    <input type="hidden" name="person_a_id" id="person_a_id">
    <div class="list-group mt-1 search-results"></div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Person B</label>
    <input class="form-control person-search" data-target="person_b_id" placeholder="Type 2+ letters">
    <input type="hidden" name="person_b_id" id="person_b_id">
    <div class="list-group mt-1 search-results"></div>
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Find Relationship</button>
  </div>
</form>
<?php if (!empty($relation)): ?>
  <div class="card mt-4"><div class="card-body">
    <h2 class="h6 mb-3">Result</h2>
    <?php $useTa = (($lang ?? 'en') === 'ta'); ?>
    <p class="mb-1"><strong>Title:</strong> <?= htmlspecialchars($useTa ? (string)$relation['title_ta'] : (string)$relation['title_en'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="mb-1"><strong>Side:</strong> <?= htmlspecialchars((string)$relation['side'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="mb-1"><strong>Generation Difference:</strong> <?= (int)$relation['generation_difference'] ?></p>
    <p class="mb-1"><strong>Cousin Level:</strong> <?= $relation['cousin_level'] === null ? '-' : (int)$relation['cousin_level'] ?></p>
    <p class="mb-0"><strong>Removed:</strong> <?= $relation['removed'] === null ? '-' : (int)$relation['removed'] ?></p>
  </div></div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>