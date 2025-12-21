<?php
/**
 * Debug Session Info
 * Access via: http://localhost/siapkak/public/debug-session.php
 */

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

header('Content-Type: application/json');

$sessionData = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false,
    'cookie_params' => session_get_cookie_params(),
    'cookies' => $_COOKIE
];

echo json_encode($sessionData, JSON_PRETTY_PRINT);
