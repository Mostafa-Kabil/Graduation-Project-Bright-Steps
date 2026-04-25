<?php
require 'connection.php';
try {
    $stmt = $connect->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(["success" => true, "tables" => $tables]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
