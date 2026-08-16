<?php include __DIR__ . '/../layouts/app_start.php'; ?>

<?php
$evId       = (int)($event_id ?? 0);
$evTitle    = htmlspecialchars((string)($event['title'] ?? 'Unknown Event'), ENT_QUOTES, 'UTF-8');
$evDate     = !empty($event['event_date']) ? date('d M Y', strtotime((string)$event['event_date'])) : '';
$prefillDate = !empty($event['event_date']) ? (string)$event['event_date'] : '';
?>

<div class="page-header">
  <div>
    <a href="/index.php?route=admin/moi-list" class="btn btn-outline-secondary btn-sm btn-pill mb-1">&#8592; Moi Register</a>
    <h1 style="margin-top:.25rem;">&#128176; <?= $evTitle ?></h1>
    <?php if ($evDate): ?>
    <div class="text-muted" style="font-size:.9rem;"><?= htmlspecialchars($evDate, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php if ($evId > 0): ?>
    <a href="/index.php?route=admin/export-moi-csv&event_id=<?= $evId ?>"
       class="btn btn-outline-secondary btn-sm btn-pill">&#8659; Export CSV</a>
    <?php endif; ?>
    <button class="btn btn-primary btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#addMoiModal">+ Add Entry</button>
  </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Total tile -->
<div class="row mb-4">
  <div class="col-12 col-md-4">
    <div class="card text-center">
      <div class="card-body py-3">
        <div class="text-muted" style="font-size:.8rem;letter-spacing:.05em;text-transform:uppercase;">Total Collected</div>
        <div class="fw-bold" style="font-size:1.6rem;color:#16a34a;"><?= htmlspecialchars(format_inr((float)($total ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-muted" style="font-size:.8rem;"><?= count($entries ?? []) ?> entr<?= count($entries ?? []) === 1 ? 'y' : 'ies' ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Entries table -->
<div class="card mb-4">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" style="font-size:.88rem;">
      <thead>
        <tr>
          <th>#</th>
          <th>Giver Name</th>
          <th>Father / Husband Name</th>
          <th>Location</th>
          <th>Gift Type</th>
          <th class="text-end">Amount</th>
          <th>Notes</th>
          <th>Recorded By</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($entries)): ?>
        <tr>
          <td colspan="10" class="text-center text-muted py-4">No entries yet. Click "+ Add Entry" to begin recording moi.</td>
        </tr>
        <?php endif; ?>
        <?php $n = 1; foreach (($entries ?? []) as $e): ?>
        <?php
          $eId       = (int)($e['moi_id'] ?? 0);
          $giftType  = (string)($e['gift_type'] ?? 'cash');
          $giftBadge = ['cash'=>'bg-success','cheque'=>'bg-primary','gold'=>'bg-warning text-dark','gift'=>'bg-info text-dark','other'=>'bg-secondary'];
          $badgeClass = $giftBadge[$giftType] ?? 'bg-secondary';
        ?>
        <tr>
          <td class="text-muted"><?= $n++ ?></td>
          <td class="fw-600"><?= htmlspecialchars((string)($e['giver_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($e['giver_father_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($e['giver_location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($giftType), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td class="text-end fw-600"><?= htmlspecialchars(format_inr((float)($e['amount'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars(mb_strimwidth((string)($e['notes'] ?? ''), 0, 60, '…'), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted"><?= htmlspecialchars((string)($e['recorder_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted" style="white-space:nowrap;"><?= htmlspecialchars(!empty($e['created_at']) ? date('d M Y', strtotime((string)$e['created_at'])) : '', ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <div class="d-flex gap-1">
              <!-- Add to family tree as pending proposal -->
              <form method="post" action="/index.php?route=admin/submit-moi-as-member"
                    onsubmit="return confirm('Submit <?= htmlspecialchars(addslashes((string)($e['giver_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?> as a pending family member?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="moi_id"    value="<?= $eId ?>">
                <input type="hidden" name="event_id"  value="<?= $evId ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm btn-pill" title="Submit as pending family member">+ Tree</button>
              </form>
              <!-- Delete -->
              <form method="post" action="/index.php?route=admin/delete-moi"
                    onsubmit="return confirm('Delete this moi entry?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="moi_id"    value="<?= $eId ?>">
                <input type="hidden" name="event_id"  value="<?= $evId ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm btn-pill">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Entry Modal -->
<datalist id="moiGiverList">
  <?php foreach (($giver_names ?? []) as $gn): ?>
  <option value="<?= htmlspecialchars((string)$gn, ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</datalist>

<div class="modal fade" id="addMoiModal" tabindex="-1" aria-labelledby="addMoiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/create-moi">
        <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="event_id"    value="<?= $evId ?>">
        <input type="hidden" name="event_label" value="<?= $evTitle ?>">
        <input type="hidden" name="event_date"  value="<?= htmlspecialchars($prefillDate, ENT_QUOTES, 'UTF-8') ?>">

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="addMoiModalLabel">Add Moi Entry — <?= $evTitle ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label fw-600" for="moiGiverName">Giver Name <span class="text-danger">*</span></label>
              <input class="form-control" id="moiGiverName" name="giver_name"
                     required list="moiGiverList" autocomplete="off"
                     placeholder="e.g. Rajan Kumar">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-600" for="moiFatherName">Father's / Husband's Name</label>
              <input class="form-control" id="moiFatherName" name="giver_father_name"
                     placeholder="e.g. Murugan">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-600" for="moiLocation">Location / Village</label>
              <input class="form-control" id="moiLocation" name="giver_location"
                     placeholder="e.g. Madurai">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-600" for="moiAmount">Amount (&#8377;) <span class="text-danger">*</span></label>
              <input class="form-control" id="moiAmount" name="amount" type="number"
                     step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-600" for="moiGiftType">Gift Type</label>
              <select class="form-select" id="moiGiftType" name="gift_type">
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
                <option value="gold">Gold</option>
                <option value="gift">Gift</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label fw-600" for="moiNotes">Notes</label>
            <textarea class="form-control" id="moiNotes" name="notes" rows="2"
                      placeholder="Any additional notes…"></textarea>
          </div>

        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-pill">Save Entry</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/app_end.php'; ?>
