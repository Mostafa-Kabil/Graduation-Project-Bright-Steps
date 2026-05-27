<?php
/**
 * Helper to insert in-app notifications for doctors/specialists.
 */
function doctor_notify(PDO $connect, int $doctorUserId, string $type, string $title, string $message): bool
{
    if ($doctorUserId <= 0 || $title === '' || $message === '') {
        return false;
    }

    $validTypes = ['new_appointment', 'new_message', 'report_shared', 'appointment_reminder', 'system'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'system';
    }

    // Check doctor's notification settings
    try {
        $settingsStmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ? LIMIT 1");
        $settingsStmt->execute([$doctorUserId]);
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        if ($settings) {
            if ($type === 'appointment_reminder' && empty($settings['appointment_reminders'])) {
                return false; // Disabled by setting
            }
            if (($type === 'new_appointment' || $type === 'new_message') && empty($settings['push_notifications'])) {
                return false; // Disabled by setting
            }
        }
    } catch (Exception $e) {
        // If user_settings doesn't exist or errors out, fallback to inserting
    }

    try {
        $stmt = $connect->prepare(
            "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$doctorUserId, $type, $title, $message]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Resolve specialist user_id from specialist_id (they are the same in this schema).
 */
function doctor_user_id_from_specialist(PDO $connect, $specialistId): ?int
{
    $sid = (int) $specialistId;
    if ($sid <= 0) {
        return null;
    }
    try {
        $stmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = ? LIMIT 1");
        $stmt->execute([$sid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['specialist_id'] : $sid;
    } catch (Exception $e) {
        return $sid;
    }
}
