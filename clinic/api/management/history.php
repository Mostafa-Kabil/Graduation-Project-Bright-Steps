<?php
/**
 * Bright Steps Clinic API — Medical History & Appointment Tracking
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

try {
    $action = get_action();
    $method = get_method();

    // ── Full Child Medical History ─────────────────────────
    if ($action === 'child' && $method === 'GET') {
        $authUser = require_auth();

        $childId = get_int('child_id');
        if (!$childId) {
            json_error('child_id parameter is required');
        }

        $db = get_db();

        // SELF-HEALING: Ensure tables exist
        $db->exec("CREATE TABLE IF NOT EXISTS `medical_records` (
          `record_id` int(11) NOT NULL AUTO_INCREMENT,
          `child_id` int(11) NOT NULL,
          `doctor_id` int(11) NOT NULL,
          `diagnosis` varchar(255) DEFAULT NULL,
          `symptoms` text DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `follow_up_date` date DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`record_id`),
          KEY `child_id` (`child_id`),
          KEY `doctor_id` (`doctor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("CREATE TABLE IF NOT EXISTS `prescriptions` (
          `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
          `child_id` int(11) NOT NULL,
          `doctor_id` int(11) NOT NULL,
          `record_id` int(11) DEFAULT NULL,
          `medication_name` varchar(255) NOT NULL,
          `dosage` varchar(100) DEFAULT NULL,
          `frequency` varchar(100) DEFAULT NULL,
          `duration` varchar(100) DEFAULT NULL,
          `instructions` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`prescription_id`),
          KEY `child_id` (`child_id`),
          KEY `doctor_id` (`doctor_id`),
          KEY `record_id` (`record_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Child info
        $stmt = $db->prepare("
            SELECT c.child_id, c.ssn, c.first_name, c.last_name, c.gender,
                   c.birth_day, c.birth_month, c.birth_year, c.birth_certificate,
                   u.first_name AS parent_first_name, u.last_name AS parent_last_name,
                   u.email AS parent_email, p.parent_id
            FROM child c
            INNER JOIN parent p ON c.parent_id = p.parent_id
            INNER JOIN users u ON p.parent_id = u.user_id
            WHERE c.child_id = :child_id
        ");
        $stmt->execute([':child_id' => $childId]);
        $child = $stmt->fetch();

        if (!$child) {
            json_error('Child not found', 404);
        }

        // Medical records with doctor info
        $stmt2 = $db->prepare("
            SELECT mr.record_id, mr.diagnosis, mr.symptoms, mr.notes, mr.follow_up_date,
                   mr.created_at, mr.updated_at,
                   u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
                   s.specialization
            FROM medical_records mr
            INNER JOIN specialist s ON mr.doctor_id = s.specialist_id
            INNER JOIN users u ON s.specialist_id = u.user_id
            WHERE mr.child_id = :child_id
            ORDER BY mr.created_at DESC
        ");
        $stmt2->execute([':child_id' => $childId]);
        $medicalRecords = $stmt2->fetchAll();

        // Prescriptions
        $stmt3 = $db->prepare("
            SELECT p.prescription_id, p.record_id, p.medication_name, p.dosage,
                   p.frequency, p.duration, p.instructions, p.created_at,
                   u.first_name AS doctor_first_name, u.last_name AS doctor_last_name
            FROM prescriptions p
            INNER JOIN users u ON p.doctor_id = u.user_id
            WHERE p.child_id = :child_id
            ORDER BY p.created_at DESC
        ");
        $stmt3->execute([':child_id' => $childId]);
        $prescriptions = $stmt3->fetchAll();

        // Growth records
        $stmt4 = $db->prepare("
            SELECT record_id, height, weight, head_circumference, recorded_at
            FROM growth_record
            WHERE child_id = :child_id
            ORDER BY recorded_at DESC
        ");
        $stmt4->execute([':child_id' => $childId]);
        $growthRecords = $stmt4->fetchAll();

        // Appointment history
        $stmt5 = $db->prepare("
            SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
                   u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
                   s.specialization, c2.clinic_name
            FROM appointment a
            INNER JOIN specialist s ON a.specialist_id = s.specialist_id
            INNER JOIN users u ON s.specialist_id = u.user_id
            INNER JOIN clinic c2 ON s.clinic_id = c2.clinic_id
            WHERE a.parent_id = :parent_id
            ORDER BY a.scheduled_at DESC
        ");
        $stmt5->execute([':parent_id' => $child['parent_id']]);
        $appointments = $stmt5->fetchAll();

        json_success([
            'child'           => $child,
            'medical_records' => $medicalRecords,
            'prescriptions'   => $prescriptions,
            'growth_records'  => $growthRecords,
            'appointments'    => $appointments
        ]);
    }

    // ── Clinic Appointments ───────────────────────────────
    elseif ($action === 'appointments' && $method === 'GET') {
        $authUser = require_auth();
        require_role($authUser, ['admin', 'clinic']);

        $clinicId = get_int('clinic_id');
        $status = get_string('status');
        $doctorId = get_int('doctor_id');
        $date = get_string('date');

        $db = get_db();

        $sql = "
            SELECT a.appointment_id, NULL as child_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
                   pu.first_name AS parent_first_name, pu.last_name AS parent_last_name,
                   u.first_name AS doctor_first_name, u.last_name AS doctor_last_name,
                   s.specialization, c.clinic_name,
                   ch.first_name AS child_first_name, ch.last_name AS child_last_name
            FROM appointment a
            INNER JOIN specialist s ON a.specialist_id = s.specialist_id
            INNER JOIN users u ON s.specialist_id = u.user_id
            INNER JOIN clinic c ON s.clinic_id = c.clinic_id
            INNER JOIN parent p ON a.parent_id = p.parent_id
            INNER JOIN users pu ON p.parent_id = pu.user_id
            LEFT JOIN child ch ON a.parent_id = ch.parent_id
            WHERE 1=1
        ";
        $params = [];

        if ($clinicId) {
            $sql .= " AND c.clinic_id = :clinic_id";
            $params[':clinic_id'] = $clinicId;
        }
        if ($status) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        if ($doctorId) {
            $sql .= " AND a.specialist_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }
        if ($date) {
            $sql .= " AND DATE(a.scheduled_at) = :date";
            $params[':date'] = $date;
        }

        $sql .= " ORDER BY a.scheduled_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        // Summary counts
        $countSql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN a.status IN ('scheduled','confirmed') AND a.scheduled_at >= NOW() THEN 1 ELSE 0 END) AS upcoming,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM appointment a
            INNER JOIN specialist s ON a.specialist_id = s.specialist_id
            WHERE 1=1
        ";
        $countParams = [];
        if ($clinicId) {
            $countSql .= " AND s.clinic_id = :clinic_id";
            $countParams[':clinic_id'] = $clinicId;
        }

        $stmt2 = $db->prepare($countSql);
        $stmt2->execute($countParams);
        $counts = $stmt2->fetch();

        json_success([
            'count'        => count($appointments),
            'summary'      => [
                'total'     => (int)($counts['total'] ?? 0),
                'upcoming'  => (int)($counts['upcoming'] ?? 0),
                'completed' => (int)($counts['completed'] ?? 0),
                'cancelled' => (int)($counts['cancelled'] ?? 0)
            ],
            'appointments' => $appointments
        ]);
    }

    // ── Search Children ───────────────────────────────────
    elseif ($action === 'search' && $method === 'GET') {
        $authUser = require_auth();

        $query = get_string('query');
        if (strlen($query) < 2) {
            json_error('Search query must be at least 2 characters');
        }

        $db = get_db();
        $search = '%' . $query . '%';

        $stmt = $db->prepare("
            SELECT c.child_id, c.first_name, c.last_name, c.gender, c.birth_year, c.birth_month,
                   u.first_name AS parent_first_name, u.last_name AS parent_last_name
            FROM child c
            INNER JOIN users u ON c.parent_id = u.user_id
            WHERE CONCAT(c.first_name, ' ', c.last_name) LIKE :q1
               OR CONCAT(u.first_name, ' ', u.last_name) LIKE :q2
               OR c.ssn LIKE :q3
            ORDER BY c.first_name ASC
            LIMIT 30
        ");
        $stmt->execute([':q1' => $search, ':q2' => $search, ':q3' => $search]);
        $results = $stmt->fetchAll();

        json_success([
            'count'   => count($results),
            'results' => $results
        ]);
    }

    // ── Default ───────────────────────────────────────────
    else {
        json_success([
            'endpoints' => [
                'GET ?action=child&child_id=N'    => 'Full medical history for a child',
                'GET ?action=appointments'         => 'Clinic appointments (?clinic_id=, ?status=, ?doctor_id=, ?date=)',
                'GET ?action=search&query=...'     => 'Search children by name or SSN'
            ]
        ], 'Bright Steps Clinic — History & Tracking API');
    }

} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}

