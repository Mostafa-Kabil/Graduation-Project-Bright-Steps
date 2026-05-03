<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'connection.php';

$input = [
    'action' => 'submit_report',
    'specialist_id' => 5100,
    'doctor_report_id' => 0,
    'child_id' => 5100,
    'child_report' => '',
    'doctor_notes' => 'Test Notes',
    'recommendations' => 'Test Recs',
    'report_date' => '2026-05-01',
    'shared_report_id' => 0
];

$specialist_id = intval($input['specialist_id'] ?? 0);
$child_id = intval($input['child_id'] ?? 0);
$child_report = trim($input['child_report'] ?? '');
$doctor_notes = trim($input['doctor_notes'] ?? '');
$recommendations = trim($input['recommendations'] ?? '');
$report_date = trim($input['report_date'] ?? date('Y-m-d'));
$shared_report_id = intval($input['shared_report_id'] ?? 0);
$doctor_report_id = intval($input['doctor_report_id'] ?? 0);

if (!$specialist_id || !$child_id || !$doctor_notes) {
    echo json_encode(['success' => false, 'error' => 'specialist_id, child_id, and doctor_notes are required']);
    exit;
}

try {
    if ($doctor_report_id > 0) {
        // Update existing report
        $stmt = $connect->prepare("
            UPDATE doctor_report 
            SET doctor_notes = :notes, recommendations = :rec, report_date = :rdate 
            WHERE report_id = :rid AND specialist_id = :sid
        ");
        $stmt->execute([
            ':notes' => $doctor_notes,
            ':rec' => $recommendations,
            ':rdate' => $report_date,
            ':rid' => $doctor_report_id,
            ':sid' => $specialist_id
        ]);
        echo "Updated!\n";
    } else {
        // Insert new report
        $stmt = $connect->prepare("
            INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date)
            VALUES (:sid, :cid, :cr, :notes, :rec, :rdate)
        ");
        $stmt->execute([
            ':sid' => $specialist_id,
            ':cid' => $child_id,
            ':cr' => $child_report,
            ':notes' => $doctor_notes,
            ':rec' => $recommendations,
            ':rdate' => $report_date
        ]);
        $doctor_report_id = $connect->lastInsertId();
        echo "Inserted! ID: $doctor_report_id\n";
    }
} catch (Exception $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
