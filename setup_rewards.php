<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connection.php';

try {
    // Create table if not exists
    $connect->exec("CREATE TABLE IF NOT EXISTS `reward_offers` (
        `offer_id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `points_required` INT NOT NULL,
        `icon` VARCHAR(50),
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Clear existing offers to ensure clean slate
    $connect->exec("TRUNCATE TABLE `reward_offers`");

    // Insert new meaningful offers based on existing system features
    $offers = [
        ['title' => 'Free Consultation Token', 'description' => 'Redeem points for a free specialist consultation appointment.', 'points_required' => 500, 'icon' => '🩺'],
        ['title' => 'Unlock Speech Analysis (1 Token)', 'description' => 'Get one free AI speech analysis evaluation.', 'points_required' => 200, 'icon' => '🗣️'],
        ['title' => 'Add Extra Child Profile', 'description' => 'Unlock the ability to add a second child to your account without Premium.', 'points_required' => 1000, 'icon' => '👶'],
        ['title' => '1 Month Premium Access', 'description' => 'Unlock all Premium features including motor milestones and full reports for 30 days.', 'points_required' => 2500, 'icon' => '👑'],
        ['title' => 'Download 1 Full Report', 'description' => 'Download one full PDF assessment report for free.', 'points_required' => 300, 'icon' => '📥'],
    ];

    $stmt = $connect->prepare("INSERT INTO reward_offers (title, description, points_required, icon) VALUES (?, ?, ?, ?)");
    foreach ($offers as $o) {
        $stmt->execute([$o['title'], $o['description'], $o['points_required'], $o['icon']]);
    }

    echo "Rewards populated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
