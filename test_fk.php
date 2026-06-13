<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE appointment_slots DROP FOREIGN KEY as_clinic_fk");
    echo "Dropped FK successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
