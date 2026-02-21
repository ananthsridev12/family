<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4 mb-3">Add Person</h1>
<form class="row g-3" method="post" action="#">
  <div class="col-md-6">
    <label class="form-label">Full Name</label>
    <input class="form-control" name="full_name" placeholder="Person name">
  </div>
  <div class="col-md-3">
    <label class="form-label">Gender</label>
    <select class="form-select" name="gender">
      <option value="unknown">Unknown</option>
      <option value="male">Male</option>
      <option value="female">Female</option>
      <option value="other">Other</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Birth Year</label>
    <input class="form-control" name="birth_year" type="number" min="1800" max="2100">
  </div>

  <div class="col-md-6">
    <label class="form-label">Add Parent (search)</label>
    <input class="form-control person-search" data-target="parent_id" placeholder="Type at least 2 characters">
    <input type="hidden" name="parent_id" id="parent_id">
    <div class="list-group mt-1 search-results"></div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Add Spouse (search)</label>
    <input class="form-control person-search" data-target="spouse_id" placeholder="Type at least 2 characters">
    <input type="hidden" name="spouse_id" id="spouse_id">
    <div class="list-group mt-1 search-results"></div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">Save Person</button>
  </div>
</form>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>