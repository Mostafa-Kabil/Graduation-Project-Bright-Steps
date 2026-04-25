<?php
require 'connection.php';
try {
    $stmt = $connect->query("SHOW CREATE TABLE clinic");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $res['Create Table']]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
