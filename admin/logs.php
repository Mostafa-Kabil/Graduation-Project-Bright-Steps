<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $roleFilter = $_GET['role'] ?? 'all';
    
    $sql = "SELECT log_id, activity_type, description, user_name, user_role, ip_address, created_at 
            FROM activity_log WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $sql .= " AND (description LIKE :search OR user_name LIKE :search2 OR activity_type LIKE :search3)";
        $params['search'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "%$search%";
    }
    
    if ($roleFilter !== 'all') {
        $sql .= " AND user_role = :role";
        $params['role'] = $roleFilter;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 500";
    
    try {
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get stats
        $statsStmt = $connect->query("SELECT 
            COUNT(*) as total_logs,
            SUM(CASE WHEN user_role = 'admin' THEN 1 ELSE 0 END) as admin_actions,
            SUM(CASE WHEN user_role = 'system' THEN 1 ELSE 0 END) as system_actions,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_actions
            FROM activity_log");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'logs' => $logs, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
