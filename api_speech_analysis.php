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
    echo json_encode([
        'success' => false,
        'error'   => 'Speech analysis service unavailable. Make sure the Python server is running on port 8000.',
        'details' => $curlError ?: "HTTP $httpCode"
    ]);
    exit();
}

$aiResult = json_decode($response, true);
if (!$aiResult || !isset($aiResult['transcript'])) {
    @unlink($filePath);
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

    $connect->commit();
} catch (Exception $e) {
    $connect->rollBack();
    @unlink($filePath);
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
