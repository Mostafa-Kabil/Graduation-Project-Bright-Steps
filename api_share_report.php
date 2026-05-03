<?php
/**
 * Bright Steps – Share Report API
 * Handles parent sharing reports (PDF upload or system-generated) with doctors via appointments.
 */
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$parentId = intval($_SESSION['id']);
$method = $_SERVER['REQUEST_METHOD'];

// ── Ensure table exists ──
try {
    $connect->exec("CREATE TABLE IF NOT EXISTS `shared_reports` (
        `report_id` int(11) NOT NULL AUTO_INCREMENT,
        `file_path` varchar(500) DEFAULT NULL,
        `report_type` varchar(50) DEFAULT 'full-report',
        `child_id` int(11) NOT NULL,
        `parent_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `appointment_id` int(11) DEFAULT NULL,
        `is_shared` tinyint(1) DEFAULT 1,
        `doctor_reply` text DEFAULT NULL,
        `doctor_reply_date` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`report_id`),
        KEY `idx_sr_child` (`child_id`),
        KEY `idx_sr_parent` (`parent_id`),
        KEY `idx_sr_doctor` (`doctor_id`),
        KEY `idx_sr_appointment` (`appointment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Exception $e) { /* table likely already exists */ }

// Ensure uploads/reports directory exists
$uploadDir = __DIR__ . '/uploads/reports/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ═══════════════════════════════════════════════════════
// GET – Fetch parent's shared reports & available appointments
// ═══════════════════════════════════════════════════════
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    // Get appointments with doctors for sharing (for the dropdown)
    if ($action === 'get_appointments_for_sharing') {
        $stmt = $connect->prepare("
            SELECT a.appointment_id, a.specialist_id, a.status, a.type, a.scheduled_at,
                   s.first_name AS doc_fname, s.last_name AS doc_lname, s.specialization,
                   c.clinic_name
            FROM appointment a
            JOIN specialist s ON a.specialist_id = s.specialist_id
            JOIN clinic c ON s.clinic_id = c.clinic_id
            WHERE a.parent_id = :pid
            ORDER BY a.scheduled_at DESC
        ");
        $stmt->execute([':pid' => $parentId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // Get parent's shared reports history
    if ($action === 'get_my_shared_reports') {
        $child_id = intval($_GET['child_id'] ?? 0);
        $sql = "
            SELECT sr.*, 
                   c.first_name AS child_first_name, c.last_name AS child_last_name,
                   s.first_name AS doc_fname, s.last_name AS doc_lname, s.specialization,
                   a.scheduled_at AS appointment_date, a.status AS appointment_status
            FROM shared_reports sr
            JOIN child c ON sr.child_id = c.child_id
            JOIN specialist s ON sr.doctor_id = s.specialist_id
            LEFT JOIN appointment a ON sr.appointment_id = a.appointment_id
            WHERE sr.parent_id = :pid
        ";
        $params = [':pid' => $parentId];
        if ($child_id) {
            $sql .= " AND sr.child_id = :cid";
            $params[':cid'] = $child_id;
        }
        $sql .= " ORDER BY sr.created_at DESC";
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// ═══════════════════════════════════════════════════════
// POST – Share a report with a doctor
// ═══════════════════════════════════════════════════════
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'share_report') {
        $child_id = intval($_POST['child_id'] ?? 0);
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
        $report_type = trim($_POST['report_type'] ?? 'full-report');

        if (!$child_id || !$doctor_id) {
            echo json_encode(['success' => false, 'error' => 'child_id and doctor_id are required']);
            exit;
        }

        // Verify child belongs to parent
        $check = $connect->prepare("SELECT child_id FROM child WHERE child_id = ? AND parent_id = ?");
        $check->execute([$child_id, $parentId]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Child not found or access denied']);
            exit;
        }

        $filePath = null;

        // Handle PDF file upload
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['report_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed']);
                exit;
            }
            if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                echo json_encode(['success' => false, 'error' => 'File too large. Maximum 10MB.']);
                exit;
            }

            $fileName = 'report_' . $parentId . '_' . $child_id . '_' . time() . '.pdf';
            $destPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $filePath = 'uploads/reports/' . $fileName;
                $report_type = 'uploaded-pdf';
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
                exit;
            }
        } else {
            // System-generated report: store the type, no file path needed
            // The doctor can view it via api_export_pdf.php link
            $filePath = 'api_export_pdf.php?type=' . urlencode($report_type) . '&child_id=' . $child_id;
        }

        // Insert shared report
        $stmt = $connect->prepare("
            INSERT INTO shared_reports (file_path, report_type, child_id, parent_id, doctor_id, appointment_id, is_shared)
            VALUES (:fp, :rt, :cid, :pid, :did, :aid, 1)
        ");
        $stmt->execute([
            ':fp' => $filePath,
            ':rt' => $report_type,
            ':cid' => $child_id,
            ':pid' => $parentId,
            ':did' => $doctor_id,
            ':aid' => $appointment_id
        ]);

        $reportId = $connect->lastInsertId();

        // Notify the doctor
        try {
            $childStmt = $connect->prepare("SELECT first_name, last_name FROM child WHERE child_id = ?");
            $childStmt->execute([$child_id]);
            $childInfo = $childStmt->fetch(PDO::FETCH_ASSOC);
            $childName = ($childInfo['first_name'] ?? '') . ' ' . ($childInfo['last_name'] ?? '');
            $parentName = ($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '');

            $connect->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (?, 'system', ?, ?)
            ")->execute([
                $doctor_id,
                'New Report Shared',
                "Parent {$parentName} has shared a {$report_type} report for child {$childName}."
            ]);
        } catch (Exception $e) { /* notification failure is non-critical */ }

        echo json_encode([
            'success' => true,
            'report_id' => $reportId,
            'message' => 'Report shared successfully with the doctor'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
