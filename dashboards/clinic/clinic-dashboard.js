// ─────────────────────────────────────────────────────────────
//  Clinic Dashboard – View Controller
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    try {
        console.log("Clinic Dashboard v2.0 - Add Specialist Feature Ready");
        initClinicNav();
        showClinicView('specialists'); // default view
    } catch (e) {
        document.body.innerHTML = '<div style="color:red;font-size:20px;padding:50px;">JS Error: ' + e.message + '<br>' + e.stack + '</div>';
    }
});

function showClinicAlert(title, message) {
    const alertOverlay = document.createElement('div');
    alertOverlay.className = 'clinic-modal-overlay active';
    alertOverlay.style.zIndex = '1100'; // Above other modals
    
    alertOverlay.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 400px; padding: 2rem; text-align: center; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div style="width: 60px; height: 60px; background: rgba(13, 148, 136, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #0d9488;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h3 style="margin: 0 0 0.5rem; color: var(--text-primary); font-size: 1.25rem;">${title}</h3>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">${message}</p>
            <button class="btn btn-gradient" style="width: 100%;" onclick="this.closest('.clinic-modal-overlay').remove()">Dismiss</button>
        </div>
    `;
    
    document.body.appendChild(alertOverlay);
}

function showClinicConfirm(title, message, onConfirm) {
    const confirmOverlay = document.createElement('div');
    confirmOverlay.className = 'clinic-modal-overlay active';
    confirmOverlay.style.zIndex = '1200';
    
    confirmOverlay.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 400px; padding: 2rem; text-align: center; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #ef4444;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </div>
            <h3 style="margin: 0 0 0.5rem; color: var(--text-primary); font-size: 1.25rem;">${title}</h3>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">${message}</p>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-outline" style="flex: 1;" onclick="this.closest('.clinic-modal-overlay').remove()">Cancel</button>
                <button id="clinic-confirm-btn" class="btn btn-gradient" style="flex: 1; background: linear-gradient(135deg, #ef4444, #dc2626);">Delete</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmOverlay);
    document.getElementById('clinic-confirm-btn').onclick = () => {
        confirmOverlay.remove();
        onConfirm();
    };
}

// Ensure the animation exists in CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes modalPop {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
`;
document.head.appendChild(style);

let clinicSpecialists = []; // Cache for searching
let clinicAppointments = []; // Cache for appointments
let clinicPatients = []; // Cache for patients
let currentCalendarMonth = new Date().getMonth();
let currentCalendarYear = new Date().getFullYear();

function initClinicNav() {
    renderClinicTopBar();
    
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const footerItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                navItems.forEach(n => n.classList.remove('active'));
                footerItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                showClinicView(view);
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
                showClinicView(view);
            }
        });
    });
}

function renderClinicTopBar() {
    const main = document.getElementById('clinic-main-content');
    if (!main || document.getElementById('dashboard-topbar')) return;

    const initial = window.clinicData && window.clinicData.clinic && window.clinicData.clinic.clinic_name ? window.clinicData.clinic.clinic_name[0].toUpperCase() : 'C';
    const profileImg = window.clinicData && window.clinicData.clinic && window.clinicData.clinic.profile_image ? window.clinicData.clinic.profile_image : null;

    const topbar = document.createElement('div');
    topbar.id = 'dashboard-topbar';
    topbar.style.cssText = `
        display: flex; justify-content: flex-end; align-items: center;
        padding: 1rem 2rem;
        background: transparent;
        position: sticky; top: 0; z-index: 100; width: 100%;
    `;

    topbar.innerHTML = `
        <div style="display:flex; align-items:center; gap:1.5rem;">
            <div id="topbar-notification" onclick="toggleClinicNotifDropdown()" style="position:relative; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="2" width="22" height="22" onmouseover="this.style.stroke='var(--text-primary)'" onmouseout="this.style.stroke='var(--text-secondary)'">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span id="topbar-notif-badge" style="display:none; position:absolute; top:-2px; right:-2px; width:8px; height:8px; background:#ef4444; border-radius:50%; border:2px solid var(--bg-card);"></span>
            </div>
            <div class="topbar-notification-dropdown" id="clinic-notif-dropdown" style="display:none; position:absolute; top:55px; right:2rem; width:380px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.08); overflow:hidden; z-index:1000; backdrop-filter:blur(20px);">
                <div style="padding:1.25rem 1.5rem; background:linear-gradient(135deg, #0d9488, #0891b2); display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <h4 style="margin:0; font-size:1rem; font-weight:700; color:#fff;">Notifications</h4>
                        <span id="topbar-notif-count" style="display:none; background:rgba(255,255,255,0.25); color:#fff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px;"></span>
                    </div>
                    <div style="display:flex; gap:0.75rem;">
                        <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="loadClinicNotifications()">Refresh</span>
                        <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); font-weight:600; cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="markAllClinicNotifRead()">Mark all read</span>
                    </div>
                </div>
                <div id="clinic-notif-content" style="max-height:420px; overflow-y:auto;">
                    <div style="padding:3rem 1.5rem; text-align:center;">
                        <div style="width:48px; height:48px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </div>
                        <p style="color:var(--text-secondary); font-size:0.85rem; margin:0;">Loading notifications...</p>
                    </div>
                </div>
            </div>
            <div id="topbar-avatar" onclick="showClinicView('settings')" style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, #0d9488, #0891b2); color:white; display:flex; align-items:center; justify-content:center; font-weight:600; cursor:pointer; font-size:0.9rem; overflow:hidden;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                ${profileImg ? `<img src="../../${profileImg}" style="width:100%; height:100%; object-fit:cover;">` : initial}
            </div>
        </div>
    `;
    
    const wrapper = main.parentElement;
    wrapper.insertBefore(topbar, main);
    main.style.flex = '1';
    main.style.overflowY = 'auto';

    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('clinic-notif-dropdown');
        const trigger = document.getElementById('topbar-notification');
        if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

async function toggleClinicNotifDropdown() {
    const dropdown = document.getElementById('clinic-notif-dropdown');
    if (!dropdown) {
        console.error('❌ Dropdown element not found!');
        return;
    }

    const isVisible = dropdown.style.display !== 'none';
    console.log('🔔 toggleClinicNotifDropdown called, isVisible:', isVisible);

    if (!isVisible) {
        // Fetch notifications when opening
        console.log('🔔 Opening dropdown, fetching notifications...');
        await loadClinicNotifications();
        dropdown.style.display = 'block';
        console.log('🔔 Dropdown displayed');
    } else {
        console.log('🔔 Closing dropdown');
        dropdown.style.display = 'none';
    }
}

// Load notifications from clinicData (bundled with dashboard API)
async function loadClinicNotifications() {
    const contentDiv = document.getElementById('clinic-notif-content');
    if (!contentDiv) return;

    try {
        let notifications = [];
        let unreadCount = 0;

        // Use data already loaded from api_get_clinic_data.php
        if (window.clinicData && window.clinicData.notifications) {
            notifications = window.clinicData.notifications;
            unreadCount = window.clinicData.unread_count || 0;
        } else {
            // Fallback: re-fetch from clinic data API
            const res = await fetch('../../api_get_clinic_data.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.notifications) {
                notifications = data.notifications;
                unreadCount = data.unread_count || 0;
            }
        }

        console.log('🔔 Notifications loaded:', notifications.length, 'unread:', unreadCount);

        // Update badges
        const badge = document.getElementById('topbar-notif-badge');
        if (badge) {
            badge.style.display = unreadCount > 0 ? 'block' : 'none';
        }
        const countBadge = document.getElementById('topbar-notif-count');
        if (countBadge) {
            if (unreadCount > 0) {
                countBadge.textContent = unreadCount + ' new';
                countBadge.style.display = 'inline-block';
            } else {
                countBadge.style.display = 'none';
            }
        }

        if (notifications.length === 0) {
            contentDiv.style.cssText = 'padding:2.5rem 1.5rem; text-align:center;';
            contentDiv.innerHTML = `
                <div style="width:48px; height:48px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <p style="color:var(--text-secondary); font-size:0.85rem; margin:0;">No new notifications</p>
            `;
        } else {
            contentDiv.style.cssText = 'max-height:400px; overflow-y:auto; padding:0;';

            const getIcon = (type) => {
                const icons = {
                    'appointment_reminder': { bg: 'rgba(13,148,136,0.12)', color: '#0d9488', path: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
                    'payment_success': { bg: 'rgba(34,197,94,0.12)', color: '#16a34a', path: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
                    'growth_alert': { bg: 'rgba(251,191,36,0.12)', color: '#d97706', path: '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>' },
                    'milestone': { bg: 'rgba(168,85,247,0.12)', color: '#7c3aed', path: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>' },
                    'system': { bg: 'rgba(59,130,246,0.12)', color: '#2563eb', path: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' }
                };
                const i = icons[type] || icons['system'];
                return `<div style="width:38px;height:38px;border-radius:12px;background:${i.bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${i.color}" stroke-width="2">${i.path}</svg></div>`;
            };

            const timeAgo = (dateStr) => {
                const now = new Date();
                const d = new Date(dateStr);
                const diff = Math.floor((now - d) / 1000);
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff/60) + 'm ago';
                if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
                if (diff < 604800) return Math.floor(diff/86400) + 'd ago';
                return d.toLocaleDateString([], {month:'short', day:'numeric'});
            };

            let html = notifications.map(n => `
                <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border-color); display:flex; gap:0.875rem; align-items:flex-start; transition:background 0.15s ease; cursor:pointer; ${n.is_read == 0 ? 'background:rgba(13,148,136,0.04);' : ''}" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='${n.is_read == 0 ? 'rgba(13,148,136,0.04)' : 'transparent'}'">
                    ${getIcon(n.type)}
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                            <span style="font-weight:600; font-size:0.9rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${n.title || 'Notification'}</span>
                            <span style="font-size:0.7rem; color:var(--text-muted); white-space:nowrap;">${timeAgo(n.created_at)}</span>
                        </div>
                        <p style="margin:0; font-size:0.82rem; color:var(--text-secondary); line-height:1.4;">${n.message || ''}</p>
                        ${n.is_read == 0 ? '<div style="width:6px;height:6px;background:#0d9488;border-radius:50%;position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);"></div>' : ''}
                    </div>
                </div>
            `).join('');

            html += `
                <div style="padding:1rem 1.5rem; text-align:center; background:var(--bg-card); border-top:1px solid var(--border-color);">
                    <span style="font-size:0.85rem; font-weight:600; color:#0d9488; cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'" onclick="openAllNotificationsModal()">View all notifications →</span>
                </div>
            `;

            contentDiv.innerHTML = html;
        }
    } catch (err) {
        console.error('Failed to load notifications:', err);
        contentDiv.innerHTML = `
            <div style="padding:2rem 1.5rem; text-align:center;">
                <p style="color:#ef4444; font-size:0.85rem; margin:0;">Failed to load notifications</p>
                <p style="color:var(--text-secondary); font-size:0.75rem; margin-top:0.5rem;">${err.message}</p>
            </div>
        `;
    }
}

// Mark single notification as read
async function markClinicNotifRead(notificationId, btnElement) {
    try {
        const res = await fetch('../../api_notifications.php?action=read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        });
        const data = await res.json();
        if (data.success && btnElement) {
            // Remove the "Mark as read" link
            btnElement.parentElement.remove();
            // Remove background highlight
            const notifEl = btnElement.closest('[data-notif-id]');
            if (notifEl) notifEl.style.background = 'transparent';
            // Update badge count
            const badge = document.getElementById('topbar-notif-badge');
            if (badge) {
                const current = parseInt(badge.textContent) || 1;
                if (current > 1) {
                    badge.textContent = current - 1;
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (err) {
        console.error('Failed to mark notification as read:', err);
    }
}

// Mark all notifications as read
async function markAllClinicNotifRead() {
    try {
        const clinicId = window.clinicSessionId || (window.clinicData && window.clinicData.clinic ? window.clinicData.clinic.clinic_id : null);
        let url = '../../api_notifications.php?action=read';
        if (clinicId) url += '&clinic_id=' + clinicId;
        
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        
        // Also update local data
        if (window.clinicData && window.clinicData.notifications) {
            window.clinicData.notifications.forEach(n => n.is_read = 1);
            window.clinicData.unread_count = 0;
        }
        
        // Hide badge
        const badge = document.getElementById('topbar-notif-badge');
        if (badge) badge.style.display = 'none';
        const countBadge = document.getElementById('topbar-notif-count');
        if (countBadge) countBadge.style.display = 'none';
        
        // Reload
        await loadClinicNotifications();
    } catch (err) {
        console.error('Failed to mark all notifications as read:', err);
    }
}

// Open full notifications modal
function openAllNotificationsModal() {
    // Close dropdown
    const dropdown = document.getElementById('clinic-notif-dropdown');
    if (dropdown) dropdown.style.display = 'none';

    const notifications = (window.clinicData && window.clinicData.notifications) ? window.clinicData.notifications : [];

    const getIcon = (type) => {
        const icons = {
            'appointment_reminder': { bg: 'rgba(13,148,136,0.12)', color: '#0d9488', path: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
            'payment_success': { bg: 'rgba(34,197,94,0.12)', color: '#16a34a', path: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
            'growth_alert': { bg: 'rgba(251,191,36,0.12)', color: '#d97706', path: '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>' },
            'milestone': { bg: 'rgba(168,85,247,0.12)', color: '#7c3aed', path: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>' },
            'system': { bg: 'rgba(59,130,246,0.12)', color: '#2563eb', path: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' }
        };
        const i = icons[type] || icons['system'];
        return `<div style="width:44px;height:44px;border-radius:14px;background:${i.bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="${i.color}" stroke-width="2">${i.path}</svg></div>`;
    };

    const timeAgo = (dateStr) => {
        const now = new Date();
        const d = new Date(dateStr);
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff/60) + ' min ago';
        if (diff < 86400) return Math.floor(diff/3600) + ' hours ago';
        if (diff < 604800) return Math.floor(diff/86400) + ' days ago';
        return d.toLocaleDateString([], {month:'long', day:'numeric', year:'numeric'});
    };

    const notifRows = notifications.length > 0 ? notifications.map(n => `
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; gap:1rem; align-items:flex-start; transition:background 0.15s; cursor:pointer; position:relative; ${n.is_read == 0 ? 'background:rgba(13,148,136,0.04);' : ''}" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='${n.is_read == 0 ? 'rgba(13,148,136,0.04)' : 'transparent'}'">
            ${getIcon(n.type)}
            <div style="flex:1;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                    <span style="font-weight:600; font-size:1rem; color:var(--text-primary);">${n.title || 'Notification'}</span>
                    <span style="font-size:0.78rem; color:var(--text-muted);">${timeAgo(n.created_at)}</span>
                </div>
                <p style="margin:0; font-size:0.9rem; color:var(--text-secondary); line-height:1.5;">${n.message || ''}</p>
                <span style="display:inline-block; margin-top:0.5rem; font-size:0.75rem; padding:2px 10px; border-radius:20px; background:${n.type === 'appointment_reminder' ? 'rgba(13,148,136,0.1)' : n.type === 'payment_success' ? 'rgba(34,197,94,0.1)' : 'rgba(59,130,246,0.1)'}; color:${n.type === 'appointment_reminder' ? '#0d9488' : n.type === 'payment_success' ? '#16a34a' : '#2563eb'}; text-transform:capitalize;">${(n.type || 'system').replace(/_/g, ' ')}</span>
            </div>
            ${n.is_read == 0 ? '<div style="width:8px;height:8px;background:#0d9488;border-radius:50%;position:absolute;right:1.5rem;top:1.5rem;"></div>' : ''}
        </div>
    `).join('') : `
        <div style="padding:4rem 2rem; text-align:center;">
            <div style="width:64px; height:64px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <h3 style="margin:0 0 0.5rem; color:var(--text-primary); font-size:1.1rem;">All caught up!</h3>
            <p style="margin:0; color:var(--text-secondary); font-size:0.9rem;">No notifications to display.</p>
        </div>
    `;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width:650px; width:95%; max-height:85vh; display:flex; flex-direction:column;">
            <div class="clinic-modal-header" style="background:linear-gradient(135deg, #0d9488, #0891b2); padding:1.5rem 2rem;">
                <div>
                    <h2 style="margin:0; font-size:1.3rem; color:#fff;">All Notifications</h2>
                    <p style="margin:0.25rem 0 0; font-size:0.85rem; color:rgba(255,255,255,0.75);">${notifications.length} notification${notifications.length !== 1 ? 's' : ''}</p>
                </div>
                <button class="clinic-modal-close" onclick="this.closest('.clinic-modal-overlay').remove()" style="color:#fff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="flex:1; overflow-y:auto;">
                ${notifRows}
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showClinicView(viewId, dataId = null) {
    const main = document.getElementById('clinic-main-content');
    if (!main) return;

    const views = {
        'specialists': getSpecialistsView,
        'appointments': getAppointmentsView,
        'patients': getPatientsView,
        'revenue': getRevenueView,
        'reviews': getReviewsView,
        'settings': getSettingsView,
        'specialist-details': () => getSpecialistDetailsView(dataId)
    };

    const fn = views[viewId];
    if (fn) {
        main.innerHTML = fn();
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
        
        // Load actual data from API after rendering the skeleton
        if (viewId === 'specialist-details') {
            renderSpecialistDetailsPage(dataId);
        } else {
            refreshClinicData(viewId);
        }
    }
}

async function refreshClinicData(viewId) {
    try {
        const response = await fetch('../../api_get_clinic_data.php');
        const data = await response.json();
        
        if (data.success) {
            console.log("Clinic Data Loaded:", data);
            clinicSpecialists = data.specialists || [];
            clinicPatients = data.patients || [];
            window.clinicData = data;
            
            const s = data.stats || {};
            const c = data.clinic || {};
            
            // Update Clinic Name in Sidebar and Topbar
            if (c.clinic_name) {
                updateSidebarProfile(c.clinic_name);
            }

            // Update ALL stat cards by ID (works across all views)
            const reviewStats = data.review_stats || {};
            const revenue = s.revenue || 0;
            const pendingRev = s.pending_revenue || 0;
            const txCount = s.transaction_count || 0;

            const statUpdates = {
                'stat-active-specialists': clinicSpecialists.length,
                'stat-total-appointments': s.total_appointments || 0,
                'stat-today-appointments': s.today_appointments || 0,
                'stat-completed': s.completed_appointments || 0,
                'stat-accepted': s.accepted_appointments || 0,
                'stat-cancelled': s.cancelled_appointments || 0,
                'stat-pending': s.pending_appointments || 0,
                'stat-revenue-month': revenue > 0 ? '$' + revenue.toLocaleString() : '$0',
                'stat-monthly-revenue': revenue > 0 ? '$' + revenue.toLocaleString() : '$0',
                'stat-cash-total': 'Cash: $' + (s.cash_revenue || 0).toLocaleString(),
                'stat-credit-total': 'Credit: $' + (s.credit_revenue || 0).toLocaleString(),
                'stat-sessions-booked': txCount,
                'stat-active-patients': clinicPatients.length,
                'stat-pending-revenue': pendingRev > 0 ? '$' + pendingRev.toLocaleString() : '$0',
                'stat-overall-rating': s.avg_rating || '0.0',
                'stat-avg-rating': s.avg_rating || '0.0',
                'stat-total-reviews': reviewStats.count || 0
            };
            
            for (const [id, val] of Object.entries(statUpdates)) {
                const el = document.getElementById(id);
                if (el) el.innerText = val;
            }

            // View-specific Rendering
            if (viewId === 'specialists') renderSpecialistsTable({ specialists: clinicSpecialists });
            if (viewId === 'patients') renderPatientsTable(clinicPatients);
            if (viewId === 'appointments') {
                fetchAppointments();
                // Populate the specialist filter dropdown in Appointments view
                const specFilter = document.getElementById('apt-spec-filter');
                if (specFilter) {
                    // Keep "All Specialists" and add the rest
                    specFilter.innerHTML = '<option value="">All Specialists</option>';
                    clinicSpecialists.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.specialist_id;
                        opt.textContent = `Dr. ${s.first_name} ${s.last_name}`;
                        specFilter.appendChild(opt);
                    });
                }
            }
            if (viewId === 'reviews') renderReviewsData(data);
            if (viewId === 'revenue') {
                renderRevenueChart(data.revenue_chart || []);
                renderRevenueBreakdown(data.revenue_breakdown || []);
            }
            if (viewId === 'settings') renderSettingsData();
        }
    } catch (err) {
        console.error("Error fetching clinic data:", err);
    }
}

function renderRevenueChart(chartData) {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (window.myRevenueChart) {
        window.myRevenueChart.destroy();
    }

    // If no revenue data at all, show empty state
    if (!chartData || chartData.length === 0) {
        ctx.parentElement.innerHTML = `
            <div style="height:350px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;">
                <div style="width:64px;height:64px;background:#f8fafc;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;border:1px solid #e2e8f0;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </div>
                <div style="font-size:1rem;font-weight:700;color:#64748b;margin-bottom:0.375rem;">No Revenue Data</div>
                <div style="font-size:0.85rem;color:#94a3b8;">The chart will populate once payments are recorded.</div>
            </div>`;
        return;
    }

    const labels = chartData.map(d => {
        const date = new Date(d.day);
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    });
    const cashData = chartData.map(d => parseFloat(d.cash) || 0);
    const creditData = chartData.map(d => parseFloat(d.credit) || 0);

    window.myRevenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Cash Revenue',
                    data: cashData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981'
                },
                {
                    label: 'Credit Revenue',
                    data: creditData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: '#f1f5f9'
                    },
                    ticks: { 
                        callback: (value) => '$' + value,
                        padding: 10,
                        font: { weight: '600' }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        padding: 10,
                        font: { weight: '600' }
                    }
                }
            },
            plugins: {
                legend: { 
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12, weight: '700' }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 14, weight: '700' },
                    bodyFont: { size: 13 },
                    cornerRadius: 12,
                    displayColors: true
                }
            }
        }
    });
}

function renderRevenueBreakdown(logs) {
    const container = document.getElementById('revenue-breakdown-container');
    if (!container) return;

    if (!logs || logs.length === 0) {
        container.innerHTML = `
            <div style="text-align:center; padding:4rem 2rem; color:#94a3b8;">
                <div style="width:72px;height:72px;background:#f8fafc;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;border:1px solid #e2e8f0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:#64748b;margin-bottom:0.5rem;">No Transactions Yet</div>
                <div style="font-size:0.9rem;color:#94a3b8;">Revenue will appear here once payments are received from patients.</div>
            </div>`;
        return;
    }

    let html = `
        <div class="records-table-wrap" style="border:none; border-radius:16px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="padding:1.25rem 1.5rem; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase;">Date</th>
                        <th style="padding:1.25rem 1.5rem; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase;">Specialist</th>
                        <th style="padding:1.25rem 1.5rem; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase;">Method</th>
                        <th style="padding:1.25rem 1.5rem; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase;">Status</th>
                        <th style="padding:1.25rem 1.5rem; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody style="background:white;">
    `;

    logs.forEach(log => {
        const dt = new Date(log.date);
        const dateStr = dt.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
        
        const method = (log.method || 'Cash').toLowerCase();
        const isCash = method.includes('cash');
        const methodTag = isCash ? 
            `<span style="background:#f0fdf4; color:#16a34a; padding:4px 10px; border-radius:99px; font-size:0.75rem; font-weight:600;">Cash</span>` :
            `<span style="background:#eff6ff; color:#2563eb; padding:4px 10px; border-radius:99px; font-size:0.75rem; font-weight:600;">Credit Card</span>`;

        const status = (log.status || 'Pending').toLowerCase();
        const sClass = status === 'completed' ? 'status-active' : 'status-warning';

        html += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:1.25rem 1.5rem;">
                    <div style="font-weight:600; color:#1e293b;">${dateStr}</div>
                    <div style="font-size:0.75rem; color:#64748b;">${timeStr}</div>
                </td>
                <td style="padding:1.25rem 1.5rem;">
                    <div style="font-weight:500; color:#475569;">Dr. ${log.spec_fname} ${log.spec_lname}</div>
                </td>
                <td style="padding:1.25rem 1.5rem;">${methodTag}</td>
                <td style="padding:1.25rem 1.5rem;"><span class="status-badge ${sClass}">${log.status}</span></td>
                <td style="padding:1.25rem 1.5rem; text-align:right; font-weight:700; color:#0f172a;">$${parseFloat(log.amount || 0).toLocaleString()}</td>
            </tr>
        `;
    });

    html += `</tbody></table></div>`;
    container.innerHTML = html;
}

function renderRevenueData(stats) {
    const container = document.getElementById('revenue-breakdown');
    if (!container) return;
    const completed = stats.completed_appointments || 0;
    const pending = stats.pending_appointments || 0;
    const revenue = stats.revenue || 0;
    container.innerHTML = `
        <div class="revenue-row"><span class="revenue-plan">Completed Appointments</span><span class="revenue-count">${completed} sessions</span><span class="revenue-amount">$${(completed * 50).toLocaleString()}</span></div>
        <div class="revenue-row"><span class="revenue-plan">Pending Appointments</span><span class="revenue-count">${pending} sessions</span><span class="revenue-amount">$${(pending * 50).toLocaleString()}</span></div>
        <div class="revenue-row revenue-total"><span class="revenue-plan">Total Estimated Revenue</span><span></span><span class="revenue-amount">$${revenue.toLocaleString()}</span></div>
    `;
}

function renderReviewsData(data) {
    const container = document.querySelector('.reviews-list');
    if (!container) return;

    if (!data.reviews || data.reviews.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text-secondary);">No reviews found.</div>';
        return;
    }

    const gradients = [
        'linear-gradient(135deg, #0d9488, #2dd4bf)',
        'linear-gradient(135deg, #06b6d4, #0891b2)',
        'linear-gradient(135deg, #8b5cf6, #7c3aed)'
    ];

    container.innerHTML = data.reviews.map((r, i) => {
        const parentName = (r.parent_fname + ' ' + (r.parent_lname || '')).trim() || 'Parent';
        const initial = parentName[0].toUpperCase() + (r.parent_lname ? r.parent_lname[0].toUpperCase() : '');
        const drName = (r.spec_fname + ' ' + (r.spec_lname || '')).trim();
        const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
        const dt = new Date(r.submitted_at).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric'});
        const bg = gradients[i % gradients.length];
        
        return `
            <div class="review-card">
                <div class="review-header">
                    <div class="table-user">
                        <div class="patient-avatar" style="background: ${bg};">${initial}</div>
                        <div>
                            <div class="patient-name">${parentName}</div>
                            <div class="patient-details">Nov 20, 2025</div>
                        </div>
                    </div>
                    <div class="review-stars">${stars}</div>
                </div>
                <p class="review-text">${r.content}</p>
                <div class="review-specialist">About: Dr. ${drName}</div>
            </div>`;
    }).join('');
}

function updateSidebarProfile(name) {
    const nameEl = document.querySelector('.user-info .user-name');
    const subtitle = document.querySelector('.dashboard-subtitle');
    const avatar = document.getElementById('topbar-avatar');
    
    if (nameEl) nameEl.textContent = name;
    if (subtitle) subtitle.textContent = `Healthcare Management - ${name}`;
    
    if (avatar && name) {
        const c = window.clinicData && window.clinicData.clinic ? window.clinicData.clinic : {};
        if (c.profile_image) {
            avatar.innerHTML = `<img src="../../${c.profile_image}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
            avatar.style.background = 'transparent';
        } else {
            avatar.textContent = name[0].toUpperCase();
            avatar.style.background = 'linear-gradient(135deg, #0d9488, #0891b2)';
        }
    }
}

async function fetchAppointments() {
    try {
        const response = await fetch('../../api_get_clinic_appointments.php');
        const data = await response.json();
        if (!data.error) {
            clinicAppointments = data;
            renderAppointmentsList(data);
        } else {
            console.error("Error fetching appointments:", data.error);
            renderAppointmentsList([]);
        }
    } catch (err) {
        console.error("Failed to fetch appointments", err);
    }
}

function filterAppointmentsBySpec(specId) {
    if (!specId) {
        renderAppointmentsList(clinicAppointments);
        return;
    }
    const filtered = clinicAppointments.filter(a => a.specialist_id == specId);
    renderAppointmentsList(filtered);
}

function renderAppointmentsList(appointments) {
    const container = document.getElementById('appointments-view-container');
    if (!container) return;

    if (appointments.length === 0) {
        container.innerHTML = '<div style="padding: 60px 40px; text-align: center; color: #64748b; font-weight: 500; display: flex; flex-direction: column; align-items: center; gap: 1rem;">' +
            '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3;"><path d="M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><polyline points="16 2 16 6"/><polyline points="8 2 8 6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' +
            'No upcoming appointments found for this period.</div>';
        return;
    }

    let html = '<div class="patients-list">';
    
    appointments.forEach(apt => {
        const dt = new Date(apt.scheduled_at);
        const timeStr = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
        const dateStr = dt.toLocaleDateString();

        // Safely handle potentially null child names
        const childFname = apt.child_fname || 'Unknown';
        const childLname = apt.child_lname || '';
        const childInitials = (childFname[0] || '?') + (childLname[0] || '');

        // Safely handle specialist names
        const specFname = apt.specialist_fname || '';
        const specLname = apt.specialist_lname || '';

        const statusLower = (apt.status || '').toLowerCase();
        let statusClass = "status-yellow";
        let statusIcon = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
        if (statusLower === 'completed') {
            statusClass = "status-green";
            statusIcon = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>';
        } else if (statusLower === 'cancelled') {
            statusClass = "status-danger";
            statusIcon = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
        } else if (statusLower === 'accepted') {
            statusClass = "status-indigo";
            statusIcon = '<polyline points="20 6 9 17 4 12"/>';
        }

        // Payment & Type coloring
        const paymentMethod = (apt.payment_method || 'Cash').toLowerCase();
        const isCredit = paymentMethod.includes('credit') || paymentMethod.includes('card');
        const paymentColor = isCredit ? '#2563eb' : '#059669';
        const paymentBg = isCredit ? '#eff6ff' : '#f0fdf4';

        const typeLower = (apt.type || 'onsite').toLowerCase();
        const typeColor = typeLower === 'online' ? '#8b5cf6' : '#22c55e';
        const typeBg = typeLower === 'online' ? '#f5f3ff' : '#f0fdf4';

        html += `
            <div class="patient-row" style="padding: 1.25rem 1.5rem;">
                <div class="appointment-time-badge" style="min-width: 80px;">
                    <div class="apt-time" style="font-size: 0.95rem;">${timeStr}</div>
                    <div class="apt-date" style="font-size: 0.75rem;">${dateStr}</div>
                </div>
                <div class="patient-avatar" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0d9488, #0891b2); font-size: 0.85rem;">
                    ${childInitials}
                </div>
                <div class="patient-info" style="flex: 2;">
                    <div class="patient-name" style="font-size: 1rem; margin-bottom: 0.15rem;">${childFname} ${childLname}</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500; margin-bottom: 0.35rem;">Parent: ${apt.parent_fname} ${apt.parent_lname || ''}</div>
                    <div class="patient-details" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                        <span style="color: #64748b;">with</span>
                        <span style="color: #1e293b; font-weight: 700;">Dr. ${specFname} ${specLname}</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem; flex: 1.5; justify-content: flex-end; align-items: center; margin-right: 1.5rem;">
                    <span style="padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; background: ${typeBg}; color: ${typeColor}; text-transform: uppercase; border: 1px solid ${typeColor}20;">
                        ${apt.type || 'Onsite'}
                    </span>
                    <span style="padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; background: ${paymentBg}; color: ${paymentColor}; text-transform: uppercase; border: 1px solid ${paymentColor}20;">
                        ${apt.payment_method || 'Cash'}
                    </span>
                </div>

                <div class="patient-status ${statusClass}" style="min-width: 120px; justify-content: center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">${statusIcon}</svg>
                    <span style="font-weight: 700;">${apt.status || 'Scheduled'}</span>
                </div>
                
                <button class="btn btn-sm btn-outline" style="margin-left: 1rem; padding: 0.5rem 1rem; border-radius: 10px;" onclick="viewAppointmentDetails(${apt.appointment_id})">Details</button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewAppointmentDetails(id) {
    const apt = clinicAppointments.find(a => a.appointment_id == id);
    if (!apt) return;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'view-appointment-modal';
    
    const initials = (apt.child_fname ? apt.child_fname[0] : '') + (apt.child_lname ? apt.child_lname[0] : '');
    const dt = new Date(apt.scheduled_at);
    const dateFormatted = dt.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    const timeFormatted = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 650px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f0f9ff, #e0f2fe);">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="width: 56px; height: 56px; background: #8b5cf6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
                        ${initials}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #0f172a;">${apt.child_fname} ${apt.child_lname}</h3>
                        <p style="margin: 0; font-size: 0.9rem; color: #64748b; font-weight: 500;">Appointment ID: #APT-${apt.appointment_id}</p>
                    </div>
                </div>
                <button class="clinic-modal-close" style="background: white; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <div class="clinic-modal-body" style="padding: 2.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-bottom: 2.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Parent Name</label>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #1e293b;">${apt.parent_fname} ${apt.parent_lname}</p>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Assigned Specialist</label>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #1e293b;">Dr. ${apt.specialist_fname} ${apt.specialist_lname}</p>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Date & Time</label>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #1e293b;">${dateFormatted} at ${timeFormatted}</p>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Type & Payment / Status</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                <span style="background: #f5f3ff; color: #8b5cf6; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; text-transform: capitalize;">${apt.type || 'Onsite'}</span>
                                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; text-transform: capitalize;">${apt.payment_method || 'Cash'}</span>
                                <span style="color: #475569; font-size: 0.9rem; font-weight: 700;">${apt.status.charAt(0).toUpperCase() + apt.status.slice(1)}</span>
                            </div>
                            ${apt.status.toLowerCase() === 'cancelled' && apt.cancelled_by ? `
                                <div style="font-size: 0.8rem; color: #ef4444; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Cancelled by ${apt.cancelled_by === 'specialist' ? 'Doctor (Specialist)' : (apt.cancelled_by === 'clinic' ? 'Clinic' : 'Patient')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <div style="padding-top: 2rem; border-top: 1px dashed #e2e8f0;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">Additional Comments</label>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        ${apt.comments || 'No additional comments provided for this appointment.'}
                    </div>
                </div>
            </div>

            <div class="clinic-modal-footer" style="padding: 2rem 2.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; gap: 1.25rem;">
                ${['pending', 'scheduled'].includes(apt.status.toLowerCase()) ? `
                    <button class="btn" style="flex: 1; padding: 0.875rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.95rem;" onclick="manageAppointment(${apt.appointment_id}, 'accepted')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Accept Appointment
                    </button>
                    <button class="btn" style="flex: 1; padding: 0.875rem; border-radius: 12px; border: 1.5px solid #ef4444; background: white; color: #ef4444; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.95rem;" onclick="manageAppointment(${apt.appointment_id}, 'cancel')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Cancel Appointment
                    </button>
                ` : `
                    <div style="flex: 1; text-align: center; padding: 0.875rem; border-radius: 12px; background: ${apt.status.toLowerCase() === 'completed' ? '#f0fdf4' : apt.status.toLowerCase() === 'cancelled' ? '#fef2f2' : '#f8fafc'}; color: ${apt.status.toLowerCase() === 'completed' ? '#16a34a' : apt.status.toLowerCase() === 'cancelled' ? '#ef4444' : '#64748b'}; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        ${apt.status.toLowerCase() === 'completed' ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' : apt.status.toLowerCase() === 'cancelled' ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' : ''}
                        This appointment is ${apt.status.toLowerCase()}
                    </div>
                `}
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeViewAppointmentModal() {
    const modal = document.getElementById('view-appointment-modal');
    if (modal) modal.remove();
}

async function manageAppointment(id, action, additionalData = {}) {
    try {
        const body = { action, appointment_id: id, ...additionalData };
        const res = await fetch('../../api_clinic_manage_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const result = await res.json();
        if (result.success) {
            showClinicToast(`Appointment ${action}ed successfully`, 'success');
            closeViewAppointmentModal();
            // Refresh appointment list
            refreshClinicData('appointments'); 
        } else {
            showClinicToast(result.error || 'Operation failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showClinicToast('Network error', 'error');
    }
}

function showRescheduleModal(id) {
    const apt = clinicAppointments.find(a => a.appointment_id == id);
    if (!apt) return;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'reschedule-modal';
    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 450px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f0f9ff, #e0f2fe);">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Reschedule Appointment</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Pick a new date and time</p>
                </div>
                <button class="clinic-modal-close" style="background: white; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" style="padding: 2rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Select New Date & Time <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="datetime-local" id="reschedule-datetime" class="premium-light-input" value="${apt.scheduled_at.replace(' ', 'T')}" style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                        <svg style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #64748b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="clinic-modal-footer" style="padding: 1.5rem 2rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; gap: 1rem;">
                <button class="btn" style="flex: 1; padding: 0.75rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer;" onclick="this.closest('.clinic-modal-overlay').remove()">Cancel</button>
                <button class="btn" style="flex: 1; padding: 0.75rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: white; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);" onclick="submitReschedule(${id})">Confirm</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function submitReschedule(id) {
    const newDate = document.getElementById('reschedule-datetime').value;
    if (!newDate) return showClinicToast('Please select a date', 'error');
    
    manageAppointment(id, 'reschedule', { new_date: newDate.replace('T', ' ') });
    document.getElementById('reschedule-modal').remove();
}

function filterSpecialists(query) {
    const q = query.toLowerCase();
    const filtered = clinicSpecialists.filter(s => 
        s.first_name.toLowerCase().includes(q) || 
        s.last_name.toLowerCase().includes(q) || 
        s.specialization.toLowerCase().includes(q)
    );
    renderSpecialistsTable({ specialists: filtered }, false); // Don't update counter during search
}

function renderSpecialistsTable(data, updateCounter = true) {
    const specialists = data.specialists || [];
    
    // Update counter boxes
    if (updateCounter) {
        const specCountBox = document.getElementById('stat-active-specialists');
        if (specCountBox) specCountBox.textContent = specialists.length;
    }

    const tbody = document.querySelector('.clinic-table tbody');
    if (!tbody) return;

    if (specialists.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">No specialists registered yet.</td></tr>';
        return;
    }

    tbody.innerHTML = specialists.map(spec => `
        <tr>
            <td>
                <div class="table-user">
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #009688, #00bcd4);">
                        ${spec.first_name[0]}${spec.last_name[0]}
                    </div>
                    <div>
                        <div class="patient-name">Dr. ${spec.first_name} ${spec.last_name}</div>
                        <div class="patient-details">${spec.email || 'specialist@clinic.com'}</div>
                    </div>
                </div>
            </td>
            <td>${spec.specialization}</td>
            <td>${spec.location || 'Main Branch'}</td>
            <td>${spec.experience_years || 0} years</td>
            <td>${spec.patients_count || 0}</td>
            <td><span class="rating-badge">★ ${spec.rating ? parseFloat(spec.rating).toFixed(1) : '0.0'}</span></td>
            <td><span class="status-badge status-active">Active</span></td>
            <td>
                <div style="display:flex; gap: 0.5rem;">
                    <button class="btn btn-sm btn-outline" onclick="showClinicView('specialist-details', ${spec.specialist_id})">View</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function viewSpecialistDetails(id) {
    const spec = clinicSpecialists.find(s => s.specialist_id == id);
    if (!spec) return;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'view-specialist-modal';
    
    const initial = (spec.first_name[0] || '') + (spec.last_name ? spec.last_name[0] : '');
    const clinicName = document.querySelector('.user-name')?.textContent || 'Bright Steps Clinic';
    
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 850px; width: 95%;">
            <div class="clinic-modal-header" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.1), rgba(45, 212, 191, 0.05)); border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="patient-avatar" style="width: 4.5rem; height: 4.5rem; font-size: 1.5rem; background: linear-gradient(135deg, #0d9488, #2dd4bf); box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);">
                        ${initial.toUpperCase()}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem; color: var(--text-primary);">Dr. ${spec.first_name} ${spec.last_name}</h3>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                            <span style="color: #0d9488; font-weight: 600; font-size: 0.9rem;">${spec.specialization}</span>
                        </div>
                    </div>
                </div>
                <button class="clinic-modal-close" onclick="closeViewModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <div class="clinic-modal-body" style="padding: 0; background: var(--bg-secondary); display: grid; grid-template-columns: 300px 1fr;">
                <!-- Sidebar Info -->
                <div style="padding: 2rem; border-right: 1px solid var(--border-color); background: rgba(255,255,255,0.02);">
                    <div class="info-section" style="margin-bottom: 2rem;">
                        <h4 style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Contact Information</h4>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary); word-break: break-all;">${spec.email || 'N/A'}</div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary);">+20 102 345 6789</div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary);">${clinicName}</div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2v20M2 12h20"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary);">${spec.location || 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4 style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Professional Summary</h4>
                        <div style="background: var(--bg-primary); padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color);">
                            <div style="margin-bottom: 0.75rem;">
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Experience</div>
                                <div style="font-weight: 600; color: var(--text-primary);">${spec.experience_years || 0} Years</div>
                            </div>
                            <div>
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Rating</div>
                                <div style="font-weight: 600; color: #f59e0b;">★ ${spec.rating ? parseFloat(spec.rating).toFixed(1) : '0.0'}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Details Area -->
                <div style="padding: 2rem; overflow-y: auto; max-height: 60vh;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.02); text-align: center;">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Total Patients</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">${spec.patients_count || 0} Patients</div>
                        </div>
                        <div style="background: white; padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.02); text-align: center;">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Joined Date</div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: #6366f1; margin-top: 0.4rem;">${spec.joined_at ? new Date(spec.joined_at).toLocaleDateString() : 'Active'}</div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Professional Certificates
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="width: 36px; height: 36px; background: #f0fdfa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div style="font-size: 0.9rem; font-weight: 600;">Board Certified Pediatrician</div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Verified</span>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="width: 36px; height: 36px; background: #f0fdfa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div style="font-size: 0.9rem; font-weight: 600;">Medical Experience Certificate</div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Verified</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="clinic-modal-footer" style="padding: 1.25rem 2rem; border-top: 1px solid var(--border-color); background: white;">
                <button class="btn btn-outline" onclick="closeViewModal()" style="min-width: 120px;">Close</button>
                <div style="display: flex; gap: 0.75rem;">
                    <button class="btn btn-outline" style="border-color: #f59e0b; color: #f59e0b;" onclick="openEditSpecialistModal(${spec.specialist_id})">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </button>
                    <button class="btn btn-outline" style="border-color: #ef4444; color: #ef4444;" onclick="confirmDeleteSpecialist(${spec.specialist_id}, '${spec.first_name} ${spec.last_name}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Remove
                    </button>
                    <button class="btn btn-gradient" style="min-width: 160px;" onclick="showClinicAlert('Specialist Email', 'Direct Email: ${spec.email || 'N/A'}')">Email Specialist</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeViewModal() {
    const modal = document.getElementById('view-specialist-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function confirmDeleteSpecialist(id, name) {
    showClinicConfirm(
        'Confirm Removal', 
        `Are you sure you want to remove Dr. ${name} from your clinic? This will cancel their upcoming appointments.`,
        () => {
            fetch('../../api_clinic_delete_specialist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ specialist_id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showClinicAlert('Success', `Dr. ${name} has been removed from your team.`);
                    closeViewModal();
                    // Force refresh and switch to specialists view
                    showClinicView('specialists');
                } else {
                    showClinicAlert('Error', data.error || 'Failed to remove specialist.');
                }
            })
            .catch(err => {
                console.error(err);
                showClinicAlert('Error', 'An unexpected error occurred.');
            });
        }
    );
}

function openEditSpecialistModal(id) {
    const spec = clinicSpecialists.find(s => s.specialist_id == id);
    if (!spec) {
        showClinicToast('Error: Specialist data not found.', 'error');
        return;
    }

    const existing = document.getElementById('edit-specialist-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'edit-specialist-modal';
    modal.className = 'clinic-modal-overlay active';
    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 550px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 22px; height: 22px;">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Edit Specialist Profile</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Update Dr. ${spec.first_name}'s professional details</p>
                    </div>
                </div>
                <button class="clinic-modal-close" style="background: #f8fafc; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="closeEditSpecialistModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="edit-specialist-form" class="clinic-modal-body" onsubmit="submitEditSpecialist(event, ${spec.specialist_id})" style="padding: 2rem;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">First Name</label>
                        <input type="text" name="first_name" required value="${spec.first_name}" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Last Name</label>
                        <input type="text" name="last_name" required value="${spec.last_name}" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Email Address (Locked)</label>
                        <input type="email" name="email" value="${spec.email}" readonly class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f1f5f9; color: #64748b; font-size: 0.95rem; outline: none; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Specialization</label>
                        <select name="specialization" required class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="Pediatrician" ${spec.specialization === 'Pediatrician' ? 'selected' : ''}>Pediatrician</option>
                            <option value="Neurologist" ${spec.specialization === 'Neurologist' ? 'selected' : ''}>Neurologist</option>
                            <option value="Psychologist" ${spec.specialization === 'Psychologist' ? 'selected' : ''}>Psychologist</option>
                            <option value="Speech Therapist" ${spec.specialization === 'Speech Therapist' ? 'selected' : ''}>Speech Therapist</option>
                            <option value="Occupational Therapist" ${spec.specialization === 'Occupational Therapist' ? 'selected' : ''}>Occupational Therapist</option>
                            <option value="Developmental Pediatrician" ${spec.specialization === 'Developmental Pediatrician' ? 'selected' : ''}>Developmental Pediatrician</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Work Location (Clinic Branch)</label>
                        <select name="location" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="Main Branch, Cairo" ${spec.location === 'Main Branch, Cairo' ? 'selected' : ''}>Main Branch, Cairo</option>
                            <option value="Alexandria Center" ${spec.location === 'Alexandria Center' ? 'selected' : ''}>Alexandria Center</option>
                            <option value="Giza Clinic" ${spec.location === 'Giza Clinic' ? 'selected' : ''}>Giza Clinic</option>
                            <option value="Mansoura Branch" ${spec.location === 'Mansoura Branch' ? 'selected' : ''}>Mansoura Branch</option>
                            <option value="Tanta Facility" ${spec.location === 'Tanta Facility' ? 'selected' : ''}>Tanta Facility</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Experience (Years)</label>
                        <input type="number" name="experience" required min="1" value="${spec.experience_years || 1}" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Certification Notes</label>
                        <textarea name="certification_text" placeholder="Update credentials summary..." class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; height: 80px; resize:none;">${spec.certification_text || ''}</textarea>
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Update Certificate (PDF)</label>
                        <input type="file" name="certification_pdf" accept=".pdf" class="premium-light-input" style="width: 100%; padding: 0.5rem; border-radius: 12px; border: 1.5px dashed #cbd5e1; background: #f8fafc; color: #1e293b; font-size: 0.85rem; outline: none;">
                        ${spec.certification_pdf ? `<p style="font-size: 0.75rem; color: #0d9488; margin-top: 5px;">Current: ${spec.certification_pdf.split('/').pop()}</p>` : ''}
                    </div>
                </div>
                <div class="clinic-modal-actions" style="margin-top: 2.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="padding: 0.75rem 2rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer;" onclick="closeEditSpecialistModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit" style="padding: 0.75rem 2.5rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-weight: 600; cursor: pointer; box-shadow: 0 8px 20px rgba(245, 158, 11, 0.2);">Save Changes</button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}



function closeEditSpecialistModal() {
    const modal = document.getElementById('edit-specialist-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function submitEditSpecialist(event, id) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('specialist_id', id);

    const btn = form.querySelector('.btn-submit');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span>';
    btn.disabled = true;

    fetch('../../api_clinic_edit_specialist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showClinicToast('Specialist updated successfully', 'success');
            closeEditSpecialistModal();
            refreshClinicData('specialists');
        } else {
            showClinicToast(data.error || 'Update failed', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showClinicToast('An unexpected error occurred', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function viewPatientRecords(childId, childName) {
    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'patient-records-modal';
    
    const names = childName.split(' ');
    const initials = (names[0][0] || '') + (names[1] ? names[1][0] : '');
    
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 800px; width: 95%;">
            <div class="clinic-modal-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="patient-avatar" style="width: 3rem; height: 3rem; font-size: 1.1rem; background: linear-gradient(135deg, #0d9488, #2dd4bf);">
                        ${initials.toUpperCase()}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem;">${childName}</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Medical History & Patient Records</p>
                    </div>
                </div>
                <button class="clinic-modal-close" onclick="closeRecordsModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" id="records-modal-body" style="padding: 1.5rem; max-height: 70vh; overflow-y: auto; background: var(--bg-secondary);">
                <div style="text-align:center; padding: 60px 20px;">
                    <div class="loading-spinner" style="width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: #0d9488; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Retrieving patient medical history...</p>
                </div>
            </div>
            <div class="clinic-modal-footer">
                <button class="btn btn-outline" onclick="closeRecordsModal()">Close Records</button>
            </div>
        </div>
        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
            .record-group { margin-bottom: 2rem; }
            .record-group-title { 
                font-size: 0.75rem; 
                font-weight: 700; 
                text-transform: uppercase; 
                letter-spacing: 0.05em; 
                color: var(--text-secondary);
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .record-group-title::after { content: ""; flex: 1; height: 1px; background: var(--border-color); }
            
            .record-item-card {
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-lg);
                padding: 1.25rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .record-item-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                border-color: #0d948844;
            }
            .record-item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
            .record-item-badge { 
                font-size: 0.7rem; 
                font-weight: 700; 
                padding: 0.25rem 0.625rem; 
                border-radius: 6px; 
                text-transform: uppercase;
            }
            .badge-diagnosis { background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1; }
            .badge-prescription { background: #fdf2f8; color: #db2777; border: 1px solid #fce7f3; }
            
            .record-item-date { font-size: 0.75rem; color: var(--text-secondary); font-weight: 500; }
            .record-item-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
            .record-item-text { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5; }
            .record-item-meta { 
                margin-top: 1rem; 
                padding-top: 0.75rem; 
                border-top: 1px dashed var(--border-color); 
                display: flex; 
                justify-content: space-between;
                font-size: 0.75rem;
                color: var(--text-muted);
            }
            .empty-history { text-align: center; padding: 2rem; color: var(--text-secondary); font-style: italic; font-size: 0.9rem; }
        </style>
    `;
    
    document.body.appendChild(modal);

    // Fetch actual data
    fetch(`../../clinic/api/management/history.php?action=child&child_id=${childId}`)
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Unauthorized access') });
            return res.json();
        })
        .then(data => {
            if (data.success) {
                renderRecordsContent(data);
            } else {
                throw new Error(data.error || 'Failed to load records');
            }
        })
        .catch(err => {
            console.error('Error fetching records:', err);
            document.getElementById('records-modal-body').innerHTML = `
                <div style="text-align:center; padding: 60px 20px;">
                    <div style="width: 48px; height: 48px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h4 style="color: var(--text-primary); margin-bottom: 8px;">Unable to Load Records</h4>
                    <p style="color: #ef4444; font-size: 0.9rem; margin-bottom: 20px;">${err.message}</p>
                    <button class="btn btn-outline" onclick="closeRecordsModal()">Close</button>
                </div>
            `;
        });
}

function renderRecordsContent(data) {
    const container = document.getElementById('records-modal-body');
    const recs = data.medical_records || [];
    const prescriptions = data.prescriptions || [];
    const appointments = data.appointments || [];

    let html = '';

    // 1. Diagnoses Section
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Diagnoses & Medical Reports</h4>
            ${recs.length === 0 ? '<div class="empty-history">No medical records found for this patient.</div>' : 
                recs.map(r => `
                    <div class="record-item-card">
                        <div class="record-item-header">
                            <span class="record-item-badge badge-diagnosis">Medical Record</span>
                            <span class="record-item-date">${new Date(r.created_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</span>
                        </div>
                        <div class="record-item-title">${r.diagnosis}</div>
                        <div class="record-item-text">
                            <strong>Symptoms:</strong> ${r.symptoms || 'Not specified'}<br>
                            ${r.notes ? `<strong>Notes:</strong> ${r.notes}` : ''}
                        </div>
                        <div class="record-item-meta">
                            <span>Specialist: Dr. ${r.doctor_first_name} ${r.doctor_last_name}</span>
                            <span>${r.specialization}</span>
                        </div>
                    </div>
                `).join('')
            }
        </div>
    `;

    // 2. Prescriptions Section
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Prescriptions</h4>
            ${prescriptions.length === 0 ? '<div class="empty-history">No active or past prescriptions found.</div>' : 
                prescriptions.map(p => `
                    <div class="record-item-card">
                        <div class="record-item-header">
                            <span class="record-item-badge badge-prescription">Prescription</span>
                            <span class="record-item-date">${new Date(p.created_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</span>
                        </div>
                        <div class="record-item-title">${p.medication_name} ${p.dosage ? `<span style="font-weight:400; color:var(--text-secondary); font-size:0.9rem;">— ${p.dosage}</span>` : ''}</div>
                        <div class="record-item-text">
                            <strong>Frequency:</strong> ${p.frequency || 'As directed'}<br>
                            ${p.instructions ? `<strong>Instructions:</strong> ${p.instructions}` : ''}
                        </div>
                        <div class="record-item-meta">
                            <span>Prescribed by: Dr. ${p.doctor_first_name} ${p.doctor_last_name}</span>
                        </div>
                    </div>
                `).join('')
            }
        </div>
    `;

    // 3. Appointment Timeline
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Appointment History</h4>
            ${appointments.length === 0 ? '<div class="empty-history">No appointment history available.</div>' : 
                appointments.slice(0, 10).map(a => `
                    <div class="record-item-card" style="display:flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem;">
                        <div style="display:flex; gap: 1rem; align-items: center;">
                            <div style="width: 40px; height: 40px; background: var(--bg-secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">Dr. ${a.doctor_first_name} ${a.doctor_last_name}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">${new Date(a.scheduled_at).toLocaleDateString()} • ${a.type}</div>
                            </div>
                        </div>
                        <span class="status-badge ${a.status === 'completed' ? 'status-active' : a.status === 'cancelled' ? 'status-danger' : 'status-warning'}" style="text-transform: capitalize;">
                            ${a.status}
                        </span>
                    </div>
                `).join('')
            }
        </div>
    `;

    container.innerHTML = html;
}

function closeRecordsModal() {
    const modal = document.getElementById('patient-records-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

// ── Specialists View ─────────────────────────────────────────
function getSpecialistsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Specialists Management</h1>
                <p class="dashboard-subtitle">Manage your clinic's healthcare team</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient" onclick="openAddSpecialistModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Specialist
                </button>
            </div>
        </div>

        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-active-specialists">--</div><div class="stat-card-label">Active Specialists</div></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-total-appointments">--</div><div class="stat-card-label">Total Appointments</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-active-patients">--</div><div class="stat-card-label">Total Patients</div></div>
            </div>
            <div class="stat-card stat-card-purple">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-avg-rating">--</div><div class="stat-card-label">Specialist Avg Rating</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Specialists</h2>
                <input type="text" class="search-input" placeholder="Search specialists..." onkeyup="filterSpecialists(this.value)">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Specialist</th><th>Specialization</th><th>Location</th><th>Experience</th><th>Patients</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">Loading team members...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

// ── Appointments View ────────────────────────────────────────
function getAppointmentsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Appointments</h1>
                <p class="dashboard-subtitle">Manage clinic schedule and patient appointments</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-outline" onclick="toggleCalendarView()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar View
                </button>

            </div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns: repeat(5, 1fr); gap: 1rem;">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-today-appointments">--</div><div class="stat-card-label">Today</div></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-completed">--</div><div class="stat-card-label">Completed</div></div>
            </div>
            <div class="stat-card stat-card-indigo">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-accepted">--</div><div class="stat-card-label">Accepted</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-pending">--</div><div class="stat-card-label">Pending</div></div>
            </div>
            <div class="stat-card stat-card-red" style="border-left: 4px solid #ef4444;">
                <div class="stat-card-icon" style="color:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-cancelled">--</div><div class="stat-card-label">Cancelled</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">Upcoming Appointments</h2>
                <select id="apt-spec-filter" class="search-input" style="width:auto;" onchange="filterAppointmentsBySpec(this.value)">
                    <option value="">All Specialists</option>
                </select>
            </div>
            <div id="appointments-view-container">
                <div class="patients-list">
                    <div class="patient-row">
                        <div class="appointment-time-badge">
                            <div class="apt-time">9:00 AM</div>
                            <div class="apt-date">Today</div>
                        </div>
                        <div class="patient-avatar">EJ</div>
                        <div class="patient-info">
                            <div class="patient-name">Emma Johnson</div>
                            <div class="patient-details">with Specialist • Routine Checkup</div>
                        </div>
                        <div class="patient-status status-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Onsite
                        </div>
                        <button class="btn btn-sm btn-outline">Details</button>
                    </div>
                    <!-- Other rows ... -->
                </div>
            </div>
        </div>
    </div>`;
}

// ── New Appointment Modal ───────────────────────────────────────
function openNewAppointmentModal() {
    // Create modal if not exists
    let modal = document.getElementById('appointment-modal-overlay');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'appointment-modal-overlay';
        modal.className = 'clinic-modal-overlay';
        document.body.appendChild(modal);
    }

    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 600px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">+ Schedule New Appointment</h3>
                <button class="clinic-modal-close" style="background: #f8fafc; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="closeClinicModal('appointment-modal-overlay')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" style="padding: 2rem;">
                <form id="new-appointment-form">
                    <div class="modal-form-group" style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Select Patient <span style="color: #ef4444;">*</span></label>
                        <select id="apt-patient" class="premium-light-input" required style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; transition: border-color 0.2s;">
                            <option value="">Choose a patient...</option>
                        </select>
                    </div>
                    <div class="modal-form-group" style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Select Specialist <span style="color: #ef4444;">*</span></label>
                        <select id="apt-specialist" class="premium-light-input" required style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                            <option value="">Choose a specialist...</option>
                        </select>
                    </div>
                    <div class="modal-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="modal-form-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Date <span style="color: #ef4444;">*</span></label>
                            <div style="position: relative;">
                                <input type="date" id="apt-date" class="premium-light-input" required style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem;">
                                <svg style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #64748b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                        </div>
                        <div class="modal-form-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Time <span style="color: #ef4444;">*</span></label>
                            <div style="position: relative;">
                                <input type="time" id="apt-time" class="premium-light-input" required style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem;">
                                <svg style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #64748b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="modal-form-group" style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Additional Comments</label>
                        <textarea id="apt-comment" class="premium-light-input" placeholder="Notes for the specialist..." style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; height: 80px; resize: none;"></textarea>
                    </div>
                    <div class="modal-form-group" style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Certification Document (PDF) <span style="color: #ef4444;">*</span></label>
                        <div style="position: relative; padding: 1rem; border: 1.5px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; text-align: center;">
                            <input type="file" id="apt-certification" accept=".pdf" required style="position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;">
                            <div id="file-status-text" style="color: #64748b; font-size: 0.85rem;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 4px; display: block; margin: 0 auto 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span>Click to upload PDF certification</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="clinic-modal-footer" style="padding: 1.5rem 2rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 1rem;">
                <button class="btn" style="padding: 0.75rem 2rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s;" onclick="closeClinicModal('appointment-modal-overlay')">Cancel</button>
                <button id="btn-confirm-booking" class="btn" style="padding: 0.75rem 2rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: white; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); transition: all 0.2s;" onclick="submitNewAppointment()">Confirm Booking</button>
            </div>
        </div>
        <script>
            document.getElementById('apt-certification')?.addEventListener('change', function(e) {
                const text = document.getElementById('file-status-text');
                if (this.files && this.files[0]) {
                    text.innerHTML = '<span style="color: #0d9488; font-weight: 600;">✓ ' + this.files[0].name + '</span>';
                }
            });
        </script>
    `;

    modal.classList.add('active');
    
    // Use cached data if available, otherwise fetch
    const populateForm = (data) => {
        const pSelect = document.getElementById('apt-patient');
        const sSelect = document.getElementById('apt-specialist');
        
        if (!pSelect || !sSelect) return;

        // Clear existing options (except first)
        pSelect.innerHTML = '<option value="">Choose a patient...</option>';
        sSelect.innerHTML = '<option value="">Choose a specialist...</option>';

        if (data.patients && data.patients.length > 0) {
            data.patients.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.child_id;
                opt.textContent = `${p.first_name} ${p.last_name || ''} (Parent: ${p.parent_fname})`;
                pSelect.appendChild(opt);
            });
        }
        
        if (data.specialists && data.specialists.length > 0) {
            data.specialists.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.specialist_id;
                opt.textContent = `Dr. ${s.first_name} ${s.last_name || ''} - ${s.specialization || 'Specialist'}`;
                sSelect.appendChild(opt);
            });
        }
    };

    if (window.clinicData) {
        populateForm(window.clinicData);
    } else {
        fetch('../../api_get_clinic_data.php')
            .then(res => res.json())
            .then(data => {
                if (!data.error) {
                    window.clinicData = data;
                    populateForm(data);
                }
            });
    }
}

function submitNewAppointment() {
    const pId = document.getElementById('apt-patient').value;
    const sId = document.getElementById('apt-specialist').value;
    const date = document.getElementById('apt-date').value;
    const time = document.getElementById('apt-time').value;
    const certInput = document.getElementById('apt-certification');

    if (!pId || !sId || !date || !time) {
        showClinicToast('Please fill all required fields.', 'error');
        return;
    }

    if (!certInput.files || !certInput.files[0]) {
        showClinicToast('Certification PDF is required.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('child_id', pId);
    formData.append('specialist_id', sId);
    formData.append('scheduled_at', date + ' ' + time);
    formData.append('type', document.getElementById('apt-type')?.value || 'onsite');
    formData.append('comment', document.getElementById('apt-comment').value);
    formData.append('certification_pdf', certInput.files[0]);

    const submitBtn = document.getElementById('btn-confirm-booking');
    const originalText = submitBtn.innerText;
    submitBtn.innerText = 'Scheduling...';
    submitBtn.disabled = true;

    fetch('../../api_clinic_book_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showClinicToast('Appointment scheduled successfully!', 'success');
            closeClinicModal('appointment-modal-overlay');
            showClinicView('appointments'); // Refresh view
        } else {
            showClinicToast(data.error || 'Failed to book appointment', 'error');
        }
    });
}

function closeClinicModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('active');
}

// ── Calendar View ──────────────────────────────────────────────
let isCalendarView = false;
function toggleCalendarView() {
    const container = document.getElementById('appointments-view-container');
    if (!container) return;
    
    isCalendarView = !isCalendarView;
    
    if (isCalendarView) {
        renderCalendarGrid(container);
    } else {
        showClinicView('appointments');
    }
}

function changeCalendarMonth(delta) {
    currentCalendarMonth += delta;
    if (currentCalendarMonth > 11) {
        currentCalendarMonth = 0;
        currentCalendarYear++;
    } else if (currentCalendarMonth < 0) {
        currentCalendarMonth = 11;
        currentCalendarYear--;
    }
    const container = document.getElementById('appointments-view-container');
    if (container) renderCalendarGrid(container);
}

function renderCalendarGrid(container) {
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const daysInMonth = new Date(currentCalendarYear, currentCalendarMonth + 1, 0).getDate();
    const firstDay = new Date(currentCalendarYear, currentCalendarMonth, 1).getDay();
    
    // Filter appointments for this month
    const monthApts = clinicAppointments.filter(a => {
        const d = new Date(a.scheduled_at);
        return d.getMonth() === currentCalendarMonth && d.getFullYear() === currentCalendarYear;
    });

    let gridHtml = '';
    // Empty cells for days before the 1st
    for (let i = 0; i < firstDay; i++) {
        gridHtml += `<div style="background:var(--bg-secondary);opacity:0.3;"></div>`;
    }

    // Days of the month
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset for comparison

    for (let day = 1; day <= daysInMonth; day++) {
        const checkDate = new Date(currentCalendarYear, currentCalendarMonth, day);
        const isPast = checkDate < today;
        const isToday = today.getDate() === day && today.getMonth() === currentCalendarMonth && today.getFullYear() === currentCalendarYear;
        
        // Find appointments for this specific day
        const dayApts = monthApts.filter(a => new Date(a.scheduled_at).getDate() === day);
        
        const bgColor = isPast ? '#f1f5f9' : (isToday ? 'rgba(13,148,136,0.05)' : 'var(--bg-primary)');
        const opacity = isPast ? '0.7' : '1';
        const textColor = isPast ? '#94a3b8' : 'var(--text-primary)';

        gridHtml += `
            <div style="background:${bgColor}; opacity:${opacity}; min-height:100px; padding:0.5rem; border:1px solid var(--border-color); position:relative;">
                <div style="font-weight:700; font-size:0.8rem; margin-bottom:0.5rem; color:${isToday ? '#0d9488' : textColor};">${day}</div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    ${dayApts.map(a => `
                        <div class="calendar-apt-pill" 
                             onclick="viewAppointmentDetails(${a.appointment_id})"
                             style="font-size:0.65rem;background:#f0fdf4;color:#16a34a;padding:2px 6px;border-radius:4px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border:1px solid #dcfce7;"
                             title="${a.child_fname} - ${a.type}">
                            ${new Date(a.scheduled_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})} ${a.child_fname}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    container.innerHTML = `
        <div class="calendar-wrapper" style="padding:1rem;animation:fadeIn 0.3s ease;">
            <div class="calendar-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;background:white;padding:1rem;border-radius:12px;border:1px solid var(--border-color);">
                <div style="font-size:1.25rem;font-weight:700;color:var(--text-primary);">${monthNames[currentCalendarMonth]} ${currentCalendarYear}</div>
                <div style="display:flex;gap:0.75rem;">
                    <button class="btn btn-sm btn-outline" onclick="changeCalendarMonth(-1)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                        Prev
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="changeCalendarMonth(1)">
                        Next
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="calendar-grid-header" style="display:grid;grid-template-columns:repeat(7, 1fr);margin-bottom:2px;">
                ${['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => `<div style="background:var(--bg-secondary);padding:0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:var(--text-secondary);">${d}</div>`).join('')}
            </div>
            <div class="calendar-grid" style="display:grid;grid-template-columns:repeat(7, 1fr);gap:1px;background:var(--border-color);border:1px solid var(--border-color);border-radius:0 0 12px 12px;overflow:hidden;">
                ${gridHtml}
            </div>
        </div>
    `;
}

function showClinicToast(msg, type = 'success') {
    let toast = document.getElementById('clinic-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'clinic-toast';
        toast.className = 'clinic-toast';
        document.body.appendChild(toast);
    }
    
    toast.textContent = msg;
    toast.className = `clinic-toast show dr-toast-${type}`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}


// ── Patients View ────────────────────────────────────────────
function getPatientsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Patient Directory</h1>
                <p class="dashboard-subtitle">All registered patients at City Kids Care</p>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="section-heading">All Patients</h2>
                <div style="display:flex; gap:1rem; align-items:center;">
                    <input type="text" class="search-input" placeholder="Search by name or parent..." onkeyup="filterPatients(this.value)">
                </div>
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Child</th><th>Age</th><th>Parent/Guardian</th><th>Assigned Specialist</th><th>Status</th><th>Last Visit</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">Loading patients...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

function filterPatients(query) {
    const q = query.toLowerCase();
    const filtered = clinicPatients.filter(p => {
        const childName = (p.first_name + ' ' + (p.last_name || '')).toLowerCase();
        const parentName = (p.parent_fname + ' ' + (p.parent_lname || '')).toLowerCase();
        return childName.includes(q) || parentName.includes(q);
    });
    renderPatientsTable(filtered);
}

function renderPatientsTable(patients) {
    const tbody = document.querySelector('.clinic-table tbody');
    if (!tbody) return;

    if (patients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">No patients found matching your search.</td></tr>';
        return;
    }

    const gradients = [
        'background: linear-gradient(135deg, #009688, #00bcd4)',
        'background: linear-gradient(135deg, #06b6d4, #0891b2)',
        'background: linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'background: linear-gradient(135deg, #f59e0b, #d97706)',
        'background: linear-gradient(135deg, #ec4899, #db2777)'
    ];

    tbody.innerHTML = patients.map((p, i) => {
        const bg = gradients[i % gradients.length];
        const initial = (p.first_name[0] || '') + (p.last_name ? p.last_name[0] : '');
        return `
        <tr>
            <td>
                <div class="table-user">
                    <div class="patient-avatar" style="${bg};">${initial.toUpperCase()}</div>
                    <div>
                        <div class="patient-name">${p.first_name} ${p.last_name || ''}</div>
                        <div class="patient-details">Child ID: ${p.child_id}</div>
                    </div>
                </div>
            </td>
            <td>N/A</td>
            <td>${p.parent_fname} ${p.parent_lname || ''}</td>
            <td>Unassigned</td>
            <td><span class="status-badge status-active">Active</span></td>
            <td>N/A</td>
            <td><button class="btn btn-sm btn-outline" onclick="viewPatientRecords(${p.child_id}, '${p.first_name} ${p.last_name || ''}')">View Records</button></td>
        </tr>`;
    }).join('');
}

// ── Revenue View ─────────────────────────────────────────────
function getRevenueView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Treasury & Finance</h1>
                <p class="dashboard-subtitle">Comprehensive fiscal management and transaction audit</p>
            </div>
            <div class="header-actions-inline">
                <select id="export-month-select" class="premium-light-input" style="width: auto; padding: 0.5rem 1rem;">
                    ${Array.from({length: 6}, (_, i) => {
                        const d = new Date();
                        d.setMonth(d.getMonth() - i);
                        const m = d.toLocaleString('default', { month: 'long' });
                        const y = d.getFullYear();
                        return `<option value="${d.getMonth()+1}-${y}">${m} ${y}</option>`;
                    }).join('')}
                </select>
                <button onclick="exportRevenueReport()" class="btn btn-gradient" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Report
                </button>
            </div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-card-info">
                    <div class="stat-card-value" id="stat-revenue-month">--</div>
                    <div class="stat-card-label">Gross Revenue</div>
                    <div style="font-size: 0.75rem; margin-top: 4px; display: flex; gap: 8px;">
                        <span style="color: #059669; font-weight: 600;" id="stat-cash-total">Cash: --</span>
                        <span style="color: #2563eb; font-weight: 600;" id="stat-credit-total">Credit: --</span>
                    </div>
                </div>
            </div>
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-sessions-booked">--</div><div class="stat-card-label">Transaction Volume</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-pending-revenue">--</div><div class="stat-card-label">Accounts Receivable</div></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div class="section-card">
                <div class="section-card-header">
                    <h2 class="section-heading">Daily Fiscal Summary</h2>
                </div>
                <div style="padding: 1.5rem; height: 350px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">General Ledger & Audit</h2>
            </div>
            <div id="revenue-breakdown-container">
                <div style="text-align:center; padding:3rem; color:#64748b;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem;">Loading transaction breakdown...</p>
                </div>
            </div>
        </div>
    </div>`;
}

function exportRevenueReport() {
    const period = document.getElementById('export-month-select').value;
    const [month, year] = period.split('-');
    window.open(`../../api_clinic_revenue_export.php?month=${month}&year=${year}`, '_blank');
}

// ── Reviews View ─────────────────────────────────────────────
function getReviewsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Parent Reviews & Feedback</h1>
                <p class="dashboard-subtitle">See what parents say about your clinic</p>
            </div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-overall-rating">--</div><div class="stat-card-label">Overall Clinic Rating</div></div>
            </div>
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value" id="stat-total-reviews">--</div><div class="stat-card-label">Feedback</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header"><h2 class="section-heading">Recent Reviews</h2></div>
            <div class="reviews-list">
                <div style="text-align:center; padding: 40px; color: var(--text-secondary);">Loading reviews...</div>
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
                <h1 class="dashboard-title">Clinic Settings</h1>
                <p class="dashboard-subtitle">Manage your clinic profile and platform preferences</p>
            </div>
        </div>

        <div class="settings-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="section-card" id="clinic-profile-card">
                    <div class="section-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="section-heading">Clinic Profile</h2>
                        <div class="header-actions-inline">
                            <button class="btn btn-gradient btn-sm" id="btn-edit-profile" onclick="toggleClinicProfileEdit()">Edit Profile</button>
                        </div>
                    </div>
                    
                    <!-- View Mode (Default) -->
                    <div id="clinic-profile-view" style="padding: 1.5rem;">
                        <div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading profile data...</div>
                    </div>

                    <!-- Edit Mode -->
                    <div id="clinic-profile-edit" style="display: none; padding: 1.5rem;">
                        <div class="form-grid">
                            <div class="form-group"><label>Clinic Name</label><input type="text" id="edit-clinic-name" class="form-input"></div>
                            <div class="form-group"><label>Public Email Address</label><input type="email" id="edit-clinic-email" class="form-input"></div>
                            <div class="form-group"><label>Clinic Location</label><input type="text" id="edit-clinic-location" class="form-input"></div>
                        </div>
                        <div class="form-group" style="margin-top:1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Update Profile Picture</label>
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div id="edit-pfp-preview" style="width: 70px; height: 70px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #e2e8f0;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </div>
                                <input type="file" id="edit-pfp-input" accept="image/*" style="display: none;" onchange="previewClinicPfp(this)">
                                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('edit-pfp-input').click()">Pick Image</button>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:1.5rem;">
                            <label>Description / Bio</label>
                            <textarea id="edit-clinic-bio" class="form-input" rows="4" placeholder="Brief description about your clinic's mission and services"></textarea>
                        </div>
                        
                        <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem;">
                            <button class="btn btn-outline" onclick="toggleClinicProfileEdit()">Discard Changes</button>
                            <button class="btn btn-gradient" onclick="saveClinicProfile()">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar column -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="section-card">
                    <div class="section-card-header"><h2 class="section-heading">System Preferences</h2></div>
                    <div style="padding: 1.5rem;">
                        <div class="settings-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color);">
                            <div>
                                <h4 style="margin:0; font-size:0.95rem; color:var(--text-primary);">Dark Mode</h4>
                                <p style="margin:0; font-size:0.8rem; color:var(--text-secondary);">Toggle platform theme</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="theme-setting-toggle" onclick="toggleTheme()" ${document.body.classList.contains('dark-theme') ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="settings-row" style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0; font-size:0.95rem; color:var(--text-primary);">Language</h4>
                                <p style="margin:0; font-size:0.8rem; color:var(--text-secondary);">Switch to Arabic (عربي)</p>
                            </div>
                            <button class="btn btn-outline btn-sm" onclick="toggleLanguage()">Toggle AR/EN</button>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-card-header"><h2 class="section-heading">Notification Preferences</h2></div>
                    <div style="padding: 1.5rem;">
                        <div class="toggle-row" style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <span>Push Notifications</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="notif-push-toggle" onchange="updateClinicNotificationSetting('push_notifications', this.checked ? 1 : 0)" ${clinicSettings?.push_notifications ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row" style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <span>Email Notifications</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="notif-email-toggle" onchange="updateClinicNotificationSetting('email_notifications', this.checked ? 1 : 0)" ${clinicSettings?.email_notifications ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row" style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <span>Appointment Reminders</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="notif-appointment-toggle" onchange="updateClinicNotificationSetting('appointment_reminders', this.checked ? 1 : 0)" ${clinicSettings?.appointment_reminders ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row" style="display:flex; justify-content:space-between;">
                            <span>System Alerts</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="notif-system-toggle" onchange="updateClinicNotificationSetting('system_alerts', this.checked ? 1 : 0)" ${clinicSettings?.system_alerts ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="danger-zone-card">
                    <div class="danger-zone-content">
                        <div class="danger-icon-wrapper">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div class="danger-text">
                            <h2 class="danger-title">Clinic Deactivation</h2>
                            <p class="danger-description">Irreversible actions that affect your clinic's visibility and accessibility. Please proceed with extreme caution.</p>
                        </div>
                    </div>
                    <button class="btn btn-danger-premium" onclick="handleDeactivateClinic()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                        Deactivate Clinic Account
                    </button>
                </div>
            </div>
        </div>
    </div>`;
}

function renderSettingsData() {
    const viewContainer = document.getElementById('clinic-profile-view');
    if (!viewContainer || !window.clinicData || !window.clinicData.clinic) return;
    
    const c = window.clinicData.clinic;
    
    // Populate View Mode
    viewContainer.innerHTML = `
        <div style="display:flex; gap:1.5rem; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color);">
            <div style="width:80px; height:80px; border-radius:16px; background:linear-gradient(135deg, #0d9488, #2dd4bf); display:flex; justify-content:center; align-items:center; color:white; font-size:2rem; font-weight:bold; box-shadow:0 10px 25px rgba(13,148,136,0.2); overflow:hidden;">
                ${c.profile_image ? `<img src="../../${c.profile_image}" style="width:100%; height:100%; object-fit:cover;">` : (c.clinic_name ? c.clinic_name[0] : 'C')}
            </div>
            <div>
                <h3 style="margin:0 0 0.5rem; font-size:1.5rem; color:var(--text-primary);">${c.clinic_name || 'Clinic Name'}</h3>
                <div style="display:flex; align-items:center; color:var(--text-secondary); font-size:0.95rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    ${c.location || 'Location not set'}
                </div>
            </div>
        </div>
        
        <div>
            <div style="font-size:0.85rem; font-weight:700; color:var(--text-primary); margin-bottom:0.5rem;">Clinic Overview & Bio</div>
            <p style="margin:0; font-size:0.95rem; line-height:1.6; color:var(--text-secondary);">
                ${c.bio || 'No description provided. Click Edit Profile to add information about your clinic.'}
            </p>
        </div>
    `;
    
    // Populate Edit Mode inputs
    document.getElementById('edit-clinic-name').value = c.clinic_name || '';
    document.getElementById('edit-clinic-email').value = c.email || '';
    document.getElementById('edit-clinic-location').value = c.location || '';
    document.getElementById('edit-clinic-bio').value = c.bio || '';
    
    // Populate Edit Mode PFP preview
    const editPreview = document.getElementById('edit-pfp-preview');
    if (editPreview) {
        if (c.profile_image) {
            editPreview.innerHTML = `<img src="../../${c.profile_image}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
            editPreview.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>`;
        }
    }
}

function toggleClinicProfileEdit() {
    const viewEl = document.getElementById('clinic-profile-view');
    const editEl = document.getElementById('clinic-profile-edit');
    const btn = document.getElementById('btn-edit-profile');
    
    if (viewEl.style.display !== 'none') {
        viewEl.style.display = 'none';
        editEl.style.display = 'block';
        btn.textContent = 'Editing Mode';
    } else {
        viewEl.style.display = 'block';
        editEl.style.display = 'none';
        btn.textContent = 'Edit Profile';
    }
}

function previewClinicPfp(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('edit-pfp-preview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveClinicProfile() {
    const name = document.getElementById('edit-clinic-name').value;
    const email = document.getElementById('edit-clinic-email').value;
    const loc = document.getElementById('edit-clinic-location').value;
    const bio = document.getElementById('edit-clinic-bio').value;
    const pfpInput = document.getElementById('edit-pfp-input');

    const formData = new FormData();
    formData.append('clinic_name', name);
    formData.append('email', email);
    formData.append('location', loc);
    formData.append('bio', bio);
    if (pfpInput && pfpInput.files[0]) {
        formData.append('profile_image', pfpInput.files[0]);
    }

    try {
        const res = await fetch('../../api_clinic_update_profile.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        
        if (result.success) {
            showClinicToast('Clinic profile updated successfully', 'success');
            // Update local state and re-render
            window.clinicData.clinic = { ...window.clinicData.clinic, clinic_name: name, email, location: loc, bio };
            if (result.profile_image) {
                window.clinicData.clinic.profile_image = result.profile_image;
            }
            renderSettingsData();
            toggleClinicProfileEdit();
            
            // Also update sidebar if name changed
            updateSidebarProfile(name);
        } else {
            showClinicToast(result.error || 'Failed to update profile', 'error');
        }
    } catch (err) {
        console.error("Save Profile Error:", err);
        showClinicToast('Failed to connect to server', 'error');
    }
}

function showDangerConfirm(title, message, confirmText = "Confirm Action") {
    return new Promise((resolve) => {
        const modalId = "danger-confirm-modal-" + Date.now();
        const modal = document.createElement("div");
        modal.id = modalId;
        modal.className = "clinic-modal-overlay active";
        modal.style.zIndex = "10001";
        
        modal.innerHTML = `
            <div class="clinic-modal" style="max-width: 500px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 40px; overflow: hidden; box-shadow: 0 40px 120px -20px rgba(220, 38, 38, 0.4); border: 1px solid rgba(254, 226, 226, 0.5); animation: modalIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
                <div style="padding: 3.5rem 3rem; text-align: center;">
                    <img src="../../clinic_deactivation_warning_illustration_1779711663944.png" alt="Warning" style="width: 140px; height: auto; margin-bottom: 2rem; filter: drop-shadow(0 10px 20px rgba(220, 38, 38, 0.2));">
                    <h3 style="margin: 0 0 1rem; font-size: 1.85rem; font-weight: 900; color: #1e293b; letter-spacing: -0.03em; line-height: 1.1;">Account Deactivation Checkpoint</h3>
                    <p style="margin: 0; font-size: 1.05rem; color: #64748b; line-height: 1.6; font-weight: 500;">
                        This action will immediately terminate your clinic's public presence. Are you absolutely certain you wish to proceed with deactivation?
                    </p>
                </div>
                <div style="padding: 2.5rem 3rem; background: rgba(255, 250, 250, 0.8); display: flex; flex-direction: column; gap: 1.25rem; border-top: 1px solid #fee2e2;">
                    <button class="btn-confirm" style="width: 100%; padding: 1.25rem; border-radius: 20px; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; color: white; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s; box-shadow: 0 12px 30px rgba(239, 68, 68, 0.3);">
                        Confirm Deactivation
                    </button>
                    <button class="btn-cancel" style="width: 100%; padding: 1.1rem; border-radius: 20px; background: white; border: 1px solid #e2e8f0; color: #64748b; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                        Return to Safety
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = (val) => {
            modal.style.opacity = '0';
            modal.style.transform = 'translateY(10px)';
            setTimeout(() => {
                modal.remove();
                resolve(val);
            }, 200);
        };

        modal.querySelector(".btn-cancel").onclick = () => closeModal(false);
        modal.querySelector(".btn-confirm").onclick = () => closeModal(true);
        modal.onclick = (e) => { if (e.target === modal) closeModal(false); };
    });
}

async function handleDeactivateClinic() {
    const confirmed = await showDangerConfirm(
        "Critical Deactivation", 
        "Are you sure you want to deactivate your clinic account? This will hide your profile from parents and log you out immediately. This action is permanent.",
        "Deactivate Account"
    );
    
    if (!confirmed) return;

    try {
        const res = await fetch('../../api_clinic_deactivate.php', { method: 'POST' });
        const result = await res.json();
        
        if (result.success) {
            showClinicToast("Account successfully deactivated", "success");
            setTimeout(() => {
                window.location.href = '../../login.php';
            }, 1500);
        } else {
            showClinicToast(result.error || "Failed to deactivate", "error");
        }
    } catch (err) {
        console.error(err);
        showClinicToast("Network error", "error");
    }
}

// ── Logout ───────────────────────────────────────────────────
function handleLogout() {
    const existing = document.getElementById('logout-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'logout-modal';
    modal.innerHTML = `
        <div class="logout-overlay" onclick="closeLogoutModal()"></div>
        <div class="logout-dialog">
                <div class="logout-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14l5-5-5-5m5 5H9" />
                    </svg>
                </div>
                <h3>Are you sure you want to log out?</h3>
                <p>You will need to sign in again to access your dashboard.</p>
                <div class="logout-actions">
                    <button class="logout-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                    <button class="logout-btn-confirm" onclick="confirmLogout()">Yes, Log Out</button>
                </div>
            </div>
    `;
    document.body.appendChild(modal);
    requestAnimationFrame(() => modal.classList.add('show'));
}

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (modal) {
        modal.classList.remove('show');
        modal.classList.add('hide');
        setTimeout(() => modal.remove(), 300);
    }
}

function confirmLogout() {
    window.location.href = '../../logout.php';
}

// ── Specialist Modal Implementation ──────────────────────────
function openAddSpecialistModal() {
    const existing = document.getElementById('specialist-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'specialist-modal';
    modal.className = 'clinic-modal-overlay active';
    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 550px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(13, 148, 136, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 22px; height: 22px;">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Add New Specialist</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Register a new professional to your clinic team</p>
                    </div>
                </div>
                <button class="clinic-modal-close" style="background: #f8fafc; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="closeSpecialistModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="add-specialist-form" class="clinic-modal-body" onsubmit="submitAddSpecialist(event)" style="padding: 2rem;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">First Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="first_name" required placeholder="Sarah" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Last Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="last_name" required placeholder="Mitchell" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Email Address <span style="color: #ef4444;">*</span></label>
                        <div style="position: relative;">
                            <input type="email" name="email" placeholder="dr.smith@example.com" required class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; font-weight:600;">
                            <svg style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #64748b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Default Password (Locked) <span style="color: #ef4444;">*</span></label>
                        <div style="position: relative;">
                            <input type="text" name="password" value="12345678" readonly required class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; border: 1.5px solid #bfdbfe; background: #eff6ff; color: #1e40af; font-size: 0.95rem; outline: none; cursor: not-allowed; font-weight:600;">
                            <svg style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #3b82f6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <p style="margin-top: 6px; font-size: 0.75rem; color: #3b82f6; font-weight: 500;">Specialist must update these credentials during their first secured login.</p>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Specialization <span style="color: #ef4444;">*</span></label>
                        <select name="specialization" required class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="" disabled selected>Select specialization…</option>
                            <option value="Pediatrician">Pediatrician</option>
                            <option value="Neurologist">Neurologist</option>
                            <option value="Psychologist">Psychologist</option>
                            <option value="Speech Therapist">Speech Therapist</option>
                            <option value="Occupational Therapist">Occupational Therapist</option>
                            <option value="Developmental Pediatrician">Developmental Pediatrician</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Work Location (Clinic Branch)</label>
                        <select name="location" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="Main Branch, Cairo" selected>Main Branch, Cairo</option>
                            <option value="Alexandria Center">Alexandria Center</option>
                            <option value="Giza Clinic">Giza Clinic</option>
                            <option value="Mansoura Branch">Mansoura Branch</option>
                            <option value="Tanta Facility">Tanta Facility</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Experience (Years) <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="experience" required min="1" placeholder="5" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Certification Notes <span style="color: #ef4444;">*</span></label>
                        <textarea name="certification_text" required placeholder="Verified credentials and training summary..." class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; height: 80px; resize:none;"></textarea>
                    </div>
                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Certification Document (PDF) <span style="color: #ef4444;">*</span></label>
                        <input type="file" name="certification_pdf" accept=".pdf" required class="premium-light-input" style="width: 100%; padding: 0.5rem; border-radius: 12px; border: 1.5px dashed #cbd5e1; background: #f8fafc; color: #1e293b; font-size: 0.85rem; outline: none;">
                    </div>
                </div>
                <div class="clinic-modal-actions" style="margin-top: 2.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="padding: 0.75rem 2rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer;" onclick="closeSpecialistModal()">Discard</button>
                    <button type="submit" class="btn btn-submit" style="padding: 0.75rem 2.5rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: white; font-weight: 600; cursor: pointer; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.2);">Create Specialist</button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeSpecialistModal() {
    const modal = document.getElementById('specialist-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function submitAddSpecialist(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;
    submitBtn.innerText = 'Creating Account...';
    submitBtn.disabled = true;

    fetch('../../api_clinic_add_specialist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showClinicToast(`Specialist added! Account: ${result.email}`, 'success');
            closeSpecialistModal();
            showClinicView('specialists');
        } else {
            showClinicToast(result.error || 'Failed to add specialist', 'error');
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        showClinicToast('Connection error. Please try again.', 'error');
        submitBtn.innerText = originalText;
        submitBtn.disabled = false;
    });
}

// Global variable to store clinic notification settings
let clinicSettings = null;

// Load clinic notification settings from user_settings table
async function loadClinicNotificationSettings() {
    try {
        const res = await fetch('../../api_settings.php?action=get');
        const data = await res.json();
        if (data.success) {
            clinicSettings = data.settings;
            // Update toggle states if on settings page
            const pushToggle = document.getElementById('notif-push-toggle');
            const emailToggle = document.getElementById('notif-email-toggle');
            const apptToggle = document.getElementById('notif-appointment-toggle');
            const systemToggle = document.getElementById('notif-system-toggle');
            if (pushToggle) pushToggle.checked = !!clinicSettings.push_notifications;
            if (emailToggle) emailToggle.checked = !!clinicSettings.email_notifications;
            if (apptToggle) apptToggle.checked = !!clinicSettings.appointment_reminders;
            if (systemToggle) systemToggle.checked = !!clinicSettings.system_alerts;
        }
    } catch (err) {
        console.error('Failed to load notification settings:', err);
    }
}

// Update clinic notification setting
async function updateClinicNotificationSetting(key, value) {
    try {
        const res = await fetch('../../api_settings.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ [key]: value })
        });
        const data = await res.json();
        if (data.success) {
            if (clinicSettings) clinicSettings[key] = value;
            showClinicToast('Notification preference updated', 'success');
        } else {
            showClinicToast('Failed to update setting', 'error');
        }
    } catch (err) {
        console.error('Failed to update notification setting:', err);
        showClinicToast('Network error', 'error');
    }
}

// Utility for clinic toasts
function showClinicToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `clinic-toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${type === 'success' ? '✓' : '⚠'}</span>
            <span class="toast-message">${msg}</span>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}


function showAddPatientModal() {
    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 500px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(13, 148, 136, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 22px; height: 22px;">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Add New Patient</h2>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Invite a new patient to your clinic</p>
                    </div>
                </div>
                <button class="clinic-modal-close" style="background: #f8fafc; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" style="padding: 2rem;">
                <p style="color:#64748b; margin-bottom: 2rem; font-size:0.9rem;">
                    Enter the patient's details. A welcome email will be sent to the parent to complete registration.
                </p>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Child's Full Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="new-patient-child-name" class="premium-light-input" placeholder="e.g. Liam Thompson" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Parent/Guardian Full Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="new-patient-parent-name" class="premium-light-input" placeholder="e.g. Michael Thompson" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Parent's Email Address <span style="color: #ef4444;">*</span></label>
                    <input type="email" id="new-patient-email" class="premium-light-input" placeholder="e.g. michael@example.com" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none;">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Assign to Specialist (Optional)</label>
                    <select id="new-patient-specialist" class="premium-light-input" style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; outline: none; appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="">-- Select Specialist --</option>
                        ${clinicSpecialists ? clinicSpecialists.map(s => `<option value="${s.specialist_id}">Dr. ${s.first_name} ${s.last_name}</option>`).join('') : ''}
                    </select>
                </div>
            </div>
            <div class="clinic-modal-footer" style="padding: 1.5rem 2rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 1rem;">
                <button class="btn" style="padding: 0.75rem 2rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer;" onclick="this.closest('.clinic-modal-overlay').remove()">Cancel</button>
                <button class="btn" style="padding: 0.75rem 2.5rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: white; font-weight: 600; cursor: pointer; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.2);" onclick="submitAddPatient(this)">Add Patient</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function submitAddPatient(btn) {
    const childName = document.getElementById('new-patient-child-name').value.trim();
    const parentName = document.getElementById('new-patient-parent-name').value.trim();
    const parentEmail = document.getElementById('new-patient-email').value.trim();
    const specialistId = document.getElementById('new-patient-specialist').value;

    if (!childName || !parentName || !parentEmail) {
        showClinicToast('Please fill in all required fields.', 'error');
        return;
    }

    try {
        btn.disabled = true;
        btn.textContent = 'Adding...';

        const res = await fetch('../../api_clinic_manage_patient.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                child_name: childName,
                parent_name: parentName,
                parent_email: parentEmail,
                specialist_id: specialistId
            })
        });

        const result = await res.json();

        if (result.success) {
            showClinicToast('Patient added successfully! Registration email sent.', 'success');
            btn.closest('.clinic-modal-overlay').remove();
            refreshClinicData('patients');
        } else {
            showClinicToast(result.error || 'Failed to add patient', 'error');
            btn.disabled = false;
            btn.textContent = 'Add Patient';
        }
    } catch (err) {
        console.error(err);
        showClinicToast('Network error', 'error');
        btn.disabled = false;
        btn.textContent = 'Add Patient';
    }
}


/** ── Specialist Shift Management ────────────────────────────────── */

async function manageSpecialistShifts(id) {
    const spec = clinicSpecialists.find(s => s.specialist_id == id);
    if (!spec) return;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'shifts-modal';
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 700px; width: 95%;">
            <div class="clinic-modal-header" style="border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Dr. ${spec.first_name} ${spec.last_name} – Working Hours</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Manage weekly recurring shifts</p>
                </div>
                <button class="clinic-modal-close" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <div class="clinic-modal-body" style="padding: 2rem; max-height: 60vh; overflow-y: auto;">
                <div id="shifts-container">
                    <div style="text-align:center; padding: 2rem;">Loading shifts...</div>
                </div>
            </div>
            
            <div class="clinic-modal-footer" style="padding: 1.25rem 2rem; border-top: 1px solid var(--border-color); background: #f8fafc; display:flex; justify-content: space-between; align-items: center;">
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Shifts are recurring weekly</p>
                <button class="btn btn-gradient" onclick="showAddShiftModal(${id})">+ Add New Shift</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    loadSpecialistShifts(id);
}

async function loadSpecialistShifts(id) {
    try {
        const clinicId = window.clinicData?.clinic?.clinic_id || 1;
        const res = await fetch(`../../clinic/api/management/slots.php?action=list&doctor_id=${id}&clinic_id=${clinicId}`);
        const result = await res.json();
        
        const container = document.getElementById('shifts-container');
        if (result.success && result.slots.length > 0) {
            renderShiftsList(result.slots, id);
        } else {
            container.innerHTML = `
                <div style="text-align:center; padding: 3rem; color: var(--text-muted);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p>No shifts configured for this specialist.</p>
                </div>
            `;
        }
    } catch (err) {
        console.error(err);
        document.getElementById('shifts-container').innerHTML = 'Error loading shifts.';
    }
}

function renderShiftsList(slots, specialistId) {
    const container = document.getElementById('shifts-container');
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    let html = `
        <table class="clinic-table" style="width:100%; border-collapse: separate; border-spacing: 0 0.5rem;">
            <thead>
                <tr style="background: transparent;">
                    <th style="padding: 0.5rem 1rem; font-size: 0.75rem; color: var(--text-muted);">Day</th>
                    <th style="padding: 0.5rem 1rem; font-size: 0.75rem; color: var(--text-muted);">Start Time</th>
                    <th style="padding: 0.5rem 1rem; font-size: 0.75rem; color: var(--text-muted);">End Time</th>
                    <th style="padding: 0.5rem 1rem; font-size: 0.75rem; color: var(--text-muted);">Duration</th>
                    <th style="padding: 0.5rem 1rem; text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    slots.forEach(slot => {
        html += `
            <tr style="background: white; border-radius: 8px;">
                <td style="padding: 1rem; font-weight: 600;">${dayNames[slot.day_of_week]}</td>
                <td style="padding: 1rem; color: var(--teal-600); font-weight: 500;">${slot.start_time.substring(0, 5)}</td>
                <td style="padding: 1rem; color: var(--teal-600); font-weight: 500;">${slot.end_time.substring(0, 5)}</td>
                <td style="padding: 1rem; color: var(--text-secondary);">${slot.slot_duration} min</td>
                <td style="padding: 1rem; text-align:right;">
                    <button class="btn btn-sm" style="color: #ef4444; background: transparent; padding: 4px;" onclick="deleteShift(${slot.slot_id}, ${specialistId})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `</tbody></table>`;
    container.innerHTML = html;
}

function showAddShiftModal(specialistId) {
    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'add-shift-modal';
    modal.style.zIndex = '1200';
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 400px;">
            <div class="clinic-modal-header">
                <h3>Add Working Shift</h3>
                <button class="clinic-modal-close" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" style="padding: 1.5rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Day of Week</label>
                    <select id="shift-day" class="form-input">
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                        <option value="0">Sunday</option>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" id="shift-start" class="form-input" value="09:00">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" id="shift-end" class="form-input" value="17:00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Slot Duration (min)</label>
                    <input type="number" id="shift-duration" class="form-input" value="30">
                </div>
            </div>
            <div class="clinic-modal-footer" style="padding: 1.25rem; display:flex; gap: 1rem;">
                <button class="btn btn-outline" style="flex:1" onclick="this.closest('.clinic-modal-overlay').remove()">Cancel</button>
                <button class="btn btn-gradient" style="flex:1" onclick="submitAddShift(${specialistId})">Add Shift</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function submitAddShift(specialistId) {
    try {
        const clinicId = window.clinicData?.clinic?.clinic_id || 1;
        const body = {
            doctor_id: specialistId,
            clinic_id: clinicId,
            day_of_week: document.getElementById('shift-day').value,
            start_time: document.getElementById('shift-start').value,
            end_time: document.getElementById('shift-end').value,
            slot_duration: document.getElementById('shift-duration').value
        };
        
        const res = await fetch('../../clinic/api/management/slots.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const result = await res.json();
        
        if (result.success) {
            showClinicToast('Shift added successfully', 'success');
            document.getElementById('add-shift-modal').remove();
            loadSpecialistShifts(specialistId);
        } else {
            showClinicToast(result.error || 'Failed to add shift', 'error');
        }
    } catch (err) {
        console.error(err);
        showClinicToast('Network error', 'error');
    }
}

async function deleteShift(slotId, specialistId) {
    if (!confirm('Are you sure you want to remove this working shift?')) return;
    
    try {
        const res = await fetch(`../../clinic/api/management/slots.php?action=remove&slot_id=${slotId}`, {
            method: 'DELETE'
        });
        const result = await res.json();
        
        if (result.success) {
            showClinicToast('Shift removed', 'success');
            loadSpecialistShifts(specialistId);
        } else {
            showClinicToast(result.error || 'Failed to remove shift', 'error');
        }
    } catch (err) {
        console.error(err);
        showClinicToast('Network error', 'error');
    }
}

/** ─────────────────────────────────────────────────────────────────────────────
 *  SPECIALIST DETAILS PAGE (FULL SCREEN)
 *  ───────────────────────────────────────────────────────────────────────────── */

function getSpecialistDetailsView(id) {
    return `
    <div class="specialist-details-page">
        <div class="page-header-premium" style="background: white; padding: 2rem 2.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <button class="btn-back" onclick="showClinicView('specialists')" style="display: flex; align-items: center; gap: 0.5rem; background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; padding: 0; font-size: 0.9rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Back to Directory
                </button>
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div id="spec-page-avatar" style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #0d9488, #2dd4bf); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 700; box-shadow: 0 10px 25px rgba(13, 148, 136, 0.2);">
                        ?
                    </div>
                    <div>
                        <h1 id="spec-page-name" style="margin: 0; font-size: 2rem; font-weight: 800; color: #0f172a;">Loading...</h1>
                        <p id="spec-page-sub" style="margin: 0.25rem 0 0; font-size: 1rem; color: #64748b; font-weight: 500;">Specialist Profile</p>
                    </div>
                </div>
            </div>
            <div id="spec-page-actions" style="display: none; gap: 1rem;">
                <button id="btn-remove-spec" class="btn" style="padding: 0.75rem 1.5rem; border-radius: 12px; border: 1.5px solid #fee2e2; background: #fef2f2; color: #ef4444; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Remove
                </button>
            </div>
        </div>

        <div class="page-content-premium" style="padding: 2.5rem; display: grid; grid-template-columns: 350px 1fr; gap: 2.5rem;">
            <!-- Left Sidebar: Profile & Contact -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="card-premium" style="background: white; border-radius: 24px; padding: 2rem; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 1.5rem; font-size: 0.85rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Contact Details</h3>
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div style="display: flex; gap: 1rem;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0f9ff; color: #0ea5e9; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div style="overflow: hidden;">
                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Email Address</p>
                                <p id="spec-page-email" style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 600; word-break: break-all;">...</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #22c55e; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Phone Number</p>
                                <p id="spec-page-phone" style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 600;">...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-premium" style="background: white; border-radius: 24px; padding: 2rem; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 1.5rem; font-size: 0.85rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Professional Snapshot</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9; text-align: center;">
                            <p style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #0d9488;" id="spec-stat-patients">0</p>
                            <p style="margin: 0.25rem 0 0; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Patients</p>
                        </div>
                        <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9; text-align: center;">
                            <p style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #f59e0b;" id="spec-stat-rating">0.0</p>
                            <p style="margin: 0.25rem 0 0; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Rating</p>
                        </div>
                        <div style="grid-column: span 2; background: #f8fafc; padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9; text-align: center;">
                            <p style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #6366f1;" id="spec-page-exp">...</p>
                            <p style="margin: 0.25rem 0 0; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Clinical Experience</p>
                        </div>
                    </div>
                </div>

                <div class="card-premium" style="background: white; border-radius: 24px; padding: 2rem; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                    <h3 style="margin: 0 0 1.5rem; font-size: 0.85rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Credentials & Docs</h3>
                    <div id="spec-page-credentials">
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b; line-height: 1.6; font-style: italic;" id="spec-page-cert-text"></p>
                        <div id="spec-page-cert-pdf-link" style="margin-top: 1.25rem;"></div>
                    </div>
                </div>
            </div>

            <!-- Right Area: Appointments & Schedule -->
            <div style="display: flex; flex-direction: column; gap: 2.5rem;">
                <div class="card-premium" style="background: white; border-radius: 24px; padding: 2.5rem; border: 1px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Appointment History</h3>
                        <div id="spec-stat-avail" style="padding: 0.6rem 1.25rem; border-radius: 14px; background: #f0fdf4; color: #16a34a; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 1px solid #dcfce7; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            Free Slots: 0
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="clinic-table" id="spec-appt-table" style="width: 100%; border-collapse: separate; border-spacing: 0 0.75rem;">
                            <thead>
                                <tr style="text-align: left;">
                                    <th style="padding: 0 1rem 0.5rem; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Patient / Child</th>
                                    <th style="padding: 0 1rem 0.5rem; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Type</th>
                                    <th style="padding: 0 1rem 0.5rem; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Date & Time</th>
                                    <th style="padding: 0 1rem 0.5rem; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Payment</th>
                                    <th style="padding: 0 1rem 0.5rem; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Slots injected here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-premium" style="background: white; border-radius: 24px; padding: 2.5rem; border: 1px solid #f1f5f9;">
                    <h3 style="margin: 0 0 1.5rem; font-size: 1.25rem; font-weight: 700; color: #0f172a; text-align: center;">Choose your appointment</h3>
                    <div id="spec-page-slots" style="margin: 0 -2.5rem -2.5rem; padding: 0 2.5rem 2.5rem;">
                        <!-- Slots injected here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
}

async function renderSpecialistDetailsPage(id) {
    try {
        const response = await fetch(`../../api_get_specialist_details.php?specialist_id=${id}`);
        if (!response.ok) throw new Error('Failed to fetch');
        const result = await response.json();

        if (!result.success) {
            showClinicToast(result.error || 'Failed to load details', 'error');
            return;
        }

        const p = result.profile;
        const appts = result.appointments || [];
        const slots = result.slots || [];
        const stats = result.stats || {};
        const avail = result.availability || {};

        // 1. Header & Profile
        document.getElementById('spec-page-name').textContent = `Dr. ${p.first_name} ${p.last_name}`;
        document.getElementById('spec-page-avatar').textContent = `${p.first_name[0]}${p.last_name[0]}`.toUpperCase();
        document.getElementById('spec-page-sub').textContent = p.specialization;
        document.getElementById('spec-page-email').textContent = p.email || 'N/A';
        document.getElementById('spec-page-phone').textContent = p.phone || '+20 102 345 6789';
        document.getElementById('spec-page-exp').textContent = `${p.experience_years || 0} Years`;

        // Action buttons
        const actions = document.getElementById('spec-page-actions');
        actions.style.display = 'flex';
        document.getElementById('btn-remove-spec').onclick = () => confirmDeleteSpecialist(id, `${p.first_name} ${p.last_name}`);

        // 2. Stats
        document.getElementById('spec-stat-patients').textContent = stats.total_patients || 0;
        document.getElementById('spec-stat-rating').textContent = parseFloat(stats.avg_rating || 0).toFixed(1);
        document.getElementById('spec-stat-avail').textContent = `Free Slots: ${avail.free || 0}`;

        // 3. Credentials
        document.getElementById('spec-page-cert-text').textContent = p.certification_text || 'No certification notes available.';
        const certLink = document.getElementById('spec-page-cert-pdf-link');
        if (p.certification_pdf) {
            certLink.innerHTML = `
                <a href="../../${p.certification_pdf}" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #0d9488; font-weight: 700; font-size: 0.9rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6m-3 3l3 3 3-3"/></svg>
                    Download Certification PDF
                </a>
            `;
        } else {
            certLink.innerHTML = '<p style="font-size: 0.8rem; color: #94a3b8;">No document uploaded.</p>';
        }

        // 4. Appointments Table
        const tbody = document.querySelector('#spec-appt-table tbody');
        if (appts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: #64748b;">No appointment history found.</td></tr>';
        } else {
            tbody.innerHTML = appts.map(a => {
                const dt = new Date(a.scheduled_at);
                const dateStr = dt.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                const parentName = `${a.parent_first_name} ${a.parent_last_name || ''}`;
                const childName = a.child_first_name ? `${a.child_first_name} ${a.child_last_name || ''}` : 'N/A';
                
                const method = (a.payment_method || 'Cash').toLowerCase();
                const isPaid = (a.payment_status || '').toLowerCase() === 'completed';
                const pColor = method.includes('credit') ? '#2563eb' : '#059669';
                const pBg = method.includes('credit') ? 'rgba(37, 99, 235, 0.1)' : 'rgba(5, 150, 105, 0.1)';

                const statusLower = (a.status || 'scheduled').toLowerCase();
                let statusClass = "status-active";
                if (statusLower === 'cancelled') statusClass = "status-danger";
                else if (statusLower === 'pending') statusClass = "status-warning";

                return `
                    <tr style="background: #f8fafc; border-radius: 12px;">
                        <td style="padding: 1rem; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                            <div style="font-weight:700; color:#1e293b;">${childName}</div>
                            <div style="font-size:0.75rem; color:#64748b;">Parent: ${parentName}</div>
                        </td>
                        <td style="padding: 1rem;"><span style="font-size:0.85rem; font-weight:600; color:#475569;">${a.type || 'Onsite'}</span></td>
                        <td style="padding: 1rem;"><span style="font-size:0.85rem; color:#64748b;">${dateStr}</span></td>
                        <td style="padding: 1rem;">
                            <span style="font-size:0.7rem; font-weight:800; padding:4px 10px; border-radius:8px; background:${pBg}; color:${pColor}; text-transform:uppercase;">${a.payment_method || 'Cash'}</span>
                            ${isPaid ? '<span style="color:#059669; margin-left:5px;">●</span>' : ''}
                        </td>
                        <td style="padding: 1rem; border-top-right-radius: 12px; border-bottom-right-radius: 12px;"><span class="status-badge ${statusClass}" style="font-weight:700;">${a.status}</span></td>
                    </tr>
                `;
            }).join('');
        }

        // 5. Schedule Slots
        renderSpecialistAvailability(slots, appts);

    } catch (err) {
        console.error(err);
        showClinicToast('Network error while loading specialist', 'error');
    }
}

function renderSpecialistAvailability(slots, appointments) {
    const container = document.getElementById('spec-page-slots');
    if (!container) return;

    if (!slots || slots.length === 0) {
        container.innerHTML = `<div style="text-align:center; padding:3rem; background:#f8fafc; border-radius:20px; color:#94a3b8;">No weekly schedule configured for this specialist.</div>`;
        return;
    }

    const dayNamesShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    let html = `
        <div class="availability-slider-container" style="position: relative; background: #f4f7f9; border-radius: 24px; padding: 3rem 1.5rem; margin-top: 1rem;">
            <button onclick="scrollAvailabilitySlots('prev')" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 48px; height: 48px; border-radius: 14px; background: white; border: 1px solid #e2e8f0; color: #0070ea; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 2; transition: all 0.2s;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            
            <div id="availability-scroll-box" style="display: flex; gap: 1.25rem; overflow-x: auto; scroll-behavior: smooth; padding: 0 3rem; scrollbar-width: none; -ms-overflow-style: none;">
    `;

    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const dow = date.getDay();
        const dateStr = date.toISOString().split('T')[0];
        
        let label = "";
        if (i === 0) label = "Today";
        else if (i === 1) label = "Tomorrow";
        else {
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            label = `${dayNamesShort[dow]} ${m}/${d}`;
        }

        // Generate Slots from Schedule
        const daySchedule = slots.filter(s => parseInt(s.day_of_week) === dow);
        let timeSlots = [];
        
        daySchedule.forEach(sl => {
            const startTime = sl.start_time.substring(0, 5);
            const endTime = sl.end_time.substring(0, 5);
            const duration = parseInt(sl.slot_duration) || 30;
            
            let current = new Date(`${dateStr}T${startTime}`);
            const end = new Date(`${dateStr}T${endTime}`);
            
            while (current < end) {
                const rawTime = current.toTimeString().substring(0, 5);
                const displayTime = current.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                
                const isBooked = appointments.some(a => {
                    if (!a.scheduled_at) return false;
                    const apDate = a.scheduled_at.split(' ')[0];
                    const apTime = a.scheduled_at.split(' ')[1].substring(0, 5);
                    return apDate === dateStr && apTime === rawTime && !['cancelled', 'rejected'].includes(a.status?.toLowerCase());
                });

                if (!isBooked) timeSlots.push(displayTime);
                current.setMinutes(current.getMinutes() + duration);
            }
        });

        // Header Color based on label
        let headerBg = '#7dbef8'; // Light blue default
        if (i === 1) headerBg = '#0070ea'; // Dark blue for Tomorrow

        html += `
            <div class="day-slot-card" style="min-width: 180px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; flex-shrink: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="day-header" style="background: ${headerBg}; padding: 14px; text-align: center; color: white; font-weight: 700; font-size: 1rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
                    ${label}
                </div>
                <div class="day-body" style="padding: 1.5rem 1rem; display: flex; flex-direction: column; gap: 1rem; align-items: center; min-height: 280px; justify-content: ${timeSlots.length > 0 ? 'flex-start' : 'center'};">
                    ${timeSlots.length > 0 ? 
                        timeSlots.slice(0, 7).map(t => `<div style="color: #0070ea; font-weight: 700; font-size: 0.95rem; cursor: pointer; padding: 2px;">${t}</div>`).join('') :
                        `<div style="color: #94a3b8; font-size: 0.95rem; text-align: center; font-weight: 600; line-height: 1.6;">No Available<br>Appointments</div>`
                    }
                    ${timeSlots.length > 7 ? `<div style="color: #0070ea; font-weight: 800; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; text-decoration: underline;">More</div>` : ''}
                </div>
            </div>
        `;
    }

    html += `
            </div>
            
            <button onclick="scrollAvailabilitySlots('next')" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); width: 48px; height: 48px; border-radius: 14px; background: white; border: 1px solid #e2e8f0; color: #0070ea; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 2; transition: all 0.2s;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    `;

    container.innerHTML = html;
}

function scrollAvailabilitySlots(dir) {
    const box = document.getElementById('availability-scroll-box');
    if (!box) return;
    const move = dir === 'next' ? 200 : -200;
    box.scrollBy({ left: move, behavior: 'smooth' });
}

/** ─────────────────────────────────────────────────────────────────────────────
 *  PATIENT RECORDS POPUP
 *  ───────────────────────────────────────────────────────────────────────────── */

async function viewPatientRecords(childId, childName) {
    // Show loading state or modal immediately
    const modalId = "patient-records-modal";
    let modal = document.getElementById(modalId);
    if (modal) modal.remove();

    modal = document.createElement("div");
    modal.id = modalId;
    modal.className = "clinic-modal-overlay active";
    modal.style.zIndex = "9999";
    modal.innerHTML = `
        <div class="clinic-modal light-premium-modal" style="max-width: 900px; width: 95%; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
            <div class="clinic-modal-header" style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(13, 148, 136, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 22px; height: 22px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">${childName}'s Health Records</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Comprehensive history and appointment details</p>
                    </div>
                </div>
                <button class="clinic-modal-close" style="background: #f8fafc; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b;" onclick="this.closest('.clinic-modal-overlay').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" style="padding: 2rem; max-height: 70vh; overflow-y: auto;" id="patient-records-content">
                <div style="text-align: center; padding: 3rem;">
                    <div class="spinner" style="margin: 0 auto 1rem;"></div>
                    <p style="color: #64748b;">Fetching latest medical records...</p>
                </div>
            </div>
            <div class="clinic-modal-footer" style="padding: 1.25rem 2rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <button class="btn" style="padding: 0.75rem 1.5rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-weight: 600; cursor: pointer;" onclick="this.closest('.clinic-modal-overlay').remove()">Close Records</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    try {
        const res = await fetch(`../../api_get_child_full_profile.php?child_id=${childId}`);
        const result = await res.json();
        
        if (!result.success) {
            document.getElementById("patient-records-content").innerHTML = `<div style="padding: 2rem; text-align: center; color: #ef4444;">Error: ${result.error || "Failed to load records"}</div>`;
            return;
        }

        const appts = result.appointments || [];
        const profile = result.profile || {};
        const basic = profile.basic || {};

        let apptHtml = appts.length === 0 ? "<p style='text-align:center; color:#94a3b8; padding:2rem;'>No appointment history found.</p>" : `
            <div class="records-table-wrap" style="border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Date & Time</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Specialist</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Payment</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${appts.map(a => {
                            const dt = new Date(a.scheduled_at);
                            const dateStr = dt.toLocaleDateString([], {month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit', hour12: true});
                            
                            const method = (a.payment_method || "Cash").toLowerCase();
                            const isPaid = (a.payment_status || "").toLowerCase() === "completed" || (a.payment_status || "").toLowerCase() === "paid";
                            const pColor = method.includes("credit") || method.includes("card") ? "#2563eb" : "#059669";
                            const pBg = method.includes("credit") || method.includes("card") ? "#eff6ff" : "#f0fdf4";

                            const status = (a.status || "Pending").toLowerCase();
                            let sClass = "status-active";
                            if (status === "cancelled" || status === "refunded") sClass = "status-danger";
                            if (status === "pending") sClass = "status-warning";

                            let cancelInfo = "";
                            if (status === "cancelled" && a.cancelled_by) {
                                const source = a.cancelled_by === "specialist" ? "Specialist" : (a.cancelled_by === "clinic" ? "Clinic" : "Patient");
                                cancelInfo = `<div style="font-size: 0.65rem; color: #ef4444; font-weight:700; margin-top:4px;">Cancelled by ${source}</div>`;
                            }

                            return `
                                <tr style="border-top: 1px solid #f1f5f9;">
                                    <td style="padding: 1rem; font-size: 0.9rem; color: #1e293b; font-weight: 600;">${dateStr}</td>
                                    <td style="padding: 1rem; font-size: 0.85rem; color: #475569;">Dr. ${a.spec_fname || "Unknown"} ${a.spec_lname || ""}</td>
                                    <td style="padding: 1rem;">
                                        <div style="display:flex; flex-direction:column; gap:4px;">
                                            <span style="font-size:0.7rem; font-weight:800; background:${pBg}; color:${pColor}; padding:2px 8px; border-radius:6px; display:inline-block; width:fit-content; text-transform:uppercase;">${a.payment_method || "Cash"}</span>
                                            <div style="display:flex; align-items:center; gap:4px;">
                                                <div style="width:6px; height:6px; border-radius:50%; background:${isPaid ? "#22c55e" : "#f59e0b"}"></div>
                                                <span style="font-size:0.7rem; font-weight:600; color:${isPaid ? "#22c55e" : "#f59e0b"}">${isPaid ? "Paid" : "Unpaid"}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span class="status-badge ${sClass}" style="font-weight:700; font-size:0.75rem;">${a.status}</span>
                                        ${cancelInfo}
                                    </td>
                                </tr>
                            `;
                        }).join("")}
                    </tbody>
                </table>
            </div>
        `;

        document.getElementById("patient-records-content").innerHTML = `
            <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2rem;">
                <!-- Sidebar Extra Info -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9;">
                        <h4 style="margin: 0 0 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Patient Profile</h4>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <p style="margin: 0; font-size: 0.7rem; color: #94a3b8; font-weight: 600;">Full Name</p>
                                <p style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700;">${childName}</p>
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.7rem; color: #94a3b8; font-weight: 600;">Age (Months)</p>
                                <p style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700;">${basic.age_months || "N/A"}</p>
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.7rem; color: #94a3b8; font-weight: 600;">Parent Contact</p>
                                <p style="margin: 0; font-size: 0.9rem; color: #1e293b; font-weight: 700;">${basic.parent_first_name} ${basic.parent_last_name}</p>
                                <p style="margin: 2px 0 0; font-size: 0.8rem; color: #64748b;">${basic.parent_phone || "No phone"}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Records Area -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h4 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a;">Recent Appointments</h4>
                        <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Found ${appts.length} appointments</span>
                    </div>
                    ${apptHtml}
                </div>
            </div>
        `;

    } catch (err) {
        console.error(err);
        document.getElementById("patient-records-content").innerHTML = `<div style="padding: 2rem; text-align: center; color: #ef4444;">Network Error: Could not connect to API.</div>`;
    }
}
