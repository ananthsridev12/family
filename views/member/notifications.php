<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Notifications</h1>
  <?php if (!empty($notifications)): ?>
  <a href="/index.php?route=notifications/mark-all-read" class="btn btn-sm btn-outline-secondary">Mark all as read</a>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
  <div class="text-muted">No notifications yet.</div>
<?php else: ?>
<div class="list-group">
  <?php foreach ($notifications as $n): ?>
  <?php $isUnread = !(bool)$n['is_read']; ?>
  <div class="list-group-item list-group-item-action <?= $isUnread ? 'list-group-item-light fw-semibold' : '' ?> d-flex justify-content-between align-items-start gap-3">
    <div>
      <div>
        <?php if (!empty($n['action_url'])): ?>
        <a href="<?= htmlspecialchars((string)$n['action_url'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
          <?= htmlspecialchars((string)$n['title'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php else: ?>
        <?= htmlspecialchars((string)$n['title'], ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
        <?php if ($isUnread): ?>
        <span class="badge bg-primary ms-1">New</span>
        <?php endif; ?>
      </div>
      <div class="text-muted small mt-1"><?= htmlspecialchars((string)$n['message'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="text-muted small"><?= htmlspecialchars((string)$n['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($isUnread): ?>
    <a href="/index.php?route=notifications/mark-read&id=<?= (int)$n['notification_id'] ?>" class="btn btn-xs btn-sm btn-outline-secondary flex-shrink-0">Mark read</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
