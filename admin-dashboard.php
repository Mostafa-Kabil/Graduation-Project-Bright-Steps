<?php
session_start();
// Only allow logged-in admin users
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=11">
    <link rel="stylesheet" href="styles/dashboard.css?v=11">
    <link rel="stylesheet" href="styles/doctor.css?v=11">
    <link rel="stylesheet" href="styles/clinic.css?v=11">
    <link rel="stylesheet" href="styles/admin.css?v=11">
    <link rel="stylesheet" href="styles/settings.css?v=11">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar admin-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile">
                    <div class="user-avatar admin-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div class="user-info">
                        <div class="user-name">Admin Panel</div>
                        <div class="user-badge-text admin-badge-label">Super Admin</div>
                    </div>
                    <div class="admin-shield" title="Admin Access">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" data-view="overview">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                    <span>Overview</span>
                </button>
                <button class="nav-item" data-view="users">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>Users</span>
                </button>
                <button class="nav-item" data-view="clinics">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    <span>Clinics</span>
                </button>
                <button class="nav-item" data-view="subscriptions">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                        <line x1="1" y1="10" x2="23" y2="10" />
                    </svg>
                    <span>Subscriptions</span>
                </button>
                <button class="nav-item" data-view="points">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span>Engagement & Rewards</span>
                </button>
                <button class="nav-item" data-view="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                    <span>Reports</span>
                </button>

                <button class="nav-item" data-view="notifications_mgmt">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span>Notifications</span>
                </button>
                <button class="nav-item" data-view="tickets">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>Contact & Support</span>
                </button>
                <button class="nav-item" data-view="system_health">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <span>System Health</span>
                </button>
                <button class="nav-item" data-view="logs">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <span>Audit Logs</span>
                </button>
                <button class="nav-item" data-view="roles">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M20 8v6M23 11h-6"/>
                    </svg>
                    <span>Roles</span>
                </button>

            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" data-view="settings">
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

        <!-- Main Area -->
        <div class="dashboard-main" style="display:flex; flex-direction:column;">
            <!-- Admin Topbar -->
            <div style="display:flex; justify-content:flex-end; padding:1.25rem 2.5rem 0 2.5rem; background:transparent; position:relative; z-index:100; width:100%;">
                <div style="display:flex; align-items:center; gap:1.5rem;">
                    <!-- Notification Bell -->
                    <div id="admin-topbar-notification" onclick="toggleAdminNotifDropdown()" style="position:relative; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="2" width="22" height="22" onmouseover="this.style.stroke='var(--text-primary)'" onmouseout="this.style.stroke='var(--text-secondary)'">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span id="admin-notif-badge" style="display:none; position:absolute; top:-2px; right:-2px; width:8px; height:8px; background:var(--red-500); border-radius:50%; border:2px solid var(--bg-primary);"></span>
                    </div>
                    <!-- Dropdown -->
                    <div id="admin-notif-dropdown" style="display:none; position:absolute; top:3.5rem; right:2.5rem; width:380px; background:var(--bg-card); border:1px solid var(--border); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.15); overflow:hidden; z-index:1000;">
                        <div style="padding:1.25rem 1.5rem; background:linear-gradient(135deg, #4f46e5, #6366f1); display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <h4 style="margin:0; font-size:1rem; font-weight:700; color:#fff;">Notifications</h4>
                                <span id="admin-notif-count" style="display:none; background:rgba(255,255,255,0.25); color:#fff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px;"></span>
                            </div>
                            <div style="display:flex; gap:0.75rem;">
                                <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); cursor:pointer;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="loadAdminNotifications()">Refresh</span>
                                <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); font-weight:600; cursor:pointer;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="markAllAdminNotifRead()">Mark all read</span>
                            </div>
                        </div>
                        <div id="admin-notif-content" style="max-height:420px; overflow-y:auto; padding:0;">
                            <div style="padding:3rem 1.5rem; text-align:center;">
                                <div style="width:48px; height:48px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                </div>
                                <p style="color:var(--text-secondary); font-size:0.85rem; margin:0;">Loading notifications...</p>
                            </div>
                        </div>
                    </div>
                    <!-- User Avatar -->
                    <div id="admin-topbar-avatar" onclick="showAdminView('settings')" style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, #4f46e5, #6366f1); color:white; display:flex; align-items:center; justify-content:center; font-weight:600; cursor:pointer; font-size:0.9rem;" title="Admin Settings">
                        A
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main id="admin-main-content" style="flex:1; overflow-y:auto;">
                <!-- Content loaded by JavaScript -->
            </main>
        </div>
    </div>

    <!-- Toggles removed and securely relocated to Settings view -->

    <script src="scripts/theme-toggle.js?v=17"></script>
    <script src="scripts/language-toggle.js?v=17"></script>
    <script src="scripts/navigation.js?v=17"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="scripts/admin-dashboard.js?v=21"></script>
    <script src="scripts/admin-clinics-view.js?v=21"></script>
    <script src="scripts/admin-views-extended.js?v=21"></script>
    <script src="scripts/admin-views-part2.js?v=21"></script>
    <script src="scripts/admin-views-part3.js?v=21"></script>
    <script src="scripts/admin-views-part4.js?v=21"></script>
</body>

</html>