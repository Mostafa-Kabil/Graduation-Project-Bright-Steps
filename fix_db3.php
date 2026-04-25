<?php
require 'connection.php';
try {
    $stmt = $connect->query("SELECT admin_id FROM admin LIMIT 1");
    $admin_id = $stmt->fetchColumn();
    echo json_encode(["success" => true, "data" => $admin_id]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
