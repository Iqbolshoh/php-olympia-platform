<?php
session_start();

include '../config.php';
$query = new Database();

$query->delete('active_sessions', 'session_token = ?', [session_id()], 's');

session_unset();
$_SESSION = [];
session_destroy();
session_write_close();

$cookies = ['username', 'session_token'];
foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

header("Location: ../login/");
exit;
