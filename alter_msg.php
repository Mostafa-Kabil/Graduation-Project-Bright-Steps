<?php
require_once "connection.php";
try {
    $connect->query("ALTER TABLE message ADD COLUMN meeting_link VARCHAR(500) NULL AFTER appointment_id");
    echo "meeting_link added\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $connect->query("ALTER TABLE message ADD COLUMN message_type VARCHAR(50) DEFAULT 'text' AFTER file_path");
    echo "message_type added\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
echo "Done.";
