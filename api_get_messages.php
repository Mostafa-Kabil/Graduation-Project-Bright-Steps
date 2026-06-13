<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['id'];
$otherUserId = $_GET['other_user_id'] ?? null;
$childId = $_GET['child_id'] ?? null;

if (isset($_GET['action']) && $_GET['action'] === 'get_conversations') {
    $role = $_SESSION['role'] ?? '';
    try {
        if ($role === 'parent') {
            $stmt = $connect->prepare("
                SELECT 
                    partner.user_id AS partner_id,
                    partner.first_name AS partner_first_name,
                    partner.last_name  AS partner_last_name,
                    partner.role       AS partner_role,
                    s.specialization,
                    latest.content     AS last_message,
                    latest.sent_at     AS last_message_time,
                    (SELECT COUNT(*) FROM message m2 
                     WHERE m2.sender_id = partner.user_id 
                       AND m2.receiver_id = :uid2 
                       AND m2.is_read = 0) AS unread_count,
                    apt.appointment_id,
                    apt.scheduled_at AS appointment_scheduled_at,
                    apt.status AS appointment_status,
                    apt.type AS appointment_type,
                    c.clinic_id,
                    c.clinic_name,
                    c.location AS clinic_location,
                    (SELECT COUNT(*) FROM specialist_reviews sr WHERE sr.appointment_id = apt.appointment_id) AS has_specialist_review,
                    (SELECT COUNT(*) FROM clinic_reviews cr WHERE cr.appointment_id = apt.appointment_id) AS has_clinic_review
                FROM appointment apt
                JOIN users partner ON apt.specialist_id = partner.user_id
                LEFT JOIN specialist s ON partner.user_id = s.specialist_id
                LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                LEFT JOIN message latest ON (
                    (latest.sender_id = partner.user_id AND latest.receiver_id = :uid3)
                    OR (latest.sender_id = :uid4 AND latest.receiver_id = partner.user_id)
                ) AND latest.message_id = (
                    SELECT MAX(m3.message_id) FROM message m3
                    WHERE (m3.sender_id = :uid6 AND m3.receiver_id = partner.user_id)
                       OR (m3.sender_id = partner.user_id AND m3.receiver_id = :uid7)
                )
                WHERE apt.parent_id = :uid5
                  AND apt.status IN ('confirmed', 'completed', 'approved')
                  AND apt.appointment_id = (
                      SELECT MAX(apt2.appointment_id)
                      FROM appointment apt2
                      WHERE apt2.parent_id = apt.parent_id 
                        AND apt2.specialist_id = apt.specialist_id
                        AND apt2.status IN ('confirmed', 'completed', 'approved')
                  )
                ORDER BY COALESCE(latest.sent_at, '1970-01-01 00:00:00') DESC, apt.appointment_id DESC
            ");
            $stmt->execute([
                ':uid2' => $userId,
                ':uid3' => $userId,
                ':uid4' => $userId,
                ':uid5' => $userId,
                ':uid6' => $userId,
                ':uid7' => $userId
            ]);
        } else if ($role === 'doctor' || $role === 'specialist') {
            $stmt = $connect->prepare("
                SELECT 
                    partner.user_id AS partner_id,
                    partner.first_name AS partner_first_name,
                    partner.last_name  AS partner_last_name,
                    partner.role       AS partner_role,
                    NULL AS specialization,
                    latest.content     AS last_message,
                    latest.sent_at     AS last_message_time,
                    (SELECT COUNT(*) FROM message m2 
                     WHERE m2.sender_id = partner.user_id 
                       AND m2.receiver_id = :uid2 
                       AND m2.is_read = 0) AS unread_count,
                    apt.appointment_id,
                    apt.scheduled_at AS appointment_scheduled_at,
                    apt.status AS appointment_status,
                    apt.type AS appointment_type,
                    c.clinic_id,
                    c.clinic_name,
                    c.location AS clinic_location
                FROM appointment apt
                JOIN users partner ON apt.parent_id = partner.user_id
                LEFT JOIN specialist s ON apt.specialist_id = s.specialist_id
                LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                LEFT JOIN message latest ON (
                    (latest.sender_id = partner.user_id AND latest.receiver_id = :uid3)
                    OR (latest.sender_id = :uid4 AND latest.receiver_id = partner.user_id)
                ) AND latest.message_id = (
                    SELECT MAX(m3.message_id) FROM message m3
                    WHERE (m3.sender_id = :uid6 AND m3.receiver_id = partner.user_id)
                       OR (m3.sender_id = partner.user_id AND m3.receiver_id = :uid7)
                )
                WHERE apt.specialist_id = :uid5
                  AND apt.status IN ('confirmed', 'completed', 'approved')
                  AND apt.appointment_id = (
                      SELECT MAX(apt2.appointment_id)
                      FROM appointment apt2
                      WHERE apt2.specialist_id = apt.specialist_id 
                        AND apt2.parent_id = apt.parent_id
                        AND apt2.status IN ('confirmed', 'completed', 'approved')
                  )
                ORDER BY COALESCE(latest.sent_at, '1970-01-01 00:00:00') DESC, apt.appointment_id DESC
            ");
            $stmt->execute([
                ':uid2' => $userId,
                ':uid3' => $userId,
                ':uid4' => $userId,
                ':uid5' => $userId,
                ':uid6' => $userId,
                ':uid7' => $userId
            ]);
        } else {
            echo json_encode(['success' => true, 'data' => [], 'conversations' => []]);
            exit();
        }
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $conversations, 'conversations' => $conversations]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if (!$otherUserId) {
    echo json_encode(['error' => 'other_user_id is required']);
    exit();
}

// Guard: verify appointment exists
$stmtCheck = $connect->prepare("
    SELECT 1 FROM appointment 
    WHERE ((parent_id = ? AND specialist_id = ?) OR (parent_id = ? AND specialist_id = ?))
      AND status IN ('Scheduled', 'Completed', 'confirmed', 'approved') LIMIT 1
");
$stmtCheck->execute([$userId, $otherUserId, $otherUserId, $userId]);
if (!$stmtCheck->fetch()) {
    echo json_encode(['error' => 'Messaging is only available after your appointment is confirmed by the specialist.']);
    exit();
}

try {
    // Check if active appointment exists
    $checkAppt = $connect->prepare("
        SELECT a.appointment_id, a.scheduled_at, a.status, a.type,
               c.clinic_id, c.clinic_name, c.location
        FROM appointment a
        LEFT JOIN specialist s ON a.specialist_id = s.specialist_id
        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE ((a.parent_id = :uid1 AND a.specialist_id = :uid2) 
           OR (a.parent_id = :uid2 AND a.specialist_id = :uid1))
          AND a.status IN ('confirmed', 'completed', 'approved')
        ORDER BY a.scheduled_at DESC, a.appointment_id DESC
        LIMIT 1
    ");
    $checkAppt->execute([':uid1' => $userId, ':uid2' => $otherUserId]);
    $activeAppointment = $checkAppt->fetch(PDO::FETCH_ASSOC);

    if (!$activeAppointment) {
        http_response_code(403);
        echo json_encode(['error' => 'No active appointment exists between you and this user. Access forbidden.']);
        exit();
    }

    // Uses the newly created idx_msg_thread index if child_id is provided
    $query = "
        SELECT m.message_id, m.sender_id, m.receiver_id, m.content, 
               m.meeting_link, m.file_path, m.is_read, m.sent_at,
               u1.first_name as sender_first_name, u1.last_name as sender_last_name,
               u2.first_name as receiver_first_name, u2.last_name as receiver_last_name
        FROM message m
        JOIN users u1 ON m.sender_id = u1.user_id
        JOIN users u2 ON m.receiver_id = u2.user_id
        WHERE ((m.sender_id = :user_id AND m.receiver_id = :other_user_id)
           OR (m.sender_id = :other_user_id AND m.receiver_id = :user_id))
    ";
    
    $params = [
        ':user_id' => $userId,
        ':other_user_id' => $otherUserId
    ];

    if ($childId) {
        $query .= " AND m.child_id = :child_id";
        $params[':child_id'] = $childId;
    }

    $query .= " ORDER BY m.sent_at ASC";

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read
    $updateStmt = $connect->prepare("
        UPDATE message 
        SET is_read = 1 
        WHERE receiver_id = :user_id AND sender_id = :other_user_id AND is_read = 0
    ");
    $updateStmt->execute([
        ':user_id' => $userId,
        ':other_user_id' => $otherUserId
    ]);

    echo json_encode([
        'success' => true,
        'data' => $messages,
        'messages' => $messages,
        'appointment' => $activeAppointment
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
