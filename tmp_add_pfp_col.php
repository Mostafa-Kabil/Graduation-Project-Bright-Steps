<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE clinic ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
    echo "Column 'profile_image' added to 'clinic' table successfully.";
} catch (Exception $e) {
    echo "Error adding column: " . $e->getMessage();
}
?>
