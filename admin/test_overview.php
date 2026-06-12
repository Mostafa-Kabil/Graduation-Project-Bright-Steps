<?php
include '../connection.php';
try {
    // ── Total Users ──────────────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Users by role
    $stmt = $connect->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $distribution = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $distribution[$row['role']] = (int) $row['count'];
    }
    
    // Explicitly pull clinic count from clinic table since they might not be in users table
    $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic");
    $distribution['clinic'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

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

    $stmt = $connect->query("SELECT COUNT(*) as total FROM clinic");
    $totalClinics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $connect->query("SELECT COUNT(*) as c FROM clinic WHERE status = 'pending'");
    $pendingClinics = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $connect->query("SELECT COUNT(*) as c FROM clinic WHERE added_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $newClinics = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    // ── Total Revenue ────────────────────────────────────
    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as total FROM payment WHERE status = 'completed' OR status IS NULL");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as c FROM payment WHERE (status = 'completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $revenueThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $connect->query("SELECT COALESCE(SUM(amount_post_discount), 0) as c FROM payment WHERE (status = 'completed' OR status IS NULL) AND paid_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')");
    $revenueLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    $revenueTrend = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100) : ($revenueThisMonth > 0 ? 100 : 0);

    // ── Active Subscriptions ─────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM parent_subscription");
    $activeSubs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // ── Total Children ───────────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM child");
    $totalChildren = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // ── Total Specialists ────────────────────────────────
    $stmt = $connect->query("SELECT COUNT(*) as total FROM specialist");
    $totalSpecialists = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // ── Appointments ─────────────────────────────────────
    $totalAppointments = 0; $upcomingAppointments = 0;
    try {
        $stmt = $connect->query("SELECT COUNT(*) as total FROM appointment");
        $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $connect->query("SELECT COUNT(*) as c FROM appointment WHERE scheduled_at > NOW() AND (status IS NULL OR status != 'cancelled')");
        $upcomingAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    } catch(Exception $e) {}

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int) $totalUsers,
            'users_trend' => $usersTrend,
            'user_distribution' => $distribution
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
