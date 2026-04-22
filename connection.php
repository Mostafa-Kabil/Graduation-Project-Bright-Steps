<?php
$connect = null;
$db_name = "grad";
$db_user = "root";
$db_pass = "";

// Try localhost first, then 127.0.0.1, and common ports
$hosts = ["localhost", "127.0.0.1"];
$ports = ["3306", "3307", "3308"];
$last_error = "";

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        try {
            $connect = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            break 2; // Success! Break out of both loops
        } catch (PDOException $e) {
            $last_error = "Host $host:$port -> " . $e->getMessage();
            $connect = null;
        }
    }
}

if (!$connect) {
    die("Database connection failed for all hosts. Last error: " . $last_error);
}
