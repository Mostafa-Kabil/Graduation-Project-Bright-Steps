<?php
session_start();
// Simulate parent session
$_SESSION['email'] = 'test@test.com';
$_SESSION['id'] = 15; // Adjust to the user's actual parent_id
require 'connection.php';

// Find the actual logged-in parent's children
$stmt = $connect->query("SELECT u.user_id, u.email, c.child_id, c.first_name 
    FROM users u 
    JOIN child c ON u.user_id = c.parent_id 
    WHERE c.child_id = 207");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Parent-child mapping for child 207:\n";
print_r($result);

// Now check if child 207's parent_id matches the session id
echo "\nChecking motor data for child 207...\n";
$s7 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = 207");
$s7->execute();
echo "Motor milestones total: " . $s7->fetchColumn() . "\n";

$s8 = $connect->prepare("SELECT COUNT(*) FROM motor_milestones WHERE child_id = 207 AND is_achieved = 1");
$s8->execute();
echo "Motor milestones done: " . $s8->fetchColumn() . "\n";
