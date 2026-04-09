// Admin Dashboard – View Controller (All 7 Features)
const ADMIN_API = 'admin/';
let _adminPermissions = ['all']; // Default: full access
document.addEventListener('DOMContentLoaded', async function () {
    // Fetch current admin's permissions
    try {
        const r = await apiGet('roles.php?action=get_permissions');
        if (r.success && r.permissions) _adminPermissions = r.permissions;
    } catch(e) { console.warn('Could not fetch permissions, defaulting to full access'); }
    initAdminNav();
    enforceNavPermissions();
    showAdminView('overview');
});

function hasPermission(viewId) {
    if (_adminPermissions.includes('all')) return true;
    if (viewId === 'overview' || viewId === 'settings') return true; // Always accessible
    return _adminPermissions.includes(viewId);
}

function enforceNavPermissions() {
    const allNavItems = document.querySelectorAll('.sidebar-nav .nav-item[data-view], .sidebar-footer .nav-item[data-view]');
    allNavItems.forEach(item => {
        const view = item.dataset.view;
        if (view && !hasPermission(view)) {
            item.style.display = 'none';
        }
    });
}

function initAdminNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const footerItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');
    navItems.forEach(item => { item.addEventListener('click', function () { const view = this.dataset.view; if (view) { navItems.forEach(n => n.classList.remove('active')); footerItems.forEach(n => n.classList.remove('active')); this.classList.add('active'); showAdminView(view); } }); });
    footerItems.forEach(item => { item.addEventListener('click', function () { const view = this.dataset.view; if (view) { navItems.forEach(n => n.classList.remove('active')); footerItems.forEach(n => n.classList.remove('active')); this.classList.add('active'); showAdminView(view); } }); });
}

function showAdminView(viewId) {
    const main = document.getElementById('admin-main-content');
    if (!main) return;
    // Permission check
    if (!hasPermission(viewId)) {
        main.innerHTML = `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:60vh;text-align:center;">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--red-500);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:40px;height:40px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h2 style="color:var(--text-primary);margin:0 0 .5rem;">Access Denied</h2>
            <p style="color:var(--text-secondary);max-width:400px;">You don't have permission to access this section. Contact a Super Admin to request access.</p>
            <button class="btn btn-outline" onclick="showAdminView('overview')" style="margin-top:1rem;">← Back to Overview</button>
        </div>`;
        return;
    }
    main.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:60vh;"><div class="admin-loading-spinner"></div></div>';
    const loaders = { 'overview': loadOverviewView, 'users': loadUsersView, 'clinics': loadClinicsView, 'subscriptions': loadSubscriptionsView, 'points': loadPointsView, 'reports': loadReportsView, 'settings': loadSettingsView, 'notifications_mgmt': loadNotificationsView, 'moderation': loadModerationView, 'system_health': loadSystemHealthView, 'roles': loadRolesView, 'tickets': loadTicketsView, 'banners': loadBannersView };
    const fn = loaders[viewId]; if (fn) fn(main);
}

// Helpers
function fmtNum(n) { return Number(n).toLocaleString('en-US'); }
function fmtMoney(n) { return n >= 1000 ? '$' + (n / 1000).toFixed(1) + 'K' : '$' + Number(n).toFixed(2); }
function fmtDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
function timeAgo(d) { if (!d) return ''; const s = Math.floor((new Date() - new Date(d)) / 1000); if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s / 60) + ' min ago'; if (s < 86400) return Math.floor(s / 3600) + ' hrs ago'; if (s < 604800) return Math.floor(s / 86400) + ' days ago'; return fmtDate(d); }
function getInitials(f, l) { return ((f?.[0] || '') + (l?.[0] || '')).toUpperCase(); }

const activityColors = { 'clinic_registered': 'activity-dot-green', 'clinic_verified': 'activity-dot-green', 'user_signup': 'activity-dot-blue', 'user_added': 'activity-dot-blue', 'subscription_upgrade': 'activity-dot-purple', 'payment_received': 'activity-dot-yellow', 'specialist_added': 'activity-dot-green', 'alert': 'activity-dot-red', 'user_status_change': 'activity-dot-yellow', 'system_update': 'activity-dot-blue', 'purge_inactive': 'activity-dot-red', 'points_reset': 'activity-dot-red', 'user_deleted': 'activity-dot-red', 'user_updated': 'activity-dot-blue', 'clinic_approved': 'activity-dot-green', 'plan_created': 'activity-dot-purple', 'plan_updated': 'activity-dot-purple', 'plan_deleted': 'activity-dot-red', 'plan_status_changed': 'activity-dot-yellow', 'config_updated': 'activity-dot-blue' };
const activityLabels = { 'clinic_registered': 'New Clinic', 'clinic_verified': 'Clinic Verified', 'user_signup': 'New User', 'user_added': 'User Added', 'subscription_upgrade': 'Upgrade', 'payment_received': 'Payment', 'specialist_added': 'New Specialist', 'alert': 'Alert', 'user_status_change': 'Status Change', 'system_update': 'System Update', 'purge_inactive': 'Users Purged', 'points_reset': 'Points Reset', 'user_deleted': 'User Deleted', 'user_updated': 'User Updated', 'clinic_approved': 'Clinic Approved', 'plan_created': 'Plan Created', 'plan_updated': 'Plan Updated', 'plan_deleted': 'Plan Deleted', 'plan_status_changed': 'Plan Status', 'config_updated': 'Config Updated' };
function getActivityDotColor(t) { return activityColors[t] || 'activity-dot-blue'; }
function getActivityLabel(t) { return activityLabels[t] || t; }

const avatarColors = { 'parent': 'background:linear-gradient(135deg,#6366f1,#818cf8);', 'specialist': 'background:linear-gradient(135deg,#0d9488,#0891b2);', 'admin': 'background:linear-gradient(135deg,#8b5cf6,#7c3aed);', 'clinic': 'background:linear-gradient(135deg,#d97706,#f59e0b);' };

// API
async function apiGet(ep) { const r = await fetch(ADMIN_API + ep); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }
async function apiPost(ep, data) { const r = await fetch(ADMIN_API + ep, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }

// MODAL SYSTEM
function closeModal() { document.getElementById('admin-modal-container')?.remove(); }
function showModal(title, bodyHTML, footerHTML, cssClass = '') {
    closeModal();
    const div = document.createElement('div'); div.id = 'admin-modal-container';
    div.innerHTML = `<div class="admin-modal-overlay" onclick="if(event.target===this)closeModal()"><div class="admin-modal ${cssClass}"><div class="admin-modal-header"><h3>${title}</h3><button class="admin-modal-close" onclick="closeModal()">&times;</button></div><div class="admin-modal-body">${bodyHTML}</div><div class="admin-modal-footer">${footerHTML}</div></div></div>`;
    document.body.appendChild(div);
    const firstInput = div.querySelector('input,select,textarea'); if (firstInput) firstInput.focus();
}
function showAlert(msg, type) { type = type || 'info'; const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' }; showModal('', `<div class="admin-modal-icon ${type}">${icons[type]}</div><div class="admin-modal-msg">${msg}</div>`, `<button class="btn btn-gradient" onclick="closeModal()">OK</button>`); }
function showConfirm(msg, onYes, type) { type = type || 'warning'; const icons = { success: '✓', error: '✕', warning: '⚠', info: '?' }; showModal('Confirm', `<div class="admin-modal-icon ${type}">${icons[type]}</div><div class="admin-modal-msg">${msg}</div>`, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="modal-confirm-btn">Yes, Continue</button>`); document.getElementById('modal-confirm-btn').onclick = () => { closeModal(); onYes(); }; }

// ═══ OVERVIEW ═══
async function loadOverviewView(main) {
    try {
        const data = await apiGet('overview.php');
        if (!data.success) throw new Error(data.error);
        const s = data.stats, dist = data.user_distribution, activity = data.recent_activity;
        const totalDist = (dist.parent || 0) + (dist.specialist || 0) + (dist.admin || 0) + (dist.clinic || 0);
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
                ${activity.map(a => `<div class="activity-item"><div class="activity-dot ${getActivityDotColor(a.activity_type)}"></div><div class="activity-info"><div class="activity-text"><strong>${getActivityLabel(a.activity_type)}</strong> ${a.description}${a.user_name ? ` <span class="activity-meta">by ${a.user_name}</span>` : ''}${a.ip_address ? ` <span class="activity-meta">(${a.ip_address})</span>` : ''}</div><div class="activity-time">${a.user_role ? `<span class="role-badge role-${a.user_role}" style="font-size:.7rem;padding:2px 6px;margin-right:6px;">${a.user_role}</span>` : ''}${timeAgo(a.created_at)}</div></div></div>`).join('')}
                ${activity.length === 0 ? '<div style="padding:1.5rem;color:var(--text-secondary);">No recent activity</div>' : ''}
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">User Distribution</h2></div><div style="padding:1.5rem;"><div class="distribution-bar-wrap">
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>Parents</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.parent || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div></div><div class="dist-value">${fmtNum(dist.parent || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#0d9488;"></span>Specialists</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.specialist || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#0d9488,#14b8a6);"></div></div><div class="dist-value">${fmtNum(dist.specialist || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#d97706;"></span>Clinics</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.clinic || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#d97706,#f59e0b);"></div></div><div class="dist-value">${fmtNum(dist.clinic || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#ec4899;"></span>Admins</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.admin || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#ec4899,#f472b6);"></div></div><div class="dist-value">${fmtNum(dist.admin || 0)}</div></div>
            </div></div></div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (err) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error loading overview</h2><p>${err.message}</p></div>`; }
}

// ═══ USERS ═══
async function loadUsersView(main) {
    try { const data = await apiGet('users.php?action=list'); if (!data.success) throw new Error(data.error); renderUsersTable(main, data.users, 'all', ''); }
    catch (err) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error loading users</h2><p>${err.message}</p></div>`; }
}

function renderUsersTable(main, users, currentRole, currentSearch) {
    main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">User Management</h1><p class="dashboard-subtitle">Manage all registered users across the platform</p></div>
            <div class="header-actions-inline">
                <select class="search-input" style="width:auto;" id="admin-user-role-filter">
                    <option value="all" ${currentRole === 'all' ? 'selected' : ''}>All Roles</option>
                    <option value="parent" ${currentRole === 'parent' ? 'selected' : ''}>Parents</option>
                    <option value="specialist" ${currentRole === 'specialist' ? 'selected' : ''}>Specialists</option>
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
                    <td><span class="status-badge ${u.status === 'active' ? 'status-active' : u.status === 'suspended' ? 'status-danger' : 'status-warning'}">${u.status ? u.status.charAt(0).toUpperCase() + u.status.slice(1) : 'Active'}</span></td>
                    <td><div class="action-btns">
                        <button class="btn btn-sm btn-outline" onclick="viewUser(${u.user_id})">View</button>
                        <button class="btn btn-sm btn-outline" onclick="editUser(${u.user_id},'${(u.first_name || '').replace(/'/g, "\\'")}','${(u.last_name || '').replace(/'/g, "\\'")}','${(u.email || '').replace(/'/g, "\\'")}','${u.role}')">Edit</button>
                        ${u.status === 'active' ? `<button class="btn btn-sm btn-outline" style="color:var(--yellow-600);" onclick="toggleUserStatus(${u.user_id},'suspended')">Suspend</button>` : `<button class="btn btn-sm btn-outline" style="color:var(--green-500);" onclick="toggleUserStatus(${u.user_id},'active')">Activate</button>`}
                        <button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="deleteUser(${u.user_id},'${(u.first_name || '').replace(/'/g, "\\'")} ${(u.last_name || '').replace(/'/g, "\\'")}')">Delete</button>
                    </div></td>
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
        try { const res = await apiPost('users.php', { action: 'toggle_status', user_id: userId, status: newStatus }); if (res.success) { showAlert('User status updated!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

function deleteUser(userId, userName) {
    showConfirm(`Are you sure you want to <strong>permanently delete</strong> user "${userName}"? This cannot be undone.`, async () => {
        try { const res = await apiPost('users.php', { action: 'delete', user_id: userId }); if (res.success) { showAlert('User deleted successfully!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}

function editUser(userId, firstName, lastName, email, role) {
    showModal('Edit User', `
        <div class="form-group"><label>First Name</label><input type="text" id="mu-fn" value="${firstName}"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="mu-ln" value="${lastName}"></div>
        <div class="form-group"><label>Email</label><input type="email" id="mu-em" value="${email}"></div>
        <div class="form-group"><label>Role</label><select id="mu-rl"><option value="parent" ${role === 'parent' ? 'selected' : ''}>Parent</option><option value="specialist" ${role === 'specialist' ? 'selected' : ''}>Specialist</option><option value="clinic" ${role === 'clinic' ? 'selected' : ''}>Clinic</option><option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="mu-save">Save Changes</button>`);
    document.getElementById('mu-save').onclick = async () => {
        const d = { action: 'update', user_id: userId, first_name: document.getElementById('mu-fn').value, last_name: document.getElementById('mu-ln').value, email: document.getElementById('mu-em').value, role: document.getElementById('mu-rl').value };
        try { const res = await apiPost('users.php', d); if (res.success) { showAlert('User updated!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showAddUserModal() {
    showModal('Add New User', `
        <div class="form-group"><label>First Name</label><input type="text" id="au-fn" placeholder="Enter first name"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="au-ln" placeholder="Enter last name"></div>
        <div class="form-group"><label>Email</label><input type="email" id="au-em" placeholder="user@example.com"></div>
        <div class="form-group"><label>Password</label><input type="password" id="au-pw" placeholder="Min 8 characters"></div>
        <div class="form-group"><label>Role</label><select id="au-rl"><option value="parent">Parent</option><option value="specialist">Specialist</option><option value="clinic">Clinic</option><option value="admin">Admin</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="au-save">Create User</button>`);
    document.getElementById('au-save').onclick = async () => {
        const d = { action: 'add', first_name: document.getElementById('au-fn').value, last_name: document.getElementById('au-ln').value, email: document.getElementById('au-em').value, password: document.getElementById('au-pw').value, role: document.getElementById('au-rl').value };
        if (!d.first_name || !d.email || !d.password) { showAlert('Please fill all required fields.', 'warning'); return; }
        try { const res = await apiPost('users.php', d); if (res.success) { showAlert('User created!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// ═══ CLINICS ═══
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
                    <td><div class="action-btns">${c.status === 'pending' ? `<button class="btn btn-sm btn-outline" style="color:var(--green-500);" onclick="approveClinic(${c.clinic_id})">Approve</button>` : ''}<button class="btn btn-sm btn-outline" onclick="viewClinic(${c.clinic_id},'${(c.clinic_name||'').replace(/'/g,"\\\\'")}','${(c.email||'').replace(/'/g,"\\\\'")}','${(c.location||'').replace(/'/g,"\\\\'")}','${c.status}',${c.rating||0},${c.specialist_count||0},${c.patient_count||0})">View</button></div></td>
                </tr>`).join('')}
                ${clinics.length === 0 ? '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">No clinics found</td></tr>' : ''}
            </tbody></table></div></div></div>`;
    let st; document.getElementById('admin-clinic-search').addEventListener('input', function () { clearTimeout(st); st = setTimeout(async () => { try { const [s, l] = await Promise.all([apiGet('clinics.php?action=stats'), apiGet('clinics.php?action=list&search=' + encodeURIComponent(this.value))]); renderClinicsView(main, s.stats, l.clinics, this.value); } catch (e) { } }, 400); });
    if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
}
function approveClinic(clinicId) { showConfirm('Are you sure you want to <strong>approve</strong> this clinic?', async () => { try { const res = await apiPost('clinics.php', { action: 'approve', clinic_id: clinicId }); if (res.success) { showAlert('Clinic approved!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } }); }
function showRegisterClinicModal() {
    showModal('Register New Clinic', `<div class="form-group"><label>Clinic Name</label><input type="text" id="rc-name" placeholder="Enter clinic name"></div><div class="form-group"><label>Email</label><input type="email" id="rc-email" placeholder="clinic@example.com"></div><div class="form-group"><label>Location</label><input type="text" id="rc-loc" placeholder="Enter address"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="rc-save">Register Clinic</button>`);
    document.getElementById('rc-save').onclick = async () => { const d = { action: 'register', clinic_name: document.getElementById('rc-name').value, email: document.getElementById('rc-email').value, location: document.getElementById('rc-loc').value }; if (!d.clinic_name || !d.email) { showAlert('Clinic name and email are required.', 'warning'); return; } try { const res = await apiPost('clinics.php', d); if (res.success) { showAlert('Clinic registered!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } };
}

// ═══ SUBSCRIPTIONS (Full CRUD) ═══
async function loadSubscriptionsView(main) {
    try {
        const [pd, rd, kd, cd] = await Promise.all([apiGet('subscriptions.php?action=plans'), apiGet('subscriptions.php?action=revenue'), apiGet('subscriptions.php?action=revenue_kpis'), apiGet('subscriptions.php?action=revenue_chart')]);
        const plans = pd.plans || [], revenue = rd.revenue || [], totalMRR = rd.total_mrr || 0;
        const k = kd.kpis || {}, monthly = cd.monthly_revenue || [], byPlan = cd.revenue_by_plan || [];
        const pc = ['plan-free', 'plan-standard', 'plan-premium'];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Subscriptions & Revenue</h1><p class="dashboard-subtitle">Manage plans, track subscribers and financial performance</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showCreatePlanModal()">+ Create Plan</button></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-info"><div class="admin-stat-value">$${fmtNum(k.mrr||0)}</div><div class="admin-stat-label">MRR</div><div class="admin-stat-trend trend-up">ARR: $${fmtNum(k.arr||0)}</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(k.active_subscribers||0)}</div><div class="admin-stat-label">Active Subscribers</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${k.churn_rate||0}%</div><div class="admin-stat-label">Churn Rate</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-info"><div class="admin-stat-value">${k.net_growth||0}%</div><div class="admin-stat-label">Net Growth</div></div></div>
        </div>
        <div class="plans-grid">${plans.map((p, i) => `<div class="plan-card ${pc[i] || ''} ${p.status === 'inactive' ? 'plan-inactive' : ''}">
            ${p.status === 'inactive' ? '<div class="plan-badge" style="background:var(--red-500);">Inactive</div>' : (i === plans.length - 1 && plans.length > 1 ? '<div class="plan-badge">Most Popular</div>' : '')}
            <div class="plan-header"><h3 class="plan-name">${p.plan_name}</h3><div class="plan-price">$${Number(p.price).toFixed(2)}<span>/${p.plan_period || 'mo'}</span></div></div>
            ${p.description ? `<p style="color:var(--text-secondary);font-size:.875rem;margin:0 0 1rem;">${p.description}</p>` : ''}
            <div class="plan-stats"><div class="plan-stat"><span class="plan-stat-value">${fmtNum(p.active_users)}</span><span class="plan-stat-label">Active Users</span></div><div class="plan-stat"><span class="plan-stat-value">${p.price > 0 ? '$' + fmtNum(p.mrr) : '—'}</span><span class="plan-stat-label">${p.price > 0 ? 'MRR' : '—'}</span></div></div>
            ${p.limits ? `<div style="margin:.75rem 0;padding:.75rem;background:var(--bg-secondary);border-radius:10px;"><div style="font-size:.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;">Usage Limits</div>${Object.entries(p.limits).map(([k,v]) => `<div style="display:flex;justify-content:space-between;font-size:.8rem;padding:.2rem 0;"><span>${k.replace(/_/g,' ').replace(/max /i,'Max ')}</span><span style="font-weight:600;">${v === -1 ? '∞ Unlimited' : v}</span></div>`).join('')}</div>` : ''}
            <ul class="plan-features">${(p.features || []).map(f => `<li>${f}</li>`).join('')}</ul>
            <div style="display:flex;gap:.5rem;margin-top:auto;">
                <button class="btn ${i === plans.length - 1 ? 'btn-gradient' : 'btn-outline'}" style="flex:1;" onclick='editPlan(${p.subscription_id},${JSON.stringify(p.plan_name)},${p.price},${JSON.stringify(p.plan_period || "monthly")},${JSON.stringify(p.description || "")},${JSON.stringify(p.status || "active")},${JSON.stringify(p.features || [])},${JSON.stringify(p.limits || {})})'>Edit</button>
                <button class="btn btn-outline" style="color:var(--red-500);padding:.5rem;" onclick="deletePlan(${p.subscription_id},'${p.plan_name.replace(/'/g, "\\'")}')">🗑</button>
            </div>
        </div>`).join('')}</div>
        <div class="overview-grid" style="margin-top:1.5rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Revenue Over Time</h2></div><div style="padding:1.5rem;"><canvas id="chart-rev-time" height="220"></canvas></div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Revenue by Plan</h2></div><div style="padding:1.5rem;"><canvas id="chart-rev-plan" height="220"></canvas></div></div>
        </div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Revenue by Plan (Breakdown)</h2></div><div style="padding:1.5rem;">
            ${revenue.map(r => `<div class="revenue-row"><span class="revenue-plan">${r.plan_name}</span><span class="revenue-count">${fmtNum(r.subscriber_count)} subscribers</span><span class="revenue-amount">$${fmtNum(r.monthly_revenue)}/mo</span></div>`).join('')}
            <div class="revenue-row revenue-total"><span class="revenue-plan">Total MRR</span><span></span><span class="revenue-amount">$${fmtNum(totalMRR)}/mo</span></div>
        </div></div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Top Paying Users</h2></div><div id="rev-top-users">Loading...</div></div></div>`;
        // Revenue charts
        if (typeof Chart !== 'undefined') {
            if(monthly.length) new Chart(document.getElementById('chart-rev-time'), {type:'bar',data:{labels:monthly.map(m=>m.month),datasets:[{label:'Revenue ($)',data:monthly.map(m=>m.revenue),backgroundColor:'rgba(16,185,129,0.7)',borderRadius:6}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});
            if(byPlan.length) new Chart(document.getElementById('chart-rev-plan'), {type:'pie',data:{labels:byPlan.map(p=>p.plan_name),datasets:[{data:byPlan.map(p=>p.revenue),backgroundColor:['#6366f1','#0d9488','#f59e0b','#ec4899']}]},options:{responsive:true}});
        }
        // Load top paying users
        apiGet('subscriptions.php?action=revenue_top_users').then(d=>{
            const area = document.getElementById('rev-top-users');
            if(!area) return;
            const users = d.users||[];
            area.innerHTML = `<div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>User</th><th>Email</th><th>Total Paid</th><th>Payments</th><th>Actions</th></tr></thead><tbody>${users.map(u=>`<tr><td>${u.first_name} ${u.last_name}</td><td>${u.email}</td><td>$${fmtNum(u.total_paid)}</td><td>${u.payment_count}</td><td><button class="btn btn-sm btn-outline" onclick="viewUser(${u.user_id})">View</button></td></tr>`).join('')}${users.length===0?'<tr><td colspan="5" style="text-align:center;padding:2rem;">No data</td></tr>':''}</tbody></table></div>`;
        });
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function editPlan(subId, name, price, period, description, status, features, limits) {
    const featText = Array.isArray(features) ? features.join('\n') : '';
    limits = limits || {};
    showModal('Edit Plan', `
        <div class="form-group"><label>Plan Name</label><input type="text" id="ep-name" value="${name}"></div>
        <div class="form-group"><label>Price</label><input type="number" id="ep-price" value="${price}" step="0.01"></div>
        <div class="form-group"><label>Duration</label><select id="ep-period"><option value="monthly" ${period === 'monthly' ? 'selected' : ''}>Monthly</option><option value="yearly" ${period === 'yearly' ? 'selected' : ''}>Yearly</option></select></div>
        <div class="form-group"><label>Description</label><textarea id="ep-desc" rows="2">${description}</textarea></div>
        <div class="form-group"><label>Status</label><select id="ep-status"><option value="active" ${status === 'active' ? 'selected' : ''}>Active</option><option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option></select></div>
        <div class="form-group"><label>Features (one per line)</label><textarea id="ep-feat" rows="4">${featText}</textarea></div>
        <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;margin-top:.5rem;">
            <label style="font-weight:600;font-size:.9rem;display:block;margin-bottom:.75rem;">📋 Usage Limits <span style="color:var(--text-secondary);font-weight:400;font-size:.8rem;">(-1 = unlimited)</span></label>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;">
                <div class="form-group"><label style="font-size:.8rem;">Max Speech Analyses</label><input type="number" id="ep-lim-speech" value="${limits.max_speech_analyses ?? 3}" min="-1"></div>
                <div class="form-group"><label style="font-size:.8rem;">Max Children</label><input type="number" id="ep-lim-children" value="${limits.max_children ?? 1}" min="-1"></div>
                <div class="form-group"><label style="font-size:.8rem;">Max Reports/Month</label><input type="number" id="ep-lim-reports" value="${limits.max_reports ?? 5}" min="-1"></div>
            </div>
        </div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ep-save">Save</button>`);
    document.getElementById('ep-save').onclick = async () => {
        const feats = document.getElementById('ep-feat').value.split('\n').map(f => f.trim()).filter(f => f);
        const limitsData = { max_speech_analyses: parseInt(document.getElementById('ep-lim-speech').value), max_children: parseInt(document.getElementById('ep-lim-children').value), max_reports: parseInt(document.getElementById('ep-lim-reports').value) };
        try { const res = await apiPost('subscriptions.php', { action: 'update_plan', subscription_id: subId, plan_name: document.getElementById('ep-name').value, price: parseFloat(document.getElementById('ep-price').value), plan_period: document.getElementById('ep-period').value, description: document.getElementById('ep-desc').value, status: document.getElementById('ep-status').value, features: feats, limits: limitsData }); if (res.success) { showAlert('Plan updated!', 'success'); setTimeout(() => { closeModal(); showAdminView('subscriptions'); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showCreatePlanModal() {
    showModal('Create New Plan', `
        <div class="form-group"><label>Plan Name</label><input type="text" id="cp-name" placeholder="e.g. Premium Plus"></div>
        <div class="form-group"><label>Price</label><input type="number" id="cp-price" placeholder="0.00" step="0.01"></div>
        <div class="form-group"><label>Duration</label><select id="cp-period"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
        <div class="form-group"><label>Description</label><textarea id="cp-desc" rows="2" placeholder="Brief plan description"></textarea></div>
        <div class="form-group"><label>Status</label><select id="cp-status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <div class="form-group"><label>Features (one per line)</label><textarea id="cp-feat" rows="4" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea></div>
        <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;margin-top:.5rem;">
            <label style="font-weight:600;font-size:.9rem;display:block;margin-bottom:.75rem;">📋 Usage Limits <span style="color:var(--text-secondary);font-weight:400;font-size:.8rem;">(-1 = unlimited)</span></label>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;">
                <div class="form-group"><label style="font-size:.8rem;">Max Speech Analyses</label><input type="number" id="cp-lim-speech" value="3" min="-1"></div>
                <div class="form-group"><label style="font-size:.8rem;">Max Children</label><input type="number" id="cp-lim-children" value="1" min="-1"></div>
                <div class="form-group"><label style="font-size:.8rem;">Max Reports/Month</label><input type="number" id="cp-lim-reports" value="5" min="-1"></div>
            </div>
        </div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="cp-save">Create Plan</button>`);
    document.getElementById('cp-save').onclick = async () => {
        const features = document.getElementById('cp-feat').value.split('\n').map(f => f.trim()).filter(f => f);
        const limits = { max_speech_analyses: parseInt(document.getElementById('cp-lim-speech').value), max_children: parseInt(document.getElementById('cp-lim-children').value), max_reports: parseInt(document.getElementById('cp-lim-reports').value) };
        const d = { action: 'create_plan', plan_name: document.getElementById('cp-name').value, price: parseFloat(document.getElementById('cp-price').value) || 0, plan_period: document.getElementById('cp-period').value, description: document.getElementById('cp-desc').value, status: document.getElementById('cp-status').value, features, limits };
        if (!d.plan_name) { showAlert('Plan name is required.', 'warning'); return; }
        try { const res = await apiPost('subscriptions.php', d); if (res.success) { showAlert('Plan created!', 'success'); setTimeout(() => { closeModal(); showAdminView('subscriptions'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function deletePlan(subId, planName) {
    showConfirm(`Are you sure you want to <strong>delete</strong> the plan "${planName}"? This cannot be undone.`, async () => {
        try { const res = await apiPost('subscriptions.php', { action: 'delete_plan', subscription_id: subId }); if (res.success) { showAlert('Plan deleted!', 'success'); setTimeout(() => { closeModal(); showAdminView('subscriptions'); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}

// ═══ POINTS ═══
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
    showModal('Edit Points Rule', `<div class="form-group"><label>Action Name</label><input type="text" id="er-name" value="${name}"></div><div class="form-group"><label>Points Value</label><input type="number" id="er-val" value="${value}"></div><div class="form-group"><label>Type</label><select id="er-sign"><option value="+" ${sign === '+' ? 'selected' : ''}>+ Deposit</option><option value="-" ${sign === '-' ? 'selected' : ''}>- Withdrawal</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="er-save">Save</button>`);
    document.getElementById('er-save').onclick = async () => { try { const res = await apiPost('points.php', { action: 'update_rule', refrence_id: ruleId, action_name: document.getElementById('er-name').value, points_value: parseInt(document.getElementById('er-val').value), adjust_sign: document.getElementById('er-sign').value }); if (res.success) { showAlert('Rule updated!', 'success'); setTimeout(() => { closeModal(); showAdminView('points'); }, 1000); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } };
}
function showAddRuleModal() {
    showModal('Add Points Rule', `<div class="form-group"><label>Action Name</label><input type="text" id="ar-name" placeholder="e.g. Daily Login"></div><div class="form-group"><label>Points Value</label><input type="number" id="ar-val" placeholder="10"></div><div class="form-group"><label>Type</label><select id="ar-sign"><option value="+">+ Deposit</option><option value="-">- Withdrawal</option></select></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ar-save">Add Rule</button>`);
    document.getElementById('ar-save').onclick = async () => { const d = { action: 'add_rule', action_name: document.getElementById('ar-name').value, points_value: parseInt(document.getElementById('ar-val').value), adjust_sign: document.getElementById('ar-sign').value }; if (!d.action_name || !d.points_value) { showAlert('Please fill all fields.', 'warning'); return; } try { const res = await apiPost('points.php', d); if (res.success) { showAlert('Rule added!', 'success'); setTimeout(() => { closeModal(); showAdminView('points'); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } };
}

// ═══ REPORTS (Behavioral Charts + Export) ═══
async function loadReportsView(main) {
    try {
        const [sd, cd, dd] = await Promise.all([apiGet('reports.php?action=stats'), apiGet('reports.php?action=behavior_categories'), apiGet('reports.php?action=development_status')]);
        const stats = sd.stats, categories = cd.categories || [], dev = dd.development_status;
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">System Reports</h1><p class="dashboard-subtitle">Platform-wide analytics, behavioral data & exports</p></div>
            <div class="header-actions-inline">
                <div class="export-btn-group">
                    <button class="btn btn-outline btn-export" onclick="exportReportPDF()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>PDF</button>
                    <button class="btn btn-outline btn-export" onclick="exportReportExcel()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>Excel</button>
                    <button class="btn btn-outline btn-export" onclick="exportReportCSV()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>CSV</button>
                </div>
            </div></div>
        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.growth_records)}</div><div class="admin-stat-label">Growth Records</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.voice_samples)}</div><div class="admin-stat-label">Voice Samples</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${stats.on_track_rate}%</div><div class="admin-stat-label">On Track Rate</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(stats.flagged_children)}</div><div class="admin-stat-label">Flagged Children</div></div></div>
        </div>
        <!-- Behavioral Filters -->
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Child Behavioral Progress</h2>
            <div class="report-filters" id="report-filters">
                <select id="rpt-child" class="search-input" style="width:auto;"><option value="">All Children</option></select>
                <select id="rpt-specialist" class="search-input" style="width:auto;"><option value="">All Specialists</option></select>
                <input type="date" id="rpt-date-from" class="search-input" style="width:auto;" placeholder="From">
                <input type="date" id="rpt-date-to" class="search-input" style="width:auto;" placeholder="To">
                <button class="btn btn-gradient btn-sm" onclick="loadBehavioralData()">Filter</button>
            </div></div>
            <div id="behavioral-charts-area" style="padding:1.5rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <div><canvas id="chart-improvement" height="250"></canvas></div>
                    <div><canvas id="chart-categories" height="250"></canvas></div>
                </div>
                <div id="behavioral-table-area" style="margin-top:1.5rem;"></div>
            </div>
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
        // Load filter dropdowns and initial behavioral data
        loadReportFilters();
        loadBehavioralData();
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function loadReportFilters() {
    try {
        const [cl, sl] = await Promise.all([apiGet('reports.php?action=children_list'), apiGet('reports.php?action=specialists_list')]);
        const childSel = document.getElementById('rpt-child');
        const specSel = document.getElementById('rpt-specialist');
        if (childSel && cl.children) cl.children.forEach(c => { const o = document.createElement('option'); o.value = c.child_id; o.textContent = `${c.first_name} ${c.last_name}`; childSel.appendChild(o); });
        if (specSel && sl.specialists) sl.specialists.forEach(s => { const o = document.createElement('option'); o.value = s.specialist_id; o.textContent = s.specialist_name; specSel.appendChild(o); });
    } catch (e) { console.error('Failed to load filters:', e); }
}

let _improvChart, _catChart;
async function loadBehavioralData() {
    const childId = document.getElementById('rpt-child')?.value || '';
    const specId = document.getElementById('rpt-specialist')?.value || '';
    const dateFrom = document.getElementById('rpt-date-from')?.value || '';
    const dateTo = document.getElementById('rpt-date-to')?.value || '';
    let url = `reports.php?action=behavioral_progress`;
    if (childId) url += `&child_id=${childId}`;
    if (specId) url += `&specialist_id=${specId}`;
    if (dateFrom) url += `&date_from=${dateFrom}`;
    if (dateTo) url += `&date_to=${dateTo}`;
    try {
        const data = await apiGet(url);
        if (!data.success) return;
        const children = data.children || [], catDist = data.category_distribution || [];
        // Improvement Score Chart
        if (_improvChart) _improvChart.destroy();
        const ctx1 = document.getElementById('chart-improvement');
        if (ctx1 && typeof Chart !== 'undefined') {
            _improvChart = new Chart(ctx1, { type: 'bar', data: { labels: children.map(c => c.first_name + ' ' + (c.last_name || '').charAt(0) + '.'), datasets: [
                { label: 'Improvement Score', data: children.map(c => c.improvement_score), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 6 },
                { label: 'Sessions', data: children.map(c => c.therapy_sessions), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 6 },
                { label: 'Attendance', data: children.map(c => c.attendance_days), backgroundColor: 'rgba(245,158,11,0.7)', borderRadius: 6 }
            ]}, options: { responsive: true, plugins: { title: { display: true, text: 'Child Progress Overview', color: getComputedStyle(document.body).getPropertyValue('--text-primary') || '#333' } }, scales: { y: { beginAtZero: true } } } });
        }
        // Category Distribution Chart
        if (_catChart) _catChart.destroy();
        const ctx2 = document.getElementById('chart-categories');
        if (ctx2 && typeof Chart !== 'undefined' && catDist.length > 0) {
            _catChart = new Chart(ctx2, { type: 'doughnut', data: { labels: catDist.map(c => c.category_name), datasets: [{ data: catDist.map(c => c.count), backgroundColor: ['#6366f1','#0d9488','#f59e0b','#ec4899','#8b5cf6','#ef4444'] }] }, options: { responsive: true, plugins: { title: { display: true, text: 'Behavior Category Distribution', color: getComputedStyle(document.body).getPropertyValue('--text-primary') || '#333' } } } });
        }
        // Table
        const tbl = document.getElementById('behavioral-table-area');
        if (tbl) {
            tbl.innerHTML = `<table class="clinic-table"><thead><tr><th>Child</th><th>Improvement</th><th>Sessions</th><th>Attendance</th><th>Milestones</th><th>Engagement</th></tr></thead><tbody>
                ${children.map(c => `<tr><td>${c.first_name} ${c.last_name}</td><td><div class="progress-bar-mini"><div class="progress-fill-mini" style="width:${c.improvement_score}%;background:${c.improvement_score >= 70 ? 'var(--green-500)' : c.improvement_score >= 40 ? 'var(--yellow-500)' : 'var(--red-500)'};"></div></div><span>${c.improvement_score}%</span></td><td>${c.therapy_sessions}</td><td>${c.attendance_days} days</td><td>${c.milestones_achieved}</td><td>${c.activity_engagement}</td></tr>`).join('')}
                ${children.length === 0 ? '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">No behavioral data found</td></tr>' : ''}
            </tbody></table>`;
        }
    } catch (e) { console.error('Behavioral data error:', e); }
}

// Export functions
let _cachedExportData = null;
async function getExportData() {
    if (_cachedExportData) return _cachedExportData;
    try { const d = await apiGet('reports.php?action=export&period=9999'); _cachedExportData = d.records || []; setTimeout(() => { _cachedExportData = null; }, 30000); return _cachedExportData; } catch (e) { showAlert('Failed to fetch export data', 'error'); return []; }
}

async function exportReportPDF() {
    const records = await getExportData();
    if (!records.length) { showAlert('No data to export', 'warning'); return; }
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.setFontSize(18); doc.text('Bright Steps - System Report', 14, 22);
        doc.setFontSize(10); doc.text('Generated: ' + new Date().toLocaleString(), 14, 30);
        const headers = [['#', 'Child Name', 'Height', 'Weight', 'Head Circ.', 'Date']];
        const rows = records.map((r, i) => [i + 1, `${r.first_name} ${r.last_name}`, r.height || '—', r.weight || '—', r.head_circumference || '—', fmtDate(r.recorded_at)]);
        doc.autoTable({ head: headers, body: rows, startY: 36, styles: { fontSize: 8 }, headStyles: { fillColor: [99, 102, 241] } });
        doc.save('bright-steps-report.pdf');
        showAlert('PDF exported successfully!', 'success');
    } catch (e) { showAlert('PDF export failed: ' + e.message, 'error'); }
}

async function exportReportExcel() {
    const records = await getExportData();
    if (!records.length) { showAlert('No data to export', 'warning'); return; }
    try {
        const wsData = [['Child Name', 'Height', 'Weight', 'Head Circumference', 'Date'], ...records.map(r => [`${r.first_name} ${r.last_name}`, r.height, r.weight, r.head_circumference, r.recorded_at])];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Report');
        XLSX.writeFile(wb, 'bright-steps-report.xlsx');
        showAlert('Excel file exported successfully!', 'success');
    } catch (e) { showAlert('Excel export failed: ' + e.message, 'error'); }
}

async function exportReportCSV() {
    const records = await getExportData();
    if (!records.length) { showAlert('No data to export', 'warning'); return; }
    const headers = 'Child Name,Height,Weight,Head Circumference,Date\n';
    const rows = records.map(r => `"${r.first_name} ${r.last_name}",${r.height || ''},${r.weight || ''},${r.head_circumference || ''},"${r.recorded_at || ''}"`).join('\n');
    const blob = new Blob([headers + rows], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'bright-steps-report.csv'; a.click();
    showAlert('CSV exported successfully!', 'success');
}

// ═══ SETTINGS ═══
async function loadSettingsView(main) {
    try {
        const [pd, cd] = await Promise.all([apiGet('settings.php?action=profile'), apiGet('settings.php?action=config')]);
        const profile = pd.profile, config = cd.config || {};
        const initials = ((profile?.first_name?.[0] || '') + (profile?.last_name?.[0] || '')).toUpperCase() || 'AD';

        let notifSettings = {};
        try { const nd = await apiGet('settings.php?action=notifications'); notifSettings = nd.settings || {}; } catch(e) {}

        const togRow = (key, label, onchange) => `
            <div class="toggle-row"><span>${label}</span>
                <label class="toggle-switch"><input type="checkbox" ${key} onchange="${onchange}"><span class="toggle-slider"></span></label>
            </div>`;

        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Settings</h1><p class="dashboard-subtitle">Manage your admin account and platform configuration</p></div></div>

        <div class="settings-grid">
            <!-- Admin Profile -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Admin Profile</h2></div>
                <div style="padding:1.5rem;">
                    <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#fff;flex-shrink:0;">${initials}</div>
                        <div style="flex:1;min-width:150px;">
                            <h3 style="margin:0 0 .25rem;">${profile?.first_name || ''} ${profile?.last_name || ''}</h3>
                            <p style="color:var(--text-secondary);font-size:.875rem;margin:0;">${profile?.email || ''}</p>
                            <span class="role-badge role-admin" style="margin-top:.5rem;">Administrator</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                        <button class="btn btn-outline" onclick="showEditAdminProfile(${JSON.stringify(profile?.first_name||'')},${JSON.stringify(profile?.last_name||'')})">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:4px;vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit Profile
                        </button>
                        <button class="btn btn-outline" onclick="showAdminChangePassword()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:4px;vertical-align:middle;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Change Password
                        </button>
                    </div>
                    <div style="background:var(--bg-secondary);border-radius:10px;padding:.75rem 1rem;display:flex;align-items:center;gap:.75rem;margin-top:1.25rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--green-500)" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span style="font-size:.8125rem;color:var(--text-secondary);">Email is managed via the authentication system and cannot be changed here.</span>
                    </div>
                </div>
            </div>

            <!-- Platform Configuration -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Platform Configuration</h2></div>
                <div style="padding:1.5rem;">
                    ${togRow(config.allow_clinic_registration==='1'?'checked':'', 'Allow new clinic registrations', "updateConfig('allow_clinic_registration',this.checked?'1':'0')")}
                    ${togRow(config.auto_approve_clinics==='1'?'checked':'', 'Auto-approve verified clinics', "updateConfig('auto_approve_clinics',this.checked?'1':'0')")}
                    ${togRow(config.enable_free_trial==='1'?'checked':'', 'Enable free trial signups', "updateConfig('enable_free_trial',this.checked?'1':'0')")}
                    ${togRow(config.weekly_digest==='1'?'checked':'', 'Send weekly platform digest', "updateConfig('weekly_digest',this.checked?'1':'0')")}
                    <div class="toggle-row" style="border-bottom:none;border-top:2px solid var(--red-400);margin-top:.5rem;padding-top:1rem;">
                        <span style="color:var(--red-500);font-weight:600;">⚠ Maintenance Mode</span>
                        <label class="toggle-switch"><input type="checkbox" ${config.maintenance_mode==='1'?'checked':''} onchange="updateConfig('maintenance_mode',this.checked?'1':'0')"><span class="toggle-slider"></span></label>
                    </div>
                </div>
            </div>

            <!-- Admin Notifications -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Admin Notifications</h2></div>
                <div style="padding:1.5rem;">
                    ${togRow(notifSettings.push_notifications!=='0'?'checked':'', 'Push Notifications', "saveAdminNotifSetting('push_notifications',this.checked)")}
                    ${togRow(notifSettings.email_updates!=='0'?'checked':'', 'Email Updates', "saveAdminNotifSetting('email_updates',this.checked)")}
                    ${togRow(notifSettings.system_alerts!=='0'?'checked':'', 'System Alerts', "saveAdminNotifSetting('system_alerts',this.checked)")}
                    ${togRow(notifSettings.weekly_reports!=='0'?'checked':'', 'Weekly Reports', "saveAdminNotifSetting('weekly_reports',this.checked)")}
                </div>
            </div>

            <!-- Preferences -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Preferences</h2></div>
                <div style="padding:1.5rem;">
                    <div class="toggle-row">
                        <span>Language</span>
                        <select class="settings-select" onchange="updateConfig('language',this.value)">
                            <option value="en" ${(config.language||'en')==='en'?'selected':''}>English</option>
                            <option value="ar" ${config.language==='ar'?'selected':''}>العربية</option>
                        </select>
                    </div>
                    ${togRow(config.data_sharing==='1'?'checked':'', 'Allow data sharing for improvements', "updateConfig('data_sharing',this.checked?'1':'0')")}
                    ${togRow(config.dark_mode_default==='1'?'checked':'', 'Default dark mode for new admins', "updateConfig('dark_mode_default',this.checked?'1':'0')")}
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="section-card danger-card" style="grid-column:1/-1;">
                <div class="section-card-header"><h2 class="section-heading" style="color:var(--red-600);">⚠ Danger Zone</h2></div>
                <div style="padding:1.5rem;">
                    <p style="color:var(--text-secondary);margin-bottom:1.25rem;font-size:.875rem;">These actions affect the entire platform and cannot be easily undone.</p>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);" onclick="purgeInactiveUsers()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:4px;vertical-align:middle;"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Purge Inactive Users
                        </button>
                        <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);" onclick="resetPointsSystem()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:4px;vertical-align:middle;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            Reset Points System
                        </button>
                    </div>
                </div>
            </div>
        </div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function saveAdminNotifSetting(key, checked) {
    try { await apiPost('settings.php', { action: 'update_notifications', key, value: checked ? '1' : '0' }); } catch(e) { showAlert('Error saving setting', 'error'); }
}

function showEditAdminProfile(firstName, lastName) {
    showModal('Edit Profile', `
        <div class="form-group"><label>First Name</label><input type="text" id="ap-fname" value="${firstName}"></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="ap-lname" value="${lastName}"></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ap-save">Save</button>`);
    document.getElementById('ap-save').onclick = async () => {
        const f = document.getElementById('ap-fname').value.trim();
        const l = document.getElementById('ap-lname').value.trim();
        if (!f || !l) { showAlert('Both fields required', 'warning'); return; }
        try {
            const r = await apiPost('settings.php', { action: 'update_profile', first_name: f, last_name: l });
            if (r.success) { showAlert('Profile updated!', 'success'); setTimeout(() => { closeModal(); showAdminView('settings'); }, 1000); }
            else showAlert(r.error || 'Failed', 'error');
        } catch(e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

function showAdminChangePassword() {
    showModal('Change Password', `
        <div class="form-group"><label>Current Password</label><input type="password" id="cp-cur" placeholder="Enter current password"></div>
        <div class="form-group"><label>New Password</label><input type="password" id="cp-new" placeholder="At least 8 characters"></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" id="cp-conf" placeholder="Repeat new password"></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="cp-save">Update Password</button>`);
    document.getElementById('cp-save').onclick = async () => {
        const cur = document.getElementById('cp-cur').value;
        const nw = document.getElementById('cp-new').value;
        const conf = document.getElementById('cp-conf').value;
        if (!cur || !nw || !conf) { showAlert('All fields required', 'warning'); return; }
        if (nw !== conf) { showAlert('New passwords do not match', 'warning'); return; }
        if (nw.length < 8) { showAlert('Password must be at least 8 characters', 'warning'); return; }
        try {
            const r = await fetch('api_email_verify.php?action=change-password', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ current_password: cur, new_password: nw }) });
            const data = await r.json();
            if (data.success) { showAlert('Password updated successfully!', 'success'); closeModal(); }
            else showAlert(data.error || 'Failed', 'error');
        } catch(e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

async function updateConfig(key, value) { try { await apiPost('settings.php', { action: 'update_config', setting_key: key, setting_value: value }); } catch (e) { showAlert('Error updating setting: ' + e.message, 'error'); } }
function purgeInactiveUsers() { showConfirm('This will <strong>permanently delete</strong> all inactive parent accounts older than 6 months. This cannot be undone.', async () => { try { const res = await apiPost('settings.php', { action: 'purge_inactive' }); showAlert(res.message || 'Done', 'success'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } }, 'error'); }
function resetPointsSystem() { showConfirm('This will <strong>reset ALL points wallets to 0</strong>. This action cannot be undone.', async () => { try { const res = await apiPost('settings.php', { action: 'reset_points' }); showAlert(res.message || 'Done', 'success'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } }, 'error'); }
function handleLogout() { showConfirm('Are you sure you want to log out?', () => { window.location.href = 'logout.php'; }, 'info'); }
