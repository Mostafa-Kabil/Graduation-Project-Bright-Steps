<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    die(json_encode(["error" => "Unauthorized"]));
}

try {
    $user_id = $_SESSION['id'];

    // 1. Resolve actual clinic_id from the user's ID (Clinic Admin)
    $cStmt = $connect->prepare("SELECT clinic_id, clinic_name FROM clinic WHERE admin_id = ? LIMIT 1");
    $cStmt->execute([$user_id]);
    $clinic = $cStmt->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        // SELF-HEALING: Create default clinic profile if missing
        // This ensures the dashboard always has a container to show
        $default_name = ($_SESSION['fname'] ?? 'New') . "'s Healthcare Clinic";
        $insClinic = $connect->prepare("INSERT INTO clinic (clinic_name, admin_id, status) VALUES (?, ?, 'active')");
        $insClinic->execute([$default_name, $user_id]);
        $clinic_id = $connect->lastInsertId();
        $clinic_name = $default_name;
    } else {
        $clinic_id = $clinic['clinic_id'];
        $clinic_name = $clinic['clinic_name'];
    }

    // 2. Fetch Specialists for this clinic
    // Match schema: join with users to get email
    $specStmt = $connect->prepare("
        SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, s.experience_years, u.email
        FROM specialist s
        LEFT JOIN users u ON s.specialist_id = u.user_id
        WHERE s.clinic_id = ?
    ");
    $specStmt->execute([$clinic_id]);
    $specialists = $specStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Patients
    // Match schema: child table uses birth_day/month/year instead of date_of_birth
    $childStmt = $connect->query("
        SELECT c.child_id, c.first_name, c.last_name, u.first_name as parent_fname, u.last_name as parent_lname
        FROM child c
        JOIN users u ON c.parent_id = u.user_id
        LIMIT 100
    ");
    $patients = $childStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "clinic_name" => $clinic_name,
        "specialists" => $specialists,
        "patients" => $patients,
        "debug" => [
            "clinic_id" => $clinic_id,
            "user_id" => $user_id,
            "spec_count" => count($specialists),
            "patient_count" => count($patients)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
