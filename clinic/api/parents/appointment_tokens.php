<?php
/**
 * Bright Steps - Appointment Tokens API for Clinic/Specialist Portal
 * Allows specialists to view token discounts applied to appointments
 */
session_start();
require_once "../../../connection.php";
header('Content-Type: application/json');

// Require authenticated clinic or specialist
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['clinic', 'specialist'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Clinic/Specialist access required']);
    exit();
}

$userId = $_SESSION['id'];
$role = $_SESSION['role'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        // Get appointments with token discounts for this clinic
        if ($action === 'clinic_appointments') {
            // Get clinic_id from session or query
            $clinicId = $_GET['clinic_id'] ?? $_SESSION['clinic_id'] ?? null;

            if (!$clinicId) {
                http_response_code(400);
                echo json_encode(['error' => 'clinic_id required']);
                exit();
            }

            $status = $_GET['status'] ?? 'upcoming'; // upcoming, all, completed

            $sql = "
                SELECT
                    a.appointment_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
                    p.parent_id, pu.first_name as parent_fname, pu.last_name as parent_last_name,
                    s.specialist_id, su.first_name as specialist_fname, su.last_name as specialist_last_name,
                    c.clinic_id, c.clinic_name,
                    pm.amount_pre_discount, pm.amount_post_discount, pm.tokens_used,
                    at.token_id, at.token_type, at.discount_amount, at.status as token_status
                FROM appointment a
                INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                INNER JOIN users su ON s.specialist_id = su.user_id
                INNER JOIN parent p ON a.parent_id = p.parent_id
                INNER JOIN users pu ON p.parent_id = pu.user_id
                INNER JOIN clinic c ON s.clinic_id = c.clinic_id
                LEFT JOIN payment pm ON a.payment_id = pm.payment_id
                LEFT JOIN appointment_tokens at ON pm.token_id = at.token_id
                WHERE s.clinic_id = ?
            ";

            $params = [$clinicId];

            if ($status === 'upcoming') {
                $sql .= " AND a.scheduled_at >= NOW() AND a.status NOT IN ('cancelled', 'completed')";
            } elseif ($status === 'completed') {
                $sql .= " AND a.status = 'completed'";
            }

            $sql .= " ORDER BY a.scheduled_at ASC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format for response
            $formatted = [];
            foreach ($appointments as $apt) {
                $formatted[] = [
                    'appointment_id' => $apt['appointment_id'],
                    'status' => $apt['status'],
                    'type' => $apt['type'],
                    'scheduled_at' => $apt['scheduled_at'],
                    'parent_name' => $apt['parent_fname'] . ' ' . $apt['parent_last_name'],
                    'specialist_name' => $apt['specialist_fname'] . ' ' . $apt['specialist_last_name'],
                    'clinic_name' => $apt['clinic_name'],
                    'pricing' => [
                        'original_price' => (float) $apt['amount_pre_discount'],
                        'discount_amount' => (float) ($apt['tokens_used'] ?? 0),
                        'final_price' => (float) $apt['amount_post_discount'],
                        'token_type' => $apt['token_type'],
                        'token_status' => $apt['token_status']
                    ],
                    'has_token_discount' => (float) ($apt['tokens_used'] ?? 0) > 0
                ];
            }

            echo json_encode([
                'success' => true,
                'appointments' => $formatted,
                'count' => count($formatted),
                'total_discount_given' => array_sum(array_column($formatted, 'pricing'))['discount_amount'] ?? 0
            ]);

        // Get single appointment details with token info
        } elseif ($action === 'appointment_details') {
            $appointmentId = $_GET['appointment_id'] ?? null;

            if (!$appointmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'appointment_id required']);
                exit();
            }

            $stmt = $connect->prepare("
                SELECT
                    a.*,
                    p.parent_id, pu.first_name as parent_fname, pu.last_name as parent_last_name, pu.email as parent_email,
                    s.specialist_id, su.first_name as specialist_fname, su.last_name as specialist_last_name,
                    pm.amount_pre_discount, pm.amount_post_discount, pm.tokens_used, pm.method as payment_method, pm.status as payment_status,
                    at.token_id, at.token_type, at.discount_amount, at.expires_at as token_expires_at
                FROM appointment a
                INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                INNER JOIN parent p ON a.parent_id = p.parent_id
                INNER JOIN users pu ON p.parent_id = pu.user_id
                INNER JOIN users su ON s.specialist_id = su.user_id
                LEFT JOIN payment pm ON a.payment_id = pm.payment_id
                LEFT JOIN appointment_tokens at ON pm.token_id = at.token_id
                WHERE a.appointment_id = ?
            ");
            $stmt->execute([$appointmentId]);
            $apt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$apt) {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
                exit();
            }

            echo json_encode([
                'success' => true,
                'appointment' => [
                    'appointment_id' => $apt['appointment_id'],
                    'status' => $apt['status'],
                    'type' => $apt['type'],
                    'scheduled_at' => $apt['scheduled_at'],
                    'comment' => $apt['comment'],
                    'report' => $apt['report'],
                    'parent' => [
                        'name' => $apt['parent_fname'] . ' ' . $apt['parent_last_name'],
                        'email' => $apt['parent_email']
                    ],
                    'specialist' => [
                        'name' => $apt['specialist_fname'] . ' ' . $apt['specialist_last_name']
                    ],
                    'payment' => [
                        'original_price' => (float) $apt['amount_pre_discount'],
                        'discount' => (float) ($apt['tokens_used'] ?? 0),
                        'final_price' => (float) $apt['amount_post_discount'],
                        'method' => $apt['payment_method'],
                        'status' => $apt['payment_status'],
                        'token_type' => $apt['token_type'],
                        'token_expires_at' => $apt['token_expires_at']
                    ],
                    'has_discount' => (float) ($apt['tokens_used'] ?? 0) > 0
                ]
            ]);

        // Get token usage statistics for clinic
        } elseif ($action === 'token_stats') {
            $clinicId = $_GET['clinic_id'] ?? $_SESSION['clinic_id'] ?? null;
            $dateRange = $_GET['range'] ?? '30'; // days

            if (!$clinicId) {
                http_response_code(400);
                echo json_encode(['error' => 'clinic_id required']);
                exit();
            }

            // Total discounts given
            $stmt = $connect->prepare("
                SELECT
                    COUNT(DISTINCT a.appointment_id) as discounted_appointments,
                    COALESCE(SUM(pm.tokens_used), 0) as total_discount_amount,
                    COALESCE(AVG(pm.tokens_used), 0) as avg_discount
                FROM appointment a
                INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                INNER JOIN payment pm ON a.payment_id = pm.payment_id
                WHERE s.clinic_id = ?
                AND pm.tokens_used > 0
                AND a.scheduled_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$clinicId, $dateRange]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Token types breakdown
            $stmt = $connect->prepare("
                SELECT
                    at.token_type,
                    COUNT(*) as count,
                    COALESCE(SUM(at.discount_amount), 0) as total_discount
                FROM appointment_tokens at
                INNER JOIN appointment a ON at.applied_to_appointment = a.appointment_id
                INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                WHERE s.clinic_id = ?
                AND at.status IN ('applied', 'used')
                AND a.scheduled_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY at.token_type
            ");
            $stmt->execute([$clinicId, $dateRange]);
            $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'period_days' => (int) $dateRange,
                    'discounted_appointments' => (int) $stats['discounted_appointments'],
                    'total_discount_given' => (float) $stats['total_discount_amount'],
                    'average_discount' => (float) $stats['avg_discount'],
                    'by_token_type' => $breakdown
                ]
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
