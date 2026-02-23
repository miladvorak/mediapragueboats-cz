<?php
session_start();

require_once __DIR__ . '/config/auth.php';

// Already logged in
if (!empty($_SESSION['authenticated'])) {
    header('Location: /');
    exit;
}

$error = '';
$return_to = isset($_GET['return_to']) ? $_GET['return_to'] : '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $return_to = isset($_POST['return_to']) ? $_POST['return_to'] : '/';

    if (password_verify($password, AUTH_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        header('Location: ' . $return_to);
        exit;
    } else {
        $error = 'Nesprávné heslo.';
    }
}
?>
<!doctype html>
<html lang="cs">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Login — Media Hub — Prague Boats</title>
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <style>
      .login-page {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: var(--bg);
      }
      .login-box {
        background: var(--card);
        border-radius: 14px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 40px 36px;
        width: 100%;
        max-width: 380px;
      }
      .login-box .brand {
        justify-content: center;
        margin-bottom: 28px;
      }
      .login-box label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #2b3142;
        margin-bottom: 6px;
      }
      .login-box input[type="password"] {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 14px;
        background: var(--bg);
        color: var(--text);
        outline: none;
        box-sizing: border-box;
      }
      .login-box input[type="password"]:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-weak);
      }
      .login-box button {
        width: 100%;
        padding: 10px 20px;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 16px;
      }
      .login-box button:hover {
        background: #3947d4;
      }
      .login-error {
        color: #dc2626;
        font-size: 13px;
        margin-top: 10px;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class="login-page">
      <div class="login-box">
        <a class="brand" href="/">
          <img class="brand-logo" src="/assets/logo/logo.svg" alt="Prague Boats logo" />
          <div class="brand-text">
            <div class="brand-title">PRAGUE BOATS</div>
            <div class="brand-subtitle">Media hub</div>
          </div>
        </a>
        <form method="post" action="/login.php">
          <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>" />
          <label for="password">Heslo</label>
          <input type="password" id="password" name="password" autofocus required />
          <button type="submit">Přihlásit se</button>
          <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </body>
</html>
