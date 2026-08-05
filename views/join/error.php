<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invalid Invite Link</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    .err-card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.1); max-width: 440px; width: 100%; padding: 2.5rem 2rem; text-align: center; }
    .err-icon { font-size: 3rem; margin-bottom: 1rem; }
    h2 { font-weight: 800; letter-spacing: -.03em; }
    p { color: #64748b; }
  </style>
</head>
<body>
<div class="err-card">
  <div class="err-icon">&#128274;</div>
  <h2>Invalid Link</h2>
  <p><?= htmlspecialchars((string)($message ?? 'This invite link is not valid.'), ENT_QUOTES, 'UTF-8') ?></p>
  <a href="/index.php?route=login" class="btn btn-outline-secondary mt-2" style="border-radius:10px;font-weight:600;">Back to login</a>
</div>
</body>
</html>
