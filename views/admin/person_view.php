<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Person Profile</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-outline-primary" href="/index.php?route=admin/edit-person&id=<?= (int)$person['person_id'] ?>">Edit Profile</a>
    <button class="btn btn-sm btn-outline-indigo" onclick="document.getElementById('viewLinkSection').scrollIntoView({behavior:'smooth'})" style="border-color:#6366f1;color:#6366f1;">&#128279; View Link</button>
    <a class="btn btn-sm btn-outline-secondary" href="/index.php?route=admin/family-list">Back to List</a>
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

<div class="card card-body shadow-sm mt-4">
  <h5 class="mb-3">Photos &amp; Documents</h5>
  <form id="upload_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <input type="file" name="attachment" class="form-control" style="max-width:320px;" accept="image/jpeg,image/png,image/webp,application/pdf">
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

<!-- View Link Section -->
<div class="card card-body shadow-sm mt-4" id="viewLinkSection">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">&#128279; Secure View Links</h5>
    <button class="btn btn-sm btn-primary btn-pill" data-bs-toggle="modal" data-bs-target="#genLinkModal">+ Generate Link</button>
  </div>
  <p class="text-muted mb-3" style="font-size:.85rem;">
    Share a secure, personal link with this family member so they can view their profile and request corrections — no account needed.
  </p>
  <?php if (!empty($viewTokens)): ?>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Label</th><th>Link</th><th>Expires</th><th>Created</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($viewTokens as $vt):
          $vtUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                 . '/index.php?route=view&token=' . urlencode((string)$vt['token']);
          $vtExpired = $vt['expires_at'] !== null && strtotime((string)$vt['expires_at']) < time();
        ?>
        <tr>
          <td><?= htmlspecialchars((string)($vt['label'] ?? 'Personal view'), ENT_QUOTES, 'UTF-8') ?></td>
          <td style="max-width:260px;">
            <div class="d-flex align-items-center gap-2">
              <input type="text" class="form-control form-control-sm" readonly value="<?= htmlspecialchars($vtUrl, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
              <button class="btn btn-sm btn-outline-secondary btn-pill" onclick="copyVtLink(this,'<?= htmlspecialchars($vtUrl, ENT_QUOTES, 'UTF-8') ?>')" title="Copy">&#128203;</button>
            </div>
          </td>
          <td style="font-size:.8rem;">
            <?php if ($vt['expires_at']): ?>
              <span class="<?= $vtExpired ? 'text-danger' : 'text-muted' ?>">
                <?= date('d M Y', strtotime((string)$vt['expires_at'])) ?>
              </span>
            <?php else: ?>
              <span class="text-muted">Never</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.8rem; color:var(--ft-muted);"><?= date('d M Y', strtotime((string)$vt['created_at'])) ?></td>
          <td>
            <form method="post" action="/index.php?route=admin/delete-view-token" onsubmit="return confirm('Revoke this link?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="token_id" value="<?= (int)$vt['token_id'] ?>">
              <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
              <button class="btn btn-sm btn-outline-danger btn-pill">Revoke</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p class="text-muted mb-0" style="font-size:.85rem;">No view links yet. Generate one to share with this person.</p>
  <?php endif; ?>
</div>

<!-- Generate Link Modal -->
<div class="modal fade" id="genLinkModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/generate-view-token">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Generate View Link</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control" name="label" placeholder="e.g. Shared via WhatsApp">
          </div>
          <div class="mb-1">
            <label class="form-label">Expires on <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control" name="expires_at" type="date">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-pill">Generate Link</button>
        </div>
      </form>
    </div>
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

function copyVtLink(btn, url) {
  navigator.clipboard.writeText(url).then(function() {
    var orig = btn.innerHTML;
    btn.innerHTML = '&#10003;';
    btn.classList.replace('btn-outline-secondary','btn-success');
    setTimeout(function() { btn.innerHTML = orig; btn.classList.replace('btn-success','btn-outline-secondary'); }, 2000);
  });
}
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
