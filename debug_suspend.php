<?php
include 'connection.php';

// Fetch the user to see their status before
$stmt = $connect->prepare("SELECT user_id, email, password, status FROM users WHERE email = 'mostafakabil123@gmail.com'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Before suspend: " . json_encode($user) . "\n";

// Update the user directly
$connect->query("UPDATE users SET status = 'suspended' WHERE email = 'mostafakabil123@gmail.com'");

// Fetch again
$stmt = $connect->prepare("SELECT user_id, email, password, status FROM users WHERE email = 'mostafakabil123@gmail.com'");
$stmt->execute();
$user2 = $stmt->fetch(PDO::FETCH_ASSOC);

echo "After suspend: " . json_encode($user2) . "\n";

// Let's test the exact logic from login.php
if (isset($user2['status']) && $user2['status'] === 'suspended') {
    echo "LOGIC PASSED: User is suspended and would redirect to account-suspended.php\n";
} else {
    echo "LOGIC FAILED: Status is not 'suspended' or isset failed.\n";
    var_dump(isset($user2['status']));
    var_dump($user2['status'] === 'suspended');
}
