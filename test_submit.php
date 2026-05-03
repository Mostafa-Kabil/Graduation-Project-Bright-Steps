<?php
include 'connection.php';
$specialist_id = 5100;
$child_id = 5100;
$doctor_notes = "test notes";
$recommendations = "test rec";
$report_date = "2026-05-01";
$shared_report_id = 1;
$doctor_report_id = 0;

try {
    $stmt = $connect->prepare("
        INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date)
        VALUES (:sid, :cid, :cr, :notes, :rec, :rdate)
    ");
    $stmt->execute([
        ':sid' => $specialist_id,
        ':cid' => $child_id,
        ':cr' => '',
        ':notes' => $doctor_notes,
        ':rec' => $recommendations,
        ':rdate' => $report_date
    ]);
    echo "Insert Success\n";

    if ($shared_report_id) {
        $stmtUpdate = $connect->prepare("
            UPDATE shared_reports 
            SET doctor_reply = :reply, doctor_reply_date = :rdate 
            WHERE report_id = :rid AND doctor_id = :sid
        ");
        $stmtUpdate->execute([
            ':reply' => "Report written", 
            ':rdate' => $report_date,
            ':rid' => $shared_report_id, 
            ':sid' => $specialist_id
        ]);
        echo "Update shared_reports Success\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
