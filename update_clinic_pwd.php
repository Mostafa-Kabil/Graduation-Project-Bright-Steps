<?php
include 'connection.php';

$email = 'mallak@gmail.com';
$password = password_hash('passbrightsteps', PASSWORD_DEFAULT);

$stmt = $connect->prepare("UPDATE clinic SET password = ? WHERE email = ?");
$stmt->execute([$password, $email]);

echo "Clinic password updated.\n";
