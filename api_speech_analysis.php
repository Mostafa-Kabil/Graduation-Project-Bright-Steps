<?php
// api_speech_analysis.php
// Accepts audio upload + child_id, forwards to Python FastAPI, saves results to DB
header('Content-Type: application/json');
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$childId = isset($_POST['child_id']) ? (int) $_POST['child_id'] : 0;

// Validate child belongs to logged-in parent
if (!$childId) {
    echo json_encode(['success' => false, 'error' => 'child_id required']);
    exit();
}

$stmt = $connect->prepare("SELECT child_id, birth_day, birth_month, birth_year FROM child WHERE child_id = :cid AND parent_id = :pid");
$stmt->execute(['cid' => $childId, 'pid' => $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    echo json_encode(['success' => false, 'error' => 'Child not found']);
    exit();
}

// Calculate child age in months
$bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
$ageMonths = (int) floor((time() - $bd) / (30.44 * 86400));
// Clamp to valid range for the API (12–72 months)
$ageMonths = max(12, min(72, $ageMonths));

// Validate uploaded audio file
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No audio file uploaded']);
    exit();
}

// Save audio file to uploads/speech/
$uploadDir = __DIR__ . '/uploads/speech/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION) ?: 'webm';
$filename = 'speech_' . $childId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$filePath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save audio file']);
    exit();
}

// Check if Python server is running, and start it if it isn't
function isPythonServerRunning($port = 8000) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

if (!isPythonServerRunning(8000)) {
    // Start the Python server in the background (Windows)
    $scriptDir = realpath(__DIR__ . '/APIs/Speech Analysis');
    if ($scriptDir) {
        pclose(popen('cd "' . $scriptDir . '" && start /B python -m uvicorn app:app --port 8000 > NUL 2> NUL', 'r'));
        // Wait up to 10 seconds for it to start
        $maxWait = 20;
        while (!isPythonServerRunning(8000) && $maxWait > 0) {
            usleep(500000); // 0.5s
            $maxWait--;
        }
    }
}

// Forward to Python FastAPI at localhost:8000/analyze
$apiUrl = 'http://127.0.0.1:8000/analyze';
$curlFile = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['audio' => $curlFile, 'age' => $ageMonths],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    // Clean up saved file on failure
    @unlink($filePath);

    // Insert Failure Notification
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Failed', 'Could not complete the analysis. Please run start-server.bat in APIs/Speech Analysis folder.']);

    echo json_encode([
        'success' => false,
        'error'   => 'Speech AI is offline. To fix this: Run the file "APIs/Speech Analysis/start-server.bat" to start the speech service.',
        'details' => $curlError ?: "HTTP $httpCode",
        'fix'     => 'Navigate to APIs/Speech Analysis folder and double-click start-server.bat'
    ]);
    exit();
}

$aiResult = json_decode($response, true);
if (!$aiResult || !isset($aiResult['transcript'])) {
    @unlink($filePath);
    // Insert Failure Notification
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Failed', 'Invalid response from speech API.']);
    echo json_encode(['success' => false, 'error' => 'Invalid response from speech API']);
    exit();
}

// Map status to clarify_score (0–1 range)
$statusMap = [
    'Within expected range' => 1.00,
    'Above expected range'  => 1.20,
    'Below expected range'  => 0.50,
];
$clarifyScore = $statusMap[$aiResult['status']] ?? 0.75;
$vocabScore   = (float) ($aiResult['vocab_size'] ?? 0);

try {
    $connect->beginTransaction();

    // Insert voice_sample row
    $stmt = $connect->prepare(
        "INSERT INTO voice_sample (child_id, feedback, audio_url) VALUES (:cid, :fb, :url)"
    );
    $stmt->execute([
        'cid' => $childId,
        'fb'  => $aiResult['status'],
        'url' => 'uploads/speech/' . $filename,
    ]);
    $sampleId = (int) $connect->lastInsertId();

    // Insert speech_analysis row (sample_id is FK → voice_sample.sample_id)
    $stmt = $connect->prepare(
        "INSERT INTO speech_analysis (sample_id, transcript, vocabulary_score, clarify_score)
         VALUES (:sid, :tr, :vs, :cs)"
    );
    $stmt->execute([
        'sid' => $sampleId,
        'tr'  => $aiResult['transcript'],
        'vs'  => $vocabScore,
        'cs'  => $clarifyScore,
    ]);

    // Insert Success Notification
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Complete', 'Your child\'s speech has been transcribed and analyzed. (' . count($aiResult['unique_words'] ?? []) . ' words detected)']);

    $connect->commit();
} catch (Exception $e) {
    $connect->rollBack();
    @unlink($filePath);
    // Insert Failure Notification
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Failed', 'Database error occurred.']);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Return full result to frontend
echo json_encode([
    'success'        => true,
    'sample_id'      => $sampleId,
    'transcript'     => $aiResult['transcript'],
    'vocab_size'     => $aiResult['vocab_size'],
    'unique_words'   => $aiResult['unique_words'] ?? [],
    'expected_vocab' => $aiResult['expected_vocab'],
    'status'         => $aiResult['status'],
    'age_months'     => $ageMonths,
    'message'        => 'Analysis complete! ' . count($aiResult['unique_words'] ?? []) . ' unique words detected.',
]);
