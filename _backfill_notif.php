<?php
include 'connection.php';
$user_id = 40;
$specialist_id = 40;

// Get appointments for this specialist
$stmt = $connect->prepare("SELECT a.*, p.first_name, p.last_name FROM appointment a JOIN users p ON a.parent_id = p.user_id WHERE a.specialist_id = ?");
$stmt->execute([$specialist_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($appointments as $appt) {
    $title = "New Appointment: " . $appt['first_name'] . " " . $appt['last_name'];
    $message = "You have an appointment scheduled for " . date('F j, Y, g:i a', strtotime($appt['scheduled_at'])) . " (" . ucfirst($appt['type']) . ")";
    
    // Check if it already exists
    $check = $connect->prepare("SELECT 1 FROM notifications WHERE user_id = ? AND title = ? AND created_at = ?");
    $check->execute([$user_id, $title, $appt['scheduled_at']]);
    if (!$check->fetch()) {
        $ins = $connect->prepare("INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES (?, 'new_appointment', ?, ?, 0, ?)");
        $ins->execute([$user_id, $title, $message, $appt['scheduled_at']]);
        echo "Inserted notification for appointment {$appt['appointment_id']}\n";
    }
}
echo "Done.";
?>
