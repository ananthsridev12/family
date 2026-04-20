<?php include __DIR__ . '/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <aside class="col-lg-2 p-0 app-sidebar">
      <?php include __DIR__ . '/sidebar.php'; ?>
    </aside>
    <main class="col-lg-10 p-0">
      <div class="app-topbar">
        <div class="topbar-left">
          <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-label="Menu">
            <span>&#9776;</span>
          </button>
        </div>
        <div class="topbar-right">
          <?php if (!empty(app_user())): ?>
          <?php
            $__unread = 0;
            try {
                $__notifStmt = app_db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
                $__notifStmt->execute([':uid' => (int)(app_user()['user_id'] ?? 0)]);
                $__unread = (int)$__notifStmt->fetchColumn();
            } catch (Throwable $__e) { /* table may not exist yet */ }
          ?>
          <form method="post" action="/index.php?route=set-pov" class="pov-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? '/index.php'), ENT_QUOTES, 'UTF-8') ?>">
            <label>POV</label>
            <select class="form-select form-select-sm" name="pov_person_id">
              <?php $activePov = current_pov_id(); ?>
              <?php foreach (available_pov_people() as $p): ?>
              <option value="<?= (int)$p['person_id'] ?>" <?= $activePov === (int)$p['person_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars((string)$p['full_name'], ENT_QUOTES, 'UTF-8') ?> (#<?= (int)$p['person_id'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary" type="submit">Apply</button>
          </form>
          <a href="/index.php?route=notifications" class="notif-btn position-relative" title="Notifications">
            &#128276;
            <?php if ($__unread > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;min-width:18px;">
              <?= $__unread > 99 ? '99+' : $__unread ?>
            </span>
            <?php endif; ?>
          </a>
          <div class="user-badge">
            <span class="avatar"><?= strtoupper(mb_substr((string)(app_user()['name'] ?? 'U'), 0, 1)) ?></span>
            <?= htmlspecialchars((string)(app_user()['name'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="app-main">
