<?php
/**
 * Debug script: Tests every AJAX endpoint the doctor dashboard uses.
 * Run this in the browser to see what works and what's broken.
 */
session_start();
require 'connection.php';
header('Content-Type: text/html; charset=utf-8');

echo "<pre style='font-family:monospace;font-size:14px;background:#1a1a2e;color:#e0e0e0;padding:2rem;'>";
echo "═══════════════════════════════════════════\n";
echo "  DOCTOR DASHBOARD — FULL DIAGNOSTIC\n";
echo "═══════════════════════════════════════════\n\n";

// 1. Session check
echo "── SESSION ──\n";
$id = intval($_SESSION['id'] ?? 0);
$sid = intval($_SESSION['specialist_id'] ?? $_SESSION['id'] ?? 0);
echo "  Session ID: $id\n";
echo "  Specialist ID: $sid\n";
echo "  Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "  Name: " . ($_SESSION['fname'] ?? '?') . " " . ($_SESSION['lname'] ?? '?') . "\n\n";

// Find ANY doctor if no session
if (!$sid) {
    echo "  ⚠ No session! Looking for doctor accounts...\n";
    try {
        $rows = $connect->query("SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, s.specialist_id 
            FROM users u LEFT JOIN specialist s ON u.user_id = s.specialist_id 
            WHERE u.role IN ('doctor','specialist') LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $r) echo "    Doctor: {$r['first_name']} {$r['last_name']} (ID:{$r['user_id']}, email:{$r['email']}, role:{$r['role']}, spec_id:{$r['specialist_id']})\n";
            $sid = intval($rows[0]['user_id']);
            echo "  → Using ID: $sid for testing\n";
        } else {
            echo "  ❌ NO DOCTOR USERS FOUND IN DATABASE!\n";
            echo "  Run: INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Test','Doctor','test@doc.com','".password_hash('12345678',PASSWORD_DEFAULT)."','doctor');\n";
        }
    } catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }
    echo "\n";
}

// 2. Table existence check
echo "── TABLES ──\n";
$tables = ['users','specialist','parent','child','appointment','payment','message','doctor_report',
           'child_generated_system_report','growth_record','milestones','child_milestones',
           'feedback','notifications','appointment_slots','doctor_onboarding'];
foreach ($tables as $t) {
    try {
        $count = $connect->query("SELECT COUNT(*) n FROM `$t`")->fetch()['n'];
        $icon = $count > 0 ? '✓' : '⚠';
        echo "  $icon $t: $count rows\n";
    } catch (Exception $e) {
        echo "  ❌ $t: TABLE MISSING! (" . $e->getMessage() . ")\n";
    }
}
echo "\n";

if (!$sid) { echo "Cannot test endpoints without a doctor ID.\n</pre>"; exit; }

// 3. Test each query the dashboard actually runs
echo "── ENDPOINT TESTS (specialist_id=$sid) ──\n\n";

// PATIENTS
echo "  [PATIENTS] get_patients:\n";
try {
    $stmt = $connect->prepare("
        SELECT DISTINCT c.child_id, c.first_name AS child_first_name, c.last_name AS child_last_name,
               c.gender, c.birth_year, c.birth_month, c.birth_day,
               u.first_name AS parent_first_name, u.last_name AS parent_last_name, p.parent_id
        FROM appointment a
        JOIN parent p ON p.parent_id = a.parent_id
        JOIN users u ON u.user_id = p.parent_id
        JOIN child c ON c.parent_id = p.parent_id
        WHERE a.specialist_id = :sid GROUP BY c.child_id ORDER BY c.child_id
    ");
    $stmt->execute([':sid' => $sid]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "    ✓ Found " . count($patients) . " patients\n";
    foreach (array_slice($patients, 0, 3) as $p) echo "      - {$p['child_first_name']} {$p['child_last_name']} (gender:{$p['gender']})\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// APPOINTMENTS
echo "\n  [APPOINTMENTS] get_appointments:\n";
try {
    $stmt = $connect->prepare("SELECT COUNT(*) n FROM appointment WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $sid]);
    echo "    ✓ " . $stmt->fetch()['n'] . " appointments\n";
    $stmt2 = $connect->prepare("SELECT status, COUNT(*) c FROM appointment WHERE specialist_id = :sid GROUP BY status");
    $stmt2->execute([':sid' => $sid]);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) echo "      - {$r['status']}: {$r['c']}\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// MESSAGES
echo "\n  [MESSAGES] get_conversations:\n";
try {
    $stmt = $connect->prepare("
        SELECT COUNT(DISTINCT CASE WHEN sender_id = :s1 THEN receiver_id ELSE sender_id END) AS conv_count,
               COUNT(*) AS total_msgs
        FROM message WHERE sender_id = :s2 OR receiver_id = :s3
    ");
    $stmt->execute([':s1' => $sid, ':s2' => $sid, ':s3' => $sid]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "    ✓ {$r['conv_count']} conversations, {$r['total_msgs']} total messages\n";
    
    // Show last 3 messages
    $stmt2 = $connect->prepare("SELECT m.*, u.first_name AS sender_name FROM message m JOIN users u ON m.sender_id = u.user_id WHERE m.sender_id = :s OR m.receiver_id = :r ORDER BY m.sent_at DESC LIMIT 3");
    $stmt2->execute([':s' => $sid, ':r' => $sid]);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $dir = $m['sender_id'] == $sid ? '→' : '←';
        echo "      $dir [{$m['sender_name']}]: " . substr($m['content'], 0, 60) . "...\n";
    }
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// REPORTS - Doctor's own
echo "\n  [REPORTS] get_doctor_reports:\n";
try {
    $stmt = $connect->prepare("SELECT COUNT(*) n FROM doctor_report WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $sid]);
    echo "    ✓ " . $stmt->fetch()['n'] . " doctor reports\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// REPORTS - Shared child reports
echo "\n  [REPORTS] get_shared_reports:\n";
try {
    $stmt = $connect->prepare("
        SELECT COUNT(*) n FROM child_generated_system_report csr
        JOIN child c ON csr.child_id = c.child_id
        JOIN parent p ON c.parent_id = p.parent_id
        JOIN appointment a ON a.parent_id = p.parent_id AND a.specialist_id = :sid
    ");
    $stmt->execute([':sid' => $sid]);
    echo "    ✓ " . $stmt->fetch()['n'] . " shared reports visible\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// PATIENT DETAIL - test report_id column
echo "\n  [PATIENT DETAIL] report_id column test:\n";
try {
    $stmt = $connect->prepare("SELECT doctor_report_id, doctor_notes, report_date FROM doctor_report WHERE specialist_id = :sid LIMIT 1");
    $stmt->execute([':sid' => $sid]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) echo "    ✓ Column is 'doctor_report_id' (value: {$r['doctor_report_id']})\n";
    else echo "    ⚠ No reports to test\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// Test the BROKEN query from doctor-dashboard.php line 393
echo "\n  [BUG CHECK] Testing 'report_id' (wrong column name):\n";
try {
    $stmt = $connect->prepare("SELECT report_id FROM doctor_report LIMIT 1");
    $stmt->execute();
    echo "    ✓ 'report_id' column exists (unexpected)\n";
} catch (Exception $e) { echo "    ❌ BUG CONFIRMED: 'report_id' does NOT exist! Must use 'doctor_report_id'\n"; }

// GROWTH RECORDS
echo "\n  [PATIENT DETAIL] growth_record:\n";
try {
    $stmt = $connect->query("SELECT COUNT(*) n FROM growth_record WHERE child_id BETWEEN 5200 AND 5208");
    echo "    ✓ " . $stmt->fetch()['n'] . " growth records for seed children\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// MILESTONES  
echo "\n  [PATIENT DETAIL] milestones:\n";
try {
    $mc = $connect->query("SELECT COUNT(*) n FROM milestones")->fetch()['n'];
    echo "    ✓ $mc milestones defined\n";
    $cmc = $connect->query("SELECT COUNT(*) n FROM child_milestones WHERE child_id BETWEEN 5200 AND 5208")->fetch()['n'];
    echo "    ✓ $cmc child milestones for seed children\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// ANALYTICS
echo "\n  [ANALYTICS] get_analytics:\n";
try {
    $stmt = $connect->prepare("SELECT ROUND(AVG(rating),1) avg_r, COUNT(*) cnt FROM feedback WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $sid]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "    ✓ Feedback: {$r['cnt']} reviews, avg rating: {$r['avg_r']}\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

// SETTINGS
echo "\n  [SETTINGS] get_profile:\n";
try {
    $stmt = $connect->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email,
               s.specialization, s.experience_years, s.certificate_of_experience, s.clinic_id
        FROM users u LEFT JOIN specialist s ON u.user_id = s.specialist_id WHERE u.user_id = :uid
    ");
    $stmt->execute([':uid' => $sid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p) echo "    ✓ {$p['first_name']} {$p['last_name']} ({$p['email']}), spec: {$p['specialization']}, exp: {$p['experience_years']}y\n";
    else echo "    ❌ No profile found for user $sid\n";
} catch (Exception $e) { echo "    ❌ " . $e->getMessage() . "\n"; }

echo "\n═══════════════════════════════════════════\n";
echo "  DIAGNOSTIC COMPLETE\n";
echo "═══════════════════════════════════════════\n";
echo "</pre>";
