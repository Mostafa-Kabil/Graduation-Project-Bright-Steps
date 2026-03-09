<?php
session_start();
include __DIR__ . "/../connection.php";
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$parentId = $_SESSION['id'] ?? null;
