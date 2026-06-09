<?php
include 'connection.php';
$connect->exec("UPDATE subscription SET price = 250.00, plan_period = 'monthly' WHERE LOWER(plan_name) = 'premium'");
echo "Done. Premium plan updated to 250 EGP/month.";
<<<<<<< HEAD
?>
=======
>>>>>>> c6c63367441a1a3bc16b41c1969603b936ca730e
