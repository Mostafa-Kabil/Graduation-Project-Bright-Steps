<?php
session_start();
include "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$parentId = $_SESSION['id'];
$fname = trim($_POST['first_name'] ?? '');
$lname = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

$errors = [];

if ($fname === '') {
    $errors[] = 'First name is required.';
}
if ($lname === '') {
    $errors[] = 'Last name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email is required.';
}

// Check if email is taken by another user
if (empty($errors)) {
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :uid LIMIT 1");
    $stmt->execute(['email' => $email, 'uid' => $parentId]);
    if ($stmt->rowCount() > 0) {
        $errors[] = 'This email is already in use by another account.';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['errors' => $errors]);
    exit();
}

// Update user record
$stmt = $connect->prepare("UPDATE users SET first_name = :fname, last_name = :lname, email = :email WHERE user_id = :uid");
$stmt->execute([
    'fname' => $fname,
    'lname' => $lname,
    'email' => $email,
    'uid' => $parentId
]);

// Update session
$_SESSION['fname'] = $fname;
$_SESSION['lname'] = $lname;
$_SESSION['email'] = $email;

echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
