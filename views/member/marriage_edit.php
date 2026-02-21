<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Edit Marriage</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card card-body shadow-sm">
  <p class="mb-3">
    <strong><?= htmlspecialchars((string)$marriage['person1_name'], ENT_QUOTES, 'UTF-8') ?></strong>
    and
    <strong><?= htmlspecialchars((string)$marriage['person2_name'], ENT_QUOTES, 'UTF-8') ?></strong>
  </p>
  <form method="post" action="/index.php?route=member/edit-marriage">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="marriage_id" value="<?= (int)$marriage['marriage_id'] ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Marriage Date</label>
        <input type="date" name="marriage_date" class="form-control" value="<?= htmlspecialchars((string)($marriage['marriage_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Divorce Date</label>
        <input type="date" name="divorce_date" class="form-control" value="<?= htmlspecialchars((string)($marriage['divorce_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <?php $st = (string)($marriage['status'] ?? 'married'); ?>
        <select name="status" class="form-select">
          <option value="married" <?= $st === 'married' ? 'selected' : '' ?>>Married</option>
          <option value="divorced" <?= $st === 'divorced' ? 'selected' : '' ?>>Divorced</option>
          <option value="widowed" <?= $st === 'widowed' ? 'selected' : '' ?>>Widowed</option>
        </select>
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary" type="submit">Update Marriage</button>
      <a class="btn btn-outline-secondary" href="/index.php?route=member/add-marriage">Back</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
