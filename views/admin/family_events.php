<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>Family Events</h1>
  <button class="btn btn-primary btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#newEventModal">+ New Event</button>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php
$typeBadge = [
    'wedding'          => 'bg-warning text-dark',
    'birth'            => 'bg-success',
    'graduation'       => 'bg-primary',
    'reunion'          => 'fe-badge-reunion',
    'death'            => 'bg-secondary',
    'other'            => 'bg-light text-dark border',
    'naming_ceremony'  => 'bg-info text-dark',
    'ear_piercing'     => 'bg-pink',
    'puberty_ceremony' => 'bg-purple',
    'housewarming'     => 'bg-orange',
    'birthday'         => 'bg-danger',
];
$typeLabel = [
    'wedding'          => 'Wedding',
    'birth'            => 'Birth',
    'graduation'       => 'Graduation',
    'reunion'          => 'Reunion',
    'death'            => 'Death',
    'other'            => 'Other',
    'naming_ceremony'  => 'Naming Ceremony',
    'ear_piercing'     => 'Ear Piercing',
    'puberty_ceremony' => 'Puberty Ceremony',
    'housewarming'     => 'Housewarming',
    'birthday'         => 'Birthday',
];
?>

<div class="card mb-4">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Title</th>
          <th>Type</th>
          <th>Date</th>
          <th>Photos</th>
          <th>Moi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($events)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">No family events yet. Add one to get started.</td>
        </tr>
        <?php endif; ?>
        <?php foreach (($events ?? []) as $ev): ?>
        <?php
          $evId       = (int)($ev['event_id'] ?? 0);
          $title      = htmlspecialchars((string)($ev['title']       ?? ''), ENT_QUOTES, 'UTF-8');
          $evType     = (string)($ev['event_type'] ?? 'other');
          $evDate     = !empty($ev['event_date']) ? date('d M Y', strtotime((string)$ev['event_date'])) : '—';
          $driveLink  = htmlspecialchars((string)($ev['drive_link']  ?? ''), ENT_QUOTES, 'UTF-8');
          $desc       = htmlspecialchars((string)($ev['description'] ?? ''), ENT_QUOTES, 'UTF-8');
          $badgeClass = $typeBadge[$evType] ?? 'bg-secondary';
          $badgeLbl   = htmlspecialchars($typeLabel[$evType] ?? ucfirst($evType), ENT_QUOTES, 'UTF-8');
        ?>
        <tr>
          <td>
            <div class="fw-600"><?= $title ?></div>
            <?php if ($desc): ?>
            <div class="text-muted" style="font-size:.78rem;"><?= mb_strimwidth(strip_tags($desc), 0, 80, '…') ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($evType === 'reunion'): ?>
              <span class="badge fe-badge-reunion">Reunion</span>
            <?php else: ?>
              <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= $badgeLbl ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-size:.88rem;"><?= htmlspecialchars($evDate, ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td>
            <?php if ($driveLink): ?>
            <a href="<?= $driveLink ?>" target="_blank" rel="noopener noreferrer"
               class="btn btn-outline-secondary btn-sm btn-pill" title="Open Google Drive folder">
              &#128193; Photos
            </a>
            <?php else: ?>
            <span class="text-muted" style="font-size:.82rem;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="/index.php?route=admin/moi-event&event_id=<?= $evId ?>"
               class="btn btn-outline-success btn-sm btn-pill" title="View Moi entries for this event">
              &#128176; Moi
            </a>
          </td>
          <td>
            <form method="post" action="/index.php?route=admin/delete-family-event"
                  onsubmit="return confirm('Delete this event? This cannot be undone.')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="event_id"   value="<?= $evId ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm btn-pill">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New Event Modal -->
<div class="modal fade" id="newEventModal" tabindex="-1" aria-labelledby="newEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/create-family-event">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="newEventModalLabel">Add Family Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label fw-600" for="feTitle">Title <span class="text-danger">*</span></label>
            <input class="form-control" id="feTitle" name="title" required
                   placeholder="e.g. Rajan &amp; Priya's Wedding">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-600" for="feType">Type</label>
              <select class="form-select" id="feType" name="event_type">
                <option value="wedding">Wedding</option>
                <option value="birth">Birth</option>
                <option value="naming_ceremony">Naming Ceremony</option>
                <option value="ear_piercing">Ear Piercing</option>
                <option value="puberty_ceremony">Puberty Ceremony</option>
                <option value="birthday">Birthday</option>
                <option value="housewarming">Housewarming</option>
                <option value="graduation">Graduation</option>
                <option value="reunion">Reunion</option>
                <option value="death">Death</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-600" for="feDate">Date</label>
              <input class="form-control" id="feDate" name="event_date" type="date">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-600" for="feDesc">Description</label>
            <textarea class="form-control" id="feDesc" name="description" rows="3"
                      placeholder="Brief description of the event…"></textarea>
          </div>

          <div class="mb-1">
            <label class="form-label fw-600" for="feDrive">Google Drive Link</label>
            <input class="form-control" id="feDrive" name="drive_link" type="text"
                   placeholder="Google Drive share link">
          </div>

        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-pill">Save Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.fe-badge-reunion { background-color: #7c3aed; color: #fff; }
.bg-pink          { background-color: #ec4899; color: #fff; }
.bg-purple        { background-color: #a855f7; color: #fff; }
.bg-orange        { background-color: #f97316; color: #fff; }
</style>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
