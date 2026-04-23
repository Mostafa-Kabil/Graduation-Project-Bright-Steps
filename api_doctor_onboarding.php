<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

// Suppress PHP warnings/notices from appearing before JSON
error_reporting(E_ERROR);

if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$doctorId = intval($_SESSION['id']);
$specialistId = intval($_SESSION['specialist_id'] ?? $_SESSION['id']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Ensure table exists (outside of any transaction)
try {
    $connect->exec("CREATE TABLE IF NOT EXISTS `doctor_onboarding` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `doctor_id` INT NOT NULL,
        `specialization` VARCHAR(100),
        `experience_years` INT DEFAULT 0,
        `certifications` VARCHAR(255),
        `certificate_path` VARCHAR(500) DEFAULT NULL,
        `focus_areas` TEXT,
        `working_days` TEXT,
        `start_time` TIME DEFAULT '09:00:00',
        `end_time` TIME DEFAULT '17:00:00',
        `consultation_types` TEXT,
        `goals` TEXT,
        `completed_at` TIMESTAMP DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Add certificate_path column if table already exists without it
    try {
        $connect->exec("ALTER TABLE `doctor_onboarding` ADD COLUMN `certificate_path` VARCHAR(500) DEFAULT NULL AFTER `certifications`");
    } catch (Exception $e) {
        // Column already exists — fine
    }
} catch (Exception $e) {
    // Table might already exist — that's fine
}

// Upload directory
define('CERT_UPLOAD_DIR', __DIR__ . '/uploads/certificates/');
define('CERT_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('CERT_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);
define('CERT_ALLOWED_EXTS', ['jpg', 'jpeg', 'png', 'pdf']);

switch ($action) {
    case 'save':
        // FormData (multipart) — fields come from $_POST, file from $_FILES
        $specialization = trim($_POST['specialization'] ?? '');
        $experienceYears = intval($_POST['experience_years'] ?? 0);
        $certifications = trim($_POST['certifications'] ?? '');
        $focusAreas = $_POST['focus_areas'] ?? '[]';
        $workingDays = $_POST['working_days'] ?? '[]';
        $startTime = trim($_POST['start_time'] ?? '09:00');
        $endTime = trim($_POST['end_time'] ?? '17:00');
        $consultationTypes = $_POST['consultation_types'] ?? '[]';
        $goals = $_POST['goals'] ?? '[]';

        if (empty($specialization)) {
            echo json_encode(['error' => 'Specialization is required.']);
            exit();
        }

        // ── Handle Certificate File Upload ──────────────────
        $certificatePath = null;

        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['certificate'];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
                    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                ];
                $errMsg = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
                echo json_encode(['error' => $errMsg]);
                exit();
            }

            // Validate file size
            if ($file['size'] > CERT_MAX_SIZE) {
                echo json_encode(['error' => 'Certificate file is too large. Maximum size is 5MB.']);
                exit();
            }

            // Validate file type (by extension and MIME)
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($file['tmp_name']);

            if (!in_array($ext, CERT_ALLOWED_EXTS)) {
                echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
                exit();
            }

            if (!in_array($mime, CERT_ALLOWED_TYPES)) {
                echo json_encode(['error' => 'Invalid file content. The file does not match its extension.']);
                exit();
            }

            // Create upload directory if it doesn't exist
            if (!is_dir(CERT_UPLOAD_DIR)) {
                if (!mkdir(CERT_UPLOAD_DIR, 0755, true)) {
                    echo json_encode(['error' => 'Failed to create upload directory.']);
                    exit();
                }
            }

            // Generate unique filename
            $uniqueName = 'cert_' . $doctorId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = CERT_UPLOAD_DIR . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['error' => 'Failed to save uploaded file.']);
                exit();
            }

            // Store relative path for DB
            $certificatePath = 'uploads/certificates/' . $uniqueName;
        }

        try {
            // 1. Check if already onboarded — prevent duplicates
            $checkStmt = $connect->prepare("SELECT id FROM doctor_onboarding WHERE doctor_id = ? LIMIT 1");
            $checkStmt->execute([$doctorId]);
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode(['success' => true, 'redirect' => 'doctor-dashboard.php']);
                exit();
            }

            // 2. Save onboarding data
            $stmt = $connect->prepare("INSERT INTO doctor_onboarding 
                (doctor_id, specialization, experience_years, certifications, certificate_path, focus_areas, working_days, start_time, end_time, consultation_types, goals) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $doctorId,
                $specialization,
                $experienceYears,
                $certifications,
                $certificatePath,
                $focusAreas,
                $workingDays,
                $startTime,
                $endTime,
                $consultationTypes,
                $goals
            ]);

            // 3. Update the specialist table with the new info (optional)
            try {
                $updateStmt = $connect->prepare("UPDATE specialist SET 
                    specialization = ?, 
                    experience_years = ?, 
                    certificate_of_experience = ? 
                    WHERE specialist_id = ?");
                $updateStmt->execute([
                    $specialization,
                    $experienceYears,
                    $certifications,
                    $specialistId
                ]);
                $_SESSION['specialization'] = $specialization;
            } catch (Exception $e) {
                // Optional — don't fail
            }

            // 4. Try to save availability slots (optional)
            try {
                $daysArray = json_decode($workingDays, true) ?? [];
                $clinicStmt = $connect->prepare("SELECT clinic_id FROM specialist WHERE specialist_id = ? LIMIT 1");
                $clinicStmt->execute([$specialistId]);
                $clinicRow = $clinicStmt->fetch(PDO::FETCH_ASSOC);
                $clinicId = $clinicRow ? intval($clinicRow['clinic_id']) : 0;

                if ($clinicId && !empty($daysArray)) {
                    $delStmt = $connect->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?");
                    $delStmt->execute([$specialistId]);

                    $slotStmt = $connect->prepare("INSERT INTO appointment_slots (doctor_id, clinic_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                    foreach ($daysArray as $day) {
                        $slotStmt->execute([$specialistId, $clinicId, intval($day), $startTime, $endTime]);
                    }
                }
            } catch (Exception $e) {
                // Optional — don't fail
            }

            echo json_encode(['success' => true, 'redirect' => 'doctor-dashboard.php']);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to save: ' . $e->getMessage()]);
        }
        break;

    case 'check':
        $hasCompleted = false;
        try {
            $stmt = $connect->prepare("SELECT id FROM doctor_onboarding WHERE doctor_id = ? LIMIT 1");
            $stmt->execute([$doctorId]);
            $hasCompleted = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        } catch (Exception $e) {
            $hasCompleted = false;
        }

        // Also check if specialist already has specialization set
        if (!$hasCompleted) {
            try {
                $specStmt = $connect->prepare("SELECT specialization, experience_years FROM specialist WHERE specialist_id = ? LIMIT 1");
                $specStmt->execute([$specialistId]);
                $specData = $specStmt->fetch(PDO::FETCH_ASSOC);
                if ($specData && !empty($specData['specialization']) && intval($specData['experience_years']) > 0) {
                    $hasCompleted = true;
                }
            } catch (Exception $e) {}
        }

        echo json_encode(['completed' => $hasCompleted]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
