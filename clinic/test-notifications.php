<?php
/**
 * Bright Steps - Clinic Notification Test Page
 * This page allows testing the notification system integration
 */
session_start();
if (!isset($_SESSION['id'])) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login Required</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1>Login Required</h1><p>Please <a href="../../clinic-login.php">login as clinic</a> first.</p></body></html>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications - Bright Steps</title>
    <link rel="icon" type="image/png" href="../../assets/logo.png">
    <link rel="stylesheet" href="../../styles/globals.css">
    <link rel="stylesheet" href="../../styles/dashboard.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .test-container { max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .test-card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .test-card h2 { margin-top: 0; color: #0d9488; }
        .test-row { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
        .test-row:last-child { border-bottom: none; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #0d9488, #0891b2); color: white; }
        .btn-outline { background: transparent; border: 2px solid #0d9488; color: #0d9488; }
        .btn:hover { opacity: 0.9; }
        .status { padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .status-success { background: #dcfce7; color: #16a34a; }
        .status-error { background: #fee2e2; color: #dc2626; }
        .log { background: #1e293b; color: #22d3ee; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; max-height: 300px; overflow-y: auto; }
        .toggle-switch { position: relative; width: 50px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; border-radius: 26px; transition: 0.3s; }
        .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: 0.3s; }
        input:checked + .toggle-slider { background-color: #0d9488; }
        input:checked + .toggle-slider:before { transform: translateX(24px); }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 style="text-align: center; color: #0d9488; margin-bottom: 2rem;">Notification System Test</h1>

        <!-- Current Settings -->
        <div class="test-card">
            <h2>Your Current Notification Settings</h2>
            <div id="current-settings">Loading...</div>
        </div>

        <!-- Notification Preferences -->
        <div class="test-card">
            <h2>Update Preferences</h2>
            <div class="test-row">
                <div>
                    <strong>Push Notifications</strong>
                    <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">Receive in-app notifications</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="push-toggle" onchange="updateSetting('push_notifications', this.checked ? 1 : 0)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="test-row">
                <div>
                    <strong>Email Notifications</strong>
                    <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">Receive email updates</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="email-toggle" onchange="updateSetting('email_notifications', this.checked ? 1 : 0)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="test-row">
                <div>
                    <strong>Appointment Reminders</strong>
                    <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">Get notified before appointments</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="appointment-toggle" onchange="updateSetting('appointment_reminders', this.checked ? 1 : 0)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="test-row">
                <div>
                    <strong>System Alerts</strong>
                    <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">Receive system announcements</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="system-toggle" onchange="updateSetting('system_alerts', this.checked ? 1 : 0)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Create Test Notification -->
        <div class="test-card">
            <h2>Create Test Notification</h2>
            <div style="display: grid; gap: 1rem;">
                <input type="text" id="notif-title" placeholder="Notification Title" class="form-input" style="padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px;">
                <textarea id="notif-message" placeholder="Notification Message" rows="3" style="padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box;"></textarea>
                <select id="notif-type" style="padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <option value="system">System</option>
                    <option value="appointment_reminder">Appointment Reminder</option>
                    <option value="general">General</option>
                    <option value="payment_success">Payment Success</option>
                </select>
                <button class="btn btn-primary" onclick="createTestNotification()">Create Test Notification</button>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="test-card">
            <h2>Recent Notifications</h2>
            <button class="btn btn-outline" onclick="loadNotifications()" style="margin-bottom: 1rem;">Refresh</button>
            <div id="notifications-list">Loading...</div>
        </div>

        <!-- Activity Log -->
        <div class="test-card">
            <h2>Activity Log</h2>
            <div id="activity-log" class="log"></div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboards/clinic/clinic-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>

    <script>
        let settings = {};

        function log(message) {
            const logEl = document.getElementById('activity-log');
            const time = new Date().toLocaleTimeString();
            logEl.innerHTML += `[${time}] ${message}\n`;
            logEl.scrollTop = logEl.scrollHeight;
        }

        async function loadSettings() {
            try {
                const res = await fetch('../api_settings.php?action=get');
                const text = await res.text();
                console.log('Settings API raw response:', text.substring(0, 200));
                const data = JSON.parse(text);
                if (data.success) {
                    settings = data.settings;
                    document.getElementById('current-settings').innerHTML = `
                        <div class="test-row"><span>Push Notifications</span><span class="status ${settings.push_notifications ? 'status-success' : 'status-error'}">${settings.push_notifications ? 'ON' : 'OFF'}</span></div>
                        <div class="test-row"><span>Email Notifications</span><span class="status ${settings.email_notifications ? 'status-success' : 'status-error'}">${settings.email_notifications ? 'ON' : 'OFF'}</span></div>
                        <div class="test-row"><span>Appointment Reminders</span><span class="status ${settings.appointment_reminders ? 'status-success' : 'status-error'}">${settings.appointment_reminders ? 'ON' : 'OFF'}</span></div>
                        <div class="test-row"><span>System Alerts</span><span class="status ${settings.system_alerts ? 'status-success' : 'status-error'}">${settings.system_alerts ? 'ON' : 'OFF'}</span></div>
                    `;
                    // Update toggles
                    document.getElementById('push-toggle').checked = !!settings.push_notifications;
                    document.getElementById('email-toggle').checked = !!settings.email_notifications;
                    document.getElementById('appointment-toggle').checked = !!settings.appointment_reminders;
                    document.getElementById('system-toggle').checked = !!settings.system_alerts;
                    log('Settings loaded successfully');
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            } catch (err) {
                document.getElementById('current-settings').innerHTML = '<span class="status status-error">Failed to load</span>';
                log('Error loading settings: ' + err.message);
            }
        }

        async function updateSetting(key, value) {
            try {
                const res = await fetch('../api_settings.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ [key]: value })
                });
                const text = await res.text();
                console.log('Update API response:', text.substring(0, 200));
                const data = JSON.parse(text);
                if (data.success) {
                    settings[key] = value;
                    log(`Setting updated: ${key} = ${value ? 'ON' : 'OFF'}`);
                    loadSettings();
                } else {
                    log('Failed to update setting: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                log('Network error: ' + err.message);
            }
        }

        async function createTestNotification() {
            const title = document.getElementById('notif-title').value;
            const message = document.getElementById('notif-message').value;
            const type = document.getElementById('notif-type').value;

            if (!title || !message) {
                alert('Please fill in title and message');
                return;
            }

            try {
                const res = await fetch('../api_notifications.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title, message, type })
                });
                const text = await res.text();
                console.log('Create notification response:', text.substring(0, 200));
                const data = JSON.parse(text);
                if (data.success) {
                    log('Test notification created successfully!');
                    alert('Notification created! Check the notifications list below.');
                    document.getElementById('notif-title').value = '';
                    document.getElementById('notif-message').value = '';
                    loadNotifications();
                } else {
                    log('Failed to create notification: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                log('Network error: ' + err.message);
            }
        }

        async function loadNotifications() {
            try {
                const res = await fetch('../api_notifications.php?action=list&limit=10');
                const text = await res.text();
                console.log('Notifications API raw response:', text);
                const data = JSON.parse(text);
                const notifications = data.notifications || [];

                console.log('Loaded notifications:', notifications);

                if (notifications.length === 0) {
                    document.getElementById('notifications-list').innerHTML = '<p style="color: #64748b; text-align: center;">No notifications yet. Click "Create Test Notification" to add one.</p>';
                } else {
                    document.getElementById('notifications-list').innerHTML = notifications.map(n => `
                        <div class="test-row" style="display: block; padding: 1rem; background: ${n.is_read == 0 ? '#f0fdf4' : '#f8fafc'}; border-radius: 8px; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600; color: #0d9488;">${n.title}</span>
                                <span style="font-size: 0.75rem; color: #64748b;">${new Date(n.created_at).toLocaleString()}</span>
                            </div>
                            <div style="color: #334155; font-size: 0.9rem; margin-bottom: 0.5rem;">${n.message}</div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span class="status" style="background: #e0f2fe; color: #0284c7;">${n.type}</span>
                                <span class="status ${n.is_read == 0 ? 'status-success' : 'status-error'}">${n.is_read == 0 ? 'Unread' : 'Read'}</span>
                                ${n.is_read == 0 ? `<button class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;" onclick="markAsRead(${n.notification_id}, this)">Mark Read</button>` : ''}
                            </div>
                        </div>
                    `).join('');
                }
                log('Notifications loaded: ' + notifications.length + ' total');
            } catch (err) {
                document.getElementById('notifications-list').innerHTML = '<span class="status status-error">Failed to load</span>';
                log('Error loading notifications: ' + err.message);
            }
        }

        async function markAsRead(id, btn) {
            try {
                const res = await fetch('../../api_notifications.php?action=read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    log('Notification marked as read');
                    loadNotifications();
                }
            } catch (err) {
                log('Error marking as read: ' + err.message);
            }
        }

        // Initialize
        loadSettings();
        loadNotifications();
        log('Test page initialized');
    </script>
</body>
</html>
