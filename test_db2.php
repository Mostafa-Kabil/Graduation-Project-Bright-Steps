<?php
require 'connection.php';
$stmt = $connect->prepare("
                        SELECT u.user_id, u.email, s.clinic_id, c.clinic_name
                        FROM users u
                        LEFT JOIN specialist s ON u.user_id = s.specialist_id
                        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                        WHERE u.role = 'specialist'
                    ");
$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($profiles);
?>
