<?php
require 'connection.php';
try {
    $connect->exec("ALTER TABLE clinic 
        ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL AFTER location, 
        ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) DEFAULT NULL AFTER bio, 
        ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER cover_image, 
        ADD COLUMN IF NOT EXISTS opening_hours VARCHAR(255) DEFAULT NULL AFTER profile_image, 
        ADD COLUMN IF NOT EXISTS specialties TEXT DEFAULT NULL AFTER opening_hours, 
        ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL AFTER specialties");
    echo "Migration OK\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
