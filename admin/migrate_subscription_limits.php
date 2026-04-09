<?php
/**
 * Migration: Add limits column to subscription table
 * Run once via browser: /admin/migrate_subscription_limits.php
 */
session_start();
include '../connection.php';

try {
    // Add limits column if it doesn't exist
    $connect->exec("ALTER TABLE `subscription` ADD COLUMN IF NOT EXISTS `limits` TEXT DEFAULT NULL COMMENT 'JSON: usage limits per plan'");

    // Seed default limits
    $connect->exec("UPDATE subscription SET limits = '{\"max_speech_analyses\":1,\"max_children\":1,\"max_reports\":3}' WHERE plan_name = 'Free Trial' AND limits IS NULL");
    $connect->exec("UPDATE subscription SET limits = '{\"max_speech_analyses\":5,\"max_children\":3,\"max_reports\":10}' WHERE plan_name = 'Standard' AND limits IS NULL");
    $connect->exec("UPDATE subscription SET limits = '{\"max_speech_analyses\":-1,\"max_children\":-1,\"max_reports\":-1}' WHERE plan_name = 'Premium' AND limits IS NULL");

    echo "Migration complete! Subscription limits column added and seeded.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
