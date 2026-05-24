<?php /** First-run: create the admin password. */ ?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Nastavení admina — Prague Boats</title>
  <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Vítej 👋</h1>
      <p class="sub">První spuštění. Nastav si heslo do administrace.</p>
      <form id="setupForm">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field">
          <span>Heslo <span class="hint">(min. 8 znaků)</span></span>
          <input type="password" name="password" autocomplete="new-password" required minlength="8" autofocus>
        </label>
        <label class="field">
          <span>Heslo znovu</span>
          <input type="password" name="password2" autocomplete="new-password" required minlength="8">
        </label>
        <button class="btn" type="submit" style="width:100%;justify-content:center;">Vytvořit heslo</button>
        <div class="msg err" id="msg"></div>
      </form>
    </div>
  </div>
  <script>
    document.getElementById('setupForm').addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const f = ev.currentTarget, msg = document.getElementById('msg');
      msg.className = 'msg err';
      const fd = new FormData(f); fd.append('action', 'setup');
      const r = await fetch('index.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { location.href = j.redirect; }
      else { msg.textContent = j.error || 'Chyba.'; msg.classList.add('show'); }
    });
  </script>
</body>
</html>
