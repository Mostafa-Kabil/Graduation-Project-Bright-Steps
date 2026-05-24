<?php
session_start();
$_SESSION['id'] = 5;
$_SESSION['role'] = 'parent';

$post = [
    'child_id' => 1,
    'weight' => 15.5,
    'height' => 95.0,
    'head_circumference' => 50.0
];

$ch = curl_init('http://localhost/Bright%20Steps%20Website/api_add_growth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

// To pass session cookie, we can just execute the script using php-cgi or just include it?
// Actually we can't easily fake the session via curl without getting the cookie first.
// Let's just create a test_growth.php that includes the api_add_growth.php after faking session.

file_put_contents('test_growth.php', '<?php
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
echo "OUTPUT:\\n";
var_dump($output);
?>');

echo "Script created. Run it.";
