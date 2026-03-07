<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

// Verify admin access
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // ── Total Users ──────────────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Users this month
    $stmt = $connect->query("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $usersThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // Users last month
    $stmt = $connect->query("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')");
    $usersLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    $usersTrend = $usersLastMonth > 0 ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100) : ($usersThisMonth > 0 ? 100 : 0);

    // ── Active Clinics ───────────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic WHERE status = 'verified'");
    $activeClinics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // New clinics this month
    $stmt = $connect->query("SELECT COUNT(*) as c FROM clinic WHERE added_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $newClinics = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // ── Total Revenue ────────────────────────────────────
    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as total FROM payment WHERE status = 'completed' OR status IS NULL");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Revenue this month
    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as c FROM payment WHERE (status = 'completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $revenueThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as c FROM payment WHERE (status = 'completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')");
    $revenueLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    $revenueTrend = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100) : ($revenueThisMonth > 0 ? 100 : 0);

    // ── Active Subscriptions ─────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM parent_subscription");
    $activeSubs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Subs this month - compare with last month
    $stmt = $connect->query("SELECT COUNT(*) as c FROM parent_subscription ps JOIN subscription s ON ps.subscription_id = s.subscription_id");
    $subsTotal = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // ── Recent Activity ──────────────────────────────────
    $stmt = $connect->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── User Distribution ────────────────────────────────
    $stmt = $connect->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        GROUP BY role
    ");
    $distribution = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $distribution[$row['role']] = (int) $row['count'];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int) $totalUsers,
            'users_trend' => $usersTrend,
            'active_clinics' => (int) $activeClinics,
            'new_clinics' => (int) $newClinics,
            'total_revenue' => (float) $totalRevenue,
            'revenue_trend' => $revenueTrend,
            'active_subscriptions' => (int) $activeSubs,
        ],
        'recent_activity' => $recentActivity,
        'user_distribution' => $distribution
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
