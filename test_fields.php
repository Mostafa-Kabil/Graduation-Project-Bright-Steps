<?php
error_reporting(0);
$_GET['parent_id'] = 4;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grad', 'root', '');
    $sql = "SELECT a.appointment_id, s.clinic_id,
               (SELECT COUNT(*) FROM specialist_reviews sr WHERE sr.appointment_id = a.appointment_id) AS has_specialist_review,
               (SELECT COUNT(*) FROM clinic_reviews cr WHERE cr.appointment_id = a.appointment_id) AS has_clinic_review
        FROM appointment a
        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE a.parent_id = :parent_id
        ORDER BY a.scheduled_at DESC
        LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['parent_id' => 9]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
}
