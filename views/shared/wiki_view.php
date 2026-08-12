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

$pUrl = fn(int $id): string => '/index.php?route=' . $profileRoute . '&id=' . $id;
$wUrl = fn(int $id): string => '/index.php?route=' . $wikiRoute   . '&id=' . $id;

// ── BFS: flatten recursive ancestor tree into generation levels ──
// Level 0 = subject, Level 1 = parents, Level 2 = grandparents, …
// Order within each level is preserved left-to-right (paternal first).
function buildAncestorLevels(array $root): array
{
    $levels = [];
    $queue  = [['node' => $root, 'depth' => 0]];
    while (!empty($queue)) {
        $item = array_shift($queue);
        $n    = $item['node'];
        $d    = $item['depth'];
        $levels[$d][] = $n;
        if (!empty($n['father'])) $queue[] = ['node' => $n['father'], 'depth' => $d + 1];
        if (!empty($n['mother'])) $queue[] = ['node' => $n['mother'], 'depth' => $d + 1];
    }
    return $levels;
}

function genLabel(int $depth): string
{
    return match($depth) {
        1 => 'Parents',
        2 => 'Grandparents',
        3 => 'Great-grandparents',
        4 => 'Great-great-grandparents',
        5 => '3rd great-grandparents',
        6 => '4th great-grandparents',
        default => 'Generation +' . $depth . ' ancestors',
    };
}

function renderAncBox(array $p, string $pRoute, string $wRoute, bool $isSelf = false): string
{
    $id    = (int)($p['person_id'] ?? 0);
    $name  = htmlspecialchars((string)($p['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $year  = htmlspecialchars((string)($p['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8');
    $alive = (int)($p['is_alive'] ?? 1);
    $g     = strtolower((string)($p['gender'] ?? ''));
    $gi    = match($g) { 'male' => '♂', 'female' => '♀', default => '' };

    $cls = 'anc-box'
        . ($alive === 0 ? ' anc-dec' : '')
        . ($isSelf ? ' anc-self-box' : '');

    $nameH = $id > 0
        ? '<a href="/index.php?route=' . htmlspecialchars($pRoute, ENT_QUOTES, 'UTF-8') . '&id=' . $id . '" class="anc-nm">' . $name . '</a>'
        : '<span class="anc-nm anc-nm-plain">' . $name . '</span>';
    $wikiH = ($id > 0 && !$isSelf)
        ? ' <a href="/index.php?route=' . htmlspecialchars($wRoute, ENT_QUOTES, 'UTF-8') . '&id=' . $id . '" class="anc-wl" title="Wiki view">📖</a>'
        : '';
    $yearH = $year ? '<span class="anc-yr">' . $year . '</span>' : '';
    $giH   = $gi   ? '<span class="anc-gi">' . $gi . '</span>' : '';

    return '<div class="' . $cls . '">' . $giH . $nameH . $yearH . $wikiH . '</div>';
}
?>
<style>
/* ═══ Page grid ═══ */
.wiki-wrap {
  display: grid;
  grid-template-columns: 1fr 258px;
  gap: 0 1.5rem;
  max-width: 1060px;
  margin: 0 auto;
}
@media (max-width: 780px) {
  .wiki-wrap { grid-template-columns: 1fr; }
  .wiki-sidebar { order: -1; }
}

/* ═══ Sticky infobox ═══ */
.wiki-sidebar { grid-column: 2; grid-row: 1 / 8; align-self: start; }
@media (max-width: 780px) { .wiki-sidebar { grid-column: 1; grid-row: auto; } }

.wiki-infobox {
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
  font-size: .84rem;
  background: #f8fafc;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
  position: sticky;
  top: 70px;
}
.wiki-infobox-head {
  background: #1e293b;
  color: #fff;
  padding: .65rem .85rem;
  font-weight: 700;
  font-size: .95rem;
  text-align: center;
  line-height: 1.35;
}
.wiki-infobox-head small {
  display: block;
  font-size: .7rem;
  font-weight: 400;
  color: #94a3b8;
  margin-top: 2px;
}
.wiki-infobox table { width: 100%; border-collapse: collapse; }
.wiki-infobox td {
  padding: .3rem .65rem;
  vertical-align: top;
  font-size: .82rem;
}
.wiki-infobox td:first-child {
  color: #475569;
  font-weight: 600;
  white-space: nowrap;
  width: 36%;
}
.wiki-infobox td:last-child { color: #0f172a; }
.wiki-infobox tr { border-top: 1px solid #e2e8f0; }
.wiki-infobox tr:first-child { border-top: none; }
.wiki-infobox a { color: #2563eb; text-decoration: none; }
.wiki-infobox a:hover { text-decoration: underline; }

/* ═══ Main content ═══ */
.wiki-main { grid-column: 1; min-width: 0; }
.wiki-title {
  font-size: 1.7rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 .2rem;
  line-height: 1.2;
}
.wiki-sub { color: #64748b; font-size: .88rem; margin: 0 0 .75rem; }
.wiki-dec-badge {
  display: inline-flex; align-items: center; gap: .3rem;
  background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 999px;
  padding: .12rem .55rem; font-size: .73rem; color: #64748b; margin-left: .4rem;
}

/* ═══ Section headings ═══ */
.wiki-h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: .3rem;
  margin: 1.6rem 0 .9rem;
  display: flex;
  align-items: center;
  gap: .4rem;
}

/* ═══ Ancestor tree — TOP TO BOTTOM ═══ */
.anc-tree {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
  padding: .25rem 0 .5rem;
}

/* One generation row */
.anc-gen {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .35rem;
}
.anc-gen-label {
  font-size: .68rem;
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 700;
  color: #94a3b8;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: .1rem .65rem;
}
.anc-boxes {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
  justify-content: center;
  align-items: center;
}

/* Person box in the ancestor tree */
.anc-box {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  background: #fff;
  padding: .3rem .6rem;
  font-size: .8rem;
  white-space: nowrap;
  box-shadow: 0 1px 2px rgba(0,0,0,.05);
  transition: box-shadow .15s;
}
.anc-box:hover { box-shadow: 0 0 0 2px #6366f1; }
.anc-box.anc-dec { background: #f8fafc; border-style: dashed; }
.anc-box.anc-self-box {
  background: #eef2ff;
  border: 2px solid #6366f1;
  font-size: .92rem;
  padding: .38rem .75rem;
  box-shadow: 0 2px 8px rgba(99,102,241,.15);
}
.anc-nm { color: #1d4ed8; text-decoration: none; font-weight: 600; }
.anc-nm:hover { text-decoration: underline; }
.anc-nm-plain { color: #1e293b; font-weight: 600; }
.anc-yr { font-size: .67rem; color: #94a3b8; background: #f1f5f9; border-radius: 999px; padding: 0 5px; }
.anc-gi { font-size: .7rem; color: #94a3b8; }
.anc-wl { font-size: .72rem; text-decoration: none; opacity: .55; }
.anc-wl:hover { opacity: 1; }

/* Vertical arrow between generations */
.anc-arrow {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: .15rem 0;
  gap: 1px;
}
.anc-arrow-line { width: 2px; height: 20px; background: #cbd5e1; border-radius: 1px; }
.anc-arrow-head {
  width: 0;
  height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 6px solid #cbd5e1;
}

/* Heart between subject and spouse */
.anc-heart { color: #f43f5e; font-size: 1rem; display: flex; align-items: center; padding: 0 .3rem; }

/* ═══ Children grid ═══ */
.children-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
  gap: .7rem;
}
.child-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .65rem .8rem;
  background: #fff;
  font-size: .84rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
  transition: box-shadow .15s;
}
.child-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.09); }
.child-card.ch-dec { background: #f8fafc; border-style: dashed; }
.ch-name { font-weight: 600; color: #1d4ed8; text-decoration: none; font-size: .87rem; }
.ch-name:hover { text-decoration: underline; }
.ch-meta { color: #94a3b8; font-size: .73rem; margin-top: 3px; }
.ch-spouse { font-size: .78rem; color: #0891b2; margin-top: 4px; }
.ch-spouse a { color: inherit; text-decoration: none; font-weight: 500; }
.ch-spouse a:hover { text-decoration: underline; }
.ch-links { margin-top: 6px; display: flex; gap: .5rem; }
.ch-links a { font-size: .71rem; color: #6366f1; text-decoration: none; }
.ch-links a:hover { text-decoration: underline; }

/* ═══ Siblings ═══ */
.sibling-list { display: flex; flex-wrap: wrap; gap: .45rem; }
.sib-chip {
  display: inline-flex;
  align-items: center;
  gap: .28rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .28rem .62rem;
  font-size: .81rem;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
  transition: box-shadow .15s;
}
.sib-chip:hover { box-shadow: 0 0 0 2px #6366f1; }
.sib-chip.sib-dec { background: #f8fafc; border-style: dashed; color: #94a3b8; }
.sib-chip a { color: #1d4ed8; text-decoration: none; font-weight: 500; }
.sib-chip a:hover { text-decoration: underline; }
.sib-yr { font-size: .69rem; color: #94a3b8; }
.sib-hrt { color: #f43f5e; font-size: .78rem; }

/* ═══ Descendants embedded tree ═══ */
.desc-wrap {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .75rem 1rem;
}
.tree-node { background: #fff; }

/* ═══ Action bar ═══ */
.wiki-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
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
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="/index.php?route=<?= $esc($profileRoute) ?>">Family List</a></li>
    <li class="breadcrumb-item"><a href="<?= $pUrl($pid) ?>"><?= $esc($personName) ?></a></li>
    <li class="breadcrumb-item active">Wiki View</li>
  </ol>
</nav>

<div class="wiki-wrap">

<!-- ══ SIDEBAR ══ -->
<div class="wiki-sidebar">
  <div class="wiki-infobox">
    <div class="wiki-infobox-head">
      <?= $esc($personName) ?>
      <?php if ($genderIcon): ?><small><?= $genderIcon ?> <?= ucfirst($gender) ?></small><?php endif; ?>
    </div>
    <?php if (!empty($profile_photo_id)): ?>
    <div class="text-center py-2" style="border-bottom:1px solid #e2e8f0;">
      <img src="/index.php?route=person/attachment&id=<?= (int)$profile_photo_id ?>"
           style="width:90px;height:90px;object-fit:cover;border-radius:50%;border:3px solid #6366f1;"
           alt="<?= $esc($personName) ?>">
    </div>
    <?php endif; ?>
    <table>
      <?php if ($born): ?>
      <tr><td>Born</td><td><?= $esc($born) ?><?= $ageStr ? ' <span style="color:#94a3b8;font-size:.76rem;">(' . $esc($ageStr) . ' yrs)</span>' : '' ?></td></tr>
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
</div>

<!-- ══ MAIN CONTENT ══ -->
<div class="wiki-main">

  <h1 class="wiki-title">
    <?= $esc($personName) ?>
    <?php if (!$isAlive): ?><span class="wiki-dec-badge">† Deceased</span><?php endif; ?>
  </h1>
  <p class="wiki-sub">
    <?php if ($born): ?>Born <?= $esc($born) ?><?php if ($ageStr): ?> &middot; <?= $isAlive ? 'Age' : 'Lived' ?> <?= $esc($ageStr) ?><?php endif; ?><?php endif; ?>
    <?php if (!$isAlive && $died): ?><?= $born ? ' &middot; ' : '' ?>Died <?= $esc($died) ?><?php endif; ?>
  </p>

  <!-- ── Ancestors (top-to-bottom) ── -->
  <div class="wiki-h2">🌳 Ancestors</div>
  <?php
  $hasAncestors = $ancestorTree && (!empty($ancestorTree['father']) || !empty($ancestorTree['mother']));
  ?>
  <?php if ($hasAncestors): ?>
  <?php
  $levels   = buildAncestorLevels($ancestorTree);
  $maxDepth = max(array_keys($levels));
  ?>
  <div class="anc-tree">
    <?php for ($d = $maxDepth; $d >= 1; $d--): ?>
      <?php if (empty($levels[$d])) continue; ?>
      <!-- Generation row -->
      <div class="anc-gen">
        <div class="anc-gen-label"><?= genLabel($d) ?></div>
        <div class="anc-boxes">
          <?php foreach ($levels[$d] as $p): ?>
            <?= renderAncBox($p, $profileRoute, $wikiRoute) ?>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Downward arrow -->
      <div class="anc-arrow">
        <div class="anc-arrow-line"></div>
        <div class="anc-arrow-head"></div>
      </div>
    <?php endfor; ?>

    <!-- Subject row (always at bottom) -->
    <div class="anc-gen">
      <div class="anc-gen-label" style="background:#eef2ff;border-color:#a5b4fc;color:#4338ca;">
        <?= $esc($personName) ?>
      </div>
      <div class="anc-boxes">
        <?= renderAncBox($ancestorTree, $profileRoute, $wikiRoute, true) ?>
        <?php if (!empty($person['spouse_name'])): ?>
          <div class="anc-heart">♥</div>
          <?php
          $spArr = $spouseId > 0
              ? ['person_id' => $spouseId, 'full_name' => $person['spouse_name'], 'birth_year' => '', 'is_alive' => 1, 'gender' => '']
              : null;
          if ($spArr) echo renderAncBox($spArr, $profileRoute, $wikiRoute);
          ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <p class="text-muted mt-1" style="font-size:.73rem;">Click any name → profile &nbsp;|&nbsp; 📖 → wiki view for that person</p>
  <?php else: ?>
  <p class="text-muted small">No ancestry information recorded.</p>
  <?php endif; ?>

  <!-- ── Children ── -->
  <?php if (count($children) > 0): ?>
  <div class="wiki-h2">👶 Children <span style="font-size:.8rem;font-weight:400;color:#94a3b8;">(<?= count($children) ?>)</span></div>
  <div class="children-grid">
    <?php foreach ($children as $ch): ?>
    <?php
    $cId   = (int)($ch['person_id'] ?? 0);
    $cAlive = (int)($ch['is_alive'] ?? 1);
    $cYear  = (string)($ch['birth_year'] ?? '');
    $cSp    = trim((string)($ch['spouse_name'] ?? ''));
    $cSpId  = (int)($ch['spouse_id'] ?? 0);
    $cG     = strtolower((string)($ch['gender'] ?? ''));
    $cGi    = match($cG) { 'male' => '♂', 'female' => '♀', default => '' };
    ?>
    <div class="child-card<?= $cAlive === 0 ? ' ch-dec' : '' ?>">
      <div>
        <?php if ($cGi): ?><span style="color:#94a3b8;font-size:.75rem;"><?= $cGi ?></span> <?php endif; ?>
        <?php if ($cId > 0): ?>
          <a href="<?= $pUrl($cId) ?>" class="ch-name"><?= $esc((string)($ch['full_name'] ?? '')) ?></a>
        <?php else: ?>
          <span class="fw-semibold"><?= $esc((string)($ch['full_name'] ?? '')) ?></span>
        <?php endif; ?>
        <?php if ($cAlive === 0): ?><span style="color:#94a3b8;font-size:.7rem;"> †</span><?php endif; ?>
      </div>
      <?php if ($cYear): ?><div class="ch-meta">Born <?= $esc($cYear) ?></div><?php endif; ?>
      <?php if ($cSp !== ''): ?>
      <div class="ch-spouse">♥
        <?php if ($cSpId > 0): ?><a href="<?= $pUrl($cSpId) ?>"><?= $esc($cSp) ?></a>
        <?php else: ?><?= $esc($cSp) ?><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($cId > 0): ?>
      <div class="ch-links">
        <a href="<?= $pUrl($cId) ?>">Profile</a>
        <a href="<?= $wUrl($cId) ?>">📖 Wiki</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Descendants (AJAX tree) ── -->
  <div class="wiki-h2">🌿 Descendants</div>
  <?php if (count($children) > 0): ?>
  <div class="desc-wrap">
    <div id="descTreeContainer"
         data-children-route="/index.php?route=<?= $esc($childrenAjaxRoute) ?>"
         data-profile-route="/index.php?route=<?= $esc($profileRoute) ?>"
         data-wiki-route="/index.php?route=<?= $esc($wikiRoute) ?>">
      <div class="text-muted small">Loading…</div>
    </div>
  </div>
  <script>
  (function(){
    var c = document.getElementById('descTreeContainer');
    if (!c) return;
    var pr = c.getAttribute('data-profile-route') || '';
    var wr = c.getAttribute('data-wiki-route')    || '';
    var cr = c.getAttribute('data-children-route') || '';

    function mkNode(p, lv) {
      var wrap = document.createElement('div');
      wrap.className = 'tree-node border rounded p-2 mb-2';
      wrap.style.marginLeft = (lv * 16) + 'px';

      var hdr = document.createElement('div');
      hdr.className = 'd-flex align-items-start gap-2 flex-wrap';

      var tog = document.createElement('button');
      tog.type = 'button';
      tog.className = 'btn btn-sm btn-outline-secondary flex-shrink-0';
      tog.style.cssText = 'min-width:28px;height:28px;padding:0;font-size:.8rem;line-height:1;';
      tog.textContent = '+';

      var info = document.createElement('div');
      info.style.flex = '1';

      var nr = document.createElement('div');
      nr.className = 'd-flex align-items-center gap-2 flex-wrap';
      var nl = document.createElement('a');
      nl.href = pr ? pr + '&id=' + encodeURIComponent(p.id) : '#';
      nl.textContent = p.name;
      nl.className = 'text-decoration-none fw-semibold';
      nl.style.fontSize = '.9rem';
      nr.appendChild(nl);
      if (p.birth_year) {
        var yb = document.createElement('span');
        yb.textContent = 'b.' + p.birth_year;
        yb.style.cssText = 'font-size:.7rem;color:#94a3b8;background:#f1f5f9;border-radius:999px;padding:1px 7px;';
        nr.appendChild(yb);
      }
      info.appendChild(nr);

      if (p.spouse_name) {
        var sr = document.createElement('div');
        sr.style.marginTop = '2px';
        sr.innerHTML = '<span style="color:#f43f5e;font-size:.78rem;">♥</span> '
          + (p.spouse_id && pr
            ? '<a href="' + pr + '&id=' + encodeURIComponent(p.spouse_id) + '" style="font-size:.8rem;color:#0891b2;font-weight:500;text-decoration:none;">' + p.spouse_name + '</a>'
            : '<span style="font-size:.8rem;color:#0891b2;">' + p.spouse_name + '</span>');
        info.appendChild(sr);
      }
      if (wr && p.id) {
        var wrow = document.createElement('div');
        wrow.style.marginTop = '3px';
        var wl = document.createElement('a');
        wl.href = wr + '&id=' + encodeURIComponent(p.id);
        wl.textContent = '📖 Wiki';
        wl.style.cssText = 'font-size:.7rem;color:#6366f1;text-decoration:none;';
        wrow.appendChild(wl);
        info.appendChild(wrow);
      }

      hdr.appendChild(tog);
      hdr.appendChild(info);

      var kids = document.createElement('div');
      kids.className = 'mt-1';
      kids.hidden = true;

      tog.addEventListener('click', function() {
        if (kids.dataset.loading === '1') return;
        if (!kids.dataset.loaded) {
          kids.dataset.loading = '1'; tog.textContent = '…';
          fetch(cr + '&person_id=' + encodeURIComponent(p.id), { headers: {'Accept':'application/json'} })
            .then(function(r){return r.json();})
            .then(function(list){
              if (!Array.isArray(list)) list = [];
              kids.innerHTML = '';
              var seen = {};
              list.forEach(function(k){ if(seen[k.id]) return; seen[k.id]=true; kids.appendChild(mkNode(k, lv+1)); });
              kids.dataset.loaded = '1'; kids.dataset.loading = '0';
              kids.hidden = list.length === 0;
              tog.textContent = list.length === 0 ? '·' : '−';
              if (list.length === 0) tog.disabled = true;
            })
            .catch(function(){ kids.dataset.loaded='1'; kids.dataset.loading='0'; kids.hidden=false;
              kids.innerHTML='<div class="text-danger small">Failed to load.</div>'; tog.textContent='!'; });
        } else { kids.hidden = !kids.hidden; tog.textContent = kids.hidden ? '+' : '−'; }
      });

      wrap.appendChild(hdr); wrap.appendChild(kids);
      return wrap;
    }

    fetch('/index.php?route=person/node-info&person_id=<?= $pid ?>', {headers:{'Accept':'application/json'}})
      .then(function(r){return r.json();})
      .then(function(info){ c.innerHTML=''; if(info && info.id) c.appendChild(mkNode(info,0)); })
      .catch(function(){ c.innerHTML='<div class="text-muted small">Could not load.</div>'; });
  })();
  </script>
  <?php else: ?>
  <p class="text-muted small">No children recorded.</p>
  <?php endif; ?>

  <!-- ── Siblings ── -->
  <?php if (count($siblings) > 0): ?>
  <div class="wiki-h2">👥 Siblings <span style="font-size:.8rem;font-weight:400;color:#94a3b8;">(<?= count($siblings) ?>)</span></div>
  <div class="sibling-list">
    <?php foreach ($siblings as $sib): ?>
    <?php
    $sId    = (int)($sib['person_id'] ?? 0);
    $sAlive = (int)($sib['is_alive'] ?? 1);
    $sYear  = (string)($sib['birth_year'] ?? '');
    $sSp    = trim((string)($sib['spouse_name'] ?? ''));
    $sSpId  = (int)($sib['spouse_id'] ?? 0);
    $sG     = strtolower((string)($sib['gender'] ?? ''));
    $sGi    = match($sG) { 'male' => '♂', 'female' => '♀', default => '' };
    ?>
    <div class="sib-chip<?= $sAlive === 0 ? ' sib-dec' : '' ?>">
      <?php if ($sGi): ?><span class="anc-gi"><?= $sGi ?></span><?php endif; ?>
      <?php if ($sId > 0): ?><a href="<?= $pUrl($sId) ?>"><?= $esc((string)($sib['full_name'] ?? '')) ?></a>
      <?php else: ?><span><?= $esc((string)($sib['full_name'] ?? '')) ?></span><?php endif; ?>
      <?php if ($sYear): ?><span class="sib-yr"><?= $esc($sYear) ?></span><?php endif; ?>
      <?php if ($sSp !== ''): ?><span class="sib-hrt">♥</span>
        <?php if ($sSpId > 0): ?><a href="<?= $pUrl($sSpId) ?>"><?= $esc($sSp) ?></a>
        <?php else: ?><span><?= $esc($sSp) ?></span><?php endif; ?>
      <?php endif; ?>
      <?php if ($sAlive === 0): ?><span style="color:#94a3b8;font-size:.73rem;"> †</span><?php endif; ?>
      <?php if ($sId > 0): ?><a href="<?= $wUrl($sId) ?>" class="anc-wl" title="Wiki">📖</a><?php endif; ?>
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
    <a href="<?= $wUrl($spouseId) ?>" class="btn btn-sm" style="border:1px solid #0891b2;color:#0891b2;">♥ Spouse's Wiki</a>
    <?php endif; ?>
    <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm ms-auto">🖨 Print</a>
  </div>

</div><!-- /wiki-main -->
</div><!-- /wiki-wrap -->
</div>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>
