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
        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASS;
        $hosts = ["localhost", "127.0.0.1"];
        $ports = ["3306", "3307", "3308"];
        $last_error = "";

        foreach ($hosts as $host) {
            foreach ($ports as $port) {
                try {
                    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=" . DB_CHARSET;
                    $pdo = new PDO($dsn, $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    return $pdo;
                } catch (PDOException $e) {
                    $last_error = $e->getMessage();
                }
            }
        }

        // If we reach here, connection failed for all combinations
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed. ' . $last_error
        ]);
        exit();
    }
    return $pdo;
}
