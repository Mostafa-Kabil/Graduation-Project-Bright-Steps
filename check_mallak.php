<?php
include 'connection.php';
$u = $connect->query("SELECT * FROM users WHERE email='mallak@gmail.com'")->fetch(PDO::FETCH_ASSOC);
print_r($u);
