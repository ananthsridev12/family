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
    --vp-bg: #f1f5f9;
    --vp-card: #ffffff;
    --vp-border: #e2e8f0;
    --vp-muted: #64748b;
    --vp-text: #1e293b;
  }
  body { background: var(--vp-bg); color: var(--vp-text); font-family: system-ui, sans-serif; }

  /* Hero */
  .vp-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #fff; padding: 2.5rem 1rem 4rem; text-align: center;
  }
  .vp-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(255,255,255,.2); font-size: 1.8rem;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: .75rem; border: 3px solid rgba(255,255,255,.35);
  }
  .vp-hero h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: .2rem; }
  .vp-hero .sub { font-size: .88rem; opacity: .8; }

  /* Body */
  .vp-body { max-width: 720px; margin: -2rem auto 0; padding: 0 1rem 3rem; }

  /* Flash */
  .vp-flash { border-radius: 12px; padding: .8rem 1.1rem; margin-bottom: 1rem; font-size: .88rem; }
  .vp-flash.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .vp-flash.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

  /* Section heading */
  .vp-section-title {
    font-size: .72rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--vp-muted);
    margin: 1.75rem 0 .6rem;
  }

  /* Person card */
  .person-card {
    background: var(--vp-card); border: 1px solid var(--vp-border);
    border-radius: 14px; overflow: hidden; margin-bottom: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
  }
  .person-card-top {
    display: flex; align-items: center; gap: .85rem;
    padding: .9rem 1.1rem;
  }
  .pc-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; background: #eef2ff; color: #4f46e5;
  }
  .pc-avatar.female { background: #fdf2f8; color: #9d174d; }
  .pc-name { font-weight: 700; font-size: .97rem; }
  .pc-meta { font-size: .78rem; color: var(--vp-muted); margin-top: .05rem; }
  .pc-actions { margin-left: auto; }
  .btn-correct {
    font-size: .75rem; border: 1px solid #c7d2fe; background: #eef2ff;
    color: #4f46e5; border-radius: 999px; padding: .28rem .75rem;
    cursor: pointer; transition: background .15s;
  }
  .btn-correct:hover { background: #e0e7ff; }
  .btn-correct.open { background: #4f46e5; color: #fff; border-color: #4f46e5; }

  /* Details grid inside a person card */
  .pc-details {
    padding: 0 1.1rem .85rem; display: grid;
    grid-template-columns: 1fr 1fr; gap: .3rem .75rem;
    font-size: .83rem;
  }
  .pc-detail { display: flex; flex-direction: column; }
  .pc-detail-label { font-size: .7rem; color: var(--vp-muted); text-transform: uppercase; letter-spacing: .05em; }
  .pc-detail-value { font-weight: 500; }

  /* Inline correction form */
  .correction-inline {
    display: none; border-top: 1px solid var(--vp-border);
    background: #f8fafc; padding: 1rem 1.1rem;
  }
  .correction-inline.open { display: block; }
  .correction-inline label { font-size: .8rem; font-weight: 600; margin-bottom: .2rem; display: block; }
  .correction-inline input,
  .correction-inline textarea { font-size: .83rem; }

  /* Footer */
  .vp-footer { text-align: center; color: var(--vp-muted); font-size: .75rem; padding: 1.5rem 1rem 2.5rem; }

  @media (max-width: 480px) {
    .pc-details { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<?php
// Helper: render a full person card with expandable correction form
function renderPersonCard(array $p, string $token, string $relation, string $uid): void {
    $g = strtolower((string)($p['gender'] ?? ''));
    $isFemale = $g === 'female';
    $gIcon = $isFemale ? '&#128105;' : ($g === 'male' ? '&#128104;' : '&#128100;');

    $metaParts = [];
    if (!empty($p['birth_year'])) $metaParts[] = 'b. ' . (int)$p['birth_year'];
    if (!empty($p['native_location'])) $metaParts[] = htmlspecialchars((string)$p['native_location'], ENT_QUOTES, 'UTF-8');
    $meta = implode(' · ', $metaParts);

    $formId = 'cf-' . $uid;

    echo '<div class="person-card">';

    // Top row
    echo '<div class="person-card-top">';
    echo '<div class="pc-avatar' . ($isFemale ? ' female' : '') . '">' . $gIcon . '</div>';
    echo '<div>';
    echo '<div class="pc-name">' . htmlspecialchars((string)$p['full_name'], ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div class="pc-meta">' . htmlspecialchars($relation, ENT_QUOTES, 'UTF-8') . ($meta !== '' ? ' · ' . $meta : '') . '</div>';
    echo '</div>';
    echo '<div class="pc-actions">';
    echo '<button class="btn-correct" onclick="toggleCorrection(\'' . $formId . '\', this)">&#9998; Correction</button>';
    echo '</div>';
    echo '</div>';

    // Details grid
    $details = [
        'Gender'       => $p['gender'] ?? '',
        'Date of Birth'=> !empty($p['date_of_birth']) ? date('d M Y', strtotime((string)$p['date_of_birth'])) : '',
        'Birth Year'   => empty($p['date_of_birth']) && !empty($p['birth_year']) ? (string)(int)$p['birth_year'] : '',
        'Blood Group'  => $p['blood_group'] ?? '',
        'Occupation'   => $p['occupation'] ?? '',
        'Current Location' => $p['current_location'] ?? '',
        'Native'       => $p['native_location'] ?? '',
        'Mobile'       => $p['mobile'] ?? '',
        'Email'        => $p['email'] ?? '',
        'Address'      => $p['address'] ?? '',
    ];
    $hasDetail = false;
    foreach ($details as $v) { if ((string)$v !== '') { $hasDetail = true; break; } }

    if ($hasDetail) {
        echo '<div class="pc-details">';
        foreach ($details as $lbl => $val) {
            if ((string)$val === '') continue;
            echo '<div class="pc-detail">';
            echo '<span class="pc-detail-label">' . $lbl . '</span>';
            echo '<span class="pc-detail-value">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }

    // Inline correction form
    echo '<div class="correction-inline" id="' . $formId . '">';
    echo '<form method="post" action="/index.php?route=view/request-correction">';
    echo '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="person_id" value="' . (int)$p['person_id'] . '">';
    echo '<div class="row g-2 mb-2">';
    echo '<div class="col-6"><label>Your Name <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>';
    echo '<input class="form-control form-control-sm" name="requester_name" placeholder="e.g. Ramesh"></div>';
    echo '<div class="col-6"><label>Your Phone/Email <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>';
    echo '<input class="form-control form-control-sm" name="requester_contact" placeholder="e.g. 98765 43210"></div>';
    echo '</div>';
    echo '<label>What needs to be corrected? <span style="color:#ef4444;">*</span></label>';
    echo '<textarea class="form-control form-control-sm mb-2" name="correction_note" rows="3" required ';
    echo 'placeholder="Describe what is wrong and what the correct information should be…"></textarea>';
    echo '<button type="submit" class="btn btn-sm btn-primary" style="border-radius:999px;font-size:.8rem;padding:.3rem 1rem;">Submit</button>';
    echo '</form>';
    echo '</div>';

    echo '</div>';
}
?>

<div class="vp-hero">
  <div class="vp-avatar">
    <?php
      $g = strtolower((string)($person['gender'] ?? ''));
      echo $g === 'female' ? '&#128105;' : ($g === 'male' ? '&#128104;' : '&#128100;');
    ?>
  </div>
  <h1><?= htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
  <div class="sub">
    <?php $heroParts = []; ?>
    <?php if (!empty($person['birth_year'])): $heroParts[] = 'Born ' . (int)$person['birth_year']; endif; ?>
    <?php if (!empty($person['current_location'])): $heroParts[] = htmlspecialchars((string)$person['current_location'], ENT_QUOTES, 'UTF-8'); endif; ?>
    <?= implode(' &middot; ', $heroParts) ?>
  </div>
</div>

<div class="vp-body">

  <?php if (!empty($flash)): ?>
  <div class="vp-flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <!-- Main person card -->
  <div class="vp-section-title">Your Profile</div>
  <?php renderPersonCard($person, (string)$tokenRow['token'], 'You', 'self'); ?>

  <!-- Parents -->
  <?php if ($father !== null || $mother !== null): ?>
  <div class="vp-section-title">Parents</div>
  <?php if ($father !== null): renderPersonCard($father, (string)$tokenRow['token'], 'Father', 'father'); endif; ?>
  <?php if ($mother !== null): renderPersonCard($mother, (string)$tokenRow['token'], 'Mother', 'mother'); endif; ?>
  <?php endif; ?>

  <!-- Spouse -->
  <?php if ($spouse !== null): ?>
  <div class="vp-section-title">Spouse</div>
  <?php renderPersonCard($spouse, (string)$tokenRow['token'], 'Spouse', 'spouse'); ?>
  <?php endif; ?>

  <!-- Siblings -->
  <?php if (!empty($siblings)): ?>
  <div class="vp-section-title">Siblings (<?= count($siblings) ?>)</div>
  <?php foreach ($siblings as $i => $sib): ?>
    <?php renderPersonCard($sib, (string)$tokenRow['token'], 'Sibling', 'sib-' . $i); ?>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- Children -->
  <?php if (!empty($children)): ?>
  <div class="vp-section-title">Children (<?= count($children) ?>)</div>
  <?php foreach ($children as $i => $child): ?>
    <?php renderPersonCard($child, (string)$tokenRow['token'], 'Child', 'child-' . $i); ?>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<div class="vp-footer">
  <?php
    $currentUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/index.php?route=view&token=' . urlencode((string)($tokenRow['token'] ?? ''));
    $waMsg = 'Here is my family profile: ' . $currentUrl;
  ?>
  <a href="https://wa.me/?text=<?= urlencode($waMsg) ?>" target="_blank" rel="noopener"
     style="display:inline-flex;align-items:center;gap:.4rem;background:#25d366;color:#fff;border-radius:999px;padding:.4rem 1rem;font-size:.82rem;font-weight:600;text-decoration:none;margin-bottom:1rem;">
    &#128172; Share on WhatsApp
  </a>
  <br>This is a private family profile. Use the &#9998; Correction button on any person to report wrong details.
</div>

<script>
function toggleCorrection(id, btn) {
  var el = document.getElementById(id);
  var isOpen = el.classList.contains('open');
  // Close all open forms first
  document.querySelectorAll('.correction-inline.open').forEach(function(f) { f.classList.remove('open'); });
  document.querySelectorAll('.btn-correct.open').forEach(function(b) { b.classList.remove('open'); });
  if (!isOpen) {
    el.classList.add('open');
    btn.classList.add('open');
    el.querySelector('textarea').focus();
  }
}
</script>

</body>
</html>
