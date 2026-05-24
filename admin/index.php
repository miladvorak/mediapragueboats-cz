<?php
/**
 * Admin front controller.
 *   ?page=...   -> render a view (GET)
 *   ?action=... -> handle a POST action
 * Everything except login/setup requires an authenticated session.
 */

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

// A POST whose body exceeded post_max_size arrives with empty $_POST/$_FILES
// even though Content-Length is large. Catch it so the client gets clear JSON
// instead of an unexpected HTML page.
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && empty($_POST) && empty($_FILES)
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    json_out([
        'ok'    => false,
        'error' => 'Data požadavku byla příliš velká pro limity serveru (post_max_size / upload_max_filesize). '
                 . 'Fotky se sice zmenšují, ale zkus jich přiložit méně najednou, nebo navyš limity v PHP.',
    ], 413);
}

$action = $_POST['action'] ?? '';
$page   = $_GET['page'] ?? 'compose';

// ---------------------------------------------------------------------------
// POST actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {

    // First-run setup: create the admin password before anything is locked.
    if ($action === 'setup') {
        if (admin_password_is_set()) {
            json_out(['ok' => false, 'error' => 'Heslo už je nastavené.'], 400);
        }
        csrf_check();
        $pw  = (string) ($_POST['password'] ?? '');
        $pw2 = (string) ($_POST['password2'] ?? '');
        if (strlen($pw) < 8) {
            json_out(['ok' => false, 'error' => 'Heslo musí mít aspoň 8 znaků.'], 400);
        }
        if ($pw !== $pw2) {
            json_out(['ok' => false, 'error' => 'Hesla se neshodují.'], 400);
        }
        $ok = pb_write_array(PB_CONFIG_DIR . '/auth.php', [
            'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
        ]);
        if (!$ok) {
            json_out(['ok' => false, 'error' => 'Nepodařilo se zapsat config/auth.php (práva k zápisu?).'], 500);
        }
        $_SESSION['admin_authed'] = true;
        session_regenerate_id(true);
        json_out(['ok' => true, 'redirect' => 'index.php?page=compose']);
    }

    // Login
    if ($action === 'login') {
        csrf_check();
        // Light throttle against brute force.
        $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
        if ($_SESSION['login_fails'] >= 5) {
            $wait = min(30, 2 ** ($_SESSION['login_fails'] - 4));
            usleep($wait * 1_000_000);
        }
        $auth = pb_read_array(PB_CONFIG_DIR . '/auth.php');
        $pw = (string) ($_POST['password'] ?? '');
        if (!empty($auth['password_hash']) && password_verify($pw, $auth['password_hash'])) {
            $_SESSION['login_fails'] = 0;
            $_SESSION['admin_authed'] = true;
            session_regenerate_id(true);
            json_out(['ok' => true, 'redirect' => 'index.php?page=compose']);
        }
        $_SESSION['login_fails']++;
        json_out(['ok' => false, 'error' => 'Nesprávné heslo.'], 401);
    }

    // Everything below requires auth.
    if (!is_authed()) {
        json_out(['ok' => false, 'error' => 'Nejste přihlášeni.'], 401);
    }
    csrf_check();

    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            json_out(['ok' => true, 'redirect' => 'index.php?page=login']);
            // no break

        case 'save_settings':
            $settings = pb_settings();
            foreach (['resend_api_key','from_email','from_name','reply_to','media_url','site_base_url','subject_template','body_template'] as $k) {
                if (array_key_exists($k, $_POST)) {
                    $settings[$k] = trim((string) $_POST[$k]);
                }
            }
            pb_save_settings($settings)
                ? json_out(['ok' => true])
                : json_out(['ok' => false, 'error' => 'Uložení selhalo (práva k zápisu do config/?).'], 500);
            // no break

        case 'add_recipient':
            $email = trim((string) ($_POST['email'] ?? ''));
            $name  = trim((string) ($_POST['name'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_out(['ok' => false, 'error' => 'Neplatný e-mail.'], 400);
            }
            $list = pb_recipients();
            foreach ($list as $r) {
                if (strcasecmp($r['email'], $email) === 0) {
                    json_out(['ok' => false, 'error' => 'Tento e-mail už v seznamu je.'], 400);
                }
            }
            $list[] = ['email' => $email, 'name' => $name, 'active' => true];
            pb_save_recipients($list);
            json_out(['ok' => true, 'recipients' => pb_recipients()]);
            // no break

        case 'delete_recipient':
            $email = trim((string) ($_POST['email'] ?? ''));
            $list = array_filter(pb_recipients(), static fn($r) => strcasecmp($r['email'], $email) !== 0);
            pb_save_recipients(array_values($list));
            json_out(['ok' => true, 'recipients' => pb_recipients()]);
            // no break

        case 'toggle_recipient':
            $email = trim((string) ($_POST['email'] ?? ''));
            $list = pb_recipients();
            foreach ($list as &$r) {
                if (strcasecmp($r['email'], $email) === 0) {
                    $r['active'] = !$r['active'];
                }
            }
            unset($r);
            pb_save_recipients($list);
            json_out(['ok' => true, 'recipients' => pb_recipients()]);
            // no break

        case 'send':
            require __DIR__ . '/lib/send.php';
            pb_handle_send();
            // no break

        default:
            json_out(['ok' => false, 'error' => 'Neznámá akce.'], 400);
    }
}

// ---------------------------------------------------------------------------
// GET pages
// ---------------------------------------------------------------------------

// First run — no password yet.
if (!admin_password_is_set()) {
    $page = 'setup';
}

if ($page === 'login' || $page === 'setup') {
    if (is_authed() && $page === 'login') {
        redirect('index.php?page=compose');
    }
    require __DIR__ . "/views/$page.php";
    exit;
}

require_login();

$validPages = ['compose', 'recipients', 'settings'];
if (!in_array($page, $validPages, true)) {
    $page = 'compose';
}

require __DIR__ . '/views/layout.php';
