<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$senderId = $_SESSION['id'];
$receiverId = $_POST['receiver_id'] ?? null;
$childId = $_POST['child_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$content = $content !== '' ? $content : null;

if (!$receiverId || (!$content && empty($_FILES['attachment']))) {
    echo json_encode(['error' => 'Receiver ID and either content or attachment are required']);
    exit();
}

$filePath = null;

// Handle file upload
if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/messages/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileInfo = pathinfo($_FILES['attachment']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['error' => 'Invalid file type. Allowed: jpg, png, pdf, doc, docx']);
        exit();
    }
    
    if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) { // 5MB
        echo json_encode(['error' => 'File size exceeds 5MB limit']);
        exit();
    }
    
    $newFileName = uniqid('msg_') . '.' . $ext;
    $destination = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
        $filePath = 'uploads/messages/' . $newFileName;
    } else {
        echo json_encode(['error' => 'Failed to save file']);
        exit();
    }
}

// Check if active appointment exists and get its ID
try {
    $checkAppt = $connect->prepare("
        SELECT appointment_id 
        FROM appointment 
        WHERE ((parent_id = :uid1 AND specialist_id = :uid2) 
           OR (parent_id = :uid2 AND specialist_id = :uid1))
          AND status IN ('confirmed', 'completed', 'approved')
        ORDER BY scheduled_at DESC, appointment_id DESC
        LIMIT 1
    ");
    $checkAppt->execute([':uid1' => $senderId, ':uid2' => $receiverId]);
    $activeAppointment = $checkAppt->fetch(PDO::FETCH_ASSOC);

    if (!$activeAppointment) {
        http_response_code(403);
        echo json_encode(['error' => 'No active appointment exists between you and this user. Message blocked.']);
        exit();
    }
    $appointmentId = $activeAppointment['appointment_id'];
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error during appointment validation: ' . $e->getMessage()]);
    exit();
}

// Google Meet Link processing & Zoom/Teams link rejection
$meetingLink = null;
if ($content !== null) {
    // Zoom/Teams rejection
    if (preg_match('/zoom\.(us|com)/i', $content) || preg_match('/teams\.(microsoft|live)\.com/i', $content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only Google Meet links are allowed. Zoom and Teams links are not permitted.']);
        exit();
    }

    // Google Meet extraction
    if (preg_match('/(?:https?:\/\/)?meet\.google\.com\/[a-z0-9\-]+/i', $content, $matches)) {
        $meetingLink = $matches[0];
        if (!preg_match('/^https?:\/\//i', $meetingLink)) {
            $meetingLink = 'https://' . $meetingLink;
        }
    }
}

try {
    $stmt = $connect->prepare("
        INSERT INTO message (sender_id, receiver_id, child_id, appointment_id, meeting_link, content, file_path) 
        VALUES (:sender, :receiver, :child, :appointment, :meeting_link, :content, :file)
    ");
    
    $stmt->execute([
        ':sender' => $senderId,
        ':receiver' => $receiverId,
        ':child' => $childId ?: null,
        ':appointment' => $appointmentId,
        ':meeting_link' => $meetingLink,
        ':content' => $content,
        ':file' => $filePath
    ]);
    
    $messageId = $connect->lastInsertId();

    // Notify doctor when a parent sends a message
    if (($_SESSION['role'] ?? '') === 'parent') {
        try {
            require_once __DIR__ . '/includes/doctor_notifications.php';
            $senderStmt = $connect->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
            $senderStmt->execute([$senderId]);
            $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);
            $parentName = trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? ''));
            $preview = $content ? substr($content, 0, 80) : 'Sent an attachment';
            if (strlen((string) $content) > 80) {
                $preview .= '…';
            }
            doctor_notify(
                $connect,
                (int) $receiverId,
                'new_message',
                'New Message' . ($parentName ? " from {$parentName}" : ''),
                $preview
            );
        } catch (Exception $e) { /* non-critical */ }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'message_id' => $messageId,
        'file_path' => $filePath
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
