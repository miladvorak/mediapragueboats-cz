<?php
session_start();

require_once __DIR__ . '/../config/auth.php';

if (empty($_SESSION['authenticated'])) {
    $return_to = $_SERVER['REQUEST_URI'];
    $return_to = urlencode($return_to);
    header('Location: /login.php?return_to=' . $return_to);
    exit;
}
