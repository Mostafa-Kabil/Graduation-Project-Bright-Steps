<?php
include 'connection.php';

$email = 'mallak@gmail.com';
$password = password_hash('passbrightsteps', PASSWORD_DEFAULT);

$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Creating mallak user...\n";
    $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES ('Mallak', 'Clinic', ?, ?, 'clinic', 'active')")->execute([$email, $password]);
    $user_id = $connect->lastInsertId();
} else {
    echo "Updating mallak user password and role...\n";
    $user_id = $user['user_id'];
    $connect->prepare("UPDATE users SET password = ?, role = 'clinic', status = 'active' WHERE user_id = ?")->execute([$password, $user_id]);
}

// Check clinic table
$stmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE email = ?");
$stmt->execute([$email]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    echo "Creating clinic entry...\n";
    $connect->prepare("INSERT INTO clinic (clinic_id, name, clinic_name, email, phone, location, status) VALUES (?, 'Mallak Clinic', 'Mallak Clinic', ?, '1234567890', 'Cairo, Egypt', 'verified')")->execute([$user_id, $email]);
    $clinic_id = $user_id;
} else {
    echo "Updating clinic status...\n";
    $clinic_id = $clinic['clinic_id'];
    $connect->prepare("UPDATE clinic SET status = 'verified' WHERE email = ?")->execute([$email]);
}

// If Salsabel exists, assign her to Mallak clinic
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$stmt->execute();
$salsabel = $stmt->fetch(PDO::FETCH_ASSOC);
if ($salsabel) {
    $salsabel_id = $salsabel['user_id'];
    $connect->query("UPDATE specialist SET clinic_id = $clinic_id WHERE specialist_id = $salsabel_id");
    echo "Assigned Salsabel to Mallak Clinic.\n";
}

echo "Done. You can now login with $email / passbrightsteps\n";
