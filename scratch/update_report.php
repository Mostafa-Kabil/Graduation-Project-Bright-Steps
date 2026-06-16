<?php
require 'connection.php';
$connect->query("UPDATE shared_reports SET doctor_reply = 'This is a test reply' WHERE report_id = 4");
echo "Updated report 4";
