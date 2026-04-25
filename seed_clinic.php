<?php
require 'connection.php';

$email = 'clinic@brightsteps.com';
$password = password_hash('password123', PASSWORD_DEFAULT);

// Check if it already exists
$stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
if (!$stmt->fetch()) {
    $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['City Kids', 'Care', $email, $password, 'clinic', 'active']);
    // Update dashboard to use this insert ID instead of hardcoded 1 or 9 if we just use the session user_id
    echo "Created clinic dummy account.\n";
} else {
    echo "Clinic dummy account already exists.\n";
}
?>
