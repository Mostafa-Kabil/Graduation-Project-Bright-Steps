<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bright Steps</title>
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
                <div class="user-profile">
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
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4" />
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
                    <h1 class="dashboard-title">My Profile</h1>
                </div>

                <div class="profile-content">
                    <!-- Profile Picture Section -->
                    <div class="profile-picture-section">
                        <div class="profile-picture-large">SJ</div>
                        <button class="btn btn-outline">Change Photo</button>
                    </div>

                    <!-- Profile Form -->
                    <form class="profile-form" id="profile-form">
                        <div class="form-section">
                            <h3 class="form-section-title">Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="first-name">First Name</label>
                                    <input type="text" id="first-name" class="form-input" value="Sarah">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="last-name">Last Name</label>
                                    <input type="text" id="last-name" class="form-input" value="Johnson">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" class="form-input" value="sarah.johnson@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" class="form-input" value="+1 (555) 123-4567">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">Address</h3>
                            <div class="form-group">
                                <label class="form-label" for="address">Street Address</label>
                                <input type="text" id="address" class="form-input" value="123 Family Lane">
                            </div>
                            <div class="form-grid form-grid-3">
                                <div class="form-group">
                                    <label class="form-label" for="city">City</label>
                                    <input type="text" id="city" class="form-input" value="San Francisco">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="state">State</label>
                                    <input type="text" id="state" class="form-input" value="CA">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="zip">ZIP Code</label>
                                    <input type="text" id="zip" class="form-input" value="94102">
                                </div>
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
        document.getElementById('profile-form').addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Profile updated successfully!');
        });
    </script>
</body>

</html>