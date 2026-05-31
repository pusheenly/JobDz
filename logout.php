<?php
require_once 'functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];


if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 42000, '/');
    setcookie($name, '', time() - 42000, '/', '', isset($_SERVER['HTTPS']), true);
}

session_unset();
session_destroy();

$redirectUrl = 'index.php';

if (!headers_sent()) {
    header('Location: ' . $redirectUrl);
    exit;
}

echo '<!DOCTYPE html>';
echo '<html lang="en"><head><meta charset="UTF-8"><title>Logging out</title>';
echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">';
echo '<script>window.location.href = ' . json_encode($redirectUrl) . ';</script>';
echo '</head><body>If you are not redirected, <a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">click here</a>.</body></html>';
exit;
