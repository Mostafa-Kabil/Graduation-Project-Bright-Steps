<?php
// api_speech_history.php
// Returns last 10 speech analyses for a child belonging to the logged-in parent
header('Content-Type: application/json');
require_once 'includes/auth_check.php';

$childId = isset($_GET['child_id']) ? (int) $_GET['child_id'] : 0;

if (!$childId) {
    echo json_encode(['success' => false, 'error' => 'child_id required']);
    exit();
}

// Verify child belongs to parent
$stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = :cid AND parent_id = :pid");
$stmt->execute(['cid' => $childId, 'pid' => $parentId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Fetch latest analyses joined with voice_sample (includes enhanced NLP metrics)
$stmt = $connect->prepare("
    SELECT
        vs.sample_id,
        vs.audio_url,
        vs.feedback       AS status,
        vs.sent_at,
        vs.mode,
        vs.target_text,
        sa.transcript,
        sa.vocabulary_score,
        sa.clarify_score,
        sa.match_score,
        sa.sentence_count,
        sa.avg_sentence_length,
        sa.sentence_complexity_score,
        sa.avg_word_length,
        sa.avg_syllables_per_word,
        sa.polysyllabic_word_count,
        sa.flesch_reading_ease,
        sa.flesch_kincaid_grade,
        sa.overall_development_score,
        sa.developmental_feedback,
        sa.analyzed_at
    FROM voice_sample vs
    LEFT JOIN speech_analysis sa ON sa.sample_id = vs.sample_id
    WHERE vs.child_id = :cid
    ORDER BY vs.sent_at DESC
    LIMIT 10
");
$stmt->execute(['cid' => $childId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'analyses' => $rows]);
