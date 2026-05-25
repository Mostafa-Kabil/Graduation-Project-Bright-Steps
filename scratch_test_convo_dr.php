<?php
require 'connection.php';

$userId = 4; // Mariam Ghareb (doctor)
$role = 'doctor';

try {
    $query = "
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
    ";
    
    $stmt = $connect->prepare($query);
    $stmt->execute([
        ':uid2' => $userId,
        ':uid3' => $userId,
        ':uid4' => $userId,
        ':uid5' => $userId,
        ':uid6' => $userId,
        ':uid7' => $userId
    ]);
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($conversations);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
