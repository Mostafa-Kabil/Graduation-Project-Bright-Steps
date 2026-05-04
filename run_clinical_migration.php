<?php
require 'connection.php';

$sql = file_get_contents('migration_clinical_features.sql');

try {
    $connect->exec($sql);
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
