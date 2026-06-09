<?php
session_start();
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
    header('Location: login.php');
    exit();
}

// Redirect specialists who haven't configured credentials yet
if (isset($_SESSION['is_first_login']) && $_SESSION['is_first_login'] === 1) {
    header('Location: doctor-first-login.php');
    exit;
}

include 'connection.php';
$doctorId = intval($_SESSION['id']);
$specialistId = intval($_SESSION['specialist_id'] ?? $_SESSION['id']);

$specialty = '';
try {
    $stmt = $connect->prepare("SELECT specialization FROM specialist WHERE specialist_id = ? LIMIT 1");
    $stmt->execute([$specialistId]);
    $specialty = $stmt->fetchColumn() ?: '';
} catch (Exception $e) {}

$doctorName = htmlspecialchars(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
?>
