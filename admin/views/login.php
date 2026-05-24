<?php /** Login screen. */ ?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Přihlášení — Prague Boats Admin</title>
  <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Prague Boats — Admin</h1>
      <p class="sub">Zadej heslo pro přístup do administrace.</p>
      <form id="loginForm">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field">
          <span>Heslo</span>
          <input type="password" name="password" autocomplete="current-password" required autofocus>
        </label>
        <button class="btn" type="submit" style="width:100%;justify-content:center;">Přihlásit se</button>
        <div class="msg err" id="msg"></div>
      </form>
    </div>
  </div>
  <script>
    document.getElementById('loginForm').addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const f = ev.currentTarget, msg = document.getElementById('msg');
      const fd = new FormData(f); fd.append('action', 'login');
      const r = await fetch('index.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { location.href = j.redirect; }
      else { msg.textContent = j.error || 'Chyba.'; msg.classList.add('show'); }
    });
  </script>
</body>
</html>
