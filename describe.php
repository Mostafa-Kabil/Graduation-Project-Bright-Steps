<?php
require 'connection.php';
try {
    $stmt = $connect->query("DESCRIBE appointments");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $stmt = $connect->query("DESCRIBE patients");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
?>
