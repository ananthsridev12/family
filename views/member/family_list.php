<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Family List</h1>
  <div>
    <a class="btn btn-primary btn-sm" href="/index.php?route=member/add-person">Add Person</a>
    <a class="btn btn-outline-secondary btn-sm" href="/index.php?route=member/add-marriage">Add Marriage</a>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Father</th>
        <th>Mother</th>
        <th>Spouse</th>
        <th>Gender</th>
        <th>Age</th>
        <th>Birth Year</th>
        <th>Relationship Status</th>
        <th>Marital</th>
        <th>Current Location</th>
        <th>Native Location</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $item): ?>
      <tr>
        <td><?= (int)$item['person_id'] ?></td>
        <td>
          <a href="/index.php?route=member/person-view&id=<?= (int)$item['person_id'] ?>">
            <?= htmlspecialchars((string)$item['full_name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </td>
        <td><?= htmlspecialchars((string)($item['father_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['mother_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['spouse_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)$item['gender'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= $item['age'] === null ? '-' : (int)$item['age'] ?></td>
        <td><?= htmlspecialchars((string)($item['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['relationship_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['marital_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['current_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['native_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
          <?php if (!empty($item['can_edit'])): ?>
            <a class="btn btn-sm btn-outline-primary" href="/index.php?route=member/edit-person&id=<?= (int)$item['person_id'] ?>">Edit</a>
          <?php else: ?>
            <span class="text-muted small">Read Only</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
