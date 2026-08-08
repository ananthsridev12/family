<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<?php
$esc = fn(mixed $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$pid = (int)($person['person_id'] ?? 0);
$personName = (string)($person['full_name'] ?? '');

// Profile URL helper
$profileUrl = fn(?array $p): string =>
    ($p && (int)($p['person_id'] ?? 0) > 0)
        ? '/index.php?route=' . $profileRoute . '&id=' . (int)$p['person_id']
        : '#';
$wikiUrl = fn(?array $p): string =>
    ($p && (int)($p['person_id'] ?? 0) > 0)
        ? '/index.php?route=' . $wikiRoute . '&id=' . (int)$p['person_id']
        : '#';

// Format a date or fall back to birth_year
$fmtDate = function(?string $date, mixed $year): string {
    if ($date && $date !== '0000-00-00') {
        $ts = strtotime($date);
        return $ts ? date('j F Y', $ts) : $date;
    }
    return $year ? (string)$year : '';
};

// Calculate age
$calcAge = function(array $p): ?string {
    $dob = ($p['date_of_birth'] ?? '') ?: '';
    $dod = ($p['date_of_death'] ?? '') ?: '';
    if ($dob === '' || $dob === '0000-00-00') return null;
    $start = new DateTime($dob);
    $end   = ($dod && $dod !== '0000-00-00') ? new DateTime($dod) : new DateTime('today');
    $diff  = $start->diff($end);
    $label = ($dod && $dod !== '0000-00-00') ? 'died aged' : 'age';
    return $label . ' ' . $diff->y;
};

$born     = $fmtDate($person['date_of_birth'] ?? null, $person['birth_year'] ?? null);
$died     = $fmtDate($person['date_of_death'] ?? null, null);
$ageStr   = $calcAge($person);
$isAlive  = (int)($person['is_alive'] ?? 1) === 1;
$gender   = strtolower((string)($person['gender'] ?? ''));
$genderIcon = match($gender) {
    'male'   => '♂',
    'female' => '♀',
    default  => '⚧',
};
?>
<style>
/* ── Wikipedia-style wiki page ── */
.wiki-page { max-width: 1000px; margin: 0 auto; font-size: .96rem; }

/* Infobox */
.wiki-infobox {
  float: right;
  clear: right;
  margin: 0 0 1rem 1.5rem;
  border: 1px solid #a2a9b1;
  background: #f8f9fa;
  font-size: .875rem;
  width: 260px;
  border-collapse: collapse;
}
.wiki-infobox caption {
  background: #222e3c;
  color: #fff;
  font-weight: 600;
  font-size: .95rem;
  padding: .45rem .7rem;
  text-align: center;
}
.wiki-infobox th {
  background: #eaecf0;
  color: #202122;
  font-weight: 600;
  padding: .3rem .5rem;
  width: 40%;
  vertical-align: top;
  white-space: nowrap;
}
.wiki-infobox td {
  padding: .3rem .5rem;
  color: #202122;
}
.wiki-infobox tr { border-top: 1px solid #d0d4da; }

/* Section headings */
.wiki-h2 {
  font-size: 1.2rem;
  font-weight: 600;
  border-bottom: 1px solid #a2a9b1;
  padding-bottom: .2rem;
  margin: 1.6rem 0 .8rem;
  color: #202122;
}
.wiki-h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 1rem 0 .4rem;
  color: #36454f;
}

/* Pedigree chart */
.pedigree-wrap { overflow-x: auto; }
.pedigree {
  display: table;
  border-collapse: separate;
  border-spacing: 0;
  min-width: 580px;
  margin: .5rem 0 1rem;
}
.pedigree-row { display: table-row; }
.pedigree-cell { display: table-cell; vertical-align: middle; text-align: center; padding: 4px 6px; }

/* Person boxes in pedigree */
.wiki-box {
  display: inline-block;
  border: 1px solid #a2a9b1;
  border-radius: 4px;
  padding: .3rem .6rem;
  background: #fff;
  min-width: 110px;
  max-width: 160px;
  font-size: .8rem;
  text-align: center;
  vertical-align: middle;
  transition: box-shadow .15s;
}
.wiki-box:hover { box-shadow: 0 0 0 2px #4f46e5; }
.wiki-box a { text-decoration: none; color: #0645ad; font-weight: 500; }
.wiki-box a:hover { text-decoration: underline; }
.wiki-box .box-year { font-size: .72rem; color: #6c757d; }
.wiki-box.box-self {
  background: #eef2ff;
  border-color: #4f46e5;
  font-size: .9rem;
  min-width: 130px;
}
.wiki-box.box-self a { color: #3730a3; font-size: .95rem; font-weight: 700; }
.wiki-box.box-deceased { background: #f5f5f5; border-style: dashed; }

/* Connectors between pedigree rows */
.conn-down {
  display: block;
  width: 2px;
  height: 18px;
  background: #a2a9b1;
  margin: 0 auto;
}
.conn-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
}
.conn-hline { flex: 1; height: 2px; background: #a2a9b1; }
.conn-vbottom { width: 2px; height: 18px; background: #a2a9b1; margin: 0 auto; }

/* Couple bracket (─┬─) */
.couple-wrap { display: flex; align-items: center; gap: 4px; justify-content: center; }
.heart-sep { color: #f43f5e; font-size: .85rem; flex-shrink: 0; }

/* Children grid */
.children-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: .75rem;
  margin: .5rem 0;
}
.child-card {
  border: 1px solid #d0d4da;
  border-radius: 6px;
  padding: .6rem .75rem;
  background: #fff;
  font-size: .85rem;
}
.child-card a { text-decoration: none; color: #0645ad; font-weight: 600; }
.child-card a:hover { text-decoration: underline; }
.child-card .child-meta { color: #6c757d; font-size: .75rem; margin-top: 2px; }
.child-card .child-spouse { font-size: .78rem; color: #0891b2; margin-top: 3px; }

/* Siblings list */
.sibling-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin: .5rem 0; }
.sibling-chip {
  border: 1px solid #d0d4da;
  border-radius: 999px;
  padding: .2rem .75rem;
  font-size: .82rem;
  background: #fff;
}
.sibling-chip a { text-decoration: none; color: #0645ad; }
.sibling-chip a:hover { text-decoration: underline; }
.sibling-chip.chip-self { background: #eef2ff; border-color: #4f46e5; font-weight: 600; }
.sibling-chip.chip-deceased { background: #f5f5f5; color: #6c757d; border-style: dashed; }

/* Wiki page title area */
.wiki-title-bar { margin-bottom: .75rem; }
.wiki-title-bar h1 { font-size: 1.7rem; font-weight: 700; color: #202122; margin: 0 0 .1rem; }
.wiki-title-bar .wiki-subtitle { color: #6c757d; font-size: .9rem; margin: 0; }

/* Clear float after infobox */
.wiki-clearfix::after { content: ''; display: table; clear: both; }

/* Print: full width */
@media print {
  .wiki-infobox { float: none; width: 100%; margin: 0 0 1rem; }
}
</style>

<div class="wiki-page py-2">

  <!-- ── Breadcrumb ── -->
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="/index.php?route=<?= $esc($profileRoute) ?>">Family List</a></li>
      <li class="breadcrumb-item"><a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $pid ?>"><?= $esc($personName) ?></a></li>
      <li class="breadcrumb-item active">Wiki View</li>
    </ol>
  </nav>

  <!-- ── Infobox (float right) ── -->
  <table class="wiki-infobox">
    <caption><?= $esc($personName) ?></caption>
    <?php if ($born): ?>
    <tr><th>Born</th><td><?= $esc($born) ?><?= $ageStr ? ' <span style="color:#6c757d">(' . $esc($ageStr) . ')</span>' : '' ?></td></tr>
    <?php endif; ?>
    <?php if (!$isAlive && $died): ?>
    <tr><th>Died</th><td><?= $esc($died) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($person['blood_group'])): ?>
    <tr><th>Blood group</th><td><?= $esc($person['blood_group']) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($person['occupation'])): ?>
    <tr><th>Occupation</th><td><?= $esc($person['occupation']) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($person['native_location'])): ?>
    <tr><th>Native place</th><td><?= $esc($person['native_location']) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($person['current_location'])): ?>
    <tr><th>Resides</th><td><?= $esc($person['current_location']) ?></td></tr>
    <?php endif; ?>
    <?php if ($father): ?>
    <tr>
      <th>Father</th>
      <td><a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= (int)$father['person_id'] ?>"><?= $esc($father['full_name']) ?></a></td>
    </tr>
    <?php endif; ?>
    <?php if ($mother): ?>
    <tr>
      <th>Mother</th>
      <td><a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= (int)$mother['person_id'] ?>"><?= $esc($mother['full_name']) ?></a></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($person['spouse_name'])): ?>
    <tr>
      <th>Spouse</th>
      <td>
        <?php if ((int)($person['spouse_id'] ?? 0) > 0): ?>
          <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= (int)$person['spouse_id'] ?>"><?= $esc($person['spouse_name']) ?></a>
        <?php else: ?>
          <?= $esc($person['spouse_name']) ?>
        <?php endif; ?>
      </td>
    </tr>
    <?php endif; ?>
    <?php if (count($children) > 0): ?>
    <tr>
      <th>Children</th>
      <td><?= count($children) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (count($siblings) > 0): ?>
    <tr>
      <th>Siblings</th>
      <td><?= count($siblings) ?></td>
    </tr>
    <?php endif; ?>
  </table>

  <!-- ── Title ── -->
  <div class="wiki-title-bar">
    <h1>
      <span style="font-size:.9rem;margin-right:.3rem;"><?= $genderIcon ?></span><?= $esc($personName) ?>
      <?php if (!$isAlive): ?><span style="font-size:.75rem;color:#6c757d;font-weight:400"> (deceased)</span><?php endif; ?>
    </h1>
    <p class="wiki-subtitle">
      Family profile
      <?php if ($born): ?>&mdash; Born <?= $esc($born) ?><?php endif; ?>
    </p>
  </div>

  <div class="wiki-clearfix">

  <!-- ── Ancestry ── -->
  <div class="wiki-h2">Ancestry</div>
  <?php
  $hasAncestors = $father || $mother || $patGrandfather || $patGrandmother || $matGrandfather || $matGrandmother;
  ?>
  <?php if (!$hasAncestors): ?>
    <p class="text-muted small">No ancestry information recorded.</p>
  <?php else: ?>

  <?php
  // Helper: render one person box
  $box = function(?array $p, bool $isSelf = false) use ($esc, $fmtDate, $profileRoute, $wikiRoute): string {
      if ($p === null) {
          return '<div class="wiki-box" style="border-style:dashed;color:#aaa;font-size:.78rem;">—</div>';
      }
      $name = $esc((string)($p['full_name'] ?? ''));
      $year = $esc((string)($p['birth_year'] ?? ''));
      $pid  = (int)($p['person_id'] ?? 0);
      $alive = (int)($p['is_alive'] ?? 1);
      $cls  = 'wiki-box' . ($isSelf ? ' box-self' : '') . ($alive === 0 ? ' box-deceased' : '');
      $link = $pid > 0 ? '/index.php?route=' . $profileRoute . '&id=' . $pid : '#';
      $wlink = $pid > 0 ? '/index.php?route=' . $wikiRoute . '&id=' . $pid : '#';
      $nameHtml = $pid > 0
          ? '<a href="' . $link . '" title="Profile">' . $name . '</a>'
          : $name;
      $yearHtml = $year ? '<div class="box-year">' . $year . '</div>' : '';
      $wikiBtn  = $pid > 0 && !$isSelf
          ? '<div><a href="' . $wlink . '" style="font-size:.68rem;color:#888;" title="Wiki view">&#128196;</a></div>'
          : '';
      return '<div class="' . $cls . '">' . $nameHtml . $yearHtml . $wikiBtn . '</div>';
  };

  // Connector helper: vertical bar
  $vbar = '<div class="conn-down"></div>';
  ?>

  <div class="pedigree-wrap">

  <!-- Row: grandparents -->
  <?php if ($patGrandfather || $patGrandmother || $matGrandfather || $matGrandmother): ?>
  <div style="display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;margin-bottom:.25rem;">

    <?php if ($father && ($patGrandfather || $patGrandmother)): ?>
    <div style="text-align:center;">
      <div class="wiki-h3" style="font-size:.72rem;color:#888;margin:.2rem 0;">Paternal</div>
      <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;">
        <?php if ($patGrandfather): ?>
        <div><?= $box($patGrandfather) ?></div>
        <?php endif; ?>
        <?php if ($patGrandmother): ?>
        <div><?= $box($patGrandmother) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($mother && ($matGrandfather || $matGrandmother)): ?>
    <div style="text-align:center;">
      <div class="wiki-h3" style="font-size:.72rem;color:#888;margin:.2rem 0;">Maternal</div>
      <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;">
        <?php if ($matGrandfather): ?>
        <div><?= $box($matGrandfather) ?></div>
        <?php endif; ?>
        <?php if ($matGrandmother): ?>
        <div><?= $box($matGrandmother) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
  <!-- connectors down to parents -->
  <div style="display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;margin-bottom:.1rem;">
    <?php if ($father && ($patGrandfather || $patGrandmother)): ?>
    <div style="text-align:center;width:auto;"><?= $vbar ?></div>
    <?php endif; ?>
    <?php if ($mother && ($matGrandfather || $matGrandmother)): ?>
    <div style="text-align:center;width:auto;"><?= $vbar ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Row: parents -->
  <?php if ($father || $mother): ?>
  <div style="display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;margin-bottom:.25rem;">
    <?php if ($father): ?>
    <div style="text-align:center;">
      <?php if (empty($patGrandfather) && empty($patGrandmother)): ?>
        <div class="wiki-h3" style="font-size:.72rem;color:#888;margin:.2rem 0;">Father</div>
      <?php endif; ?>
      <?= $box($father) ?>
    </div>
    <?php endif; ?>
    <?php if ($mother): ?>
    <div style="text-align:center;">
      <?php if (empty($matGrandfather) && empty($matGrandmother)): ?>
        <div class="wiki-h3" style="font-size:.72rem;color:#888;margin:.2rem 0;">Mother</div>
      <?php endif; ?>
      <?= $box($mother) ?>
    </div>
    <?php endif; ?>
  </div>
  <!-- connector down to self -->
  <div style="text-align:center;"><?= $vbar ?></div>
  <?php endif; ?>

  <!-- Row: subject (+ spouse) -->
  <div style="display:flex;gap:.4rem;justify-content:center;align-items:center;margin-bottom:.5rem;flex-wrap:wrap;">
    <?= $box($person, true) ?>
    <?php if (!empty($person['spouse_name'])): ?>
    <span class="heart-sep">♥</span>
    <?php
    $spouseArr = null;
    if ((int)($person['spouse_id'] ?? 0) > 0) {
        $spouseArr = [
            'person_id'  => $person['spouse_id'],
            'full_name'  => $person['spouse_name'],
            'birth_year' => '',
            'is_alive'   => 1,
        ];
    }
    ?>
    <?= $box($spouseArr) ?>
    <?php endif; ?>
  </div>

  </div><!-- /.pedigree-wrap -->
  <?php endif; // hasAncestors ?>

  <!-- ── Children ── -->
  <?php if (count($children) > 0): ?>
  <div class="wiki-h2">Children (<?= count($children) ?>)</div>
  <div class="children-grid">
    <?php foreach ($children as $child): ?>
    <?php
    $cId    = (int)($child['person_id'] ?? 0);
    $cAlive = (int)($child['is_alive'] ?? 1);
    $cYear  = $child['birth_year'] ?? '';
    $cSpouse = trim((string)($child['spouse_name'] ?? ''));
    $cSpouseId = (int)($child['spouse_id'] ?? 0);
    $cGender = strtolower((string)($child['gender'] ?? ''));
    $cIcon = match($cGender) { 'male' => '♂', 'female' => '♀', default => '' };
    ?>
    <div class="child-card<?= $cAlive === 0 ? ' box-deceased' : '' ?>">
      <div>
        <?= $cIcon ? '<span style="color:#94a3b8;font-size:.8rem;">' . $cIcon . '</span> ' : '' ?>
        <?php if ($cId > 0): ?>
          <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $cId ?>"><?= $esc((string)($child['full_name'] ?? '')) ?></a>
        <?php else: ?>
          <?= $esc((string)($child['full_name'] ?? '')) ?>
        <?php endif; ?>
        <?php if ($cAlive === 0): ?> <span style="font-size:.7rem;color:#888;">(†)</span><?php endif; ?>
      </div>
      <?php if ($cYear): ?><div class="child-meta">b. <?= $esc($cYear) ?></div><?php endif; ?>
      <?php if ($cSpouse !== ''): ?>
      <div class="child-spouse">
        ♥
        <?php if ($cSpouseId > 0): ?>
          <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $cSpouseId ?>"><?= $esc($cSpouse) ?></a>
        <?php else: ?>
          <?= $esc($cSpouse) ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($cId > 0): ?>
      <div class="mt-1">
        <a href="/index.php?route=<?= $esc($wikiRoute) ?>&id=<?= $cId ?>" style="font-size:.72rem;color:#888;" title="Wiki view">&#128196; Wiki</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Siblings ── -->
  <?php if (count($siblings) > 0): ?>
  <div class="wiki-h2">Siblings (<?= count($siblings) ?>)</div>
  <div class="sibling-chips">
    <?php foreach ($siblings as $sib): ?>
    <?php
    $sId    = (int)($sib['person_id'] ?? 0);
    $sAlive = (int)($sib['is_alive'] ?? 1);
    $sYear  = $sib['birth_year'] ?? '';
    $sSpouse = trim((string)($sib['spouse_name'] ?? ''));
    $sSpouseId = (int)($sib['spouse_id'] ?? 0);
    ?>
    <span class="sibling-chip<?= $sAlive === 0 ? ' chip-deceased' : '' ?>">
      <?php if ($sId > 0): ?>
        <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $sId ?>"><?= $esc((string)($sib['full_name'] ?? '')) ?></a>
      <?php else: ?>
        <?= $esc((string)($sib['full_name'] ?? '')) ?>
      <?php endif; ?>
      <?php if ($sYear): ?><span style="color:#888;font-size:.72rem;"> <?= $esc($sYear) ?></span><?php endif; ?>
      <?php if ($sSpouse !== ''): ?>
        <span style="color:#f43f5e;">♥</span>
        <?php if ($sSpouseId > 0): ?>
          <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $sSpouseId ?>"><?= $esc($sSpouse) ?></a>
        <?php else: ?>
          <?= $esc($sSpouse) ?>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($sAlive === 0): ?> †<?php endif; ?>
    </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  </div><!-- /.wiki-clearfix -->

  <!-- ── Action bar ── -->
  <div class="d-flex gap-2 flex-wrap mt-4 pt-3 border-top">
    <a href="/index.php?route=<?= $esc($profileRoute) ?>&id=<?= $pid ?>" class="btn btn-outline-secondary btn-sm">
      ← Back to Profile
    </a>
    <?php if ($father): ?>
    <a href="/index.php?route=<?= $esc($wikiRoute) ?>&id=<?= (int)$father['person_id'] ?>" class="btn btn-outline-primary btn-sm">
      ↑ Father's Wiki
    </a>
    <?php endif; ?>
    <?php if ($mother): ?>
    <a href="/index.php?route=<?= $esc($wikiRoute) ?>&id=<?= (int)$mother['person_id'] ?>" class="btn btn-outline-primary btn-sm">
      ↑ Mother's Wiki
    </a>
    <?php endif; ?>
    <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm ms-auto">
      &#128438; Print
    </a>
  </div>

</div><!-- /.wiki-page -->
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
