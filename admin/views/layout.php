<?php
/** Authenticated shell: top nav + the selected page. */
require_once __DIR__ . '/../lib/boats.php';

$settings   = pb_settings();
$recipients = pb_recipients();
$boats      = pb_parse_boats();

$titles = [
    'compose'    => 'Nová notifikace',
    'recipients' => 'Příjemci',
    'settings'   => 'Nastavení',
];

// Data passed to the front-end.
$bootData = [
    'csrf'       => csrf_token(),
    'boats'      => $boats,
    'recipients' => $recipients,
    'settings'   => [
        'media_url'        => $settings['media_url'],
        'subject_template' => $settings['subject_template'],
        'body_template'    => $settings['body_template'],
        'from_email'       => $settings['from_email'],
        'resend_set'       => $settings['resend_api_key'] !== '',
    ],
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($titles[$page] ?? 'Admin') ?> — Prague Boats</title>
  <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">Prague Boats <small>Admin</small></div>
      <nav class="nav">
        <a href="index.php?page=compose"    class="<?= $page === 'compose' ? 'active' : '' ?>">Notifikace</a>
        <a href="index.php?page=recipients" class="<?= $page === 'recipients' ? 'active' : '' ?>">Příjemci</a>
        <a href="index.php?page=settings"   class="<?= $page === 'settings' ? 'active' : '' ?>">Nastavení</a>
      </nav>
      <button class="logout-btn" id="logoutBtn" type="button">Odhlásit</button>
    </div>
  </header>

  <main class="wrap">
    <?php require __DIR__ . "/$page.php"; ?>
  </main>

  <script>
    window.PB = <?= json_encode($bootData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script src="<?= e(asset('admin.js')) ?>"></script>
</body>
</html>
