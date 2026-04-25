<?php
/**
 * Check Database Schema and Data
 */
session_start();
include '../connection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Database</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #1e293b; color: #22d3ee; }
        .section { background: #0f172a; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .success { color: #4ade80; }
        .error { color: #ef4444; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #334155; padding: 8px; text-align: left; }
        th { background: #334155; }
    </style>
</head>
<body>
    <h1>Database Check</h1>

    <div class="section">
        <h2>Session Info</h2>
        <p>User ID: <?php echo isset($_SESSION['id']) ? htmlspecialchars($_SESSION['id']) : '<span class="error">NOT SET</span>'; ?></p>
        <p>Role: <?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : '<span class="error">NOT SET</span>'; ?></p>
    </div>

    <div class="section">
        <h2>user_settings Table Schema</h2>
        <?php
        try {
            $stmt = $connect->query("SHOW COLUMNS FROM user_settings");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<table><tr><th>Field</th><th>Type</th><th>Default</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            echo '</table>';

            // Check if system_alerts exists
            $hasSystemAlerts = false;
            $hasWeeklyReports = false;
            foreach ($columns as $col) {
                if ($col['Field'] === 'system_alerts') $hasSystemAlerts = true;
                if ($col['Field'] === 'weekly_reports') $hasWeeklyReports = true;
            }

            if ($hasSystemAlerts) {
                echo '<p class="success">✓ system_alerts column exists</p>';
            } else {
                echo '<p class="error">✗ system_alerts column MISSING - Run migration!</p>';
                echo '<p><a href="run_migration.php" class="success">Run Migration Now</a></p>';
            }

            if ($hasWeeklyReports) {
                echo '<p class="success">✓ weekly_reports column exists</p>';
            } else {
                echo '<p class="error">✗ weekly_reports column MISSING</p>';
            }

        } catch (PDOException $e) {
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Your user_settings Record</h2>
        <?php
        if (!isset($_SESSION['id'])) {
            echo '<p class="error">Not logged in!</p>';
        } else {
            $userId = $_SESSION['id'];
            try {
                $stmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ?");
                $stmt->execute([$userId]);
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($settings) {
                    echo '<table><tr><th>Setting</th><th>Value</th></tr>';
                    foreach ($settings as $key => $value) {
                        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p>No settings record found. Creating default...</p>';
                    $stmt = $connect->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                    echo '<p class="success">Created! <a href="">Refresh</a></p>';
                }
            } catch (PDOException $e) {
                echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>Your Notifications</h2>
        <?php
        if (!isset($_SESSION['id'])) {
            echo '<p class="error">Not logged in!</p>';
        } else {
            $userId = $_SESSION['id'];
            try {
                $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                $stmt->execute([$userId]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($notifications) === 0) {
                    echo '<p>No notifications found.</p>';
                } else {
                    echo '<p class="success">Found ' . count($notifications) . ' notifications</p>';
                    echo '<table><tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>';
                    foreach ($notifications as $n) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($n['notification_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($n['type']) . '</td>';
                        echo '<td>' . htmlspecialchars($n['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($n['message']) . '</td>';
                        echo '<td>' . ($n['is_read'] == 0 ? '<span class="error">No</span>' : '<span class="success">Yes</span>') . '</td>';
                        echo '<td>' . htmlspecialchars($n['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (PDOException $e) {
                echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        ?>
    </div>

    <p><a href="../../dashboards/clinic/clinic-dashboard.php">Go to Dashboard</a> | <a href="add-test-notifs.php">Add Test Notifications</a></p>
</body>
</html>
?>
