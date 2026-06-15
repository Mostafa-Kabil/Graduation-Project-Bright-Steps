<?php
/**
 * cron_follow_up.php
 * Run this script via a cron job (e.g., daily) to send automated follow-up messages
 * from specialists to parents for completed appointments, based on the specialist's preferences.
 */

require_once __DIR__ . '/connection.php';

echo "Starting Follow-Up Reminders Cron Job...\n";

try {
    // Add follow_up_sent column if it doesn't exist
    try {
        $connect->exec("ALTER TABLE appointment ADD COLUMN follow_up_sent TINYINT(1) DEFAULT 0");
        echo "Added follow_up_sent column to appointment table.\n";
    } catch (PDOException $e) {
        // Column likely already exists
    }

    // Get completed appointments that haven't had a follow up sent
    $stmt = $connect->prepare("
        SELECT a.appointment_id, a.specialist_id, a.parent_id, a.child_id, a.scheduled_at,
               o.follow_up_reminder,
               u.first_name AS doc_first_name, u.last_name AS doc_last_name
        FROM appointment a
        JOIN doctor_onboarding o ON a.specialist_id = o.doctor_id
        JOIN users u ON a.specialist_id = u.user_id
        WHERE a.status = 'completed' AND a.follow_up_sent = 0
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sentCount = 0;

    foreach ($appointments as $appt) {
        $reminderPref = $appt['follow_up_reminder'] ?? '1week';
        $intervalDays = 7; // default 1 week

        if ($reminderPref === '2weeks') {
            $intervalDays = 14;
        } elseif ($reminderPref === '1month') {
            $intervalDays = 30;
        } elseif ($reminderPref === 'custom') {
            continue; // Skip custom for now as there's no UI to set custom days
        }

        $scheduledAt = new DateTime($appt['scheduled_at']);
        $now = new DateTime();
        $diffDays = $now->diff($scheduledAt)->days;

        // If the appointment was completed and the required days have passed
        if ($scheduledAt <= $now && $diffDays >= $intervalDays) {
            
            // Send message
            $messageContent = "Hello, this is an automated follow-up message. It has been a while since our last appointment. How is everything going? Please feel free to book another appointment if you'd like to check in on progress.";
            
            $msgStmt = $connect->prepare("
                INSERT INTO message (sender_id, receiver_id, appointment_id, child_id, content, is_read, sent_at)
                VALUES (:sid, :rid, :aid, :cid, :content, 0, NOW())
            ");
            $msgStmt->execute([
                ':sid' => $appt['specialist_id'],
                ':rid' => $appt['parent_id'],
                ':aid' => $appt['appointment_id'],
                ':cid' => $appt['child_id'],
                ':content' => $messageContent
            ]);

            // Mark as sent
            $updStmt = $connect->prepare("UPDATE appointment SET follow_up_sent = 1 WHERE appointment_id = :aid");
            $updStmt->execute([':aid' => $appt['appointment_id']]);

            echo "Sent follow-up for appointment ID {$appt['appointment_id']} (Specialist: {$appt['doc_first_name']})\n";
            $sentCount++;
        }
    }

    echo "Cron Job Finished. Total reminders sent: $sentCount\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
