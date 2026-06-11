<?php
include 'connection.php';

// Create a test user
$stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES ('Test', 'Suspended', 'test_suspend@example.com', ?, 'parent', 'suspended')");
$stmt->execute([password_hash('password123', PASSWORD_DEFAULT)]);

// Simulate login
$_POST['login'] = true;
$_POST['email'] = 'test_suspend@example.com';
$_POST['password'] = 'password123';

ob_start();
include 'login.php';
$output = ob_get_clean();

echo "Headers sent:\n";
print_r(headers_list());

// Cleanup
$connect->query("DELETE FROM users WHERE email = 'test_suspend@example.com'");
