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

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'stats') {
            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic");
            $totalClinics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM specialist");
            $totalSpecialists = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic WHERE status = 'verified'");
            $verified = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_clinics' => (int) $totalClinics,
                    'total_specialists' => (int) $totalSpecialists,
                    'verified' => (int) $verified
                ]
            ]);

        } elseif ($action === 'list') {
            $search = $_GET['search'] ?? '';

            $sql = "SELECT c.clinic_id, c.clinic_name, c.email, c.location, c.status, c.rating, c.added_at,
                    (SELECT COUNT(*) FROM specialist s WHERE s.clinic_id = c.clinic_id) as specialist_count,
                    (SELECT COUNT(DISTINCT a.parent_id) FROM appointment a 
                     JOIN specialist s2 ON a.specialist_id = s2.specialist_id 
                     WHERE s2.clinic_id = c.clinic_id) as patient_count
                    FROM clinic c WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (c.clinic_name LIKE :search OR c.email LIKE :search2 OR c.location LIKE :search3)";
                $params['search'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            }

            $sql .= " ORDER BY c.added_at DESC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'clinics' => $clinics]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'approve') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) {
                echo json_encode(['success' => false, 'error' => 'Clinic ID required']);
                exit;
            }

            $stmt = $connect->prepare("UPDATE clinic SET status = 'verified' WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);

            // Log activity
            $stmt2 = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $stmt2->execute(['id' => $clinicId]);
            $c = $stmt2->fetch(PDO::FETCH_ASSOC);
            $name = $c ? $c['clinic_name'] : 'Unknown';

            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description) VALUES ('clinic_verified', :desc)");
            $logStmt->execute(['desc' => "Clinic verified: {$name}"]);

            echo json_encode(['success' => true, 'message' => 'Clinic approved']);

        } elseif ($action === 'register') {
            $clinicName = $data['clinic_name'] ?? '';
            $email = $data['email'] ?? '';
            $location = $data['location'] ?? '';
            $password = $data['password'] ?? '';

            if (!$clinicName || !$email) {
                echo json_encode(['success' => false, 'error' => 'Clinic name and email required']);
                exit;
            }

            $hashedPassword = password_hash($password ?: 'default123', PASSWORD_DEFAULT);
            $adminId = $_SESSION['id'];

            $stmt = $connect->prepare("INSERT INTO clinic (admin_id, clinic_name, email, password, location, status, rating) VALUES (:aid, :name, :email, :pass, :loc, 'pending', 0.00)");
            $stmt->execute([
                'aid' => $adminId,
                'name' => $clinicName,
                'email' => $email,
                'pass' => $hashedPassword,
                'loc' => $location
            ]);

            $logStmt = $connect->prepare("INSERT INTO activity_log (activity_type, description) VALUES ('clinic_registered', :desc)");
            $logStmt->execute(['desc' => "New Clinic registered: {$clinicName}"]);

            echo json_encode(['success' => true, 'message' => 'Clinic registered']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
