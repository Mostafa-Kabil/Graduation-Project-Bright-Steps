<?php
include 'connection.php';
$stmt = $connect->query('DESCRIBE clinic');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo implode(' | ', $row) . PHP_EOL;
}
