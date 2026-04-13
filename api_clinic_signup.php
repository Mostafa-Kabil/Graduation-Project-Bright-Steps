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
$location = $_POST['location'] ?? '';

if (!$clinicName || !$email || !$location) {
    echo json_encode(['success' => false, 'error' => 'Clinic Name, Email, and Location are required.']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $connect->prepare("SELECT clinic_id FROM clinic WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A clinic with this email is already registered.']);
        exit;
    }

    // Default password for newly self-registered accounts (or they can reset it later, or an admin assigns it)
    $hashedPassword = password_hash('clinic1234', PASSWORD_DEFAULT);

    /* 
     Handle file upload if provided.
     In a real production environment, you would validate MIME types securely and move the file into a /uploads/verification/ folder.
    */
    $docPath = null;
    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/verification/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['verification_doc']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['verification_doc']['tmp_name'], $targetFile)) {
            $docPath = 'uploads/verification/' . $fileName;
        }
    }

    // Since this is self-registration, admin_id is NULL (will be assigned upon approval)
    $stmt = $connect->prepare("INSERT INTO clinic (admin_id, clinic_name, email, password, location, status, rating, added_at) VALUES (NULL, :name, :email, :pass, :loc, 'pending', 0.00, NOW())");
    $stmt->execute([
        'name' => $clinicName,
        'email' => $email,
        'pass' => $hashedPassword,
        'loc' => $location
    ]);
    
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
