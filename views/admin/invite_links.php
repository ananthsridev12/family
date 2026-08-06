<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>Invite Links</h1>
  <button class="btn btn-primary btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#newLinkModal">+ New Invite Link</button>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Label</th>
          <th>Link</th>
          <th>Uses</th>
          <th>Expires</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($links)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No invite links yet. Create one to share with family members.</td></tr>
        <?php endif; ?>
        <?php foreach ($links as $lnk): ?>
        <?php
          $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
               . '/index.php?route=join&token=' . urlencode((string)$lnk['token']);
          $isActive = (bool)$lnk['is_active'];
          $maxUses  = (int)$lnk['max_uses'];
          $used     = (int)$lnk['used_count'];
          $expired  = $lnk['expires_at'] !== null && strtotime((string)$lnk['expires_at']) < time();
        ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars((string)($lnk['label'] ?: 'General invite'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted" style="font-size:.75rem;">by <?= htmlspecialchars((string)($lnk['creator_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= date('d M Y', strtotime((string)$lnk['created_at'])) ?></div>
          </td>
          <td style="max-width:280px;">
            <div class="d-flex align-items-center gap-2">
              <input type="text" class="form-control form-control-sm" readonly value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
              <button class="btn btn-sm btn-outline-secondary btn-pill" onclick="copyLink(this,'<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>')" title="Copy">&#128203;</button>
            </div>
          </td>
          <td>
            <span class="badge bg-secondary"><?= $used ?><?= $maxUses > 0 ? ' / ' . $maxUses : '' ?></span>
          </td>
          <td>
            <?php if ($lnk['expires_at']): ?>
              <span class="<?= $expired ? 'text-danger' : 'text-muted' ?>" style="font-size:.82rem;">
                <?= date('d M Y', strtotime((string)$lnk['expires_at'])) ?>
              </span>
            <?php else: ?>
              <span class="text-muted" style="font-size:.82rem;">Never</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$isActive || $expired || ($maxUses > 0 && $used >= $maxUses)): ?>
              <span class="badge bg-danger">Inactive</span>
            <?php else: ?>
              <span class="badge bg-success">Active</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($isActive): ?>
            <form method="post" action="/index.php?route=admin/deactivate-invite" onsubmit="return confirm('Deactivate this link?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="link_id" value="<?= (int)$lnk['link_id'] ?>">
              <button class="btn btn-outline-danger btn-sm btn-pill">Deactivate</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New Link Modal -->
<div class="modal fade" id="newLinkModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/create-invite">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Create Invite Link</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control" name="label" placeholder="e.g. For cousins in Chennai">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Max uses <span class="text-muted fw-normal">(0 = unlimited)</span></label>
              <input class="form-control" name="max_uses" type="number" min="0" value="0">
            </div>
            <div class="col-6">
              <label class="form-label">Expires on <span class="text-muted fw-normal">(optional)</span></label>
              <input class="form-control" name="expires_at" type="date">
            </div>
          </div>
          <hr class="my-3">
          <p class="fw-bold mb-1" style="font-size:.88rem;">Default permissions for people who join via this link</p>
          <div class="mb-3">
            <label class="form-label">Edit access</label>
            <select class="form-select" name="default_edit_scope">
              <option value="self">Own profile only</option>
              <option value="children" selected>Own profile + children</option>
              <option value="grandchildren">Own profile + children + grandchildren</option>
              <option value="all">All persons</option>
              <option value="none">None (view only)</option>
            </select>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="default_can_add_person" id="inviteCanAdd" value="1" checked>
            <label class="form-check-label" for="inviteCanAdd">Can submit new persons for approval</label>
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
function copyLink(btn, url) {
  navigator.clipboard.writeText(url).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '&#10003;';
    btn.classList.replace('btn-outline-secondary','btn-success');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('btn-success','btn-outline-secondary'); }, 2000);
  });
}
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
