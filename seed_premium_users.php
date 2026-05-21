<?php
require_once "connection.php";

$premiumUsers = [
    ['email' => 'premium1@brightsteps.com', 'password' => 'password123', 'fname' => 'Premium', 'lname' => 'User One'],
    ['email' => 'premium2@brightsteps.com', 'password' => 'password123', 'fname' => 'Premium', 'lname' => 'User Two'],
];

try {
    $connect->beginTransaction();

    // Get Premium subscription ID
    $subStmt = $connect->prepare("SELECT subscription_id FROM subscription WHERE plan_name = 'Premium' LIMIT 1");
    $subStmt->execute();
    $premiumSubId = $subStmt->fetchColumn();

    if (!$premiumSubId) {
        throw new Exception("Premium subscription plan not found in database.");
    }

    foreach ($premiumUsers as $u) {
        // Check if user exists
        $check = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$u['email']]);
        $userId = $check->fetchColumn();

        if (!$userId) {
            // Create user
            $hash = password_hash($u['password'], PASSWORD_DEFAULT);
            $insUser = $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, 'parent', 'active')");
            $insUser->execute([$u['fname'], $u['lname'], $u['email'], $hash]);
            $userId = $connect->lastInsertId();
            echo "Created user: {$u['email']} (ID: $userId)\n";
        } else {
            echo "User already exists: {$u['email']} (ID: $userId)\n";
        }

        // Ensure entry in parent table
        $parentCheck = $connect->prepare("SELECT parent_id FROM parent WHERE parent_id = ?");
        $parentCheck->execute([$userId]);
        if (!$parentCheck->fetch()) {
            $insParent = $connect->prepare("INSERT INTO parent (parent_id, number_of_children) VALUES (?, 0)");
            $insParent->execute([$userId]);
            echo "Created parent record for user $userId\n";
        }

        // Assign Premium Subscription
        // First, clear existing subscriptions to avoid conflict if we are updating
        $clearSub = $connect->prepare("DELETE FROM parent_subscription WHERE parent_id = ?");
        $clearSub->execute([$userId]);

        $assignSub = $connect->prepare("INSERT INTO parent_subscription (parent_id, subscription_id, child_name) VALUES (?, ?, 'Default Child')");
        $assignSub->execute([$userId, $premiumSubId]);
        echo "Assigned Premium subscription to user $userId\n";
    }

    $connect->commit();
    echo "Premium users seeded successfully.\n";
} catch (Exception $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo "Error seeding users: " . $e->getMessage() . "\n";
}
?>
