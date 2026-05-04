<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper: log activity
function logSpecialistActivity($connect, $type, $desc) {
    $adminId = $_SESSION['id'] ?? null;
    $userName = '';
    if ($adminId) {
        $s = $connect->prepare("SELECT first_name, last_name FROM users WHERE user_id = :id");
        $s->execute(['id' => $adminId]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u) $userName = $u['first_name'] . ' ' . $u['last_name'];
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $connect->prepare("INSERT INTO activity_log (activity_type, description, user_name, user_role, ip_address) VALUES (:type, :desc, :uname, 'admin', :ip)");
    $stmt->execute(['type' => $type, 'desc' => $desc, 'uname' => $userName, 'ip' => $ip]);
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? ''; // we need status in specialist? Wait, does specialist have status?

            // Let's check if specialist has a status or if it's based on user.
            // Wait, specialist is linked to users table? No, wait. 
            // `specialist` table in `grad.sql` has: specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, experience_years, created_at.
            // But is there a status?
            
            // Let's query
            $sql = "SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, s.experience_years, s.certificate_of_experience, s.created_at, c.clinic_name, u.email, u.status 
                    FROM specialist s 
                    LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                    LEFT JOIN users u ON s.specialist_id = u.user_id 
                    WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search2 OR c.clinic_name LIKE :search3 OR u.email LIKE :search4)";
                $params['search'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
                $params['search4'] = "%$search%";
            }

            if ($status !== '' && $status !== 'all') {
                $sql .= " AND u.status = :status";
                $params['status'] = $status;
            }

            $sql .= " ORDER BY s.created_at DESC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'specialists' => $specialists]);

        } elseif ($action === 'detail') {
            $specialistId = (int) ($_GET['specialist_id'] ?? 0);
            if (!$specialistId) { echo json_encode(['success' => false, 'error' => 'Specialist ID required']); exit; }

            $stmt = $connect->prepare("SELECT s.*, c.clinic_name, u.email, u.status 
                FROM specialist s 
                LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                LEFT JOIN users u ON s.specialist_id = u.user_id
                WHERE s.specialist_id = :id");
            $stmt->execute(['id' => $specialistId]);
            $specialist = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$specialist) { echo json_encode(['success' => false, 'error' => 'Specialist not found']); exit; }

            echo json_encode([
                'success' => true,
                'specialist' => $specialist
            ]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'approve') {
            $specialistId = (int) ($data['specialist_id'] ?? 0);
            if (!$specialistId) { echo json_encode(['success' => false, 'error' => 'Specialist ID required']); exit; }

            // Update user status - specialists may have role='doctor' or 'specialist'
            $stmt = $connect->prepare("UPDATE users SET status = 'active' WHERE user_id = :id AND user_id IN (SELECT specialist_id FROM specialist)");
            $stmt->execute(['id' => $specialistId]);

            logSpecialistActivity($connect, 'specialist_verified', "Specialist verified: ID #{$specialistId}");
            echo json_encode(['success' => true, 'message' => 'Specialist approved']);

        } elseif ($action === 'reject') {
            $specialistId = (int) ($data['specialist_id'] ?? 0);
            if (!$specialistId) { echo json_encode(['success' => false, 'error' => 'Specialist ID required']); exit; }

            $stmt = $connect->prepare("UPDATE users SET status = 'rejected' WHERE user_id = :id AND user_id IN (SELECT specialist_id FROM specialist)");
            $stmt->execute(['id' => $specialistId]);

            logSpecialistActivity($connect, 'specialist_rejected', "Specialist rejected: ID #{$specialistId}");
            echo json_encode(['success' => true, 'message' => 'Specialist signup rejected']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
