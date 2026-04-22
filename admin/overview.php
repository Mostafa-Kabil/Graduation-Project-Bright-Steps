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

if (isset($_GET['action']) && $_GET['action'] === 'signup_chart') {
    try {
        $range = $_GET['range'] ?? 'week';
        $dFrom = $_GET['date_from'] ?? null;
        $dTo = $_GET['date_to'] ?? null;

        $sql = "SELECT DATE(created_at) as signup_date, COUNT(*) as count FROM users WHERE ";
        $params = [];

        if ($range === 'custom' && $dFrom && $dTo) {
            $sql .= "DATE(created_at) >= :df AND DATE(created_at) <= :dt";
            $params['df'] = $dFrom;
            $params['dt'] = $dTo;
        } elseif ($range === 'quarter') {
            $sql .= "created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        } elseif ($range === 'month') {
            $sql .= "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        } else {
            $sql .= "created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        }
        $sql .= " GROUP BY DATE(created_at) ORDER BY signup_date ASC";

        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'chart' => $data]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

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

    // ── Growth Records ───────────────────────────────────
    $growthRecords = 0; $growthThisMonth = 0;
    try {
        $stmt = $connect->query("SELECT COUNT(*) as total FROM growth_record");
        $growthRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $connect->query("SELECT COUNT(*) as c FROM growth_record WHERE recorded_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $growthThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    } catch(Exception $e) {}

    // ── Average Feedback Rating ──────────────────────────
    $avgRating = 0; $totalFeedback = 0;
    try {
        $stmt = $connect->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM feedback WHERE rating IS NOT NULL");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $avgRating = round($row['avg_rating'] ?? 0, 1);
        $totalFeedback = $row['total'];
    } catch(Exception $e) {}

    // ── Open Support Tickets ─────────────────────────────
    $openTickets = 0;
    try {
        $stmt = $connect->query("SELECT COUNT(*) as total FROM support_tickets WHERE status IN ('open','in_progress','waiting')");
        $openTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(Exception $e) {}

    // ── User Signups Over Time (last 7 days) ─────────────
    $signupChart = [];
    try {
        $stmt = $connect->query("
            SELECT DATE(created_at) as signup_date, COUNT(*) as count
            FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at) ORDER BY signup_date ASC
        ");
        $signupChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Revenue Over Time (last 6 months) ────────────────
    $revenueChart = [];
    try {
        $stmt = $connect->query("
            SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(amount_post_discount), 0) as revenue
            FROM payment WHERE (status = 'completed' OR status IS NULL) AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(paid_at, '%Y-%m') ORDER BY month ASC
        ");
        $revenueChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── System Logs (for IT/System Admin) ─────────────────
    $systemLogs = [];
    try {
        $stmt = $connect->query("SELECT id, level, message, endpoint, method, response_time_ms, created_at FROM system_logs ORDER BY created_at DESC LIMIT 15");
        $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Recent Audit Actions ─────────────────────────────
    $recentAudit = [];
    try {
        $stmt = $connect->query("
            SELECT al.log_id, al.action, al.resource, al.resource_id, al.ip_address, al.details, al.created_at,
                   u.first_name, u.last_name, u.role
            FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC LIMIT 10
        ");
        $recentAudit = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Recent Activity ─────────────────
    $recentActivity = [];
    try {
        $stmt = $connect->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 15");
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Top Performing Clinics ───────────────────────────
    $topClinics = [];
    try {
        $stmt = $connect->query("
            SELECT c.clinic_name, c.rating, c.status,
                   (SELECT COUNT(*) FROM specialist s WHERE s.clinic_id = c.clinic_id) as specialist_count
            FROM clinic c WHERE c.status = 'verified' ORDER BY c.rating DESC LIMIT 5
        ");
        $topClinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Subscription Distribution ────────────────────────
    $subDistribution = [];
    try {
        $stmt = $connect->query("
            SELECT s.plan_name, COUNT(ps.parent_id) as user_count
            FROM subscription s LEFT JOIN parent_subscription ps ON s.subscription_id = ps.subscription_id
            GROUP BY s.subscription_id, s.plan_name ORDER BY user_count DESC
        ");
        $subDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // ── Recent Payments ──────────────────────────────────
    $recentPayments = [];
    try {
        $stmt = $connect->query("
            SELECT p.payment_id, p.amount_post_discount, p.method, p.paid_at, p.status, s.plan_name
            FROM payment p LEFT JOIN subscription s ON p.subscription_id = s.subscription_id
            ORDER BY p.paid_at DESC LIMIT 5
        ");
        $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int) $totalUsers,
            'users_trend' => $usersTrend,
            'users_this_month' => (int) $usersThisMonth,
            'active_clinics' => (int) $activeClinics,
            'total_clinics' => (int) $totalClinics,
            'pending_clinics' => (int) $pendingClinics,
            'new_clinics' => (int) $newClinics,
            'total_revenue' => (float) $totalRevenue,
            'revenue_trend' => $revenueTrend,
            'revenue_this_month' => (float) $revenueThisMonth,
            'active_subscriptions' => (int) $activeSubs,
            'total_children' => (int) $totalChildren,
            'total_specialists' => (int) $totalSpecialists,
            'total_appointments' => (int) $totalAppointments,
            'upcoming_appointments' => (int) $upcomingAppointments,
            'growth_records' => (int) $growthRecords,
            'growth_this_month' => (int) $growthThisMonth,
            'avg_rating' => $avgRating,
            'total_feedback' => (int) $totalFeedback,
            'open_tickets' => (int) $openTickets,
        ],
        'signup_chart' => $signupChart,
        'revenue_chart' => $revenueChart,
        'system_logs' => $systemLogs,
        'recent_audit' => $recentAudit,
        'recent_activity' => $recentActivity,
        'user_distribution' => $distribution,
        'top_clinics' => $topClinics,
        'recent_payments' => $recentPayments,
        'subscription_distribution' => $subDistribution
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
