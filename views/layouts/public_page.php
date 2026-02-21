<?php include __DIR__ . '/public_header.php'; ?>
<div class="container py-5">
  <h1 class="display-6"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="lead"><?= htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php include __DIR__ . '/footer.php'; ?>