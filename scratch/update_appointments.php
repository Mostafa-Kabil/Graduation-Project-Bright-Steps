<?php
require 'connection.php';
$stmt = $connect->query("UPDATE appointment SET status = 'pending' WHERE LOWER(status) = 'scheduled'");
echo "Updated " . $stmt->rowCount() . " rows.";
