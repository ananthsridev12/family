<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Add Marriage</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/index.php?route=member/add-marriage" class="card card-body shadow-sm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

  <div class="row g-3">
    <div class="col-md-6 position-relative">
      <label class="form-label">Person 1 Search</label>
      <input type="text" id="person1_search" class="form-control" placeholder="Type name or ID">
      <input type="hidden" name="person1_id" id="person1_id" required>
      <div id="person1_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-6 position-relative">
      <label class="form-label">Person 2 Search</label>
      <input type="text" id="person2_search" class="form-control" placeholder="Type name or ID">
      <input type="hidden" name="person2_id" id="person2_id" required>
      <div id="person2_results" class="list-group position-absolute w-100"></div>
    </div>

    <div class="col-md-4">
      <label class="form-label">Marriage Date</label>
      <input type="date" name="marriage_date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Divorce Date</label>
      <input type="date" name="divorce_date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="married" selected>Married</option>
        <option value="divorced">Divorced</option>
        <option value="widowed">Widowed</option>
      </select>
    </div>
  </div>

  <div class="mt-4">
    <button type="submit" class="btn btn-primary">Save Marriage</button>
  </div>
</form>

<script>
(function () {
  function attachSearch(inputId, hiddenId, resultsId) {
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
    }

    input.addEventListener('input', function () {
      var q = input.value.trim();
      hidden.value = '';
      clearResults();
      if (q.length < 2) {
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

  attachSearch('person1_search', 'person1_id', 'person1_results');
  attachSearch('person2_search', 'person2_id', 'person2_results');
})();
</script>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
