<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Settings</h1>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="/index.php?route=member/settings" class="card card-body shadow-sm" style="max-width: 460px;">
  <div class="mb-3">
    <label class="form-label">Language</label>
    <select name="lang" class="form-select">
      <option value="en" <?= (($lang ?? 'en') === 'en') ? 'selected' : '' ?>>English</option>
      <option value="ta" <?= (($lang ?? 'en') === 'ta') ? 'selected' : '' ?>>Tamil</option>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Save Settings</button>
</form>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
