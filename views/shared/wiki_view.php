<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<?php
$esc = fn(mixed $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$pid = (int)($person['person_id'] ?? 0);
$personName = (string)($person['full_name'] ?? '');
$isAlive  = (int)($person['is_alive'] ?? 1) === 1;
$gender   = strtolower((string)($person['gender'] ?? ''));
$genderIcon = match($gender) { 'male' => '♂', 'female' => '♀', default => '' };

$fmtDate = function(mixed $date, mixed $year): string {
    $d = (string)($date ?? '');
    if ($d && $d !== '0000-00-00') {
        $ts = strtotime($d);
        return $ts ? date('j M Y', $ts) : $d;
    }
    return $year ? (string)$year : '';
};

$calcAge = function(array $p): ?string {
    $dob = ($p['date_of_birth'] ?? '') ?: '';
    $dod = ($p['date_of_death'] ?? '') ?: '';
    if ($dob === '' || $dob === '0000-00-00') return null;
    $start = new DateTime($dob);
    $end   = ($dod && $dod !== '0000-00-00') ? new DateTime($dod) : new DateTime('today');
    return (string)$start->diff($end)->y;
};

$born   = $fmtDate($person['date_of_birth'] ?? null, $person['birth_year'] ?? null);
$died   = $fmtDate($person['date_of_death'] ?? null, null);
$ageStr = $calcAge($person);

// Link helpers
$pUrl = fn(int $id): string => '/index.php?route=' . $profileRoute . '&id=' . $id;
$wUrl = fn(int $id): string => '/index.php?route=' . $wikiRoute . '&id=' . $id;

// Recursive ancestor tree renderer
// Renders a right-to-left pedigree list: deepest ancestors on left, subject on right
function renderAncestorBranch(array $node, string $pRoute, string $wRoute, int $depth = 0): string
{
    $id   = (int)($node['person_id'] ?? 0);
    $name = htmlspecialchars((string)($node['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $year = htmlspecialchars((string)($node['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8');
    $alive = (int)($node['is_alive'] ?? 1);
    $gender = strtolower((string)($node['gender'] ?? ''));
    $gIcon = match($gender) { 'male' => '♂', 'female' => '♀', default => '' };
    $hasFather = !empty($node['father']);
    $hasMother = !empty($node['mother']);
    $hasParents = $hasFather || $hasMother;

    // Style the box
    $boxCls = 'anc-box' . ($alive === 0 ? ' anc-deceased' : '');

    $nameHtml = $id > 0
        ? '<a href="/index.php?route=' . htmlspecialchars($pRoute, ENT_QUOTES, 'UTF-8') . '&id=' . $id . '" class="anc-name">' . $name . '</a>'
        : '<span class="anc-name">' . $name . '</span>';
    $wikiHtml = $id > 0
        ? '<a href="/index.php?route=' . htmlspecialchars($wRoute, ENT_QUOTES, 'UTF-8') . '&id=' . $id . '" class="anc-wiki" title="Wiki view">📖</a>'
        : '';
    $yearHtml = $year ? '<span class="anc-year">' . $year . '</span>' : '';
    $gHtml    = $gIcon ? '<span class="anc-gender">' . $gIcon . '</span>' : '';

    $boxHtml = '<div class="' . $boxCls . '">'
        . $gHtml . $nameHtml . $yearHtml . $wikiHtml
        . '</div>';

    if (!$hasParents) {
        return '<div class="anc-leaf">' . $boxHtml . '</div>';
    }

    $branches = '';
    if ($hasFather) {
        $branches .= '<div class="anc-branch anc-father">'
            . renderAncestorBranch($node['father'], $pRoute, $wRoute, $depth + 1)
            . '</div>';
    }
    if ($hasMother) {
        $branches .= '<div class="anc-branch anc-mother">'
            . renderAncestorBranch($node['mother'], $pRoute, $wRoute, $depth + 1)
            . '</div>';
    }

    return '<div class="anc-unit">'
        . '<div class="anc-parents">' . $branches . '</div>'
        . '<div class="anc-connector"><div class="anc-vline"></div></div>'
        . '<div class="anc-self">' . $boxHtml . '</div>'
        . '</div>';
}
?>
<style>
/* ═══ Wiki page layout ═══ */
.wiki-wrap {
  display: grid;
  grid-template-columns: 1fr 260px;
  grid-template-rows: auto;
  gap: 0 1.5rem;
  max-width: 1060px;
  margin: 0 auto;
}
@media (max-width: 768px) {
  .wiki-wrap { grid-template-columns: 1fr; }
  .wiki-sidebar { order: -1; }
}

/* ═══ Infobox ═══ */
.wiki-sidebar { grid-column: 2; grid-row: 1 / 6; align-self: start; }
@media (max-width: 768px) { .wiki-sidebar { grid-column: 1; grid-row: auto; } }

.wiki-infobox {
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  font-size: .85rem;
  background: #f8fafc;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
  position: sticky;
  top: 72px;
}
.wiki-infobox-head {
  background: #1e293b;
  color: #fff;
  padding: .6rem .8rem;
  font-weight: 700;
  font-size: .95rem;
  text-align: center;
  line-height: 1.3;
}
.wiki-infobox-head .ibox-sub {
  font-size: .72rem;
  font-weight: 400;
  color: #94a3b8;
  display: block;
  margin-top: 2px;
}
.wiki-infobox table { width: 100%; border-collapse: collapse; }
.wiki-infobox td { padding: .32rem .65rem; vertical-align: top; font-size: .83rem; }
.wiki-infobox td:first-child {
  color: #475569;
  font-weight: 600;
  white-space: nowrap;
  width: 38%;
}
.wiki-infobox td:last-child { color: #0f172a; }
.wiki-infobox tr { border-top: 1px solid #e2e8f0; }
.wiki-infobox tr:first-child { border-top: none; }
.wiki-infobox a { color: #2563eb; text-decoration: none; }
.wiki-infobox a:hover { text-decoration: underline; }

/* ═══ Title ═══ */
.wiki-main { grid-column: 1; }
.wiki-title { font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 .2rem; line-height: 1.2; }
.wiki-sub   { color: #64748b; font-size: .9rem; margin: 0 0 1rem; }
.wiki-deceased-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 999px;
  padding: .15rem .6rem; font-size: .76rem; color: #64748b; margin-left: .5rem;
}

/* ═══ Section headings ═══ */
.wiki-h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: .3rem;
  margin: 1.75rem 0 .9rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.wiki-h2-icon { font-size: 1rem; }

/* ═══ Ancestor pedigree tree ═══ */
.anc-scroll { overflow-x: auto; padding-bottom: .5rem; }
.anc-root { display: inline-flex; align-items: center; }

/* Each unit = parents block + connector + self box */
.anc-unit {
  display: flex;
  align-items: center;
  gap: 0;
}
.anc-parents {
  display: flex;
  flex-direction: column;
}
.anc-branch {
  display: flex;
  align-items: center;
  position: relative;
}
/* Connector: vertical bracket ─┤ on the RIGHT side of parents */
.anc-parents {
  position: relative;
}
.anc-parents::after {
  content: '';
  position: absolute;
  right: 0;
  top: 25%;
  height: 50%;
  border-right: 2px solid #94a3b8;
}
.anc-father { padding-bottom: 4px; }
.anc-mother { padding-top: 4px; }
.anc-father::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  width: 14px;
  border-bottom: 2px solid #94a3b8;
}
.anc-mother::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  width: 14px;
  border-top: 2px solid #94a3b8;
}
.anc-connector {
  display: flex;
  align-items: center;
  padding: 0 2px;
}
.anc-vline {
  width: 18px;
  height: 2px;
  background: #94a3b8;
}
.anc-leaf { display: flex; align-items: center; }

/* Person box in ancestor tree */
.anc-box {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
  padding: .28rem .55rem;
  margin: 3px 6px;
  white-space: nowrap;
  font-size: .8rem;
  transition: box-shadow .15s;
  box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.anc-box:hover { box-shadow: 0 0 0 2px #6366f1; }
.anc-box.anc-deceased { background: #f8fafc; border-style: dashed; color: #94a3b8; }
.anc-self .anc-box {
  background: #eef2ff;
  border-color: #6366f1;
  border-width: 2px;
  font-weight: 700;
  font-size: .9rem;
}
.anc-name { color: #1d4ed8; text-decoration: none; font-weight: 600; }
.anc-name:hover { text-decoration: underline; }
.anc-year { font-size: .68rem; color: #94a3b8; background: #f1f5f9; border-radius: 999px; padding: 0 5px; }
.anc-gender { font-size: .72rem; color: #94a3b8; }
.anc-wiki { font-size: .75rem; text-decoration: none; margin-left: 1px; opacity: .6; }
.anc-wiki:hover { opacity: 1; }

/* ═══ Children grid ═══ */
.children-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
  gap: .75rem;
}
.child-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .7rem .85rem;
  background: #fff;
  font-size: .85rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
  transition: box-shadow .15s;
}
.child-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.child-card.child-deceased { background: #f8fafc; border-style: dashed; }
.child-name { font-weight: 600; color: #1d4ed8; text-decoration: none; font-size: .88rem; }
.child-name:hover { text-decoration: underline; }
.child-meta { color: #94a3b8; font-size: .74rem; margin-top: 3px; }
.child-spouse { font-size: .79rem; color: #0891b2; margin-top: 4px; }
.child-spouse a { color: inherit; text-decoration: none; font-weight: 500; }
.child-spouse a:hover { text-decoration: underline; }
.child-links { margin-top: 6px; display: flex; gap: .5rem; }
.child-links a { font-size: .72rem; color: #6366f1; text-decoration: none; }
.child-links a:hover { text-decoration: underline; }

/* ═══ Siblings ═══ */
.sibling-list {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
}
.sib-chip {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .3rem .65rem;
  font-size: .82rem;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
  transition: box-shadow .15s;
}
.sib-chip:hover { box-shadow: 0 0 0 2px #6366f1; }
.sib-chip.sib-deceased { background: #f8fafc; border-style: dashed; color: #94a3b8; }
.sib-chip a { color: #1d4ed8; text-decoration: none; font-weight: 500; }
.sib-chip a:hover { text-decoration: underline; }
.sib-year { font-size: .7rem; color: #94a3b8; }
.sib-heart { color: #f43f5e; font-size: .8rem; }

/* ═══ Embedded descendant tree ═══ */
.desc-tree-wrap { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: .75rem 1rem; }
.tree-node { background: #fff; }

/* ═══ Action bar ═══ */
.wiki-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  margin-top: 1.5rem;
}

@media print {
  .wiki-sidebar { position: static; }
  .wiki-wrap { grid-template-columns: 1fr; }
}
</style>

<div class="py-2">
<!-- breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="/index.php?route=<?= $esc($profileRoute) ?>">Family List</a></li>
    <li class="breadcrumb-item"><a href="<?= $pUrl($pid) ?>"><?= $esc($personName) ?></a></li>
    <li class="breadcrumb-item active">Wiki View</li>
  </ol>
</nav>

<div class="wiki-wrap">

<!-- ══ SIDEBAR INFOBOX ══ -->
<div class="wiki-sidebar">
  <div class="wiki-infobox">
    <div class="wiki-infobox-head">
      <?= $esc($personName) ?>
      <?php if ($genderIcon): ?>
        <span class="ibox-sub"><?= $genderIcon ?> <?= ucfirst($gender) ?></span>
      <?php endif; ?>
    </div>
    <table>
      <?php if ($born): ?>
      <tr><td>Born</td><td><?= $esc($born) ?><?= $ageStr ? ' <span style="color:#94a3b8;font-size:.78rem;">(' . $esc($ageStr) . ' yrs)</span>' : '' ?></td></tr>
      <?php endif; ?>
      <?php if (!$isAlive && $died): ?>
      <tr><td>Died</td><td><?= $esc($died) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['blood_group'])): ?>
      <tr><td>Blood</td><td><?= $esc($person['blood_group']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['occupation'])): ?>
      <tr><td>Occupation</td><td><?= $esc($person['occupation']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['native_location'])): ?>
      <tr><td>Native</td><td><?= $esc($person['native_location']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['current_location'])): ?>
      <tr><td>Resides</td><td><?= $esc($person['current_location']) ?></td></tr>
      <?php endif; ?>
      <?php
      $fatherId = (int)($person['father_id'] ?? 0);
      $motherId = (int)($person['mother_id'] ?? 0);
      $spouseId = (int)($person['spouse_id'] ?? 0);
      ?>
      <?php if (!empty($person['father_name'])): ?>
      <tr><td>Father</td><td><?= $fatherId > 0 ? '<a href="' . $pUrl($fatherId) . '">' . $esc($person['father_name']) . '</a>' : $esc($person['father_name']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['mother_name'])): ?>
      <tr><td>Mother</td><td><?= $motherId > 0 ? '<a href="' . $pUrl($motherId) . '">' . $esc($person['mother_name']) . '</a>' : $esc($person['mother_name']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['spouse_name'])): ?>
      <tr><td>Spouse</td><td><?= $spouseId > 0 ? '<a href="' . $pUrl($spouseId) . '">' . $esc($person['spouse_name']) . '</a>' : $esc($person['spouse_name']) ?></td></tr>
      <?php endif; ?>
      <?php if (count($children) > 0): ?>
      <tr><td>Children</td><td><?= count($children) ?></td></tr>
      <?php endif; ?>
      <?php if (count($siblings) > 0): ?>
      <tr><td>Siblings</td><td><?= count($siblings) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['mobile'])): ?>
      <tr><td>Mobile</td><td><?= $esc($person['mobile']) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($person['email'])): ?>
      <tr><td>Email</td><td><?= $esc($person['email']) ?></td></tr>
      <?php endif; ?>
    </table>
  </div>
</div><!-- /sidebar -->

<!-- ══ MAIN CONTENT ══ -->
<div class="wiki-main">

  <!-- Title -->
  <h1 class="wiki-title">
    <?= $esc($personName) ?>
    <?php if (!$isAlive): ?>
      <span class="wiki-deceased-badge">† Deceased</span>
    <?php endif; ?>
  </h1>
  <p class="wiki-sub">
    <?php if ($born): ?>Born <?= $esc($born) ?><?php if ($ageStr): ?> &middot; <?= $isAlive ? 'Age' : 'Lived' ?> <?= $esc($ageStr) ?><?php endif; ?><?php endif; ?>
    <?php if (!$isAlive && $died): ?><?php if ($born): ?> &middot; <?php endif; ?>Died <?= $esc($died) ?><?php endif; ?>
  </p>

  <!-- ── Ancestry ── -->
  <div class="wiki-h2"><span class="wiki-h2-icon">🌳</span> Ancestors</div>
  <?php if ($ancestorTree && ($ancestorTree['father'] || $ancestorTree['mother'])): ?>
  <div class="anc-scroll">
    <div class="anc-root">
      <?php
      // Build the display tree starting from subject's parents
      // We show person + their parents/ancestors
      $displayNode = $ancestorTree;
      $displayNode['full_name'] = $personName; // ensure subject name shown
      echo renderAncestorBranch($displayNode, $profileRoute, $wikiRoute);
      ?>
    </div>
  </div>
  <p class="text-muted small mt-1" style="font-size:.75rem;">← scroll left to see earlier generations &nbsp;|&nbsp; click 📖 to open a wiki view &nbsp;|&nbsp; click name for full profile</p>
  <?php else: ?>
  <p class="text-muted small">No ancestry information recorded.</p>
  <?php endif; ?>

  <!-- ── Children ── -->
  <?php if (count($children) > 0): ?>
  <div class="wiki-h2"><span class="wiki-h2-icon">👶</span> Children <span style="font-size:.8rem;font-weight:400;color:#94a3b8;">(<?= count($children) ?>)</span></div>
  <div class="children-grid">
    <?php foreach ($children as $ch): ?>
    <?php
    $cId    = (int)($ch['person_id'] ?? 0);
    $cAlive = (int)($ch['is_alive'] ?? 1);
    $cYear  = (string)($ch['birth_year'] ?? '');
    $cSp    = trim((string)($ch['spouse_name'] ?? ''));
    $cSpId  = (int)($ch['spouse_id'] ?? 0);
    $cG     = strtolower((string)($ch['gender'] ?? ''));
    $cGi    = match($cG) { 'male' => '♂', 'female' => '♀', default => '' };
    ?>
    <div class="child-card<?= $cAlive === 0 ? ' child-deceased' : '' ?>">
      <div>
        <?php if ($cGi): ?><span style="color:#94a3b8;font-size:.78rem;"><?= $cGi ?></span> <?php endif; ?>
        <?php if ($cId > 0): ?>
          <a href="<?= $pUrl($cId) ?>" class="child-name"><?= $esc((string)($ch['full_name'] ?? '')) ?></a>
        <?php else: ?>
          <span class="fw-semibold"><?= $esc((string)($ch['full_name'] ?? '')) ?></span>
        <?php endif; ?>
        <?php if ($cAlive === 0): ?><span style="color:#94a3b8;font-size:.72rem;"> †</span><?php endif; ?>
      </div>
      <?php if ($cYear): ?><div class="child-meta">Born <?= $esc($cYear) ?></div><?php endif; ?>
      <?php if ($cSp !== ''): ?>
      <div class="child-spouse">
        ♥
        <?php if ($cSpId > 0): ?>
          <a href="<?= $pUrl($cSpId) ?>"><?= $esc($cSp) ?></a>
        <?php else: ?>
          <?= $esc($cSp) ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($cId > 0): ?>
      <div class="child-links">
        <a href="<?= $pUrl($cId) ?>">Profile</a>
        <a href="<?= $wUrl($cId) ?>">📖 Wiki</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Descendants (AJAX tree) ── -->
  <div class="wiki-h2"><span class="wiki-h2-icon">🌿</span> Descendants</div>
  <?php if (count($children) > 0): ?>
  <div class="desc-tree-wrap">
    <div id="descTreeContainer"
         data-children-route="/index.php?route=<?= $esc($childrenAjaxRoute) ?>"
         data-profile-route="/index.php?route=<?= $esc($profileRoute) ?>"
         data-wiki-route="/index.php?route=<?= $esc($wikiRoute) ?>">
      <div class="text-muted small">Loading descendants…</div>
    </div>
  </div>
  <script>
  (function() {
    var container = document.getElementById('descTreeContainer');
    if (!container) return;

    // Reuse the same createNode from app.js by duplicating minimal tree logic here
    function debounce(fn, w) {
      var t = null;
      return function() { clearTimeout(t); t = setTimeout(fn, w); };
    }

    function mkNode(person, level) {
      var profileRoute = container.getAttribute('data-profile-route') || '';
      var wikiRoute    = container.getAttribute('data-wiki-route')    || '';
      var childRoute   = container.getAttribute('data-children-route') || '';

      var wrap = document.createElement('div');
      wrap.className = 'tree-node border rounded p-2 mb-2';
      wrap.style.marginLeft = (level * 16) + 'px';

      var hdr = document.createElement('div');
      hdr.className = 'd-flex align-items-start gap-2 flex-wrap';

      var tog = document.createElement('button');
      tog.type = 'button';
      tog.className = 'btn btn-sm btn-outline-secondary flex-shrink-0';
      tog.style.cssText = 'min-width:28px;height:28px;padding:0;font-size:.8rem;line-height:1;';
      tog.textContent = '+';

      var info = document.createElement('div');
      info.style.flex = '1';

      var nameRow = document.createElement('div');
      nameRow.className = 'd-flex align-items-center gap-2 flex-wrap';

      var nl = document.createElement('a');
      nl.href = profileRoute ? profileRoute + '&id=' + encodeURIComponent(person.id) : '#';
      nl.textContent = person.name;
      nl.className = 'text-decoration-none fw-semibold';
      nl.style.fontSize = '.9rem';
      nameRow.appendChild(nl);

      if (person.birth_year) {
        var yb = document.createElement('span');
        yb.textContent = 'b.' + person.birth_year;
        yb.style.cssText = 'font-size:.7rem;color:#94a3b8;background:#f1f5f9;border-radius:999px;padding:1px 7px;';
        nameRow.appendChild(yb);
      }
      info.appendChild(nameRow);

      if (person.spouse_name) {
        var sr = document.createElement('div');
        sr.style.marginTop = '2px';
        sr.innerHTML = '<span style="color:#f43f5e;font-size:.78rem;">♥</span> '
          + (person.spouse_id && profileRoute
            ? '<a href="' + profileRoute + '&id=' + encodeURIComponent(person.spouse_id) + '" style="font-size:.8rem;color:#0891b2;font-weight:500;text-decoration:none;">' + person.spouse_name + '</a>'
            : '<span style="font-size:.8rem;color:#0891b2;">' + person.spouse_name + '</span>');
        info.appendChild(sr);
      }

      if (wikiRoute && person.id) {
        var wr = document.createElement('div');
        wr.style.marginTop = '3px';
        var wl = document.createElement('a');
        wl.href = wikiRoute + '&id=' + encodeURIComponent(person.id);
        wl.textContent = '📖 Wiki';
        wl.style.cssText = 'font-size:.7rem;color:#6366f1;text-decoration:none;';
        wr.appendChild(wl);
        info.appendChild(wr);
      }

      hdr.appendChild(tog);
      hdr.appendChild(info);

      var kids = document.createElement('div');
      kids.className = 'mt-1';
      kids.hidden = true;

      tog.addEventListener('click', function() {
        if (kids.dataset.loading === '1') return;
        if (!kids.dataset.loaded) {
          kids.dataset.loading = '1';
          tog.textContent = '…';
          fetch(childRoute + '&person_id=' + encodeURIComponent(person.id), { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(list) {
              if (!Array.isArray(list)) list = [];
              kids.innerHTML = '';
              var seen = {};
              list.forEach(function(k) {
                if (seen[k.id]) return;
                seen[k.id] = true;
                kids.appendChild(mkNode(k, level + 1));
              });
              kids.dataset.loaded = '1';
              kids.dataset.loading = '0';
              kids.hidden = list.length === 0;
              tog.textContent = list.length === 0 ? '·' : '−';
              if (list.length === 0) tog.disabled = true;
            })
            .catch(function() {
              kids.dataset.loaded = '1';
              kids.dataset.loading = '0';
              kids.hidden = false;
              kids.innerHTML = '<div class="text-danger small">Failed to load.</div>';
              tog.textContent = '!';
            });
        } else {
          kids.hidden = !kids.hidden;
          tog.textContent = kids.hidden ? '+' : '−';
        }
      });

      wrap.appendChild(hdr);
      wrap.appendChild(kids);
      return wrap;
    }

    // Load root (person themselves) as the tree root
    var infoRoute = '/index.php?route=person/node-info&person_id=<?= $pid ?>';
    fetch(infoRoute, { headers: { 'Accept': 'application/json' } })
      .then(function(r) { return r.json(); })
      .then(function(info) {
        container.innerHTML = '';
        if (info && info.id) {
          container.appendChild(mkNode(info, 0));
        }
      })
      .catch(function() { container.innerHTML = '<div class="text-muted small">Could not load.</div>'; });
  })();
  </script>
  <?php else: ?>
  <p class="text-muted small">No children recorded — nothing to expand.</p>
  <?php endif; ?>

  <!-- ── Siblings ── -->
  <?php if (count($siblings) > 0): ?>
  <div class="wiki-h2"><span class="wiki-h2-icon">👥</span> Siblings <span style="font-size:.8rem;font-weight:400;color:#94a3b8;">(<?= count($siblings) ?>)</span></div>
  <div class="sibling-list">
    <?php foreach ($siblings as $sib): ?>
    <?php
    $sId   = (int)($sib['person_id'] ?? 0);
    $sAlive = (int)($sib['is_alive'] ?? 1);
    $sYear  = (string)($sib['birth_year'] ?? '');
    $sSp    = trim((string)($sib['spouse_name'] ?? ''));
    $sSpId  = (int)($sib['spouse_id'] ?? 0);
    $sG     = strtolower((string)($sib['gender'] ?? ''));
    $sGi    = match($sG) { 'male' => '♂', 'female' => '♀', default => '' };
    ?>
    <div class="sib-chip<?= $sAlive === 0 ? ' sib-deceased' : '' ?>">
      <?php if ($sGi): ?><span class="anc-gender"><?= $sGi ?></span><?php endif; ?>
      <?php if ($sId > 0): ?>
        <a href="<?= $pUrl($sId) ?>"><?= $esc((string)($sib['full_name'] ?? '')) ?></a>
      <?php else: ?>
        <span class="fw-500"><?= $esc((string)($sib['full_name'] ?? '')) ?></span>
      <?php endif; ?>
      <?php if ($sYear): ?><span class="sib-year"><?= $esc($sYear) ?></span><?php endif; ?>
      <?php if ($sSp !== ''): ?>
        <span class="sib-heart">♥</span>
        <?php if ($sSpId > 0): ?>
          <a href="<?= $pUrl($sSpId) ?>"><?= $esc($sSp) ?></a>
        <?php else: ?>
          <span><?= $esc($sSp) ?></span>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($sAlive === 0): ?><span style="color:#94a3b8;font-size:.75rem;"> †</span><?php endif; ?>
      <?php if ($sId > 0): ?>
        <a href="<?= $wUrl($sId) ?>" class="anc-wiki" title="Wiki view">📖</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Actions ── -->
  <div class="wiki-actions">
    <a href="<?= $pUrl($pid) ?>" class="btn btn-outline-secondary btn-sm">← Profile</a>
    <?php if ($fatherId > 0): ?>
    <a href="<?= $wUrl($fatherId) ?>" class="btn btn-outline-primary btn-sm">↑ Father's Wiki</a>
    <?php endif; ?>
    <?php if ($motherId > 0): ?>
    <a href="<?= $wUrl($motherId) ?>" class="btn btn-outline-primary btn-sm">↑ Mother's Wiki</a>
    <?php endif; ?>
    <?php if ($spouseId > 0): ?>
    <a href="<?= $wUrl($spouseId) ?>" class="btn btn-outline-info btn-sm" style="border-color:#0891b2;color:#0891b2;">♥ Spouse's Wiki</a>
    <?php endif; ?>
    <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm ms-auto">🖨 Print</a>
  </div>

</div><!-- /wiki-main -->
</div><!-- /wiki-wrap -->
</div><!-- /py-2 -->
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
