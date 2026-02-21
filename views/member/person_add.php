<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Add Family Member</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="alert alert-info">
  Tip: keep <strong>Relation to You</strong> relative to selected <strong>Reference Person</strong> (default is selected person itself).
</div>

<form method="post" action="/index.php?route=member/add-person" class="card card-body shadow-sm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

  <div class="row g-3">
    <div class="col-md-6 position-relative">
      <label class="form-label">Reference Person (optional)</label>
      <input type="text" id="reference_search" class="form-control" placeholder="Default is selected person">
      <input type="hidden" name="reference_person_id" id="reference_person_id">
      <div class="form-text">Relation selected below will be applied relative to this person.</div>
      <div id="reference_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-6 position-relative">
      <label class="form-label">Search Existing Person</label>
      <input type="text" id="existing_search" class="form-control" placeholder="Type name or ID">
      <input type="hidden" name="existing_person_id" id="existing_person_id">
      <div class="form-text">Select existing first to avoid duplicates.</div>
      <div id="existing_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Or New Full Name</label>
      <input type="text" name="full_name" id="full_name" class="form-control">
    </div>

    <div class="col-md-6 position-relative">
      <label class="form-label">Father (optional)</label>
      <input type="text" id="father_search" class="form-control" placeholder="Search father name or ID">
      <input type="hidden" name="father_person_id" id="father_person_id">
      <div id="father_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-6 position-relative">
      <label class="form-label">Mother (optional)</label>
      <input type="text" id="mother_search" class="form-control" placeholder="Search mother name or ID">
      <input type="hidden" name="mother_person_id" id="mother_person_id">
      <div id="mother_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <option value="unknown" selected>Unknown</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
      </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" checked>
        <label class="form-check-label" for="is_alive">Is Alive</label>
      </div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="date_of_birth" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Date of Death</label>
      <input type="date" name="date_of_death" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Birth Year</label>
      <input type="number" name="birth_year" class="form-control" min="1800" max="2100">
    </div>

    <div class="col-md-6">
      <label class="form-label">Current Location</label>
      <input type="text" name="current_location" class="form-control">
    </div>

    <div class="col-md-6">
      <label class="form-label">Native Location</label>
      <input type="text" name="native_location" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Blood Group</label>
      <input type="text" name="blood_group" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Occupation</label>
      <input type="text" name="occupation" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Mobile</label>
      <input type="text" name="mobile" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control">
    </div>

    <div class="col-12">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Relation to You</label>
      <select name="relation_type" id="relation_type" class="form-select">
        <option value="none" selected>No direct link</option>
        <option value="child">Child</option>
        <option value="father">Father</option>
        <option value="mother">Mother</option>
        <option value="brother">Brother</option>
        <option value="sister">Sister</option>
        <option value="grandfather">Grandfather</option>
        <option value="grandmother">Grandmother</option>
        <option value="spouse">Spouse</option>
      </select>
    </div>

    <div class="col-md-3" id="parent_type_wrap" style="display:none;">
      <label class="form-label">Your Parent Type</label>
      <select name="parent_type" class="form-select">
        <option value="father">Father</option>
        <option value="mother">Mother</option>
        <option value="adoptive">Adoptive</option>
        <option value="step">Step</option>
      </select>
    </div>

    <div class="col-md-3" id="birth_order_wrap" style="display:none;">
      <label class="form-label">Child Birth Order</label>
      <input type="number" name="birth_order" class="form-control" min="1">
    </div>

    <div class="col-md-3" id="spouse_marriage_wrap" style="display:none;">
      <label class="form-label">Marriage Date (if spouse)</label>
      <input type="date" name="spouse_marriage_date" class="form-control">
    </div>
  </div>

  <div class="mt-4">
    <button type="submit" class="btn btn-primary">Save Family Member</button>
  </div>
</form>

<script>
(function () {
  var relation = document.getElementById('relation_type');
  var parentWrap = document.getElementById('parent_type_wrap');
  var orderWrap = document.getElementById('birth_order_wrap');
  var spouseWrap = document.getElementById('spouse_marriage_wrap');
  var fullName = document.getElementById('full_name');

  function toggleFields() {
    var isChild = relation.value === 'child';
    var isSpouse = relation.value === 'spouse';
    parentWrap.style.display = isChild ? '' : 'none';
    orderWrap.style.display = isChild ? '' : 'none';
    spouseWrap.style.display = isSpouse ? '' : 'none';
  }

  function attachSearch(inputId, hiddenId, resultsId, onSelect) {
    var input = document.getElementById(inputId);
    var hidden = document.getElementById(hiddenId);
    var results = document.getElementById(resultsId);
    var timer = null;

    function clearResults() {
      results.innerHTML = '';
    }

    function selectItem(item) {
      input.value = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
      hidden.value = item.person_id;
      clearResults();
      if (typeof onSelect === 'function') {
        onSelect(item);
      }
    }

    input.addEventListener('input', function () {
      var q = input.value.trim();
      hidden.value = '';
      if (q.length < 2) {
        clearResults();
        return;
      }
      clearTimeout(timer);
      timer = setTimeout(function () {
        fetch('/index.php?route=member/person-search&q=' + encodeURIComponent(q))
          .then(function (res) { return res.json(); })
          .then(function (data) {
            clearResults();
            data.forEach(function (item) {
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'list-group-item list-group-item-action';
              btn.textContent = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
              btn.addEventListener('click', function () { selectItem(item); });
              results.appendChild(btn);
            });
          });
      }, 300);
    });

    document.addEventListener('click', function (e) {
      if (!results.contains(e.target) && e.target !== input) {
        clearResults();
      }
    });
  }

  relation.addEventListener('change', toggleFields);
  toggleFields();

  attachSearch('reference_search', 'reference_person_id', 'reference_results');
  attachSearch('existing_search', 'existing_person_id', 'existing_results', function () {
    fullName.value = '';
  });
  attachSearch('father_search', 'father_person_id', 'father_results');
  attachSearch('mother_search', 'mother_person_id', 'mother_results');

  fullName.addEventListener('input', function () {
    if (fullName.value.trim() !== '') {
      document.getElementById('existing_person_id').value = '';
      document.getElementById('existing_search').value = '';
      document.getElementById('existing_results').innerHTML = '';
    }
  });
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
