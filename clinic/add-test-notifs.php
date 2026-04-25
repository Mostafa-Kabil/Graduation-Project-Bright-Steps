<?php
/**
 * Add Test Notifications for Current User
 * This script adds sample notifications for the logged-in clinic user
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
    <title>Add Test Notifications</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 2rem; background: #f1f5f9; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #0d9488; margin-top: 0; }
        .success { background: #dcfce7; color: #16a34a; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .error { background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .info { background: #e0f2fe; color: #0284c7; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #0d9488, #0891b2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 1rem; }
        .notification { background: #f8fafc; border-left: 4px solid #0d9488; padding: 1rem; margin: 0.5rem 0; border-radius: 4px; }
        .notification.unread { background: #f0fdf4; border-left-color: #16a34a; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Test Notifications</h1>

        <?php
        if (!isset($_SESSION['id'])) {
            echo '<div class="error">Error: You are not logged in. Please <a href="../../clinic-login.php">login as clinic</a> first.</div>';
            exit;
        }

        $userId = $_SESSION['id'];
        $role = $_SESSION['role'] ?? 'unknown';

        echo '<div class="info">';
        echo '<strong>User Info:</strong><br>';
        echo 'User ID: ' . htmlspecialchars($userId) . '<br>';
        echo 'Role: ' . htmlspecialchars($role) . '<br>';
        echo 'Name: ' . htmlspecialchars($_SESSION['fname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lname'] ?? '');
        echo '</div>';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $notifications = [
                    ['system', 'Welcome to Bright Steps', 'Your clinic account has been set up successfully.'],
                    ['appointment_reminder', 'New Appointment Request', 'You have a new appointment request from a parent.'],
                    ['general', 'System Update', 'We have updated the clinic dashboard with new features.'],
                    ['payment_success', 'Payment Received', 'A payment has been processed for your clinic services.'],
                    ['medical_record', 'New Medical Record', 'A patient\'s medical record has been updated.'],
                    ['prescription_added', 'Prescription Added', 'A new prescription has been added for a patient.'],
                    ['milestone', 'Milestone Achieved', 'One of your patients reached a therapy milestone!'],
                    ['growth_alert', 'Growth Alert', 'A patient\'s growth metrics have been updated.'],
                ];

                $stmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)");

                $count = 0;
                foreach ($notifications as $notif) {
                    $stmt->execute([$userId, $notif[0], $notif[1], $notif[2]]);
                    $count++;
                }

                echo '<div class="success">';
                echo '<strong>Success!</strong> Added ' . $count . ' test notifications to your account.<br>';
                echo '<a href="test-notifications.php" class="btn">View Test Page</a> ';
                echo '<a href="../../dashboards/clinic/clinic-dashboard.php" class="btn">Go to Dashboard</a>';
                echo '</div>';

            } catch (PDOException $e) {
                echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<p>Click the button below to add sample notifications to your account for testing:</p>';
            echo '<form method="POST">';
            echo '<button type="submit" class="btn" style="border:none; cursor:pointer;">Add Test Notifications</button>';
            echo '</form>';
        }

        // Show existing notifications
        try {
            $stmt = $connect->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<h2 style="margin-top: 2rem;">Your Recent Notifications</h2>';

            if (count($notifications) === 0) {
                echo '<p style="color: #64748b;">No notifications yet. Click the button above to add test notifications.</p>';
            } else {
                foreach ($notifications as $n) {
                    $unread = $n['is_read'] == 0;
                    echo '<div class="notification ' . ($unread ? 'unread' : '') . '">';
                    echo '<strong>' . htmlspecialchars($n['title']) . '</strong><br>';
                    echo '<small style="color: #64748b;">Type: ' . htmlspecialchars($n['type']) . ' | ' . $n['created_at'] . '</small><br>';
                    echo htmlspecialchars($n['message']);
                    if ($unread) {
                        echo '<br><small style="color: #16a34a; font-weight: 600;">● Unread</small>';
                    }
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="error">Error loading notifications: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <p style="margin-top: 2rem; text-align: center;">
            <a href="../../dashboards/clinic/clinic-dashboard.php">← Back to Dashboard</a> |
            <a href="test-notifications.php">Open Test Page</a>
        </p>
    </div>
</body>
</html>
?>
