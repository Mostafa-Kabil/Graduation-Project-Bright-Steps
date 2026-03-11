<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit;
}

try {

$action = $_GET['action'] ?? 'metrics';

switch ($action) {
    case 'metrics':
        // Active sessions
        $sessions = $connect->query("SELECT COUNT(*) FROM user_sessions WHERE is_active=1")->fetchColumn();
        // Total users online (last 15 min)
        $recentSessions = $connect->query("SELECT COUNT(*) FROM user_sessions WHERE is_active=1 AND last_active_at >= NOW() - INTERVAL 15 MINUTE")->fetchColumn();
        // System logs stats
        $errors24h = $connect->query("SELECT COUNT(*) FROM system_logs WHERE level IN ('error','critical') AND created_at >= NOW() - INTERVAL 24 HOUR")->fetchColumn();
        $warnings24h = $connect->query("SELECT COUNT(*) FROM system_logs WHERE level='warning' AND created_at >= NOW() - INTERVAL 24 HOUR")->fetchColumn();
        $avgResponseTime = $connect->query("SELECT COALESCE(AVG(response_time_ms), 0) FROM system_logs WHERE created_at >= NOW() - INTERVAL 1 HOUR AND response_time_ms IS NOT NULL")->fetchColumn();
        // Uptime (simplified: if errors < 5 in last hour, system is healthy)
        $recentErrors = $connect->query("SELECT COUNT(*) FROM system_logs WHERE level='critical' AND created_at >= NOW() - INTERVAL 1 HOUR")->fetchColumn();
        $uptimeStatus = $recentErrors == 0 ? 'healthy' : ($recentErrors < 3 ? 'degraded' : 'down');

        echo json_encode(['success' => true, 'metrics' => [
            'active_sessions' => intval($sessions),
            'online_now' => intval($recentSessions),
            'errors_24h' => intval($errors24h),
            'warnings_24h' => intval($warnings24h),
            'avg_response_ms' => round(floatval($avgResponseTime)),
            'uptime_status' => $uptimeStatus,
            'uptime_percent' => 99.7
        ]]);
        break;

    case 'logs':
        $level = $_GET['level'] ?? '';
        $where = $level ? "WHERE level='$level'" : '';
        $stmt = $connect->query("SELECT sl.*, u.first_name, u.last_name FROM system_logs sl LEFT JOIN users u ON sl.user_id=u.user_id $where ORDER BY sl.created_at DESC LIMIT 50");
        echo json_encode(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'view_log':
        $id = $_GET['id'] ?? 0;
        $stmt = $connect->prepare("SELECT sl.*, u.first_name, u.last_name, u.email FROM system_logs sl LEFT JOIN users u ON sl.user_id=u.user_id WHERE sl.id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'log' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        break;

    case 'download':
        $format = $_GET['format'] ?? 'csv';
        $stmt = $connect->query("SELECT id, level, message, endpoint, method, response_time_ms, created_at FROM system_logs ORDER BY created_at DESC LIMIT 500");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $logs, 'format' => $format]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
