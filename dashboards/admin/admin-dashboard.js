// ─────────────────────────────────────────────────────────────
//  Admin Dashboard – View Controller
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initAdminNav();
    showAdminView('overview'); // default view
});

function initAdminNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const footerItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                navItems.forEach(n => n.classList.remove('active'));
                footerItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                showAdminView(view);
            }
        });
    });

    footerItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                navItems.forEach(n => n.classList.remove('active'));
                footerItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                showAdminView(view);
            }
        });
    });
}

function showAdminView(viewId) {
    const main = document.getElementById('admin-main-content');
    if (!main) return;

    const views = {
        'overview': getOverviewView,
        'users': getUsersView,
        'clinics': getClinicsView,
        'subscriptions': getSubscriptionsView,
        'points': getPointsView,
        'reports': getReportsView,
        'settings': getSettingsView
    };

    const fn = views[viewId];
    if (fn) {
        main.innerHTML = fn();
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    }
}

// ── Overview View ────────────────────────────────────────────
function getOverviewView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Platform Overview</h1>
                <p class="dashboard-subtitle">Bright Steps system-wide analytics and activity</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Report
                </button>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-indigo">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">1,248</div>
                    <div class="admin-stat-label">Total Users</div>
                    <div class="admin-stat-trend trend-up">↑ 12% this month</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-teal">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">24</div>
                    <div class="admin-stat-label">Active Clinics</div>
                    <div class="admin-stat-trend trend-up">↑ 3 new</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-emerald">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">$87.2K</div>
                    <div class="admin-stat-label">Total Revenue</div>
                    <div class="admin-stat-trend trend-up">↑ 22% this month</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-amber">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">892</div>
                    <div class="admin-stat-label">Active Subscriptions</div>
                    <div class="admin-stat-trend trend-up">↑ 8% this month</div>
                </div>
            </div>
        </div>

        <div class="overview-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Recent Activity</h2></div>
                <div class="activity-feed">
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-green"></div>
                        <div class="activity-info"><div class="activity-text"><strong>New Clinic</strong> registered: Sunrise Pediatrics</div><div class="activity-time">2 hours ago</div></div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-blue"></div>
                        <div class="activity-info"><div class="activity-text"><strong>New User</strong> signed up: Ahmed Hassan (Parent)</div><div class="activity-time">3 hours ago</div></div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-purple"></div>
                        <div class="activity-info"><div class="activity-text"><strong>Subscription Upgrade</strong>: Sarah Johnson → Premium</div><div class="activity-time">5 hours ago</div></div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-yellow"></div>
                        <div class="activity-info"><div class="activity-text"><strong>Payment Received</strong>: $300.00 from Michael Thompson</div><div class="activity-time">6 hours ago</div></div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-green"></div>
                        <div class="activity-info"><div class="activity-text"><strong>New Specialist</strong> added: Dr. Layla Noor at City Kids Care</div><div class="activity-time">8 hours ago</div></div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot activity-dot-red"></div>
                        <div class="activity-info"><div class="activity-text"><strong>Alert</strong>: 3 children flagged for developmental review</div><div class="activity-time">1 day ago</div></div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">User Distribution</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="distribution-bar-wrap">
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>Parents</div><div class="dist-bar"><div class="dist-fill" style="width:65%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div></div><div class="dist-value">812</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#0d9488;"></span>Doctors</div><div class="dist-bar"><div class="dist-fill" style="width:22%;background:linear-gradient(90deg,#0d9488,#14b8a6);"></div></div><div class="dist-value">275</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#f59e0b;"></span>Clinics</div><div class="dist-bar"><div class="dist-fill" style="width:8%;background:linear-gradient(90deg,#f59e0b,#fbbf24);"></div></div><div class="dist-value">24</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#ec4899;"></span>Admins</div><div class="dist-bar"><div class="dist-fill" style="width:2%;background:linear-gradient(90deg,#ec4899,#f472b6);"></div></div><div class="dist-value">5</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Users View ───────────────────────────────────────────────
function getUsersView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">User Management</h1>
                <p class="dashboard-subtitle">Manage all registered users across the platform</p>
            </div>
            <div class="header-actions-inline">
                <select class="search-input" style="width:auto;">
                    <option>All Roles</option>
                    <option>Parents</option>
                    <option>Doctors</option>
                    <option>Admins</option>
                </select>
                <button class="btn btn-gradient">+ Add User</button>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Users</h2>
                <input type="text" class="search-input" placeholder="Search by name or email...">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Email</th><th>Joined</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar">SJ</div><div><div class="patient-name">Sarah Johnson</div><div class="patient-details">ID: #1001</div></div></div></td>
                            <td><span class="role-badge role-parent">Parent</span></td>
                            <td>sarah.j@email.com</td>
                            <td>Jan 15, 2025</td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">Edit</button><button class="btn btn-sm btn-outline" style="margin-left:0.5rem;color:var(--red-500);">Suspend</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#0d9488,#0891b2);">SM</div><div><div class="patient-name">Dr. Sarah Mitchell</div><div class="patient-details">ID: #1002</div></div></div></td>
                            <td><span class="role-badge role-doctor">Doctor</span></td>
                            <td>sarah.m@citykids.com</td>
                            <td>Feb 10, 2025</td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">Edit</button><button class="btn btn-sm btn-outline" style="margin-left:0.5rem;color:var(--red-500);">Suspend</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">MT</div><div><div class="patient-name">Michael Thompson</div><div class="patient-details">ID: #1003</div></div></div></td>
                            <td><span class="role-badge role-parent">Parent</span></td>
                            <td>michael.t@email.com</td>
                            <td>Mar 5, 2025</td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">Edit</button><button class="btn btn-sm btn-outline" style="margin-left:0.5rem;color:var(--red-500);">Suspend</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">AH</div><div><div class="patient-name">Dr. Ahmed Hassan</div><div class="patient-details">ID: #1004</div></div></div></td>
                            <td><span class="role-badge role-doctor">Doctor</span></td>
                            <td>ahmed.h@sunrisepeds.com</td>
                            <td>Apr 20, 2025</td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">Edit</button><button class="btn btn-sm btn-outline" style="margin-left:0.5rem;color:var(--red-500);">Suspend</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#ec4899,#db2777);">JW</div><div><div class="patient-name">Jennifer Williams</div><div class="patient-details">ID: #1005</div></div></div></td>
                            <td><span class="role-badge role-parent">Parent</span></td>
                            <td>jennifer.w@email.com</td>
                            <td>May 12, 2025</td>
                            <td><span class="status-badge status-warning">Inactive</span></td>
                            <td><button class="btn btn-sm btn-outline">Edit</button><button class="btn btn-sm btn-outline" style="margin-left:0.5rem;color:var(--green-500);">Activate</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

// ── Clinics View ─────────────────────────────────────────────
function getClinicsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Clinic Management</h1>
                <p class="dashboard-subtitle">Manage all registered clinics on the platform</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient">+ Register Clinic</button>
            </div>
        </div>

        <div class="admin-stats-grid" style="grid-template-columns: repeat(3,1fr);">
            <div class="admin-stat-card admin-stat-teal">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">24</div><div class="admin-stat-label">Total Clinics</div></div>
            </div>
            <div class="admin-stat-card admin-stat-indigo">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">156</div><div class="admin-stat-label">Total Specialists</div></div>
            </div>
            <div class="admin-stat-card admin-stat-emerald">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">22</div><div class="admin-stat-label">Verified</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Clinics</h2>
                <input type="text" class="search-input" placeholder="Search clinics...">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Clinic</th><th>Location</th><th>Specialists</th><th>Patients</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#0d9488,#0891b2);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="patient-name">City Kids Care</div><div class="patient-details">info@citykidscare.com</div></div></div></td>
                            <td>123 Downtown Blvd</td><td>8</td><td>143</td>
                            <td><span class="rating-badge">★ 4.8</span></td>
                            <td><span class="status-badge status-active">Verified</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="patient-name">Sunrise Pediatrics</div><div class="patient-details">hello@sunrisepeds.com</div></div></div></td>
                            <td>456 Oak Avenue</td><td>5</td><td>98</td>
                            <td><span class="rating-badge">★ 4.6</span></td>
                            <td><span class="status-badge status-active">Verified</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#ec4899,#db2777);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="patient-name">Happy Smiles Clinic</div><div class="patient-details">contact@happysmiles.com</div></div></div></td>
                            <td>789 Maple Street</td><td>6</td><td>112</td>
                            <td><span class="rating-badge">★ 4.9</span></td>
                            <td><span class="status-badge status-active">Verified</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="patient-name">Little Stars Wellness</div><div class="patient-details">info@littlestars.com</div></div></div></td>
                            <td>321 Elm Drive</td><td>3</td><td>45</td>
                            <td><span class="rating-badge">★ 4.4</span></td>
                            <td><span class="status-badge status-warning">Pending</span></td>
                            <td><button class="btn btn-sm btn-outline" style="color:var(--green-500);">Approve</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

// ── Subscriptions View ───────────────────────────────────────
function getSubscriptionsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Subscription Plans</h1>
                <p class="dashboard-subtitle">Manage plans and track subscriber metrics</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient">+ Create Plan</button>
            </div>
        </div>

        <div class="plans-grid">
            <div class="plan-card plan-free">
                <div class="plan-header">
                    <h3 class="plan-name">Free Trial</h3>
                    <div class="plan-price">$0<span>/mo</span></div>
                </div>
                <div class="plan-stats">
                    <div class="plan-stat"><span class="plan-stat-value">356</span><span class="plan-stat-label">Active Users</span></div>
                    <div class="plan-stat"><span class="plan-stat-value">28%</span><span class="plan-stat-label">Conversion</span></div>
                </div>
                <ul class="plan-features">
                    <li>Basic growth tracking</li>
                    <li>1 child profile</li>
                    <li>Monthly reports</li>
                </ul>
                <button class="btn btn-outline" style="width:100%;">Edit Plan</button>
            </div>
            <div class="plan-card plan-standard">
                <div class="plan-header">
                    <h3 class="plan-name">Standard</h3>
                    <div class="plan-price">$9.99<span>/mo</span></div>
                </div>
                <div class="plan-stats">
                    <div class="plan-stat"><span class="plan-stat-value">412</span><span class="plan-stat-label">Active Users</span></div>
                    <div class="plan-stat"><span class="plan-stat-value">$4,116</span><span class="plan-stat-label">MRR</span></div>
                </div>
                <ul class="plan-features">
                    <li>AI-powered insights</li>
                    <li>Up to 3 child profiles</li>
                    <li>Weekly reports</li>
                    <li>Speech analysis</li>
                </ul>
                <button class="btn btn-outline" style="width:100%;">Edit Plan</button>
            </div>
            <div class="plan-card plan-premium">
                <div class="plan-badge">Most Popular</div>
                <div class="plan-header">
                    <h3 class="plan-name">Premium</h3>
                    <div class="plan-price">$24.99<span>/mo</span></div>
                </div>
                <div class="plan-stats">
                    <div class="plan-stat"><span class="plan-stat-value">124</span><span class="plan-stat-label">Active Users</span></div>
                    <div class="plan-stat"><span class="plan-stat-value">$3,099</span><span class="plan-stat-label">MRR</span></div>
                </div>
                <ul class="plan-features">
                    <li>Everything in Standard</li>
                    <li>Unlimited child profiles</li>
                    <li>Doctor consultations</li>
                    <li>Priority support</li>
                    <li>1-on-1 specialist access</li>
                </ul>
                <button class="btn btn-gradient" style="width:100%;">Edit Plan</button>
            </div>
        </div>

        <div class="section-card" style="margin-top:2rem;">
            <div class="section-card-header"><h2 class="section-heading">Revenue by Plan</h2></div>
            <div style="padding:1.5rem;">
                <div class="revenue-row"><span class="revenue-plan">Free Trial → Standard</span><span class="revenue-count">28% conversion rate</span><span class="revenue-amount">$4,116/mo</span></div>
                <div class="revenue-row"><span class="revenue-plan">Standard → Premium</span><span class="revenue-count">15% upgrade rate</span><span class="revenue-amount">$3,099/mo</span></div>
                <div class="revenue-row revenue-total"><span class="revenue-plan">Total MRR</span><span></span><span class="revenue-amount">$7,215/mo</span></div>
            </div>
        </div>
    </div>`;
}

// ── Points System View ───────────────────────────────────────
function getPointsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Points & Rewards System</h1>
                <p class="dashboard-subtitle">Configure points rules and manage wallets</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient">+ Add Points Rule</button>
            </div>
        </div>

        <div class="admin-stats-grid" style="grid-template-columns: repeat(3,1fr);">
            <div class="admin-stat-card admin-stat-amber">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">45,200</div><div class="admin-stat-label">Total Points Issued</div></div>
            </div>
            <div class="admin-stat-card admin-stat-indigo">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 3H8l-2 4h12l-2-4z"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">312</div><div class="admin-stat-label">Active Wallets</div></div>
            </div>
            <div class="admin-stat-card admin-stat-emerald">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">1,890</div><div class="admin-stat-label">Badges Earned</div></div>
            </div>
        </div>

        <div class="overview-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Points Rules</h2></div>
                <div class="clinic-table-wrap">
                    <table class="clinic-table">
                        <thead><tr><th>Action</th><th>Points</th><th>Type</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr><td>Daily Login</td><td class="points-plus">+10</td><td>Deposit</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                            <tr><td>Growth Measurement</td><td class="points-plus">+25</td><td>Deposit</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                            <tr><td>Voice Sample Upload</td><td class="points-plus">+50</td><td>Deposit</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                            <tr><td>Complete Weekly Goal</td><td class="points-plus">+100</td><td>Deposit</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                            <tr><td>Redeem Badge</td><td class="points-minus">-200</td><td>Withdrawal</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                            <tr><td>Missed Check-in</td><td class="points-minus">-5</td><td>Withdrawal</td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Top Wallets</h2></div>
                <div class="patients-list">
                    <div class="patient-row">
                        <div class="rank-badge">1</div>
                        <div class="patient-avatar">EJ</div>
                        <div class="patient-info"><div class="patient-name">Emma Johnson</div><div class="patient-details">8 badges earned</div></div>
                        <div class="wallet-points">2,450 pts</div>
                    </div>
                    <div class="patient-row">
                        <div class="rank-badge">2</div>
                        <div class="patient-avatar" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">OW</div>
                        <div class="patient-info"><div class="patient-name">Olivia Williams</div><div class="patient-details">6 badges earned</div></div>
                        <div class="wallet-points">1,890 pts</div>
                    </div>
                    <div class="patient-row">
                        <div class="rank-badge">3</div>
                        <div class="patient-avatar" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">NS</div>
                        <div class="patient-info"><div class="patient-name">Noah Smith</div><div class="patient-details">5 badges earned</div></div>
                        <div class="wallet-points">1,620 pts</div>
                    </div>
                    <div class="patient-row">
                        <div class="rank-badge">4</div>
                        <div class="patient-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">LT</div>
                        <div class="patient-info"><div class="patient-name">Liam Thompson</div><div class="patient-details">4 badges earned</div></div>
                        <div class="wallet-points">1,340 pts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Reports View ─────────────────────────────────────────────
function getReportsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">System Reports</h1>
                <p class="dashboard-subtitle">Platform-wide analytics and behavioral data</p>
            </div>
            <div class="header-actions-inline">
                <select class="search-input" style="width:auto;">
                    <option>Last 30 Days</option>
                    <option>Last 7 Days</option>
                    <option>Last 90 Days</option>
                    <option>All Time</option>
                </select>
                <button class="btn btn-outline">Export CSV</button>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-indigo">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">3,842</div><div class="admin-stat-label">Growth Records</div></div>
            </div>
            <div class="admin-stat-card admin-stat-teal">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">892</div><div class="admin-stat-label">Voice Samples</div></div>
            </div>
            <div class="admin-stat-card admin-stat-emerald">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">78%</div><div class="admin-stat-label">On Track Rate</div></div>
            </div>
            <div class="admin-stat-card admin-stat-amber">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <div class="admin-stat-info"><div class="admin-stat-value">142</div><div class="admin-stat-label">Flagged Children</div></div>
            </div>
        </div>

        <div class="overview-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Behavior Categories</h2></div>
                <div class="clinic-table-wrap">
                    <table class="clinic-table">
                        <thead><tr><th>Category</th><th>Type</th><th>Behaviors Tracked</th><th>Children Affected</th></tr></thead>
                        <tbody>
                            <tr><td>Motor Development</td><td>Physical</td><td>24</td><td>312</td></tr>
                            <tr><td>Speech & Language</td><td>Communication</td><td>18</td><td>245</td></tr>
                            <tr><td>Social Interaction</td><td>Social-Emotional</td><td>15</td><td>198</td></tr>
                            <tr><td>Cognitive Skills</td><td>Cognitive</td><td>20</td><td>287</td></tr>
                            <tr><td>Self-Care</td><td>Adaptive</td><td>12</td><td>156</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Development Status</h2></div>
                <div style="padding:1.5rem;">
                    <div class="status-overview">
                        <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--green-500);"></span>On Track</div><div class="status-bar-fill" style="width:78%;background:var(--green-500);"></div><span>78%</span></div>
                        <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--yellow-500);"></span>Needs Review</div><div class="status-bar-fill" style="width:15%;background:var(--yellow-500);"></div><span>15%</span></div>
                        <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--red-500);"></span>Needs Attention</div><div class="status-bar-fill" style="width:7%;background:var(--red-500);"></div><span>7%</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Settings View ────────────────────────────────────────────
function getSettingsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">System Settings</h1>
                <p class="dashboard-subtitle">Platform configuration and admin profile</p>
            </div>
        </div>

        <div class="settings-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Admin Profile</h2></div>
                <div style="padding: 1.5rem;">
                    <div style="display:flex;gap:2rem;align-items:center;margin-bottom:2rem;">
                        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:36px;height:36px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <h3 style="margin-bottom:0.25rem;">Super Administrator</h3>
                            <p style="color:var(--text-secondary);font-size:0.875rem;">admin@brightsteps.com</p>
                            <p style="color:var(--text-secondary);font-size:0.8125rem;">Role Level: 1 (Full Access)</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Admin Email</label><input type="email" class="form-input" value="admin@brightsteps.com"></div>
                        <div class="form-group"><label>Change Password</label><input type="password" class="form-input" placeholder="Enter new password"></div>
                    </div>
                    <button class="btn btn-gradient" style="margin-top:1.5rem;">Save Profile</button>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Platform Configuration</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="toggle-row"><span>Allow new clinic registrations</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Auto-approve verified clinics</span><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Enable free trial signups</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Send weekly platform digest</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Maintenance mode</span><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                </div>
            </div>

            <div class="section-card danger-card">
                <div class="section-card-header"><h2 class="section-heading" style="color:var(--red-600);">Danger Zone</h2></div>
                <div style="padding: 1.5rem;">
                    <p style="color:var(--text-secondary);margin-bottom:1rem;">These actions affect the entire platform and cannot be easily undone.</p>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);">Purge Inactive Users</button>
                        <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);">Reset Points System</button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Logout ───────────────────────────────────────────────────
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = 'index.php';
    }
}
