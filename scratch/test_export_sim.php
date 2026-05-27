<?php
// Simulate doctor session and test api_export_pdf.php
session_start();
$_SESSION['id'] = 4;
$_SESSION['role'] = 'doctor';
$_SESSION['specialist_id'] = 4;
$_SESSION['fname'] = 'mariam';
$_SESSION['lname'] = 'ghareb';

// Simulate GET params
$_GET['type'] = 'growth-report';
$_GET['child_id'] = '5201';
$_GET['view'] = '1';

echo "=== Simulating api_export_pdf.php ===\n";
echo "Session ID: " . $_SESSION['id'] . "\n";
echo "Session Role: " . $_SESSION['role'] . "\n";
echo "Session specialist_id: " . $_SESSION['specialist_id'] . "\n";

include __DIR__ . '/../connection.php';

$userId = $_SESSION['id'];
$userRole = $_SESSION['role'] ?? 'parent';
$type = $_GET['type'] ?? 'full-report';
$childId = $_GET['child_id'] ?? null;

echo "userRole=$userRole, type=$type, childId=$childId\n";

$specialistId = $_SESSION['specialist_id'] ?? $_SESSION['id'] ?? null;
echo "specialistId=$specialistId\n";

$stmt = $connect->prepare("
    SELECT c.* 
    FROM child c
    JOIN shared_reports sr ON c.child_id = sr.child_id
    WHERE c.child_id = ? AND sr.doctor_id = ? AND sr.is_shared = 1
    LIMIT 1
");
$stmt->execute([$childId, $specialistId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if ($child) {
    echo "SUCCESS: Found child: " . $child['first_name'] . " " . $child['last_name'] . "\n";
} else {
    echo "FAIL: Child not found or no access\n";
}
