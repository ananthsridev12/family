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

<?php if (!empty($pendingProposal)): ?>
<div class="alert alert-warning mt-3">
  You have a pending edit proposal for this person (submitted <?= htmlspecialchars((string)$pendingProposal['created_at'], ENT_QUOTES, 'UTF-8') ?>). It is awaiting admin review.
</div>
<?php endif; ?>

<div class="card card-body shadow-sm mt-4">
  <h5 class="mb-3">Photos &amp; Documents</h5>
  <form id="upload_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <input type="file" name="attachment" id="attachment_file" class="form-control" style="max-width:320px;" accept="image/jpeg,image/png,image/webp,application/pdf">
      <button type="submit" class="btn btn-sm btn-primary">Upload</button>
      <span class="text-muted small">JPEG, PNG, WebP or PDF — max 5 MB</span>
    </div>
    <div id="upload_msg" class="mt-2"></div>
  </form>

  <div id="attachment_list" class="row g-3 mt-1">
    <?php foreach ($attachments as $att): ?>
    <?php $isPhoto = in_array($att['mime_type'], ['image/jpeg','image/png','image/webp'], true); ?>
    <div class="col-6 col-md-3" id="att_<?= (int)$att['attachment_id'] ?>">
      <div class="card h-100">
        <?php if ($isPhoto): ?>
        <a href="/index.php?route=person/attachment&id=<?= (int)$att['attachment_id'] ?>" target="_blank">
          <img src="/index.php?route=person/attachment&id=<?= (int)$att['attachment_id'] ?>" class="card-img-top" style="max-height:140px;object-fit:cover;" alt="<?= htmlspecialchars((string)$att['file_name'], ENT_QUOTES, 'UTF-8') ?>">
        </a>
        <?php else: ?>
        <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height:80px;">
          <span style="font-size:2rem;">&#128196;</span>
          <a href="/index.php?route=person/attachment&id=<?= (int)$att['attachment_id'] ?>" target="_blank" class="small text-break text-center">
            <?= htmlspecialchars((string)$att['file_name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </div>
        <?php endif; ?>
        <div class="card-footer d-flex justify-content-between align-items-center py-1">
          <small class="text-muted"><?= htmlspecialchars((string)$att['attachment_type'], ENT_QUOTES, 'UTF-8') ?></small>
          <button class="btn btn-outline-danger btn-sm delete-att" data-id="<?= (int)$att['attachment_id'] ?>" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">&#x2715;</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function () {
  var form = document.getElementById('upload_form');
  var msg  = document.getElementById('upload_msg');
  var list = document.getElementById('attachment_list');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    msg.textContent = 'Uploading\u2026';
    fetch('/index.php?route=person/upload-attachment', { method: 'POST', body: new FormData(form) })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.error) { msg.textContent = d.error; return; }
        msg.textContent = 'Uploaded successfully.';
        form.reset();
        location.reload();
      })
      .catch(function () { msg.textContent = 'Upload failed.'; });
  });

  list.addEventListener('click', function (e) {
    var btn = e.target.closest('.delete-att');
    if (!btn || !confirm('Delete this attachment?')) return;
    var fd = new FormData();
    fd.append('attachment_id', btn.dataset.id);
    fd.append('csrf_token', btn.dataset.csrf);
    fetch('/index.php?route=person/delete-attachment', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) {
          var el = document.getElementById('att_' + btn.dataset.id);
          if (el) el.remove();
        } else {
          alert(d.error || 'Delete failed.');
        }
      });
  });
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
