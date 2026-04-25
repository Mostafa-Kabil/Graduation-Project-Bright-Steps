<?php
/**
 * Migration Runner - Add system_alerts column to user_settings
 */
include '../connection.php';

echo "<!DOCTYPE html><html><head><title>Migration Runner</title>";
echo "<style>body{font-family:monospace;padding:2rem;background:#1e293b;color:#22d3ee}</style>";
echo "</head><body>";
echo "<h1>Database Migration</h1>";

try {
    // Check if column exists
    $stmt = $connect->query("SHOW COLUMNS FROM user_settings LIKE 'system_alerts'");
    $exists = $stmt->rowCount() > 0;

    if ($exists) {
        echo "<p style='color:#4ade80'>Column 'system_alerts' already exists!</p>";
    } else {
        // Add column
        $connect->exec("ALTER TABLE user_settings ADD COLUMN system_alerts tinyint(1) DEFAULT 1");
        echo "<p style='color:#4ade80'>Successfully added 'system_alerts' column to user_settings table!</p>";
    }

    // Check if weekly_reports exists
    $stmt = $connect->query("SHOW COLUMNS FROM user_settings LIKE 'weekly_reports'");
    $exists = $stmt->rowCount() > 0;

    if ($exists) {
        echo "<p style='color:#4ade80'>Column 'weekly_reports' already exists!</p>";
    } else {
        $connect->exec("ALTER TABLE user_settings ADD COLUMN weekly_reports tinyint(1) DEFAULT 1");
        echo "<p style='color:#4ade80'>Successfully added 'weekly_reports' column to user_settings table!</p>";
    }

    echo "<h2>Current user_settings structure:</h2>";
    $stmt = $connect->query("SHOW COLUMNS FROM user_settings");
    echo "<ul>";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>{$col['Field']} - {$col['Type']} (Default: {$col['Default']})</li>";
    }
    echo "</ul>";

    echo "<p><a href='test-notifications.php' style='color:#fbbf24'>Go to Test Page</a></p>";
    echo "<p><a href='dashboards/clinic/clinic-dashboard.php' style='color:#fbbf24'>Back to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:#ef4444'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
