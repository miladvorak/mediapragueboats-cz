<?php
/**
 * Shared bootstrap for the admin area.
 * Starts a hardened session, loads config helpers and exposes small utilities.
 */

declare(strict_types=1);

if (defined('PB_BOOTSTRAPPED')) {
    return;
}
define('PB_BOOTSTRAPPED', true);

// Never let warnings/notices/deprecations corrupt a JSON response.
// Errors are logged on the server, not printed to the client.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Repo root (admin/lib -> repo root is two levels up).
define('PB_ROOT', dirname(__DIR__, 2));
define('PB_CONFIG_DIR', PB_ROOT . '/config');
define('PB_INDEX_HTML', PB_ROOT . '/index.html');

require __DIR__ . '/store.php';

// ---- Session (hardened) ----------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
session_name('pb_admin');
session_start();

// ---- CSRF ------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $sent)) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Neplatný bezpečnostní token. Obnovte stránku.']);
        exit;
    }
}

// ---- Auth ------------------------------------------------------------------
function is_authed(): bool
{
    return !empty($_SESSION['admin_authed']);
}

function require_login(): void
{
    if (!is_authed()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function admin_password_is_set(): bool
{
    $auth = pb_read_array(PB_CONFIG_DIR . '/auth.php');
    return !empty($auth['password_hash']);
}

// ---- Misc helpers ----------------------------------------------------------
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** Asset URL with a cache-busting version derived from the file's mtime. */
function asset(string $relPath): string
{
    $full = dirname(__DIR__) . '/assets/' . $relPath; // admin/assets/...
    $v = @filemtime($full) ?: time();
    return 'assets/' . $relPath . '?v=' . $v;
}
