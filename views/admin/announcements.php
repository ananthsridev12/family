<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>Announcements</h1>
  <button class="btn btn-primary btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">+ New Announcement</button>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Title</th>
          <th style="min-width:200px;">Body</th>
          <th>By</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($announcements)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">No announcements yet. Post one to keep the family informed.</td>
        </tr>
        <?php endif; ?>
        <?php foreach (($announcements ?? []) as $ann): ?>
        <?php
          $aid         = (int)($ann['announcement_id'] ?? 0);
          $annTitle    = htmlspecialchars((string)($ann['title']        ?? ''), ENT_QUOTES, 'UTF-8');
          $annBody     = htmlspecialchars((string)($ann['body']         ?? ''), ENT_QUOTES, 'UTF-8');
          $isPinned    = !empty($ann['is_pinned']);
          $creatorName = htmlspecialchars((string)($ann['creator_name'] ?? ''), ENT_QUOTES, 'UTF-8');
          $createdAt   = !empty($ann['created_at']) ? date('d M Y', strtotime((string)$ann['created_at'])) : '—';
          $bodySnippet = mb_strimwidth(strip_tags($ann['body'] ?? ''), 0, 100, '…');
        ?>
        <tr>
          <td>
            <span class="fw-600">
              <?php if ($isPinned): ?>
                <span class="ann-pin-icon" title="Pinned">&#128204;</span>
              <?php endif; ?>
              <?= $annTitle ?>
            </span>
          </td>
          <td>
            <span class="text-muted" style="font-size:.85rem;"><?= htmlspecialchars($bodySnippet, ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td>
            <span style="font-size:.85rem;"><?= $creatorName ?></span>
          </td>
          <td>
            <span style="font-size:.85rem;"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td>
            <div class="d-flex gap-2 justify-content-end">
              <!-- Pin / Unpin -->
              <form method="post" action="/index.php?route=admin/toggle-announcement-pin">
                <input type="hidden" name="csrf_token"       value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="announcement_id"  value="<?= $aid ?>">
                <button type="submit"
                        class="btn btn-sm btn-pill <?= $isPinned ? 'btn-warning' : 'btn-outline-secondary' ?>"
                        title="<?= $isPinned ? 'Unpin' : 'Pin' ?> announcement">
                  <?= $isPinned ? 'Unpin' : '&#128204; Pin' ?>
                </button>
              </form>
              <!-- Delete -->
              <form method="post" action="/index.php?route=admin/delete-announcement"
                    onsubmit="return confirm('Delete this announcement? This cannot be undone.')">
                <input type="hidden" name="csrf_token"       value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="announcement_id"  value="<?= $aid ?>">
                <button type="submit" class="btn btn-sm btn-pill btn-outline-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New Announcement Modal -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <form method="post" action="/index.php?route=admin/create-announcement">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="newAnnouncementModalLabel">New Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label fw-600" for="annTitle">Title <span class="text-danger">*</span></label>
            <input class="form-control" id="annTitle" name="title" required
                   placeholder="e.g. Family Reunion 2026 – Save the Date!">
          </div>

          <div class="mb-3">
            <label class="form-label fw-600" for="annBody">Body <span class="text-danger">*</span></label>
            <textarea class="form-control" id="annBody" name="body" rows="4" required
                      placeholder="Write the full announcement here…"></textarea>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_pinned" id="annPinned" value="1">
            <label class="form-check-label" for="annPinned">
              &#128204; Pin this announcement (shown at the top for all members)
            </label>
          </div>

        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-pill">Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.ann-pin-icon {
  font-size: .95rem;
  margin-right: .2rem;
}
</style>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
