<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Join the Family Tree</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; color: #0f172a; -webkit-font-smoothing: antialiased; min-height: 100vh; }
    h1,h2,h3 { font-weight: 800; letter-spacing: -.03em; }

    /* Hero */
    .join-hero {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
      padding: 2.5rem 1.5rem 5rem;
      text-align: center;
      color: #fff;
    }
    .join-hero .brand { font-size: 1rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .7; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: .5rem; }
    .join-hero .brand-dot { width: 10px; height: 10px; background: #a5b4fc; border-radius: 50%; display: inline-block; }
    .join-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin-bottom: .5rem; }
    .join-hero p { font-size: 1rem; opacity: .75; max-width: 500px; margin: 0 auto; }

    /* Steps */
    .steps-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-top: 2rem;
      position: relative;
      z-index: 1;
    }
    .step-item { display: flex; flex-direction: column; align-items: center; gap: .3rem; }
    .step-num {
      width: 36px; height: 36px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,.3);
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 700;
      color: rgba(255,255,255,.5);
      transition: all .3s;
    }
    .step-num.active { background: #fff; color: #4f46e5; border-color: #fff; }
    .step-num.done { background: rgba(255,255,255,.2); color: #fff; border-color: rgba(255,255,255,.6); }
    .step-label { font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.5); white-space: nowrap; transition: color .3s; }
    .step-label.active { color: #fff; }
    .step-connector { width: 48px; height: 2px; background: rgba(255,255,255,.2); margin: 0 4px; align-self: flex-start; margin-top: 17px; }

    /* Card wrapper */
    .join-card-wrap {
      max-width: 560px;
      margin: -3rem auto 3rem;
      padding: 0 1rem;
      position: relative;
      z-index: 2;
    }
    .join-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,.12), 0 4px 16px rgba(0,0,0,.08);
      overflow: hidden;
    }
    .join-card-header {
      padding: 1.75rem 2rem 1.25rem;
      border-bottom: 1px solid #f1f5f9;
    }
    .join-card-header .step-tag { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #6366f1; margin-bottom: .4rem; }
    .join-card-header h2 { font-size: 1.3rem; margin: 0; }
    .join-card-header p { font-size: .875rem; color: #64748b; margin: .25rem 0 0; }
    .join-card-body { padding: 1.75rem 2rem; }
    .join-card-footer { padding: 1rem 2rem 1.5rem; display: flex; gap: .75rem; justify-content: flex-end; border-top: 1px solid #f1f5f9; }

    /* Form */
    .field-label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .35rem; display: block; }
    .field-label .req { color: #ef4444; margin-left: 2px; }
    .form-control, .form-select { border-color: #e2e8f0; border-radius: 10px; font-size: .9rem; padding: .65rem 1rem; transition: border-color .15s, box-shadow .15s; }
    .form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

    /* Gender picker */
    .gender-group { display: flex; gap: .5rem; }
    .gender-btn { flex: 1; padding: .6rem .5rem; border: 2px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .82rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all .15s; text-align: center; }
    .gender-btn:hover { border-color: #a5b4fc; color: #4f46e5; }
    .gender-btn.selected { border-color: #6366f1; background: #eef2ff; color: #4f46e5; }

    /* Parent section */
    .parent-card { border: 2px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; transition: border-color .2s; }
    .parent-card.linked { border-color: #6366f1; background: #f5f3ff; }
    .linked-person-pill { display: flex; align-items: center; gap: .6rem; background: #ede9fe; border-radius: 999px; padding: .4rem 1rem .4rem .5rem; width: fit-content; }
    .linked-person-pill .avatar { width: 28px; height: 28px; border-radius: 50%; background: #6366f1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; }
    .linked-person-pill .pill-name { font-size: .85rem; font-weight: 600; color: #3730a3; }
    .linked-person-pill .pill-remove { background: none; border: none; color: #6366f1; font-size: 1rem; cursor: pointer; padding: 0; line-height: 1; }

    .or-divider { display: flex; align-items: center; gap: .75rem; margin: .9rem 0; color: #94a3b8; font-size: .78rem; font-weight: 600; }
    .or-divider::before, .or-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

    /* Search */
    .search-wrap { position: relative; }
    .search-results-drop { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.1); z-index: 50; display: none; max-height: 220px; overflow-y: auto; }
    .search-results-drop .sr-item { padding: .65rem 1rem; cursor: pointer; font-size: .85rem; border-bottom: 1px solid #f8fafc; transition: background .1s; }
    .search-results-drop .sr-item:last-child { border-bottom: none; }
    .search-results-drop .sr-item:hover { background: #f5f3ff; }
    .search-results-drop .sr-item .sr-name { font-weight: 600; }
    .search-results-drop .sr-item .sr-meta { color: #94a3b8; font-size: .75rem; }

    /* Buttons */
    .btn-next { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; border-radius: 10px; padding: .65rem 1.75rem; font-weight: 700; font-size: .9rem; transition: opacity .15s, transform .1s; }
    .btn-next:hover { opacity: .9; color: #fff; }
    .btn-next:active { transform: scale(.97); }
    .btn-back { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem 1.5rem; font-weight: 600; font-size: .9rem; transition: background .15s; }
    .btn-back:hover { background: #f1f5f9; }
    .btn-skip { background: none; border: none; color: #94a3b8; font-size: .82rem; font-weight: 600; padding: .5rem; cursor: pointer; }
    .btn-skip:hover { color: #475569; }

    /* Account step */
    .password-wrap { position: relative; }
    .password-wrap .toggle-pw { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .85rem; }
    .strength-bar { height: 4px; border-radius: 2px; margin-top: .4rem; background: #e2e8f0; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0%; }

    /* Error */
    .error-list { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .error-list li { font-size: .85rem; color: #991b1b; }

    /* Progress bar (mobile) */
    .mobile-progress { height: 3px; background: #e2e8f0; }
    .mobile-progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #4f46e5); transition: width .4s; }

    @media (max-width: 480px) {
      .join-card-body, .join-card-header, .join-card-footer { padding-left: 1.25rem; padding-right: 1.25rem; }
      .step-connector { width: 28px; }
    }
  </style>
</head>
<body>

<div class="join-hero">
  <div class="brand"><span class="brand-dot"></span> FamilyTree <span class="brand-dot"></span></div>
  <h1>You're invited to join<br>the family tree</h1>
  <p>Fill in a few details and we'll create your account. It only takes a minute.</p>

  <div class="steps-row" id="stepsRow">
    <div class="step-item">
      <div class="step-num active" id="snum1">1</div>
      <div class="step-label active" id="slbl1">You</div>
    </div>
    <div class="step-connector"></div>
    <div class="step-item">
      <div class="step-num" id="snum2">2</div>
      <div class="step-label" id="slbl2">Father</div>
    </div>
    <div class="step-connector"></div>
    <div class="step-item">
      <div class="step-num" id="snum3">3</div>
      <div class="step-label" id="slbl3">Mother</div>
    </div>
    <div class="step-connector"></div>
    <div class="step-item">
      <div class="step-num" id="snum4">4</div>
      <div class="step-label" id="slbl4">Account</div>
    </div>
  </div>
</div>

<div class="join-card-wrap">
  <div class="join-card">
    <div class="mobile-progress"><div class="mobile-progress-fill" id="mobileProgress" style="width:25%"></div></div>

    <?php if (!empty($errors)): ?>
    <div class="join-card-body pb-0">
      <ul class="error-list">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=join" id="joinForm">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="gender" id="hiddenGender" value="<?= htmlspecialchars((string)(($old ?? [])['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="father_person_id" id="hiddenFatherId" value="">
      <input type="hidden" name="mother_person_id" id="hiddenMotherId" value="">

      <!-- ─── Step 1: You ─── -->
      <div id="step1">
        <div class="join-card-header">
          <div class="step-tag">Step 1 of 4</div>
          <h2>Tell us about yourself</h2>
          <p>This information will appear on your family tree profile.</p>
        </div>
        <div class="join-card-body">
          <div class="mb-3">
            <label class="field-label">Full Name <span class="req">*</span></label>
            <input class="form-control" name="full_name" id="fullNameInput" placeholder="e.g. Ananthan Rajan"
                   value="<?= htmlspecialchars((string)(($old ?? [])['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="mb-3">
            <label class="field-label">Gender <span class="req">*</span></label>
            <div class="gender-group" id="genderGroup">
              <?php $selGender = (string)(($old ?? [])['gender'] ?? ''); ?>
              <button type="button" class="gender-btn<?= $selGender === 'male' ? ' selected' : '' ?>" data-val="male">&#9794; Male</button>
              <button type="button" class="gender-btn<?= $selGender === 'female' ? ' selected' : '' ?>" data-val="female">&#9792; Female</button>
              <button type="button" class="gender-btn<?= $selGender === 'other' ? ' selected' : '' ?>" data-val="other">&#11096; Other</button>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="field-label">Birth Year</label>
              <input class="form-control" name="birth_year" type="number" min="1900" max="2025"
                     placeholder="e.g. 1985" value="<?= (int)(($old ?? [])['birth_year'] ?? 0) ?: '' ?>">
            </div>
            <div class="col-4">
              <label class="field-label">Month</label>
              <select class="form-select" name="birth_month">
                <option value="">–</option>
                <?php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; ?>
                <?php foreach ($months as $mi => $mn): ?>
                <option value="<?= $mi+1 ?>"<?= (int)(($old ?? [])['birth_month'] ?? 0) === $mi+1 ? ' selected' : '' ?>><?= $mn ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="field-label">Day</label>
              <select class="form-select" name="birth_day">
                <option value="">–</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                <option value="<?= $d ?>"<?= (int)(($old ?? [])['birth_day'] ?? 0) === $d ? ' selected' : '' ?>><?= $d ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="field-label">Mobile</label>
              <input class="form-control" name="mobile" type="tel" placeholder="+91 99999 99999"
                     value="<?= htmlspecialchars((string)(($old ?? [])['mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6">
              <label class="field-label">Email <span class="req">*</span></label>
              <input class="form-control" name="email" type="email" placeholder="you@example.com"
                     value="<?= htmlspecialchars((string)(($old ?? [])['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
        </div>
        <div class="join-card-footer">
          <button type="button" class="btn-next" onclick="goStep(2)">Continue &rarr;</button>
        </div>
      </div>

      <!-- ─── Step 2: Father ─── -->
      <div id="step2" style="display:none">
        <div class="join-card-header">
          <div class="step-tag">Step 2 of 4</div>
          <h2>Your father</h2>
          <p>Search if he's already in the tree, or add his name below.</p>
        </div>
        <div class="join-card-body">
          <div class="parent-card" id="fatherCard">
            <div id="fatherLinked" style="display:none">
              <div class="linked-person-pill">
                <div class="avatar" id="fatherAvatar"></div>
                <span class="pill-name" id="fatherLinkedName"></span>
                <button type="button" class="pill-remove" onclick="clearParent('father')">&#10005;</button>
              </div>
            </div>
            <div id="fatherSearch">
              <label class="field-label">Search existing members</label>
              <div class="search-wrap">
                <input class="form-control" id="fatherSearchInput" placeholder="Type name to search…" autocomplete="off">
                <div class="search-results-drop" id="fatherDrop"></div>
              </div>
              <div class="or-divider">or add manually</div>
              <div class="row g-2">
                <div class="col-8">
                  <label class="field-label">Father's Name</label>
                  <input class="form-control" name="father_name" id="fatherNameInput" placeholder="Full name"
                         value="<?= htmlspecialchars((string)(($old ?? [])['father_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-4">
                  <label class="field-label">Birth Year</label>
                  <input class="form-control" name="father_birth_year" type="number" min="1900" max="2010"
                         placeholder="e.g. 1955" value="<?= (int)(($old ?? [])['father_birth_year'] ?? 0) ?: '' ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="join-card-footer">
          <button type="button" class="btn-back" onclick="goStep(1)">&larr; Back</button>
          <button type="button" class="btn-skip" onclick="goStep(3)">Skip</button>
          <button type="button" class="btn-next" onclick="goStep(3)">Continue &rarr;</button>
        </div>
      </div>

      <!-- ─── Step 3: Mother ─── -->
      <div id="step3" style="display:none">
        <div class="join-card-header">
          <div class="step-tag">Step 3 of 4</div>
          <h2>Your mother</h2>
          <p>Search if she's already in the tree, or add her name below.</p>
        </div>
        <div class="join-card-body">
          <div class="parent-card" id="motherCard">
            <div id="motherLinked" style="display:none">
              <div class="linked-person-pill">
                <div class="avatar" id="motherAvatar"></div>
                <span class="pill-name" id="motherLinkedName"></span>
                <button type="button" class="pill-remove" onclick="clearParent('mother')">&#10005;</button>
              </div>
            </div>
            <div id="motherSearch">
              <label class="field-label">Search existing members</label>
              <div class="search-wrap">
                <input class="form-control" id="motherSearchInput" placeholder="Type name to search…" autocomplete="off">
                <div class="search-results-drop" id="motherDrop"></div>
              </div>
              <div class="or-divider">or add manually</div>
              <div class="row g-2">
                <div class="col-8">
                  <label class="field-label">Mother's Name</label>
                  <input class="form-control" name="mother_name" id="motherNameInput" placeholder="Full name"
                         value="<?= htmlspecialchars((string)(($old ?? [])['mother_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-4">
                  <label class="field-label">Birth Year</label>
                  <input class="form-control" name="mother_birth_year" type="number" min="1900" max="2010"
                         placeholder="e.g. 1958" value="<?= (int)(($old ?? [])['mother_birth_year'] ?? 0) ?: '' ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="join-card-footer">
          <button type="button" class="btn-back" onclick="goStep(2)">&larr; Back</button>
          <button type="button" class="btn-skip" onclick="goStep(4)">Skip</button>
          <button type="button" class="btn-next" onclick="goStep(4)">Continue &rarr;</button>
        </div>
      </div>

      <!-- ─── Step 4: Account ─── -->
      <div id="step4" style="display:none">
        <div class="join-card-header">
          <div class="step-tag">Step 4 of 4</div>
          <h2>Create your account</h2>
          <p>Choose a username and password to sign in.</p>
        </div>
        <div class="join-card-body">
          <div class="mb-3">
            <label class="field-label">Username <span class="req">*</span></label>
            <input class="form-control" name="username" id="usernameInput" placeholder="e.g. ananthan85"
                   value="<?= htmlspecialchars((string)(($old ?? [])['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="username">
            <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">Used to sign in. No spaces.</div>
          </div>
          <div class="mb-3">
            <label class="field-label">Password <span class="req">*</span></label>
            <div class="password-wrap">
              <input class="form-control" name="password" id="passwordInput" type="password" placeholder="Minimum 8 characters" autocomplete="new-password">
              <button type="button" class="toggle-pw" onclick="togglePw('passwordInput',this)">Show</button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          </div>
          <div class="mb-1">
            <label class="field-label">Confirm Password <span class="req">*</span></label>
            <div class="password-wrap">
              <input class="form-control" name="confirm_password" id="confirmInput" type="password" placeholder="Repeat password" autocomplete="new-password">
              <button type="button" class="toggle-pw" onclick="togglePw('confirmInput',this)">Show</button>
            </div>
          </div>
        </div>
        <div class="join-card-footer" style="justify-content:space-between">
          <button type="button" class="btn-back" onclick="goStep(3)">&larr; Back</button>
          <button type="submit" class="btn-next">Create Account &#10003;</button>
        </div>
      </div>

    </form>
  </div>
  <p class="text-center mt-3" style="font-size:.8rem;color:#94a3b8;">
    Already have an account? <a href="/index.php?route=login" style="color:#6366f1;font-weight:600;">Sign in</a>
  </p>
</div>

<script>
const TOTAL = 4;
let currentStep = <?= !empty($errors) ? '4' : '1' ?>;

function goStep(n) {
  if (n < 1 || n > TOTAL) return;
  document.getElementById('step' + currentStep).style.display = 'none';
  currentStep = n;
  document.getElementById('step' + currentStep).style.display = '';
  updateStepUI();
  window.scrollTo({top: 0, behavior: 'smooth'});
}

function updateStepUI() {
  for (let i = 1; i <= TOTAL; i++) {
    const num = document.getElementById('snum' + i);
    const lbl = document.getElementById('slbl' + i);
    num.className = 'step-num' + (i < currentStep ? ' done' : i === currentStep ? ' active' : '');
    lbl.className = 'step-label' + (i === currentStep ? ' active' : '');
    if (i < currentStep) num.innerHTML = '&#10003;';
    else num.textContent = i;
  }
  document.getElementById('mobileProgress').style.width = (currentStep / TOTAL * 100) + '%';
}

// Gender picker
document.querySelectorAll('.gender-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('hiddenGender').value = btn.dataset.val;
  });
});

// Parent search
function setupParentSearch(prefix) {
  const input = document.getElementById(prefix + 'SearchInput');
  const drop  = document.getElementById(prefix + 'Drop');
  const hidId = document.getElementById('hidden' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Id');
  let timer;

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { drop.style.display = 'none'; return; }
    timer = setTimeout(() => {
      fetch('/index.php?route=person/search&q=' + encodeURIComponent(q))
        .then(r => r.json()).then(data => {
          if (!data.length) { drop.style.display = 'none'; return; }
          drop.innerHTML = data.slice(0,8).map(p =>
            `<div class="sr-item" data-id="${p.person_id}" data-name="${escHtml(p.full_name)}">
               <div class="sr-name">${escHtml(p.full_name)}</div>
               <div class="sr-meta">${p.birth_year ? '#' + p.person_id + ' · ' + p.birth_year : '#' + p.person_id}</div>
             </div>`
          ).join('');
          drop.style.display = 'block';
          drop.querySelectorAll('.sr-item').forEach(el => {
            el.addEventListener('click', () => linkParent(prefix, el.dataset.id, el.dataset.name));
          });
        });
    }, 280);
  });

  document.addEventListener('click', e => {
    if (!drop.contains(e.target) && e.target !== input) drop.style.display = 'none';
  });
}

function linkParent(prefix, id, name) {
  document.getElementById('hidden' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Id').value = id;
  document.getElementById(prefix + 'LinkedName').textContent = name;
  document.getElementById(prefix + 'Avatar').textContent = name.charAt(0).toUpperCase();
  document.getElementById(prefix + 'Linked').style.display = '';
  document.getElementById(prefix + 'Search').style.display = 'none';
  document.getElementById(prefix + 'Card').classList.add('linked');
  const ni = document.getElementById(prefix + 'NameInput');
  if (ni) ni.value = '';
}

function clearParent(prefix) {
  document.getElementById('hidden' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Id').value = '';
  document.getElementById(prefix + 'Linked').style.display = 'none';
  document.getElementById(prefix + 'Search').style.display = '';
  document.getElementById(prefix + 'Card').classList.remove('linked');
  document.getElementById(prefix + 'SearchInput').value = '';
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

setupParentSearch('father');
setupParentSearch('mother');

// Password strength
const pwInput = document.getElementById('passwordInput');
const fill    = document.getElementById('strengthFill');
pwInput.addEventListener('input', () => {
  const v = pwInput.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const colors = ['#ef4444','#f59e0b','#10b981','#059669'];
  fill.style.width = (score * 25) + '%';
  fill.style.background = colors[score - 1] || '#e2e8f0';
});

function togglePw(id, btn) {
  const el = document.getElementById(id);
  const show = el.type === 'password';
  el.type = show ? 'text' : 'password';
  btn.textContent = show ? 'Hide' : 'Show';
}

// Auto-suggest username from name
document.getElementById('fullNameInput').addEventListener('blur', () => {
  const uInput = document.getElementById('usernameInput');
  if (uInput.value) return;
  const parts = document.getElementById('fullNameInput').value.trim().toLowerCase().split(/\s+/);
  if (parts.length > 0) uInput.value = parts.join('').replace(/[^a-z0-9]/g,'');
});

// On errors, show last step
<?php if (!empty($errors)): ?>
goStep(4);
<?php endif; ?>

updateStepUI();
</script>
</body>
</html>
