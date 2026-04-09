<?php
/**
 * Bright Steps – Social Login Handler
 * Handles "Continue with Google/Facebook" by email lookup or auto-registration.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$name = trim($input['name'] ?? '');
$provider = trim($input['provider'] ?? 'google'); // google | facebook

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit();
}

// Try to find existing user
$stmt = $connect->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Existing user – log them in
    if ($user['role'] === 'parent') {
        $_SESSION['id'] = $user['user_id'];
        $_SESSION['fname'] = $user['first_name'];
        $_SESSION['lname'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        echo json_encode(['success' => true, 'redirect' => 'dashboards/parent/dashboard.php', 'message' => 'Welcome back!']);
    } elseif ($user['role'] === 'doctor') {
        $_SESSION['id'] = $user['user_id'];
        $_SESSION['fname'] = $user['first_name'];
        $_SESSION['lname'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        echo json_encode(['success' => true, 'redirect' => 'dashboards/doctor/doctor-dashboard.php', 'message' => 'Welcome back, Dr.!']);
    } else {
        echo json_encode(['success' => true, 'redirect' => 'dashboards/parent/dashboard.php', 'message' => 'Welcome back!']);
    }
} else {
    // New user – auto-register as parent
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter your full name to create an account.']);
        exit();
    }

    $parts = explode(' ', $name, 2);
    $fname = $parts[0];
    $lname = $parts[1] ?? '';

    // Generate a random password (user can change later via settings)
    $randomPwd = bin2hex(random_bytes(16));
    $hashedPwd = password_hash($randomPwd, PASSWORD_DEFAULT);

    $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'parent')");
    $stmt->execute([$fname, $lname, $email, $hashedPwd]);
    $newId = $connect->lastInsertId();

    $stmt = $connect->prepare("INSERT INTO parent (parent_id, number_of_children) VALUES (?, 0)");
    $stmt->execute([$newId]);

    $_SESSION['id'] = $newId;
    $_SESSION['fname'] = $fname;
    $_SESSION['lname'] = $lname;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'parent';

    echo json_encode(['success' => true, 'redirect' => 'onboarding.php', 'message' => 'Account created! Let\'s setup your profile.', 'new_account' => true]);
}
