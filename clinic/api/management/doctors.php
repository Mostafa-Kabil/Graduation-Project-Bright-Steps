<?php
/**
 * Bright Steps Clinic API — Doctor Management (Clinic Admin)
 * 
 * Endpoints:
 *   GET    ?action=list     — List all doctors in a clinic
 *   POST   ?action=add      — Add a new doctor to the clinic
 *   PUT    ?action=update   — Update doctor information
 *   DELETE ?action=remove   — Remove a doctor from the clinic
 *   GET    ?action=details  — Get doctor profile details
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── List Doctors ──────────────────────────────────────
if ($action === 'list' && $method === 'GET') {
    $clinicId = get_int('clinic_id');

    $db = get_db();

    $sql = "
        SELECT s.specialist_id, u.first_name, u.last_name, u.email, u.status,
               s.specialization, s.experience_years, s.certificate_of_experience,
               c.clinic_id, c.clinic_name,
               (SELECT ROUND(AVG(f.rating), 1) FROM feedback f WHERE f.specialist_id = s.specialist_id) AS avg_rating,
               (SELECT COUNT(*) FROM feedback f WHERE f.specialist_id = s.specialist_id) AS review_count,
               (SELECT COUNT(*) FROM appointment a WHERE a.specialist_id = s.specialist_id AND a.status IN ('scheduled','confirmed') AND a.scheduled_at >= NOW()) AS upcoming_appointments
        FROM specialist s
        INNER JOIN users u ON s.specialist_id = u.user_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE 1=1
    ";
    $params = [];

    if ($clinicId) {
        $sql .= " AND s.clinic_id = :clinic_id";
        $params[':clinic_id'] = $clinicId;
    }

    $sql .= " ORDER BY u.first_name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll();

    json_success([
        'count'   => count($doctors),
        'doctors' => $doctors
    ]);
}

// ── Add Doctor ────────────────────────────────────────
elseif ($action === 'add' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, ['admin', 'clinic']);

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['first_name', 'last_name', 'email', 'password', 'specialization', 'clinic_id']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    if (!validate_email($input['email'])) {
        json_error('Invalid email address');
    }

    $db = get_db();

    // Check email uniqueness
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute([':email' => $input['email']]);
    if ($stmt->fetch()) {
        json_error('Email already registered', 409);
    }

    // Verify clinic exists
    $stmt = $db->prepare("SELECT clinic_id FROM clinic WHERE clinic_id = :cid");
    $stmt->execute([':cid' => $input['clinic_id']]);
    if (!$stmt->fetch()) {
        json_error('Clinic not found', 404);
    }

    try {
        $db->beginTransaction();

        // Create user account
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, status)
            VALUES (:first_name, :last_name, :email, :password, 'specialist', 'active')
        ");
        $stmt->execute([
            ':first_name' => $input['first_name'],
            ':last_name'  => $input['last_name'],
            ':email'      => $input['email'],
            ':password'   => $hashedPassword
        ]);
        $userId = $db->lastInsertId();

        // Create specialist record
        $stmt = $db->prepare("
            INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years, certificate_of_experience)
            VALUES (:sid, :clinic_id, :first_name, :last_name, :specialization, :experience_years, :certificate)
        ");
        $stmt->execute([
            ':sid'              => $userId,
            ':clinic_id'        => intval($input['clinic_id']),
            ':first_name'       => $input['first_name'],
            ':last_name'        => $input['last_name'],
            ':specialization'   => $input['specialization'],
            ':experience_years' => intval($input['experience_years'] ?? 0),
            ':certificate'      => $input['certificate_of_experience'] ?? null
        ]);

        $db->commit();

        json_success([
            'doctor_id' => (int)$userId
        ], 'Doctor added successfully', 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to add doctor', 500);
    }
}

// ── Update Doctor ─────────────────────────────────────
elseif ($action === 'update' && $method === 'PUT') {
    $authUser = require_auth();
    require_role($authUser, ['admin', 'clinic']);

    $input = get_json_input();
    $doctorId = intval($input['doctor_id'] ?? 0);

    if (!$doctorId) {
        json_error('doctor_id is required');
    }

    $db = get_db();

    // Verify doctor exists
    $stmt = $db->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $doctorId]);
    if (!$stmt->fetch()) {
        json_error('Doctor not found', 404);
    }

    // Update users table fields
    $userFields = [];
    $userParams = [':uid' => $doctorId];
    foreach (['first_name', 'last_name', 'email', 'status'] as $field) {
        if (isset($input[$field])) {
            $userFields[] = "$field = :$field";
            $userParams[":$field"] = sanitize_input($input[$field]);
        }
    }

    if (!empty($userFields)) {
        $sql = "UPDATE users SET " . implode(', ', $userFields) . " WHERE user_id = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute($userParams);
    }

    // Update specialist table fields
    $specFields = [];
    $specParams = [':sid' => $doctorId];
    foreach (['specialization', 'experience_years', 'certificate_of_experience'] as $field) {
        if (isset($input[$field])) {
            $specFields[] = "$field = :$field";
            $specParams[":$field"] = sanitize_input($input[$field]);
        }
    }

    // Also sync first_name and last_name to specialist table
    foreach (['first_name', 'last_name'] as $field) {
        if (isset($input[$field])) {
            $specFields[] = "$field = :s_$field";
            $specParams[":s_$field"] = sanitize_input($input[$field]);
        }
    }

    if (!empty($specFields)) {
        $sql = "UPDATE specialist SET " . implode(', ', $specFields) . " WHERE specialist_id = :sid";
        $stmt = $db->prepare($sql);
        $stmt->execute($specParams);
    }

    if (empty($userFields) && empty($specFields)) {
        json_error('No fields to update');
    }

    json_success([], 'Doctor updated successfully');
}

// ── Remove Doctor ─────────────────────────────────────
elseif ($action === 'remove' && $method === 'DELETE') {
    $authUser = require_auth();
    require_role($authUser, ['admin', 'clinic']);

    $doctorId = get_int('doctor_id');
    if (!$doctorId) {
        json_error('doctor_id parameter is required');
    }

    $db = get_db();

    // Verify doctor exists
    $stmt = $db->prepare("SELECT specialist_id FROM specialist WHERE specialist_id = :sid");
    $stmt->execute([':sid' => $doctorId]);
    if (!$stmt->fetch()) {
        json_error('Doctor not found', 404);
    }

    // Soft-delete: deactivate instead of hard delete
    $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE user_id = :uid");
    $stmt->execute([':uid' => $doctorId]);

    json_success([], 'Doctor deactivated successfully');
}

// ── Doctor Details ────────────────────────────────────
elseif ($action === 'details' && $method === 'GET') {
    $doctorId = get_int('doctor_id');
    if (!$doctorId) {
        json_error('doctor_id parameter is required');
    }

    $db = get_db();

    $stmt = $db->prepare("
        SELECT s.specialist_id, u.first_name, u.last_name, u.email, u.status, u.created_at,
               s.specialization, s.experience_years, s.certificate_of_experience,
               c.clinic_id, c.clinic_name, c.location AS clinic_location,
               (SELECT ROUND(AVG(f.rating), 1) FROM feedback f WHERE f.specialist_id = s.specialist_id) AS avg_rating,
               (SELECT COUNT(*) FROM feedback f WHERE f.specialist_id = s.specialist_id) AS review_count
        FROM specialist s
        INNER JOIN users u ON s.specialist_id = u.user_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE s.specialist_id = :sid
    ");
    $stmt->execute([':sid' => $doctorId]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        json_error('Doctor not found', 404);
    }

    // Get phone numbers
    $stmt2 = $db->prepare("SELECT phone FROM clinic_phone WHERE clinic_id = :cid");
    $stmt2->execute([':cid' => $doctor['clinic_id']]);
    $doctor['clinic_phones'] = array_column($stmt2->fetchAll(), 'phone');

    json_success(['doctor' => $doctor]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'GET    ?action=list'                => 'List doctors (?clinic_id=N)',
            'POST   ?action=add'                 => 'Add doctor (first_name, last_name, email, password, specialization, clinic_id)',
            'PUT    ?action=update'              => 'Update doctor (doctor_id, ...fields)',
            'DELETE ?action=remove&doctor_id=N'  => 'Remove/deactivate doctor',
            'GET    ?action=details&doctor_id=N' => 'Get doctor details'
        ]
    ], 'Bright Steps Clinic — Doctor Management API');
}
