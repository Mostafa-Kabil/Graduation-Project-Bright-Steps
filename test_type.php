<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grad', 'root', '');
    $stmt = $pdo->query('SELECT (SELECT COUNT(*) FROM specialist_reviews sr WHERE sr.appointment_id = a.appointment_id) AS has_specialist_review FROM appointment a WHERE a.appointment_id = 15');
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($res);
    echo json_encode($res);
} catch(Exception $e) {
    echo $e->getMessage();
}
