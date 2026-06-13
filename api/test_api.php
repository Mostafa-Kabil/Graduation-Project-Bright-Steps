<?php
$_GET['id'] = 1;
require_once '../connection.php';
$stmt = $connect->query(" SELECT specialist_id FROM specialist LIMIT 1\); $row = $stmt->fetch(PDO::FETCH_ASSOC); echo json_encode($row);
