<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Review Edit Proposal #<?= (int)$proposal['proposal_id'] ?></h1>
  <a href="/index.php?route=admin/proposals" class="btn btn-sm btn-outline-secondary">Back to Proposals</a>
</div>

<div class="card card-body shadow-sm mb-4">
  <div class="row g-2">
    <div class="col-md-4"><strong>Person:</strong>
      <a href="/index.php?route=admin/person-view&id=<?= (int)$proposal['person_id'] ?>">
        <?= htmlspecialchars((string)$proposal['person_name'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    </div>
    <div class="col-md-4"><strong>Proposed by:</strong> <?= htmlspecialchars((string)$proposal['proposer_name'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="col-md-4"><strong>Submitted:</strong> <?= htmlspecialchars((string)$proposal['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php if (!empty($proposal['change_summary'])): ?>
    <div class="col-12"><strong>Summary:</strong> <?= htmlspecialchars((string)$proposal['change_summary'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
</div>

<h5>Changes (side-by-side)</h5>
<div class="table-responsive mb-4">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Field</th>
        <th>Current value</th>
        <th>Proposed value</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($changes as $field => $vals): ?>
    <tr>
      <td><code><?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?></code></td>
      <td class="text-danger"><?= htmlspecialchars((string)($vals['old'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
      <td class="text-success"><?= htmlspecialchars((string)($vals['new'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="d-flex gap-3 flex-wrap">
  <form method="post" action="/index.php?route=admin/approve-proposal">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="proposal_id" value="<?= (int)$proposal['proposal_id'] ?>">
    <button class="btn btn-success" type="submit" onclick="return confirm('Approve and apply these changes?')">Approve &amp; Apply</button>
  </form>

  <form method="post" action="/index.php?route=admin/reject-proposal" class="d-flex gap-2 align-items-start flex-wrap">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="proposal_id" value="<?= (int)$proposal['proposal_id'] ?>">
    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Rejection reason (optional)" style="min-width:240px;">
    <button class="btn btn-outline-danger btn-sm" type="submit">Reject</button>
  </form>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
