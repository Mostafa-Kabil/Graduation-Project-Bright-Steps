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

// Fetch latest analyses joined with voice_sample
// Try full query first (with extended NLP columns), fall back to basic columns
$rows = [];

// Attempt 1: Full query with all extended columns
try {
    $stmt = $connect->prepare("
        SELECT
            vs.sample_id,
            vs.audio_url,
            vs.feedback       AS status,
            vs.sent_at,
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
} catch (PDOException $e) {
    // Attempt 2: Basic JOIN with only original speech_analysis columns
    try {
        $stmt = $connect->prepare("
            SELECT
                vs.sample_id,
                vs.audio_url,
                vs.feedback AS status,
                vs.sent_at,
                sa.transcript,
                sa.vocabulary_score,
                sa.clarify_score,
                sa.analyzed_at
            FROM voice_sample vs
            LEFT JOIN speech_analysis sa ON sa.sample_id = vs.sample_id
            WHERE vs.child_id = :cid
            ORDER BY vs.sent_at DESC
            LIMIT 10
        ");
        $stmt->execute(['cid' => $childId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        // Attempt 3: voice_sample only (no analysis data)
        try {
            $stmt = $connect->prepare("
                SELECT
                    vs.sample_id,
                    vs.audio_url,
                    vs.feedback AS status,
                    vs.sent_at
                FROM voice_sample vs
                WHERE vs.child_id = :cid
                ORDER BY vs.sent_at DESC
                LIMIT 10
            ");
            $stmt->execute(['cid' => $childId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e3) {
            $rows = [];
        }
    }
}

echo json_encode(['success' => true, 'analyses' => $rows]);
