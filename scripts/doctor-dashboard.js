// Doctor Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize navigation
    initDoctorNav();
});

function initDoctorNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const sidebarFooterItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');

    // Handle nav clicks
    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                setActiveNav(this);
                showDoctorView(view);
            }
        });
    });

    // Handle sidebar footer (Settings, Profile)
    sidebarFooterItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                // Remove active from main nav
                navItems.forEach(n => n.classList.remove('active'));
                showDoctorView(view);
            }
        });
    });
}

function setActiveNav(activeItem) {
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
        item.classList.remove('active');
    });
    activeItem.classList.add('active');
}

function showDoctorView(viewId) {
    const mainContent = document.querySelector('.dashboard-main');
    if (!mainContent) return;

    const views = {
        'patients': getPatientsView,
        'reports': getReportsView,
        'appointments': getAppointmentsView,
        'messages': getMessagesView,
        'analytics': getAnalyticsView,
        'settings': getSettingsView,
        'profile': getProfileView
    };

    const viewFunction = views[viewId];
    if (viewFunction) {
        mainContent.innerHTML = viewFunction();
    }
}

function getPatientsView() {
    return document.querySelector('.dashboard-content') ?
        document.querySelector('.dashboard-content').outerHTML :
        '<div class="dashboard-content"><h1 class="dashboard-title">My Patients</h1><p class="dashboard-subtitle">View and manage your connected patients</p></div>';
}

function getReportsView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Reports</h1>
                    <p class="dashboard-subtitle">View patient progress reports and analytics</p>
                </div>
            </div>
            <div class="dashboard-card" style="padding: 2rem;">
                <p style="color: var(--text-secondary);">No reports available yet. Patient reports will appear here once patients share their data with you.</p>
            </div>
        </div>
    `;
}

function getAppointmentsView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Appointments</h1>
                    <p class="dashboard-subtitle">Manage your schedule and patient appointments</p>
                </div>
                <button class="btn btn-gradient">+ New Appointment</button>
            </div>
            <div class="dashboard-card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;">Upcoming Appointments</h3>
                <p style="color: var(--text-secondary);">No upcoming appointments. Your schedule is clear!</p>
            </div>
        </div>
    `;
}

function getMessagesView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Messages</h1>
                    <p class="dashboard-subtitle">Communicate with patients and parents</p>
                </div>
            </div>
            <div class="dashboard-card" style="padding: 2rem;">
                <p style="color: var(--text-secondary);">No messages yet. Messages from parents will appear here.</p>
            </div>
        </div>
    `;
}

function getAnalyticsView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Analytics</h1>
                    <p class="dashboard-subtitle">Practice insights and patient statistics</p>
                </div>
            </div>
            <div class="doctor-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--blue-500);">12</div>
                    <div style="color: var(--text-secondary);">Total Patients</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--green-500);">45</div>
                    <div style="color: var(--text-secondary);">Reports Reviewed</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--purple-500);">8</div>
                    <div style="color: var(--text-secondary);">This Week</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--orange-500);">95%</div>
                    <div style="color: var(--text-secondary);">Satisfaction</div>
                </div>
            </div>
        </div>
    `;
}

function getSettingsView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Settings</h1>
                    <p class="dashboard-subtitle">Manage your account and preferences</p>
                </div>
            </div>
            <div class="dashboard-card" style="padding: 2rem; margin-bottom: 1rem;">
                <h3 style="margin-bottom: 1rem;">Account Settings</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-lg);">
                        <span>Email Notifications</span>
                        <label style="position: relative; display: inline-block; width: 50px; height: 26px;">
                            <input type="checkbox" checked style="opacity: 0; width: 0; height: 0;">
                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--green-500); border-radius: 26px;"></span>
                        </label>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-lg);">
                        <span>SMS Alerts</span>
                        <label style="position: relative; display: inline-block; width: 50px; height: 26px;">
                            <input type="checkbox" style="opacity: 0; width: 0; height: 0;">
                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--slate-300); border-radius: 26px;"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dashboard-card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;">Practice Information</h3>
                <p style="color: var(--text-secondary);">Update your clinic details and specializations.</p>
                <button class="btn btn-outline" style="margin-top: 1rem;">Edit Practice Info</button>
            </div>
        </div>
    `;
}

function getProfileView() {
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">My Profile</h1>
                    <p class="dashboard-subtitle">Manage your professional profile</p>
                </div>
            </div>
            <div class="dashboard-card" style="padding: 2rem;">
                <div style="display: flex; gap: 2rem; align-items: center; margin-bottom: 2rem;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--blue-500), var(--purple-600)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: 700;">
                        DA
                    </div>
                    <div>
                        <h2 style="margin-bottom: 0.5rem;">Dr. Ahmed Hassan</h2>
                        <p style="color: var(--text-secondary);">Pediatric Development Specialist</p>
                        <p style="color: var(--green-500); font-size: 0.875rem;">✓ Verified Healthcare Provider</p>
                    </div>
                </div>
                <button class="btn btn-gradient">Edit Profile</button>
            </div>
        </div>
    `;
}

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = 'doctor-login.php';
    }
}
