<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<h1 class="h4"><?= htmlspecialchars($title ?? 'Member', ENT_QUOTES, 'UTF-8') ?></h1>
<p class="text-muted"><?= htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8') ?></p>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>