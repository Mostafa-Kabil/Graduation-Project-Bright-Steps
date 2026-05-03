<?php
/**
 * Bright Steps Clinic API — Medical Records (Doctor)
 * 
 * Endpoints:
 *   POST ?action=create         — Create a medical record for a child visit
 *   GET  ?action=child_history  — Get all medical records for a child
 *   PUT  ?action=update         — Update an existing medical record
 *   GET  ?action=details        — Get single record details
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Create Medical Record ─────────────────────────────
if ($action === 'create' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['child_id', 'diagnosis']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    $db = get_db();

    // Verify child exists
    $stmt = $db->prepare("SELECT child_id, parent_id FROM child WHERE child_id = :child_id");
    $stmt->execute([':child_id' => $input['child_id']]);
    $child = $stmt->fetch();
    if (!$child) {
        json_error('Child not found', 404);
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO medical_records (child_id, doctor_id, appointment_id, diagnosis, symptoms, notes, follow_up_date)
            VALUES (:child_id, :doctor_id, :appointment_id, :diagnosis, :symptoms, :notes, :follow_up_date)
        ");
        $stmt->execute([
            ':child_id'       => intval($input['child_id']),
            ':doctor_id'      => $authUser['user_id'],
            ':appointment_id' => $input['appointment_id'] ?? null,
            ':diagnosis'      => $input['diagnosis'],
            ':symptoms'       => $input['symptoms'] ?? null,
            ':notes'          => $input['notes'] ?? null,
            ':follow_up_date' => $input['follow_up_date'] ?? null
        ]);
        $recordId = $db->lastInsertId();

        // Notify parent
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (:user_id, 'New Medical Record', 'A new medical record has been added for your child.', 'medical_record')
        ");
        $stmt->execute([
            ':user_id' => $child['parent_id']
        ]);

        $db->commit();

        json_success([
            'record_id' => (int)$recordId
        ], 'Medical record created successfully', 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to create medical record', 500);
    }
}

// ── Child Medical History ─────────────────────────────
elseif ($action === 'child_history' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $childId = get_int('child_id');
    if (!$childId) {
        json_error('child_id parameter is required');
    }

    $db = get_db();

    // Get child info
    $stmt = $db->prepare("
        SELECT c.child_id, c.first_name, c.last_name, c.gender, c.birth_year, c.birth_month, c.birth_day,
               u.first_name AS parent_first_name, u.last_name AS parent_last_name
        FROM child c
        INNER JOIN users u ON c.parent_id = u.user_id
        WHERE c.child_id = :child_id
    ");
    $stmt->execute([':child_id' => $childId]);
    $child = $stmt->fetch();

    if (!$child) {
        json_error('Child not found', 404);
    }

    // Get medical records
    $stmt2 = $db->prepare("
        SELECT mr.record_id, mr.diagnosis, mr.symptoms, mr.notes, mr.follow_up_date, mr.created_at, mr.updated_at,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization
        FROM medical_records mr
        INNER JOIN specialist s ON mr.doctor_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        WHERE mr.child_id = :child_id
        ORDER BY mr.created_at DESC
    ");
    $stmt2->execute([':child_id' => $childId]);
    $records = $stmt2->fetchAll();

    // Get prescriptions
    $stmt3 = $db->prepare("
        SELECT p.prescription_id, p.record_id, p.medication_name, p.dosage, p.frequency,
               p.duration, p.instructions, p.created_at
        FROM prescriptions p
        WHERE p.child_id = :child_id
        ORDER BY p.created_at DESC
    ");
    $stmt3->execute([':child_id' => $childId]);
    $prescriptions = $stmt3->fetchAll();

    // Get growth records
    $stmt4 = $db->prepare("
        SELECT record_id, height, weight, head_circumference, recorded_at
        FROM growth_record
        WHERE child_id = :child_id
        ORDER BY recorded_at DESC LIMIT 10
    ");
    $stmt4->execute([':child_id' => $childId]);
    $growth = $stmt4->fetchAll();

    json_success([
        'child'           => $child,
        'medical_records' => $records,
        'prescriptions'   => $prescriptions,
        'growth_records'  => $growth
    ]);
}

// ── Update Medical Record ─────────────────────────────
elseif ($action === 'update' && $method === 'PUT') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $input = get_json_input();
    $recordId = intval($input['record_id'] ?? 0);

    if (!$recordId) {
        json_error('record_id is required');
    }

    $db = get_db();

    // Verify ownership
    $stmt = $db->prepare("SELECT record_id FROM medical_records WHERE record_id = :rid AND doctor_id = :did");
    $stmt->execute([':rid' => $recordId, ':did' => $authUser['user_id']]);
    if (!$stmt->fetch()) {
        json_error('Record not found or access denied', 404);
    }

    $fields = [];
    $params = [':rid' => $recordId];

    $updatable = ['diagnosis', 'symptoms', 'notes', 'follow_up_date'];
    foreach ($updatable as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = sanitize_input($input[$field]);
        }
    }

    if (empty($fields)) {
        json_error('No fields to update');
    }

    $sql = "UPDATE medical_records SET " . implode(', ', $fields) . " WHERE record_id = :rid";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    json_success(['updated' => $stmt->rowCount()], 'Medical record updated');
}

// ── Record Details ────────────────────────────────────
elseif ($action === 'details' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $recordId = get_int('record_id');
    if (!$recordId) {
        json_error('record_id parameter is required');
    }

    $db = get_db();

    $stmt = $db->prepare("
        SELECT mr.*,
               c.first_name AS child_first_name, c.last_name AS child_last_name,
               c.gender AS child_gender, c.birth_year AS child_birth_year,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization
        FROM medical_records mr
        INNER JOIN child c ON mr.child_id = c.child_id
        INNER JOIN specialist s ON mr.doctor_id = s.specialist_id
        INNER JOIN users u ON s.specialist_id = u.user_id
        WHERE mr.record_id = :rid
    ");
    $stmt->execute([':rid' => $recordId]);
    $record = $stmt->fetch();

    if (!$record) {
        json_error('Record not found', 404);
    }

    // Get related prescriptions
    $stmt2 = $db->prepare("
        SELECT prescription_id, medication_name, dosage, frequency, duration, instructions, created_at
        FROM prescriptions
        WHERE record_id = :rid
        ORDER BY created_at DESC
    ");
    $stmt2->execute([':rid' => $recordId]);
    $prescriptions = $stmt2->fetchAll();

    $record['prescriptions'] = $prescriptions;

    json_success(['record' => $record]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST ?action=create'                  => 'Create medical record (child_id, diagnosis, ?symptoms, ?notes)',
            'GET  ?action=child_history&child_id=N' => 'Get child\'s full medical history',
            'PUT  ?action=update'                   => 'Update record (record_id, ?diagnosis, ?symptoms, ?notes)',
            'GET  ?action=details&record_id=N'      => 'Get single record with prescriptions'
        ]
    ], 'Bright Steps Clinic — Medical Records API');
}
