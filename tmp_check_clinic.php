<?php
require 'connection.php';
header('Content-Type: text/plain');
try {
    echo "--- Table: clinic ---\n";
    $stmt = $connect->query("DESCRIBE clinic");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']} - Default: {$row['Default']}\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
