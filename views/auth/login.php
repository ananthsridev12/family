<?php include __DIR__ . '/../layouts/header.php'; ?>
<div class="container py-5" style="max-width: 460px;">
  <h1 class="h4 mb-3">FamilyTree 3.0 Login</h1>
  <form method="post" action="/index.php?route=login" class="card card-body shadow-sm">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Role</label>
      <select class="form-select" name="role">
        <option value="member">Member</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <button class="btn btn-primary">Continue</button>
  </form>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>