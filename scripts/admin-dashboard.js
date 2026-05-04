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

async function showAdminView(viewId) {
    const main = document.getElementById('admin-main-content');
    if (!main) return;
    
    // Update active state in sidebar
    const allNavItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item[data-view]');
    allNavItems.forEach(n => n.classList.remove('active'));
    const targetNav = document.querySelector(`.nav-item[data-view="${viewId}"]`);
    if (targetNav) targetNav.classList.add('active');

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
    try {
        const loaders = { 'overview': window.loadOverviewView || loadOverviewView, 'users': window.loadUsersView || (typeof loadUsersView!=='undefined'?loadUsersView:null), 'clinics': window.loadClinicsView || (typeof loadClinicsView!=='undefined'?loadClinicsView:null), 'subscriptions': window.loadSubscriptionsView || (typeof loadSubscriptionsView!=='undefined'?loadSubscriptionsView:null), 'points': window.loadPointsView || (typeof loadPointsView!=='undefined'?loadPointsView:null), 'reports': window.loadReportsView || (typeof loadReportsView!=='undefined'?loadReportsView:null), 'settings': window.loadSettingsView || (typeof loadSettingsView!=='undefined'?loadSettingsView:null), 'notifications_mgmt': window.loadNotificationsView, 'moderation': window.loadModerationView || (typeof loadModerationView!=='undefined'?loadModerationView:null), 'system_health': window.loadSystemHealthView || (typeof loadSystemHealthView!=='undefined'?loadSystemHealthView:null), 'roles': window.loadRolesView || (typeof loadRolesView!=='undefined'?loadRolesView:null), 'tickets': window.loadTicketsView, 'banners': window.loadBannersView || (typeof loadBannersView!=='undefined'?loadBannersView:null), 'logs': window.loadLogsView };
        const fn = loaders[viewId]; 
        if (fn) {
            await fn(main);
        } else {
            main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>View Not Found</h2><p>Could not load the requested view (${viewId}). Please refresh the page.</p></div>`;
        }
    } catch(e) {
        console.error('Error rendering view:', e);
        main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Render Error</h2><p>${e.message}</p></div>`;
    }
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

// ═══ TOPBAR NOTIFICATIONS ═══
async function toggleAdminNotifDropdown() {
    const dropdown = document.getElementById('admin-notif-dropdown');
    if (!dropdown) return;
    const isVisible = dropdown.style.display !== 'none';
    if (!isVisible) {
        await loadAdminNotifications();
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('admin-notif-dropdown');
    const trigger = document.getElementById('admin-topbar-notification');
    if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

async function loadAdminNotifications() {
    const contentDiv = document.getElementById('admin-notif-content');
    if (!contentDiv) return;
    try {
        const res = await fetch(ADMIN_API + 'notifications.php?action=list');
        const data = await res.json();
        const notifications = data.notifications || [];
        const unreadCount = data.unread || data.unread_count || 0;

        const badge = document.getElementById('admin-notif-badge');
        if (badge) badge.style.display = unreadCount > 0 ? 'block' : 'none';
        const countBadge = document.getElementById('admin-notif-count');
        if (countBadge) {
            if (unreadCount > 0) { countBadge.textContent = unreadCount + ' new'; countBadge.style.display = 'inline-block'; }
            else { countBadge.style.display = 'none'; }
        }

        if (notifications.length === 0) {
            contentDiv.innerHTML = `<div style="padding:3rem 1.5rem; text-align:center;"><div style="width:48px; height:48px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div><p style="color:var(--text-secondary); font-size:0.85rem; margin:0;">No new notifications</p></div>`;
        } else {
            contentDiv.innerHTML = notifications.map(n => `
                <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border); display:flex; gap:0.875rem; align-items:flex-start; cursor:pointer; background:${n.is_read == 0 ? 'var(--bg-secondary)' : 'transparent'};" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='${n.is_read == 0 ? 'var(--bg-secondary)' : 'transparent'}'" onclick="markSingleAdminNotifRead(${n.notification_id}, this)">
                    <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(139,92,246,0.1));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <div style="flex:1; min-width:0; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                            <span style="font-weight:600; font-size:0.9rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${n.title || 'Notification'}</span>
                            <span style="font-size:0.7rem; color:var(--text-secondary); white-space:nowrap;">${timeAgo(n.created_at)}</span>
                        </div>
                        <p style="margin:0; font-size:0.82rem; color:var(--text-secondary); line-height:1.4;">${n.message || ''}</p>
                        ${n.is_read == 0 ? '<div class="unread-dot" style="width:6px;height:6px;background:var(--red-500);border-radius:50%;position:absolute;right:0;top:50%;transform:translateY(-50%);"></div>' : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        contentDiv.innerHTML = `<div style="padding:2rem;text-align:center;color:var(--red-500);"><p>Error loading notifications</p></div>`;
    }
}

async function markAllAdminNotifRead() {
    try {
        await fetch('api_notifications.php?action=read', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
        await loadAdminNotifications();
    } catch (e) { console.error(e); }
}

async function markSingleAdminNotifRead(id, el) {
    try {
        await fetch('api_notifications.php?action=read', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ notification_id: id }) });
        const dot = el.querySelector('.unread-dot'); if (dot) dot.remove();
        el.style.background = 'transparent';
        const badge = document.getElementById('admin-notif-badge');
        const countBadge = document.getElementById('admin-notif-count');
        if (countBadge && countBadge.textContent) {
            let cnt = parseInt(countBadge.textContent);
            if (cnt > 1) { countBadge.textContent = (cnt - 1) + ' new'; }
            else { countBadge.style.display = 'none'; if(badge) badge.style.display = 'none'; }
        }
    } catch (e) { console.error(e); }
}
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
        const s = data.stats || {}, dist = data.user_distribution || {}, sysLogs = data.system_logs || [], topClinics = data.top_clinics || [], payments = data.recent_payments || [], audit = data.recent_audit || [];
        const totalDist = (dist.parent || 0) + (dist.specialist || 0) + (dist.admin || 0) + (dist.clinic || 0);
        const now = new Date();
        const greeting = now.getHours() < 12 ? 'Good Morning' : now.getHours() < 18 ? 'Good Afternoon' : 'Good Evening';
        const dateStr = now.toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric', year:'numeric'});
        const logLevelColor = l => l === 'error' ? 'var(--red-500)' : l === 'warning' ? 'var(--yellow-500)' : 'var(--green-500)';
        const logLevelBg = l => l === 'error' ? 'rgba(239,68,68,0.1)' : l === 'warning' ? 'rgba(245,158,11,0.1)' : 'rgba(16,185,129,0.1)';

        main.innerHTML = `<div class="dashboard-content">
        <!-- Welcome Banner -->
        <div style="background:linear-gradient(135deg,#4f46e5,#6366f1,#7c3aed);border-radius:20px;padding:1.5rem 2rem;margin-bottom:1rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
            <div style="position:absolute;bottom:-30px;right:60px;width:80px;height:80px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;">
                <h1 style="font-size:1.6rem;font-weight:800;margin:0 0 .25rem;color:white !important;">${greeting}, Admin 👋</h1>
                <p style="opacity:.9;margin:0;font-size:.9rem;color:white !important;">${dateStr} — Here's your platform at a glance</p>
            </div>
            <div style="display:flex;gap:.6rem;margin-top:1rem;position:relative;z-index:1;">
                <button class="btn" onclick="showAdminView('users')" style="background:rgba(255,255,255,0.95);color:#4f46e5;border:none;font-size:.8rem;padding:.5rem 1.1rem;border-radius:10px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform=''">👥 Manage Users</button>
                <button class="btn" onclick="showAdminView('reports')" style="background:rgba(255,255,255,0.95);color:#4f46e5;border:none;font-size:.8rem;padding:.5rem 1.1rem;border-radius:10px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform=''">📊 View Reports</button>
                <button class="btn" onclick="showAdminView('tickets')" style="background:rgba(255,255,255,0.95);color:#4f46e5;border:none;font-size:.8rem;padding:.5rem 1.1rem;border-radius:10px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform=''">🎫 Support</button>
            </div>
        </div>

        <!-- Primary Stats -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.85rem;margin-bottom:.85rem;">
            <div style="background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(99,102,241,0.03));border:1px solid rgba(99,102,241,0.15);border-radius:14px;padding:1rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(99,102,241,0.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;"><div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:.9rem;">👥</div><div style="font-size:.65rem;font-weight:600;color:${s.users_trend >= 0 ? 'var(--green-500)' : 'var(--red-500)'};background:${s.users_trend >= 0 ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};padding:2px 6px;border-radius:5px;">${s.users_trend >= 0 ? '↑' : '↓'} ${Math.abs(s.users_trend)}%</div></div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtNum(s.total_users)}</div>
                <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.15rem;">Total Users</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(13,148,136,0.1),rgba(13,148,136,0.03));border:1px solid rgba(13,148,136,0.15);border-radius:14px;padding:1rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(13,148,136,0.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;"><div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;font-size:.9rem;">🏥</div><div style="font-size:.65rem;font-weight:600;color:var(--green-500);background:rgba(16,185,129,0.1);padding:2px 6px;border-radius:5px;">+${s.new_clinics} new</div></div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtNum(s.active_clinics)}</div>
                <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.15rem;">Active Clinics</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03));border:1px solid rgba(16,185,129,0.15);border-radius:14px;padding:1rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(16,185,129,0.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;"><div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;font-size:.9rem;">💰</div><div style="font-size:.65rem;font-weight:600;color:${s.revenue_trend >= 0 ? 'var(--green-500)' : 'var(--red-500)'};background:${s.revenue_trend >= 0 ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};padding:2px 6px;border-radius:5px;">${s.revenue_trend >= 0 ? '↑' : '↓'} ${Math.abs(s.revenue_trend)}%</div></div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtMoney(s.total_revenue)}</div>
                <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.15rem;">Total Revenue</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));border:1px solid rgba(245,158,11,0.15);border-radius:14px;padding:1rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(245,158,11,0.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;"><div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:.9rem;">💳</div></div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtNum(s.active_subscriptions)}</div>
                <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.15rem;">Active Subscriptions</div>
            </div>
        </div>

        <!-- Secondary Stats (Valuable Info) -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.6rem;margin-bottom:1.1rem;">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:.7rem .5rem;text-align:center;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);">${fmtNum(s.total_children)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">👶 Children</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:.7rem .5rem;text-align:center;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);">${fmtNum(s.total_specialists)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">🩺 Specialists</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:.7rem .5rem;text-align:center;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);">${fmtNum(s.total_appointments)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">📅 Appointments</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:.7rem .5rem;text-align:center;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);">${fmtNum(s.growth_records)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">📈 Growth Records</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid ${s.open_tickets > 0 ? 'rgba(239,68,68,0.3)' : 'var(--border)'};border-radius:10px;padding:.7rem .5rem;text-align:center;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.25rem;font-weight:800;color:${s.open_tickets > 0 ? 'var(--red-500)' : 'var(--text-primary)'}">${fmtNum(s.open_tickets)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">🎫 Open Tickets</div>
            </div>
        </div>

        <!-- System Logs + User Distribution -->
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">System Logs</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">Live</span></div><div style="max-height:260px;overflow-y:auto;padding:.35rem .75rem;">
                ${sysLogs.map(l => `<div style="display:flex;align-items:flex-start;gap:.75rem;padding:.6rem 0;border-bottom:1px solid var(--border);transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:4px;background:${logLevelBg(l.level)};color:${logLevelColor(l.level)};text-transform:uppercase;flex-shrink:0;margin-top:2px;">${l.level||'info'}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.8rem;color:var(--text-primary);word-break:break-word;">${l.message||'—'}</div>
                        <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.2rem;">${l.method||''} ${l.endpoint||''} ${l.response_time_ms ? '• '+l.response_time_ms+'ms' : ''} • ${timeAgo(l.created_at)}</div>
                    </div>
                </div>`).join('')}
                ${sysLogs.length === 0 ? '<div style="padding:2rem;text-align:center;color:var(--text-secondary);"><p>No system logs</p></div>' : ''}
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">User Distribution</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">${fmtNum(totalDist)} total</span></div><div style="padding:1.5rem;">
                <div style="display:flex;justify-content:center;margin-bottom:1.5rem;">
                    <div style="width:140px;height:140px;border-radius:50%;background:conic-gradient(#6366f1 0% ${totalDist ? ((dist.parent||0)/totalDist*100) : 0}%, #0d9488 ${totalDist ? ((dist.parent||0)/totalDist*100) : 0}% ${totalDist ? (((dist.parent||0)+(dist.specialist||0))/totalDist*100) : 0}%, #d97706 ${totalDist ? (((dist.parent||0)+(dist.specialist||0))/totalDist*100) : 0}% ${totalDist ? (((dist.parent||0)+(dist.specialist||0)+(dist.clinic||0))/totalDist*100) : 0}%, #ec4899 ${totalDist ? (((dist.parent||0)+(dist.specialist||0)+(dist.clinic||0))/totalDist*100) : 0}% 100%);display:flex;align-items:center;justify-content:center;">
                        <div style="width:90px;height:90px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);">${fmtNum(totalDist)}</div><div style="font-size:.6rem;color:var(--text-secondary);">Users</div></div>
                    </div>
                </div>
                <div class="distribution-bar-wrap">
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>Parents</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.parent || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div></div><div class="dist-value">${fmtNum(dist.parent || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#0d9488;"></span>Specialists</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.specialist || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#0d9488,#14b8a6);"></div></div><div class="dist-value">${fmtNum(dist.specialist || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#d97706;"></span>Clinics</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.clinic || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#d97706,#f59e0b);"></div></div><div class="dist-value">${fmtNum(dist.clinic || 0)}</div></div>
                <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#ec4899;"></span>Admins</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.admin || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#ec4899,#f472b6);"></div></div><div class="dist-value">${fmtNum(dist.admin || 0)}</div></div>
            </div></div></div>
        </div>

        <!-- Top Clinics + Recent Payments -->
        <div class="overview-grid" style="margin-top:.85rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Top Clinics</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">By Rating</span></div><div class="patients-list">
                ${topClinics.map((c,i) => `<div class="patient-row" style="transition:background .15s;padding:.6rem;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <div style="width:28px;text-align:center;font-size:${i < 3 ? '1.1rem' : '.8rem'};font-weight:700;">${['🥇','🥈','🥉'][i] || '#'+(i+1)}</div>
                    <div style="flex:1;"><div style="font-weight:600;font-size:.85rem;">${c.clinic_name}</div><div style="font-size:.7rem;color:var(--text-secondary);">${c.specialist_count} specialists</div></div>
                    <div style="font-weight:700;color:var(--yellow-500);">★ ${Number(c.rating).toFixed(1)}</div>
                </div>`).join('')}
                ${topClinics.length === 0 ? '<div style="padding:1.5rem;text-align:center;color:var(--text-secondary);">No clinics</div>' : ''}
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Recent Payments</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">Latest</span></div><div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
                ${payments.map(p => `<tr style="transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''"><td>${p.plan_name||'—'}</td><td style="font-weight:700;color:var(--green-500);">$${Number(p.amount_post_discount).toFixed(2)}</td><td>${p.method||'—'}</td><td><span class="status-badge ${p.status==='paid'?'status-active':'status-warning'}">${p.status}</span></td><td>${p.paid_at ? fmtDate(p.paid_at) : '—'}</td></tr>`).join('')}
                ${payments.length === 0 ? '<tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">No payments yet</td></tr>' : ''}
            </tbody></table></div></div>
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
        try { const res = await apiPost('users.php', d); if (res.success) { showAlert('User created!', 'success'); setTimeout(() => { closeModal(); filterUsers(); }, 1200); } else showAlert(res.error || 'Failed', 'error'); } catch (e) { showAlert('Error: ' + e.message, 'error'); } };
}

// ═══ CLINIC MANAGEMENT — moved to admin-clinics-view.js ═══

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

// ═══ POINTS (Modernized) ═══
async function loadPointsView(main) {
    try {
        const [sd, wd, ed] = await Promise.all([apiGet('points.php?action=stats'), apiGet('points.php?action=top_wallets'), apiGet('engagement.php?action=all_data')]);
        const stats = sd.stats, wallets = wd.wallets || [], rules = ed.rules || [], badges = ed.badges || [], banners = ed.banners || [];
        const medals = ['🥇','🥈','🥉'];
        const styleColors = {info:'#6366f1',warning:'#f59e0b',success:'#10b981',error:'#ef4444'};
        main.innerHTML = `<div class="dashboard-content">
        <!-- Hero Header -->
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706,#b45309);border-radius:20px;padding:1.5rem 2rem;margin-bottom:1rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:20px;font-size:80px;opacity:.15;">🏆</div>
            <div style="position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 .25rem;color:white !important;">Engagement & Rewards</h1>
                    <p style="opacity:.85;margin:0;font-size:.95rem;color:white !important;">Points, badges & banners — gamify the platform experience</p>
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button class="btn" onclick="showAddRuleModal()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);font-size:.8rem;padding:.45rem 1rem;border-radius:10px;cursor:pointer;">+ Rule</button>
                    <button class="btn" onclick="showAddBadgeModal()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);font-size:.8rem;padding:.45rem 1rem;border-radius:10px;cursor:pointer;">+ Badge</button>
                    <button class="btn" onclick="showAddBannerModal()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);font-size:.8rem;padding:.45rem 1rem;border-radius:10px;cursor:pointer;">+ Banner</button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));border:1px solid rgba(245,158,11,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(245,158,11,0.15)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:1rem;">⭐</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtNum(stats.total_points_issued)}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Points Issued</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(99,102,241,0.03));border:1px solid rgba(99,102,241,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(99,102,241,0.15)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;">👛</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);line-height:1;">${fmtNum(stats.active_wallets)}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Active Wallets</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03));border:1px solid rgba(16,185,129,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(16,185,129,0.15)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;font-size:1rem;">🏅</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);line-height:1;">${badges.length}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Badges</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(236,72,153,0.1),rgba(236,72,153,0.03));border:1px solid rgba(236,72,153,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(236,72,153,0.15)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;font-size:1rem;">📢</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);line-height:1;">${banners.filter(b=>b.is_active).length}/${banners.length}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Active Banners</div>
            </div>
        </div>

        <!-- Points Rules + Leaderboard -->
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Points Rules</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">${rules.length} rules</span></div><div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Action</th><th>Points</th><th>Type</th><th>Actions</th></tr></thead><tbody>
                ${rules.map(r => `<tr style="transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''"><td><strong>${r.action_name}</strong></td><td><span style="font-weight:700;color:${r.adjust_sign === '+' ? 'var(--green-500)' : 'var(--red-500)'};">${r.adjust_sign}${r.points_value}</span></td><td><span style="font-size:.75rem;padding:3px 10px;border-radius:6px;background:${r.adjust_sign === '+' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};color:${r.adjust_sign === '+' ? 'var(--green-500)' : 'var(--red-500)'};">${r.adjust_sign === '+' ? '↑ Deposit' : '↓ Withdrawal'}</span></td><td><button class="btn btn-sm btn-outline" onclick="editRule(${r.refrence_id},'${r.action_name.replace(/'/g, "\\\\'")}',${r.points_value},'${r.adjust_sign}')">Edit</button></td></tr>`).join('')}
                ${rules.length === 0 ? '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-secondary);">No rules</td></tr>' : ''}
            </tbody></table></div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">🏆 Leaderboard</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">Top ${wallets.length}</span></div><div class="patients-list" style="max-height:320px;overflow-y:auto;">
                ${wallets.map((w, i) => `<div class="patient-row" style="transition:background .15s;border-radius:10px;padding:.6rem;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <div style="width:28px;text-align:center;font-size:${i < 3 ? '1.2rem' : '.8rem'};font-weight:700;">${i < 3 ? medals[i] : '#'+(i+1)}</div>
                    <div class="patient-avatar" style="${avatarColors.parent}">${getInitials(w.first_name, w.last_name)}</div>
                    <div class="patient-info"><div class="patient-name">${w.first_name} ${w.last_name}</div><div class="patient-details">${w.badge_count} badges</div></div>
                    <div style="font-weight:800;font-size:.9rem;color:var(--text-primary);background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));padding:5px 12px;border-radius:8px;border:1px solid rgba(245,158,11,0.15);">${fmtNum(w.total_points)} pts</div>
                </div>`).join('')}
                ${wallets.length === 0 ? '<div style="padding:2rem;text-align:center;color:var(--text-secondary);">🏆 No wallets yet</div>' : ''}
            </div></div>
        </div>

        <!-- Badges + Banners -->
        <div class="overview-grid" style="margin-top:1rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">🏅 Badges</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">${badges.length} badges</span></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.5rem;padding:.75rem;">
                ${(() => {
                    const badgeIconMap = {first_steps:'👶',voice_hero:'🎤',weekly_champion:'🏆',growth_tracker:'📈',super_parent:'⭐',rising_star:'🌟',consistency_king:'👑',weekly_champ:'🏅',monthly_master:'🎯',milestone_maker:'🎪',data_wizard:'🧙',health_hero:'💪',speech_star:'🗣️',explorer:'🧭',bookworm:'📚'};
                    return badges.map(b => `<div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;padding:.75rem .5rem;text-align:center;transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column;justify-content:space-between;" onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
                        <div style="font-size:1.5rem;margin-bottom:.3rem;">${badgeIconMap[b.icon] || '🏅'}</div>
                        <div style="font-weight:700;font-size:.75rem;color:var(--text-primary);line-height:1.2;">${b.name}</div>
                        <div style="font-size:.6rem;color:var(--text-secondary);margin-top:.3rem;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;white-space:normal;">${b.description || '—'}</div>
                        <button class="btn btn-sm btn-outline" style="margin-top:.5rem;font-size:.6rem;padding:2px 6px;align-self:center;" onclick="deleteBadge(${b.badge_id},'${(b.name||'').replace(/'/g,"\\\\'")}')">Delete</button>
                    </div>`).join('');
                })()}
                ${badges.length === 0 ? '<div style="padding:1.5rem;text-align:center;color:var(--text-secondary);grid-column:1/-1;">No badges defined yet</div>' : ''}
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">📢 Banners</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">${banners.length} banners</span></div>
                <div style="padding:.75rem 1rem;max-height:320px;overflow-y:auto;">
                ${banners.map(b => `<div style="display:flex;align-items:center;gap:.75rem;padding:.6rem;border-bottom:1px solid var(--border);transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <div style="width:6px;height:40px;border-radius:3px;background:${styleColors[b.style]||'#6366f1'};flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.8rem;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${b.message}</div>
                        <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.15rem;">${b.target_audience} • ${b.style}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
                        <span style="font-size:.65rem;padding:2px 8px;border-radius:4px;background:${b.is_active ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};color:${b.is_active ? 'var(--green-500)' : 'var(--red-500)'};">${b.is_active ? 'Active' : 'Inactive'}</span>
                        <button class="btn btn-sm btn-outline" style="font-size:.65rem;" onclick="deleteBannerEngagement(${b.id})">×</button>
                    </div>
                </div>`).join('')}
                ${banners.length === 0 ? '<div style="padding:2rem;text-align:center;color:var(--text-secondary);">No banners yet</div>' : ''}
            </div></div>
        </div>
        
        <!-- Reward Offers -->
        <div class="section-card" style="margin-top:1rem;">
            <div class="section-card-header">
                <h2 class="section-heading">🎁 Redeemable Reward Offers</h2>
                <button class="btn btn-sm btn-gradient" onclick="showAddOfferModal()">+ Add Offer</button>
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead><tr><th>Icon</th><th>Offer Title</th><th>Description</th><th>Cost (Points)</th><th>Actions</th></tr></thead>
                    <tbody>
                        ${(ed.offers || []).map(o => `<tr style="transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                            <td style="font-size:1.5rem;text-align:center;">${o.icon || '🎁'}</td>
                            <td><strong>${o.title}</strong></td>
                            <td style="color:var(--text-secondary);font-size:.85rem;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${o.description || '—'}</td>
                            <td><span style="font-weight:700;color:var(--yellow-500);background:rgba(245,158,11,0.1);padding:4px 8px;border-radius:6px;">⭐ ${o.points_required}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="editOffer(${o.offer_id},'${(o.title||'').replace(/'/g,"\\\\'")}',${o.points_required},'${(o.description||'').replace(/'/g,"\\\\'")}', '${(o.icon||'🎁')}')">Edit</button>
                                <button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="deleteOffer(${o.offer_id},'${(o.title||'').replace(/'/g,"\\\\'")}')">Delete</button>
                            </td>
                        </tr>`).join('')}
                        ${!(ed.offers && ed.offers.length) ? '<tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-secondary);">No reward offers configured</td></tr>' : ''}
                    </tbody>
                </table>
            </div>
        </div>
        
        </div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}
// Badge & Banner helpers for Engagement page
function showAddBadgeModal() {
    showModal('Add Badge', `<div class="form-group"><label>Name</label><input type="text" id="nb-name" placeholder="e.g. Early Bird"></div><div class="form-group"><label>Description</label><input type="text" id="nb-desc" placeholder="Badge description"></div><div class="form-group"><label>Icon (emoji)</label><input type="text" id="nb-icon" value="🏆" style="font-size:1.5rem;width:80px;text-align:center;"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="nb-save">Add Badge</button>`);
    document.getElementById('nb-save').onclick = async () => { try { const r = await apiPost('engagement.php', {action:'save_badge',name:document.getElementById('nb-name').value,description:document.getElementById('nb-desc').value,icon:document.getElementById('nb-icon').value}); if(r.success){showAlert('Badge added!','success');setTimeout(()=>{closeModal();showAdminView('points');},1000);}else showAlert(r.error||'Failed','error');} catch(e){showAlert('Error','error');} };
}
function deleteBadge(id, name) {
    showConfirm(`Delete badge <strong>${name}</strong>?`, async () => { try { const r = await apiPost('engagement.php', {action:'delete_badge',badge_id:id}); if(r.success){showAlert('Badge deleted!','success');setTimeout(()=>{closeModal();showAdminView('points');},800);}else showAlert('Failed','error');} catch(e){showAlert('Error','error');} });
}
function deleteBannerEngagement(id) {
    showConfirm('Delete this banner?', async () => { try { const r = await apiPost('engagement.php', {action:'delete_banner',id:id}); if(r.success){showAlert('Banner deleted!','success');setTimeout(()=>{closeModal();showAdminView('points');},800);}else showAlert('Failed','error');} catch(e){showAlert('Error','error');} });
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
function showAddOfferModal() {
    showModal('Add Reward Offer', `<div class="form-group"><label>Offer Title</label><input type="text" id="ao-title" placeholder="e.g. Free Consultation"></div><div class="form-group"><label>Points Required</label><input type="number" id="ao-pts" placeholder="500"></div><div class="form-group"><label>Description</label><input type="text" id="ao-desc" placeholder="Offer details..."></div><div class="form-group"><label>Icon (emoji)</label><input type="text" id="ao-icon" value="🎁" style="font-size:1.5rem;width:80px;text-align:center;"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ao-save">Add Offer</button>`);
    document.getElementById('ao-save').onclick = async () => {
        try { const r = await apiPost('engagement.php', {action:'save_offer', title:document.getElementById('ao-title').value, points_required:parseInt(document.getElementById('ao-pts').value), description:document.getElementById('ao-desc').value, icon:document.getElementById('ao-icon').value});
        if(r.success) { showAlert('Offer added!', 'success'); setTimeout(()=>{closeModal();showAdminView('points');},1000); } else showAlert(r.error||'Failed','error'); } catch(e) { showAlert('Error','error'); }
    };
}
function editOffer(id, title, pts, desc, icon) {
    showModal('Edit Reward Offer', `<div class="form-group"><label>Offer Title</label><input type="text" id="eo-title" value="${title}"></div><div class="form-group"><label>Points Required</label><input type="number" id="eo-pts" value="${pts}"></div><div class="form-group"><label>Description</label><input type="text" id="eo-desc" value="${desc}"></div><div class="form-group"><label>Icon (emoji)</label><input type="text" id="eo-icon" value="${icon}" style="font-size:1.5rem;width:80px;text-align:center;"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="eo-save">Save Changes</button>`);
    document.getElementById('eo-save').onclick = async () => {
        try { const r = await apiPost('engagement.php', {action:'save_offer', offer_id:id, title:document.getElementById('eo-title').value, points_required:parseInt(document.getElementById('eo-pts').value), description:document.getElementById('eo-desc').value, icon:document.getElementById('eo-icon').value});
        if(r.success) { showAlert('Offer updated!', 'success'); setTimeout(()=>{closeModal();showAdminView('points');},1000); } else showAlert(r.error||'Failed','error'); } catch(e) { showAlert('Error','error'); }
    };
}
function deleteOffer(id, title) {
    showConfirm(`Delete offer <strong>${title}</strong>?`, async () => { try { const r = await apiPost('engagement.php', {action:'delete_offer', offer_id:id}); if(r.success){showAlert('Offer deleted!','success');setTimeout(()=>{closeModal();showAdminView('points');},800);}else showAlert('Failed','error');} catch(e){showAlert('Error','error');} });
}

// ═══ REPORTS (System Analytics + Behavioral Charts + Export) ═══
async function loadReportsView(main) {
    try {
        const [sd, cd, dd] = await Promise.all([apiGet('reports.php?action=stats'), apiGet('reports.php?action=behavior_categories'), apiGet('reports.php?action=development_status')]);
        const s = sd.stats || {}, categories = cd.categories || [], dev = dd.development_status || {};

        // Detect low-usage warnings
        const warnings = [];
        if (s.ai_activities === 0) warnings.push('⚠️ No AI activities have been generated. The OpenAI recommendation engine may be offline.');
        if (s.voice_samples === 0) warnings.push('⚠️ No voice samples recorded. Speech analysis feature may not be discoverable by parents.');
        if (s.motor_milestones === 0) warnings.push('⚠️ No motor milestones logged. Motor skills tracking may need attention.');
        if (s.total_children > 0 && s.growth_records === 0) warnings.push('⚠️ Children exist but no growth records. Parents may not know how to log measurements.');

        const warningHtml = warnings.length > 0 ? `<div style="margin-bottom:1.5rem;">
            ${warnings.map(w => `<div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(234,88,12,0.05));border:1px solid rgba(245,158,11,0.3);border-radius:12px;padding:.85rem 1.25rem;margin-bottom:.5rem;display:flex;align-items:center;gap:.75rem;font-size:.875rem;color:var(--text-primary);">
                <span style="font-size:1.1rem;flex-shrink:0;">${w.substring(0,2)}</span><span>${w.substring(3)}</span>
            </div>`).join('')}
        </div>` : '';

        const completionColor = s.activity_completion_rate >= 70 ? 'var(--green-500)' : s.activity_completion_rate >= 40 ? 'var(--yellow-500)' : 'var(--red-500)';

        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Reports & Analytics</h1><p class="dashboard-subtitle">System-wide usage analytics, diagnostic alerts & behavioral insights</p></div>
            <div class="header-actions-inline">
                <div class="export-btn-group">
                    <button class="btn btn-outline btn-export" onclick="exportReportPDF()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>PDF</button>
                    <button class="btn btn-outline btn-export" onclick="exportReportExcel()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>Excel</button>
                    <button class="btn btn-outline btn-export" onclick="exportReportCSV()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>CSV</button>
                </div>
            </div></div>

        ${warningHtml}

        <!-- System Analytics Overview -->
        <div class="section-card" style="margin-bottom:1.5rem;"><div class="section-card-header"><h2 class="section-heading">📊 System Usage Analytics</h2><span style="font-size:.75rem;color:var(--text-secondary);background:var(--bg-secondary);padding:4px 10px;border-radius:6px;">Real-time</span></div>
        <div style="padding:1.5rem;">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
                <div style="background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(99,102,241,0.02));border:1px solid rgba(99,102,241,0.15);border-radius:14px;padding:1.25rem;text-align:center;">
                    <div style="font-size:1.75rem;font-weight:800;color:var(--indigo-500);">${fmtNum(s.ai_activities)}</div>
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-top:.25rem;">AI Activities Generated</div>
                    <div style="font-size:.7rem;color:${s.ai_activities === 0 ? 'var(--red-500)' : 'var(--green-500)'};margin-top:.5rem;font-weight:600;">${s.ai_activities === 0 ? '🔴 Inactive' : '🟢 Active'}</div>
                </div>
                <div style="background:linear-gradient(135deg,rgba(13,148,136,0.08),rgba(13,148,136,0.02));border:1px solid rgba(13,148,136,0.15);border-radius:14px;padding:1.25rem;text-align:center;">
                    <div style="font-size:1.75rem;font-weight:800;color:var(--teal-500);">${fmtNum(s.voice_samples)}</div>
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-top:.25rem;">Voice Samples Analyzed</div>
                    <div style="font-size:.7rem;color:${s.voice_samples === 0 ? 'var(--red-500)' : 'var(--green-500)'};margin-top:.5rem;font-weight:600;">${s.voice_samples === 0 ? '🔴 No data' : '🟢 Processing'}</div>
                </div>
                <div style="background:linear-gradient(135deg,rgba(168,85,247,0.08),rgba(168,85,247,0.02));border:1px solid rgba(168,85,247,0.15);border-radius:14px;padding:1.25rem;text-align:center;">
                    <div style="font-size:1.75rem;font-weight:800;color:#8b5cf6;">${fmtNum(s.motor_milestones)}</div>
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-top:.25rem;">Motor Milestones Logged</div>
                    <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.5rem;">${s.motor_achieved} achieved</div>
                </div>
                <div style="background:linear-gradient(135deg,rgba(236,72,153,0.08),rgba(236,72,153,0.02));border:1px solid rgba(236,72,153,0.15);border-radius:14px;padding:1.25rem;text-align:center;">
                    <div style="font-size:1.75rem;font-weight:800;color:#ec4899;">${fmtNum(s.consultations)}</div>
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-top:.25rem;">Consultations</div>
                    <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.5rem;">${s.completed_consultations} completed</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-top:1rem;">
                <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.75rem;">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:18px;height:18px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <div><div style="font-size:1.1rem;font-weight:700;">${fmtNum(s.growth_records)}</div><div style="font-size:.75rem;color:var(--text-secondary);">Growth Records</div><div style="font-size:.65rem;color:var(--indigo-500);">${s.growth_this_month} this month</div></div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.75rem;">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></div>
                    <div><div style="font-size:1.1rem;font-weight:700;">${fmtNum(s.total_appointments)}</div><div style="font-size:.75rem;color:var(--text-secondary);">Appointments</div><div style="font-size:.65rem;color:var(--amber-500);">${s.appointments_this_month} this month</div></div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.75rem;">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:18px;height:18px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                    <div><div style="font-size:1.1rem;font-weight:700;">${fmtNum(s.article_reads)}</div><div style="font-size:.75rem;color:var(--text-secondary);">Article Reads</div></div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.75rem;">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                    <div><div style="font-size:1.1rem;font-weight:700;">${fmtNum(s.community_messages)}</div><div style="font-size:.75rem;color:var(--text-secondary);">Community Posts</div></div>
                </div>
            </div>

            <!-- Activity Completion Rate -->
            <div style="margin-top:1.25rem;padding:1rem 1.25rem;background:var(--bg-secondary);border-radius:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                    <span style="font-weight:600;font-size:.875rem;">Activity Completion Rate</span>
                    <span style="font-weight:700;color:${completionColor};">${s.activity_completion_rate}%</span>
                </div>
                <div style="background:var(--bg-primary);border-radius:8px;height:12px;overflow:hidden;">
                    <div style="height:100%;width:${s.activity_completion_rate}%;background:${completionColor};border-radius:8px;transition:width .6s ease;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:.5rem;font-size:.75rem;color:var(--text-secondary);">
                    <span>${fmtNum(s.completed_activities)} completed</span>
                    <span>${fmtNum(s.total_activities)} total activities</span>
                </div>
            </div>
        </div></div>

        <!-- Child Health Overview -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${s.on_track_rate}%</div><div class="admin-stat-label">Children On Track</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.flagged_children)}</div><div class="admin-stat-label">Flagged Children</div><div class="admin-stat-trend ${s.flagged_children > 0 ? 'trend-down' : 'trend-up'}">${s.flagged_children > 0 ? '⚠ Needs review' : '✓ All clear'}</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.total_children)}</div><div class="admin-stat-label">Total Children</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.growth_records)}</div><div class="admin-stat-label">Growth Records</div></div></div>
        </div>

        <!-- Parent Engagement & AI Adoption -->
        <div class="overview-grid" style="margin-top:1.5rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">🧠 AI Feature Adoption</h2></div>
            <div style="padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <div><div style="font-weight:700;font-size:1.1rem;color:var(--text-primary);">${s.ai_activities || 0}</div><div style="font-size:.75rem;color:var(--text-secondary);">Total Recommendations generated</div></div>
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🤖</div>
                </div>
                <div style="margin-bottom:.5rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;font-size:.75rem;font-weight:600;"><span style="color:var(--indigo-500);">Activity Completion Rate</span><span>${s.activity_completion_rate || 0}%</span></div>
                    <div style="background:var(--bg-primary);border-radius:6px;height:8px;overflow:hidden;"><div style="height:100%;width:${s.activity_completion_rate || 0}%;background:var(--indigo-500);border-radius:6px;"></div></div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;font-size:.75rem;font-weight:600;"><span style="color:var(--teal-500);">Voice Samples Ratio</span><span>${s.voice_samples > 0 && s.total_children > 0 ? Math.round((s.voice_samples / s.total_children) * 100) : 0}%</span></div>
                    <div style="background:var(--bg-primary);border-radius:6px;height:8px;overflow:hidden;"><div style="height:100%;width:${s.voice_samples > 0 && s.total_children > 0 ? Math.min(100, Math.round((s.voice_samples / s.total_children) * 100)) : 0}%;background:var(--teal-500);border-radius:6px;"></div></div>
                </div>
            </div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">📈 Development Outcomes</h2></div>
            <div style="padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <div><div style="font-weight:700;font-size:1.1rem;color:var(--text-primary);">${fmtNum((parseInt(s.growth_records)||0) + (parseInt(s.motor_milestones)||0))}</div><div style="font-size:.75rem;color:var(--text-secondary);">Total Dev Milestones Logged</div></div>
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎯</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div style="background:linear-gradient(135deg,rgba(168,85,247,0.1),rgba(168,85,247,0.02));padding:.75rem;border-radius:10px;text-align:center;border:1px solid rgba(168,85,247,0.1);">
                        <div style="font-size:1.25rem;font-weight:800;color:var(--purple-500);">${s.motor_achieved || 0}</div>
                        <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.25rem;">Motor Skills Reached</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.02));padding:.75rem;border-radius:10px;text-align:center;border:1px solid rgba(245,158,11,0.1);">
                        <div style="font-size:1.25rem;font-weight:800;color:var(--yellow-500);">${s.on_track_rate || 0}%</div>
                        <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.25rem;">Overall On-Track Rate</div>
                    </div>
                </div>
            </div></div>
        </div>

        <!-- Global Platform Health & Development Status -->
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Platform Health & Development Status</h2></div>
            <div style="padding:1.5rem;">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">
                    <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),transparent);border:1px solid rgba(16,185,129,0.2);border-radius:16px;padding:1.25rem;text-align:center;position:relative;overflow:hidden;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                        <div style="position:absolute;top:-10px;right:-10px;font-size:60px;opacity:.05;">✅</div>
                        <div style="font-size:2rem;font-weight:800;color:var(--green-500);">${dev?.on_track?.percentage || 0}%</div>
                        <div style="font-size:.9rem;font-weight:700;margin:.5rem 0 .25rem;">On Track</div>
                        <div style="font-size:.75rem;color:var(--text-secondary);">${dev?.on_track?.count || 0} children are progressing normally according to system analytics.</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),transparent);border:1px solid rgba(245,158,11,0.2);border-radius:16px;padding:1.25rem;text-align:center;position:relative;overflow:hidden;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                        <div style="position:absolute;top:-10px;right:-10px;font-size:60px;opacity:.05;">⚠️</div>
                        <div style="font-size:2rem;font-weight:800;color:var(--yellow-500);">${dev?.needs_review?.percentage || 0}%</div>
                        <div style="font-size:.9rem;font-weight:700;margin:.5rem 0 .25rem;">Needs Review</div>
                        <div style="font-size:.75rem;color:var(--text-secondary);">${dev?.needs_review?.count || 0} children show irregular patterns or missing milestones.</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(239,68,68,0.1),transparent);border:1px solid rgba(239,68,68,0.2);border-radius:16px;padding:1.25rem;text-align:center;position:relative;overflow:hidden;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                        <div style="position:absolute;top:-10px;right:-10px;font-size:60px;opacity:.05;">🚩</div>
                        <div style="font-size:2rem;font-weight:800;color:var(--red-500);">${dev?.needs_attention?.percentage || 0}%</div>
                        <div style="font-size:.9rem;font-weight:700;margin:.5rem 0 .25rem;">Needs Attention</div>
                        <div style="font-size:.75rem;color:var(--text-secondary);">${dev?.needs_attention?.count || 0} children have low engagement scores and require follow-up.</div>
                    </div>
                </div>
            </div>
        </div></div>`;
        // (Behavioral functionality has been relocated to modern modules)
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
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
        
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || localStorage.getItem('theme') === 'dark';

        const fNameEnc = (profile?.first_name || '').replace(/"/g, '&quot;').replace(/'/g, "\\'");
        const lNameEnc = (profile?.last_name || '').replace(/"/g, '&quot;').replace(/'/g, "\\'");

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
                        <button class="btn btn-outline" onclick="showEditAdminProfile('${fNameEnc}', '${lNameEnc}')">
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
                    <div class="settings-item">
                        <div class="settings-item-info">
                            <div class="settings-item-label">Language</div>
                            <div class="settings-item-description">Choose your preferred language</div>
                        </div>
                        <select class="settings-select" onchange="updateConfig('language',this.value); if(this.value==='ar'){document.body.classList.add('rtl');}else{document.body.classList.remove('rtl');} toggleLanguage(this.value)" style="width:auto;min-width:120px;">
                            <option value="en" ${(config.language || 'en') === 'en' ? 'selected' : ''}>English</option>
                            <option value="ar" ${config.language === 'ar' ? 'selected' : ''}>العربية</option>
                        </select>
                    </div>
                    <div class="settings-item">
                        <div class="settings-item-info">
                            <div class="settings-item-label">Dark Mode</div>
                            <div class="settings-item-description">Switch to a darker, eye-friendly theme</div>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" id="admin-dark-mode-toggle" ${isDark?'checked':''} onchange="toggleTheme()"><span class="toggle-slider"></span></label>
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
    try {
        const res = await apiPost('settings.php', { action: 'update_notifications', key: key, value: checked ? '1' : '0' });
        if (res.success) showAlert('Notification setting updated', 'success');
        else showAlert(res.error || 'Error saving setting', 'error');
    } catch(e) { showAlert('Error saving setting', 'error'); }
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

async function updateConfig(key, value) {
    try {
        const res = await apiPost('settings.php', { action: 'update_config', setting_key: key, setting_value: value });
        if (res.success) showAlert(res.message || 'Setting updated', 'success');
        else showAlert(res.error || 'Error saving setting', 'error');
    } catch (e) { showAlert('Error updating setting: ' + e.message, 'error'); }
}
function purgeInactiveUsers() {
    showConfirm('This will <strong>permanently delete</strong> all inactive parent accounts older than 6 months. This cannot be undone.', async () => {
        try {
            const res = await apiPost('settings.php', { action: 'purge_inactive' });
            if (res.success) showAlert(res.message || 'Done', 'success');
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}
function resetPointsSystem() {
    showConfirm('This will <strong>reset ALL points wallets to 0</strong>. This action cannot be undone.', async () => {
        try {
            const res = await apiPost('settings.php', { action: 'reset_points' });
            if (res.success) showAlert(res.message || 'Done', 'success');
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    }, 'error');
}
function handleLogout() { showConfirm('Are you sure you want to log out?', () => { window.location.href = 'logout.php'; }, 'info'); }
