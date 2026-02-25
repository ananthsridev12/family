<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Person Profile</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-outline-primary" href="/index.php?route=member/edit-person&id=<?= (int)$person['person_id'] ?>">Edit Profile</a>
    <a class="btn btn-sm btn-outline-secondary" href="/index.php?route=member/family-list">Back to List</a>
  </div>
</div>

<div class="card card-body shadow-sm">
  <div class="row g-3">
    <div class="col-md-6">
      <strong>Name:</strong> <?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-3">
      <strong>Gender:</strong> <?= htmlspecialchars((string)($person['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-3">
      <strong>Birth Year:</strong> <?= htmlspecialchars((string)($person['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-3">
      <strong>Birth Order:</strong> <?= htmlspecialchars((string)($person['birth_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Father:</strong> <?= htmlspecialchars((string)($person['father_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Mother:</strong> <?= htmlspecialchars((string)($person['mother_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Spouse:</strong> <?= htmlspecialchars((string)($person['spouse_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Location:</strong>
      <?= htmlspecialchars((string)($person['current_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Native:</strong>
      <?= htmlspecialchars((string)($person['native_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Email:</strong>
      <?= htmlspecialchars((string)($person['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-md-6">
      <strong>Mobile:</strong>
      <?= htmlspecialchars((string)($person['mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="col-12">
      <strong>Address:</strong>
      <?= htmlspecialchars((string)($person['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
