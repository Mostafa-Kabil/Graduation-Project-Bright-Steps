<?php
require_once "includes/auth_check.php";
$parentId = $_SESSION['id'];
$fname = $_SESSION['fname'];
$lname = $_SESSION['lname'];
$email = $_SESSION['email'] ?? '';
$initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
$stmt = $connect->prepare("SELECT s.plan_name FROM parent_subscription ps INNER JOIN subscription s ON ps.subscription_id = s.subscription_id WHERE ps.parent_id = :pid LIMIT 1");
$stmt->execute(['pid' => $parentId]);
$planname = $stmt->fetchColumn() ?: 'Free';

$stmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$parentId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    $stmt2 = $connect->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)");
    $stmt2->execute([$parentId]);
    $settings = [
        'theme' => 'light',
        'language' => 'en',
        'push_notifications' => 1,
        'email_notifications' => 1,
        'appointment_reminders' => 1,
        'data_sharing' => 1
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/settings.css">
</head>

<body>
    <?php include "includes/header.php"; ?>
    <div class="dashboard-layout">
        <?php include "includes/sidebar.php"; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="settings-header">
                    <h1 class="dashboard-title">Settings</h1>
                    <p class="dashboard-subtitle">Manage your account preferences</p>
                </div>

                <div class="settings-grid">
                    <!-- Account Section -->
                    <div class="settings-section">
                        <h2 class="settings-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Account
                        </h2>
                        <div class="settings-card">
                            <div class="settings-item" onclick="openEditProfileModal()" style="cursor:pointer;">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">My Profile</div>
                                    <div class="settings-item-description">View and edit your personal information</div>
                                </div>
                                <svg class="settings-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </div>
                            <div class="settings-item" onclick="navigateTo('child-profile')">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Child Profile</div>
                                    <div class="settings-item-description">Manage your child's information</div>
                                </div>
                                <svg class="settings-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </div>
                            <div class="settings-item" onclick="openChangePasswordModal()" style="cursor:pointer;">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Change Password</div>
                                    <div class="settings-item-description">Update your account password</div>
                                </div>
                                <svg class="settings-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Section -->
                    <div class="settings-section">
                        <h2 class="settings-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                            Notifications
                        </h2>
                        <div class="settings-card">
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Push Notifications</div>
                                    <div class="settings-item-description">Receive activity reminders on your device
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="updateSetting('push_notifications', this.checked ? 1 : 0)" <?php echo ($settings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Email Updates</div>
                                    <div class="settings-item-description">Weekly progress reports via email</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="updateSetting('email_notifications', this.checked ? 1 : 0)" <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Appointment Reminders</div>
                                    <div class="settings-item-description">Get notified before scheduled appointments
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="updateSetting('appointment_reminders', this.checked ? 1 : 0)" <?php echo ($settings['appointment_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preferences Section -->
                    <div class="settings-section">
                        <h2 class="settings-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09" />
                            </svg>
                            Preferences
                        </h2>
                        <div class="settings-card">
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Language</div>
                                    <div class="settings-item-description">Choose your preferred language</div>
                                </div>
                                <select class="settings-select" onchange="updateSetting('language', this.value)">
                                    <option value="en" <?php echo ($settings['language'] == 'en') ? 'selected' : ''; ?>>English</option>
                                    <option value="es" <?php echo ($settings['language'] == 'es') ? 'selected' : ''; ?>>Español</option>
                                    <option value="fr" <?php echo ($settings['language'] == 'fr') ? 'selected' : ''; ?>>Français</option>
                                    <option value="ar" <?php echo ($settings['language'] == 'ar') ? 'selected' : ''; ?>>العربية</option>
                                </select>
                            </div>
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Data Sharing</div>
                                    <div class="settings-item-description">Share progress with healthcare providers
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="updateSetting('data_sharing', this.checked ? 1 : 0)" <?php echo ($settings['data_sharing'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Section -->
                    <div class="settings-section">
                        <h2 class="settings-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            Subscription
                        </h2>
                        <div class="settings-card">
                            <div class="subscription-info">
                                <div class="subscription-badge">Premium</div>
                                <div class="subscription-details">
                                    <p>Your next billing date is <strong>March 15, 2026</strong></p>
                                    <p class="subscription-price">$9.99/month</p>
                                </div>
                            </div>
                            <button class="btn btn-outline btn-full" onclick="window.location.href='payment.php'">Manage
                                Subscription</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Floating Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path
                d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <script src="scripts/dashboard.js?v=7"></script>
    <script>
        function openEditProfileModal() {
            let existing = document.getElementById('edit-profile-modal');
            if (existing) existing.remove();
            const modal = document.createElement('div');
            modal.id = 'edit-profile-modal';
            modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:var(--white,#fff);border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Edit Profile</h2>
                    <input type="text" id="ep-fname" value="${<?php echo htmlspecialchars(json_encode($fname, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>.replace(/(^"|"$)/g, '')}" placeholder="First Name" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                    <input type="text" id="ep-lname" value="${<?php echo htmlspecialchars(json_encode($lname, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>.replace(/(^"|"$)/g, '')}" placeholder="Last Name" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                    <input type="email" id="ep-email" value="${<?php echo htmlspecialchars(json_encode($email, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>.replace(/(^"|"$)/g, '')}" placeholder="Email Address" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                    <button onclick="saveProfile()" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#6C63FF,#a78bfa);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Save Changes</button>
                    <div id="ep-error" style="color:#ef4444;font-size:0.85rem;margin-top:0.5rem;"></div>
                    <div id="ep-success" style="color:#22c55e;font-size:0.85rem;margin-top:0.5rem;"></div>
                </div>
            </div>`;
            document.body.appendChild(modal);
        }
        
        async function saveProfile() {
            const formData = new FormData();
            formData.append('first_name', document.getElementById('ep-fname').value);
            formData.append('last_name', document.getElementById('ep-lname').value);
            formData.append('email', document.getElementById('ep-email').value);
            
            try {
                const res = await fetch('api_save_profile.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('ep-success').textContent = data.message;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    document.getElementById('ep-error').textContent = data.errors ? data.errors.join(', ') : data.error;
                }
            } catch(e) { document.getElementById('ep-error').textContent = 'Network error'; }
        }

        function openChangePasswordModal() {
            let existing = document.getElementById('change-pwd-modal');
            if (existing) existing.remove();
            const modal = document.createElement('div');
            modal.id = 'change-pwd-modal';
            modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:var(--white,#fff);border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Change Password</h2>
                    <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem;" id="cp-desc">We will send a verification code to your email.</p>
                    
                    <div id="cp-step1">
                        <button onclick="sendChangePwdCode()" id="btn-send-code" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#6C63FF,#a78bfa);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Send Verification Code</button>
                    </div>

                    <div id="cp-step2" style="display:none;">
                        <input type="text" id="cp-code" placeholder="6-digit Verification Code" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;text-align:center;letter-spacing:0.25rem;">
                        <input type="password" id="cp-current" placeholder="Current password" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                        <input type="password" id="cp-new" placeholder="New password (min 8 chars)" style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                        <button onclick="verifyAndChangePassword()" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#6C63FF,#a78bfa);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Update Password</button>
                    </div>

                    <div id="cp-error" style="color:#ef4444;font-size:0.85rem;margin-top:0.5rem;"></div>
                    <div id="cp-success" style="color:#22c55e;font-size:0.85rem;margin-top:0.5rem;"></div>
                </div>
            </div>
            `;
            document.body.appendChild(modal);
        }

        async function sendChangePwdCode() {
            const btn = document.getElementById('btn-send-code');
            const err = document.getElementById('cp-error');
            const suc = document.getElementById('cp-success');
            err.textContent = ''; suc.textContent = '';
            btn.disabled = true; btn.textContent = 'Sending...';

            try {
                const res = await fetch('api_email_verify.php?action=send-change-pw-code', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    suc.textContent = 'Code sent to your email!';
                    document.getElementById('cp-step1').style.display = 'none';
                    document.getElementById('cp-step2').style.display = 'block';
                    document.getElementById('cp-desc').textContent = 'Enter the code and your passwords to update.';
                } else {
                    err.textContent = data.error;
                    btn.disabled = false; btn.textContent = 'Send Verification Code';
                }
            } catch(e) { err.textContent = 'Network error'; btn.disabled = false; btn.textContent = 'Send Verification Code'; }
        }

        async function verifyAndChangePassword() {
            const code = document.getElementById('cp-code').value;
            const current = document.getElementById('cp-current').value;
            const newPwd = document.getElementById('cp-new').value;
            const err = document.getElementById('cp-error');
            const suc = document.getElementById('cp-success');
            err.textContent = ''; suc.textContent = '';

            if (!code || !current || !newPwd) { err.textContent = 'All fields are required'; return; }
            if (newPwd.length < 8) { err.textContent = 'New password must be at least 8 characters'; return; }

            try {
                const res = await fetch('api_email_verify.php?action=change-password-verify', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code, current_password: current, new_password: newPwd })
                });
                const data = await res.json();
                if (data.success) {
                    suc.textContent = data.message;
                    setTimeout(() => { const m = document.getElementById('change-pwd-modal'); if (m) m.remove(); }, 2000);
                } else { err.textContent = data.error; }
            } catch (e) { err.textContent = 'Network error'; }
        }

        async function updateSetting(key, value) {
            try {
                const res = await fetch('api_settings.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ [key]: value })
                });
                const data = await res.json();
                if (data.success) {
                    console.log('Setting updated:', key, value);
                    if (key === 'language') {
                        window.location.reload();
                    }
                } else {
                    console.error('Failed to update setting', data.error);
                }
            } catch (error) {
                console.error('Network error during updateSetting:', error);
            }
        }
    </script>
</body>

</html>