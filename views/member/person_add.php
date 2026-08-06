<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<style>
/* ─── Wizard chrome ─────────────────────────────────────────── */
.wiz-steps { display:flex; align-items:center; gap:0; margin-bottom:2rem; }
.wiz-step  { display:flex; align-items:center; gap:.55rem; }
.wiz-num   { width:30px; height:30px; border-radius:50%; background:var(--ft-border); color:var(--ft-muted); font-size:.8rem; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all .2s; }
.wiz-num.active { background:var(--ft-primary); color:#fff; }
.wiz-num.done   { background:#d1fae5; color:#059669; }
.wiz-lbl  { font-size:.82rem; font-weight:600; color:var(--ft-muted); }
.wiz-lbl.active { color:var(--ft-primary); }
.wiz-line { flex:1; height:2px; background:var(--ft-border); margin:0 .5rem; }

/* ─── Step panels ───────────────────────────────────────────── */
.step-panel { display:none; }
.step-panel.active { display:block; }

/* ─── Gender picker ─────────────────────────────────────────── */
.g-opts { display:flex; gap:.5rem; }
.g-opt  { flex:1; padding:.6rem .5rem; border:2px solid var(--ft-border); border-radius:10px; background:#fff;
          font-size:.83rem; font-weight:600; color:var(--ft-muted); cursor:pointer; text-align:center; transition:all .15s; }
.g-opt:hover { border-color:#a5b4fc; color:var(--ft-primary); }
.g-opt.sel  { border-color:var(--ft-primary); background:#eef2ff; color:var(--ft-primary); }

/* ─── Parent card ───────────────────────────────────────────── */
.parent-box { border:2px solid var(--ft-border); border-radius:14px; padding:1.25rem; transition:border-color .2s; }
.parent-box.linked { border-color:var(--ft-primary); background:#f5f3ff; }
.pill-link  { display:inline-flex; align-items:center; gap:.5rem; background:#ede9fe; border-radius:999px; padding:.3rem .85rem .3rem .4rem; }
.pill-av    { width:26px; height:26px; border-radius:50%; background:var(--ft-primary); color:#fff; font-size:.72rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
.pill-nm    { font-size:.85rem; font-weight:600; color:#3730a3; }
.pill-rm    { background:none; border:none; color:var(--ft-primary); font-size:1rem; cursor:pointer; line-height:1; padding:0; }
.or-div     { display:flex; align-items:center; gap:.6rem; color:var(--ft-muted); font-size:.75rem; font-weight:600; margin:.75rem 0; }
.or-div::before, .or-div::after { content:''; flex:1; height:1px; background:var(--ft-border); }

/* ─── Search dropdown ───────────────────────────────────────── */
.sw { position:relative; }
.sd { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid var(--ft-border);
      border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.1); z-index:60; display:none; max-height:200px; overflow-y:auto; }
.sd .si { padding:.6rem 1rem; cursor:pointer; font-size:.85rem; border-bottom:1px solid #f8fafc; transition:background .1s; }
.sd .si:last-child { border-bottom:none; }
.sd .si:hover { background:#f5f3ff; }
.sd .si-n { font-weight:600; }
.sd .si-m { font-size:.75rem; color:var(--ft-muted); }

/* ─── Siblings grid ─────────────────────────────────────────── */
.sib-row { display:grid; grid-template-columns:28px 1fr 110px; gap:.5rem; align-items:center; margin-bottom:.5rem; }
.sib-pos  { width:28px; height:28px; border-radius:50%; background:var(--ft-border); color:var(--ft-muted); font-size:.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
.sib-pos.me { background:var(--ft-primary); color:#fff; }

/* ─── Review card ───────────────────────────────────────────── */
.review-row { display:flex; padding:.55rem 0; border-bottom:1px solid var(--ft-border); gap:1rem; }
.review-row:last-child { border-bottom:none; }
.review-lbl { font-size:.78rem; font-weight:600; color:var(--ft-muted); min-width:120px; }
.review-val { font-size:.88rem; font-weight:500; }

/* ─── Footer ────────────────────────────────────────────────── */
.wiz-footer { display:flex; gap:.75rem; justify-content:flex-end; padding-top:1.25rem; border-top:1px solid var(--ft-border); margin-top:1.5rem; }
</style>

<div class="page-header mb-3">
  <h1>Add a Family Member</h1>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="alert alert-info" style="font-size:.875rem;">
  &#128276; Your submission will be <strong>reviewed by an admin</strong> before appearing in the family tree.
</div>

<div class="card">
  <div class="card-body">

    <!-- Step indicator -->
    <div class="wiz-steps">
      <div class="wiz-step">
        <div class="wiz-num active" id="wn1">1</div>
        <div class="wiz-lbl active" id="wl1">Details</div>
      </div>
      <div class="wiz-line"></div>
      <div class="wiz-step">
        <div class="wiz-num" id="wn2">2</div>
        <div class="wiz-lbl" id="wl2">Parents</div>
      </div>
      <div class="wiz-line"></div>
      <div class="wiz-step">
        <div class="wiz-num" id="wn3">3</div>
        <div class="wiz-lbl" id="wl3">Review</div>
      </div>
    </div>

    <form method="post" action="/index.php?route=member/add-person" id="addForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="gender" id="hidGender" value="">
      <input type="hidden" name="father_person_id" id="hidFatherId" value="">
      <input type="hidden" name="mother_person_id" id="hidMotherId" value="">
      <input type="hidden" name="total_children" id="hidTotalChildren" value="1">
      <input type="hidden" name="person_position" id="hidPersonPosition" value="1">

      <!-- ═══════════ STEP 1 ═══════════ -->
      <div class="step-panel active" id="sp1">
        <div class="mb-3">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input class="form-control form-control-lg" name="full_name" id="inpFullName"
                 placeholder="Enter their full name" required autocomplete="off">
          <div id="dupWarn" class="mt-2" style="display:none;">
            <div class="alert alert-warning py-2 mb-0" style="font-size:.83rem;">
              <strong>Possible duplicates:</strong>
              <ul id="dupList" class="mb-0 mt-1 ps-3"></ul>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Gender <span class="text-danger">*</span></label>
          <div class="g-opts" id="gOpts">
            <button type="button" class="g-opt" data-v="male">&#9794; Male</button>
            <button type="button" class="g-opt" data-v="female">&#9792; Female</button>
            <button type="button" class="g-opt" data-v="other">&#11096; Other</button>
            <button type="button" class="g-opt" data-v="unknown">? Unknown</button>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-sm-4">
            <label class="form-label">Birth Year</label>
            <input class="form-control" name="birth_year" id="inpBirthYear" type="number" min="1800" max="2025" placeholder="e.g. 1965">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Full Date of Birth <span class="text-muted small">(optional)</span></label>
            <input class="form-control" name="date_of_birth" type="date">
          </div>
          <div class="col-sm-4 d-flex align-items-end pb-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_alive" id="chkAlive" checked value="1">
              <label class="form-check-label fw-600" for="chkAlive">Currently alive</label>
            </div>
          </div>
        </div>

        <div class="mb-3" id="dodWrap" style="display:none;">
          <label class="form-label">Date of Death</label>
          <input class="form-control" name="date_of_death" type="date" style="max-width:200px;">
        </div>

        <div class="mb-1">
          <label class="form-label">Note for admin <span class="text-muted small">(optional — helps with review)</span></label>
          <textarea class="form-control" name="admin_note" rows="2"
                    placeholder="e.g. This is my father's younger brother, born in Madurai"></textarea>
        </div>

        <div class="wiz-footer">
          <button type="button" class="btn btn-primary btn-pill" onclick="goStep(2)">Next: Parents &rarr;</button>
        </div>
      </div>

      <!-- ═══════════ STEP 2 ═══════════ -->
      <div class="step-panel" id="sp2">

        <!-- Father -->
        <p class="fw-bold mb-2">Father</p>
        <div class="parent-box mb-3" id="fBox">
          <div id="fLinked" style="display:none;">
            <div class="pill-link">
              <div class="pill-av" id="fAv"></div>
              <span class="pill-nm" id="fNm"></span>
              <button type="button" class="pill-rm" onclick="clearParent('f')">&#10005;</button>
            </div>
          </div>
          <div id="fForm">
            <div class="mb-2">
              <label class="form-label mb-1">Search existing member</label>
              <div class="sw">
                <input class="form-control" id="fSrch" placeholder="Type to search…" autocomplete="off">
                <div class="sd" id="fDrop"></div>
              </div>
            </div>
            <div class="or-div">or add new</div>
            <div class="row g-2">
              <div class="col-8">
                <input class="form-control" name="father_name" id="fName" placeholder="Father's full name">
              </div>
              <div class="col-4">
                <input class="form-control" name="father_birth_year" type="number" min="1900" max="2000" placeholder="Birth year">
              </div>
            </div>
          </div>
        </div>

        <!-- Sibling section (shown when father is set) -->
        <div id="sibSection" style="display:none;" class="mb-3">
          <div class="card" style="background:var(--ft-bg);border-color:var(--ft-border);">
            <div class="card-body py-3">
              <p class="fw-bold mb-3" style="font-size:.9rem;">&#128101; Children of <span id="sibFatherName">this father</span></p>
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label">Total children (that you know of)</label>
                  <input class="form-control" type="number" id="inpTotalChildren" min="1" max="20" value="1">
                </div>
                <div class="col-6">
                  <label class="form-label">This person's position</label>
                  <select class="form-select" id="inpPosition">
                    <option value="1">1st (eldest)</option>
                  </select>
                </div>
              </div>
              <div id="sibRows"></div>
              <div id="sibNamesWrap" style="display:none;">
                <p class="text-muted mb-2" style="font-size:.8rem;">Optional: name the other children</p>
                <div id="sibNameRows"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mother -->
        <p class="fw-bold mb-2">Mother</p>
        <div class="parent-box" id="mBox">
          <div id="mLinked" style="display:none;">
            <div class="pill-link">
              <div class="pill-av" id="mAv"></div>
              <span class="pill-nm" id="mNm"></span>
              <button type="button" class="pill-rm" onclick="clearParent('m')">&#10005;</button>
            </div>
          </div>
          <div id="mForm">
            <div class="mb-2">
              <label class="form-label mb-1">Search existing member</label>
              <div class="sw">
                <input class="form-control" id="mSrch" placeholder="Type to search…" autocomplete="off">
                <div class="sd" id="mDrop"></div>
              </div>
            </div>
            <div class="or-div">or add new</div>
            <div class="row g-2">
              <div class="col-8">
                <input class="form-control" name="mother_name" id="mName" placeholder="Mother's full name">
              </div>
              <div class="col-4">
                <input class="form-control" name="mother_birth_year" type="number" min="1900" max="2000" placeholder="Birth year">
              </div>
            </div>
          </div>
        </div>

        <div class="wiz-footer">
          <button type="button" class="btn btn-outline-secondary btn-pill" onclick="goStep(1)">&larr; Back</button>
          <button type="button" class="btn btn-primary btn-pill" onclick="goStep(3)">Next: Review &rarr;</button>
        </div>
      </div>

      <!-- ═══════════ STEP 3 ═══════════ -->
      <div class="step-panel" id="sp3">
        <p class="fw-bold mb-3">Review before submitting</p>

        <div class="mb-3">
          <div class="review-row">
            <div class="review-lbl">Full Name</div>
            <div class="review-val" id="rv-name">—</div>
          </div>
          <div class="review-row">
            <div class="review-lbl">Gender</div>
            <div class="review-val" id="rv-gender">—</div>
          </div>
          <div class="review-row">
            <div class="review-lbl">Birth Year</div>
            <div class="review-val" id="rv-year">—</div>
          </div>
          <div class="review-row">
            <div class="review-lbl">Status</div>
            <div class="review-val" id="rv-alive">—</div>
          </div>
          <div class="review-row">
            <div class="review-lbl">Father</div>
            <div class="review-val" id="rv-father">Not specified</div>
          </div>
          <div class="review-row">
            <div class="review-lbl">Mother</div>
            <div class="review-val" id="rv-mother">Not specified</div>
          </div>
          <div class="review-row" id="rv-sib-row" style="display:none;">
            <div class="review-lbl">Birth order</div>
            <div class="review-val" id="rv-sib">—</div>
          </div>
          <div class="review-row" id="rv-note-row" style="display:none;">
            <div class="review-lbl">Note</div>
            <div class="review-val" id="rv-note" style="font-style:italic;color:var(--ft-muted);"></div>
          </div>
        </div>

        <div class="alert alert-info" style="font-size:.83rem;">
          &#10003; An admin will review this and add it to the family tree. You'll get a notification when it's approved or if they have questions.
        </div>

        <div class="wiz-footer">
          <button type="button" class="btn btn-outline-secondary btn-pill" onclick="goStep(2)">&larr; Back</button>
          <button type="submit" class="btn btn-primary btn-pill">Submit for Review &#10003;</button>
        </div>
      </div>

      <!-- hidden fields for siblings JSON -->
      <input type="hidden" name="siblings_json" id="hidSiblingsJson" value="[]">

    </form>
  </div>
</div>

<script>
var curStep = 1;
var fLinkedId = '', fLinkedName = '';
var mLinkedId = '', mLinkedName = '';
var siblings = []; // {pos, name, gender, isMe}

// ── Step nav ──────────────────────────────────────────────────
function goStep(n) {
  if (n === 2 && !validateStep1()) return;
  if (n === 3) buildReview();
  document.getElementById('sp' + curStep).classList.remove('active');
  curStep = n;
  document.getElementById('sp' + curStep).classList.add('active');
  updateStepUI();
  syncSiblingsJson();
  window.scrollTo({top: 0, behavior: 'smooth'});
}

function validateStep1() {
  var nm = document.getElementById('inpFullName').value.trim();
  if (nm.length < 2) { alert('Please enter the person\'s full name.'); return false; }
  if (!document.getElementById('hidGender').value) { alert('Please select a gender.'); return false; }
  return true;
}

function updateStepUI() {
  for (var i = 1; i <= 3; i++) {
    var num = document.getElementById('wn' + i);
    var lbl = document.getElementById('wl' + i);
    if (i < curStep)  { num.className = 'wiz-num done'; num.innerHTML = '&#10003;'; }
    else if (i === curStep) { num.className = 'wiz-num active'; num.textContent = i; }
    else { num.className = 'wiz-num'; num.textContent = i; }
    lbl.className = 'wiz-lbl' + (i === curStep ? ' active' : '');
  }
}

// ── Gender ────────────────────────────────────────────────────
document.querySelectorAll('.g-opt').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.g-opt').forEach(function(b) { b.classList.remove('sel'); });
    btn.classList.add('sel');
    document.getElementById('hidGender').value = btn.dataset.v;
  });
});

// ── Alive toggle ──────────────────────────────────────────────
document.getElementById('chkAlive').addEventListener('change', function() {
  document.getElementById('dodWrap').style.display = this.checked ? 'none' : '';
});

// ── Duplicate check ───────────────────────────────────────────
var dupTimer;
document.getElementById('inpFullName').addEventListener('input', function() {
  clearTimeout(dupTimer);
  var nm = this.value.trim();
  if (nm.length < 2) { document.getElementById('dupWarn').style.display = 'none'; return; }
  dupTimer = setTimeout(function() {
    var yr = document.getElementById('inpBirthYear').value;
    var gn = document.getElementById('hidGender').value;
    fetch('/index.php?route=person/check-duplicate&name=' + encodeURIComponent(nm)
      + (yr ? '&birth_year=' + yr : '') + (gn ? '&gender=' + gn : ''))
      .then(function(r) { return r.json(); })
      .then(function(d) {
        var w = document.getElementById('dupWarn');
        var l = document.getElementById('dupList');
        if (!d.length) { w.style.display = 'none'; return; }
        l.innerHTML = '';
        d.forEach(function(p) {
          var li = document.createElement('li');
          var a = document.createElement('a');
          a.href = '/index.php?route=member/person-view&id=' + p.person_id;
          a.target = '_blank'; a.textContent = p.label;
          li.appendChild(a); l.appendChild(li);
        });
        w.style.display = '';
      });
  }, 450);
});

// ── Parent search ─────────────────────────────────────────────
function setupSearch(pfx, onLink) {
  var inp  = document.getElementById(pfx + 'Srch');
  var drop = document.getElementById(pfx + 'Drop');
  var t;
  inp.addEventListener('input', function() {
    clearTimeout(t);
    var q = inp.value.trim();
    if (q.length < 2) { drop.style.display = 'none'; return; }
    t = setTimeout(function() {
      fetch('/index.php?route=person/search&q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (!d.length) { drop.style.display = 'none'; return; }
          drop.innerHTML = d.slice(0,8).map(function(p) {
            return '<div class="si" data-id="' + p.person_id + '" data-name="' + esc(p.full_name) + '">'
              + '<div class="si-n">' + esc(p.full_name) + '</div>'
              + '<div class="si-m">#' + p.person_id + (p.birth_year ? ' · ' + p.birth_year : '') + '</div>'
              + '</div>';
          }).join('');
          drop.style.display = 'block';
          drop.querySelectorAll('.si').forEach(function(el) {
            el.addEventListener('click', function() {
              onLink(el.dataset.id, el.dataset.name);
              drop.style.display = 'none';
            });
          });
        });
    }, 280);
  });
  document.addEventListener('click', function(e) {
    if (!drop.contains(e.target) && e.target !== inp) drop.style.display = 'none';
  });
}

function linkParent(pfx, id, name) {
  if (pfx === 'f') { fLinkedId = id; fLinkedName = name; document.getElementById('hidFatherId').value = id; }
  else              { mLinkedId = id; mLinkedName = name; document.getElementById('hidMotherId').value = id; }
  document.getElementById(pfx + 'Av').textContent = name.charAt(0).toUpperCase();
  document.getElementById(pfx + 'Nm').textContent = name;
  document.getElementById(pfx + 'Linked').style.display = '';
  document.getElementById(pfx + 'Form').style.display = 'none';
  document.getElementById(pfx + 'Box').classList.add('linked');
  if (pfx === 'f') showSibSection(name);
}

function clearParent(pfx) {
  if (pfx === 'f') { fLinkedId = ''; fLinkedName = ''; document.getElementById('hidFatherId').value = ''; hideSibSection(); }
  else              { mLinkedId = ''; mLinkedName = ''; document.getElementById('hidMotherId').value = ''; }
  document.getElementById(pfx + 'Linked').style.display = 'none';
  document.getElementById(pfx + 'Form').style.display = '';
  document.getElementById(pfx + 'Box').classList.remove('linked');
  document.getElementById(pfx + 'Srch').value = '';
}

setupSearch('f', function(id, name) { linkParent('f', id, name); });
setupSearch('m', function(id, name) { linkParent('m', id, name); });

// When father name is typed manually also show sib section
document.getElementById('fName').addEventListener('input', function() {
  var nm = this.value.trim();
  if (nm) showSibSection(nm); else hideSibSection();
});

// ── Sibling section ───────────────────────────────────────────
function showSibSection(fatherName) {
  document.getElementById('sibFatherName').textContent = fatherName;
  document.getElementById('sibSection').style.display = '';
  renderSibRows();
}

function hideSibSection() {
  document.getElementById('sibSection').style.display = 'none';
  siblings = [];
}

var totalEl = document.getElementById('inpTotalChildren');
var posEl   = document.getElementById('inpPosition');

totalEl.addEventListener('input', function() {
  document.getElementById('hidTotalChildren').value = this.value;
  rebuildPositionSelect();
  renderSibRows();
});

posEl.addEventListener('change', function() {
  document.getElementById('hidPersonPosition').value = this.value;
  renderSibRows();
});

function rebuildPositionSelect() {
  var total = Math.max(1, parseInt(totalEl.value) || 1);
  var cur   = parseInt(posEl.value) || 1;
  posEl.innerHTML = '';
  var suffixes = ['st','nd','rd'];
  for (var i = 1; i <= total; i++) {
    var opt = document.createElement('option');
    opt.value = i;
    opt.textContent = i + (suffixes[i-1] || 'th') + (i === 1 ? ' (eldest)' : i === total ? ' (youngest)' : '');
    if (i === cur) opt.selected = true;
    posEl.appendChild(opt);
  }
  document.getElementById('hidPersonPosition').value = posEl.value;
}

function renderSibRows() {
  var total  = Math.max(1, parseInt(totalEl.value) || 1);
  var mePos  = parseInt(posEl.value) || 1;
  var wrap   = document.getElementById('sibNameRows');
  var parent = document.getElementById('sibNamesWrap');
  if (total <= 1) { parent.style.display = 'none'; return; }
  parent.style.display = '';
  wrap.innerHTML = '';
  for (var i = 1; i <= total; i++) {
    var isMe = (i === mePos);
    var row  = document.createElement('div');
    row.className = 'sib-row';
    row.innerHTML =
      '<div class="sib-pos' + (isMe ? ' me' : '') + '">' + i + '</div>'
      + (isMe
        ? '<span style="font-size:.83rem;font-weight:600;color:var(--ft-primary);">' + (document.getElementById('inpFullName').value.trim() || 'This person') + '</span><span></span>'
        : '<input class="form-control form-control-sm" data-pos="' + i + '" placeholder="Name (optional)">'
          + '<select class="form-select form-select-sm sib-g" data-pos="' + i + '">'
          + '<option value="">Gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>'
          + '</select>');
    wrap.appendChild(row);
  }
}

function collectSiblings() {
  var total = Math.max(1, parseInt(totalEl.value) || 1);
  var mePos = parseInt(posEl.value) || 1;
  var out   = [];
  var inputs = document.querySelectorAll('#sibNameRows input[data-pos]');
  var selects = document.querySelectorAll('#sibNameRows .sib-g');
  inputs.forEach(function(inp) {
    var pos = parseInt(inp.dataset.pos);
    var gEl = document.querySelector('#sibNameRows .sib-g[data-pos="' + pos + '"]');
    out.push({ pos: pos, name: inp.value.trim(), gender: gEl ? gEl.value : '', isMe: false });
  });
  return out;
}

function syncSiblingsJson() {
  document.getElementById('hidSiblingsJson').value = JSON.stringify(collectSiblings());
}

// ── Review build ──────────────────────────────────────────────
function buildReview() {
  var nm = document.getElementById('inpFullName').value.trim();
  var gv = document.getElementById('hidGender').value;
  var yr = document.getElementById('inpBirthYear').value;
  var al = document.getElementById('chkAlive').checked;
  var note = document.querySelector('[name="admin_note"]').value.trim();

  document.getElementById('rv-name').textContent   = nm || '—';
  document.getElementById('rv-gender').textContent = gv ? gv.charAt(0).toUpperCase() + gv.slice(1) : '—';
  document.getElementById('rv-year').textContent   = yr || 'Not specified';
  document.getElementById('rv-alive').textContent  = al ? 'Alive' : 'Deceased';

  var fatherText = '—';
  if (fLinkedId) fatherText = fLinkedName + ' (linked)';
  else { var fn = document.getElementById('fName').value.trim(); if (fn) fatherText = fn + ' (new)'; }
  document.getElementById('rv-father').textContent = fatherText === '—' ? 'Not specified' : fatherText;

  var motherText = '—';
  if (mLinkedId) motherText = mLinkedName + ' (linked)';
  else { var mn = document.getElementById('mName').value.trim(); if (mn) motherText = mn + ' (new)'; }
  document.getElementById('rv-mother').textContent = motherText === '—' ? 'Not specified' : motherText;

  var sibVisible = document.getElementById('sibSection').style.display !== 'none';
  if (sibVisible) {
    var total = parseInt(totalEl.value) || 1;
    var pos   = parseInt(posEl.value) || 1;
    var suffixes = ['st','nd','rd'];
    var suf = suffixes[pos-1] || 'th';
    document.getElementById('rv-sib').textContent = pos + suf + ' of ' + total + ' children';
    document.getElementById('rv-sib-row').style.display = '';
  } else {
    document.getElementById('rv-sib-row').style.display = 'none';
  }

  if (note) {
    document.getElementById('rv-note').textContent = note;
    document.getElementById('rv-note-row').style.display = '';
  } else {
    document.getElementById('rv-note-row').style.display = 'none';
  }

  syncSiblingsJson();
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Init
rebuildPositionSelect();
</script>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>
