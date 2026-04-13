<?php
/**
 * Bright Steps Clinic API — Children Management (Parent)
 * 
 * Endpoints:
 *   POST ?action=add       — Add a child profile
 *   GET  ?action=list      — List parent's children
 *   GET  ?action=details   — Get child details (child_id required)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Add Child ─────────────────────────────────────────
if ($action === 'add' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['first_name', 'last_name', 'ssn', 'gender', 'birth_day', 'birth_month', 'birth_year']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    // Validate gender
    if (!in_array(strtolower($input['gender']), ['male', 'female'])) {
        json_error("Gender must be 'male' or 'female'");
    }

    $db = get_db();

    // Check SSN uniqueness
    $stmt = $db->prepare("SELECT child_id FROM child WHERE ssn = :ssn");
    $stmt->execute([':ssn' => $input['ssn']]);
    if ($stmt->fetch()) {
        json_error('A child with this SSN already exists', 409);
    }

    $stmt = $db->prepare("
        INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender, birth_certificate)
        VALUES (:ssn, :parent_id, :first_name, :last_name, :birth_day, :birth_month, :birth_year, :gender, :birth_certificate)
    ");
    $stmt->execute([
        ':ssn'               => $input['ssn'],
        ':parent_id'         => $authUser['user_id'],
        ':first_name'        => $input['first_name'],
        ':last_name'         => $input['last_name'],
        ':birth_day'         => intval($input['birth_day']),
        ':birth_month'       => intval($input['birth_month']),
        ':birth_year'        => intval($input['birth_year']),
        ':gender'            => strtolower($input['gender']),
        ':birth_certificate' => $input['birth_certificate'] ?? null
    ]);

    json_success([
        'child_id' => (int)$db->lastInsertId()
    ], 'Child profile added successfully', 201);
}

// ── List Children ─────────────────────────────────────
elseif ($action === 'list' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $db = get_db();

    $stmt = $db->prepare("
        SELECT child_id, ssn, first_name, last_name, gender, birth_day, birth_month, birth_year, birth_certificate
        FROM child
        WHERE parent_id = :parent_id
        ORDER BY child_id DESC
    ");
    $stmt->execute([':parent_id' => $authUser['user_id']]);
    $children = $stmt->fetchAll();

    json_success([
        'count'    => count($children),
        'children' => $children
    ]);
}

// ── Child Details ─────────────────────────────────────
elseif ($action === 'details' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $childId = get_int('child_id');
    if (!$childId) {
        json_error('child_id parameter is required');
    }

    $db = get_db();

    // Get child info (verify ownership)
    $stmt = $db->prepare("
        SELECT child_id, ssn, first_name, last_name, gender, birth_day, birth_month, birth_year, birth_certificate
        FROM child
        WHERE child_id = :child_id AND parent_id = :parent_id
    ");
    $stmt->execute([':child_id' => $childId, ':parent_id' => $authUser['user_id']]);
    $child = $stmt->fetch();

    if (!$child) {
        json_error('Child not found or access denied', 404);
    }

    // Get growth records
    $stmt2 = $db->prepare("
        SELECT record_id, height, weight, head_circumference, recorded_at
        FROM growth_record
        WHERE child_id = :child_id
        ORDER BY recorded_at DESC LIMIT 10
    ");
    $stmt2->execute([':child_id' => $childId]);
    $growthRecords = $stmt2->fetchAll();

    // Get medical records
    $stmt3 = $db->prepare("
        SELECT mr.record_id, mr.diagnosis, mr.symptoms, mr.notes, mr.follow_up_date, mr.created_at,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization
        FROM medical_records mr
        INNER JOIN specialist s ON mr.doctor_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        WHERE mr.child_id = :child_id
        ORDER BY mr.created_at DESC
    ");
    $stmt3->execute([':child_id' => $childId]);
    $medicalRecords = $stmt3->fetchAll();

    // Get recent appointments
    $stmt4 = $db->prepare("
        SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization, c.clinic_name
        FROM appointment a
        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        INNER JOIN clinic c ON s.clinic_id = c.clinic_id
        WHERE a.parent_id = :parent_id AND (a.child_id = :child_id OR a.child_id IS NULL)
        ORDER BY a.scheduled_at DESC LIMIT 20
    ");
    $stmt4->execute([':parent_id' => $authUser['user_id'], ':child_id' => $childId]);
    $appointments = $stmt4->fetchAll();

    json_success([
        'child'           => $child,
        'growth_records'  => $growthRecords,
        'medical_records' => $medicalRecords,
        'appointments'    => $appointments
    ]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST ?action=add'                 => 'Add a child profile',
            'GET  ?action=list'                => 'List parent\'s children',
            'GET  ?action=details&child_id=N'  => 'Get child full details'
        ]
    ], 'Bright Steps Clinic — Children API');
}
