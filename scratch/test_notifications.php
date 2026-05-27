<?php
/**
 * Automated Verification Script for Doctor Notifications
 * Simulates creation of:
 * 1. New Appointment notification
 * 2. New Message notification
 * 3. Appointment Reminder notification
 */

require_once 'connection.php';
require_once 'includes/doctor_notifications.php';

echo "=== Doctor Notification Types Verification ===\n\n";

// 1. Resolve a valid specialist_id from DB
$specStmt = $connect->query("SELECT specialist_id FROM specialist LIMIT 1");
$specialist = $specStmt->fetch(PDO::FETCH_ASSOC);

if (!$specialist) {
    echo "ERROR: No specialist found in DB. Please seed database first.\n";
    exit(1);
}

$doctorId = (int) $specialist['specialist_id'];
echo "Found Specialist ID: {$doctorId}\n";

// Ensure settings exist and all notifications are enabled for testing
$connect->prepare("INSERT IGNORE INTO user_settings (user_id, push_notifications, email_notifications, appointment_reminders) VALUES (?, 1, 1, 1)")
        ->execute([$doctorId]);
$connect->prepare("UPDATE user_settings SET push_notifications = 1, email_notifications = 1, appointment_reminders = 1 WHERE user_id = ?")
        ->execute([$doctorId]);

// 2. Clear old test notifications for this doctor to start clean
$connect->prepare("DELETE FROM notifications WHERE user_id = ? AND (type IN ('new_appointment', 'new_message', 'appointment_reminder') OR title LIKE 'Test %')")
        ->execute([$doctorId]);

// --- Test 1: New Appointment ---
echo "\n--- Test 1: New Appointment Notification ---\n";
$title = "Test: New Appointment";
$message = "A test appointment has been scheduled for tomorrow.";
$ok = doctor_notify($connect, $doctorId, 'new_appointment', $title, $message);
if ($ok) {
    echo "SUCCESS: doctor_notify returned true for new_appointment.\n";
} else {
    echo "FAILED: doctor_notify returned false for new_appointment.\n";
}

// Verify in DB
$checkStmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'new_appointment' ORDER BY id DESC LIMIT 1");
$checkStmt->execute([$doctorId]);
$notif = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($notif && $notif['title'] === $title) {
    echo "VERIFIED: Notification row found in DB with correct values.\n";
} else {
    echo "FAILED: Notification row not found or values mismatched in DB.\n";
}


// --- Test 2: New Message ---
echo "\n--- Test 2: New Message Notification ---\n";
$title = "Test: New Message from Parent";
$message = "Hello Doctor, this is a test message.";
$ok = doctor_notify($connect, $doctorId, 'new_message', $title, $message);
if ($ok) {
    echo "SUCCESS: doctor_notify returned true for new_message.\n";
} else {
    echo "FAILED: doctor_notify returned false for new_message.\n";
}

// Verify in DB
$checkStmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'new_message' ORDER BY id DESC LIMIT 1");
$checkStmt->execute([$doctorId]);
$notif = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($notif && $notif['title'] === $title) {
    echo "VERIFIED: Notification row found in DB with correct values.\n";
} else {
    echo "FAILED: Notification row not found or values mismatched in DB.\n";
}


// --- Test 3: Appointment Reminder ---
echo "\n--- Test 3: Appointment Reminder Notification ---\n";
// Resolve a valid parent parent_id from DB
$parentStmt = $connect->query("SELECT parent_id FROM parent LIMIT 1");
$parentRow = $parentStmt->fetch(PDO::FETCH_ASSOC);
if (!$parentRow) {
    echo "ERROR: No parent found in DB. Please seed parent table first.\n";
    exit(1);
}
$parentId = (int) $parentRow['parent_id'];

// Resolve a valid payment_id from DB or create a dummy one
$payStmt = $connect->query("SELECT payment_id FROM payment LIMIT 1");
$payRow = $payStmt->fetch(PDO::FETCH_ASSOC);
if (!$payRow) {
    $connect->prepare("INSERT INTO payment (status) VALUES ('completed')")->execute();
    $paymentId = (int) $connect->lastInsertId();
    $insertedPayment = true;
} else {
    $paymentId = (int) $payRow['payment_id'];
    $insertedPayment = false;
}

// Insert a dummy appointment scheduled for 15 minutes from now (aligned with MySQL timezone)
$mysqlTimeRow = $connect->query("SELECT NOW() as now")->fetch(PDO::FETCH_ASSOC);
$mysqlTime = strtotime($mysqlTimeRow['now']);
$scheduledTime = date('Y-m-d H:i:s', $mysqlTime + 900); // 15 minutes in the future of MySQL NOW()

$connect->prepare("
    INSERT INTO appointment (parent_id, payment_id, specialist_id, scheduled_at, status, type)
    VALUES (?, ?, ?, ?, 'scheduled', 'online')
")
->execute([$parentId, $paymentId, $doctorId, $scheduledTime]);
$apptId = $connect->lastInsertId();
echo "Inserted dummy appointment #{$apptId} scheduled at {$scheduledTime} (MySQL time: " . $mysqlTimeRow['now'] . ").\n";

// Mock session variables to satisfy api_doctor_appointment_reminders.php auth checks
$_SESSION['id'] = $doctorId;
$_SESSION['role'] = 'doctor';
$_SESSION['specialist_id'] = $doctorId;

// Execute the reminders detection file
echo "Triggering api_doctor_appointment_reminders.php...\n";
ob_start();
include 'api_doctor_appointment_reminders.php';
$output = ob_get_clean();
echo "API Output: " . $output . "\n";

// Verify reminder notification was generated
$checkStmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'appointment_reminder' ORDER BY id DESC LIMIT 1");
$checkStmt->execute([$doctorId]);
$notif = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($notif && strpos($notif['message'], "#{$apptId}") !== false) {
    echo "VERIFIED: Appointment reminder row found in DB for appointment #{$apptId}.\n";
} else {
    echo "FAILED: Appointment reminder row not found or mismatch for appt #{$apptId}.\n";
}


// --- Test 4: Settings Filtering Check ---
echo "\n--- Test 4: Settings Filtering check ---\n";
// Turn off push_notifications (which controls new_appointment / new_message)
echo "Disabling push_notifications in settings...\n";
$connect->prepare("UPDATE user_settings SET push_notifications = 0 WHERE user_id = ?")
        ->execute([$doctorId]);

$ok = doctor_notify($connect, $doctorId, 'new_appointment', 'Test Ignored', 'Should not be saved');
if (!$ok) {
    echo "SUCCESS: doctor_notify correctly skipped creation when push_notifications is off.\n";
} else {
    echo "FAILED: doctor_notify created notification despite push_notifications being disabled.\n";
}

// Clean up test records
echo "\nCleaning up test records...\n";
$connect->prepare("DELETE FROM appointment WHERE appointment_id = ?")->execute([$apptId]);
if ($insertedPayment) {
    $connect->prepare("DELETE FROM payment WHERE payment_id = ?")->execute([$paymentId]);
}
$connect->prepare("DELETE FROM notifications WHERE user_id = ? AND (type IN ('new_appointment', 'new_message', 'appointment_reminder') OR title LIKE 'Test %')")
        ->execute([$doctorId]);
echo "Cleanup complete.\n";

echo "\nVerification script finished.\n";
