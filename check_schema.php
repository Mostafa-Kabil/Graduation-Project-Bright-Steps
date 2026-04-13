<?php
include 'connection.php';
try {
    $stmt = $connect->query("SHOW COLUMNS FROM clinic");
    $clinic_cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $connect->query("SHOW COLUMNS FROM specialist");
    $spec_cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'clinic' => $clinic_cols,
        'specialist' => $spec_cols
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
