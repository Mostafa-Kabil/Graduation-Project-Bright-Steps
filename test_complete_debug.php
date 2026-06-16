<?php
// Debug wrapper to capture the input to action=complete
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
file_put_contents('test_complete_debug.log', date('Y-m-d H:i:s') . " - INPUT: " . print_r($input, true) . "\n", FILE_APPEND);
