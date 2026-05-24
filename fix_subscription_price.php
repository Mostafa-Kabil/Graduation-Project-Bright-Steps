<?php
include 'connection.php';
$connect->exec("UPDATE subscription SET price = 250.00, plan_period = 'monthly' WHERE LOWER(plan_name) = 'premium'");
echo "Done. Premium plan updated to 250 EGP/month.";
