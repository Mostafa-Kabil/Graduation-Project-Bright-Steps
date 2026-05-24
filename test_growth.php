<?php
session_start();
$_SESSION["id"] = 3; // Use a known parent ID, e.g. from parent table
$_SESSION["role"] = "parent";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST["child_id"] = 1; // Needs to belong to parent
$_POST["weight"] = 15.5;
$_POST["height"] = 95.0;
$_POST["head_circumference"] = 50.0;
ob_start();
require "api_add_growth.php";
$output = ob_get_clean();
echo "OUTPUT:\n";
var_dump($output);
?>