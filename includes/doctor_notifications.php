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
