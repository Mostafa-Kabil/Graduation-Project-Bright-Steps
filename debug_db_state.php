<?php
require 'connection.php';
try {
    $stmt = $connect->query("SHOW CREATE TABLE specialist");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
