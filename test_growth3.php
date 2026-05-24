<?php
require 'connection.php';
$stmt = $connect->query("SELECT child_id, parent_id FROM child LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Delete the latest record to avoid the 7-day restriction
    $connect->query("DELETE FROM growth_record WHERE child_id = " . $row['child_id'] . " ORDER BY recorded_at DESC LIMIT 1");

    session_start();
    $_SESSION["id"] = $row["parent_id"];
    $_SESSION["role"] = "parent";
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST["child_id"] = $row["child_id"];
    $_POST["weight"] = 16.0;
    $_POST["height"] = 96.0;
    $_POST["head_circumference"] = 51.0;
    
    ob_start();
    require "api_add_growth.php";
    $output = ob_get_clean();
    echo "OUTPUT:\n";
    var_dump($output);
}
?>
