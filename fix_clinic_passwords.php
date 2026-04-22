<?php
/**
 * Fix clinic passwords — hashes all plain-text passwords in the clinic table
 * and ensures at least one working test account exists.
 * 
 * Run once: http://localhost/Bright%20Steps%20Website/fix_clinic_passwords.php
 */
include 'connection.php';

$defaultPassword = 'clinic1234';
$hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

echo "<h2>Fixing Clinic Passwords</h2>";

// Update ALL existing clinics whose passwords are NOT already bcrypt hashed
$stmt = $connect->query("SELECT clinic_id, clinic_name, email, password, status FROM clinic");
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
foreach ($clinics as $clinic) {
    // bcrypt hashes always start with $2y$ — if it doesn't, it's plain text
    if (strpos($clinic['password'], '$2y$') !== 0) {
        $newHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $update = $connect->prepare("UPDATE clinic SET password = :pass WHERE clinic_id = :id");
        $update->execute(['pass' => $newHash, 'id' => $clinic['clinic_id']]);
        echo "<p>✅ Fixed: <strong>{$clinic['clinic_name']}</strong> ({$clinic['email']}) — status: {$clinic['status']}</p>";
        $fixed++;
    } else {
        echo "<p>⏭️ Already hashed: <strong>{$clinic['clinic_name']}</strong> ({$clinic['email']}) — status: {$clinic['status']}</p>";
    }
}

// Also make sure at least one clinic is 'active' (not pending/suspended)
$activeCheck = $connect->query("SELECT COUNT(*) FROM clinic WHERE status = 'active'")->fetchColumn();
if ($activeCheck == 0) {
    // Activate the first clinic
    $connect->query("UPDATE clinic SET status = 'active' ORDER BY clinic_id LIMIT 1");
    echo "<p>⚠️ No active clinics found — activated the first one.</p>";
}

echo "<hr>";
echo "<h3>Results</h3>";
echo "<p>Fixed {$fixed} clinic password(s). All passwords are now set to: <code>{$defaultPassword}</code></p>";
echo "<h3>Working Login Credentials</h3>";

$stmt = $connect->query("SELECT clinic_name, email, status FROM clinic WHERE status = 'active' LIMIT 5");
$active = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($active) {
    echo "<table border='1' cellpadding='8'><tr><th>Clinic</th><th>Email</th><th>Password</th><th>Status</th></tr>";
    foreach ($active as $c) {
        echo "<tr><td>{$c['clinic_name']}</td><td><strong>{$c['email']}</strong></td><td><code>{$defaultPassword}</code></td><td>{$c['status']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No active clinics found.</p>";
}
?>
