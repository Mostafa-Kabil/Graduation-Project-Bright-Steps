<?php
include 'connection.php';
print_r($connect->query("SELECT * FROM clinic WHERE email='mallak@gmail.com'")->fetch(PDO::FETCH_ASSOC));
