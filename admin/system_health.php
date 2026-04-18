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
        // Uptime status based on recent errors
        $recentErrors = $connect->query("SELECT COUNT(*) FROM system_logs WHERE level='critical' AND created_at >= NOW() - INTERVAL 1 HOUR")->fetchColumn();
        $uptimeStatus = $recentErrors == 0 ? 'healthy' : ($recentErrors < 3 ? 'degraded' : 'down');

        // Calculate real uptime % from last 7 days of logs
        $totalLogs7d = $connect->query("SELECT COUNT(*) FROM system_logs WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
        $errorLogs7d = $connect->query("SELECT COUNT(*) FROM system_logs WHERE level IN ('error','critical') AND created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
        $uptimePercent = $totalLogs7d > 0 ? round((1 - ($errorLogs7d / max($totalLogs7d, 1))) * 100, 1) : 99.9;
        if ($uptimePercent < 0) $uptimePercent = 0;

        // Real server metrics
        $phpVersion = phpversion();
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        // Convert memory limit to bytes
        $memLimitBytes = convertToBytes($memoryLimit);

        // Disk usage
        $diskPath = (PHP_OS_FAMILY === 'Windows') ? 'C:' : '/';
        $diskFree = @disk_free_space($diskPath);
        $diskTotal = @disk_total_space($diskPath);
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        // Database size
        $dbSizeStmt = $connect->query("SELECT ROUND(SUM(data_length + index_length), 0) AS db_size FROM information_schema.TABLES WHERE table_schema = 'grad'");
        $dbSize = $dbSizeStmt->fetchColumn() ?: 0;

        // Total tables and rows
        $tableCountStmt = $connect->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = 'grad'");
        $tableCount = $tableCountStmt->fetchColumn();
        $totalRowsStmt = $connect->query("SELECT SUM(table_rows) FROM information_schema.TABLES WHERE table_schema = 'grad'");
        $totalRows = $totalRowsStmt->fetchColumn() ?: 0;

        // Server software
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

        // Total users count
        $totalUsers = $connect->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Last error
        $lastErrorStmt = $connect->query("SELECT message, created_at FROM system_logs WHERE level IN ('error','critical') ORDER BY created_at DESC LIMIT 1");
        $lastError = $lastErrorStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'metrics' => [
            'active_sessions' => intval($sessions),
            'online_now' => intval($recentSessions),
            'errors_24h' => intval($errors24h),
            'warnings_24h' => intval($warnings24h),
            'avg_response_ms' => round(floatval($avgResponseTime)),
            'uptime_status' => $uptimeStatus,
            'uptime_percent' => $uptimePercent,
            // Server info
            'php_version' => $phpVersion,
            'server_software' => $serverSoftware,
            'memory_usage' => $memoryUsage,
            'memory_peak' => $memoryPeak,
            'memory_limit' => $memLimitBytes,
            'memory_limit_str' => $memoryLimit,
            // Disk
            'disk_free' => $diskFree,
            'disk_total' => $diskTotal,
            'disk_used' => $diskUsed,
            'disk_percent' => $diskPercent,
            // Database
            'db_size' => intval($dbSize),
            'db_tables' => intval($tableCount),
            'db_rows' => intval($totalRows),
            // Platform
            'total_users' => intval($totalUsers),
            'last_error' => $lastError,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ]]);
        break;

    case 'logs':
        $level = $_GET['level'] ?? '';
        $where = '';
        $params = [];
        if ($level) {
            $where = "WHERE level = ?";
            $params[] = $level;
        }
        $stmt = $connect->prepare("SELECT sl.*, u.first_name, u.last_name FROM system_logs sl LEFT JOIN users u ON sl.user_id=u.user_id $where ORDER BY sl.created_at DESC LIMIT 50");
        $stmt->execute($params);
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

function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = intval($val);
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}
