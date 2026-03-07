<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $action = $_GET['action'] ?? 'stats';

    if ($action === 'stats') {
        // Growth records count
        $stmt = $connect->query("SELECT COUNT(*) as total FROM growth_record");
        $growthRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Voice samples count
        $stmt = $connect->query("SELECT COUNT(*) as total FROM voice_sample");
        $voiceSamples = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Flagged children (children with 'severe' or 'high' severity behaviors)
        $stmt = $connect->query("SELECT COUNT(DISTINCT child_id) as total FROM child_exhibited_behavior WHERE severity IN ('severe', 'high', 'critical')");
        $flaggedChildren = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total children
        $stmt = $connect->query("SELECT COUNT(*) as total FROM child");
        $totalChildren = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // On-track rate
        $onTrackRate = $totalChildren > 0 ? round((($totalChildren - $flaggedChildren) / $totalChildren) * 100) : 0;

        echo json_encode([
            'success' => true,
            'stats' => [
                'growth_records' => (int) $growthRecords,
                'voice_samples' => (int) $voiceSamples,
                'on_track_rate' => $onTrackRate,
                'flagged_children' => (int) $flaggedChildren,
                'total_children' => (int) $totalChildren
            ]
        ]);

    } elseif ($action === 'behavior_categories') {
        $stmt = $connect->query("
            SELECT bc.category_id, bc.category_name, bc.category_type,
                (SELECT COUNT(*) FROM behavior b WHERE b.category_id = bc.category_id) as behavior_count,
                (SELECT COUNT(DISTINCT ceb.child_id) FROM child_exhibited_behavior ceb 
                 JOIN behavior b2 ON ceb.behavior_id = b2.behavior_id 
                 WHERE b2.category_id = bc.category_id) as children_affected
            FROM behavior_category bc
            ORDER BY bc.category_name ASC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'categories' => $categories]);

    } elseif ($action === 'development_status') {
        // Calculate development status percentages
        $stmt = $connect->query("SELECT COUNT(*) as total FROM child");
        $totalChildren = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Children with severe/critical behaviors → "Needs Attention"
        $stmt = $connect->query("SELECT COUNT(DISTINCT child_id) as c FROM child_exhibited_behavior WHERE severity IN ('severe', 'critical')");
        $needsAttention = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

        // Children with moderate/high behaviors → "Needs Review"
        $stmt = $connect->query("SELECT COUNT(DISTINCT child_id) as c FROM child_exhibited_behavior WHERE severity IN ('moderate', 'high') AND child_id NOT IN (SELECT DISTINCT child_id FROM child_exhibited_behavior WHERE severity IN ('severe', 'critical'))");
        $needsReview = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

        $onTrack = $totalChildren - $needsAttention - $needsReview;

        $pctOnTrack = $totalChildren > 0 ? round(($onTrack / $totalChildren) * 100) : 0;
        $pctReview = $totalChildren > 0 ? round(($needsReview / $totalChildren) * 100) : 0;
        $pctAttention = $totalChildren > 0 ? round(($needsAttention / $totalChildren) * 100) : 0;

        // Ensure percentages sum to 100 if there are children
        if ($totalChildren > 0 && ($pctOnTrack + $pctReview + $pctAttention) !== 100) {
            $pctOnTrack = 100 - $pctReview - $pctAttention;
        }

        echo json_encode([
            'success' => true,
            'development_status' => [
                'on_track' => ['count' => $onTrack, 'percentage' => $pctOnTrack],
                'needs_review' => ['count' => $needsReview, 'percentage' => $pctReview],
                'needs_attention' => ['count' => $needsAttention, 'percentage' => $pctAttention]
            ]
        ]);

    } elseif ($action === 'export') {
        // Export report data as CSV-ready JSON
        $period = $_GET['period'] ?? '30';
        $interval = (int) $period;

        $stmt = $connect->prepare("
            SELECT gr.record_id, c.first_name, c.last_name, gr.height, gr.weight, gr.head_circumference, gr.recorded_at
            FROM growth_record gr
            JOIN child c ON gr.child_id = c.child_id
            WHERE gr.recorded_at >= NOW() - INTERVAL :days DAY
            ORDER BY gr.recorded_at DESC
        ");
        $stmt->execute(['days' => $interval]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'records' => $records]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
