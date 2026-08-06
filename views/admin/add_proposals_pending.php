<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>New Person Proposals</h1>
  <span class="badge bg-primary" style="font-size:.9rem;"><?= count($proposals) ?> pending</span>
</div>

<?php if (empty($proposals)): ?>
<div class="card card-body text-center py-5 text-muted">
  &#10003; No pending submissions — all caught up!
</div>
<?php else: ?>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Gender</th>
          <th>Birth Year</th>
          <th>Father</th>
          <th>Mother</th>
          <th>Submitted by</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proposals as $pr): ?>
        <?php $d = json_decode((string)$pr['proposed_data'], true) ?? []; ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars((string)($d['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($d['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= $d['birth_year'] ? (int)$d['birth_year'] : '—' ?></td>
          <td class="text-muted" style="font-size:.82rem;">
            <?php
              $fn = $d['father_person_id'] ? 'Linked #'.(int)$d['father_person_id'] : ($d['father_name'] ?: '—');
              echo htmlspecialchars($fn, ENT_QUOTES, 'UTF-8');
            ?>
          </td>
          <td class="text-muted" style="font-size:.82rem;">
            <?php
              $mn = $d['mother_person_id'] ? 'Linked #'.(int)$d['mother_person_id'] : ($d['mother_name'] ?: '—');
              echo htmlspecialchars($mn, ENT_QUOTES, 'UTF-8');
            ?>
          </td>
          <td><?= htmlspecialchars((string)($pr['proposer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:.8rem;" class="text-muted"><?= date('d M Y', strtotime((string)$pr['created_at'])) ?></td>
          <td>
            <a href="/index.php?route=admin/review-add-proposal&id=<?= (int)$pr['proposal_id'] ?>" class="btn btn-sm btn-primary btn-pill">Review</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
