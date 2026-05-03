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
function logClinicActivity($connect, $type, $desc) {
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

        if ($action === 'stats') {
            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic");
            $totalClinics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM specialist");
            $totalSpecialists = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic WHERE status = 'verified'");
            $verified = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic WHERE status = 'pending'");
            $pending = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic WHERE status = 'suspended'");
            $suspended = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $connect->query("SELECT AVG(rating) as avg_rating FROM clinic WHERE rating > 0");
            $avgRating = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0, 1);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_clinics' => (int) $totalClinics,
                    'total_specialists' => (int) $totalSpecialists,
                    'verified' => (int) $verified,
                    'pending' => (int) $pending,
                    'suspended' => (int) $suspended,
                    'avg_rating' => $avgRating
                ]
            ]);

        } elseif ($action === 'list') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

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

            if ($status !== '' && $status !== 'all') {
                $sql .= " AND c.status = :status";
                $params['status'] = $status;
            }

            $sql .= " ORDER BY c.added_at DESC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'clinics' => $clinics]);

        } elseif ($action === 'detail') {
            $clinicId = (int) ($_GET['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            // Clinic info
            $stmt = $connect->prepare("SELECT * FROM clinic WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);
            $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$clinic) { echo json_encode(['success' => false, 'error' => 'Clinic not found']); exit; }

            // Specialists
            $stmt = $connect->prepare("
                SELECT s.specialist_id, u.first_name, u.last_name, u.email, s.specialization, s.bio
                FROM specialist s JOIN users u ON s.specialist_id = u.user_id
                WHERE s.clinic_id = :cid
            ");
            $stmt->execute(['cid' => $clinicId]);
            $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent appointments
            $appointmentCount = 0;
            try {
                $stmt = $connect->prepare("
                    SELECT COUNT(*) as total FROM appointment a
                    JOIN specialist s ON a.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :cid
                ");
                $stmt->execute(['cid' => $clinicId]);
                $appointmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {}

            echo json_encode([
                'success' => true,
                'clinic' => $clinic,
                'specialists' => $specialists,
                'appointment_count' => (int) $appointmentCount
            ]);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'approve') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            $stmt = $connect->prepare("UPDATE clinic SET status = 'verified' WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);

            $stmt2 = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $stmt2->execute(['id' => $clinicId]);
            $c = $stmt2->fetch(PDO::FETCH_ASSOC);
            $name = $c ? $c['clinic_name'] : 'Unknown';

            logClinicActivity($connect, 'clinic_verified', "Clinic verified: {$name}");
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

            // Check if email already exists
            $stmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'A clinic with this email already exists']);
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

            logClinicActivity($connect, 'clinic_registered', "New Clinic registered: {$clinicName}");
            echo json_encode(['success' => true, 'message' => 'Clinic registered']);

        } elseif ($action === 'update') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            $fields = [];
            $params = ['id' => $clinicId];

            if (isset($data['clinic_name']) && $data['clinic_name'] !== '') {
                $fields[] = "clinic_name = :name";
                $params['name'] = $data['clinic_name'];
            }
            if (isset($data['email']) && $data['email'] !== '') {
                $fields[] = "email = :email";
                $params['email'] = $data['email'];
            }
            if (isset($data['location'])) {
                $fields[] = "location = :loc";
                $params['loc'] = $data['location'];
            }
            if (isset($data['status']) && in_array($data['status'], ['pending', 'verified', 'suspended'])) {
                $fields[] = "status = :status";
                $params['status'] = $data['status'];
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                exit;
            }

            $sql = "UPDATE clinic SET " . implode(', ', $fields) . " WHERE clinic_id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $updated = $nameStmt->fetch(PDO::FETCH_ASSOC);

            logClinicActivity($connect, 'clinic_updated', "Clinic updated: " . ($updated['clinic_name'] ?? "ID #{$clinicId}"));
            echo json_encode(['success' => true, 'message' => 'Clinic updated']);

        } elseif ($action === 'reset_password') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            $newPassword = $data['new_password'] ?? '';
            if (!$clinicId || !$newPassword) { echo json_encode(['success' => false, 'error' => 'Clinic ID and new password required']); exit; }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $connect->prepare("UPDATE clinic SET password = :pw WHERE clinic_id = :id");
            $stmt->execute(['pw' => $hashedPassword, 'id' => $clinicId]);

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);

            logClinicActivity($connect, 'clinic_password_reset', "Reset password for Clinic: " . ($c['clinic_name'] ?? "#{$clinicId}"));
            echo json_encode(['success' => true, 'message' => 'Password reset']);

        } elseif ($action === 'suspend') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            $stmt = $connect->prepare("UPDATE clinic SET status = 'suspended' WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);

            logClinicActivity($connect, 'clinic_suspended', "Clinic suspended: " . ($c['clinic_name'] ?? "#{$clinicId}"));
            echo json_encode(['success' => true, 'message' => 'Clinic suspended']);

        } elseif ($action === 'reactivate') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            $stmt = $connect->prepare("UPDATE clinic SET status = 'verified' WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);

            logClinicActivity($connect, 'clinic_reactivated', "Clinic reactivated: " . ($c['clinic_name'] ?? "#{$clinicId}"));
            echo json_encode(['success' => true, 'message' => 'Clinic reactivated']);

        } elseif ($action === 'reject') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            $reason = $data['reason'] ?? 'No reason provided';
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $rejectedName = $c ? $c['clinic_name'] : "ID #{$clinicId}";

            $stmt = $connect->prepare("UPDATE clinic SET status = 'rejected' WHERE clinic_id = :id");
            $stmt->execute(['id' => $clinicId]);

            logClinicActivity($connect, 'clinic_rejected', "Clinic rejected: {$rejectedName}. Reason: {$reason}");
            echo json_encode(['success' => true, 'message' => 'Clinic signup rejected']);

        } elseif ($action === 'delete') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }

            // Check if clinic has specialists
            $stmt = $connect->prepare("SELECT COUNT(*) as c FROM specialist WHERE clinic_id = :cid");
            $stmt->execute(['cid' => $clinicId]);
            $specCount = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            if ($specCount > 0) {
                echo json_encode(['success' => false, 'error' => "Cannot delete clinic with {$specCount} specialist(s). Suspend it instead."]);
                exit;
            }

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $deletedName = $c ? $c['clinic_name'] : "ID #{$clinicId}";

            try {
                $connect->prepare("DELETE FROM clinic_phone WHERE clinic_id = :id")->execute(['id' => $clinicId]);
                $stmt = $connect->prepare("DELETE FROM clinic WHERE clinic_id = :id");
                $stmt->execute(['id' => $clinicId]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete clinic due to existing related data.']);
                exit;
            }

            logClinicActivity($connect, 'clinic_deleted', "Clinic deleted: {$deletedName}");
            echo json_encode(['success' => true, 'message' => 'Clinic deleted']);

        } elseif ($action === 'reset_password') {
            $clinicId = (int) ($data['clinic_id'] ?? 0);
            $newPassword = $data['new_password'] ?? '';
            if (!$clinicId) { echo json_encode(['success' => false, 'error' => 'Clinic ID required']); exit; }
            if (strlen($newPassword) < 8) { echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']); exit; }

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $connect->prepare("UPDATE clinic SET password = :pass WHERE clinic_id = :id");
            $stmt->execute(['pass' => $hashed, 'id' => $clinicId]);

            $nameStmt = $connect->prepare("SELECT clinic_name FROM clinic WHERE clinic_id = :id");
            $nameStmt->execute(['id' => $clinicId]);
            $c = $nameStmt->fetch(PDO::FETCH_ASSOC);

            logClinicActivity($connect, 'clinic_password_reset', "Clinic password reset: " . ($c['clinic_name'] ?? "#{$clinicId}"));
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
