<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Welcome to the Family Tree!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    h1,h2 { font-weight: 800; letter-spacing: -.03em; }
    .welcome-card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.1); max-width: 480px; width: 100%; overflow: hidden; text-align: center; }
    .welcome-hero { background: linear-gradient(135deg, #1e1b4b, #4f46e5); padding: 2.5rem 2rem; color: #fff; }
    .welcome-icon { width: 72px; height: 72px; background: rgba(255,255,255,.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; }
    .welcome-body { padding: 2rem; }
    .welcome-body p { color: #64748b; font-size: .95rem; line-height: 1.6; }
    .btn-go { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; border-radius: 12px; padding: .8rem 2rem; font-weight: 700; font-size: 1rem; width: 100%; transition: opacity .15s; }
    .btn-go:hover { opacity: .9; color: #fff; }
  </style>
</head>
<body>
<div class="welcome-card">
  <div class="welcome-hero">
    <div class="welcome-icon">&#127968;</div>
    <h1 style="font-size:1.8rem;margin:0">Welcome to the family!</h1>
  </div>
  <div class="welcome-body">
    <p>Your profile has been created and you're now part of the family tree. You can explore your connections, add more family members, and discover relationships.</p>
    <a href="/index.php?route=member/dashboard" class="btn btn-go mt-1">Go to my dashboard &rarr;</a>
    <a href="/index.php?route=member/family-list" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px;font-weight:600;">Browse the family tree</a>
  </div>
</div>
</body>
</html>
