// ─────────────────────────────────────────────────────────────
//  Admin Dashboard – View Controller (Live Data + Modal Popups)
// ─────────────────────────────────────────────────────────────

const ADMIN_API = 'admin/';

document.addEventListener('DOMContentLoaded', function () {
    initAdminNav();
    showAdminView('overview');
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
    main.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:60vh;"><div class="admin-loading-spinner"></div></div>';
    const loaders = { 'overview': loadOverviewView, 'users': loadUsersView, 'clinics': loadClinicsView, 'subscriptions': loadSubscriptionsView, 'points': loadPointsView, 'reports': loadReportsView, 'settings': loadSettingsView };
    const fn = loaders[viewId];
    if (fn) fn(main);
}

// ── Helpers ──────────────────────────────────────────────────
function fmtNum(n) { return Number(n).toLocaleString('en-US'); }
function fmtMoney(n) { return n >= 1000 ? '$' + (n / 1000).toFixed(1) + 'K' : '$' + Number(n).toFixed(2); }
function fmtDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
function timeAgo(d) { if (!d) return ''; const s = Math.floor((new Date() - new Date(d)) / 1000); if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s / 60) + ' min ago'; if (s < 86400) return Math.floor(s / 3600) + ' hrs ago'; if (s < 604800) return Math.floor(s / 86400) + ' days ago'; return fmtDate(d); }
function getInitials(f, l) { return ((f?.[0] || '') + (l?.[0] || '')).toUpperCase(); }

const activityColors = { 'clinic_registered': 'activity-dot-green', 'clinic_verified': 'activity-dot-green', 'user_signup': 'activity-dot-blue', 'user_added': 'activity-dot-blue', 'subscription_upgrade': 'activity-dot-purple', 'payment_received': 'activity-dot-yellow', 'specialist_added': 'activity-dot-green', 'alert': 'activity-dot-red', 'user_status_change': 'activity-dot-yellow', 'system_update': 'activity-dot-blue', 'purge_inactive': 'activity-dot-red', 'points_reset': 'activity-dot-red', 'user_deleted': 'activity-dot-red', 'user_updated': 'activity-dot-blue', 'clinic_approved': 'activity-dot-green' };
const activityLabels = { 'clinic_registered': 'New Clinic', 'clinic_verified': 'Clinic Verified', 'user_signup': 'New User', 'user_added': 'User Added', 'subscription_upgrade': 'Upgrade', 'payment_received': 'Payment', 'specialist_added': 'New Specialist', 'alert': 'Alert', 'user_status_change': 'Status Change', 'system_update': 'System Update', 'purge_inactive': 'Users Purged', 'points_reset': 'Points Reset', 'user_deleted': 'User Deleted', 'user_updated': 'User Updated', 'clinic_approved': 'Clinic Approved' };
function getActivityDotColor(t) { return activityColors[t] || 'activity-dot-blue'; }
function getActivityLabel(t) { return activityLabels[t] || t; }

const avatarColors = {
    'parent': 'background:linear-gradient(135deg,#6366f1,#818cf8);',
    'doctor': 'background:linear-gradient(135deg,#0d9488,#0891b2);',
    'admin': 'background:linear-gradient(135deg,#8b5cf6,#7c3aed);',
    'clinic': 'background:linear-gradient(135deg,#d97706,#f59e0b);'
};

// ── API ──────────────────────────────────────────────────────
async function apiGet(ep) { const r = await fetch(ADMIN_API + ep); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }
async function apiPost(ep, data) { const r = await fetch(ADMIN_API + ep, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }

// ══════════════════════════════════════════════════════════════
//  MODAL SYSTEM – replaces all prompt/alert/confirm
// ══════════════════════════════════════════════════════════════
function closeModal() { document.getElementById('admin-modal-container')?.remove(); }

function showModal(title, bodyHTML, footerHTML) {
    closeModal();
    const div = document.createElement('div');
    div.id = 'admin-modal-container';
    div.innerHTML = `<div class="admin-modal-overlay" onclick="if(event.target===this)closeModal()">
        <div class="admin-modal">
            <div class="admin-modal-header"><h3>${title}</h3><button class="admin-modal-close" onclick="closeModal()">&times;</button></div>
            <div class="admin-modal-body">${bodyHTML}</div>
            <div class="admin-modal-footer">${footerHTML}</div>
        </div></div>`;
    document.body.appendChild(div);
    const firstInput = div.querySelector('input,select,textarea');
    if (firstInput) firstInput.focus();
}

function showAlert(msg, type) {
    type = type || 'info';
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    showModal('', `<div class="admin-modal-icon ${type}">${icons[type]}</div><div class="admin-modal-msg">${msg}</div>`, `<button class="btn btn-gradient" onclick="closeModal()">OK</button>`);
}

function showConfirm(msg, onYes, type) {
    type = type || 'warning';
    const icons = { success: '✓', error: '✕', warning: '⚠', info: '?' };
    showModal('Confirm', `<div class="admin-modal-icon ${type}">${icons[type]}</div><div class="admin-modal-msg">${msg}</div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="modal-confirm-btn">Yes, Continue</button>`);
    document.getElementById('modal-confirm-btn').onclick = () => { closeModal(); onYes(); };
}

// ══════════════════════════════════════════════════════════════
//  OVERVIEW
// ══════════════════════════════════════════════════════════════
async function loadOverviewView(main) {
    try {
        const data = await apiGet('overview.php');
        if (!data.success) throw new Error(data.error);
        const s = data.stats, dist = data.user_distribution, activity = data.recent_activity;
        const totalDist = (dist.parent || 0) + (dist.doctor || 0) + (dist.admin || 0) + (dist.clinic || 0);

        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Platform Overview</h1><p class="dashboard-subtitle">Bright Steps system-wide analytics and activity</p></div></div>
        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.total_users)}</div><div class="admin-stat-label">Total Users</div><div class="admin-stat-trend ${s.users_trend >= 0 ? 'trend-up' : 'trend-down'}">${s.users_trend >= 0 ? '↑' : '↓'} ${Math.abs(s.users_trend)}% this month</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.active_clinics)}</div><div class="admin-stat-label">Active Clinics</div><div class="admin-stat-trend trend-up">↑ ${s.new_clinics} new</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtMoney(s.total_revenue)}</div><div class="admin-stat-label">Total Revenue</div><div class="admin-stat-trend ${s.revenue_trend >= 0 ? 'trend-up' : 'trend-down'}">${s.revenue_trend >= 0 ? '↑' : '↓'} ${Math.abs(s.revenue_trend)}% this month</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.active_subscriptions)}</div><div class="admin-stat-label">Active Subscriptions</div></div></div>
        </div>
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Recent Activity</h2></div><div class="activity-feed">
                ${activity.map(a => `<div class="activity-item"><div class="activity-dot ${getActivityDotColor(a.activity_type)}"></div><div class="activity-info"><div class="activity-text"><strong>${getActivityLabel(a.activity_type)}</strong> ${a.description}</div><div class="activity-time">${timeAgo(a.created_at)}</div></div></div>`).join('')}
                ${activity.length === 0 ? '<div style="padding:1.5rem;color:var(--text-secondary);">No recent activity</div>' : ''}
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">User Distribution</h2></div><div style="padding:1.5rem;"><div class="distribution-bar-wrap">
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>Parents</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.parent || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div></div><div class="dist-value">${fmtNum(dist.parent || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#0d9488;"></span>Doctors</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.doctor || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#0d9488,#14b8a6);"></div></div><div class="dist-value">${fmtNum(dist.doctor || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#d97706;"></span>Clinics</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.clinic || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#d97706,#f59e0b);"></div></div><div class="dist-value">${fmtNum(dist.clinic || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#ec4899;"></span>Admins</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.admin || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#ec4899,#f472b6);"></div></div><div class="dist-value">${fmtNum(dist.admin || 0)}</div></div>
            </div></div></div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (err) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error loading overview</h2><p>${err.message}</p></div>`; }
}

// ══════════════════════════════════════════════════════════════
//  USERS
// ══════════════════════════════════════════════════════════════
async function loadUsersView(main) {
    try {
        const data = await apiGet('users.php?action=list');
        if (!data.success) throw new Error(data.error);
        renderUsersTable(main, data.users, 'all', '');
    } catch (err) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error loading users</h2><p>${err.message}</p></div>`; }
}

function renderUsersTable(main, users, currentRole, currentSearch) {
    main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">User Management</h1><p class="dashboard-subtitle">Manage all registered users across the platform</p></div>
            <div class="header-actions-inline">
                <select class="search-input" style="width:auto;" id="admin-user-role-filter">
                    <option value="all" ${currentRole === 'all' ? 'selected' : ''}>All Roles</option>
                    <option value="parent" ${currentRole === 'parent' ? 'selected' : ''}>Parents</option>
                    <option value="doctor" ${currentRole === 'doctor' ? 'selected' : ''}>Doctors</option>
                    <option value="clinic" ${currentRole === 'clinic' ? 'selected' : ''}>Clinics</option>
                    <option value="admin" ${currentRole === 'admin' ? 'selected' : ''}>Admins</option>
                </select>
                <button class="btn btn-gradient" onclick="showAddUserModal()">+ Add User</button>
            </div></div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">All Users</h2><input type="text" class="search-input" placeholder="Search by name or email..." id="admin-user-search" value="${currentSearch}"></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>User</th><th>Role</th><th>Email</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                ${users.map(u => `<tr>
                    <td><div class="table-user"><div class="patient-avatar" style="${avatarColors[u.role] || ''}">${getInitials(u.first_name, u.last_name)}</div><div><div class="patient-name">${u.first_name || ''} ${u.last_name || ''}</div><div class="patient-details">ID: #${u.user_id}</div></div></div></td>
                    <td><span class="role-badge role-${u.role}">${u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : '—'}</span></td>
                    <td>${u.email || '—'}</td><td>${fmtDate(u.created_at)}</td>
                    <td><span class="status-badge ${u.status === 'active' ? 'status-active' : 'status-warning'}">${u.status ? u.status.charAt(0).toUpperCase() + u.status.slice(1) : 'Active'}</span></td>
                    <td><button class="btn btn-sm btn-outline" onclick="editUser(${u.user_id},'${(u.first_name || '').replace(/'/g, "\\'")}','${(u.last_name || '').replace(/'/g, "\\'")}','${(u.email || '').replace(/'/g, "\\'")}','${u.role}')">Edit</button>
                    ${u.status === 'active' ? `<button class="btn btn-sm btn-outline" style="margin-left:.5rem;color:var(--red-500);" onclick="toggleUserStatus(${u.user_id},'suspended')">Suspend</button>` : `<button class="btn btn-sm btn-outline" style="margin-left:.5rem;color:var(--green-500);" onclick="toggleUserStatus(${u.user_id},'active')">Activate</button>`}</td>
                </tr>`).join('')}
                ${users.length === 0 ? '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">No users found</td></tr>' : ''}
            </tbody></table></div></div></div>`;
    document.getElementById('admin-user-role-filter').addEventListener('change', () => filterUsers());
    let st; document.getElementById('admin-user-search').addEventListener('input', () => { clearTimeout(st); st = setTimeout(() => filterUsers(), 400); });
    if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
}

async function filterUsers() {
    const role = document.getElementById('admin-user-role-filter')?.value || 'all';
    const search = document.getElementById('admin-user-search')?.value || '';
    const main = document.getElementById('admin-main-content');
    try { const data = await apiGet(`users.php?action=list&role=${encodeURIComponent(role)}&search=${encodeURIComponent(search)}`); if (data.success) renderUsersTable(main, data.users, role, search); } catch (e) { console.error(e); }
}

function toggleUserStatus(userId, newStatus) {
    showConfirm(`Are you sure you want to <strong>${newStatus === 'suspended' ? 'suspend' : 'activate'}</strong> this user?`, async () => {
        try { const res = await apiPost('users.php', { action: 'toggle_status', user_id: userId, status: newStatus }); if (res.success) { showAlert('User status updated successfully!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

function editUser(userId, firstName, lastName, email, role) {
    showModal('Edit User', `
        <div class="form-group"><label>First Name</label><input type="text" id="mu-fn" value="${firstName}"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="mu-ln" value="${lastName}"></div>
        <div class="form-group"><label>Email</label><input type="email" id="mu-em" value="${email}"></div>
        <div class="form-group"><label>Role</label><select id="mu-rl"><option value="parent" ${role === 'parent' ? 'selected' : ''}>Parent</option><option value="doctor" ${role === 'doctor' ? 'selected' : ''}>Doctor</option><option value="clinic" ${role === 'clinic' ? 'selected' : ''}>Clinic</option><option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="mu-save">Save Changes</button>`);
    document.getElementById('mu-save').onclick = async () => {
        const d = { action: 'update', user_id: userId, first_name: document.getElementById('mu-fn').value, last_name: document.getElementById('mu-ln').value, email: document.getElementById('mu-em').value };
        try { const res = await apiPost('users.php', d); if (res.success) { showAlert('User updated!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showAddUserModal() {
    showModal('Add New User', `
        <div class="form-group"><label>First Name</label><input type="text" id="au-fn" placeholder="Enter first name"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="au-ln" placeholder="Enter last name"></div>
        <div class="form-group"><label>Email</label><input type="email" id="au-em" placeholder="user@example.com"></div>
        <div class="form-group"><label>Password</label><input type="password" id="au-pw" placeholder="Min 8 characters"></div>
        <div class="form-group"><label>Role</label><select id="au-rl"><option value="parent">Parent</option><option value="doctor">Doctor</option><option value="clinic">Clinic</option><option value="admin">Admin</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="au-save">Create User</button>`);
    document.getElementById('au-save').onclick = async () => {
        const d = { action: 'add', first_name: document.getElementById('au-fn').value, last_name: document.getElementById('au-ln').value, email: document.getElementById('au-em').value, password: document.getElementById('au-pw').value, role: document.getElementById('au-rl').value };
        if (!d.first_name || !d.email || !d.password) { showAlert('Please fill all required fields.', 'warning'); return; }
        try { const res = await apiPost('users.php', d); if (res.success) { showAlert('User created successfully!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// ══════════════════════════════════════════════════════════════
//  CLINICS
// ══════════════════════════════════════════════════════════════
async function loadClinicsView(main) {
    try { const [sd, ld] = await Promise.all([apiGet('clinics.php?action=stats'), apiGet('clinics.php?action=list')]); renderClinicsView(main, sd.stats, ld.clinics, ''); }
    catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function renderClinicsView(main, stats, clinics, currentSearch) {
    main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Clinic Management</h1><p class="dashboard-subtitle">Manage all registered clinics on the platform</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showRegisterClinicModal()">+ Register Clinic</button></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.total_clinics)}</div><div class="admin-stat-label">Total Clinics</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.total_specialists)}</div><div class="admin-stat-label">Total Specialists</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.verified)}</div><div class="admin-stat-label">Verified</div></div></div>
        </div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">All Clinics</h2><input type="text" class="search-input" placeholder="Search clinics..." id="admin-clinic-search" value="${currentSearch}"></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Clinic</th><th>Location</th><th>Specialists</th><th>Patients</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                ${clinics.map(c => `<tr>
                    <td><div class="table-user"><div class="patient-avatar" style="background:linear-gradient(135deg,#0d9488,#0891b2);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="patient-name">${c.clinic_name}</div><div class="patient-details">${c.email || ''}</div></div></div></td>
                    <td>${c.location || '—'}</td><td>${c.specialist_count || 0}</td><td>${c.patient_count || 0}</td>
                    <td><span class="rating-badge">★ ${Number(c.rating).toFixed(1)}</span></td>
                    <td><span class="status-badge ${c.status === 'verified' ? 'status-active' : 'status-warning'}">${c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : 'Pending'}</span></td>
                    <td>${c.status === 'pending' ? `<button class="btn btn-sm btn-outline" style="color:var(--green-500);" onclick="approveClinic(${c.clinic_id})">Approve</button>` : `<button class="btn btn-sm btn-outline">View</button>`}</td>
                </tr>`).join('')}
                ${clinics.length === 0 ? '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">No clinics found</td></tr>' : ''}
            </tbody></table></div></div></div>`;
    let st; document.getElementById('admin-clinic-search').addEventListener('input', function () { clearTimeout(st); st = setTimeout(async () => { try { const [s, l] = await Promise.all([apiGet('clinics.php?action=stats'), apiGet('clinics.php?action=list&search=' + encodeURIComponent(this.value))]); renderClinicsView(main, s.stats, l.clinics, this.value); } catch (e) { } }, 400); });
    if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
}

function approveClinic(clinicId) {
    showConfirm('Are you sure you want to <strong>approve</strong> this clinic?', async () => {
        try { const res = await apiPost('clinics.php', { action: 'approve', clinic_id: clinicId }); if (res.success) { showAlert('Clinic approved successfully!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

function showRegisterClinicModal() {
    showModal('Register New Clinic', `
        <div class="form-group"><label>Clinic Name</label><input type="text" id="rc-name" placeholder="Enter clinic name"></div>
        <div class="form-group"><label>Email</label><input type="email" id="rc-email" placeholder="clinic@example.com"></div>
        <div class="form-group"><label>Location</label><input type="text" id="rc-loc" placeholder="Enter address"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="rc-save">Register Clinic</button>`);
    document.getElementById('rc-save').onclick = async () => {
        const d = { action: 'register', clinic_name: document.getElementById('rc-name').value, email: document.getElementById('rc-email').value, location: document.getElementById('rc-loc').value };
        if (!d.clinic_name || !d.email) { showAlert('Clinic name and email are required.', 'warning'); return; }
        try { const res = await apiPost('clinics.php', d); if (res.success) { showAlert('Clinic registered!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// ══════════════════════════════════════════════════════════════
//  SUBSCRIPTIONS
// ══════════════════════════════════════════════════════════════
async function loadSubscriptionsView(main) {
    try {
        const [pd, rd] = await Promise.all([apiGet('subscriptions.php?action=plans'), apiGet('subscriptions.php?action=revenue')]);
        const plans = pd.plans || [], revenue = rd.revenue || [], totalMRR = rd.total_mrr || 0;
        const pc = ['plan-free', 'plan-standard', 'plan-premium'];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Subscription Plans</h1><p class="dashboard-subtitle">Manage plans and track subscriber metrics</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showCreatePlanModal()">+ Create Plan</button></div></div>
        <div class="plans-grid">${plans.map((p, i) => `<div class="plan-card ${pc[i] || ''}">
            ${i === plans.length - 1 && plans.length > 1 ? '<div class="plan-badge">Most Popular</div>' : ''}
            <div class="plan-header"><h3 class="plan-name">${p.plan_name}</h3><div class="plan-price">$${Number(p.price).toFixed(2)}<span>/mo</span></div></div>
            <div class="plan-stats"><div class="plan-stat"><span class="plan-stat-value">${fmtNum(p.active_users)}</span><span class="plan-stat-label">Active Users</span></div><div class="plan-stat"><span class="plan-stat-value">${p.price > 0 ? '$' + fmtNum(p.mrr) : '—'}</span><span class="plan-stat-label">${p.price > 0 ? 'MRR' : '—'}</span></div></div>
            <ul class="plan-features">${(p.features || []).map(f => `<li>${f}</li>`).join('')}</ul>
            <button class="btn ${i === plans.length - 1 ? 'btn-gradient' : 'btn-outline'}" style="width:100%;" onclick="editPlan(${p.subscription_id},'${p.plan_name.replace(/'/g, "\\'")}',${p.price})">Edit Plan</button>
        </div>`).join('')}</div>
        <div class="section-card" style="margin-top:2rem;"><div class="section-card-header"><h2 class="section-heading">Revenue by Plan</h2></div><div style="padding:1.5rem;">
            ${revenue.map(r => `<div class="revenue-row"><span class="revenue-plan">${r.plan_name}</span><span class="revenue-count">${fmtNum(r.subscriber_count)} subscribers</span><span class="revenue-amount">$${fmtNum(r.monthly_revenue)}/mo</span></div>`).join('')}
            <div class="revenue-row revenue-total"><span class="revenue-plan">Total MRR</span><span></span><span class="revenue-amount">$${fmtNum(totalMRR)}/mo</span></div>
        </div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function editPlan(subId, name, price) {
    showModal('Edit Plan', `
        <div class="form-group"><label>Plan Name</label><input type="text" id="ep-name" value="${name}"></div>
        <div class="form-group"><label>Price ($/month)</label><input type="number" id="ep-price" value="${price}" step="0.01"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ep-save">Save</button>`);
    document.getElementById('ep-save').onclick = async () => {
        try { const res = await apiPost('subscriptions.php', { action: 'update_plan', subscription_id: subId, plan_name: document.getElementById('ep-name').value, price: parseFloat(document.getElementById('ep-price').value) }); if (res.success) { showAlert('Plan updated!', 'success'); setTimeout(() => { closeModal(); showAdminView('subscriptions'); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showCreatePlanModal() {
    showModal('Create New Plan', `
        <div class="form-group"><label>Plan Name</label><input type="text" id="cp-name" placeholder="e.g. Premium Plus"></div>
        <div class="form-group"><label>Price ($/month)</label><input type="number" id="cp-price" placeholder="0.00" step="0.01"></div>
        <div class="form-group"><label>Features (one per line)</label><textarea id="cp-feat" rows="4" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="cp-save">Create Plan</button>`);
    document.getElementById('cp-save').onclick = async () => {
        const features = document.getElementById('cp-feat').value.split('\n').map(f => f.trim()).filter(f => f);
        const d = { action: 'create_plan', plan_name: document.getElementById('cp-name').value, price: parseFloat(document.getElementById('cp-price').value) || 0, features };
        if (!d.plan_name) { showAlert('Plan name is required.', 'warning'); return; }
        try { const res = await apiPost('subscriptions.php', d); if (res.success) { showAlert('Plan created!', 'success'); setTimeout(() => { closeModal(); showAdminView('subscriptions'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// ══════════════════════════════════════════════════════════════
//  POINTS
// ══════════════════════════════════════════════════════════════
async function loadPointsView(main) {
    try {
        const [sd, rd, wd] = await Promise.all([apiGet('points.php?action=stats'), apiGet('points.php?action=rules'), apiGet('points.php?action=top_wallets')]);
        const stats = sd.stats, rules = rd.rules || [], wallets = wd.wallets || [];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Points & Rewards System</h1><p class="dashboard-subtitle">Configure points rules and manage wallets</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showAddRuleModal()">+ Add Points Rule</button></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.total_points_issued)}</div><div class="admin-stat-label">Total Points Issued</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 3H8l-2 4h12l-2-4z"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.active_wallets)}</div><div class="admin-stat-label">Active Wallets</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.badges_earned)}</div><div class="admin-stat-label">Badges Earned</div></div></div>
        </div>
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Points Rules</h2></div><div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Action</th><th>Points</th><th>Type</th><th>Actions</th></tr></thead><tbody>
                ${rules.map(r => `<tr><td>${r.action_name}</td><td class="${r.adjust_sign === '+' ? 'points-plus' : 'points-minus'}">${r.adjust_sign}${r.points_value}</td><td>${r.adjust_sign === '+' ? 'Deposit' : 'Withdrawal'}</td><td><button class="btn btn-sm btn-outline" onclick="editRule(${r.refrence_id},'${r.action_name.replace(/'/g, "\\'")}',${r.points_value},'${r.adjust_sign}')">Edit</button></td></tr>`).join('')}
                ${rules.length === 0 ? '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-secondary);">No rules</td></tr>' : ''}
            </tbody></table></div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Top Wallets</h2></div><div class="patients-list">
                ${wallets.map((w, i) => `<div class="patient-row"><div class="rank-badge">${i + 1}</div><div class="patient-avatar" style="${avatarColors.parent}">${getInitials(w.first_name, w.last_name)}</div><div class="patient-info"><div class="patient-name">${w.first_name} ${w.last_name}</div><div class="patient-details">${w.badge_count} badges</div></div><div class="wallet-points">${fmtNum(w.total_points)} pts</div></div>`).join('')}
                ${wallets.length === 0 ? '<div style="padding:2rem;text-align:center;color:var(--text-secondary);">No wallets yet</div>' : ''}
            </div></div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function editRule(ruleId, name, value, sign) {
    showModal('Edit Points Rule', `
        <div class="form-group"><label>Action Name</label><input type="text" id="er-name" value="${name}"></div>
        <div class="form-group"><label>Points Value</label><input type="number" id="er-val" value="${value}"></div>
        <div class="form-group"><label>Type</label><select id="er-sign"><option value="+" ${sign === '+' ? 'selected' : ''}>+ Deposit</option><option value="-" ${sign === '-' ? 'selected' : ''}>- Withdrawal</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="er-save">Save</button>`);
    document.getElementById('er-save').onclick = async () => {
        try { const res = await apiPost('points.php', { action: 'update_rule', refrence_id: ruleId, action_name: document.getElementById('er-name').value, points_value: parseInt(document.getElementById('er-val').value), adjust_sign: document.getElementById('er-sign').value }); if (res.success) { showAlert('Rule updated!', 'success'); setTimeout(() => { closeModal(); showAdminView('points'); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showAddRuleModal() {
    showModal('Add Points Rule', `
        <div class="form-group"><label>Action Name</label><input type="text" id="ar-name" placeholder="e.g. Daily Login"></div>
        <div class="form-group"><label>Points Value</label><input type="number" id="ar-val" placeholder="10"></div>
        <div class="form-group"><label>Type</label><select id="ar-sign"><option value="+">+ Deposit</option><option value="-">- Withdrawal</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ar-save">Add Rule</button>`);
    document.getElementById('ar-save').onclick = async () => {
        const d = { action: 'add_rule', action_name: document.getElementById('ar-name').value, points_value: parseInt(document.getElementById('ar-val').value), adjust_sign: document.getElementById('ar-sign').value };
        if (!d.action_name || !d.points_value) { showAlert('Please fill all fields.', 'warning'); return; }
        try { const res = await apiPost('points.php', d); if (res.success) { showAlert('Rule added!', 'success'); setTimeout(() => { closeModal(); showAdminView('points'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// ══════════════════════════════════════════════════════════════
//  REPORTS
// ══════════════════════════════════════════════════════════════
async function loadReportsView(main) {
    try {
        const [sd, cd, dd] = await Promise.all([apiGet('reports.php?action=stats'), apiGet('reports.php?action=behavior_categories'), apiGet('reports.php?action=development_status')]);
        const stats = sd.stats, categories = cd.categories || [], dev = dd.development_status;
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">System Reports</h1><p class="dashboard-subtitle">Platform-wide analytics and behavioral data</p></div>
            <div class="header-actions-inline"><select class="search-input" style="width:auto;" id="admin-report-period"><option value="30">Last 30 Days</option><option value="7">Last 7 Days</option><option value="90">Last 90 Days</option><option value="9999">All Time</option></select></div></div>
        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.growth_records)}</div><div class="admin-stat-label">Growth Records</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.voice_samples)}</div><div class="admin-stat-label">Voice Samples</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${stats.on_track_rate}%</div><div class="admin-stat-label">On Track Rate</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.flagged_children)}</div><div class="admin-stat-label">Flagged Children</div></div></div>
        </div>
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Behavior Categories</h2></div><div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Category</th><th>Type</th><th>Behaviors</th><th>Children Affected</th></tr></thead><tbody>
                ${categories.map(c => `<tr><td>${c.category_name}</td><td>${c.category_type}</td><td>${c.behavior_count}</td><td>${c.children_affected}</td></tr>`).join('')}
                ${categories.length === 0 ? '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-secondary);">No categories</td></tr>' : ''}
            </tbody></table></div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Development Status</h2></div><div style="padding:1.5rem;"><div class="status-overview">
                <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--green-500);"></span>On Track</div><div class="status-bar-fill" style="width:${dev?.on_track?.percentage || 0}%;background:var(--green-500);"></div><span>${dev?.on_track?.percentage || 0}%</span></div>
                <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--yellow-500);"></span>Needs Review</div><div class="status-bar-fill" style="width:${dev?.needs_review?.percentage || 0}%;background:var(--yellow-500);"></div><span>${dev?.needs_review?.percentage || 0}%</span></div>
                <div class="status-bar-item"><div class="status-bar-label"><span class="dist-dot" style="background:var(--red-500);"></span>Needs Attention</div><div class="status-bar-fill" style="width:${dev?.needs_attention?.percentage || 0}%;background:var(--red-500);"></div><span>${dev?.needs_attention?.percentage || 0}%</span></div>
            </div></div></div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

// ══════════════════════════════════════════════════════════════
//  SETTINGS
// ══════════════════════════════════════════════════════════════
async function loadSettingsView(main) {
    try {
        const [pd, cd] = await Promise.all([apiGet('settings.php?action=profile'), apiGet('settings.php?action=config')]);
        const profile = pd.profile, config = cd.config || {};
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">System Settings</h1><p class="dashboard-subtitle">Platform configuration and admin profile</p></div></div>
        <div class="settings-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Admin Profile</h2></div><div style="padding:1.5rem;">
                <div style="display:flex;gap:2rem;align-items:center;margin-bottom:2rem;">
                    <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:36px;height:36px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <div><h3 style="margin-bottom:.25rem;">${profile ? profile.first_name + ' ' + profile.last_name : 'Administrator'}</h3><p style="color:var(--text-secondary);font-size:.875rem;">${profile?.email || ''}</p><p style="color:var(--text-secondary);font-size:.8125rem;">Role Level: ${profile?.role_level || '1'} (Full Access)</p></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Admin Email</label><input type="email" class="form-input" id="admin-settings-email" value="${profile?.email || ''}"></div>
                    <div class="form-group"><label>Change Password</label><input type="password" class="form-input" id="admin-settings-password" placeholder="Enter new password"></div>
                </div>
                <button class="btn btn-gradient" style="margin-top:1.5rem;" onclick="saveAdminProfile()">Save Profile</button>
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Platform Configuration</h2></div><div style="padding:1.5rem;">
                <div class="toggle-row"><span>Allow new clinic registrations</span><label class="toggle-switch"><input type="checkbox" ${config.allow_clinic_registration === '1' ? 'checked' : ''} onchange="updateConfig('allow_clinic_registration',this.checked?'1':'0')"><span class="toggle-slider"></span></label></div>
                <div class="toggle-row"><span>Auto-approve verified clinics</span><label class="toggle-switch"><input type="checkbox" ${config.auto_approve_clinics === '1' ? 'checked' : ''} onchange="updateConfig('auto_approve_clinics',this.checked?'1':'0')"><span class="toggle-slider"></span></label></div>
                <div class="toggle-row"><span>Enable free trial signups</span><label class="toggle-switch"><input type="checkbox" ${config.enable_free_trial === '1' ? 'checked' : ''} onchange="updateConfig('enable_free_trial',this.checked?'1':'0')"><span class="toggle-slider"></span></label></div>
                <div class="toggle-row"><span>Send weekly platform digest</span><label class="toggle-switch"><input type="checkbox" ${config.weekly_digest === '1' ? 'checked' : ''} onchange="updateConfig('weekly_digest',this.checked?'1':'0')"><span class="toggle-slider"></span></label></div>
                <div class="toggle-row"><span>Maintenance mode</span><label class="toggle-switch"><input type="checkbox" ${config.maintenance_mode === '1' ? 'checked' : ''} onchange="updateConfig('maintenance_mode',this.checked?'1':'0')"><span class="toggle-slider"></span></label></div>
            </div></div>
            <div class="section-card danger-card"><div class="section-card-header"><h2 class="section-heading" style="color:var(--red-600);">Danger Zone</h2></div><div style="padding:1.5rem;">
                <p style="color:var(--text-secondary);margin-bottom:1rem;">These actions affect the entire platform and cannot be easily undone.</p>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);" onclick="purgeInactiveUsers()">Purge Inactive Users</button>
                    <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);" onclick="resetPointsSystem()">Reset Points System</button>
                </div>
            </div></div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function saveAdminProfile() {
    const email = document.getElementById('admin-settings-email')?.value || '';
    const password = document.getElementById('admin-settings-password')?.value || '';
    if (!email && !password) { showAlert('No changes to save.', 'info'); return; }
    try { const res = await apiPost('settings.php', { action: 'update_profile', email, password }); if (res.success) { showAlert('Profile updated successfully!', 'success'); setTimeout(() => { closeModal(); showAdminView('settings'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
}

async function updateConfig(key, value) {
    try { await apiPost('settings.php', { action: 'update_config', setting_key: key, setting_value: value }); } catch (e) { showAlert('Error updating setting: ' + e.message, 'error'); }
}

function purgeInactiveUsers() {
    showConfirm('This will <strong>permanently delete</strong> all inactive parent accounts older than 6 months. This cannot be undone.', async () => {
        try { const res = await apiPost('settings.php', { action: 'purge_inactive' }); showAlert(res.message || 'Done', 'success'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}

function resetPointsSystem() {
    showConfirm('This will <strong>reset ALL points wallets to 0</strong>. This action cannot be undone.', async () => {
        try { const res = await apiPost('settings.php', { action: 'reset_points' }); showAlert(res.message || 'Done', 'success'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}

function handleLogout() {
    showConfirm('Are you sure you want to log out?', () => { window.location.href = 'logout.php'; }, 'info');
}
