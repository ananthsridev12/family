<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Link Expired — Family Profile</title>
<style>
  body { font-family: system-ui, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .box { text-align: center; padding: 2.5rem; max-width: 380px; }
  .icon { font-size: 3rem; margin-bottom: 1rem; }
  h1 { font-size: 1.4rem; color: #1e293b; margin-bottom: .5rem; }
  p { color: #64748b; font-size: .9rem; line-height: 1.6; }
</style>
</head>
<body>
<div class="box">
  <div class="icon">&#128274;</div>
  <h1>This link is no longer valid</h1>
  <p><?= htmlspecialchars((string)($message ?? 'The link you followed has expired or is invalid. Please ask the family admin for a new link.'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
</body>
</html>
