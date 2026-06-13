<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE doctor_onboarding ADD COLUMN age_groups TEXT DEFAULT NULL, ADD COLUMN therapy_approaches TEXT DEFAULT NULL");
    echo "Added columns successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
