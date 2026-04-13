<?php
/**
 * Bright Steps Clinic API — Prescriptions (Doctor)
 * 
 * Endpoints:
 *   POST ?action=create   — Add a prescription linked to a medical record
 *   GET  ?action=list     — List prescriptions for a child or record
 *   PUT  ?action=update   — Update a prescription
 *   GET  ?action=details  — Get single prescription details
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Create Prescription ───────────────────────────────
if ($action === 'create' && $method === 'POST') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['record_id', 'child_id', 'medication_name']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    $db = get_db();

    // Verify medical record exists and belongs to this doctor
    $stmt = $db->prepare("
        SELECT record_id, child_id FROM medical_records
        WHERE record_id = :rid AND doctor_id = :did
    ");
    $stmt->execute([':rid' => $input['record_id'], ':did' => $authUser['user_id']]);
    $record = $stmt->fetch();

    if (!$record) {
        json_error('Medical record not found or access denied', 404);
    }

    // Get parent for notification
    $stmt = $db->prepare("SELECT parent_id FROM child WHERE child_id = :cid");
    $stmt->execute([':cid' => $input['child_id']]);
    $child = $stmt->fetch();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO prescriptions (record_id, child_id, doctor_id, medication_name, dosage, frequency, duration, instructions)
            VALUES (:record_id, :child_id, :doctor_id, :medication_name, :dosage, :frequency, :duration, :instructions)
        ");
        $stmt->execute([
            ':record_id'       => intval($input['record_id']),
            ':child_id'        => intval($input['child_id']),
            ':doctor_id'       => $authUser['user_id'],
            ':medication_name' => $input['medication_name'],
            ':dosage'          => $input['dosage'] ?? null,
            ':frequency'       => $input['frequency'] ?? null,
            ':duration'        => $input['duration'] ?? null,
            ':instructions'    => $input['instructions'] ?? null
        ]);
        $prescriptionId = $db->lastInsertId();

        // Notify parent
        if ($child) {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, reference_id)
                VALUES (:user_id, 'New Prescription', :message, 'prescription_added', :ref_id)
            ");
            $stmt->execute([
                ':user_id' => $child['parent_id'],
                ':message' => 'A new prescription for ' . $input['medication_name'] . ' has been added for your child.',
                ':ref_id'  => $prescriptionId
            ]);
        }

        $db->commit();

        json_success([
            'prescription_id' => (int)$prescriptionId
        ], 'Prescription added successfully', 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Failed to create prescription', 500);
    }
}

// ── List Prescriptions ────────────────────────────────
elseif ($action === 'list' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor', 'parent']);

    $childId = get_int('child_id');
    $recordId = get_int('record_id');

    if (!$childId && !$recordId) {
        json_error('Either child_id or record_id parameter is required');
    }

    $db = get_db();

    $sql = "
        SELECT p.prescription_id, p.record_id, p.medication_name, p.dosage, p.frequency,
               p.duration, p.instructions, p.created_at,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               c.first_name AS child_first_name, c.last_name AS child_last_name
        FROM prescriptions p
        INNER JOIN users u ON p.doctor_id = u.user_id
        INNER JOIN child c ON p.child_id = c.child_id
        WHERE 1=1
    ";
    $params = [];

    if ($childId) {
        $sql .= " AND p.child_id = :child_id";
        $params[':child_id'] = $childId;
    }
    if ($recordId) {
        $sql .= " AND p.record_id = :record_id";
        $params[':record_id'] = $recordId;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $prescriptions = $stmt->fetchAll();

    json_success([
        'count'         => count($prescriptions),
        'prescriptions' => $prescriptions
    ]);
}

// ── Update Prescription ───────────────────────────────
elseif ($action === 'update' && $method === 'PUT') {
    $authUser = require_auth();
    require_role($authUser, ['specialist', 'doctor']);

    $input = get_json_input();
    $prescriptionId = intval($input['prescription_id'] ?? 0);

    if (!$prescriptionId) {
        json_error('prescription_id is required');
    }

    $db = get_db();

    // Verify ownership
    $stmt = $db->prepare("SELECT prescription_id FROM prescriptions WHERE prescription_id = :pid AND doctor_id = :did");
    $stmt->execute([':pid' => $prescriptionId, ':did' => $authUser['user_id']]);
    if (!$stmt->fetch()) {
        json_error('Prescription not found or access denied', 404);
    }

    $fields = [];
    $params = [':pid' => $prescriptionId];

    $updatable = ['medication_name', 'dosage', 'frequency', 'duration', 'instructions'];
    foreach ($updatable as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = sanitize_input($input[$field]);
        }
    }

    if (empty($fields)) {
        json_error('No fields to update');
    }

    $sql = "UPDATE prescriptions SET " . implode(', ', $fields) . " WHERE prescription_id = :pid";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    json_success(['updated' => $stmt->rowCount()], 'Prescription updated');
}

// ── Prescription Details ──────────────────────────────
elseif ($action === 'details' && $method === 'GET') {
    $authUser = require_auth();

    $prescriptionId = get_int('prescription_id');
    if (!$prescriptionId) {
        json_error('prescription_id parameter is required');
    }

    $db = get_db();

    $stmt = $db->prepare("
        SELECT p.*,
               u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
               s.specialization,
               c.first_name AS child_first_name, c.last_name AS child_last_name,
               mr.diagnosis AS record_diagnosis
        FROM prescriptions p
        INNER JOIN users u ON p.doctor_id = u.user_id
        INNER JOIN specialist s ON p.doctor_id = s.specialist_id
        INNER JOIN child c ON p.child_id = c.child_id
        INNER JOIN medical_records mr ON p.record_id = mr.record_id
        WHERE p.prescription_id = :pid
    ");
    $stmt->execute([':pid' => $prescriptionId]);
    $prescription = $stmt->fetch();

    if (!$prescription) {
        json_error('Prescription not found', 404);
    }

    json_success(['prescription' => $prescription]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST ?action=create'                    => 'Add prescription (record_id, child_id, medication_name, ?dosage, ?frequency)',
            'GET  ?action=list&child_id=N'           => 'List prescriptions for child',
            'GET  ?action=list&record_id=N'          => 'List prescriptions for record',
            'PUT  ?action=update'                    => 'Update prescription (prescription_id, ...fields)',
            'GET  ?action=details&prescription_id=N' => 'Get prescription details'
        ]
    ], 'Bright Steps Clinic — Prescriptions API');
}
