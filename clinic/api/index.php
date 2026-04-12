<?php
/**
 * Bright Steps Clinic API — Central Router
 *
 * Base URL: /clinic/api/index.php
 *
 * Routes:
 *   ?route=auth/parent          → parents/auth.php
 *   ?route=auth/clinic          → management/auth.php
 *   ?route=appointments/parent  → parents/appointments.php
 *   ?route=records/doctor       → doctors/medical_records.php
 *   ?route=prescriptions        → doctors/prescriptions.php
 *   ?route=slots                → management/slots.php
 *   ?route=doctors              → management/doctors.php
 *   ?route=history              → management/history.php
 *   ?route=feedback             → parents/feedback.php
 *   ?route=notifications        → notifications.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$route = $_GET['route'] ?? '';

$routeMap = [
    'auth/parent'          => __DIR__ . '/parents/auth.php',
    'auth/clinic'          => __DIR__ . '/management/auth.php',
    'appointments/parent'  => __DIR__ . '/parents/appointments.php',
    'records/doctor'       => __DIR__ . '/doctors/medical_records.php',
    'prescriptions'        => __DIR__ . '/doctors/prescriptions.php',
    'slots'                => __DIR__ . '/management/slots.php',
    'doctors'              => __DIR__ . '/management/doctors.php',
    'history'              => __DIR__ . '/management/history.php',
    'feedback'             => __DIR__ . '/parents/feedback.php',
    'notifications'        => __DIR__ . '/notifications.php',
];

if (isset($routeMap[$route])) {
    // Pass through to the routed file
    require $routeMap[$route];
} else {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Bright Steps Clinic API v1.0',
        'version' => '1.0.0',
        'routes'  => array_map(function($path) {
            return '?route=' . $path;
        }, array_keys($routeMap)),
        'usage'   => [
            'authentication' => 'Bearer <token> in Authorization header',
            'format'         => 'All responses: { success: bool, message: string, data... }',
            'example'        => '/clinic/api/index.php?route=auth/parent&action=login'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
