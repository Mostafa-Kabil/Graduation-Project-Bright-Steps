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

try {
    $stmt = $connect->prepare("
        INSERT INTO message (sender_id, receiver_id, child_id, content, file_path) 
        VALUES (:sender, :receiver, :child, :content, :file)
    ");
    
    $stmt->execute([
        ':sender' => $senderId,
        ':receiver' => $receiverId,
        ':child' => $childId ?: null,
        ':content' => $content,
        ':file' => $filePath
    ]);
    
    $messageId = $connect->lastInsertId();
    
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
