<?php
require_once '../connection.php';
$stmt = $connect->query("SELECT specialist_id FROM specialist ORDER BY specialist_id DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $_GET['id'] = $row['specialist_id'];
    require_once 'api_get_specialist.php';
} else {
    echo "No specialist found";
}
