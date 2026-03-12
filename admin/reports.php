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
        // Export report data as JSON (client-side will convert to PDF/Excel/CSV)
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

    } elseif ($action === 'children_list') {
        // Get list of children for dropdown filter
        $stmt = $connect->query("
            SELECT c.child_id, c.first_name, c.last_name, 
                   CONCAT(u.first_name, ' ', u.last_name) as parent_name
            FROM child c
            JOIN parent p ON c.parent_id = p.parent_id
            JOIN users u ON p.parent_id = u.user_id
            ORDER BY c.first_name ASC
        ");
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'children' => $children]);

    } elseif ($action === 'specialists_list') {
        // Get list of specialists for dropdown filter
        $stmt = $connect->query("
            SELECT s.specialist_id, 
                   CONCAT(u.first_name, ' ', u.last_name) as specialist_name,
                   s.specialization
            FROM specialist s
            JOIN users u ON s.specialist_id = u.user_id
            ORDER BY u.first_name ASC
        ");
        $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'specialists' => $specialists]);

    } elseif ($action === 'behavioral_progress') {
        // Get behavioral progress data for charts
        $childId = isset($_GET['child_id']) ? (int) $_GET['child_id'] : null;
        $specialistId = isset($_GET['specialist_id']) ? (int) $_GET['specialist_id'] : null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        // Build child behavioral data query
        $sql = "
            SELECT c.child_id, c.first_name, c.last_name,
                (SELECT COUNT(*) FROM appointment a WHERE a.parent_id = c.parent_id" .
                ($specialistId ? " AND a.specialist_id = :spec_appt" : "") . ") as therapy_sessions,
                (SELECT COUNT(*) FROM child_exhibited_behavior ceb WHERE ceb.child_id = c.child_id) as total_behaviors,
                (SELECT COUNT(*) FROM child_exhibited_behavior ceb2 WHERE ceb2.child_id = c.child_id AND ceb2.severity IN ('low', 'mild')) as positive_behaviors,
                (SELECT COUNT(*) FROM child_milestones cm WHERE cm.child_id = c.child_id) as milestones_achieved,
                (SELECT COUNT(DISTINCT DATE(cl.login_at)) FROM child_last_login cl WHERE cl.child_id = c.child_id" .
                ($dateFrom ? " AND cl.login_at >= :date_from_login" : "") .
                ($dateTo ? " AND cl.login_at <= :date_to_login" : "") . ") as attendance_days,
                (SELECT COUNT(*) FROM growth_record gr WHERE gr.child_id = c.child_id) as growth_records
            FROM child c
            WHERE 1=1
        ";
        $params = [];

        if ($childId) {
            $sql .= " AND c.child_id = :child_id";
            $params['child_id'] = $childId;
        }

        if ($specialistId) {
            $params['spec_appt'] = $specialistId;
        }

        if ($dateFrom) {
            $params['date_from_login'] = $dateFrom;
        }
        if ($dateTo) {
            $params['date_to_login'] = $dateTo;
        }

        $sql .= " ORDER BY c.first_name ASC LIMIT 50";

        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate improvement score for each child
        foreach ($children as &$child) {
            $total = (int) $child['total_behaviors'];
            $positive = (int) $child['positive_behaviors'];
            $milestones = (int) $child['milestones_achieved'];
            // Improvement score: weighted combination of positive behaviors and milestones
            $child['improvement_score'] = $total > 0
                ? min(100, round(($positive / $total) * 60 + min($milestones * 5, 40)))
                : ($milestones > 0 ? min(100, $milestones * 10) : 0);
            $child['activity_engagement'] = (int) $child['growth_records'] + (int) $child['attendance_days'];
        }

        // Get behavior category distribution
        $catSql = "
            SELECT bc.category_name, COUNT(ceb.child_id) as count
            FROM child_exhibited_behavior ceb
            JOIN behavior b ON ceb.behavior_id = b.behavior_id
            JOIN behavior_category bc ON b.category_id = bc.category_id
        ";
        $catParams = [];
        if ($childId) {
            $catSql .= " WHERE ceb.child_id = :child_id";
            $catParams['child_id'] = $childId;
        }
        $catSql .= " GROUP BY bc.category_name ORDER BY count DESC";
        
        $catStmt = $connect->prepare($catSql);
        $catStmt->execute($catParams);
        $categoryDist = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'children' => $children,
            'category_distribution' => $categoryDist
        ]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
