<?php
require 'connection.php';
$stmt = $connect->query('SELECT parent_id FROM parent LIMIT 1');
$pid = $stmt->fetchColumn();
$stmt = $connect->prepare('SELECT child_id FROM child WHERE parent_id=? LIMIT 1');
$stmt->execute([$pid]);
$cid = $stmt->fetchColumn();

file_put_contents('scratch_test4.php', "<?php
session_start();
\$_SESSION['id'] = $pid;
\$_SESSION['role'] = 'parent';
\$_GET['action'] = 'recommend';
\$_GET['child_id'] = $cid;
\$_GET['force'] = 1;
require 'api_activities.php';
");
echo "Created scratch_test4.php with pid=$pid, cid=$cid";
