<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?> — Family Profile</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  :root {
    --vp-primary: #4f46e5;
    --vp-bg: #f8fafc;
    --vp-card: #ffffff;
    --vp-border: #e2e8f0;
    --vp-muted: #64748b;
    --vp-text: #1e293b;
  }
  body { background: var(--vp-bg); color: var(--vp-text); font-family: 'Inter', system-ui, sans-serif; }
  .vp-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #fff;
    padding: 2.5rem 1rem 3.5rem;
    text-align: center;
  }
  .vp-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(255,255,255,.25); font-size: 2rem;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 1rem; border: 3px solid rgba(255,255,255,.4);
  }
  .vp-hero h1 { font-size: 1.7rem; font-weight: 700; margin-bottom: .25rem; }
  .vp-hero .sub { font-size: .9rem; opacity: .8; }
  .vp-body { max-width: 700px; margin: -1.5rem auto 0; padding: 0 1rem 3rem; }
  .vp-card { background: var(--vp-card); border-radius: 16px; border: 1px solid var(--vp-border); margin-bottom: 1.25rem; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
  .vp-card-header { padding: .75rem 1.25rem; background: #f1f5f9; border-bottom: 1px solid var(--vp-border); font-weight: 600; font-size: .85rem; letter-spacing: .04em; text-transform: uppercase; color: var(--vp-muted); }
  .vp-card-body { padding: 1.25rem; }
  .info-row { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
  .info-row:last-child { border-bottom: none; }
  .info-label { color: var(--vp-muted); min-width: 130px; }
  .info-value { font-weight: 500; text-align: right; }
  .person-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #eef2ff; color: #4f46e5; border-radius: 999px;
    padding: .2rem .7rem; font-size: .82rem; font-weight: 600;
  }
  .person-pill.female { background: #fdf2f8; color: #9d174d; }
  .sibling-list { display: flex; flex-wrap: wrap; gap: .5rem; }
  .correction-form { background: #f8fafc; border-radius: 12px; padding: 1.25rem; border: 1px solid #e2e8f0; }
  .vp-footer { text-align: center; color: var(--vp-muted); font-size: .78rem; padding: 2rem 1rem; }
  .alert-vp { border-radius: 12px; padding: .85rem 1.2rem; margin-bottom: 1rem; font-size: .9rem; }
  .alert-vp.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .alert-vp.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>

<div class="vp-hero">
  <div class="vp-avatar">
    <?php
      $g = strtolower((string)($person['gender'] ?? ''));
      echo $g === 'female' ? '&#128105;' : ($g === 'male' ? '&#128104;' : '&#128100;');
    ?>
  </div>
  <h1><?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
  <div class="sub">
    <?php if (!empty($person['birth_year'])): ?>
      Born <?= (int)$person['birth_year'] ?>
    <?php endif; ?>
    <?php if (!empty($person['native_location'])): ?>
      &middot; <?= htmlspecialchars((string)$person['native_location'], ENT_QUOTES, 'UTF-8') ?>
    <?php endif; ?>
  </div>
</div>

<div class="vp-body">

  <?php if (!empty($flash)): ?>
  <div class="alert-vp <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <!-- Basic Info -->
  <div class="vp-card">
    <div class="vp-card-header">Personal Details</div>
    <div class="vp-card-body">
      <?php
        $fields = [
          'Gender'       => $person['gender'] ?? '',
          'Date of Birth'=> !empty($person['date_of_birth']) ? date('d M Y', strtotime((string)$person['date_of_birth'])) : ($person['birth_year'] ?? ''),
          'Blood Group'  => $person['blood_group'] ?? '',
          'Occupation'   => $person['occupation'] ?? '',
          'Location'     => $person['current_location'] ?? '',
          'Native'       => $person['native_location'] ?? '',
        ];
        foreach ($fields as $label => $val):
          if ((string)$val === '') continue;
      ?>
      <div class="info-row">
        <span class="info-label"><?= $label ?></span>
        <span class="info-value"><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Family Circle -->
  <?php if (!empty($person['father_name']) || !empty($person['mother_name']) || !empty($person['spouse_name']) || !empty($children) || !empty($siblings)): ?>
  <div class="vp-card">
    <div class="vp-card-header">Family</div>
    <div class="vp-card-body">

      <?php if (!empty($person['father_name'])): ?>
      <div class="info-row">
        <span class="info-label">Father</span>
        <span class="info-value">
          <span class="person-pill"><?= htmlspecialchars((string)$person['father_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </span>
      </div>
      <?php endif; ?>

      <?php if (!empty($person['mother_name'])): ?>
      <div class="info-row">
        <span class="info-label">Mother</span>
        <span class="info-value">
          <span class="person-pill female"><?= htmlspecialchars((string)$person['mother_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </span>
      </div>
      <?php endif; ?>

      <?php if (!empty($person['spouse_name'])): ?>
      <div class="info-row">
        <span class="info-label">Spouse</span>
        <span class="info-value">
          <?php $sg = ''; /* unknown spouse gender */ ?>
          <span class="person-pill"><?= htmlspecialchars((string)$person['spouse_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </span>
      </div>
      <?php endif; ?>

      <?php if (!empty($siblings)): ?>
      <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:.5rem;">
        <span class="info-label">Siblings (<?= count($siblings) ?>)</span>
        <div class="sibling-list">
          <?php foreach ($siblings as $sib): ?>
            <?php $sc = strtolower((string)($sib['gender'] ?? '')) === 'female' ? 'female' : ''; ?>
            <span class="person-pill <?= $sc ?>">
              <?= htmlspecialchars((string)$sib['full_name'], ENT_QUOTES, 'UTF-8') ?>
              <?php if (!empty($sib['birth_year'])): ?>
                <span style="opacity:.6;font-weight:400;"><?= (int)$sib['birth_year'] ?></span>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($children)): ?>
      <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:.5rem;">
        <span class="info-label">Children (<?= count($children) ?>)</span>
        <div class="sibling-list">
          <?php foreach ($children as $child): ?>
            <?php $cc = strtolower((string)($child['gender'] ?? '')) === 'female' ? 'female' : ''; ?>
            <span class="person-pill <?= $cc ?>">
              <?= htmlspecialchars((string)$child['full_name'], ENT_QUOTES, 'UTF-8') ?>
              <?php if (!empty($child['birth_year'])): ?>
                <span style="opacity:.6;font-weight:400;"><?= (int)$child['birth_year'] ?></span>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

  <!-- Request Correction -->
  <?php if (empty($flash) || $flash['type'] !== 'success'): ?>
  <div class="vp-card">
    <div class="vp-card-header">Request a Correction</div>
    <div class="vp-card-body">
      <p style="font-size:.85rem; color:var(--vp-muted); margin-bottom:1rem;">
        See something wrong? Let us know and the admin will review and update the details.
      </p>
      <form method="post" action="/index.php?route=view/request-correction">
        <input type="hidden" name="token" value="<?= htmlspecialchars((string)($tokenRow['token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
        <div class="correction-form">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Your Name <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control form-control-sm" name="requester_name" placeholder="e.g. Ramesh Kumar">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Your Phone / Email <span class="text-muted fw-normal">(optional, so admin can follow up)</span></label>
            <input class="form-control form-control-sm" name="requester_contact" placeholder="e.g. 98765 43210">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">What needs to be corrected? <span class="text-danger">*</span></label>
            <textarea class="form-control form-control-sm" name="correction_note" rows="4" required
              placeholder="e.g. My date of birth is wrong. It should be 12 June 1975, not 1974. Also my mother's name is Kavitha, not Kaveri."></textarea>
          </div>
          <button type="submit" class="btn btn-sm btn-primary" style="background:var(--vp-primary);border-color:var(--vp-primary);border-radius:999px;padding:.4rem 1.2rem;">
            Submit Correction Request
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>

<div class="vp-footer">
  This is a private family profile shared securely. Do not share this link with others.
</div>

</body>
</html>
