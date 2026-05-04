<?php
$file = 'c:\xampp\htdocs\Bright Steps Website\scripts\doctor-dashboard.js';
$content = file_get_contents($file);

$topbarCode = <<<EOT

// ─── Topbar & Notifications ─────────────────────────
function renderDoctorTopBar() {
    const main = document.querySelector('.dashboard-main');
    if (!main || document.getElementById('dashboard-topbar')) return;

    const initial = (typeof SESSION_DOCTOR_NAME !== 'undefined' && SESSION_DOCTOR_NAME) ? SESSION_DOCTOR_NAME[0].toUpperCase() : 'D';

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
            <div id="topbar-notification" onclick="toggleDoctorNotifDropdown()" style="position:relative; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="2" width="22" height="22" onmouseover="this.style.stroke='var(--text-primary)'" onmouseout="this.style.stroke='var(--text-secondary)'">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span id="topbar-notif-badge" style="display:none; position:absolute; top:-2px; right:-2px; width:8px; height:8px; background:#ef4444; border-radius:50%; border:2px solid var(--bg-card);"></span>
            </div>
            <div class="topbar-notification-dropdown" id="doctor-notif-dropdown" style="display:none; position:absolute; top:55px; right:2rem; width:380px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.08); overflow:hidden; z-index:1000; backdrop-filter:blur(20px);">
                <div style="padding:1.25rem 1.5rem; background:linear-gradient(135deg, #0d9488, #0891b2); display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <h4 style="margin:0; font-size:1rem; font-weight:700; color:#fff;">Notifications</h4>
                        <span id="topbar-notif-count" style="display:none; background:rgba(255,255,255,0.25); color:#fff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px;"></span>
                    </div>
                    <div style="display:flex; gap:0.75rem;">
                        <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="loadDoctorNotifications()">Refresh</span>
                        <span style="font-size:0.75rem; color:rgba(255,255,255,0.8); font-weight:600; cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'" onclick="markAllDoctorNotifRead()">Mark all read</span>
                    </div>
                </div>
                <div id="doctor-notif-content" style="max-height:420px; overflow-y:auto;">
                    <div style="padding:3rem 1.5rem; text-align:center;">
                        <div style="width:48px; height:48px; background:var(--bg-secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </div>
                        <p style="color:var(--text-secondary); font-size:0.85rem; margin:0;">Loading notifications...</p>
                    </div>
                </div>
            </div>
            <div id="topbar-avatar" onclick="navigateToView('settings')" style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, #0d9488, #0891b2); color:white; display:flex; align-items:center; justify-content:center; font-weight:600; cursor:pointer; font-size:0.9rem;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                \${initial}
            </div>
        </div>
    `;
    
    const wrapper = main.parentElement;
    wrapper.insertBefore(topbar, main);
    main.style.flex = '1';
    main.style.overflowY = 'auto';

    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('doctor-notif-dropdown');
        const trigger = document.getElementById('topbar-notification');
        if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Initial load
    loadDoctorNotifications();
}

async function toggleDoctorNotifDropdown() {
    const dropdown = document.getElementById('doctor-notif-dropdown');
    if (!dropdown) return;
    const isVisible = dropdown.style.display !== 'none';
    if (!isVisible) {
        await loadDoctorNotifications();
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

async function loadDoctorNotifications() {
    const contentDiv = document.getElementById('doctor-notif-content');
    if (!contentDiv) return;

    try {
        const res = await fetch('api_notifications.php?action=get&user_id=' + SPECIALIST_ID);
        const data = await res.json();
        
        let notifications = [];
        let unreadCount = 0;
        
        if (data.success && data.notifications) {
            notifications = data.notifications;
            unreadCount = data.unread_count || 0;
        }

        const badge = document.getElementById('topbar-notif-badge');
        if (badge) badge.style.display = unreadCount > 0 ? 'block' : 'none';
        
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
                    'new_message': { bg: 'rgba(168,85,247,0.12)', color: '#7c3aed', path: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' },
                    'report_shared': { bg: 'rgba(59,130,246,0.12)', color: '#2563eb', path: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>' },
                    'system': { bg: 'rgba(100,116,139,0.12)', color: '#475569', path: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' }
                };
                const i = icons[type] || icons['system'];
                return `<div style="width:38px;height:38px;border-radius:12px;background:\${i.bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="\${i.color}" stroke-width="2">\${i.path}</svg></div>`;
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
                <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border-color); display:flex; gap:0.875rem; align-items:flex-start; transition:background 0.15s ease; cursor:pointer; \${n.is_read == 0 ? 'background:rgba(13,148,136,0.04);' : ''}" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='\${n.is_read == 0 ? 'rgba(13,148,136,0.04)' : 'transparent'}'">
                    \${getIcon(n.type)}
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                            <span style="font-weight:600; font-size:0.9rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">\${n.title || 'Notification'}</span>
                            <span style="font-size:0.7rem; color:var(--text-muted); white-space:nowrap;">\${timeAgo(n.created_at)}</span>
                        </div>
                        <p style="margin:0; font-size:0.82rem; color:var(--text-secondary); line-height:1.4;">\${n.message || ''}</p>
                        \${n.is_read == 0 ? '<div style="width:6px;height:6px;background:#0d9488;border-radius:50%;position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);"></div>' : ''}
                    </div>
                </div>
            `).join('');

            html += `
                <div style="padding:1rem 1.5rem; text-align:center; background:var(--bg-card); border-top:1px solid var(--border-color);">
                    <span style="font-size:0.85rem; font-weight:600; color:#0d9488; cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'" onclick="openAllDoctorNotificationsModal()">View all notifications →</span>
                </div>
            `;

            contentDiv.innerHTML = html;
        }
    } catch (err) {
        console.error('Failed to load notifications:', err);
    }
}

async function markAllDoctorNotifRead() {
    try {
        await fetch('api_notifications.php?action=read&user_id=' + SPECIALIST_ID, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
        const badge = document.getElementById('topbar-notif-badge');
        if (badge) badge.style.display = 'none';
        const countBadge = document.getElementById('topbar-notif-count');
        if (countBadge) countBadge.style.display = 'none';
        await loadDoctorNotifications();
    } catch (err) {
        console.error(err);
    }
}

function openAllDoctorNotificationsModal() {
    const dropdown = document.getElementById('doctor-notif-dropdown');
    if (dropdown) dropdown.style.display = 'none';
    
    fetch('api_notifications.php?action=get&user_id=' + SPECIALIST_ID)
    .then(r => r.json())
    .then(data => {
        let notifications = data.notifications || [];
        
        const getIcon = (type) => {
            const icons = {
                'appointment_reminder': { bg: 'rgba(13,148,136,0.12)', color: '#0d9488', path: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
                'new_message': { bg: 'rgba(168,85,247,0.12)', color: '#7c3aed', path: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' },
                'report_shared': { bg: 'rgba(59,130,246,0.12)', color: '#2563eb', path: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>' },
                'system': { bg: 'rgba(100,116,139,0.12)', color: '#475569', path: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' }
            };
            const i = icons[type] || icons['system'];
            return `<div style="width:44px;height:44px;border-radius:14px;background:\${i.bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="\${i.color}" stroke-width="2">\${i.path}</svg></div>`;
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
            <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; gap:1rem; align-items:flex-start; transition:background 0.15s; cursor:pointer; position:relative; \${n.is_read == 0 ? 'background:rgba(13,148,136,0.04);' : ''}" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='\${n.is_read == 0 ? 'rgba(13,148,136,0.04)' : 'transparent'}'">
                \${getIcon(n.type)}
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                        <span style="font-weight:600; font-size:1rem; color:var(--text-primary);">\${n.title || 'Notification'}</span>
                        <span style="font-size:0.78rem; color:var(--text-muted);">\${timeAgo(n.created_at)}</span>
                    </div>
                    <p style="margin:0; font-size:0.9rem; color:var(--text-secondary); line-height:1.5;">\${n.message || ''}</p>
                </div>
                \${n.is_read == 0 ? '<div style="width:8px;height:8px;background:#0d9488;border-radius:50%;position:absolute;right:1.5rem;top:1.5rem;"></div>' : ''}
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
        modal.className = 'dr-modal-overlay active';
        modal.innerHTML = `
            <div class="dr-modal" style="max-width:650px; width:95%; max-height:85vh; display:flex; flex-direction:column; padding:0; border-radius:12px; overflow:hidden;">
                <div class="dr-modal-header" style="background:linear-gradient(135deg, #0d9488, #0891b2); padding:1.5rem 2rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h2 style="margin:0; font-size:1.3rem; color:#fff;">All Notifications</h2>
                        <p style="margin:0.25rem 0 0; font-size:0.85rem; color:rgba(255,255,255,0.75);">\${notifications.length} notification\${notifications.length !== 1 ? 's' : ''}</p>
                    </div>
                    <button onclick="this.closest('.dr-modal-overlay').remove()" style="background:transparent; border:none; color:#fff; cursor:pointer;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div style="flex:1; overflow-y:auto; background:var(--bg-card);">
                    \${notifRows}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    });
}

EOT;

$content = str_replace('// ─── Utilities ──────────────────────────────────────', $topbarCode . "\n// ─── Utilities ──────────────────────────────────────", $content);
file_put_contents($file, $content);
echo "Injected notification functions into doctor-dashboard.js";
?>
