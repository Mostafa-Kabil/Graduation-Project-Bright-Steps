<?php
include 'connection.php';
$connect->query("UPDATE users SET status = 'active' WHERE user_id = 9");
echo "Unsuspended\n";
