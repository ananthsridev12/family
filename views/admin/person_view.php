<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Person Profile</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-outline-primary" href="/index.php?route=admin/edit-person&id=<?= (int)$person['person_id'] ?>">Edit Profile</a>
    <a class="btn btn-sm btn-outline-secondary" href="/index.php?route=admin/wiki-view&id=<?= (int)$person['person_id'] ?>" title="Wikipedia-style family view">&#128196; Wiki View</a>
    <button class="btn btn-sm btn-outline-indigo" onclick="document.getElementById('viewLinkSection').scrollIntoView({behavior:'smooth'})" style="border-color:#6366f1;color:#6366f1;">&#128279; View Link</button>
    <?php
      $waUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/index.php?route=admin/person-view&id=' . (int)$person['person_id'];
    ?>
    <a class="btn btn-sm btn-outline-success btn-pill" href="https://wa.me/?text=<?= urlencode('View family profile: ' . $waUrl) ?>" target="_blank" rel="noopener">&#128172; WhatsApp</a>
    <a class="btn btn-sm btn-outline-secondary" href="/index.php?route=admin/family-list">Back to List</a>
  </div>
</div>

<?php $isDeceased = (int)($person['is_alive'] ?? 1) === 0 || !empty($person['date_of_death']); ?>
<?php if ($isDeceased): ?>
<div class="alert d-flex align-items-center gap-3 mb-3" style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:12px;">
  <span style="font-size:1.4rem;">&#129354;</span>
  <div class="flex-grow-1">
    <strong>Deceased</strong>
    <?php if (!empty($person['date_of_death'])): ?>
      &mdash; <?= date('d M Y', strtotime((string)$person['date_of_death'])) ?>
    <?php endif; ?>
  </div>
  <form method="post" action="/index.php?route=admin/mark-alive" onsubmit="return confirm('Mark as alive?')">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
    <button class="btn btn-sm btn-outline-success btn-pill">&#8617; Mark as Alive</button>
  </form>
</div>
<?php else: ?>
<div class="d-flex justify-content-end mb-2">
  <button class="btn btn-sm btn-outline-secondary btn-pill" data-bs-toggle="modal" data-bs-target="#deceasedModal">
    &#129354; Mark as Deceased
  </button>
</div>
<?php endif; ?>

<!-- Mark as Deceased Modal -->
<div class="modal fade" id="deceasedModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/mark-deceased">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Mark as Deceased</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted" style="font-size:.85rem;">
            This will mark <strong><?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?></strong> as deceased.
            You can optionally record their date of death.
          </p>
          <label class="form-label">Date of Death <span class="text-muted fw-normal">(optional)</span></label>
          <input class="form-control" type="date" name="date_of_death" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-pill">Confirm</button>
        </div>
      </form>
    </div>
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

<!-- Sibling / Children Order Section -->
<?php if (!empty($children)): ?>
<div class="card card-body shadow-sm mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">&#128106; Children & Sibling Order</h5>
    <span class="text-muted" style="font-size:.82rem;">Drag to reorder, then click Save</span>
  </div>
  <p class="text-muted mb-3" style="font-size:.84rem;">
    Arrange the children of <strong><?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?></strong> in birth order (eldest first).
    This order is used across the tree to display siblings correctly.
  </p>

  <ul id="siblingList" class="list-group mb-3" style="max-width:520px;">
    <?php foreach ($children as $child): ?>
    <li class="list-group-item d-flex align-items-center gap-3 py-2"
        data-id="<?= (int)$child['person_id'] ?>"
        style="cursor:grab; border-radius:10px; margin-bottom:.35rem; border:1px solid var(--ft-border);">
      <span class="drag-handle text-muted" style="font-size:1.1rem; cursor:grab;">&#9776;</span>
      <span class="sib-pos-badge badge"
            style="background:var(--ft-border);color:var(--ft-muted);min-width:24px;font-size:.75rem;font-weight:700;">
        <?= (int)($child['birth_order'] ?? 0) > 0 ? (int)$child['birth_order'] : '—' ?>
      </span>
      <div class="flex-grow-1">
        <div class="fw-600" style="font-size:.9rem;"><?= htmlspecialchars((string)$child['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-muted" style="font-size:.75rem;">
          <?= htmlspecialchars((string)($child['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php if (!empty($child['birth_year'])): ?> · <?= (int)$child['birth_year'] ?><?php endif; ?>
        </div>
      </div>
      <a href="/index.php?route=admin/person-view&id=<?= (int)$child['person_id'] ?>"
         class="btn btn-outline-secondary btn-sm btn-pill" title="View profile">&#8599;</a>
    </li>
    <?php endforeach; ?>
  </ul>

  <div class="d-flex align-items-center gap-3">
    <button id="saveSibOrder" class="btn btn-primary btn-sm btn-pill">Save Order</button>
    <span id="sibOrderMsg" class="text-muted" style="font-size:.83rem;"></span>
  </div>
</div>
<?php endif; ?>

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

// ── Sibling drag-and-drop reorder ────────────────────────────────────
(function() {
  var list = document.getElementById('siblingList');
  if (!list) return;

  var dragging = null;

  list.querySelectorAll('li').forEach(function(item) {
    item.setAttribute('draggable', 'true');
    item.addEventListener('dragstart', function(e) {
      dragging = item;
      setTimeout(function() { item.style.opacity = '0.4'; }, 0);
    });
    item.addEventListener('dragend', function() {
      item.style.opacity = '';
      dragging = null;
      updatePositionBadges();
    });
    item.addEventListener('dragover', function(e) {
      e.preventDefault();
      if (dragging && dragging !== item) {
        var rect = item.getBoundingClientRect();
        var mid  = rect.top + rect.height / 2;
        if (e.clientY < mid) {
          list.insertBefore(dragging, item);
        } else {
          list.insertBefore(dragging, item.nextSibling);
        }
      }
    });
  });

  function updatePositionBadges() {
    list.querySelectorAll('li').forEach(function(li, idx) {
      var badge = li.querySelector('.sib-pos-badge');
      if (badge) badge.textContent = idx + 1;
    });
  }

  document.getElementById('saveSibOrder') && document.getElementById('saveSibOrder').addEventListener('click', function() {
    var ids = [];
    list.querySelectorAll('li[data-id]').forEach(function(li) { ids.push(li.dataset.id); });
    var msg = document.getElementById('sibOrderMsg');
    msg.textContent = 'Saving…';
    var fd = new FormData();
    fd.append('csrf_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>');
    fd.append('parent_id', '<?= (int)$person['person_id'] ?>');
    fd.append('order_json', JSON.stringify(ids));
    fetch('/index.php?route=admin/save-sibling-order', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.ok) {
          msg.textContent = '✓ Order saved.';
          msg.style.color = '#059669';
        } else {
          msg.textContent = 'Error: ' + (d.error || 'unknown');
          msg.style.color = '#dc2626';
        }
        setTimeout(function() { msg.textContent = ''; }, 3000);
      })
      .catch(function() { msg.textContent = 'Save failed.'; msg.style.color = '#dc2626'; });
  });
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
