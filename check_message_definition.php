<?php
require 'connection.php';
$stmt = $connect->query("SHOW CREATE TABLE message");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo $res['Create Table'];
?>
