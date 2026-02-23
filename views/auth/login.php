<?php include __DIR__ . '/../layouts/header.php'; ?>
<div class="container py-5" style="max-width: 460px;">
  <h1 class="h4 mb-3">FamilyTree 3.0 Login</h1>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="post" action="/index.php?route=login" class="card card-body shadow-sm">
    <div class="mb-3">
      <label class="form-label">Email or Username</label>
      <input class="form-control" name="login" value="<?= htmlspecialchars((string)($login ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <button class="btn btn-primary">Sign In</button>
  </form>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
