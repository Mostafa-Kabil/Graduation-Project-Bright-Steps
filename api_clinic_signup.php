<?php
header('Content-Type: application/json');
include 'connection.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$clinicName = $_POST['clinic_name'] ?? '';
$email = $_POST['email'] ?? '';
$street = $_POST['street'] ?? '';
$city = $_POST['city'] ?? '';
$detailed = $_POST['detailed_location'] ?? '';
$password = $_POST['password'] ?? '';
$raw_phone = $_POST['phone'] ?? '';
$country_code = $_POST['country_code'] ?? '+20';

if (!$clinicName || !$email || !$street || !$city || !$detailed || !$password || !$raw_phone) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$location = trim("$street, $city - $detailed");

$isValid = false;
if ($country_code === '+20' && preg_match('/^1[0-9]{9}$/', $raw_phone)) $isValid = true;
else if ($country_code === '+1' && preg_match('/^[0-9]{10}$/', $raw_phone)) $isValid = true;
else if ($country_code === '+44' && preg_match('/^[0-9]{10}$/', $raw_phone)) $isValid = true;
else if ($country_code === '+966' && preg_match('/^5[0-9]{8}$/', $raw_phone)) $isValid = true;
else if ($country_code === '+971' && preg_match('/^5[0-9]{8}$/', $raw_phone)) $isValid = true;
else if ($country_code === 'other' && preg_match('/^[0-9]{8,15}$/', $raw_phone)) $isValid = true;

if (!$isValid) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number format for selected country.']);
    exit;
}

$phone = ($country_code === 'other' ? '' : $country_code) . $raw_phone;

try {
    // Check if email already exists
    $stmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A clinic with this email is already registered.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Since this is self-registration, we need to assign a default admin_id from the admin table
    // in order to avoid the "cannot be null" constraint error.
    $stmtAdmin = $connect->prepare("SELECT admin_id FROM admin LIMIT 1");
    $stmtAdmin->execute();
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    $defaultAdminId = $admin ? $admin['admin_id'] : 1; // Fallback to 1 if no admin found (though unlikely system-wise)

    // Handle file upload (Now Required)
    $docPath = null;
    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/verification/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['verification_doc']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['verification_doc']['tmp_name'], $targetFile)) {
            $docPath = 'uploads/verification/' . $fileName;
        }
    }

    if (!$docPath) {
        echo json_encode(['success' => false, 'error' => 'Verification document is required.']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO clinic (admin_id, clinic_name, email, password, location, status, rating, added_at, logo) VALUES (:aid, :name, :email, :pass, :loc, 'pending', 0.00, NOW(), :doc)");
    $stmt->execute([
        'aid' => $defaultAdminId,
        'name' => $clinicName,
        'email' => $email,
        'pass' => $hashedPassword,
        'loc' => $location,
        'doc' => $docPath // Storing the verification doc in the logo column for now if there is no specific doc column, 
                          // or let's check if there is a verification_doc column.
    ]);
    
    $clinic_id = $connect->lastInsertId();
    
    $stmtPhone = $connect->prepare("INSERT INTO clinic_phone (clinic_id, phone) VALUES (?, ?)");
    $stmtPhone->execute([$clinic_id, $phone]);
    
    // Log Activity (system level)
    $stmtLog = $connect->prepare("INSERT INTO activity_log (activity_type, description, user_name, user_role, ip_address) VALUES ('clinic_registered', :desc, 'System', 'system', :ip)");
    $stmtLog->execute([
        'desc' => "New public clinic registration submitted: {$clinicName}",
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Registration application submitted.']);
} catch (PDOException $e) {
    // In production, don't expose raw SQL error
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
