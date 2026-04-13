<?php
/**
 * Bright Steps Clinic API — Configuration
 * Database connection, JWT settings, and CORS headers
 */

// ── CORS Headers ──────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── Database Configuration ────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'grad');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── JWT Configuration ─────────────────────────────────
define('JWT_SECRET', 'bright-steps-clinic-jwt-secret-2026');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 86400); // 24 hours in seconds

// ── Database Connection (Singleton) ───────────────────
function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed'
            ]);
            exit();
        }
    }
    return $pdo;
}
