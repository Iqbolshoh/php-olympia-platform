<?php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login/");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] != 'admin') {
    header("Location: ../login/");
    exit;
}

include '../config.php';
$query = new Database();

?>

