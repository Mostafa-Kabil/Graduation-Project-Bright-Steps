<?php
require 'connection.php';
try {
    echo "feedback table:\n";
    print_r($connect->query('SELECT * FROM feedback LIMIT 5')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    echo "\nspecialist_reviews table:\n";
    print_r($connect->query('SELECT * FROM specialist_reviews LIMIT 5')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
