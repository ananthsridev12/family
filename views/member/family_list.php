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
        <th>Birth Order</th>
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
          <?php if (!empty($item['photo_id'])): ?>
          <img src="/index.php?route=person/attachment&id=<?= (int)$item['photo_id'] ?>"
               style="width:32px;height:32px;object-fit:cover;border-radius:50%;margin-right:6px;vertical-align:middle;"
               alt="">
          <?php endif; ?>
          <a href="/index.php?route=member/person-view&id=<?= (int)$item['person_id'] ?>">
            <?= htmlspecialchars((string)$item['full_name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
          <a href="/index.php?route=member/wiki-view&id=<?= (int)$item['person_id'] ?>" class="ms-1" style="font-size:.72rem;color:#888;" title="Wiki View">&#128196;</a>
        </td>
        <td>
          <?php if (!empty($item['father_name']) && !empty($item['father_id'])): ?>
            <a href="/index.php?route=member/person-view&id=<?= (int)$item['father_id'] ?>"><?= htmlspecialchars((string)$item['father_name'], ENT_QUOTES, 'UTF-8') ?></a>
          <?php else: ?>
            <?= htmlspecialchars((string)($item['father_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($item['mother_name']) && !empty($item['mother_id'])): ?>
            <a href="/index.php?route=member/person-view&id=<?= (int)$item['mother_id'] ?>"><?= htmlspecialchars((string)$item['mother_name'], ENT_QUOTES, 'UTF-8') ?></a>
          <?php else: ?>
            <?= htmlspecialchars((string)($item['mother_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($item['spouse_name']) && !empty($item['spouse_id'])): ?>
            <a href="/index.php?route=member/person-view&id=<?= (int)$item['spouse_id'] ?>"><?= htmlspecialchars((string)$item['spouse_name'], ENT_QUOTES, 'UTF-8') ?></a>
          <?php else: ?>
            <?= htmlspecialchars((string)($item['spouse_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars((string)$item['gender'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= $item['age'] === null ? '-' : (int)$item['age'] ?></td>
        <td><?= htmlspecialchars((string)($item['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($item['birth_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
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
