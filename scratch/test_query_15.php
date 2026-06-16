<?php
require 'connection.php';
$specialist_id = 15;
$stmt = $connect->prepare("
    SELECT DISTINCT
        c.child_id, c.first_name AS child_first_name, c.last_name AS child_last_name,
        c.gender, c.birth_year, c.birth_month, c.birth_day,
        u.first_name AS parent_first_name, u.last_name AS parent_last_name,
        p.parent_id,
        (SELECT a2.status FROM appointment a2 
         WHERE a2.specialist_id = :sid2 AND a2.parent_id = p.parent_id 
         ORDER BY a2.scheduled_at DESC LIMIT 1) AS last_appointment_status,
        (SELECT a3.scheduled_at FROM appointment a3 
         WHERE a3.specialist_id = :sid3 AND a3.parent_id = p.parent_id 
         ORDER BY a3.scheduled_at DESC LIMIT 1) AS last_appointment_date
    FROM appointment a
    JOIN parent p ON p.parent_id = a.parent_id
    JOIN users u ON u.user_id = p.parent_id
    LEFT JOIN child c ON c.child_id = a.child_id
    WHERE a.specialist_id = :sid
    GROUP BY c.child_id
    ORDER BY last_appointment_date DESC
");
$stmt->execute([':sid' => $specialist_id, ':sid2' => $specialist_id, ':sid3' => $specialist_id]);
$patients_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($patients_data);
