<?php
require_once "connection.php";

echo "<h2>Bright Steps — Force Column Fix</h2>";

try {
    // Check what columns we actually have
    $stmt = $connect->query("DESCRIBE `appointment` ");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns in 'appointment': " . implode(", ", $columns) . "<br>";

    if (!in_array('child_id', $columns)) {
        echo "child_id is definitely missing. FORCING add...<br>";
        $connect->exec("ALTER TABLE `appointment` ADD `child_id` INT(11) NULL DEFAULT NULL AFTER `parent_id` ");
        echo "<b style='color:green;'>SUCCESS: Column child_id added.</b><br>";
    } else {
        echo "<b style='color:blue;'>Column child_id already exists. No action needed.</b><br>";
    }

    // Refresh columns list
    $stmt = $connect->query("DESCRIBE `appointment` ");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Updated columns: " . implode(", ", $columns) . "<br>";

} catch (Exception $e) {
    echo "<b style='color:red;'>FAILED: " . $e->getMessage() . "</b>";
}
