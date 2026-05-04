<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE message ADD COLUMN file_path VARCHAR(500) DEFAULT NULL");
    echo 'Column added';
} catch(PDOException $e) {
    echo $e->getMessage();
}
