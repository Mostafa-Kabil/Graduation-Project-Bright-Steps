<?php
require 'connection.php';
$connect->query("UPDATE shared_reports SET file_path = NULL");
echo "Done.";
