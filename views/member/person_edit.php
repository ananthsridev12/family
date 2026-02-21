<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Edit Family Person</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/index.php?route=member/edit-person" class="card card-body shadow-sm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full Name</label>
      <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Gender</label>
      <?php $g = (string)($person['gender'] ?? 'unknown'); ?>
      <select name="gender" class="form-select">
        <option value="unknown" <?= $g === 'unknown' ? 'selected' : '' ?>>Unknown</option>
        <option value="male" <?= $g === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $g === 'female' ? 'selected' : '' ?>>Female</option>
        <option value="other" <?= $g === 'other' ? 'selected' : '' ?>>Other</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" <?= (int)($person['is_alive'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_alive">Is Alive</label>
      </div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars((string)($person['date_of_birth'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Birth Year</label>
      <input type="number" name="birth_year" class="form-control" value="<?= htmlspecialchars((string)($person['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Date of Death</label>
      <input type="date" name="date_of_death" class="form-control" value="<?= htmlspecialchars((string)($person['date_of_death'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Blood Group</label>
      <input type="text" name="blood_group" class="form-control" value="<?= htmlspecialchars((string)($person['blood_group'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Occupation</label>
      <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars((string)($person['occupation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Mobile</label>
      <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars((string)($person['mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string)($person['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Current Location</label>
      <input type="text" name="current_location" class="form-control" value="<?= htmlspecialchars((string)($person['current_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Native Location</label>
      <input type="text" name="native_location" class="form-control" value="<?= htmlspecialchars((string)($person['native_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars((string)($person['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="mt-4">
    <button class="btn btn-primary" type="submit">Save Changes</button>
    <a class="btn btn-outline-secondary" href="/index.php?route=member/add-marriage">Add Marriage</a>
  </div>
</form>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
