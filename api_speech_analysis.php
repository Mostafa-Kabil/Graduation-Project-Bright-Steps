<?php
// api_speech_analysis.php
// Accepts audio upload + child_id + optional mode, forwards to Python FastAPI, saves results to DB
header('Content-Type: application/json');
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$childId    = isset($_POST['child_id']) ? (int) $_POST['child_id'] : 0;
$mode       = in_array($_POST['mode'] ?? '', ['free_talk', 'read_compare']) ? $_POST['mode'] : 'free_talk';
$targetText = trim($_POST['target_text'] ?? '');

if (!$childId) {
    echo json_encode(['success' => false, 'error' => 'child_id required']);
    exit();
}

// Validate child belongs to logged-in parent
$stmt = $connect->prepare("SELECT child_id, birth_day, birth_month, birth_year FROM child WHERE child_id = :cid AND parent_id = :pid");
$stmt->execute(['cid' => $childId, 'pid' => $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    echo json_encode(['success' => false, 'error' => 'Child not found']);
    exit();
}

// Calculate child age in months
$bd        = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
$ageMonths = (int) floor((time() - $bd) / (30.44 * 86400));
$ageMonths = max(12, min(72, $ageMonths));

// Validate uploaded audio
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No audio file uploaded']);
    exit();
}

// Save audio
$uploadDir = __DIR__ . '/uploads/speech/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext      = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION) ?: 'webm';
$filename = 'speech_' . $childId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$filePath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save audio file']);
    exit();
}

// ── Start Python server if not running ────────────────────────────────────
function isPythonServerRunning($port = 8000) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($fp) { fclose($fp); return true; }
    return false;
}

if (!isPythonServerRunning(8000)) {
    $scriptDir = realpath(__DIR__ . '/APIs/Speech Analysis');
    if ($scriptDir) {
        pclose(popen('cd "' . $scriptDir . '" && start /B python -m uvicorn app:app --port 8000 > NUL 2> NUL', 'r'));
        $maxWait = 20;
        while (!isPythonServerRunning(8000) && $maxWait > 0) {
            usleep(500000);
            $maxWait--;
        }
    }
}

// ── Choose endpoint based on mode ─────────────────────────────────────────
if ($mode === 'read_compare' && $targetText !== '') {
    $apiUrl     = 'http://127.0.0.1:8000/analyze-compare';
    $postFields = ['audio' => new CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
                   'age'   => $ageMonths,
                   'target_text' => $targetText];
} else {
    $mode       = 'free_talk'; // normalise
    $apiUrl     = 'http://127.0.0.1:8000/analyze';
    $postFields = ['audio' => new CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
                   'age'   => $ageMonths];
}

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
]);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    @unlink($filePath);
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Failed',
                     'Could not complete the analysis. Please run start-server.bat in APIs/Speech Analysis folder.']);
    echo json_encode([
        'success' => false,
        'error'   => 'Speech AI is offline. Run "APIs/Speech Analysis/start-server.bat" to start the speech service.',
        'details' => $curlError ?: "HTTP $httpCode",
    ]);
    exit();
}

$aiResult = json_decode($response, true);
if (!$aiResult || !isset($aiResult['transcript'])) {
    @unlink($filePath);
    echo json_encode(['success' => false, 'error' => 'Invalid response from speech API']);
    exit();
}

// Map status to clarify_score (0–1)
$statusMap = [
    'Within expected range' => 1.00,
    'Above expected range'  => 1.20,
    'Below expected range'  => 0.50,
];
$clarifyScore = $statusMap[$aiResult['status']] ?? 0.75;
$vocabScore   = (float) ($aiResult['vocab_size'] ?? 0);
$matchScore   = isset($aiResult['match_score']) ? (float) $aiResult['match_score'] : null;

// Extract enhanced NLP metrics (with graceful fallback for older API versions)
$sentenceComplexity = $aiResult['sentence_complexity'] ?? [];
$wordComplexity = $aiResult['word_complexity'] ?? [];
$readabilityScores = $aiResult['readability_scores'] ?? [];
$developmentalFeedback = $aiResult['developmental_feedback'] ?? null;
$overallDevScore = isset($aiResult['overall_development_score']) ? (float) $aiResult['overall_development_score'] : null;

// ── Ensure schema has required columns (outside transaction) ──────────────
try {
    // Test if voice_sample has 'mode' column
    $connect->query("SELECT `mode` FROM voice_sample LIMIT 0");
} catch (PDOException $e) {
    try { $connect->exec("ALTER TABLE voice_sample ADD COLUMN `mode` VARCHAR(20) DEFAULT 'free_talk'"); } catch (Exception $e2) {}
    try { $connect->exec("ALTER TABLE voice_sample ADD COLUMN `target_text` TEXT DEFAULT NULL"); } catch (Exception $e2) {}
}

try {
    // Test if speech_analysis has extended columns
    $connect->query("SELECT `match_score` FROM speech_analysis LIMIT 0");
} catch (PDOException $e) {
    $extraCols = [
        'match_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'sentence_count' => 'INT DEFAULT NULL',
        'avg_sentence_length' => 'DECIMAL(5,2) DEFAULT NULL',
        'sentence_complexity_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'avg_word_length' => 'DECIMAL(5,2) DEFAULT NULL',
        'avg_syllables_per_word' => 'DECIMAL(5,2) DEFAULT NULL',
        'polysyllabic_word_count' => 'INT DEFAULT NULL',
        'flesch_reading_ease' => 'DECIMAL(6,2) DEFAULT NULL',
        'flesch_kincaid_grade' => 'DECIMAL(5,2) DEFAULT NULL',
        'overall_development_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'developmental_feedback' => 'TEXT DEFAULT NULL',
    ];
    foreach ($extraCols as $col => $def) {
        try { $connect->exec("ALTER TABLE speech_analysis ADD COLUMN `$col` $def"); } catch (Exception $e2) {}
    }
}

try {
    $connect->beginTransaction();

    $stmt = $connect->prepare(
        "INSERT INTO voice_sample (child_id, feedback, audio_url, mode, target_text)
         VALUES (:cid, :fb, :url, :mode, :target)"
    );
    $stmt->execute([
        'cid'    => $childId,
        'fb'     => $aiResult['status'],
        'url'    => 'uploads/speech/' . $filename,
        'mode'   => $mode,
        'target' => $targetText ?: null,
    ]);
    $sampleId = (int) $connect->lastInsertId();

    $saParams = [
        'sid' => $sampleId,
        'tr'  => $aiResult['transcript'],
        'vs'  => $vocabScore,
        'cs'  => $clarifyScore,
        'ms'  => $matchScore,
        'sc'   => $sentenceComplexity['sentence_count'] ?? null,
        'asl'  => $sentenceComplexity['avg_sentence_length'] ?? null,
        'scs'  => $sentenceComplexity['complexity_score'] ?? null,
        'awl'  => $wordComplexity['avg_word_length'] ?? null,
        'aspw' => $wordComplexity['avg_syllables_per_word'] ?? null,
        'pwc'  => $wordComplexity['polysyllabic_word_count'] ?? null,
        'fre'  => $readabilityScores['flesch_reading_ease'] ?? null,
        'fkg'  => $readabilityScores['flesch_kincaid_grade'] ?? null,
        'ods'  => $overallDevScore,
        'df'   => $developmentalFeedback ? json_encode($developmentalFeedback) : null,
    ];

    $fullSql = "INSERT INTO speech_analysis (
            sample_id, transcript, vocabulary_score, clarify_score, match_score,
            sentence_count, avg_sentence_length, sentence_complexity_score,
            avg_word_length, avg_syllables_per_word, polysyllabic_word_count,
            flesch_reading_ease, flesch_kincaid_grade,
            overall_development_score, developmental_feedback
         ) VALUES (
            :sid, :tr, :vs, :cs, :ms,
            :sc, :asl, :scs,
            :awl, :aspw, :pwc,
            :fre, :fkg,
            :ods, :df
         )";

    try {
        $stmt = $connect->prepare($fullSql);
        $stmt->execute($saParams);
    } catch (PDOException $saErr) {
        // Fallback: basic insert with original columns only
        $stmt = $connect->prepare("INSERT INTO speech_analysis (sample_id, transcript, vocabulary_score, clarify_score) VALUES (:sid, :tr, :vs, :cs)");
        $stmt->execute(['sid' => $sampleId, 'tr' => $aiResult['transcript'], 'vs' => $vocabScore, 'cs' => $clarifyScore]);
    }

    $wordCount = count($aiResult['unique_words'] ?? []);
    $modeLabel = $mode === 'read_compare' ? 'Read & Compare' : 'Free Talk';
    $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
    $nstmt->execute([$parentId, 'Speech Analysis Complete',
                     "$modeLabel session complete. $wordCount words detected."
                     . ($matchScore !== null ? " Match score: {$matchScore}%." : '')]);

    $connect->commit();
} catch (Exception $e) {
    $connect->rollBack();
    @unlink($filePath);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

echo json_encode([
    'success'      => true,
    'sample_id'    => $sampleId,
    'mode'         => $mode,
    'transcript'   => $aiResult['transcript'],
    'vocab_size'   => $aiResult['vocab_size'],
    'unique_words' => $aiResult['unique_words'] ?? [],
    'expected_vocab' => $aiResult['expected_vocab'],
    'status'       => $aiResult['status'],
    'age_months'   => $ageMonths,
    'match_score'  => $matchScore,
    'word_hits'    => $aiResult['word_hits'] ?? [],
    'word_misses'  => $aiResult['word_misses'] ?? [],
    'message'      => 'Analysis complete! ' . count($aiResult['unique_words'] ?? []) . ' unique words detected.'
                      . ($matchScore !== null ? " Match: {$matchScore}%." : ''),
]);
