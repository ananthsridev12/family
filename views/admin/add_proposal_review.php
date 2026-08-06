<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<?php
$d = json_decode((string)($proposal['proposed_data'] ?? '{}'), true) ?? [];
$siblings = is_array($d['siblings'] ?? null) ? $d['siblings'] : [];
$totalChildren = (int)($d['total_children'] ?? 1);
$personPos = (int)($d['person_position'] ?? 1);
$fatherLinkedId = (int)($d['father_person_id'] ?? 0);
$motherLinkedId = (int)($d['mother_person_id'] ?? 0);
$fatherLinked = $fatherLinkedId > 0 ? $linkedPersons[$fatherLinkedId] ?? null : null;
$motherLinked = $motherLinkedId > 0 ? $linkedPersons[$motherLinkedId] ?? null : null;

function suffixFor(int $n): string {
    if ($n === 1) return 'st';
    if ($n === 2) return 'nd';
    if ($n === 3) return 'rd';
    return 'th';
}
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars((string)($d['full_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="text-muted mb-0" style="font-size:.85rem;">
      Submitted by <strong><?= htmlspecialchars((string)($proposal['proposer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
      on <?= date('d M Y, g:i a', strtotime((string)$proposal['created_at'])) ?>
    </p>
  </div>
  <a href="/index.php?route=admin/add-proposals" class="btn btn-outline-secondary btn-sm btn-pill">&larr; Back to list</a>
</div>

<div class="row g-4">
  <!-- Left: proposed data -->
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Proposed Details</h6>
        <table class="table table-sm mb-0">
          <tbody>
            <tr><td class="text-muted" style="width:140px;">Full Name</td><td class="fw-bold"><?= htmlspecialchars((string)($d['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><td class="text-muted">Gender</td><td><?= htmlspecialchars(ucfirst((string)($d['gender'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><td class="text-muted">Birth Year</td><td><?= $d['birth_year'] ? (int)$d['birth_year'] : '—' ?></td></tr>
            <tr><td class="text-muted">Date of Birth</td><td><?= $d['date_of_birth'] ? htmlspecialchars((string)$d['date_of_birth'], ENT_QUOTES, 'UTF-8') : '—' ?></td></tr>
            <tr><td class="text-muted">Status</td><td><?= ((int)($d['is_alive'] ?? 1)) ? '<span class="badge bg-success">Alive</span>' : '<span class="badge bg-secondary">Deceased</span>' ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Father -->
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Father</h6>
        <?php if ($fatherLinked): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary">Linked</span>
            <a href="/index.php?route=admin/person-view&id=<?= $fatherLinkedId ?>" target="_blank" class="fw-bold">
              <?= htmlspecialchars((string)($fatherLinked['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <span class="text-muted" style="font-size:.8rem;">(Person #<?= $fatherLinkedId ?>)</span>
          </div>
        <?php elseif (!empty($d['father_name'])): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark">New</span>
            <strong><?= htmlspecialchars((string)$d['father_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (!empty($d['father_birth_year'])): ?>
              <span class="text-muted" style="font-size:.8rem;">b. <?= (int)$d['father_birth_year'] ?></span>
            <?php endif; ?>
          </div>
          <div class="text-muted mt-1" style="font-size:.78rem;">A new person record will be created for this father.</div>
        <?php else: ?>
          <span class="text-muted">Not specified</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Mother -->
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Mother</h6>
        <?php if ($motherLinked): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary">Linked</span>
            <a href="/index.php?route=admin/person-view&id=<?= $motherLinkedId ?>" target="_blank" class="fw-bold">
              <?= htmlspecialchars((string)($motherLinked['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <span class="text-muted" style="font-size:.8rem;">(Person #<?= $motherLinkedId ?>)</span>
          </div>
        <?php elseif (!empty($d['mother_name'])): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark">New</span>
            <strong><?= htmlspecialchars((string)$d['mother_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (!empty($d['mother_birth_year'])): ?>
              <span class="text-muted" style="font-size:.8rem;">b. <?= (int)$d['mother_birth_year'] ?></span>
            <?php endif; ?>
          </div>
          <div class="text-muted mt-1" style="font-size:.78rem;">A new person record will be created for this mother.</div>
        <?php else: ?>
          <span class="text-muted">Not specified</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Siblings -->
    <?php if ($totalChildren > 0): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Sibling Order (<?= $totalChildren ?> children total)</h6>
        <div style="display:grid;gap:.4rem;">
          <?php for ($i = 1; $i <= $totalChildren; $i++):
            $isMe = ($i === $personPos);
            $sibInfo = null;
            foreach ($siblings as $s) {
              if ((int)($s['pos'] ?? 0) === $i) { $sibInfo = $s; break; }
            }
            $sibName = $isMe ? ($d['full_name'] ?? '') : ($sibInfo['name'] ?? '');
            $sibGender = $isMe ? ($d['gender'] ?? '') : ($sibInfo['gender'] ?? '');
            $suf = suffixFor($i);
          ?>
          <div style="display:flex;align-items:center;gap:.75rem;padding:.4rem .6rem;border-radius:8px;background:<?= $isMe ? '#eef2ff' : 'var(--ft-bg)' ?>">
            <span style="width:24px;height:24px;border-radius:50%;background:<?= $isMe ? 'var(--ft-primary)' : 'var(--ft-border)' ?>;color:<?= $isMe ? '#fff' : 'var(--ft-muted)' ?>;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $i ?></span>
            <span style="font-size:.83rem;font-weight:<?= $isMe ? '700' : '500' ?>;color:<?= $isMe ? 'var(--ft-primary)' : 'var(--ft-text)' ?>;">
              <?= $isMe ? '&#9658; ' : '' ?>
              <?= $sibName !== '' ? htmlspecialchars($sibName, ENT_QUOTES, 'UTF-8') : '<em class="text-muted">Unknown</em>' ?>
              <?= $sibGender ? ' <span class="text-muted">(' . htmlspecialchars($sibGender, ENT_QUOTES, 'UTF-8') . ')</span>' : '' ?>
            </span>
            <span class="text-muted ms-auto" style="font-size:.72rem;"><?= $i . $suf ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($d['admin_note'])): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-1">Note from submitter</h6>
        <p class="text-muted mb-0" style="font-style:italic;"><?= htmlspecialchars((string)$d['admin_note'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right: approve / reject -->
  <div class="col-lg-5">
    <div class="card mb-3" style="border-color:#d1fae5;">
      <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#059669;">&#10003; Approve</h6>
        <form method="post" action="/index.php?route=admin/approve-add-proposal">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="proposal_id" value="<?= (int)$proposal['proposal_id'] ?>">
          <div class="mb-3">
            <label class="form-label">Admin notes <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" name="admin_notes" rows="2" placeholder="Any notes for the submitter…"></textarea>
          </div>
          <button type="submit" class="btn btn-success btn-pill w-100">Approve &amp; Create Person</button>
        </form>
      </div>
    </div>

    <div class="card" style="border-color:#fecaca;">
      <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#dc2626;">&#10005; Reject</h6>
        <form method="post" action="/index.php?route=admin/reject-add-proposal">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="proposal_id" value="<?= (int)$proposal['proposal_id'] ?>">
          <div class="mb-3">
            <label class="form-label">Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" name="admin_notes" rows="2" placeholder="Tell the submitter why…" required></textarea>
          </div>
          <button type="submit" class="btn btn-outline-danger btn-pill w-100">Reject Submission</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
