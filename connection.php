<?php
$connect = null;
try {
    $connect = new PDO("mysql:host=localhost;dbname=grad;charset=utf8", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Connection failed - $connect remains null
    // API endpoints will handle this and return proper JSON errors
    error_log("Database connection failed: " . $e->getMessage());
}

