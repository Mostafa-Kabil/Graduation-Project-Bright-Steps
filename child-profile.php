<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Profile - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/profile.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="index.html" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile" onclick="navigateTo('profile')" style="cursor: pointer;">
                    <div class="user-avatar">SJ</div>
                    <div class="user-info">
                        <div class="user-name">Sarah Johnson</div>
                        <div class="user-badge-text">Premium Member</div>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item" onclick="navigateTo('dashboard')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    <span>Home</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" onclick="navigateTo('settings')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0" />
                    </svg>
                    <span>Settings</span>
                </button>
                <button class="nav-item nav-item-logout" onclick="navigateTo('index')">
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
            <div class="dashboard-content">
                <div class="profile-header">
                    <button class="back-btn" onclick="navigateTo('settings')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Back to Settings
                    </button>
                    <h1 class="dashboard-title">Child Profile</h1>
                </div>

                <div class="profile-content">
                    <!-- Child Picture Section -->
                    <div class="profile-picture-section child-section">
                        <div class="profile-picture-large child-avatar-large">E</div>
                        <div class="child-header-info">
                            <h2>Emma Johnson</h2>
                            <p>15 months old</p>
                        </div>
                        <button class="btn btn-outline">Change Photo</button>
                    </div>

                    <!-- Child Form -->
                    <form class="profile-form" id="child-profile-form">
                        <div class="form-section">
                            <h3 class="form-section-title">Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="child-first-name">First Name</label>
                                    <input type="text" id="child-first-name" class="form-input" value="Emma">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="child-last-name">Last Name</label>
                                    <input type="text" id="child-last-name" class="form-input" value="Johnson">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="birth-date">Date of Birth</label>
                                    <input type="date" id="birth-date" class="form-input" value="2024-08-23">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="gender">Gender</label>
                                    <select id="gender" class="form-input">
                                        <option value="female" selected>Female</option>
                                        <option value="male">Male</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">Growth Measurements</h3>
                            <div class="form-grid form-grid-3">
                                <div class="form-group">
                                    <label class="form-label" for="weight">Weight (kg)</label>
                                    <input type="number" step="0.1" id="weight" class="form-input" value="11.1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="height">Height (cm)</label>
                                    <input type="number" step="0.1" id="height" class="form-input" value="78">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="head">Head Circumference (cm)</label>
                                    <input type="number" step="0.1" id="head" class="form-input" value="46">
                                </div>
                            </div>
                            <p class="form-hint">Last updated: November 15, 2025</p>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">Medical Information</h3>
                            <div class="form-group">
                                <label class="form-label" for="pediatrician">Primary Pediatrician</label>
                                <input type="text" id="pediatrician" class="form-input"
                                    value="Dr. Smith - City Pediatrics">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="allergies">Known Allergies</label>
                                <textarea id="allergies" class="form-input form-textarea"
                                    placeholder="List any known allergies...">None</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="notes">Special Notes</label>
                                <textarea id="notes" class="form-input form-textarea"
                                    placeholder="Any additional notes...">Born via C-section. Passed all hearing tests.</textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-outline"
                                onclick="navigateTo('settings')">Cancel</button>
                            <button type="submit" class="btn btn-gradient">Save Changes</button>
                        </div>
                    </form>
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
    <script>
        document.getElementById('child-profile-form').addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Child profile updated successfully!');
        });
    </script>
</body>

</html>