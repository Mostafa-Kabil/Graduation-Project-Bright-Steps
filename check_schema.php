<?php
include 'connection.php';
try {
    echo "--- MESSAGE TABLE ---\n";
    $stmt = $connect->query("DESCRIBE message");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n--- APPOINTMENT TABLE ---\n";
    $stmt2 = $connect->query("DESCRIBE appointment");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
?>
