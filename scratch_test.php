<?php
require 'connection.php';
try {
    $stmt = $connect->prepare("INSERT INTO message (sender_id, receiver_id, content) VALUES (3, 4, 'test')");
    $stmt->execute();
    echo 'Success';
} catch(PDOException $e) {
    echo $e->getMessage();
}
