<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE doctor_report ADD COLUMN visibility enum('private','shared') DEFAULT 'private'");
    echo 'Column added';
} catch(PDOException $e) {
    echo $e->getMessage();
}
