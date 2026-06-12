<?php
include 'connection.php';

try {
    $connect->exec("TRUNCATE TABLE reward_offers");

    // Standard/All Rewards
    $stmt = $connect->prepare("INSERT INTO reward_offers (title, description, points_required, icon, target_plan) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute(['Standard Profile Avatar', 'Unlock a special avatar border for your profile.', 500, '🌟', 'all']);
    $stmt->execute(['Download 1 Full Report', 'Export your child\'s progress report as a detailed PDF.', 1000, '📄', 'all']);
    $stmt->execute(['Basic Educational Material', 'Download a printable activity workbook for preschoolers.', 1500, '📚', 'all']);

    // Premium Rewards
    $stmt->execute(['10% Off Clinic Appointment', 'Get a 10% discount on your next clinic appointment booking.', 2000, '🎟️', 'premium']);
    $stmt->execute(['25% Off Clinic Appointment', 'Get a 25% discount on your next clinic appointment booking.', 4500, '🎫', 'premium']);
    $stmt->execute(['50% Off Clinic Appointment', 'Get a massive 50% discount on your next clinic appointment booking.', 8000, '🔥', 'premium']);
    $stmt->execute(['Free Clinic Appointment', 'Redeem points for a completely free 1-on-1 specialist consultation.', 15000, '👩‍⚕️', 'premium']);
    $stmt->execute(['10% Off Premium Plan Renewal', 'Get a 10% discount on your next Premium subscription renewal.', 3000, '💎', 'premium']);

    echo "Premium rewards updated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
