<?php
// Redirect to the new location of the clinic dashboard
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: dashboards/clinic/clinic-dashboard.php" . $query_string);
exit();
?>
