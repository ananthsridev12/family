<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Pending Edit Proposals</h1>

<?php if (!empty($success ?? null)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($error ?? null)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
if ($flashSuccess): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($proposals)): ?>
  <p class="text-muted">No pending proposals.</p>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Person</th>
        <th>Proposed By</th>
        <th>Fields Changed</th>
        <th>Submitted</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($proposals as $p): ?>
    <?php $changes = json_decode((string)$p['proposed_changes'], true) ?: []; ?>
    <tr>
      <td><?= (int)$p['proposal_id'] ?></td>
      <td>
        <a href="/index.php?route=admin/person-view&id=<?= (int)$p['person_id'] ?>">
          <?= htmlspecialchars((string)$p['person_name'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      </td>
      <td><?= htmlspecialchars((string)$p['proposer_name'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars(implode(', ', array_keys($changes)), ENT_QUOTES, 'UTF-8') ?></td>
      <td class="small text-muted"><?= htmlspecialchars((string)$p['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
      <td>
        <a href="/index.php?route=admin/proposal-review&id=<?= (int)$p['proposal_id'] ?>" class="btn btn-sm btn-outline-primary">Review</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
