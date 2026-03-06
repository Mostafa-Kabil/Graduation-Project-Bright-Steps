<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Doctor Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/doctor.css">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/profile.css">
    <link rel="stylesheet" href="styles/dr-settings.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar doctor-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile">
                    <div class="user-avatar doctor-avatar">DS</div>
                    <div class="user-info">
                        <div class="user-name">Dr. Sarah Mitchell</div>
                        <div class="user-badge-text">Pediatrician</div>
                    </div>
                    <div class="verified-badge" title="Verified Provider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item" onclick="window.location.href='doctor-dashboard.php'">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>My Patients</span>
                </button>
                <button class="nav-item" onclick="window.location.href='doctor-dashboard.php?view=reports'">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                    </svg>
                    <span>Reports</span>
                </button>
                <button class="nav-item" onclick="window.location.href='doctor-dashboard.php?view=appointments'">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span>Appointments</span>
                </button>
                <button class="nav-item" onclick="window.location.href='doctor-dashboard.php?view=messages'">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Messages</span>
                </button>
                <button class="nav-item" onclick="window.location.href='doctor-dashboard.php?view=analytics'">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                    <span>Analytics</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span>Settings</span>
                </button>
                <button class="nav-item nav-item-logout" onclick="handleLogout()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">

            <!-- ═══════════════════════════════════════ -->
            <!-- VIEW 1: Settings Grid (default)        -->
            <!-- ═══════════════════════════════════════ -->
            <div class="dashboard-content" id="settings-view">
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
                            <div class="settings-item" onclick="showProfileView()">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">My Profile</div>
                                    <div class="settings-item-description">View and edit your personal & professional
                                        information</div>
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
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Email Updates</div>
                                    <div class="settings-item-description">Weekly progress reports via email</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
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
                                    <input type="checkbox" checked>
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
                                <select class="settings-select">
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="fr">Français</option>
                                    <option value="ar">العربية</option>
                                </select>
                            </div>
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <div class="settings-item-label">Data Sharing</div>
                                    <div class="settings-item-description">Share progress with healthcare providers
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
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
                            <button class="btn btn-outline btn-full">Manage Subscription</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- VIEW 2: My Profile (hidden by default) -->
            <!-- ═══════════════════════════════════════ -->
            <div class="dashboard-content" id="profile-view" style="display: none;">
                <div class="profile-header">
                    <button class="back-btn" onclick="showSettingsView()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Back to Settings
                    </button>
                    <h1 class="dashboard-title">My Profile</h1>
                    <p class="dashboard-subtitle">Manage your personal information</p>
                </div>

                <div class="profile-content">
                    <!-- Profile Photo Section -->
                    <div class="dr-profile-photo-section">
                        <div class="dr-avatar-wrapper" onclick="document.getElementById('photo-upload').click()"
                            title="Change profile photo">
                            <div class="dr-avatar-large" id="avatar-display">SM</div>
                            <div class="dr-avatar-overlay">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                    <circle cx="12" cy="13" r="4" />
                                </svg>
                            </div>
                            <input type="file" id="photo-upload" accept="image/*" style="display: none;">
                        </div>
                        <div class="dr-profile-info">
                            <h2>Dr. Sarah Mitchell</h2>
                            <p class="dr-specialty-text">Pediatrician</p>
                            <p class="dr-verified-text">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                                Verified Healthcare Provider
                            </p>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <form class="dr-profile-form" id="dr-profile-form" novalidate>

                        <!-- Section 1: Personal Information -->
                        <div class="dr-form-section">
                            <div class="dr-form-section-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <h3 class="dr-form-section-title">Personal Information</h3>
                            </div>
                            <div class="dr-form-grid">
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-fullname">Full Name <span
                                            class="required">*</span></label>
                                    <input type="text" id="dr-fullname" class="dr-form-input" value="Dr. Sarah Mitchell"
                                        required>
                                </div>
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-email">Email Address <span
                                            class="required">*</span></label>
                                    <input type="email" id="dr-email" class="dr-form-input"
                                        value="sarah.mitchell@brightsteps.com" required>
                                </div>
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-phone">Phone Number</label>
                                    <input type="tel" id="dr-phone" class="dr-form-input" value="+1 (555) 987-6543">
                                </div>
                            </div>

                            <!-- Change Password -->
                            <button type="button" class="dr-password-toggle-btn" id="toggle-password-btn"
                                onclick="togglePasswordFields()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Change Password
                            </button>
                            <div class="dr-password-fields" id="password-fields">
                                <div class="dr-form-grid">
                                    <div class="dr-form-group">
                                        <label class="dr-form-label" for="dr-current-password">Current Password</label>
                                        <input type="password" id="dr-current-password" class="dr-form-input"
                                            placeholder="Enter current password">
                                    </div>
                                    <div class="dr-form-group">
                                        <label class="dr-form-label" for="dr-new-password">New Password</label>
                                        <input type="password" id="dr-new-password" class="dr-form-input"
                                            placeholder="Enter new password">
                                    </div>
                                    <div class="dr-form-group">
                                        <label class="dr-form-label" for="dr-confirm-password">Confirm New
                                            Password</label>
                                        <input type="password" id="dr-confirm-password" class="dr-form-input"
                                            placeholder="Confirm new password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Professional Information -->
                        <div class="dr-form-section">
                            <div class="dr-form-section-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5" />
                                </svg>
                                <h3 class="dr-form-section-title">Professional Information</h3>
                            </div>
                            <div class="dr-form-grid">
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-specialty">Specialty <span
                                            class="required">*</span></label>
                                    <select id="dr-specialty" class="dr-form-select" required>
                                        <option value="pediatrician" selected>Pediatrician</option>
                                        <option value="child-psychiatrist">Child Psychiatrist</option>
                                        <option value="developmental-pediatrician">Developmental Pediatrician</option>
                                        <option value="neurologist">Pediatric Neurologist</option>
                                        <option value="speech-therapist">Speech-Language Pathologist</option>
                                        <option value="occupational-therapist">Occupational Therapist</option>
                                        <option value="behavioral-therapist">Behavioral Therapist</option>
                                        <option value="psychologist">Child Psychologist</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-experience">Years of Experience <span
                                            class="required">*</span></label>
                                    <input type="number" id="dr-experience" class="dr-form-input" value="10" min="0"
                                        max="60" required>
                                </div>
                                <div class="dr-form-group full-width">
                                    <label class="dr-form-label" for="dr-qualifications">Certifications</label>
                                    <input type="text" id="dr-qualifications" class="dr-form-input"
                                        value="MD, Board Certified Pediatrician, FAAP"
                                        placeholder="e.g. MD, FAAP, Board Certified">
                                </div>
                                <div class="dr-form-group full-width">
                                    <label class="dr-form-label" for="dr-bio">Bio </label>
                                    <textarea id="dr-bio" class="dr-form-input dr-form-textarea"
                                        placeholder="Write a short bio about your practice and expertise...">Passionate pediatrician with over 10 years of experience in child development and early intervention. Specializing in developmental milestones tracking and parental guidance for children aged 0-5 years.</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Clinic Information (Read-Only) -->
                        <div class="dr-form-section">
                            <div class="dr-form-section-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                <h3 class="dr-form-section-title">Clinic Information</h3>
                                <span class="dr-readonly-badge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                   
                                </span>
                            </div>
                            <div class="dr-form-grid">
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-clinic-name">Clinic Name</label>
                                    <input type="text" id="dr-clinic-name" class="dr-form-input readonly"
                                        value="Bright Steps Pediatric Center" readonly>
                                </div>
                                <div class="dr-form-group">
                                    <label class="dr-form-label" for="dr-clinic-location">Clinic Location</label>
                                    <input type="text" id="dr-clinic-location" class="dr-form-input readonly"
                                        value="456 Healthcare Blvd, San Francisco, CA 94102" readonly>
                                </div>
                            </div>
                            
                        </div>

                        <!-- Section 4: Availability Settings -->
                        <div class="dr-form-section">
                            <div class="dr-form-section-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                                <h3 class="dr-form-section-title">Availability Settings</h3>
                            </div>

                            <!-- Working Days -->
                            <label class="dr-form-label" style="margin-bottom: 0.75rem; display: block;">Working
                                Days</label>
                            <div class="dr-days-grid">
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-sat" name="working-days" value="saturday">
                                    <label for="day-sat">Sat</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-sun" name="working-days" value="sunday" checked>
                                    <label for="day-sun">Sun</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-mon" name="working-days" value="monday" checked>
                                    <label for="day-mon">Mon</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-tue" name="working-days" value="tuesday" checked>
                                    <label for="day-tue">Tue</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-wed" name="working-days" value="wednesday" checked>
                                    <label for="day-wed">Wed</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-thu" name="working-days" value="thursday" checked>
                                    <label for="day-thu">Thu</label>
                                </div>
                                <div class="dr-day-checkbox">
                                    <input type="checkbox" id="day-fri" name="working-days" value="friday">
                                    <label for="day-fri">Fri</label>
                                </div>
                            </div>

                            <!-- Working Hours -->
                            <label class="dr-form-label" style="margin-bottom: 0.75rem; display: block;">Working
                                Hours</label>
                            <div class="dr-hours-row">
                                <label for="dr-start-time">From</label>
                                <input type="time" id="dr-start-time" class="dr-time-input" value="09:00">
                                <span class="dr-hours-separator">—</span>
                                <label for="dr-end-time">To</label>
                                <input type="time" id="dr-end-time" class="dr-time-input" value="17:00">
                            </div>

                            <!-- Consultation Types -->
                            <label class="dr-form-label"
                                style="margin-top: 1.25rem; margin-bottom: 0.5rem; display: block;">Consultation
                                Types</label>
                            <div class="dr-consult-types">
                                <div class="dr-consult-toggle">
                                    <input type="checkbox" id="consult-online" name="consult-type" value="online"
                                        checked>
                                    <label for="consult-online">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path
                                                d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14" />
                                            <rect x="1" y="6" width="14" height="12" rx="2" ry="2" />
                                        </svg>
                                        Online
                                    </label>
                                </div>
                                <div class="dr-consult-toggle">
                                    <input type="checkbox" id="consult-onsite" name="consult-type" value="onsite"
                                        checked>
                                    <label for="consult-onsite">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                            <polyline points="9 22 9 12 15 12 15 22" />
                                        </svg>
                                        On-site
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="dr-form-actions">
                            <button type="button" class="btn btn-outline" onclick="showSettingsView()">Cancel</button>
                            <button type="submit" class="btn btn-gradient">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <!-- Toast Notification -->
    <div class="dr-toast" id="dr-toast">
        <svg id="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
        <span id="toast-message">Profile updated successfully!</span>
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

    <script>
        // ── View Switching ──
        function showProfileView() {
            document.getElementById('settings-view').style.display = 'none';
            document.getElementById('profile-view').style.display = 'block';
            window.scrollTo(0, 0);
        }

        function showSettingsView() {
            document.getElementById('profile-view').style.display = 'none';
            document.getElementById('settings-view').style.display = 'block';
            window.scrollTo(0, 0);
        }

        // ── Photo Upload Preview ──
        document.getElementById('photo-upload').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image must be smaller than 5MB', 'error');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    const avatar = document.getElementById('avatar-display');
                    avatar.innerHTML = '<img src="' + event.target.result + '" alt="Profile Photo">';
                };
                reader.readAsDataURL(file);
            }
        });

        // ── Password Fields Toggle ──
        function togglePasswordFields() {
            const fields = document.getElementById('password-fields');
            const btn = document.getElementById('toggle-password-btn');
            fields.classList.toggle('visible');
            if (fields.classList.contains('visible')) {
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel Password Change';
                document.getElementById('dr-current-password').focus();
            } else {
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Change Password';
                document.getElementById('dr-current-password').value = '';
                document.getElementById('dr-new-password').value = '';
                document.getElementById('dr-confirm-password').value = '';
            }
        }

        // ── Form Validation & Submission ──
        document.getElementById('dr-profile-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const fullName = document.getElementById('dr-fullname').value.trim();
            const email = document.getElementById('dr-email').value.trim();
            const specialty = document.getElementById('dr-specialty').value;
            const experience = document.getElementById('dr-experience').value;

            if (!fullName) {
                showToast('Please enter your full name', 'error');
                document.getElementById('dr-fullname').focus();
                return;
            }
            if (!email) {
                showToast('Please enter your email address', 'error');
                document.getElementById('dr-email').focus();
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showToast('Please enter a valid email address', 'error');
                document.getElementById('dr-email').focus();
                return;
            }
            if (!specialty) {
                showToast('Please select your specialty', 'error');
                document.getElementById('dr-specialty').focus();
                return;
            }
            if (!experience || experience < 0) {
                showToast('Please enter valid years of experience', 'error');
                document.getElementById('dr-experience').focus();
                return;
            }

            // Password validation
            const passwordFieldsVisible = document.getElementById('password-fields').classList.contains('visible');
            if (passwordFieldsVisible) {
                const currentPw = document.getElementById('dr-current-password').value;
                const newPw = document.getElementById('dr-new-password').value;
                const confirmPw = document.getElementById('dr-confirm-password').value;

                if (!currentPw) {
                    showToast('Please enter your current password', 'error');
                    document.getElementById('dr-current-password').focus();
                    return;
                }
                if (!newPw || newPw.length < 6) {
                    showToast('New password must be at least 6 characters', 'error');
                    document.getElementById('dr-new-password').focus();
                    return;
                }
                if (newPw !== confirmPw) {
                    showToast('Passwords do not match', 'error');
                    document.getElementById('dr-confirm-password').focus();
                    return;
                }
            }

            showToast('Profile updated successfully!', 'success');
        });

        // ── Toast Notification ──
        function showToast(message, type) {
            const toast = document.getElementById('dr-toast');
            const toastMsg = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            toastMsg.textContent = message;

            if (type === 'success') {
                toastIcon.innerHTML = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />';
            } else {
                toastIcon.innerHTML = '<circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />';
            }

            toast.className = 'dr-toast ' + type;
            setTimeout(function () { toast.classList.add('show'); }, 50);
            setTimeout(function () { toast.classList.remove('show'); }, 3500);
        }

        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'doctor-login.php';
            }
        }
    </script>
</body>

</html>