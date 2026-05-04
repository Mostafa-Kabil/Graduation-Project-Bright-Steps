<?php
session_start();
require_once 'connection.php';

// Check maintenance mode
try {
    $mStmt = $connect->prepare("SELECT setting_value FROM system_config WHERE setting_key = 'maintenance_mode'");
    $mStmt->execute();
    if ($mStmt->fetchColumn() === '1') {
        header("Location: maintenance.php");
        exit;
    }
} catch (Exception $e) { /* system_config table may not exist yet */ }

// ─── Auth: only authenticated doctors/specialists can access ─────────────
$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || isset($_GET['ajax']);
if (!$isAjax) {
    // For HTML page requests: enforce login
    if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
        header('Location: doctor-login.php');
        exit;
    }
}

// Session-derived variables for the HTML view
$sessionSpecialistId = intval($_SESSION['specialist_id'] ?? $_SESSION['id'] ?? 0);
$sessionDoctorName = 'Dr. ' . htmlspecialchars($_SESSION['fname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lname'] ?? '');
$sessionDoctorInitials = strtoupper(substr($_SESSION['fname'] ?? 'D', 0, 1) . substr($_SESSION['lname'] ?? 'S', 0, 1));
$sessionSpecialization = htmlspecialchars($_SESSION['specialization'] ?? 'Specialist');

// ─── Onboarding Check: redirect if not completed ─────────────
if (!$isAjax) {
    $needsOnboarding = true;
    try {
        // Check doctor_onboarding table
        $connect->exec("CREATE TABLE IF NOT EXISTS `doctor_onboarding` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `doctor_id` INT NOT NULL,
            `specialization` VARCHAR(100),
            `experience_years` INT DEFAULT 0,
            `certifications` VARCHAR(255),
            `focus_areas` TEXT,
            `working_days` TEXT,
            `start_time` TIME DEFAULT '09:00:00',
            `end_time` TIME DEFAULT '17:00:00',
            `consultation_types` TEXT,
            `goals` TEXT,
            `completed_at` TIMESTAMP DEFAULT current_timestamp()
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $obStmt = $connect->prepare("SELECT id FROM doctor_onboarding WHERE doctor_id = ? LIMIT 1");
        $obStmt->execute([intval($_SESSION['id'])]);
        if ($obStmt->fetch(PDO::FETCH_ASSOC)) {
            $needsOnboarding = false;
        }
    } catch (Exception $e) {
        $needsOnboarding = false; // Don't block if table issue
    }

    // Also skip if specialist already has specialization configured
    if ($needsOnboarding) {
        try {
            $specCheck = $connect->prepare("SELECT specialization, experience_years FROM specialist WHERE specialist_id = ? LIMIT 1");
            $specCheck->execute([$sessionSpecialistId]);
            $specRow = $specCheck->fetch(PDO::FETCH_ASSOC);
            if ($specRow && !empty($specRow['specialization']) && intval($specRow['experience_years']) > 0) {
                $needsOnboarding = false;
            }
        } catch (Exception $e) {
            $needsOnboarding = false;
        }
    }

    if ($needsOnboarding) {
        header('Location: doctor-onboarding.php');
        exit;
    }
}

// ═══════════════════════════════════════════════════════
// Doctor Dashboard — Backend API Handler
// Handles AJAX requests for Reports & Messages
// ═══════════════════════════════════════════════════════
if ($isAjax) {
    header('Content-Type: application/json');

    $method = $_SERVER['REQUEST_METHOD'];
    $section = $_GET['section'] ?? '';

    // Ensure notifications table exists
    try {
        $connect->exec("CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `type` VARCHAR(50) DEFAULT 'system',
            `title` VARCHAR(255),
            `message` TEXT,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_notif_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch (Exception $e) { /* table may already exist */ }

    // ─── REPORTS SECTION ────────────────────────────────
    if ($section === 'reports') {

        // Ensure shared_reports table exists
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
                KEY `idx_sr_doctor` (`doctor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Exception $e) { /* table exists */ }

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_shared_reports') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                // Only show reports explicitly shared with this doctor AND linked to an appointment
                $stmt = $connect->prepare("
                    SELECT 
                        sr.report_id, sr.file_path, sr.report_type, sr.child_id, sr.parent_id,
                        sr.doctor_id, sr.appointment_id, sr.is_shared,
                        sr.doctor_reply, sr.doctor_reply_date, sr.created_at,
                        c.first_name AS child_first_name, c.last_name AS child_last_name,
                        c.gender, c.birth_year, c.birth_month, c.birth_day,
                        u.first_name AS parent_first_name, u.last_name AS parent_last_name,
                        a.scheduled_at AS appointment_date, a.status AS appointment_status, a.type AS appointment_type
                    FROM shared_reports sr
                    JOIN child c ON sr.child_id = c.child_id
                    JOIN users u ON sr.parent_id = u.user_id
                    LEFT JOIN appointment a ON sr.appointment_id = a.appointment_id
                    WHERE sr.doctor_id = :sid
                      AND sr.is_shared = 1
                    ORDER BY sr.created_at DESC
                ");
                $stmt->execute([':sid' => $specialist_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_doctor_reports') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT dr.*, c.first_name AS child_first_name, c.last_name AS child_last_name
                    FROM doctor_report dr
                    JOIN child c ON dr.child_id = c.child_id
                    WHERE dr.specialist_id = :sid
                    ORDER BY dr.created_at DESC
                ");
                $stmt->execute([':sid' => $specialist_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_report_stats') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                // Count doctor-written reports
                $stmt = $connect->prepare("
                    SELECT
                        COUNT(*) AS total_reports,
                        SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month
                    FROM doctor_report WHERE specialist_id = :sid
                ");
                $stmt->execute([':sid' => $specialist_id]);
                $dr_stats = $stmt->fetch(PDO::FETCH_ASSOC);

                // Count shared reports from parents
                $stmt2 = $connect->prepare("
                    SELECT 
                        COUNT(*) AS shared_total,
                        SUM(CASE WHEN doctor_reply IS NULL THEN 1 ELSE 0 END) AS pending_review
                    FROM shared_reports 
                    WHERE doctor_id = :sid AND is_shared = 1
                ");
                $stmt2->execute([':sid' => $specialist_id]);
                $shared_stats = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_reports' => intval($dr_stats['total_reports'] ?? 0),
                        'this_month' => intval($dr_stats['this_month'] ?? 0),
                        'shared_total' => intval($shared_stats['shared_total'] ?? 0),
                        'pending_review' => intval($shared_stats['pending_review'] ?? 0)
                    ]
                ]);
                exit;

            } elseif ($action === 'download_report') {
                // Serve the PDF file for download
                $report_id = intval($_GET['report_id'] ?? 0);
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$report_id || !$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'report_id and specialist_id required']);
                    exit;
                }
                $stmt = $connect->prepare("SELECT file_path, report_type FROM shared_reports WHERE report_id = ? AND doctor_id = ? AND is_shared = 1");
                $stmt->execute([$report_id, $specialist_id]);
                $report = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$report) {
                    echo json_encode(['success' => false, 'error' => 'Report not found']);
                    exit;
                }
                echo json_encode(['success' => true, 'data' => $report]);
                exit;
            }

        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'submit_report') {
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
                    $report_date = trim($input['report_date'] ?? '');
                    if (empty($report_date)) {
                        $report_date = @date('Y-m-d');
                    }

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
                    }
                    
                    // If this is a reply to a shared report, update the shared report status
                    if ($shared_report_id) {
                        $stmtUpdate = $connect->prepare("
                            UPDATE shared_reports 
                            SET doctor_reply = :reply, doctor_reply_date = :rdate 
                            WHERE report_id = :rid AND doctor_id = :sid
                        ");
                        $stmtUpdate->execute([
                            ':reply' => "Report written on {$report_date}. See 'My Reports' tab.", 
                            ':rdate' => $report_date,
                            ':rid' => $shared_report_id, 
                            ':sid' => $specialist_id
                        ]);

                        // Notify parent
                        $pStmt = $connect->prepare("SELECT parent_id FROM shared_reports WHERE report_id = ?");
                        $pStmt->execute([$shared_report_id]);
                        $sr = $pStmt->fetch(PDO::FETCH_ASSOC);
                        if ($sr) {
                            $docName = $sessionDoctorName ?? 'Your doctor';
                            $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)")
                                ->execute([$sr['parent_id'], 'Doctor Report Added', "{$docName} has written a report based on your shared documents."]);
                        }
                    }

                    ob_clean();
                    echo json_encode(['success' => true, 'doctor_report_id' => $doctor_report_id]);
                    exit;
                } catch (Exception $e) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                    exit;
                }
            }
        }
    }

    // ─── MESSAGES SECTION ───────────────────────────────
    if ($section === 'messages') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_conversations') {
                $user_id = intval($_GET['user_id'] ?? 0);
                if (!$user_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id required']);
                    exit;
                }
                // Get latest message per conversation partner
                $stmt = $connect->prepare("
                    SELECT 
                        partner.user_id AS partner_id,
                        partner.first_name AS partner_first_name,
                        partner.last_name  AS partner_last_name,
                        partner.role       AS partner_role,
                        latest.content     AS last_message,
                        latest.sent_at     AS last_message_time,
                        (SELECT COUNT(*) FROM message m2 
                         WHERE m2.sender_id = partner.user_id 
                           AND m2.receiver_id = :uid2 
                           AND m2.is_read = 0) AS unread_count
                    FROM users partner
                    JOIN message latest ON (
                        (latest.sender_id = partner.user_id AND latest.receiver_id = :uid3)
                        OR (latest.sender_id = :uid4 AND latest.receiver_id = partner.user_id)
                    )
                    WHERE partner.user_id != :uid5
                      AND latest.sent_at = (
                          SELECT MAX(m3.sent_at) FROM message m3
                          WHERE (m3.sender_id = :uid6 AND m3.receiver_id = partner.user_id)
                             OR (m3.sender_id = partner.user_id AND m3.receiver_id = :uid7)
                      )
                    GROUP BY partner.user_id
                    ORDER BY latest.sent_at DESC
                ");
                $stmt->execute([
                    ':uid2' => $user_id,
                    ':uid3' => $user_id,
                    ':uid4' => $user_id,
                    ':uid5' => $user_id,
                    ':uid6' => $user_id,
                    ':uid7' => $user_id
                ]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_messages') {
                $user_id = intval($_GET['user_id'] ?? 0);
                $partner_id = intval($_GET['partner_id'] ?? 0);
                if (!$user_id || !$partner_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id and partner_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT m.*, u.first_name AS sender_first_name, u.last_name AS sender_last_name
                    FROM message m
                    JOIN users u ON m.sender_id = u.user_id
                    WHERE (m.sender_id = :uid AND m.receiver_id = :pid)
                       OR (m.sender_id = :pid2 AND m.receiver_id = :uid2)
                    ORDER BY m.sent_at ASC
                ");
                $stmt->execute([':uid' => $user_id, ':pid' => $partner_id, ':pid2' => $partner_id, ':uid2' => $user_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;
            }

        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'send_message') {
                $sender_id = intval($input['sender_id'] ?? 0);
                $receiver_id = intval($input['receiver_id'] ?? 0);
                $content = trim($input['content'] ?? '');
                $appointment_id = !empty($input['appointment_id']) ? intval($input['appointment_id']) : null;
                $child_id = !empty($input['child_id']) ? intval($input['child_id']) : null;

                if (!$sender_id || !$receiver_id || !$content) {
                    echo json_encode(['success' => false, 'error' => 'sender_id, receiver_id, and content are required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    INSERT INTO message (sender_id, receiver_id, appointment_id, child_id, content)
                    VALUES (:sid, :rid, :aid, :cid, :content)
                ");
                $stmt->execute([
                    ':sid' => $sender_id,
                    ':rid' => $receiver_id,
                    ':aid' => $appointment_id,
                    ':cid' => $child_id,
                    ':content' => $content
                ]);
                echo json_encode(['success' => true, 'message_id' => $connect->lastInsertId()]);
                exit;

            } elseif ($action === 'mark_read') {
                $user_id = intval($input['user_id'] ?? 0);
                $partner_id = intval($input['partner_id'] ?? 0);
                if (!$user_id || !$partner_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id and partner_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    UPDATE message SET is_read = 1 
                    WHERE sender_id = :pid AND receiver_id = :uid AND is_read = 0
                ");
                $stmt->execute([':pid' => $partner_id, ':uid' => $user_id]);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }
    }

    // ─── PATIENTS SECTION ───────────────────────────────
    if ($section === 'patients') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_patients') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT DISTINCT
                        c.child_id, c.first_name AS child_first_name, c.last_name AS child_last_name,
                        c.gender, c.birth_year, c.birth_month, c.birth_day,
                        u.first_name AS parent_first_name, u.last_name AS parent_last_name,
                        p.parent_id,
                        (SELECT a2.status FROM appointment a2 
                         WHERE a2.specialist_id = :sid2 AND a2.parent_id = p.parent_id 
                         ORDER BY a2.scheduled_at DESC LIMIT 1) AS last_appointment_status,
                        (SELECT a3.scheduled_at FROM appointment a3 
                         WHERE a3.specialist_id = :sid3 AND a3.parent_id = p.parent_id 
                         ORDER BY a3.scheduled_at DESC LIMIT 1) AS last_appointment_date
                    FROM appointment a
                    JOIN parent p ON p.parent_id = a.parent_id
                    JOIN users u ON u.user_id = p.parent_id
                    JOIN child c ON c.parent_id = p.parent_id
                    WHERE a.specialist_id = :sid
                    GROUP BY c.child_id
                    ORDER BY last_appointment_date DESC
                ");
                $stmt->execute([':sid' => $specialist_id, ':sid2' => $specialist_id, ':sid3' => $specialist_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_patient_detail') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                $child_id = intval($_GET['child_id'] ?? 0);
                if (!$specialist_id || !$child_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id and child_id required']);
                    exit;
                }

                // Child info
                $stmt = $connect->prepare("
                    SELECT c.child_id, c.first_name, c.last_name, c.gender, c.birth_year, c.birth_month, c.birth_day,
                           u.first_name AS parent_first_name, u.last_name AS parent_last_name, p.parent_id
                    FROM child c
                    JOIN parent p ON p.parent_id = c.parent_id
                    JOIN users u ON u.user_id = p.parent_id
                    WHERE c.child_id = :cid
                ");
                $stmt->execute([':cid' => $child_id]);
                $child = $stmt->fetch(PDO::FETCH_ASSOC);

                // Growth records
                $stmt2 = $connect->prepare("
                    SELECT record_id, height, weight, head_circumference, recorded_at
                    FROM growth_record WHERE child_id = :cid ORDER BY recorded_at DESC LIMIT 10
                ");
                $stmt2->execute([':cid' => $child_id]);
                $growth = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                // Milestones
                $stmt3 = $connect->prepare("
                    SELECT cm.milestone_id, m.title, m.category, cm.achieved_at, cm.recorded_at as created_at
                    FROM child_milestones cm
                    JOIN milestones m ON cm.milestone_id = m.milestone_id
                    WHERE cm.child_id = :cid ORDER BY cm.achieved_at DESC
                ");
                $stmt3->execute([':cid' => $child_id]);
                $milestones = $stmt3->fetchAll(PDO::FETCH_ASSOC);

                // Doctor reports for this child
                $stmt4 = $connect->prepare("
                    SELECT doctor_report_id as report_id, doctor_notes, recommendations, report_date, created_at
                    FROM doctor_report WHERE specialist_id = :sid AND child_id = :cid ORDER BY created_at DESC
                ");
                $stmt4->execute([':sid' => $specialist_id, ':cid' => $child_id]);
                $reports = $stmt4->fetchAll(PDO::FETCH_ASSOC);

                // Appointments
                $stmt5 = $connect->prepare("
                    SELECT appointment_id, status, type, scheduled_at, comment
                    FROM appointment
                    WHERE specialist_id = :sid AND parent_id = (SELECT parent_id FROM child WHERE child_id = :cid LIMIT 1)
                    ORDER BY scheduled_at DESC
                ");
                $stmt5->execute([':sid' => $specialist_id, ':cid' => $child_id]);
                $appointments = $stmt5->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'child' => $child,
                        'growth_records' => $growth,
                        'milestones' => $milestones,
                        'doctor_reports' => $reports,
                        'appointments' => $appointments
                    ]
                ]);
                exit;

            } elseif ($action === 'search_patients') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                $query = trim($_GET['query'] ?? '');
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                $search = '%' . $query . '%';
                $stmt = $connect->prepare("
                    SELECT DISTINCT
                        c.child_id, c.first_name AS child_first_name, c.last_name AS child_last_name,
                        c.gender, c.birth_year, c.birth_month, c.birth_day,
                        u.first_name AS parent_first_name, u.last_name AS parent_last_name,
                        p.parent_id
                    FROM appointment a
                    JOIN parent p ON p.parent_id = a.parent_id
                    JOIN users u ON u.user_id = p.parent_id
                    JOIN child c ON c.parent_id = p.parent_id
                    WHERE a.specialist_id = :sid
                      AND (CONCAT(c.first_name, ' ', c.last_name) LIKE :q1
                           OR CONCAT(u.first_name, ' ', u.last_name) LIKE :q2)
                    GROUP BY c.child_id
                    ORDER BY c.first_name ASC
                ");
                $stmt->execute([':sid' => $specialist_id, ':q1' => $search, ':q2' => $search]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;
            }
        }
    }

    // ─── APPOINTMENTS SECTION ──────────────────────────────
    if ($section === 'appointments') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_appointments') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                $status_filter = trim($_GET['status'] ?? '');
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }

                $sql = "
                    SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.report, a.comment,
                           u.first_name AS parent_first_name, u.last_name AS parent_last_name,
                           p.parent_id,
                           (SELECT CONCAT(c2.first_name, ' ', c2.last_name)
                            FROM child c2 WHERE c2.child_id = a.child_id) AS children_names,
                           (SELECT meeting_link FROM message WHERE appointment_id = a.appointment_id AND meeting_link IS NOT NULL LIMIT 1) AS meeting_link
                    FROM appointment a
                    JOIN parent p ON p.parent_id = a.parent_id
                    JOIN users u ON u.user_id = p.parent_id
                    WHERE a.specialist_id = :sid
                ";
                $params = [':sid' => $specialist_id];

                if ($status_filter) {
                    $sql .= " AND a.status = :status";
                    $params[':status'] = $status_filter;
                }
                $sql .= " ORDER BY a.scheduled_at DESC";

                $stmt = $connect->prepare($sql);
                $stmt->execute($params);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Also return summary counts
                $stmt2 = $connect->prepare("
                    SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = 'scheduled' OR status = 'confirmed' THEN 1 ELSE 0 END) AS upcoming,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                        SUM(CASE WHEN scheduled_at >= CURDATE() AND scheduled_at < CURDATE() + INTERVAL 7 DAY THEN 1 ELSE 0 END) AS this_week
                    FROM appointment WHERE specialist_id = :sid
                ");
                $stmt2->execute([':sid' => $specialist_id]);
                $counts = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $appointments, 'counts' => $counts]);
                exit;
            }

        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'update_appointment') {
                $appointment_id = intval($input['appointment_id'] ?? 0);
                $status = trim($input['status'] ?? '');
                $comment = trim($input['comment'] ?? '');

                if (!$appointment_id) {
                    echo json_encode(['success' => false, 'error' => 'appointment_id required']);
                    exit;
                }

                $fields = [];
                $params = [':aid' => $appointment_id];
                if ($status) {
                    $fields[] = "status = :status";
                    $params[':status'] = $status;
                }
                if ($comment) {
                    $fields[] = "comment = :comment";
                    $params[':comment'] = $comment;
                }

                if (empty($fields)) {
                    echo json_encode(['success' => false, 'error' => 'Nothing to update']);
                    exit;
                }

                $sql = "UPDATE appointment SET " . implode(', ', $fields) . " WHERE appointment_id = :aid";
                $stmt = $connect->prepare($sql);
                $stmt->execute($params);

                try {
                    if (isset($input['status'])) {
                        $pst = $connect->prepare("SELECT a.parent_id FROM appointment a WHERE a.appointment_id = ?");
                        $pst->execute([$appointment_id]);
                        $uid = $pst->fetchColumn();
                        if ($uid) {
                            $nst = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                            $nst->execute([$uid, 'Appointment Update', "Your appointment status was updated to: " . $input['status']]);
                        }
                    }
                } catch (Exception $e) { /* non-critical notification */ }
                if (isset($input['status'])) {
                    $pst = $connect->prepare("SELECT p.parent_id AS user_id, a.type, a.specialist_id, a.child_id FROM appointment a JOIN parent p ON a.parent_id = p.parent_id WHERE a.appointment_id = ?");
                    $pst->execute([$appointment_id]);
                    $apptInfo = $pst->fetch(PDO::FETCH_ASSOC);
                    if ($apptInfo && $apptInfo['user_id']) {
                        $uid = $apptInfo['user_id'];
                        $nst = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                        $nst->execute([$uid, 'Appointment Update', "Your appointment status was updated to: " . $input['status']]);
                        
                        if ($input['status'] === 'confirmed' && $apptInfo['type'] === 'online') {
                            $meetingLink = "https://meet.google.com/" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 3) . "-" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 4) . "-" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 3);
                            $msgContent = "Your telehealth appointment has been confirmed. Please join using this link at the scheduled time.";
                            $mst = $connect->prepare("
                                INSERT INTO message (sender_id, receiver_id, child_id, appointment_id, content, meeting_link, is_read, sent_at) 
                                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
                            ");
                            $mst->execute([
                                $apptInfo['specialist_id'], 
                                $uid, 
                                $apptInfo['child_id'],
                                $appointment_id, 
                                $msgContent, 
                                $meetingLink
                            ]);
                        }
                    }
                }

                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;

            } elseif ($action === 'cancel_appointment') {
                $appointment_id = intval($input['appointment_id'] ?? 0);
                if (!$appointment_id) {
                    echo json_encode(['success' => false, 'error' => 'appointment_id required']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE appointment SET status = 'cancelled' WHERE appointment_id = :aid");
                $stmt->execute([':aid' => $appointment_id]);

                try {
                    $pst = $connect->prepare("SELECT a.parent_id FROM appointment a WHERE a.appointment_id = ?");
                    $pst->execute([$appointment_id]);
                    $uid = $pst->fetchColumn();
                    if ($uid) {
                        $nst = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                        $nst->execute([$uid, 'Appointment Cancelled', "Your appointment has been cancelled by the doctor."]);
                    }
                } catch (Exception $e) { /* non-critical notification */ }

                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }
    }

    // ─── SETTINGS SECTION ──────────────────────────────────
    if ($section === 'settings') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_profile') {
                try {
                    $stmt = $connect->prepare("
                        SELECT u.user_id, u.first_name, u.last_name, u.email,
                               s.specialization, s.experience_years, s.certificate_of_experience, s.clinic_id,
                               COALESCE(c.clinic_name, '') AS clinic_name,
                               COALESCE(c.location, '') AS clinic_location,
                               o.goals AS bio, o.consultation_types, o.focus_areas
                        FROM users u
                        LEFT JOIN specialist s ON u.user_id = s.specialist_id
                        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                        LEFT JOIN doctor_onboarding o ON u.user_id = o.doctor_id
                        WHERE u.user_id = :uid
                    ");
                    $stmt->execute([':uid' => intval($_SESSION['id'])]);
                    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$profile) {
                        // Fallback: build from session
                        $profile = [
                            'user_id' => intval($_SESSION['id']),
                            'first_name' => $_SESSION['fname'] ?? '',
                            'last_name' => $_SESSION['lname'] ?? '',
                            'email' => $_SESSION['email'] ?? '',
                            'specialization' => $_SESSION['specialization'] ?? '',
                            'experience_years' => 0,
                            'certificate_of_experience' => '',
                            'clinic_name' => '',
                            'clinic_location' => ''
                        ];
                    }

                    $slots = [];
                    try {
                        $stmt2 = $connect->prepare("
                            SELECT slot_id, day_of_week, start_time, end_time, slot_duration, is_active
                            FROM appointment_slots
                            WHERE doctor_id = :did AND is_active = 1
                            ORDER BY day_of_week ASC, start_time ASC
                        ");
                        $stmt2->execute([':did' => intval($_SESSION['id'])]);
                        $slots = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) { /* table may not exist */ }

                    $profile['slots'] = $slots;
                    unset($profile['password']);

                    echo json_encode(['success' => true, 'data' => $profile]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                }
                exit;
            }
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'save_profile') {
                $doctor_id = intval($_SESSION['id']);
                $first_name = trim($input['first_name'] ?? '');
                $last_name  = trim($input['last_name'] ?? '');
                $email      = trim($input['email'] ?? '');
                $spec       = trim($input['specialization'] ?? '');
                $exp        = intval($input['experience_years'] ?? 0);
                $cert       = trim($input['certificate_of_experience'] ?? '');
                $bio        = trim($input['bio'] ?? '');

                if (!$first_name || !$last_name || !$email) {
                    echo json_encode(['success' => false, 'error' => 'Name and email are required']);
                    exit;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
                    exit;
                }
                $check = $connect->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :uid");
                $check->execute([':email' => $email, ':uid' => $doctor_id]);
                if ($check->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Email already in use']);
                    exit;
                }
                $connect->beginTransaction();
                try {
                    $connect->prepare("UPDATE users SET first_name = :fn, last_name = :ln, email = :email WHERE user_id = :uid")
                            ->execute([':fn' => $first_name, ':ln' => $last_name, ':email' => $email, ':uid' => $doctor_id]);
                    $connect->prepare("
                        INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years, certificate_of_experience)
                        VALUES (:sid, 0, :fn, :ln, :spec, :exp, :cert)
                        ON DUPLICATE KEY UPDATE
                            first_name = VALUES(first_name),
                            last_name = VALUES(last_name),
                            specialization = VALUES(specialization),
                            experience_years = VALUES(experience_years),
                            certificate_of_experience = VALUES(certificate_of_experience)
                    ")->execute([':fn' => $first_name, ':ln' => $last_name, ':spec' => $spec, ':exp' => $exp, ':cert' => $cert, ':sid' => $doctor_id]);

                    $stmt_bio = $connect->prepare("UPDATE doctor_onboarding SET goals = :bio WHERE doctor_id = :uid");
                    $stmt_bio->execute([':bio' => $bio, ':uid' => $doctor_id]);
                    if ($stmt_bio->rowCount() === 0) {
                        $check_bio = $connect->prepare("SELECT id FROM doctor_onboarding WHERE doctor_id = :uid");
                        $check_bio->execute([':uid' => $doctor_id]);
                        if (!$check_bio->fetch()) {
                            $connect->prepare("INSERT INTO doctor_onboarding (doctor_id, goals) VALUES (:uid, :bio)")
                                    ->execute([':uid' => $doctor_id, ':bio' => $bio]);
                        }
                    }
                    $connect->commit();
                    $_SESSION['fname'] = $first_name;
                    $_SESSION['lname'] = $last_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['specialization'] = $spec;
                    echo json_encode(['success' => true, 'message' => 'Profile saved successfully']);
                } catch (Exception $e) {
                    $connect->rollBack();
                    echo json_encode(['success' => false, 'error' => 'Failed to save profile']);
                }
                exit;
            }

            if ($action === 'change_password') {
                $doctor_id = intval($_SESSION['id']);
                $current = trim($input['current_password'] ?? '');
                $new     = trim($input['new_password'] ?? '');
                if (!$current || !$new) {
                    echo json_encode(['success' => false, 'error' => 'Both passwords required']);
                    exit;
                }
                if (strlen($new) < 6) {
                    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
                    exit;
                }
                $stmt = $connect->prepare("SELECT password FROM users WHERE user_id = :uid");
                $stmt->execute([':uid' => $doctor_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || !password_verify($current, $row['password'])) {
                    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                    exit;
                }
                $connect->prepare("UPDATE users SET password = :pw WHERE user_id = :uid")
                        ->execute([':pw' => password_hash($new, PASSWORD_DEFAULT), ':uid' => $doctor_id]);
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                exit;
            }

            if ($action === 'save_slots') {
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
                exit;
            }
        }
    }

    // ─── ANALYTICS SECTION ─────────────────────────────────
    if ($section === 'analytics') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_analytics') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }

                // Total unique patients (children via appointments)
                $stmt = $connect->prepare("
                    SELECT COUNT(DISTINCT c.child_id) AS total_patients
                    FROM appointment a
                    JOIN child c ON c.parent_id = a.parent_id
                    WHERE a.specialist_id = :sid
                ");
                $stmt->execute([':sid' => $specialist_id]);
                $total_patients = $stmt->fetch(PDO::FETCH_ASSOC)['total_patients'] ?? 0;

                // Appointment stats
                $stmt2 = $connect->prepare("
                    SELECT 
                        COUNT(*) AS total_appointments,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_appointments,
                        SUM(CASE WHEN (status = 'scheduled' OR status = 'confirmed') AND scheduled_at >= CURDATE() THEN 1 ELSE 0 END) AS upcoming_appointments,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_appointments,
                        SUM(CASE WHEN scheduled_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS this_week,
                        SUM(CASE WHEN scheduled_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month
                    FROM appointment WHERE specialist_id = :sid
                ");
                $stmt2->execute([':sid' => $specialist_id]);
                $appt_stats = $stmt2->fetch(PDO::FETCH_ASSOC);

                // Reports written
                $stmt3 = $connect->prepare("
                    SELECT COUNT(*) AS total_reports,
                           SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS reports_this_month
                    FROM doctor_report WHERE specialist_id = :sid
                ");
                $stmt3->execute([':sid' => $specialist_id]);
                $report_stats = $stmt3->fetch(PDO::FETCH_ASSOC);

                // Average rating from feedback
                $stmt4 = $connect->prepare("
                    SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total_reviews
                    FROM feedback WHERE specialist_id = :sid
                ");
                $stmt4->execute([':sid' => $specialist_id]);
                $rating_stats = $stmt4->fetch(PDO::FETCH_ASSOC);

                // Messages count
                $stmt5 = $connect->prepare("
                    SELECT COUNT(*) AS total_messages,
                           SUM(CASE WHEN sent_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS messages_this_month
                    FROM message WHERE sender_id = :sid OR receiver_id = :sid2
                ");
                $stmt5->execute([':sid' => $specialist_id, ':sid2' => $specialist_id]);
                $msg_stats = $stmt5->fetch(PDO::FETCH_ASSOC);

                // Monthly appointment trend (last 6 months)
                $stmt6 = $connect->prepare("
                    SELECT DATE_FORMAT(scheduled_at, '%Y-%m') AS month,
                           COUNT(*) AS count
                    FROM appointment
                    WHERE specialist_id = :sid
                      AND scheduled_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(scheduled_at, '%Y-%m')
                    ORDER BY month ASC
                ");
                $stmt6->execute([':sid' => $specialist_id]);
                $monthly_trend = $stmt6->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_patients' => intval($total_patients),
                        'total_appointments' => intval($appt_stats['total_appointments'] ?? 0),
                        'completed_appointments' => intval($appt_stats['completed_appointments'] ?? 0),
                        'upcoming_appointments' => intval($appt_stats['upcoming_appointments'] ?? 0),
                        'cancelled_appointments' => intval($appt_stats['cancelled_appointments'] ?? 0),
                        'appointments_this_week' => intval($appt_stats['this_week'] ?? 0),
                        'appointments_this_month' => intval($appt_stats['this_month'] ?? 0),
                        'total_reports' => intval($report_stats['total_reports'] ?? 0),
                        'reports_this_month' => intval($report_stats['reports_this_month'] ?? 0),
                        'avg_rating' => floatval($rating_stats['avg_rating'] ?? 0),
                        'total_reviews' => intval($rating_stats['total_reviews'] ?? 0),
                        'total_messages' => intval($msg_stats['total_messages'] ?? 0),
                        'messages_this_month' => intval($msg_stats['messages_this_month'] ?? 0),
                        'monthly_trend' => $monthly_trend
                    ]
                ]);
                exit;
            }
        }
    }

    // Fallback for unknown ajax requests
    echo json_encode(['success' => false, 'error' => 'Invalid section or action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/dashboard.css?v=8">
    <link rel="stylesheet" href="styles/doctor.css?v=8">
    <link rel="stylesheet" href="styles/settings.css?v=8">
    <link rel="stylesheet" href="styles/profile.css?v=8">
    <link rel="stylesheet" href="styles/dr-settings.css?v=9">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar doctor-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile">
                    <div class="user-avatar doctor-avatar"><?php echo $sessionDoctorInitials; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $sessionDoctorName; ?></div>
                        <div class="user-badge-text"><?php echo $sessionSpecialization; ?></div>
                    </div>
                    <div class="verified-badge" title="Verified Provider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" data-view="patients">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>My Patients</span>
                </button>
                <button class="nav-item" data-view="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                    <span>Reports</span>
                </button>
                <button class="nav-item" data-view="appointments">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span>Appointments</span>
                </button>
                <button class="nav-item" data-view="messages">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Messages</span>
                </button>
                <button class="nav-item" data-view="analytics">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                    <span>Analytics</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" data-view="settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span>Settings</span>
                </button>
                <button class="nav-item" onclick="showSupportPopup()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Contact Support</span>
                </button>
                <button class="nav-item nav-item-logout" onclick="handleLogout()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">My Patients</h1>
                        <p class="dashboard-subtitle" id="patientsSubtitle">View and manage your connected patients</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="doctor-stats-grid">
                    <div class="stat-card stat-card-blue">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value" id="stat-active-patients">--</div>
                            <div class="stat-card-label">Active Patients</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-green">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value" id="stat-on-track">--</div>
                            <div class="stat-card-label">On Track</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-yellow">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value" id="stat-needs-attention">--</div>
                            <div class="stat-card-label">Needs Attention</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-purple">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value" id="stat-this-week-patients">--</div>
                            <div class="stat-card-label">This Week</div>
                        </div>
                    </div>
                </div>

                <!-- Patients List -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h2 class="section-heading">Recent Patients</h2>
                        <input type="text" class="search-input" id="patientSearchInput" placeholder="Search patients..."
                            oninput="searchPatients(this.value)">
                    </div>
                    <div class="patients-list" id="patientsListContainer">
                        <div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading patients...
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script src="scripts/theme-toggle.js?v=8"></script>

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>
    <script src="scripts/language-toggle.js?v=8"></script>

    <script src="scripts/navigation.js?v=8"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js?v=8"></script>
    <script>
        // Session-based specialist ID — overrides the hardcoded constant in doctor-dashboard.js
        const SESSION_SPECIALIST_ID = <?php echo $sessionSpecialistId; ?>;
        const SESSION_DOCTOR_NAME = <?php echo json_encode($sessionDoctorName); ?>;
        const SESSION_DOCTOR_EMAIL = <?php echo json_encode($_SESSION['email'] ?? ''); ?>;
        const SESSION_SPECIALIZATION = <?php echo json_encode($_SESSION['specialization'] ?? 'Specialist'); ?>;
    </script>
    <script src="scripts/doctor-dashboard.js?v=19"></script>
    <script src="scripts/doctor-settings.js?v=5"></script>

</body>

</html>