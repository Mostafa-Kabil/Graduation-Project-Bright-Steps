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
    
    $sql = "SELECT al.log_id, al.action as activity_type, al.resource as description, u.first_name as user_name, u.role as user_role, al.ip_address, al.created_at, al.details, al.resource_id 
            FROM audit_logs al LEFT JOIN users u ON al.user_id=u.user_id WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $sql .= " AND (al.action LIKE :search OR al.resource LIKE :search2 OR u.first_name LIKE :search3)";
        $params['search'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "%$search%";
    }
    
    if ($roleFilter !== 'all') {
        $sql .= " AND u.role = :role";
        $params['role'] = $roleFilter;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 500";
    
    try {
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get stats
        $statsStmt = $connect->query("SELECT 
            COUNT(*) as total_logs,
            SUM(CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END) as admin_actions,
            0 as system_actions,
            SUM(CASE WHEN DATE(al.created_at) = CURDATE() THEN 1 ELSE 0 END) as today_actions
            FROM audit_logs al LEFT JOIN users u ON al.user_id=u.user_id");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'logs' => $logs, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
