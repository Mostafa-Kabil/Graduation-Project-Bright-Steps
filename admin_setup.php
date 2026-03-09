<?php
/**
 * Admin Setup Script - Run this ONCE in the browser to set up the admin dashboard
 * URL: http://localhost/Graduation%20Project/Graduation-Project-Bright-Steps/admin_setup.php
 * DELETE THIS FILE after running it!
 */

include 'connection.php';

$results = [];

try {
    // 1. Add status column to users
    try {
        $connect->exec("ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' AFTER `role`");
        $results[] = "Added 'status' column to users table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[] = "Column 'status' already exists in users - skipped";
        } else {
            throw $e;
        }
    }

    // 2. Add created_at column to users
    try {
        $connect->exec("ALTER TABLE `users` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`");
        $results[] = "Added 'created_at' column to users table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[] = "Column 'created_at' already exists in users - skipped";
        } else {
            throw $e;
        }
    }

    // 3. Add status column to clinic
    try {
        $connect->exec("ALTER TABLE `clinic` ADD COLUMN `status` VARCHAR(20) DEFAULT 'pending' AFTER `location`");
        $results[] = "Added 'status' column to clinic table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[] = "Column 'status' already exists in clinic - skipped";
        } else {
            throw $e;
        }
    }

    // 4. Add rating column to clinic
    try {
        $connect->exec("ALTER TABLE `clinic` ADD COLUMN `rating` DECIMAL(3,2) DEFAULT 0.00 AFTER `status`");
        $results[] = "Added 'rating' column to clinic table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[] = "Column 'rating' already exists in clinic - skipped";
        } else {
            throw $e;
        }
    }

    // 5. Create activity_log table
    $connect->exec("CREATE TABLE IF NOT EXISTS `activity_log` (
        `log_id` INT(11) NOT NULL AUTO_INCREMENT,
        `activity_type` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `related_user_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`),
        KEY `related_user_id` (`related_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $results[] = "Created activity_log table";

    // 6. Create platform_settings table
    $connect->exec("CREATE TABLE IF NOT EXISTS `platform_settings` (
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` VARCHAR(255) NOT NULL DEFAULT '',
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $results[] = "Created platform_settings table";

    // 7. Create subscription_feature table
    $connect->exec("CREATE TABLE IF NOT EXISTS `subscription_feature` (
        `feature_id` INT(11) NOT NULL AUTO_INCREMENT,
        `subscription_id` INT(11) NOT NULL,
        `feature_text` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`feature_id`),
        KEY `subscription_id` (`subscription_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $results[] = "Created subscription_feature table";

    // 8. Insert platform settings
    $connect->exec("INSERT IGNORE INTO `platform_settings` (`setting_key`, `setting_value`) VALUES
        ('allow_clinic_registration', '1'),
        ('auto_approve_clinics', '0'),
        ('enable_free_trial', '1'),
        ('weekly_digest', '1'),
        ('maintenance_mode', '0')");
    $results[] = "Inserted platform settings";

    // 9. Create admin user (password = 'password')
    $hashedPassword = password_hash('password', PASSWORD_DEFAULT);

    $check = $connect->prepare("SELECT user_id FROM users WHERE email = :email");
    $check->execute(['email' => 'admin@brightsteps.com']);

    if (!$check->fetch()) {
        $stmt = $connect->prepare("INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES (:fn, :ln, :em, :pw, 'admin', 'active')");
        $stmt->execute(['fn' => 'Super', 'ln' => 'Admin', 'em' => 'admin@brightsteps.com', 'pw' => $hashedPassword]);
        $adminId = $connect->lastInsertId();

        $connect->prepare("INSERT INTO `admin` (`admin_id`, `role_level`) VALUES (:id, 1)")->execute(['id' => $adminId]);
        $results[] = "Created admin user (admin@brightsteps.com / password) with ID: $adminId";
    } else {
        $adminRow = $check->fetch();
        $results[] = "Admin user already exists - skipped";
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'admin@brightsteps.com'");
        $stmt->execute();
        $adminId = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
    }

    // 10. Sample activity logs
    $connect->exec("DELETE FROM `activity_log`");
    $connect->exec("INSERT INTO `activity_log` (`activity_type`, `description`, `created_at`) VALUES
        ('clinic_registered', 'New Clinic registered: Sunrise Pediatrics', NOW() - INTERVAL 2 HOUR),
        ('user_signup', 'New User signed up: Ahmed Hassan (Parent)', NOW() - INTERVAL 3 HOUR),
        ('subscription_upgrade', 'Subscription Upgrade: Sarah Johnson to Premium', NOW() - INTERVAL 5 HOUR),
        ('payment_received', 'Payment Received: 300.00 from Michael Thompson', NOW() - INTERVAL 6 HOUR),
        ('specialist_added', 'New Specialist added: Dr. Layla Noor at City Kids Care', NOW() - INTERVAL 8 HOUR),
        ('alert', 'Alert: 3 children flagged for developmental review', NOW() - INTERVAL 1 DAY),
        ('user_signup', 'New User signed up: Jennifer Williams (Parent)', NOW() - INTERVAL 2 DAY),
        ('clinic_verified', 'Clinic verified: Happy Smiles Clinic', NOW() - INTERVAL 3 DAY)");
    $results[] = "Inserted activity logs";

    // 11. Sample subscriptions
    $check2 = $connect->query("SELECT COUNT(*) as c FROM subscription");
    if ($check2->fetch(PDO::FETCH_ASSOC)['c'] == 0) {
        $connect->exec("INSERT INTO `subscription` (`plan_name`, `plan_period`, `price`) VALUES
            ('Free Trial', 'monthly', 0.00),
            ('Standard', 'monthly', 9.99),
            ('Premium', 'monthly', 24.99)");
        $results[] = "Inserted subscription plans";
    } else {
        $results[] = "Subscriptions already exist - skipped";
    }

    // 12. Sample behavior categories
    $check3 = $connect->query("SELECT COUNT(*) as c FROM behavior_category");
    if ($check3->fetch(PDO::FETCH_ASSOC)['c'] == 0) {
        $connect->exec("INSERT INTO `behavior_category` (`category_name`, `category_type`, `category_description`) VALUES
            ('Motor Development', 'Physical', 'Tracks gross and fine motor skill progression'),
            ('Speech and Language', 'Communication', 'Tracks verbal and non-verbal communication skills'),
            ('Social Interaction', 'Social-Emotional', 'Tracks social behavior and emotional development'),
            ('Cognitive Skills', 'Cognitive', 'Tracks problem-solving and learning abilities'),
            ('Self-Care', 'Adaptive', 'Tracks self-care and daily living skills')");
        $results[] = "Inserted behavior categories";
    } else {
        $results[] = "Behavior categories already exist - skipped";
    }

    // 13. Points rules
    $check4 = $connect->query("SELECT COUNT(*) as c FROM points_refrence");
    if ($check4->fetch(PDO::FETCH_ASSOC)['c'] == 0) {
        $stmt = $connect->prepare("INSERT INTO `points_refrence` (`admin_id`, `action_name`, `points_value`, `adjust_sign`) VALUES
            (:a1, 'Daily Login', 10, '+'),
            (:a2, 'Growth Measurement', 25, '+'),
            (:a3, 'Voice Sample Upload', 50, '+'),
            (:a4, 'Complete Weekly Goal', 100, '+'),
            (:a5, 'Redeem Badge', 200, '-'),
            (:a6, 'Missed Check-in', 5, '-')");
        $stmt->execute(['a1' => $adminId, 'a2' => $adminId, 'a3' => $adminId, 'a4' => $adminId, 'a5' => $adminId, 'a6' => $adminId]);
        $results[] = "Inserted points rules";
    } else {
        $results[] = "Points rules already exist - skipped";
    }

    // 14. Badges
    $check5 = $connect->query("SELECT COUNT(*) as c FROM badge");
    if ($check5->fetch(PDO::FETCH_ASSOC)['c'] == 0) {
        $connect->exec("INSERT INTO `badge` (`name`, `description`, `icon`) VALUES
            ('First Steps', 'Complete your first growth measurement', 'first_steps'),
            ('Voice Hero', 'Upload 5 voice samples', 'voice_hero'),
            ('Weekly Champion', 'Complete 4 weekly goals in a row', 'weekly_champion'),
            ('Growth Tracker', 'Log 10 growth measurements', 'growth_tracker'),
            ('Super Parent', 'Login for 30 consecutive days', 'super_parent')");
        $results[] = "Inserted badges";
    } else {
        $results[] = "Badges already exist - skipped";
    }

    $success = true;

} catch (PDOException $e) {
    $results[] = "ERROR: " . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Setup</title>
</head>

<body style="font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:20px;">
    <h1 style="color:<?= $success ? '#059669' : '#dc2626' ?>;">
        <?= $success ? '&#10004; Setup Complete!' : '&#10006; Setup Error' ?>
    </h1>

    <h3>Results:</h3>
    <ul>
        <?php foreach ($results as $r): ?>
            <li style="margin-bottom:8px;<?= strpos($r, 'ERROR') !== false ? 'color:red;font-weight:bold;' : '' ?>">
                <?= htmlspecialchars($r) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
        <div style="background:#f0fdf4;border:2px solid #059669;border-radius:12px;padding:20px;margin-top:20px;">
            <h3 style="color:#059669;margin-top:0;">Login Credentials:</h3>
            <p><strong>Email:</strong> admin@brightsteps.com</p>
            <p><strong>Password:</strong> password</p>
            <br>
            <a href="logout.php"
                style="display:inline-block;background:#6366f1;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">
                Go to Login &rarr;
            </a>
        </div>
        <p style="color:#dc2626;margin-top:20px;font-weight:bold;">
            &#9888; DELETE this file (admin_setup.php) after using it!
        </p>
    <?php endif; ?>
</body>

</html>