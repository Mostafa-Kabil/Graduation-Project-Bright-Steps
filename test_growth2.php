<?php
require 'connection.php';
$stmt = $connect->query("SELECT child_id, parent_id FROM child LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    session_start();
    $_SESSION["id"] = $row["parent_id"];
    $_SESSION["role"] = "parent";
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST["child_id"] = $row["child_id"];
    $_POST["weight"] = 15.5;
    $_POST["height"] = 95.0;
    $_POST["head_circumference"] = 50.0;
    
    // Disable errors from outputting to stdout, log them instead to avoid breaking JSON
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
    
    ob_start();
    require "api_add_growth.php";
    $output = ob_get_clean();
    echo "OUTPUT:\\n";
    var_dump($output);
}
?>
