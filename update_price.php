<?php
require 'connection.php';
try {
    $stmt = $connect->query("UPDATE subscription SET price = 250 WHERE plan_name LIKE '%Premium%'");
    echo "Premium plan updated successfully.";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
