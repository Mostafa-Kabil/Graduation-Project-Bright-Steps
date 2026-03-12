<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "connection.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$parentId = $_SESSION['id'] ?? null;
