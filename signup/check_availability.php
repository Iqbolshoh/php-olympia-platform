<?php
session_start();

include '../config.php';
$query = new Database();

if (!empty($_SESSION['loggedin']) && isset(ROLES[$_SESSION['role']])) {
    header("Location: " . SITE_PATH . ROLES[$_SESSION['role']]);
    exit;
}

$response = ['exists' => false];

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $email_check = $query->select('users', 'email', 'email = ?', [$email], 's');
    if ($email_check) {
        $response['exists'] = true;
    }
}

if (isset($_POST['username'])) {
    $username = $_POST['username'];
    $username_check = $query->select('users', 'username', 'username = ?', [$username], 's');
    if ($username_check) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
exit;
