<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Settings</h1>
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<form method="post" action="/index.php?route=admin/settings" class="card card-body shadow-sm" style="max-width: 460px;">
  <div class="mb-3">
    <label class="form-label">Language</label>
    <?php $currentLang = (string)($_SESSION['lang'] ?? 'en'); ?>
    <select name="lang" class="form-select">
      <option value="en" <?= $currentLang === 'en' ? 'selected' : '' ?>>English</option>
      <option value="ta" <?= $currentLang === 'ta' ? 'selected' : '' ?>>Tamil</option>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Save Settings</button>
</form>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
