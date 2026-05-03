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
    try {
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
                   AND m2.is_read = 0) AS unread_count
            FROM users partner
            LEFT JOIN specialist s ON partner.user_id = s.specialist_id
            JOIN message latest ON (
                (latest.sender_id = partner.user_id AND latest.receiver_id = :uid3)
                OR (latest.sender_id = :uid4 AND latest.receiver_id = partner.user_id)
            )
            WHERE partner.user_id != :uid5
              AND latest.sent_at = (
                  SELECT MAX(m3.sent_at) FROM message m3
                  WHERE (m3.sender_id = :uid6 AND m3.receiver_id = partner.user_id)
                     OR (m3.sender_id = partner.user_id AND m3.receiver_id = :uid7)
              )
            GROUP BY partner.user_id
            ORDER BY latest.sent_at DESC
        ");
        $stmt->execute([
            ':uid2' => $userId,
            ':uid3' => $userId,
            ':uid4' => $userId,
            ':uid5' => $userId,
            ':uid6' => $userId,
            ':uid7' => $userId
        ]);
        echo json_encode(['success' => true, 'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if (!$otherUserId) {
    echo json_encode(['error' => 'other_user_id is required']);
    exit();
}

try {
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
        'messages' => $messages
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
