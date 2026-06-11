<?php
$connect = null;
$db_name = "grad";
$db_user = "root";
$db_pass = "";

// Try localhost first, then 127.0.0.1, and common ports
$hosts = ["localhost", "127.0.0.1"];
$ports = ["3306", "3307", "3308"];
$last_error = "";

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        try {
            $connect = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            break 2; // Success! Break out of both loops
        } catch (PDOException $e) {
            $last_error = "Host $host:$port -> " . $e->getMessage();
            $connect = null;
        }
    }
}

if (!$connect) {
    die("Database connection failed for all hosts. Last error: " . $last_error);
}

if (!function_exists('log_audit_action')) {
    function log_audit_action($connect, $user_id, $action, $resource, $details = '', $resource_id = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $connect->prepare("INSERT INTO audit_logs (user_id, action, resource, details, resource_id, ip_address, created_at) VALUES (:u, :a, :r, :d, :rid, :ip, NOW())");
            $stmt->execute([
                'u' => $user_id,
                'a' => $action,
                'r' => $resource,
                'd' => $details,
                'rid' => $resource_id,
                'ip' => $ip
            ]);
        } catch (Exception $e) {}
    }
}

// Global account suspension check
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id']) && isset($_SESSION['role'])) {
    try {
        $suspended = false;
        if ($_SESSION['role'] === 'clinic') {
            $check = $connect->prepare("SELECT status FROM clinic WHERE clinic_id = ?");
            $check->execute([$_SESSION['id']]);
            if ($check->fetchColumn() === 'suspended') $suspended = true;
        } else {
            $check = $connect->prepare("SELECT status FROM users WHERE user_id = ?");
            $check->execute([$_SESSION['id']]);
            if ($check->fetchColumn() === 'suspended') $suspended = true;
        }

        if ($suspended) {
            session_destroy();
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Your account has been suspended.', 'redirect' => '/account-suspended.php']);
                exit;
            } else {
                // If it's a regular page load, try to redirect
                // Use a relative path back to root
                $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
                $prefix = str_repeat('../', max(0, $depth));
                // Fallback prefix just in case
                if ($depth < 0) $prefix = '';
                header("Location: {$prefix}account-suspended.php");
                exit;
            }
        }
    } catch (Exception $e) {}
}
