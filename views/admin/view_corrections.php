<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>View Corrections</h1>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($requests)): ?>
<div class="card card-body text-center text-muted py-5">
  No pending correction requests.
</div>
<?php else: ?>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Person</th>
          <th>Requested By</th>
          <th>Contact</th>
          <th>Correction Note</th>
          <th>Received</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
        <tr>
          <td>
            <a href="/index.php?route=admin/person-view&id=<?= (int)$req['person_id'] ?>" class="fw-600 text-decoration-none">
              <?= htmlspecialchars((string)($req['person_name'] ?? '#'.$req['person_id']), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars((string)($req['requester_name'] ?? 'Anonymous'), ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:.83rem;"><?= htmlspecialchars((string)($req['requester_contact'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
          <td style="max-width:320px;">
            <div style="font-size:.85rem; white-space:pre-wrap; max-height:80px; overflow:auto;">
              <?= htmlspecialchars((string)$req['correction_note'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </td>
          <td style="font-size:.8rem; color:var(--ft-muted);">
            <?= date('d M Y', strtotime((string)$req['created_at'])) ?>
          </td>
          <td>
            <form method="post" action="/index.php?route=admin/mark-correction-reviewed">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="request_id" value="<?= (int)$req['request_id'] ?>">
              <input type="hidden" name="redirect_person" value="<?= (int)$req['person_id'] ?>">
              <button class="btn btn-sm btn-outline-success btn-pill" title="Mark as reviewed">&#10003; Done</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>
