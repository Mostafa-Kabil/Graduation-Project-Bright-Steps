<?php
session_start();
$_SESSION['id'] = 71;
$_SESSION['clinic_id'] = 129;

// Mock the input
$input = [
    'action' => 'save_slots',
    'days' => [1,2,3,4,5],
    'start_time' => '09:00',
    'end_time' => '17:00',
    'slot_duration' => 30,
    'consultation_types' => ['online'],
    'focus_areas' => ['autism']
];

// Replicate the logic in doctor-dashboard.php exactly
require 'connection.php';

try {
    $doctor_id = intval($_SESSION['id']);
    $clinic_id = intval($_SESSION['clinic_id'] ?? 1);
    $days     = $input['days'] ?? [];
    $start    = trim($input['start_time'] ?? '09:00');
    $end      = trim($input['end_time'] ?? '17:00');
    $duration = intval($input['slot_duration'] ?? 30);
    $consultation_types = isset($input['consultation_types']) ? json_encode($input['consultation_types']) : '[]';
    $focus_areas = isset($input['focus_areas']) ? json_encode($input['focus_areas']) : '[]';

    $connect->prepare("UPDATE doctor_onboarding SET consultation_types = :ct, focus_areas = :fa, working_days = :wd, start_time = :st, end_time = :et WHERE doctor_id = :did")
            ->execute([':ct' => $consultation_types, ':fa' => $focus_areas, ':wd' => json_encode($days), ':st' => $start, ':et' => $end, ':did' => $doctor_id]);

    $connect->prepare("UPDATE appointment_slots SET is_active = 0 WHERE doctor_id = :did")
            ->execute([':did' => $doctor_id]);
    if (!empty($days)) {
        $stmt = $connect->prepare("
            INSERT INTO appointment_slots (doctor_id, clinic_id, day_of_week, start_time, end_time, slot_duration, is_active)
            VALUES (:did, :cid, :dow, :start, :end, :dur, 1)
            ON DUPLICATE KEY UPDATE start_time = :start2, end_time = :end2, slot_duration = :dur2, is_active = 1
        ");
        foreach ($days as $dow) {
            $dow = intval($dow);
            if ($dow < 0 || $dow > 6) continue;
            $stmt->execute([':did'=>$doctor_id,':cid'=>$clinic_id,':dow'=>$dow,':start'=>$start,':end'=>$end,':dur'=>$duration,':start2'=>$start,':end2'=>$end,':dur2'=>$duration]);
        }
    }
    echo json_encode(['success' => true, 'message' => 'Availability saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
