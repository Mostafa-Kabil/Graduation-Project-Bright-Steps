<?php
// Redirect to child-profile.php which handles both adding and editing children
// The ?setup=1 parameter indicates first-time setup after registration
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Forward to child-profile.php (it handles new child when no child_id is provided)
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: child-profile.php" . $query);
exit();
?>