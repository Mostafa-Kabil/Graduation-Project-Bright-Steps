<?php
$sql = file_get_contents('migration_points_redemption_system.sql');
$pdo = new PDO('mysql:host=localhost;dbname=grad', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// The SQL script has DELIMITER commands which PDO doesn't support natively!
// So we need to split it, OR better yet, we can use the mysql CLI properly in PowerShell by using:
// cmd.exe /c "c:\xampp\mysql\bin\mysql.exe -u root grad < migration_points_redemption_system.sql"
// Wait, the previous error was: Unknown column 'ppt.points_earned' in 'field list'
// The problem is that the migration script ITSELF has an error!
