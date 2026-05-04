<?php
require 'connection.php';
$stmt = $connect->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'specialist' AND TABLE_SCHEMA = 'graduation_project'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
