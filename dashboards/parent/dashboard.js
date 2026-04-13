// Dashboard JavaScript
(function () {
    var children = (window.dashboardData || {}).children || [];

    // Navigation items configuration
    const navItems = [
        { id: 'home', label: 'Home', icon: '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' },
        { id: 'profile', label: 'Child Profile', icon: '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>' },
        { id: 'growth', label: 'Growth', icon: '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>' },
        { id: 'speech', label: 'Speech', icon: '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/>' },
        { id: 'motor', label: 'Motor Skills', icon: '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>' },
        { id: 'activities', label: 'Activities', icon: '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>' },
        { id: 'clinic', label: 'Book Appointments', icon: '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M10 14h4"/><path d="M12 12v4"/>' },
        { id: 'reports', label: 'Reports', icon: '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16l4-8 4 4 4-6"/>' },
        { id: 'messages', label: 'Messages', icon: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/>' }
    ];

    // Initialize navigation
    function initNav() {
        const navContainer = document.getElementById('sidebar-nav');
        if (!navContainer) return;

        navContainer.innerHTML = navItems.map(item => `
            <button class="nav-item ${item.id === 'home' ? 'active' : ''}" data-view="${item.id}">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${item.icon}
                </svg>
                <span>${item.label}</span>
            </button>
        `).join('');

        // Render Top Bar
        renderTopBar();

        // Load unread notification count
        loadNotifCount();

        // Streak check-in
        streakCheckIn();

        // Add click handlers
        navContainer.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function () {
                const view = this.dataset.view;
                switchView(view);
            });
        });
    }

    // ── Top Bar ──────────────────────────────────────────────
    function renderTopBar() {
        const main = document.querySelector('.dashboard-main');
        if (!main || document.getElementById('dashboard-topbar')) return;

        const d = window.dashboardData || {};
        const p = d.parent || {};
        const child = (d.children || [])[0] || {};
        const streaks = d.streaks || {};
        const badges = child.badges || [];
        const dailyStreak = streaks.daily_login ? streaks.daily_login.current_count : 0;
        const badgeCount = badges.length;
        const totalPoints = child.total_points || 0;
        const initials = ((p.fname || 'U')[0] + (p.lname || 'S')[0]).toUpperCase();

        const topbar = document.createElement('div');
        topbar.id = 'dashboard-topbar';
        topbar.className = 'dashboard-topbar';
        topbar.innerHTML = `
            <div class="topbar-left">
                <div class="topbar-streak" title="Daily Login Streak">
                    <div class="topbar-streak-icon">🔥</div>
                    <div class="topbar-streak-info">
                        <span class="topbar-streak-count" id="topbar-streak-count">${dailyStreak}</span>
                        <span class="topbar-streak-label">Day Streak</span>
                    </div>
                </div>
                <div class="topbar-divider"></div>
                <div class="topbar-badges" title="Badges Earned" onclick="openBadgesPopup()" style="cursor:pointer">
                    <div class="topbar-badge-icon">🏆</div>
                    <div class="topbar-badge-info">
                        <span class="topbar-badge-count" id="topbar-badge-count">${badgeCount}</span>
                        <span class="topbar-badge-label">Badges</span>
                    </div>
                </div>
                <div class="topbar-divider"></div>
                <div class="topbar-points" title="Points Wallet" style="cursor:pointer" onclick="openPointsWalletPopup()">
                    <div class="topbar-points-icon">💎</div>
                    <div class="topbar-points-info">
                        <span class="topbar-points-count" id="topbar-points-count">${totalPoints}</span>
                        <span class="topbar-points-label">Points</span>
                    </div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-notification" id="topbar-notification" onclick="toggleNotifDropdown()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="topbar-notif-badge" id="topbar-notif-badge" style="display:none">0</span>
                </div>
                <div class="topbar-notification-dropdown" id="notif-dropdown" style="display:none">
                    <div class="notif-dropdown-header">
                        <h4>Notifications</h4>
                        <button onclick="markAllRead();loadTopBarNotifs()" class="notif-mark-all">Mark all read</button>
                    </div>
                    <div id="notif-dropdown-list" class="notif-dropdown-list">Loading...</div>
                    <div class="notif-dropdown-footer" onclick="openNotificationsPopup();toggleNotifDropdown()">
                        View All Notifications
                    </div>
                </div>
                <div class="topbar-avatar" onclick="switchView('settings')" title="Settings">
                    ${initials}
                </div>
            </div>
        `;
        main.insertBefore(topbar, main.firstChild);

        // Close dropdown on outside click
        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('notif-dropdown');
            const trigger = document.getElementById('topbar-notification');
            if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    window.toggleNotifDropdown = function () {
        const dropdown = document.getElementById('notif-dropdown');
        if (!dropdown) return;
        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            loadTopBarNotifs();
        } else {
            dropdown.style.display = 'none';
        }
    };

    async function loadTopBarNotifs() {
        const list = document.getElementById('notif-dropdown-list');
        if (!list) return;
        try {
            const res = await fetch('../../api_notifications.php?action=list&limit=5');
            const data = await res.json();
            const notifs = data.notifications || [];
            const typeIcons = { appointment_reminder: '📅', payment_success: '💳', growth_alert: '📏', milestone: '🏆', system: '🔔' };
            if (notifs.length === 0) {
                list.innerHTML = '<div class="notif-dropdown-empty">No notifications</div>';
                return;
            }
            list.innerHTML = notifs.map(n => {
                const dt = new Date(n.created_at);
                const timeStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const icon = typeIcons[n.type] || '🔔';
                const unread = n.is_read == 0 ? 'notif-unread' : '';
                return `<div class="notif-dropdown-item ${unread}" onclick="markNotifRead(${n.notification_id});loadTopBarNotifs();loadNotifCount()">
                    <span class="notif-item-icon">${icon}</span>
                    <div class="notif-item-content">
                        <div class="notif-item-title">${n.title}</div>
                        <div class="notif-item-time">${timeStr}</div>
                    </div>
                </div>`;
            }).join('');
        } catch (e) {
            list.innerHTML = '<div class="notif-dropdown-empty">Error loading</div>';
        }
    }

    async function streakCheckIn() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[0] || null;
        if (!child) return;
        try {
            const res = await fetch('../../api_streaks.php?action=check-in', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ child_id: child.child_id })
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById('topbar-streak-count');
                if (el) el.textContent = data.current_streak || 0;
                // Show new badge notifications
                if (data.new_badges && data.new_badges.length > 0) {
                    data.new_badges.forEach(b => showBadgeToast(b));
                    const bc = document.getElementById('topbar-badge-count');
                    if (bc) bc.textContent = parseInt(bc.textContent || 0) + data.new_badges.length;
                }
            }
        } catch (e) { /* silent */ }
    }

    function showBadgeToast(badgeName) {
        const toast = document.createElement('div');
        toast.className = 'badge-toast';
        toast.innerHTML = `<span class="badge-toast-icon">🏆</span><div><strong>New Badge!</strong><br>${badgeName}</div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
    }

    function showToast(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'badge-toast';
        let bg = type === 'success' ? '#22c55e' : (type === 'error' ? '#ef4444' : '#64748b');
        toast.style.cssText = `position:fixed;bottom:2rem;left:50%;transform:translateX(-50%) translateY(100px);background:${bg};color:#fff;padding:1rem 1.5rem;border-radius:12px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);z-index:10000;display:flex;align-items:center;gap:0.75rem;font-weight:600;opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);`;

        let icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');
        toast.innerHTML = `<span style="font-size:1.25rem;">${icon}</span><div style="font-size:0.95rem;">${msg}</div>`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(0)'; toast.style.opacity = '1'; }, 50);
        setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(20px)'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 5000);
    }

    window.openBadgesPopup = function () {
        let existing = document.getElementById('badges-modal');
        if (existing) existing.remove();

        const d = window.dashboardData || {};
        const child = (d.children || [])[window._selectedChildIndex || 0] || {};
        const earnedBadges = child.badges || [];
        const earnedNames = earnedBadges.map(b => b.name);

        const allBadges = [
            { name: 'Rising Star', desc: '3-day streak', icon: '⭐', color: 'linear-gradient(135deg, #fde047, #f59e0b)' },
            { name: 'Consistency King', desc: '7-day streak', icon: '👑', color: 'linear-gradient(135deg, #c084fc, #9333ea)' },
            { name: 'Super Parent', desc: '30-day streak', icon: '🏆', color: 'linear-gradient(135deg, #fca5a5, #ef4444)' },
            { name: 'Weekly Champion', desc: '5 activities in a week', icon: '🥇', color: 'linear-gradient(135deg, #93c5fd, #2563eb)' },
            { name: 'Monthly Master', desc: '20 activities in a month', icon: '🎯', color: 'linear-gradient(135deg, #86efac, #16a34a)' },
            { name: 'First Steps', desc: 'Complete first activity', icon: '👣', color: 'linear-gradient(135deg, #fdba74, #ea580c)' },
            { name: 'Growth Tracker', desc: 'Log first growth record', icon: '📏', color: 'linear-gradient(135deg, #a78bfa, #4f46e5)' },
            { name: 'Article Reader', desc: 'Read your first article', icon: '📖', color: 'linear-gradient(135deg, #67e8f9, #0891b2)' },
            { name: 'Bookworm', desc: 'Read 10 articles', icon: '📚', color: 'linear-gradient(135deg, #fda4af, #e11d48)' },
            { name: 'Speech Explorer', desc: 'Complete 5 speech activities', icon: '🗣️', color: 'linear-gradient(135deg, #c4b5fd, #7c3aed)' },
            { name: 'Motor Master', desc: 'Complete 5 motor skill activities', icon: '🤸', color: 'linear-gradient(135deg, #6ee7b7, #059669)' },
            { name: 'Health Champion', desc: 'Log 5 growth measurements', icon: '💪', color: 'linear-gradient(135deg, #fcd34d, #d97706)' },
            { name: 'Clinic Regular', desc: 'Book 3 clinic appointments', icon: '🩺', color: 'linear-gradient(135deg, #a5b4fc, #4f46e5)' },
            { name: 'Message Pro', desc: 'Send 10 messages to specialists', icon: '💬', color: 'linear-gradient(135deg, #99f6e4, #0d9488)' },
            { name: 'Game Master', desc: 'Complete 5 mini-games', icon: '🎮', color: 'linear-gradient(135deg, #f0abfc, #a855f7)' }
        ];

        const gridHtml = allBadges.map((b, i) => {
            const isEarned = earnedNames.includes(b.name);
            const opacity = isEarned ? '1' : '0.4';
            const filter = isEarned ? 'none' : 'grayscale(100%)';
            const bgClass = isEarned ? '' : 'unearned-badge';

            const earnedObj = earnedBadges.find(eb => eb.name === b.name);
            const earnedDate = earnedObj && earnedObj.redeemed_at ? new Date(earnedObj.redeemed_at).toLocaleDateString() : '';
            const statusHtml = isEarned
                ? `<div style="font-size:0.7rem;color:#15803d;font-weight:700;display:flex;align-items:center;justify-content:center;gap:0.25rem;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> EARNED ${earnedDate}</div>`
                : `<div style="font-size:0.7rem;color:var(--slate-400);font-weight:700;display:flex;align-items:center;justify-content:center;gap:0.25rem;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> LOCKED</div>`;

            return `
            <div class="${bgClass}" style="position:relative;background:rgba(255,255,255,0.7);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.5);border-radius:20px;padding:1.5rem;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.03);opacity:${opacity};filter:${filter};transition:all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);animation: fadeUpIn 0.5s ease forwards ${i * 0.05}s;opacity:0;transform:translateY(20px);">
                <div style="width:70px;height:70px;border-radius:50%;background:${b.color};margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;box-shadow:0 8px 16px rgba(0,0,0,0.1);position:relative;z-index:2;">
                    ${b.icon}
                </div>
                <h4 style="font-size:1.1rem;font-weight:800;color:var(--slate-900);margin-bottom:0.25rem;font-family:'Inter',sans-serif;">${b.name}</h4>
                <p style="font-size:0.8rem;color:var(--slate-500);margin-bottom:1rem;line-height:1.4;">${b.desc}</p>
                <div style="background:${isEarned ? '#dcfce7' : '#f1f5f9'};padding:0.35rem 0.75rem;border-radius:999px;display:inline-block;border:1px solid ${isEarned ? '#bbf7d0' : '#e2e8f0'};">
                    ${statusHtml}
                </div>
            </div>`;
        }).join('');

        const modal = document.createElement('div');
        modal.id = 'badges-modal';
        modal.innerHTML = `
        <style>
            @keyframes slideUpFade { from { opacity:0; transform:translateY(40px) scale(0.95); } to { opacity:1; transform:translateY(0) scale(1); } }
            @keyframes fadeUpIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
            .unearned-badge:hover { filter:grayscale(0%)!important; opacity:0.8!important; transform:translateY(-5px)!important; }
        </style>
        <div style="position:fixed;inset:0;background:rgba(15,23,42,0.4);backdrop-filter:blur(12px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
            <div style="background:linear-gradient(145deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));border:1px solid rgba(255,255,255,0.8);border-radius:32px;width:100%;max-width:900px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 30px 60px rgba(0,0,0,0.15), inset 0 2px 0 rgba(255,255,255,0.8);overflow:hidden;animation:slideUpFade 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
                <div style="padding:2.5rem 3rem 1.5rem;display:flex;justify-content:space-between;align-items:flex-end;position:relative;z-index:2;">
                    <div>
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                            <div style="width:40px;height:40px;background:linear-gradient(135deg, #3b82f6, #8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;box-shadow:0 4px 10px rgba(99,102,241,0.3);">🏆</div>
                            <h2 style="font-size:2rem;font-weight:800;color:var(--slate-900);margin:0;letter-spacing:-0.03em;">Your Achievements</h2>
                        </div>
                        <p style="color:var(--slate-500);margin:0;font-size:1rem;">Complete tasks, read articles, and maintain streaks to unlock more badges.</p>
                    </div>
                    <button onclick="document.getElementById('badges-modal').remove()" style="background:#ffffff;border:1px solid var(--slate-200);color:var(--slate-500);cursor:pointer;padding:0.75rem;border-radius:50%;transition:all 0.2s;display:flex;box-shadow:0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='var(--white)';this.style.color='var(--slate-500)'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div style="padding:1.5rem 3rem 3rem;overflow-y:auto;flex:1;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:1.5rem;">
                        ${gridHtml}
                    </div>
                </div>
            </div>
        </div>`;
        document.body.appendChild(modal);
    };

    // Switch view
    function switchView(viewId) {
        const contentContainer = document.getElementById('dashboard-content');
        if (!contentContainer) {
            window.location.href = '../../dashboard.php?view=' + viewId;
            return;
        }

        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.dataset.view === viewId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Load view content
        loadView(viewId);
    }

    // Load view content
    function loadView(viewId) {
        const contentContainer = document.getElementById('dashboard-content');
        if (!contentContainer) return;

        const views = {
            'home': getHomeView,
            'profile': getProfileView,
            'growth': getGrowthView,
            'speech': getSpeechView,
            'motor': getMotorView,
            'activities': getActivitiesView,
            'clinic': getClinicView,
            'notifications': getNotificationsView,
            'reports': getReportsView,
            'messages': function () { return (window.getMessagesView || function () { return '<div class="dashboard-content"><p>Messages view loading...</p></div>'; })(); },
            'settings': getSettingsView
        };

        const viewFunction = views[viewId] || views['home'];
        contentContainer.innerHTML = viewFunction();

        // Post-render hooks
        if (viewId === 'home' || !viewId) {
            loadHomeActivities();
        }

        // Re-apply translations to newly injected content if in Arabic mode
        if (typeof retranslateCurrentPage === 'function') {
            retranslateCurrentPage();
        }
    }

    // Load home activities from API
    async function loadHomeActivities() {
        // Trigger daily notifications in the background
        fetch('../../api_trigger_daily.php').catch(e => console.error(e));

        const container = document.getElementById('home-activities-list');
        if (!container) return;
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[window._selectedChildIndex || 0] || children[0] || null;
        if (!child) return;

        try {
            const res = await fetch('../../api_activities.php?action=recommend&child_id=' + child.child_id);
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();

            if (data.success && data.recommendations) {
                const recs = data.recommendations;
                const activities = recs.real_life_activities || [];
                const catBadges = {
                    motor: { label: 'Motor 💪', bg: '#dcfce7', color: '#166534' },
                    speech: { label: 'Speech 🗣️', bg: '#dbeafe', color: '#1e40af' },
                    cognitive: { label: 'Cognitive 🧠', bg: '#fef3c7', color: '#92400e' },
                    social: { label: 'Social 🤝', bg: '#fce7f3', color: '#9d174d' },
                    real_life: { label: 'Activity ⭐', bg: '#f1f5f9', color: '#475569' }
                };

                if (activities.length === 0) {
                    container.innerHTML = '<p style="color:var(--slate-500);padding:0.75rem;text-align:center;font-size:0.85rem;">No activities yet. Check the Activities tab!</p>';
                } else {
                    container.innerHTML = activities.slice(0, 3).map((act, i) => {
                        const badge = catBadges[act.category] || catBadges.real_life;
                        return `<div style="display:flex;gap:0.75rem;padding:0.6rem 0;${i > 0 ? 'border-top:1px solid #f1f5f9;' : ''}align-items:flex-start;">
                            <div style="flex-shrink:0;width:2.25rem;height:2.25rem;background:${badge.bg};border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;">${badge.label.split(' ')[1] || '⭐'}</div>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.15rem;">
                                    <h4 style="font-weight:600;font-size:0.85rem;color:#1e293b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${act.title}</h4>
                                    <span style="flex-shrink:0;font-size:0.6rem;padding:2px 6px;border-radius:6px;background:${badge.bg};color:${badge.color};font-weight:600;">${badge.label.split(' ')[0]}</span>
                                </div>
                                <p style="font-size:0.75rem;color:#64748b;margin:0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${act.description}</p>
                                ${act.reason_picked ? `<p style="font-size:0.65rem;color:#8b5cf6;margin:0.15rem 0 0;font-style:italic;">💡 ${act.reason_picked}</p>` : ''}
                                <span style="font-size:0.65rem;color:#94a3b8;margin-top:0.15rem;display:inline-block;">⏱ ${act.duration || '15 min'}</span>
                            </div>
                        </div>`;
                    }).join('');
                }

                // Load Articles
                const articlesContainer = document.getElementById('home-articles-list');
                if (articlesContainer && recs.articles) {
                    const articles = recs.articles || [];
                    if (articles.length === 0) {
                        articlesContainer.innerHTML = '<p style="color:var(--slate-500);padding:1rem;text-align:center;">No articles available right now.</p>';
                    } else {
                        articlesContainer.style.display = 'flex';
                        articlesContainer.style.gap = '0.75rem';
                        articlesContainer.style.overflowX = 'auto';
                        articlesContainer.style.scrollSnapType = 'x mandatory';
                        articlesContainer.style.padding = '0.75rem';
                        articlesContainer.style.paddingBottom = '1rem';

                        articlesContainer.innerHTML = articles.slice(0, 5).map((art, i) => {
                            const esTitle = art.title.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                            const esDesc = (art.summary || '').replace(/'/g, "\\'").replace(/"/g, "&quot;");
                            const images = ['https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&q=80', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=400&q=80', 'https://images.unsplash.com/photo-1472162072942-cd5147eb3959?w=400&q=80', 'https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?w=400&q=80', 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=400&q=80'];
                            const imgUrl = images[i % images.length];

                            return `<div style="flex:0 0 220px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;cursor:pointer;scroll-snap-align:start;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 6px rgba(0,0,0,0.04);display:flex;flex-direction:column;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 6px rgba(0,0,0,0.04)'" onclick="openArticleModal(${child.child_id}, ${i}, '${esTitle}', '${esDesc}')">
                                <div style="height:100px;background:#e2e8f0;background-image:url(${imgUrl});background-size:cover;background-position:center;"></div>
                                <div style="padding:0.75rem;flex:1;display:flex;flex-direction:column;">
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:0.6rem;font-weight:700;margin-bottom:0.35rem;align-self:flex-start;">${art.category || 'Article'}</span>
                                    <h4 style="font-weight:700;font-size:0.85rem;color:#1e293b;line-height:1.3;margin:0 0 0.3rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${art.title}</h4>
                                    <p style="font-size:0.72rem;color:#64748b;line-height:1.4;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${art.summary}</p>
                                </div>
                            </div>`;
                        }).join('');
                    }
                }
            } else {
                container.innerHTML = '<p style="color:var(--slate-500);padding:0.75rem;text-align:center;font-size:0.85rem;">Activities will appear here once configured.</p>';
            }
        } catch (e) {
            console.error('Activities load error:', e);
            container.innerHTML = '<p style="color:var(--slate-500);padding:0.75rem;text-align:center;font-size:0.85rem;">Could not load activities. Check console for details.</p>';
        }

        // ── 7-Day Activity Chart ────────────────────────────
        try {
            const chartRes = await fetch('../../api_activities.php?action=summary_chart&child_id=' + child.child_id);
            const chartData = await chartRes.json();
            if (chartData.success && chartData.chart_data) {
                const canvas = document.getElementById('home-activity-chart');
                if (canvas && typeof Chart !== 'undefined') {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.chart_data.map(d => d.label),
                            datasets: [{
                                label: 'Activities',
                                data: chartData.chart_data.map(d => d.count),
                                backgroundColor: chartData.chart_data.map((d, i) => i === chartData.chart_data.length - 1 ? '#6366f1' : '#c7d2fe'),
                                borderRadius: 6,
                                borderSkipped: false,
                                barPercentage: 0.6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                                x: { ticks: { font: { size: 10 } }, grid: { display: false } }
                            }
                        }
                    });
                }
            }
        } catch (e) { }

        // ── Weekly Article Countdown Timer ────────────────────
        function updateArticleCountdown() {
            const el = document.getElementById('article-countdown');
            if (!el) return;
            const now = new Date();
            // Next Monday at 00:00:00
            const daysUntilMon = (8 - now.getDay()) % 7 || 7;
            const nextMon = new Date(now.getFullYear(), now.getMonth(), now.getDate() + daysUntilMon);
            nextMon.setHours(0, 0, 0, 0);
            const diff = nextMon - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((diff % (1000 * 60)) / 1000);
            el.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 8s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Refreshes in <strong>${days}d ${hours}h ${mins}m ${secs}s</strong>`;
        }
        updateArticleCountdown();
        setInterval(updateArticleCountdown, 1000);
    }

    // View templates
    function getHomeView() {
        const d = window.dashboardData || {};
        const p = d.parent || {};
        const children = d.children || [];
        const appts = d.appointments || [];
        const child = children[window._selectedChildIndex || 0] || children[0] || null;

        let bannerHtml = '';
        if (d.banners && d.banners.length > 0) {
            d.banners.forEach(b => {
                const isUrgent = b.style === 'urgent' || b.style === 'danger';
                const bg = b.style === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : (isUrgent ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #6366f1, #8b5cf6)');
                const icon = b.style === 'success' ? '✨' : (isUrgent ? '⚠️' : '📢');
                bannerHtml += `<div style="background:${bg}; color:#fff; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <span style="font-size:1.5rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.2));">${icon}</span>
                        <div style="font-weight:600; font-size:0.95rem; line-height:1.4;">${b.message}</div>
                    </div>
                    ${b.link ? `<a href="${b.link}" target="_blank" style="color:#fff; background:rgba(255,255,255,0.2); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.85rem; transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">View</a>` : ''}
                </div>`;
            });
        }

        if (!child) {
            return `<div class="dashboard-content">
                <div class="dashboard-header-section"><div>
                    <h1 class="dashboard-title">Welcome, ${p.fname || 'Parent'}! 👋</h1>
                    <p class="dashboard-subtitle">Get started by adding your child's profile</p>
                </div></div>
                ${bannerHtml}
                <div class="dashboard-card" style="text-align:center;padding:3rem;">
                    <svg style="width:4rem;height:4rem;color:var(--slate-300);margin:0 auto 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <h3 style="margin-bottom:.5rem;">No children added yet</h3>
                    <p style="color:var(--slate-500);margin-bottom:1.5rem;">Add your child to start tracking their development</p>
                    <a href="javascript:void(0)" onclick="openAddChildModal()" class="btn btn-gradient">Add Child Profile</a>
                </div></div>`;
        }

        const g = child.growth || {};
        const weight = g.weight ? g.weight + ' kg' : '—';
        const height = g.height ? g.height + ' cm' : '—';
        const initial = (child.first_name || '?')[0].toUpperCase();
        const fullName = (child.first_name || '') + ' ' + (child.last_name || '');

        let apptHtml = '';
        if (appts.length > 0) {
            appts.forEach(a => {
                const dt = new Date(a.scheduled_at);
                const dateStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                apptHtml += `<div class="appointment-item">
                    <div class="appointment-icon icon-blue-bg">📅</div>
                    <div class="appointment-info">
                        <div class="appointment-title">${a.type || 'Appointment'}</div>
                        <div class="appointment-date">${dateStr}</div>
                        <div class="appointment-location">Dr. ${a.doc_fname} ${a.doc_lname} - ${a.clinic_name || ''}</div>
                    </div></div>`;
            });
        } else {
            apptHtml = '<p style="color:var(--slate-500);padding:1rem;">No upcoming appointments</p>';
        }

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div>
                <h1 class="dashboard-title">Welcome back, ${p.fname || 'Parent'}! 👋</h1>
                <p class="dashboard-subtitle">Here's ${child.first_name}'s progress today</p>
            </div>
            </div>
            ${bannerHtml}
            <div class="child-profile-card">
                <div class="child-avatar">${initial}</div>
                <div class="child-info">
                    <h2 class="child-name">${child.first_name + ' ' + p.fname}</h2>
                    <div class="child-details">
                        <span>${child.age_display || ''}</span><span>•</span><span>Born: ${child.birth_date_formatted || ''}</span>
                    </div>
                </div>
                <div class="child-stats">
                    <div class="stat-box"><div class="stat-label">Weight</div><div class="stat-value">${weight}</div></div>
                    <div class="stat-box"><div class="stat-label">Height</div><div class="stat-value">${height}</div></div>
                </div>
            </div>
            
            ${(() => {
                const ageMon = child.age_months || 0;
                let devFocus = '';
                if (ageMon < 12) devFocus = 'Your baby is in a rapid growth phase! Focus on tummy time, tracking objects, and basic sounds. Every small milestone is a big victory.';
                else if (ageMon < 24) devFocus = 'Welcome to the toddler years! Focus on building vocabulary, walking independently, and engaging in simple shape-sorting or interactive games.';
                else if (ageMon < 36) devFocus = 'At this stage, curiosity is key. Encourage asking questions, coloring, and playing memory match games to boost cognitive and motor skills.';
                else devFocus = "It's time to prep for preschool! Reading, counting, and more structured social play will vastly improve confidence and readiness.";
                return `
                <div style="background:linear-gradient(135deg,rgba(59,130,246,0.1),rgba(147,197,253,0.2));border:1px solid #bfdbfe;border-radius:16px;padding:1.5rem;margin-bottom:2rem;display:flex;gap:1.25rem;align-items:flex-start;box-shadow:inset 0 2px 4px rgba(255,255,255,0.5);">
                    <div style="font-size:2.5rem;line-height:1;filter:drop-shadow(0 4px 6px rgba(0,0,0,0.1));">🌟</div>
                    <div>
                        <h3 style="margin:0 0 0.5rem;font-size:1.1rem;color:#1e3a8a;font-weight:700;">Development Focus for ${fullName}</h3>
                        <p style="margin:0;color:#1e40af;font-size:0.95rem;line-height:1.6;">${devFocus}</p>
                    </div>
                </div>`;
            })()}

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header" style="padding:1rem 1.25rem;"><h3 class="card-title" style="font-size:0.95rem;"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Today's Activities for ${child.first_name}</h3></div>
                    <div class="card-content" id="home-activities-list" style="padding:0.75rem 1.25rem;">
                        <div style="text-align:center;padding:1.5rem;color:var(--slate-400);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin:0 auto 0.4rem;display:block;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            Loading...
                        </div>
                    </div>
                </div>
                <div class="dashboard-column">
                    <div class="dashboard-card" id="home-activity-summary-card">
                        <div class="card-header" style="padding:1rem 1.25rem;"><h3 class="card-title" style="font-size:0.95rem;"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>7-Day Activity Trend</h3></div>
                        <div class="card-content" style="padding:0.75rem 1rem;">
                            <canvas id="home-activity-chart" style="width:100%;height:160px;"></canvas>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-header" style="padding:1rem 1.25rem;"><h3 class="card-title" style="font-size:0.95rem;"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Upcoming Appointments</h3></div>
                        <div class="card-content">${apptHtml}</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-header" style="justify-content:space-between;padding:1rem 1.25rem;align-items:center;">
                            <div>
                                <h3 class="card-title" style="font-size:0.95rem;margin-bottom:0.15rem;"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>Weekly Articles & Tips</h3>
                                <div id="article-countdown" style="font-size:0.7rem;color:var(--slate-400);display:flex;align-items:center;gap:0.3rem;"></div>
                            </div>
                            <a href="../../articles.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6);text-decoration:none;font-weight:600;padding:0.45rem 1rem;border-radius:10px;box-shadow:0 2px 8px rgba(99,102,241,0.3);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(99,102,241,0.4)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(99,102,241,0.3)'">View All →</a>
                        </div>
                        <div class="card-content" id="home-articles-list" style="padding:0;">
                            <div style="text-align:center;padding:2rem;color:var(--slate-400);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin:0 auto 0.4rem;display:block;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                Loading articles...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="quick-actions-card">
                <h3 class="section-heading">Quick Actions</h3>
                <div class="quick-actions-grid">
                    <button class="quick-action-btn" onclick="switchView('growth')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span>Log Growth</span></button>
                    <button class="quick-action-btn" onclick="switchView('speech')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/></svg><span>Record Speech</span></button>
                    <button class="quick-action-btn" onclick="switchView('activities')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg><span>Add Activity</span></button>
                    <button class="quick-action-btn" onclick="switchView('clinic')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span>Book Appointments</span></button>
                </div>
            </div>
        </div>`;
    }

    function getProfileView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const idx = window._selectedChildIndex || 0;
        const child = children[idx] || null;
        const sub = d.subscription || {};
        const streaks = d.streaks || {};
        const badges = d.badges || [];

        let selectorHtml = '';
        children.forEach((c, i) => {
            const ci = (c.first_name || '?')[0].toUpperCase();
            const al = c.age_months >= 24 ? Math.floor(c.age_months / 12) + ' yo' : c.age_months + ' mo';
            const act = i === idx;
            selectorHtml += `<div onclick="window._selectedChildIndex=${i};switchView('profile')" style="display:flex;flex-direction:column;align-items:center;cursor:pointer;transition:all 0.3s;${act ? '' : 'opacity:0.5;filter:grayscale(50%);'}">
                <div style="width:4.5rem;height:4.5rem;background:${act ? 'linear-gradient(135deg,#6366f1,#8b5cf6)' : '#e2e8f0'};color:${act ? 'white' : '#64748b'};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;border:3px solid ${act ? '#c7d2fe' : 'transparent'};box-shadow:${act ? '0 4px 15px rgba(99,102,241,0.3)' : 'none'};">${ci}</div>
                <span style="margin-top:0.5rem;font-weight:600;font-size:0.9rem;">${c.first_name}</span>
                <span style="font-size:0.7rem;color:#94a3b8;">${al}</span></div>`;
        });
        selectorHtml += `<div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;opacity:0.5;" onclick="openAddChildModal()">
            <div style="width:4.5rem;height:4.5rem;border:2px dashed #cbd5e1;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></div>
            <span style="margin-top:0.5rem;font-weight:500;color:#94a3b8;font-size:0.9rem;">Add New</span></div>`;

        if (!child) {
            return '<div class="dashboard-content"><div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1><p class="dashboard-subtitle">No children yet</p></div><a href="javascript:void(0)" onclick="openAddChildModal()" class="btn btn-outline">Add Child</a></div></div>';
        }

        const g = child.growth || {};
        const fullName = (child.first_name || '') + ' ' + (child.last_name || '');
        const init = (child.first_name || '?')[0].toUpperCase();
        const gender = child.gender || 'unknown';
        const genderIcon = gender === 'male' ? '♂️' : gender === 'female' ? '♀️' : '';
        const actCompleted = child.activities_completed || 0;
        const milestoneScore = g.motor_milestones_score ? parseFloat(g.motor_milestones_score).toFixed(0) + '%' : '0%';
        const totalPoints = child.total_points || 0;
        const badgeCount = badges.length || child.badge_count || 0;
        const ageMonths = child.age_months || 0;
        const ageYears = Math.floor(ageMonths / 12);
        const ageRemMonths = ageMonths % 12;
        const ageStr = ageYears > 0 ? ageYears + 'y ' + ageRemMonths + 'm' : ageMonths + ' months';

        let devStage = 'Newborn';
        if (ageMonths >= 1 && ageMonths < 4) devStage = 'Early Infancy';
        else if (ageMonths >= 4 && ageMonths < 8) devStage = 'Mid Infancy';
        else if (ageMonths >= 8 && ageMonths < 12) devStage = 'Late Infancy';
        else if (ageMonths >= 12 && ageMonths < 24) devStage = 'Toddler';
        else if (ageMonths >= 24 && ageMonths < 36) devStage = 'Early Childhood';
        else if (ageMonths >= 36) devStage = 'Preschooler';

        // Get speech data for customization
        const speechData = child._speech || null;
        const vocabScore = speechData?.vocabulary_score || null;
        const clarityScore = speechData?.clarify_score || null;

        // Build customized developmental stage description based on actual child data
        let devStageDesc = '';
        const strengths = [];
        const focusAreas = [];

        // Analyze motor skills
        const motorPctNum = parseInt(milestoneScore) || 0;
        if (motorPctNum >= 70) strengths.push(`strong motor skills (${milestoneScore} milestones achieved)`);
        else if (motorPctNum < 50) focusAreas.push('developing motor skills through play');

        // Analyze growth
        if (g && g.weight && g.height) {
            strengths.push(`healthy physical growth (${g.weight}kg, ${g.height}cm)`);
        } else {
            focusAreas.push('establishing growth tracking routine');
        }

        // Analyze speech
        if (vocabScore !== null) {
            if (vocabScore >= 75) strengths.push(`excellent vocabulary (${vocabScore}%)`);
            else if (vocabScore < 50) focusAreas.push('building vocabulary through reading and conversation');
        }
        if (clarityScore !== null) {
            if (clarityScore >= 75) strengths.push(`clear speech (${clarityScore}%)`);
            else if (clarityScore < 50) focusAreas.push('speech clarity exercises');
        }

        // Build the description
        if (strengths.length > 0) {
            devStageDesc = `${child.first_name} is thriving in the ${devStage} stage (${ageStr}). Showing ${strengths.join(', ')}. `;
        } else {
            devStageDesc = `${child.first_name} is in the ${devStage} stage (${ageStr}). `;
        }

        if (focusAreas.length > 0) {
            devStageDesc += `Focus areas: ${focusAreas.join(', ')}. `;
        }

        devStageDesc += `Continue with age-appropriate activities to support their unique development journey.`;

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1>
                <p class="dashboard-subtitle">Overview of ${child.first_name}'s development journey</p></div>
            <a href="javascript:void(0)" onclick="openAddChildModal(window.dashboardData?.children?.[${idx}])" class="btn btn-outline" style="display:flex;align-items:center;gap:0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit</a></div>

            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:1.5rem 2rem;margin-bottom:2rem;">
                <p style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:1rem;">Select Child</p>
                <div style="display:flex;gap:2rem;overflow-x:auto;padding-bottom:0.5rem;">${selectorHtml}</div></div>

            <div style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a78bfa 100%);border-radius:24px;padding:2.5rem;color:#fff;margin-bottom:2rem;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
                <div style="display:flex;align-items:center;gap:2rem;position:relative;z-index:1;">
                    <div style="width:6rem;height:6rem;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;border:3px solid rgba(255,255,255,0.3);flex-shrink:0;">${init}</div>
                    <div style="flex:1;"><h2 style="font-size:1.75rem;font-weight:800;margin:0 0 0.25rem;color:#fff;">${fullName} ${genderIcon}</h2>
                        <p style="margin:0 0 0.75rem;opacity:0.85;font-size:1rem;">${ageStr} old · ${devStage} Stage</p>
                        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                            <span style="background:rgba(255,255,255,0.15);padding:0.35rem 0.85rem;border-radius:20px;font-size:0.8rem;font-weight:600;">🎂 ${child.birth_date_formatted || 'Unknown DOB'}</span>
                            <span style="background:rgba(255,255,255,0.15);padding:0.35rem 0.85rem;border-radius:20px;font-size:0.8rem;font-weight:600;">📋 ${sub.plan_name || 'Free'} Plan</span></div></div></div></div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;">
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.25rem;text-align:center;"><div style="font-size:1.75rem;margin-bottom:0.25rem;">💎</div><div style="font-size:1.5rem;font-weight:800;color:#1e293b;">${totalPoints}</div><div style="font-size:0.75rem;color:#94a3b8;font-weight:500;">Total Points</div></div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.25rem;text-align:center;"><div style="font-size:1.75rem;margin-bottom:0.25rem;">🏆</div><div style="font-size:1.5rem;font-weight:800;color:#1e293b;">${badgeCount}</div><div style="font-size:0.75rem;color:#94a3b8;font-weight:500;">Badges Earned</div></div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.25rem;text-align:center;"><div style="font-size:1.75rem;margin-bottom:0.25rem;">📝</div><div style="font-size:1.5rem;font-weight:800;color:#1e293b;">${actCompleted}</div><div style="font-size:0.75rem;color:#94a3b8;font-weight:500;">Activities Done</div></div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.25rem;text-align:center;"><div style="font-size:1.75rem;margin-bottom:0.25rem;">🏅</div><div style="font-size:1.5rem;font-weight:800;color:#1e293b;">${milestoneScore}</div><div style="font-size:0.75rem;color:#94a3b8;font-weight:500;">Motor Progress</div></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h3 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">🧒 Developmental Stage</h3></div>
                    <div style="padding:1.5rem;">
                        <div style="background:linear-gradient(135deg,#ede9fe,#e0e7ff);border-radius:12px;padding:1.25rem;margin-bottom:1rem;">
                            <div style="font-size:1.25rem;font-weight:700;color:#4f46e5;margin-bottom:0.25rem;">${devStage}</div>
                            <div style="font-size:0.85rem;color:#6366f1;">${ageStr} old</div></div>
                        <p style="font-size:0.85rem;color:#64748b;line-height:1.6;margin:0;">${devStageDesc}</p></div></div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h3 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📋 Child Development Overview</h3></div>
                    <div style="padding:1.5rem;display:flex;flex-direction:column;gap:0.75rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <div style="width:28px;height:28px;background:linear-gradient(135deg,#60a5fa,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:700;flex-shrink:0;">📊</div>
                            <div><span style="font-size:0.75rem;color:#64748b;display:block;">Latest Growth</span><span style="font-size:0.85rem;color:#1e293b;font-weight:600;">${g.weight ? g.weight + ' kg, ' + g.height + ' cm' : 'No recent data'}</span></div></div>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <div style="width:28px;height:28px;background:linear-gradient(135deg,#f472b6,#ec4899);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:700;flex-shrink:0;">🗣️</div>
                            <div><span style="font-size:0.75rem;color:#64748b;display:block;">Speech Analysis</span><span style="font-size:0.85rem;color:#1e293b;font-weight:600;">Check Speech Hub</span></div></div>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <div style="width:28px;height:28px;background:linear-gradient(135deg,#34d399,#10b981);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:700;flex-shrink:0;">📅</div>
                            <div><span style="font-size:0.75rem;color:#64748b;display:block;">Last Checkup Date</span><span style="font-size:0.85rem;color:#1e293b;font-weight:600;">${g.recorded_at ? new Date(g.recorded_at).toLocaleDateString() : 'None recorded'}</span></div></div>
                    </div></div></div>

            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h3 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">🚀 Quick Access</h3></div>
                <div style="padding:1.5rem;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
                    <div onclick="switchView('growth')" style="cursor:pointer;text-align:center;padding:1.25rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;">📊</div><div style="font-weight:600;font-size:0.85rem;color:#166534;">Growth</div><div style="font-size:0.7rem;color:#16a34a;margin-top:0.25rem;">${g.weight ? g.weight + 'kg · ' + g.height + 'cm' : 'No data yet'}</div></div>
                    <div onclick="switchView('speech')" style="cursor:pointer;text-align:center;padding:1.25rem;background:#faf5ff;border:1px solid #e9d5ff;border-radius:14px;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;">🗣️</div><div style="font-weight:600;font-size:0.85rem;color:#6b21a8;">Speech</div><div style="font-size:0.7rem;color:#9333ea;margin-top:0.25rem;">Track vocabulary</div></div>
                    <div onclick="switchView('motor')" style="cursor:pointer;text-align:center;padding:1.25rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;">🏃</div><div style="font-weight:600;font-size:0.85rem;color:#9a3412;">Motor Skills</div><div style="font-size:0.7rem;color:#ea580c;margin-top:0.25rem;">Milestones checklist</div></div>
                    <div onclick="switchView('activities')" style="cursor:pointer;text-align:center;padding:1.25rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;">⭐</div><div style="font-weight:600;font-size:0.85rem;color:#1e40af;">Activities</div><div style="font-size:0.7rem;color:#3b82f6;margin-top:0.25rem;">Games & articles</div></div>
                </div></div>
        </div>`;
    }


    function getGrowthView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[window._selectedChildIndex || 0] || null;

        if (!child) {
            return `<div class="dashboard-content"><div class="dashboard-header-section"><div>
                <h1 class="dashboard-title">Growth Tracking 📏</h1>
                <p class="dashboard-subtitle">Add a child profile first to track growth</p>
            </div></div>
            <div class="dashboard-card" style="text-align:center;padding:3rem;">
                <p style="color:var(--slate-500);margin-bottom:1rem;">No children added yet</p>
                <a href="javascript:void(0)" onclick="openAddChildModal()" class="btn btn-gradient">Add Child</a>
            </div></div>`;
        }

        const g = child.growth || {};
        const gh = child.growth_history || [];

        // Group ALL history by date
        let historyRows = '';
        const allHistory = gh.slice().reverse();
        const grouped = {};

        allHistory.forEach((r) => {
            const dt = new Date(r.recorded_at);
            const dateStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            if (!grouped[dateStr]) grouped[dateStr] = { record_id: r.record_id, weight: '', height: '', head: '' };
            if (r.weight) grouped[dateStr].weight = r.weight;
            if (r.height) grouped[dateStr].height = r.height;
            if (r.head_circumference) grouped[dateStr].head = r.head_circumference;
            grouped[dateStr].record_id = r.record_id;
        });

        Object.keys(grouped).forEach(dateStr => {
            const gRow = grouped[dateStr];
            let summaryStr = [];
            if (gRow.weight) summaryStr.push(`<span style="color:var(--slate-700)">Weight: <strong>${gRow.weight} kg</strong></span>`);
            if (gRow.height) summaryStr.push(`<span style="color:var(--slate-700)">Height: <strong>${gRow.height} cm</strong></span>`);
            if (gRow.head) summaryStr.push(`<span style="color:var(--slate-700)">Head: <strong>${gRow.head} cm</strong></span>`);

            historyRows += `<tr class="gh-row" style="border-bottom:1px solid var(--slate-50); transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <td style="padding:1rem;">${dateStr}</td>
                <td style="padding:1rem;">${summaryStr.join(' <span style="color:#cbd5e1;margin:0 0.5rem">|</span> ')}</td>
                <td style="padding:1rem;text-align:right;">
                    <button onclick="openLogMeasurementModal(${child.child_id}, ${gRow.record_id}, '${gRow.weight}', '${gRow.height}', '${gRow.head}')" class="btn btn-sm btn-outline" style="border-radius:20px;padding:0.4rem 1rem;font-size:0.75rem;display:inline-flex;align-items:center;gap:0.3rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg> Edit
                    </button>
                </td>
            </tr>`;
        });

        if (!historyRows) historyRows = '<tr><td colspan="3" style="padding:1rem;color:var(--slate-500);text-align:center">No measurements recorded yet</td></tr>';

        // After rendering, fetch WHO comparison
        setTimeout(() => fetchWHOComparison(child.child_id), 100);

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div>
                <h1 class="dashboard-title">Growth Tracking 📏</h1>
                <p class="dashboard-subtitle">Monitor ${child.first_name}'s development using global standards</p>
            </div>
            <button class="btn btn-gradient" onclick="openLogMeasurementModal(${child.child_id})">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Log Measurement
            </button></div>

            <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Current Weight</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.weight ? g.weight + ' kg' : '—'}</h3>
                    ${g.recorded_at ? `<p style="font-size:0.75rem;color:var(--slate-400);margin-top:0.5rem;margin-bottom:0;">Last updated: ${new Date(g.recorded_at).toLocaleDateString()}</p>` : ''}
                    <span class="badge" id="who-weight-badge" style="margin-top:0.5rem;">Loading...</span>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin-top:0.5rem;line-height:1.4;" id="weight-explain">Indicates nutritional status. Compare with WHO standards to see if growth is on track.</p>
                </div></div>
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Current Height</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.height ? g.height + ' cm' : '—'}</h3>
                    ${g.recorded_at ? `<p style="font-size:0.75rem;color:var(--slate-400);margin-top:0.5rem;margin-bottom:0;">Last updated: ${new Date(g.recorded_at).toLocaleDateString()}</p>` : ''}
                    <span class="badge" id="who-height-badge" style="margin-top:0.5rem;">Loading...</span>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin-top:0.5rem;line-height:1.4;" id="height-explain">Tracks physical stature. A higher percentile means your child is taller than more kids their age.</p>
                </div></div>
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Head Circumference</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.head_circumference ? g.head_circumference + ' cm' : '—'}</h3>
                    ${g.recorded_at ? `<p style="font-size:0.75rem;color:var(--slate-400);margin-top:0.5rem;margin-bottom:0;">Last updated: ${new Date(g.recorded_at).toLocaleDateString()}</p>` : ''}
                    <span class="badge" id="who-head-badge" style="margin-top:0.5rem;">Loading...</span>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin-top:0.5rem;line-height:1.4;" id="head-explain">Monitors brain growth. Consistent growth along a percentile curve is what matters most.</p>
                </div></div>
            </div>

            <div class="dashboard-card" id="who-summary" style="margin-bottom:2rem;display:none;">
                <div class="card-content" style="padding:1.5rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3 class="card-title" style="margin:bottom:0;">Global Growth Standard (WHO)</h3>
                        <div class="info-tooltip" style="position:relative;display:inline-block;">
                            <div style="font-size:0.8rem;color:var(--slate-500);cursor:help;">ⓘ What is this?</div>
                            <div class="tooltip-content" style="display:none;position:absolute;top:100%;right:0;width:250px;background:#334155;color:#fff;padding:1rem;border-radius:8px;font-size:0.75rem;z-index:10;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);line-height:1.5;">This compares your child's measurements to the global median for healthy development, independently of ethnicity. "Percentile" shows where they stand among 100 typical kids.</div>
                        </div>
                    </div>
                    <div id="who-summary-content"></div>
                    
                    <div id="who-charts-container" style="display:grid;grid-template-columns:1fr;gap:2rem;margin-top:2rem;">
                        <!-- Charts will be injected here -->
                    </div>
            </div>
            
            <div class="dashboard-card" style="margin-bottom:2rem;">
                <div class="card-content" style="padding:1.5rem;">
                    <h4 style="font-size:1.25rem;font-weight:700;margin-bottom:1.5rem;color:var(--slate-800);">Measurement Guides</h4>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;">
                        <div style="background:#f8fafc;padding:1.5rem;border-radius:12px;border:1px solid #e2e8f0;">
                            <h5 style="font-weight:700;margin-bottom:0.5rem;color:#3b82f6;">Weight & Length</h5>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin-bottom:0.5rem;"><strong>What it means:</strong> Basic indicators of nutritional status and physical growth.</p>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin:0;"><strong>How to measure:</strong> Use a digital scale for weight. For length (< 2 yrs), measure lying down. For height (> 2 yrs), measure standing straight.</p>
                        </div>
                        <div style="background:#f8fafc;padding:1.5rem;border-radius:12px;border:1px solid #e2e8f0;">
                            <h5 style="font-weight:700;margin-bottom:0.5rem;color:#8b5cf6;">Head Circumference</h5>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin-bottom:0.5rem;"><strong>What it means:</strong> Tracks brain growth and development.</p>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin:0;"><strong>How to measure:</strong> Wrap a flexible measuring tape just above the eyebrows and ears, around the widest part.</p>
                        </div>
                        <div style="background:#f8fafc;padding:1.5rem;border-radius:12px;border:1px solid #e2e8f0;">
                            <h5 style="font-weight:700;margin-bottom:0.5rem;color:#f59e0b;">Arm Circumference</h5>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin-bottom:0.5rem;"><strong>What it means:</strong> Indicates muscle and fat mass, used to assess acute malnutrition.</p>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin:0;"><strong>How to measure:</strong> Measure the middle of the left upper arm while relaxed using a MUAC tape.</p>
                        </div>
                        <div style="background:#f8fafc;padding:1.5rem;border-radius:12px;border:1px solid #e2e8f0;">
                            <h5 style="font-weight:700;margin-bottom:0.5rem;color:#10b981;">BMI</h5>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin-bottom:0.5rem;"><strong>What it means:</strong> A screening tool for healthy weight relative to height.</p>
                            <p style="font-size:0.875rem;color:var(--slate-600);margin:0;"><strong>How to measure:</strong> Automatically calculated using weight and height (kg/m²).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <h3 class="card-title" style="margin:0;">Measurement History (${allHistory.length} dates)</h3>
                </div>
                <div style="max-height:400px;overflow-y:auto;">
                    <table style="width:100%;border-collapse:collapse;margin-top:0.5rem;">
                        <thead><tr style="border-bottom:2px solid var(--slate-100);position:sticky;top:0;background:#ffffff;">
                            <th style="text-align:left;padding:0.75rem 1rem;color:var(--slate-500);">Date</th>
                            <th style="text-align:left;padding:0.75rem 1rem;color:var(--slate-500);">Recorded Measurements</th>
                            <th style="text-align:right;padding:0.75rem 1rem;color:var(--slate-500);">Action</th>
                        </tr></thead>
                        <tbody>${historyRows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    function getSpeechView() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[window._selectedChildIndex || 0] || null;
        const childId = child ? child.child_id : null;
        const ageMonths = child ? child.age_months : 0;

        // Expected vocabulary by age (WHO/CDC estimates)
        let expectedVocab = 0;
        if (ageMonths < 12) expectedVocab = 3;
        else if (ageMonths < 18) expectedVocab = 20;
        else if (ageMonths < 24) expectedVocab = 50;
        else if (ageMonths < 30) expectedVocab = 200;
        else if (ageMonths < 36) expectedVocab = 450;
        else expectedVocab = 900;

        setTimeout(() => loadSpeechHistory(childId), 100);
        return `<div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Speech Analysis</h1>
                        <p class="dashboard-subtitle">Track ${child ? child.first_name + "'s" : ''} vocabulary and pronunciation progress</p>
                    </div>
                    <button class="btn btn-gradient" onclick="openSpeechModal(${childId})" style="display:flex;align-items:center;gap:0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                        New Recording
                    </button>
                </div>

                <!-- AI Insight Banner -->
                <div style="background:linear-gradient(135deg,#7c3aed 0%,#2563eb 100%);border-radius:20px;padding:2rem;color:#fff;margin-bottom:2rem;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:-20px;right:-20px;width:160px;height:160px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
                    <h3 style="font-size:1.25rem;font-weight:700;margin-bottom:0.75rem;color:#fff;">🧠 AI Speech Insight</h3>
                    <p id="insight-text" style="margin-bottom:1.25rem;color:rgba(255,255,255,0.9);font-size:0.95rem;line-height:1.5;">Loading latest analysis...</p>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <div style="background:rgba(255,255,255,0.15);padding:0.6rem 1.25rem;border-radius:14px;backdrop-filter:blur(6px);">
                            <span style="display:block;font-size:0.7rem;opacity:0.7;">Unique Words</span>
                            <span id="insight-words" style="font-size:1.35rem;font-weight:800;">–</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.15);padding:0.6rem 1.25rem;border-radius:14px;">
                            <span style="display:block;font-size:0.7rem;opacity:0.7;">Expected (WHO)</span>
                            <span style="font-size:1.35rem;font-weight:800;">${expectedVocab}+</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.15);padding:0.6rem 1.25rem;border-radius:14px;">
                            <span style="display:block;font-size:0.7rem;opacity:0.7;">Status</span>
                            <span id="insight-status" style="font-size:1.1rem;font-weight:700;">–</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:0.95rem;color:#1e293b;">📈 Vocabulary Progress</h4></div>
                        <div style="padding:1rem 1.25rem;height:220px;" id="speech-vocab-chart-wrap"><canvas id="speech-vocab-chart"></canvas></div>
                        <div style="padding:0 1.25rem 1rem;"><p id="desc-vocab-progress" style="font-size:0.8rem;color:#64748b;line-height:1.5;margin:0;">Vocabulary progress will appear after multiple recordings.</p></div>
                    </div>
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:0.95rem;color:#1e293b;">🎯 Clarity Score Trend</h4></div>
                        <div style="padding:1rem 1.25rem;height:220px;" id="speech-clarity-chart-wrap"><canvas id="speech-clarity-chart"></canvas></div>
                        <div style="padding:0 1.25rem 1rem;"><p id="desc-clarity-progress" style="font-size:0.8rem;color:#64748b;line-height:1.5;margin:0;">Clarity scores show how clearly your child pronounces words.</p></div>
                    </div>
                </div>

                <h3 style="font-weight:700;font-size:1.1rem;color:#1e293b;margin-bottom:1rem;">Recent Recordings</h3>
                <div id="speech-history-list">
                    <div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">Loading recordings...</div>
                </div>
            </div>`;
    }

    async function loadSpeechHistory(childId) {
        const container = document.getElementById('speech-history-list');
        if (!container || !childId) {
            if (container) container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">No child profile found.</div>';
            return;
        }
        try {
            const res = await fetch('../../api_speech_history.php?child_id=' + childId + '&t=' + Date.now());
            const data = await res.json();
            const entries = data.analyses || [];

            if (entries.length > 0) {
                const latest = entries[0];
                const insightText = document.getElementById('insight-text');
                const insightWords = document.getElementById('insight-words');
                const insightStatus = document.getElementById('insight-status');
                if (insightText) insightText.textContent = latest.transcript || 'No transcript available.';
                if (insightWords) insightWords.textContent = latest.vocabulary_score ?? '–';
                if (insightStatus) insightStatus.textContent = latest.status || '–';
            } else {
                const insightText = document.getElementById('insight-text');
                if (insightText) insightText.textContent = 'No recordings yet. Click "New Recording" to get started!';
            }

            if (entries.length === 0) {
                container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">No recordings yet. Upload an audio file to get started!</div>';
                return;
            }

            const statusColor = (s) => {
                if (!s) return '#64748b';
                if (s.includes('Within') || s.includes('Above')) return '#22c55e';
                return '#f59e0b';
            };

            const modeBadge = (mode, matchScore) => {
                if (mode === 'read_compare') {
                    const scoreColor = matchScore >= 80 ? '#22c55e' : matchScore >= 50 ? '#f59e0b' : '#ef4444';
                    return `<span style="background:#16a34a20;color:#16a34a;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:600;display:flex;align-items:center;gap:0.25rem;">📖 Read & Compare${matchScore != null ? `<span style="background:${scoreColor}20;color:${scoreColor};padding:0.1rem 0.4rem;border-radius:999px;margin-left:0.25rem;">${Math.round(matchScore)}%</span>` : ''}</span>`;
                }
                return '<span style="background:#7c3aed20;color:#7c3aed;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:600;">🎤 Free Talk</span>';
            };

            container.innerHTML = entries.map(e => {
                const dt = new Date(e.sent_at);
                const timeStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                const words = e.vocabulary_score ? Math.round(e.vocabulary_score) : '–';
                const clarify = e.clarify_score ? Math.round(e.clarify_score * 100) + '%' : '–';
                const overall = e.overall_development_score ? Math.round(e.overall_development_score) : null;
                const matchScore = e.match_score;
                const transcript = e.transcript ? (e.transcript.length > 100 ? e.transcript.substring(0, 100) + '…' : e.transcript) : 'No transcript';
                const sColor = statusColor(e.status);
                const entryJson = encodeURIComponent(JSON.stringify(e)).replace(/'/g, "%27");

                // Mode-specific metrics display
                const isCompare = e.mode === 'read_compare';
                const matchScoreVal = matchScore !== null && matchScore !== undefined ? Math.round(matchScore) : null;
                const modeMetrics = isCompare && matchScoreVal != null
                    ? `<span>🎯 Match: <strong style="color:${matchScoreVal >= 80 ? '#22c55e' : matchScoreVal >= 50 ? '#f59e0b' : '#ef4444'}">${matchScoreVal}%</strong></span>
                       ${e.word_hits ? `<span>✓ <strong>${e.word_hits.length}</strong> words hit</span>` : ''}`
                    : `<span>📊 Avg sent: <strong>${e.avg_sentence_length || '–'}</strong></span>`;

                return `<div class="dashboard-card" style="display:flex;align-items:flex-start;padding:1.5rem;gap:1.5rem;margin-bottom:0.75rem;border-left:4px solid ${sColor};transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow='none'">
                    <div style="width:3.5rem;height:3.5rem;background:${e.mode === 'read_compare' ? 'linear-gradient(135deg,#dcfce7,#86efac)' : 'linear-gradient(135deg,#ede9fe,#c4b5fd)'};border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;box-shadow:0 4px 8px rgba(0,0,0,0.1);">
                        ${e.mode === 'read_compare' ? '📖' : '🎤'}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                            ${modeBadge(e.mode, matchScore)}
                            <span style="background:${sColor}20;color:${sColor};padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:600;">${e.status || 'Unknown'}</span>
                            ${overall !== null ? `<span style="background:#7c3aed20;color:#7c3aed;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:600;">⚡ ${overall}/100</span>` : ''}
                            <span style="margin-left:auto;font-size:0.75rem;color:#94a3b8;">${timeStr}</span>
                        </div>
                        <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.75rem;font-style:italic;line-height:1.5;">"${transcript}"</p>
                        <div style="display:flex;gap:1.25rem;font-size:0.8rem;color:var(--slate-400);flex-wrap:wrap;">
                            <span style="background:#f1f5f9;padding:0.25rem 0.6rem;border-radius:6px;">📖 <strong style="color:#1e293b;">${words}</strong> words</span>
                            <span style="background:#f1f5f9;padding:0.25rem 0.6rem;border-radius:6px;">🔊 <strong style="color:#1e293b;">${clarify}</strong></span>
                            ${modeMetrics}
                        </div>
                    </div>
                    <button onclick="openSpeechDetailModal(decodeURIComponent('${entryJson}'))" style="flex-shrink:0;padding:0.6rem 1.2rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:10px;font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        View Details
                    </button>
                </div>`;
            }).join('');

            // Render speech charts
            renderSpeechCharts(entries);
        } catch (err) {
            container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:#64748b;"><div style="font-size:2rem;margin-bottom:0.5rem;">🗣️</div><p>Could not load speech history. Make sure you have speech recordings.</p></div>';
        }
    }

    function renderSpeechCharts(entries) {
        if (typeof Chart === 'undefined' || entries.length === 0) return;
        var reversed = entries.slice().reverse();
        var labels = reversed.map(function (e, i) { return 'Rec ' + (i + 1); });
        var vocabData = reversed.map(function (e) { return e.vocabulary_score ? Math.round(e.vocabulary_score) : 0; });
        var clarityData = reversed.map(function (e) { return e.clarify_score ? Math.round(e.clarify_score * 100) : 0; });

        // Vocab chart
        var vocabEl = document.getElementById('speech-vocab-chart');
        if (vocabEl) {
            new Chart(vocabEl, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Unique Words',
                        data: vocabData,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124,58,237,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#7c3aed',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2.5
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
                }
            });
            // Update description
            var descEl = document.getElementById('desc-vocab-progress');
            if (descEl && vocabData.length >= 2) {
                var first = vocabData[0], last = vocabData[vocabData.length - 1];
                var change = last - first;
                descEl.textContent = change >= 0
                    ? 'Vocabulary has grown by ' + change + ' words across ' + vocabData.length + ' recordings. Great progress!'
                    : 'Tracking ' + vocabData.length + ' recordings. Keep practicing for consistent improvement.';
            }
        }

        // Clarity chart
        var clarityEl = document.getElementById('speech-clarity-chart');
        if (clarityEl) {
            new Chart(clarityEl, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Clarity %',
                        data: clarityData,
                        backgroundColor: clarityData.map(function (v) { return v >= 80 ? 'rgba(34,197,94,0.7)' : v >= 50 ? 'rgba(245,158,11,0.7)' : 'rgba(239,68,68,0.7)'; }),
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
                }
            });
            var clarDescEl = document.getElementById('desc-clarity-progress');
            if (clarDescEl && clarityData.length > 0) {
                var avg = Math.round(clarityData.reduce(function (a, b) { return a + b; }, 0) / clarityData.length);
                clarDescEl.textContent = 'Average clarity score is ' + avg + '%. ' + (avg >= 80 ? 'Excellent pronunciation quality!' : avg >= 50 ? 'Good clarity with room for improvement.' : 'Keep practicing for clearer speech.');
            }
        }
    }

    // Age-based recommended words
    function getAgeWords(ageMonths) {
        if (ageMonths < 18) return { label: '12–17 months', words: ['mama', 'dada', 'ball', 'cup', 'no', 'bye', 'up', 'hi', 'dog', 'cat'] };
        if (ageMonths < 24) return { label: '18–23 months', words: ['water', 'shoe', 'bird', 'book', 'car', 'baby', 'hot', 'go', 'sit', 'more'] };
        if (ageMonths < 36) return { label: '24–35 months', words: ['apple', 'tree', 'run', 'jump', 'play', 'happy', 'blue', 'red', 'big', 'eat'] };
        if (ageMonths < 48) return { label: '36–47 months', words: ['orange', 'school', 'friend', 'animal', 'family', 'outside', 'music', 'color', 'dance', 'grow'] };
        if (ageMonths < 60) return { label: '48–59 months', words: ['elephant', 'butterfly', 'teacher', 'together', 'beautiful', 'favorite', 'remember', 'always', 'village', 'garden'] };
        return { label: '60–72 months', words: ['strawberry', 'hospital', 'experiment', 'neighborhood', 'imagination', 'celebrate', 'accomplish', 'wonderful', 'adventure', 'discovery'] };
    }

    window.openSpeechModal = function (childId) {
        let existing = document.getElementById('speech-modal');
        if (existing) existing.remove();

        var d = window.dashboardData || {};
        var children = d.children || [];
        var child = children.find(function (c) { return c.child_id == childId; }) || children[0] || {};
        var ageMonths = child.age_months || 24;
        var childName = child.first_name || 'Child';

        // Use static words as fallback, will be replaced by API call
        var ageWordData = getAgeWords(ageMonths);
        window._currentGuidedWords = ageWordData.words;

        var wordPills = ageWordData.words.map(function (w) {
            return '<span style="display:inline-block;background:#ede9fe;color:#5b21b6;padding:0.35rem 0.85rem;border-radius:999px;font-size:0.875rem;font-weight:600;margin:0.25rem;">' + w + '</span>';
        }).join('');

        const modal = document.createElement('div');
        modal.id = 'speech-modal';
        modal.innerHTML = `<div id="speech-modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target.id==='speech-modal-overlay')document.getElementById('speech-modal').remove()">
            <div style="background:var(--surface-light,#fff);border-radius:22px;padding:1.5rem;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 30px 60px rgba(0,0,0,0.3);">
                <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:0.25rem;text-align:center;">🎙️ New Speech Recording</h2>
                <p style="text-align:center;color:#64748b;font-size:0.875rem;margin-bottom:1.5rem;">Choose how you want to record ${childName}'s speech</p>

                <!-- Mode Cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div id="mode-card-free" onclick="selectSpeechMode('free_talk')" style="cursor:pointer;border:2.5px solid #7c3aed;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:14px;padding:1.2rem;text-align:center;transition:transform .15s;">
                        <div style="font-size:2rem;margin-bottom:0.4rem;">🎤</div>
                        <div style="font-weight:700;font-size:0.95rem;color:#4c1d95;">Free Talk</div>
                        <div style="font-size:0.75rem;color:#6d28d9;margin-top:0.25rem;">Child speaks freely</div>
                    </div>
                    <div id="mode-card-compare" onclick="selectSpeechMode('read_compare')" style="cursor:pointer;border:2.5px solid #e2e8f0;background:#f8fafc;border-radius:14px;padding:1.2rem;text-align:center;transition:transform .15s;">
                        <div style="font-size:2rem;margin-bottom:0.4rem;">📖</div>
                        <div style="font-weight:700;font-size:0.95rem;color:#334155;">Read & Compare</div>
                        <div style="font-size:0.75rem;color:#64748b;margin-top:0.25rem;">Read age-matched words</div>
                    </div>
                </div>

                <!-- Free Talk panel -->
                <div id="panel-free_talk">
                    <p style="color:#64748b;font-size:0.875rem;margin-bottom:0.75rem;text-align:center;">Upload an audio or video recording of your child speaking naturally.</p>
                    <input type="file" id="speech-file-input-free" accept="audio/*,video/*" style="width:100%;padding:0.75rem;border:2px dashed #cbd5e1;border-radius:12px;font-size:0.875rem;margin-bottom:1rem;cursor:pointer;box-sizing:border-box;">

                    <!-- Parental Consent Checkbox (COPPA/GDPR) -->
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:0.75rem;margin-bottom:1rem;">
                        <label style="display:flex;gap:0.5rem;align-items:flex-start;cursor:pointer;font-size:0.8rem;color:#475569;">
                            <input type="checkbox" class="parental-consent-checkbox" style="margin-top:2px;flex-shrink:0;">
                            <span>I give consent for my child's voice recording to be processed for speech analysis purposes. This data is stored privately and used only for developmental tracking. <a href="../../privacy.php" target="_blank" style="color:#7c3aed;text-decoration:underline;">Learn more</a></span>
                        </label>
                    </div>

                    <button onclick="submitSpeechRecording('${childId}','free_talk','')" id="speech-submit-btn" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;">🔬 Analyze Speech</button>
                </div>

                <!-- Read & Compare panel -->
                <div id="panel-read_compare" style="display:none;">
                    <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:1rem;margin-bottom:1rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                            <div style="font-size:0.8rem;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:0.5px;">📋 Age-matched words (${ageWordData.label})</div>
                            <button onclick="regenerateWords(${childId},${ageMonths})" style="background:none;border:1px solid #16a34a;color:#16a34a;border-radius:6px;padding:0.25rem 0.5rem;font-size:0.7rem;cursor:pointer;display:flex;align-items:center;gap:0.25rem;">
                                <span>🔄</span> Regenerate
                            </button>
                        </div>
                        <div id="guided-words-container" style="margin-bottom:0.5rem;">${wordPills}</div>
                        <p style="font-size:0.8rem;color:#15803d;margin:0;">Ask your child to say each word. Then upload the recording below.</p>
                    </div>
                    <input type="file" id="speech-file-input-compare" accept="audio/*,video/*" style="width:100%;padding:0.75rem;border:2px dashed #cbd5e1;border-radius:12px;font-size:0.875rem;margin-bottom:1rem;cursor:pointer;box-sizing:border-box;">

                    <!-- Parental Consent Checkbox (COPPA/GDPR) -->
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:0.75rem;margin-bottom:1rem;">
                        <label style="display:flex;gap:0.5rem;align-items:flex-start;cursor:pointer;font-size:0.8rem;color:#475569;">
                            <input type="checkbox" class="parental-consent-checkbox" style="margin-top:2px;flex-shrink:0;">
                            <span>I give consent for my child's voice recording to be processed for speech analysis purposes. This data is stored privately and used only for developmental tracking. <a href="../../privacy.php" target="_blank" style="color:#7c3aed;text-decoration:underline;">Learn more</a></span>
                        </label>
                    </div>

                    <button onclick="submitSpeechRecording('${childId}','read_compare','')" id="speech-submit-btn" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#16a34a,#059669);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;">🔬 Compare & Analyze</button>
                </div>

                <div id="speech-progress" style="margin-top:0.75rem;font-size:0.875rem;text-align:center;"></div>
            </div>
        </div>`;
        document.body.appendChild(modal);

        // Fetch dynamic words from API after modal opens
        setTimeout(function () {
            fetch('../../api_guided_words.php?child_id=' + childId + '&age_months=' + ageMonths)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.words) {
                        window._currentGuidedWords = data.words;
                        var container = document.getElementById('guided-words-container');
                        if (container) {
                            var newPills = data.words.map(function (w) {
                                return '<span style="display:inline-block;background:#ede9fe;color:#5b21b6;padding:0.35rem 0.85rem;border-radius:999px;font-size:0.875rem;font-weight:600;margin:0.25rem;">' + w + '</span>';
                            }).join('');
                            container.innerHTML = '<div style="margin-bottom:0.5rem;">' + newPills + '</div><p style="font-size:0.75rem;color:#15803d;margin:0;">✨ Personalized for ' + data.child_name + ' (Age: ' + data.age_label + ')</p>';
                        }
                    }
                })
                .catch(function (e) { console.log('Using fallback word list'); });
        }, 100);
    };

    // Regenerate guided words dynamically
    window.regenerateWords = async function (childId, ageMonths) {
        const container = document.getElementById('guided-words-container');
        if (!container) return;

        // Show loading state
        container.innerHTML = '<span style="color:#16a34a;font-size:0.85rem;">🔄 Generating words...</span>';

        try {
            const res = await fetch('../../api_guided_words.php?child_id=' + childId + '&age_months=' + ageMonths);
            const data = await res.json();

            if (data.success && data.words) {
                window._currentGuidedWords = data.words;
                const wordPills = data.words.map(function (w) {
                    return '<span style="display:inline-block;background:#ede9fe;color:#5b21b6;padding:0.35rem 0.85rem;border-radius:999px;font-size:0.875rem;font-weight:600;margin:0.25rem;animation:pulse 0.3s ease;">' + w + '</span>';
                }).join('');
                container.innerHTML = '<div style="margin-bottom:0.5rem;">' + wordPills + '</div><p style="font-size:0.75rem;color:#15803d;margin:0;">✨ New words generated for ' + data.child_name + '!</p>';

                // Update the button's target text
                const btn = document.getElementById('speech-submit-btn');
                if (btn) {
                    btn.setAttribute('onclick', btn.getAttribute('onclick').replace(/'[^']*'\)$/, "'" + data.words.join(' ') + "')"));
                }
            }
        } catch (e) {
            container.innerHTML = '<span style="color:#ef4444;font-size:0.85rem;">Could not regenerate words. Using saved list.</span>';
        }
    };

    window.selectSpeechMode = function (mode) {
        var freeCard = document.getElementById('mode-card-free');
        var compareCard = document.getElementById('mode-card-compare');
        var freePanel = document.getElementById('panel-free_talk');
        var cmpPanel = document.getElementById('panel-read_compare');
        if (!freeCard) return;

        if (mode === 'free_talk') {
            freeCard.style.border = '2.5px solid #7c3aed';
            freeCard.style.background = 'linear-gradient(135deg,#ede9fe,#ddd6fe)';
            compareCard.style.border = '2.5px solid #e2e8f0';
            compareCard.style.background = '#f8fafc';
            freePanel.style.display = 'block';
            cmpPanel.style.display = 'none';
        } else {
            compareCard.style.border = '2.5px solid #16a34a';
            compareCard.style.background = 'linear-gradient(135deg,#dcfce7,#bbf7d0)';
            freeCard.style.border = '2.5px solid #e2e8f0';
            freeCard.style.background = '#f8fafc';
            freePanel.style.display = 'none';
            cmpPanel.style.display = 'block';
        }
    };

    window.submitSpeechRecording = async function (childId, mode, targetText) {
        // Get the correct file input based on mode
        const currentMode = mode || (document.getElementById('panel-read_compare').style.display === 'none' ? 'free_talk' : 'read_compare');
        const fileInputId = currentMode === 'free_talk' ? 'speech-file-input-free' : 'speech-file-input-compare';
        const fileInput = document.getElementById(fileInputId);
        const btn = document.getElementById('speech-submit-btn');
        const progress = document.getElementById('speech-progress');
        const panelId = currentMode === 'free_talk' ? 'panel-free_talk' : 'panel-read_compare';
        const consentCheckbox = document.querySelector('#' + panelId + ' .parental-consent-checkbox');

        if (!fileInput || !fileInput.files[0]) {
            if (progress) { progress.style.color = '#ef4444'; progress.textContent = 'Please select an audio file first.'; }
            return;
        }

        // Check parental consent (COPPA/GDPR compliance)
        if (!consentCheckbox || !consentCheckbox.checked) {
            if (progress) { progress.style.color = '#ef4444'; progress.textContent = '⚠️ Parental consent is required to proceed.'; }
            return;
        }

        // Get target text for read_compare mode from stored guided words
        let actualTargetText = targetText;
        if (currentMode === 'read_compare' && window._currentGuidedWords && window._currentGuidedWords.length > 0) {
            actualTargetText = window._currentGuidedWords.join(' ');
        }

        const formData = new FormData();
        formData.append('audio', fileInput.files[0]);
        formData.append('child_id', childId);
        formData.append('mode', currentMode);
        if (actualTargetText) formData.append('target_text', actualTargetText);

        btn.disabled = true;
        btn.textContent = 'Uploading...';
        if (progress) { progress.style.color = '#6366f1'; progress.textContent = '🚀 Sending analysis securely...'; }

        // Start background upload
        showToast("Analysis started! This may take up to 30 seconds...", "info");

        try {
            // Keep fetch running
            const res = await fetch('../../api_speech_analysis.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (typeof window.loadNotifications === 'function') {
                window.loadNotifications();
                if (typeof window.loadNotifCount === 'function') window.loadNotifCount();
            }

            if (data.success) {
                // Close modal first
                const modal = document.getElementById('speech-modal');
                if (modal) modal.remove();

                showToast("Speech analysis complete!", "success");

                // Immediately switch to the speech view to force a full re-render of charts and history
                if (typeof window.switchView === 'function') {
                    window.switchView('speech');
                } else {
                    loadSpeechHistory(childId);
                }
            } else {
                var errorMsg = data.error || 'Analysis failed';
                if (errorMsg.includes('port 8000') || errorMsg.includes('unavailable') || errorMsg.includes('offline')) {
                    errorMsg = 'Speech AI is offline. Run: APIs/Speech Analysis/start-server.bat';
                    if (confirm('Speech AI server is offline.\n\nWould you like to try starting it automatically?')) {
                        startSpeechServer();
                    }
                }
                showToast(errorMsg, "error");
                btn.disabled = false;
                btn.textContent = currentMode === 'read_compare' ? '🔬 Compare & Analyze' : '🔬 Analyze Speech';
            }
        } catch (e) {
            showToast("Could not connect to the speech analysis server.", "error");
            btn.disabled = false;
            btn.textContent = currentMode === 'read_compare' ? '🔬 Compare & Analyze' : '🔬 Analyze Speech';
        }
    };

    // Auto-start Python speech server via PHP proxy
    window.startSpeechServer = async function () {
        showToast('Starting speech server…', 'info');
        try {
            const res = await fetch('../../api_speech_status.php?action=start');
            const data = await res.json();
            if (data.success && data.running) {
                showToast('Speech server started successfully!', 'success');
            } else {
                showToast('Could not auto-start. Run APIs/Speech Analysis/start-server.bat manually.', 'error');
            }
        } catch (e) {
            showToast('Failed to reach server status endpoint.', 'error');
        }
    };

    window.openSpeechDetailModal = function (entryJson) {
        let existing = document.getElementById('speech-detail-modal');
        if (existing) existing.remove();

        let entry;
        try {
            entry = JSON.parse(entryJson);
        } catch (e) {
            console.error("Failed to parse entry details:", e);
            return;
        }

        const dt = new Date(entry.sent_at);
        const timeStr = dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ' at ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const vocabScore = entry.vocabulary_score ? Math.round(entry.vocabulary_score) : 0;
        const clarityScore = entry.clarify_score ? Math.round(entry.clarify_score * 100) : 0;
        const overallScore = entry.overall_development_score ? Math.round(entry.overall_development_score) : 0;

        // Parse developmental feedback JSON if available
        let feedback = null;
        try {
            if (entry.developmental_feedback && typeof entry.developmental_feedback === 'string') {
                feedback = JSON.parse(entry.developmental_feedback);
            } else if (entry.developmental_feedback && typeof entry.developmental_feedback === 'object') {
                feedback = entry.developmental_feedback;
            }
        } catch (e) { /* ignore parsing errors */ }

        let clarityMeaning = 'Developing clear speech patterns.';
        if (clarityScore >= 100) clarityMeaning = 'Very clear pronunciation, aligning perfectly with milestones.';
        else if (clarityScore >= 75) clarityMeaning = 'Good clarity, typical for this developmental stage.';

        let vocabMeaning = 'Still building core vocabulary.';
        if (entry.status && (entry.status.includes('Within') || entry.status.includes('Above'))) {
            vocabMeaning = 'Vocabulary size is right on track or advanced for their age!';
        }

        // Overall score interpretation
        let overallMeaning = 'Continuing to develop speech skills.';
        if (overallScore >= 80) overallMeaning = 'Excellent overall speech development!';
        else if (overallScore >= 60) overallMeaning = 'Good progress across all areas.';
        else if (overallScore >= 40) overallMeaning = 'Making steady progress with practice.';

        // Build feedback sections
        let feedbackHTML = '';
        if (feedback) {
            const strengths = feedback.strengths || [];
            const areasToPractice = feedback.areas_to_practice || [];
            const recommendations = feedback.recommendations || [];

            if (strengths.length > 0 || areasToPractice.length > 0) {
                feedbackHTML = `<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem;color:var(--slate-700);">📋 Developmental Feedback</h3>
                    ${strengths.length > 0 ? `<div style="margin-bottom:0.75rem;">
                        <p style="font-size:0.8rem;font-weight:600;color:#16a34a;margin-bottom:0.35rem;">✓ Strengths</p>
                        ${strengths.map(s => `<p style="font-size:0.8rem;color:var(--slate-600);margin:0.2rem 0;">• ${s}</p>`).join('')}
                    </div>` : ''}
                    ${areasToPractice.length > 0 ? `<div style="margin-bottom:0.75rem;">
                        <p style="font-size:0.8rem;font-weight:600;color:#d97706;margin-bottom:0.35rem;">🎯 Areas to Practice</p>
                        ${areasToPractice.map(a => `<p style="font-size:0.8rem;color:var(--slate-600);margin:0.2rem 0;">• ${a}</p>`).join('')}
                    </div>` : ''}
                    ${recommendations.length > 0 ? `<div>
                        <p style="font-size:0.8rem;font-weight:600;color:#7c3aed;margin-bottom:0.35rem;">💡 Recommendations</p>
                        ${recommendations.map(r => `<p style="font-size:0.8rem;color:var(--slate-600);margin:0.2rem 0;">• ${r}</p>`).join('')}
                    </div>` : ''}
                </div>`;
            }
        }

        // Enhanced metrics section - made parent-friendly and advanced
        let enhancedMetricsHTML = '';
        if (entry.sentence_count || entry.avg_sentence_length || entry.flesch_reading_ease) {
            const getComplexityDescription = (score) => {
                const s = parseFloat(score);
                if (isNaN(s)) return 'Developing';
                if (s > 70) return 'Highly Expressive';
                if (s > 40) return 'Good Variety';
                return 'Simple & Clear';
            };
            const scs = entry.sentence_complexity_score ? getComplexityDescription(entry.sentence_complexity_score) : null;
            const fre = entry.flesch_reading_ease ? parseFloat(entry.flesch_reading_ease) : null;
            let easeDesc = '';
            if (fre !== null) {
                if (fre > 80) easeDesc = 'Very conversational and easy to understand';
                else if (fre > 60) easeDesc = 'Standard conversational level';
                else easeDesc = 'Using more advanced vocabulary';
            }

            enhancedMetricsHTML = `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem;color:var(--slate-700);">📊 In-Depth Language Insights</h3>
                <p style="font-size:0.8rem;color:var(--slate-500);margin-bottom:1rem;line-height:1.4;">An advanced breakdown of your child's speech patterns, helping you understand their growing vocabulary.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.8rem;">
                    ${entry.sentence_count ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Sentences Detected</span> <strong style="color:#1e293b;font-size:1.1rem;">${entry.sentence_count}</strong><div style="font-size:0.7rem;color:#10b981;margin-top:2px;">Distinct thoughts</div></div>` : ''}
                    
                    ${entry.avg_sentence_length ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Sentence Length</span> <strong style="color:#1e293b;font-size:1.1rem;">${Math.round(entry.avg_sentence_length * 10) / 10}</strong> <span style="font-size:0.8rem;">words</span><div style="font-size:0.7rem;color:#8b5cf6;margin-top:2px;">Average per sentence</div></div>` : ''}
                    
                    ${entry.avg_word_length ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Word Breakdown</span> <strong style="color:#1e293b;font-size:1.1rem;">${Math.round(entry.avg_word_length * 10) / 10}</strong> <span style="font-size:0.8rem;">letters</span><div style="font-size:0.7rem;color:#1e40af;margin-top:2px;">Avg. word size</div></div>` : ''}
                    
                    ${entry.polysyllabic_word_count !== null && entry.polysyllabic_word_count !== undefined ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Complex Words</span> <strong style="color:#1e293b;font-size:1.1rem;">${entry.polysyllabic_word_count}</strong><div style="font-size:0.7rem;color:#c026d3;margin-top:2px;">Words with 3+ syllables</div></div>` : ''}

                    ${entry.avg_syllables_per_word ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Pronunciation Profile</span> <strong style="color:#1e293b;font-size:1.1rem;">${Math.round(entry.avg_syllables_per_word * 10) / 10}</strong> <span style="font-size:0.8rem;">syllables</span><div style="font-size:0.7rem;color:#f59e0b;margin-top:2px;">Average per word</div></div>` : ''}
                    
                    ${entry.flesch_kincaid_grade !== null && entry.flesch_kincaid_grade !== undefined ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);grid-column: span 2;"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Speaking Maturity Score</span> <strong style="color:#1e293b;font-size:0.95rem;">Grade ${Math.max(0, Math.round(entry.flesch_kincaid_grade))} Equivalent</strong><div style="font-size:0.7rem;color:#64748b;margin-top:2px;">Approximates their linguistic complexity against standard grade levels.</div></div>` : ''}

                    ${scs ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);grid-column: span 2;"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Expression Variety</span> <strong style="color:#1e293b;font-size:0.95rem;">${scs}</strong><div style="font-size:0.7rem;color:#64748b;margin-top:2px;">Score: ${Math.round(entry.sentence_complexity_score)}/100 based on grammatical variety.</div></div>` : ''}
                    
                    ${fre !== null ? `<div style="background:#fff;padding:0.75rem;border-radius:10px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.02);grid-column: span 2;"><span style="color:#64748b;display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Communication Style</span> <strong style="color:#1e293b;font-size:0.95rem;">${easeDesc}</strong><div style="font-size:0.7rem;color:#64748b;margin-top:2px;">Readability metric indicates natural sentence flow.</div></div>` : ''}
                </div>
            </div>`;
        }

        const modal = document.createElement('div');
        modal.id = 'speech-detail-modal';
        modal.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.remove()">
            <div style="background:var(--surface-light,#fff);border-radius:20px;padding:2rem;max-width:600px;width:100%;box-shadow:0 25px 50px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;text-align:left;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;">
                    <div>
                        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Speech Analysis Details</h2>
                        <p style="color:var(--slate-500);font-size:0.9rem;">Recorded on ${timeStr}</p>
                    </div>
                    <button onclick="document.getElementById('speech-detail-modal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);line-height:1;">&times;</button>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Listen to Recording</h3>
                    <audio controls style="width:100%;height:40px;border-radius:8px;" src="${entry.audio_url ? (entry.audio_url.startsWith('http') ? entry.audio_url : '../../' + entry.audio_url) : ''}">
                        Your browser does not support the audio element.
                    </audio>
                </div>

                <div style="background:var(--slate-50,#f8fafc);border:1px solid var(--slate-200,#e2e8f0);border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Full Transcript</h3>
                    <div style="max-height:180px;overflow-y:auto;padding-right:0.5rem;">
                        <p style="font-style:italic;color:var(--slate-600);line-height:1.6;margin:0;">"${entry.transcript || 'No speech detected.'}"</p>
                    </div>
                </div>

                <!-- Overall Development Score -->
                <div style="background:linear-gradient(135deg,#7c3aed,#2563eb);border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;color:#fff;">
                    <span style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;opacity:0.9;">Overall Development Score</span>
                    <div style="font-size:2rem;font-weight:800;margin-bottom:0.25rem;">${overallScore}<span style="font-size:1rem;font-weight:500;">/100</span></div>
                    <p style="font-size:0.8rem;opacity:0.9;">${overallMeaning}</p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div style="background:#ede9fe;border-radius:12px;padding:1.25rem;">
                        <span style="display:block;font-size:0.8rem;color:#6b21a8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;">Vocabulary Score</span>
                        <div style="font-size:1.75rem;font-weight:800;color:#581c87;margin-bottom:0.5rem;">${vocabScore} <span style="font-size:1rem;font-weight:500;">words</span></div>
                        <p style="font-size:0.8rem;color:#4c1d95;line-height:1.4;">${vocabMeaning}</p>
                    </div>
                    <div style="background:#dcfce7;border-radius:12px;padding:1.25rem;">
                        <span style="display:block;font-size:0.8rem;color:#166534;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;">Clarity Score</span>
                        <div style="font-size:1.75rem;font-weight:800;color:#14532d;margin-bottom:0.5rem;">${clarityScore}%</div>
                        <p style="font-size:0.8rem;color:#15803d;line-height:1.4;">${clarityMeaning}</p>
                    </div>
                </div>

                ${(() => {
                // Word Match Comparison Grid for Read & Compare mode
                if (!isCompare || !entry.target_text) return '';
                const targetWords = entry.target_text.toLowerCase().split(/\s+/).filter(Boolean);
                const transcriptWords = new Set((entry.transcript || '').toLowerCase().split(/\s+/).map(w => w.replace(/[.,!?]/g, '')).filter(Boolean));
                const pillsHtml = targetWords.map(w => {
                    const hit = transcriptWords.has(w);
                    return `<span style="display:inline-block;padding:0.35rem 0.85rem;border-radius:999px;font-size:0.85rem;font-weight:600;margin:0.2rem;background:${hit ? '#dcfce7' : '#fee2e2'};color:${hit ? '#166534' : '#991b1b'};">${hit ? '✓' : '✗'} ${w}</span>`;
                }).join('');
                const mPct = matchScore !== null ? matchScore : 0;
                const barClr = mPct >= 70 ? '#16a34a' : mPct >= 40 ? '#d97706' : '#dc2626';
                return `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                            <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">🎯 Word Match Results</h3>
                            <span style="font-size:1.4rem;font-weight:800;color:${barClr};">${mPct}%</span>
                        </div>
                        <div style="background:#e2e8f0;border-radius:999px;height:8px;margin-bottom:0.75rem;overflow:hidden;">
                            <div style="height:100%;width:${mPct}%;background:${barClr};border-radius:999px;transition:width 0.5s ease;"></div>
                        </div>
                        <div>${pillsHtml}</div>
                        <p style="font-size:0.75rem;color:#64748b;margin-top:0.5rem;">✓ said correctly &nbsp; ✗ not detected</p>
                    </div>`;
            })()}
                ${feedbackHTML}
                ${enhancedMetricsHTML}

                <button onclick="document.getElementById('speech-detail-modal').remove()" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Close</button>
            </div>
        </div>`;
        document.body.appendChild(modal);
    };

    function getMotorView() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[window._selectedChildIndex || 0] || null;
        const childId = child ? child.child_id : null;
        const ageMonths = child ? child.age_months : 0;
        const name = child ? child.first_name : 'Your child';

        // WHO Motor Development Milestones by age
        let whoGross = [], whoFine = [], devTip = '';
        if (ageMonths < 4) {
            whoGross = ['Lifts head when on tummy', 'Pushes up on arms during tummy time'];
            whoFine = ['Grasps objects placed in hand', 'Brings hands to mouth'];
            devTip = 'At this age, focus on tummy time to strengthen neck and core muscles.';
        } else if (ageMonths < 8) {
            whoGross = ['Rolls over both ways', 'Sits without support', 'Bears weight on legs when held'];
            whoFine = ['Transfers objects between hands', 'Rakes at small objects'];
            devTip = 'Provide safe floor time and age-appropriate toys to encourage reaching and rolling.';
        } else if (ageMonths < 12) {
            whoGross = ['Crawls on hands and knees', 'Pulls to standing', 'Cruises along furniture'];
            whoFine = ['Uses pincer grasp', 'Bangs two objects together', 'Pokes with index finger'];
            devTip = 'Create safe spaces for exploration. Stack toys and shape sorters promote fine motor development.';
        } else if (ageMonths < 18) {
            whoGross = ['Walks independently', 'Squats to pick up toys', 'Begins to run'];
            whoFine = ['Stacks 2-3 blocks', 'Turns pages of a book', 'Scribbles with crayons'];
            devTip = 'Encourage walking on different surfaces. Provide crayons and stacking toys.';
        } else if (ageMonths < 24) {
            whoGross = ['Runs with good coordination', 'Kicks a ball forward', 'Walks up stairs with help'];
            whoFine = ['Stacks 4-6 blocks', 'Turns doorknobs', 'Uses spoon with some spilling'];
            devTip = 'Active outdoor play is essential. Drawing and play-dough strengthen hand muscles.';
        } else if (ageMonths < 36) {
            whoGross = ['Jumps with both feet', 'Pedals a tricycle', 'Catches a large ball'];
            whoFine = ['Draws circles', 'Uses scissors with help', 'Strings large beads'];
            devTip = 'Introduce tricycle riding and ball games. Art activities enhance fine motor precision.';
        } else {
            whoGross = ['Hops on one foot', 'Climbs well', 'Throws ball overhand'];
            whoFine = ['Draws a person with 3+ parts', 'Cuts along a line', 'Copies some letters'];
            devTip = 'Sports and structured play activities help refine both gross and fine motor skills.';
        }

        if (childId) setTimeout(() => loadMotorMilestones(childId), 100);

        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Motor Skills</h1>
                        <p class="dashboard-subtitle">${name}'s gross and fine motor development tracking</p>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                    <div style="background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:20px;padding:1.75rem;color:#fff;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;width:100px;height:100px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                        <div style="font-size:2rem;margin-bottom:0.5rem;">🦵</div>
                        <h3 style="font-weight:700;font-size:1.1rem;margin-bottom:0.25rem;color:#fff;">Gross Motor</h3>
                        <p style="font-size:0.8rem;opacity:0.85;margin-bottom:0.75rem;">Walking, running, jumping</p>
                        <div id="gross-motor-progress" style="font-size:1.5rem;font-weight:800;">Loading...</div>
                    </div>
                    <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:20px;padding:1.75rem;color:#fff;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;width:100px;height:100px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                        <div style="font-size:2rem;margin-bottom:0.5rem;">✋</div>
                        <h3 style="font-weight:700;font-size:1.1rem;margin-bottom:0.25rem;color:#fff;">Fine Motor</h3>
                        <p style="font-size:0.8rem;opacity:0.85;margin-bottom:0.75rem;">Grasping, drawing, stacking</p>
                        <div id="fine-motor-progress" style="font-size:1.5rem;font-weight:800;">Loading...</div>
                    </div>
                    <div style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:20px;padding:1.75rem;color:#fff;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;width:100px;height:100px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                        <div style="font-size:2rem;margin-bottom:0.5rem;">📊</div>
                        <h3 style="font-weight:700;font-size:1.1rem;margin-bottom:0.25rem;color:#fff;">Overall Progress</h3>
                        <p style="font-size:0.8rem;opacity:0.85;margin-bottom:0.75rem;">Combined milestone score</p>
                        <div id="motor-overall-progress" style="font-size:1.5rem;font-weight:800;">Loading...</div>
                    </div>
                </div>

                <!-- Charts + WHO -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:0.95rem;color:#1e293b;">📊 Milestone Progress</h4></div>
                        <div style="padding:1.5rem;display:flex;align-items:center;justify-content:center;height:200px;">
                            <canvas id="motor-progress-doughnut" style="max-width:200px;max-height:200px;"></canvas>
                        </div>
                        <div style="padding:0 1.5rem 1.25rem;"><p id="motor-chart-desc" style="font-size:0.8rem;color:#64748b;line-height:1.5;margin:0;text-align:center;">Loading milestone data...</p></div>
                    </div>
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
                        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:0.95rem;color:#1e293b;">🌍 WHO Motor Standards</h4></div>
                        <div style="padding:1.5rem;">
                            <p style="font-size:0.8rem;color:#94a3b8;margin:0 0 1rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Expected at ${ageMonths} months</p>
                            <div style="margin-bottom:1rem;">
                                <p style="font-weight:700;font-size:0.85rem;color:#22c55e;margin:0 0 0.5rem;">🦵 Gross Motor</p>
                                ${whoGross.map(m => '<div style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0;font-size:0.8rem;color:#334155;"><span style="color:#22c55e;">✓</span> ' + m + '</div>').join('')}
                            </div>
                            <div>
                                <p style="font-weight:700;font-size:0.85rem;color:#3b82f6;margin:0 0 0.5rem;">✋ Fine Motor</p>
                                ${whoFine.map(m => '<div style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0;font-size:0.8rem;color:#334155;"><span style="color:#3b82f6;">✓</span> ' + m + '</div>').join('')}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Motor Development Graph -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;margin-bottom:2rem;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:0.95rem;color:#1e293b;">📈 Motor Development Trends</h4></div>
                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="motor-chart"></canvas></div>
                </div>

                <!-- Developmental Tips -->
                <div style="background:linear-gradient(135deg,#fefce8,#fef3c7);border:1px solid #fde68a;border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:2rem;display:flex;align-items:flex-start;gap:1rem;">
                    <div style="font-size:1.5rem;flex-shrink:0;">💡</div>
                    <div>
                        <h4 style="font-weight:700;font-size:0.95rem;color:#92400e;margin:0 0 0.25rem;">Developmental Tip</h4>
                        <p style="font-size:0.85rem;color:#a16207;margin:0;line-height:1.5;">${devTip}</p>
                    </div>
                </div>

                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:2.5rem 2rem;text-align:center;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);margin-bottom:2rem;background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                    <div style="font-size:3.5rem;margin-bottom:1rem;line-height:1;filter:drop-shadow(0 4px 6px rgba(0,0,0,0.1));">📋</div>
                    <h3 style="font-weight:800;font-size:1.4rem;color:#1e293b;margin-bottom:0.5rem;letter-spacing:-0.5px;">Milestones Checklist</h3>
                    <p style="color:#64748b;font-size:1rem;margin-bottom:2rem;max-width:450px;margin-left:auto;margin-right:auto;line-height:1.6;">Track ${name}'s progress. Every achieved milestone earns <strong style="color:#22c55e;font-weight:800;background:#dcfce7;padding:0.2rem 0.5rem;border-radius:8px;">+15 points</strong>!</p>
                    <button onclick="openMilestonesModal(${childId})" class="btn btn-gradient" style="padding:1rem 2.5rem;font-size:1.1rem;border-radius:14px;box-shadow:0 10px 15px -3px rgba(99,102,241,0.3);transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 15px 20px -3px rgba(99,102,241,0.4)';" onmouseout="this.style.transform='';this.style.boxShadow='0 10px 15px -3px rgba(99,102,241,0.3)';">
                        Open Growth Checklist
                    </button>
                </div>
            </div>
        `;
    }

    window.openMilestonesModal = function () {
        if (!window._currentMilestones) return;
        const gross = window._currentMilestones.gross;
        const fine = window._currentMilestones.fine;
        const childId = window._currentMilestones.childId;

        const renderCategory = (title, icon, items, color) => {
            return `<div class="dashboard-card" style="margin-bottom:1.5rem;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.02);overflow:hidden;border:1px solid #e2e8f0;">
                <div class="card-header" style="background:#f8fafc;padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;">
                    <h3 class="card-title" style="margin:0;font-weight:800;font-size:1.1rem;color:var(--slate-800);">${icon} ${title}</h3>
                    <span class="badge" style="background:${color}20;color:${color};font-size:0.85rem;padding:0.35rem 0.75rem;">${items.filter(m => m.is_achieved == 1).length} / ${items.length}</span>
                </div>
                <div style="display:grid;gap:1px;background:var(--slate-100);">
                    ${items.map(m => `
                    <div style="background:#ffffff;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1.25rem;transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                        <div style="display:flex;align-items:center;justify-content:center;">
                            <input type="checkbox" ${m.is_achieved == 1 ? 'checked' : ''} style="width:1.5rem;height:1.5rem;accent-color:${color};cursor:pointer;border-radius:6px;border:2px solid #e2e8f0;"
                                onchange="toggleMilestone(${m.id}, ${childId}, this.checked)">
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:1.05rem;font-weight:600;${m.is_achieved == 1 ? 'text-decoration:line-through;color:var(--slate-400);' : 'color:var(--slate-700);'}">${m.milestone_name}</div>
                            ${m.achieved_at ? `<div style="font-size:0.75rem;color:var(--slate-400);margin-top:0.25rem;font-weight:500;">✓ Achieved on ${new Date(m.achieved_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric' })}</div>` : ''}
                        </div>
                    </div>`).join('')}
                </div>
            </div>`;
        };

        const modalHtml = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:650px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
                    <div style="background:linear-gradient(135deg, #3b82f6, #8b5cf6);padding:2rem;color:#fff;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h2 style="margin:0 0 0.25rem;font-size:1.5rem;font-weight:800;">Milestones Checklist</h2>
                            <p style="margin:0;opacity:0.9;font-size:0.95rem;">Track physical development step-by-step</p>
                        </div>
                        <button onclick="document.getElementById('milestone-modal').remove()" style="background:rgba(255,255,255,0.2);border:none;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">✕</button>
                    </div>
                    
                    <div id="milestone-modal-content" style="padding:2rem;overflow-y:auto;flex:1;">
                        ${renderCategory('Gross Motor Skills', '🦵', gross, '#22c55e')}
                        ${renderCategory('Fine Motor Skills', '✋', fine, '#3b82f6')}
                    </div>
                </div>
            </div>
        `;

        let existing = document.getElementById('milestone-modal');
        if (existing) {
            const content = document.getElementById('milestone-modal-content');
            if (content) {
                content.innerHTML = renderCategory('Gross Motor Skills', '🦵', gross, '#22c55e') + renderCategory('Fine Motor Skills', '✋', fine, '#3b82f6');
            }
        } else {
            const modal = document.createElement('div');
            modal.id = 'milestone-modal';
            modal.innerHTML = modalHtml;
            document.body.appendChild(modal);
        }
    };

    async function loadMotorMilestones(childId) {
        if (!childId) return;

        try {
            const res = await fetch('../../api_motor.php?action=list&child_id=' + childId);
            const data = await res.json();
            const milestones = data.milestones || [];

            const gross = milestones.filter(m => m.category === 'gross_motor');
            const fine = milestones.filter(m => m.category === 'fine_motor');

            // Save state globally so the modal can access it
            window._currentMilestones = { gross, fine, childId };

            // If the modal is currently open, refresh its content
            if (document.getElementById('milestone-modal')) {
                window.openMilestonesModal();
            }

            // Update progress
            const grossDone = gross.filter(m => m.is_achieved == 1).length;
            const fineDone = fine.filter(m => m.is_achieved == 1).length;

            const grossEl = document.getElementById('gross-motor-progress');
            const fineEl = document.getElementById('fine-motor-progress');
            if (grossEl) grossEl.innerHTML = `${grossDone}/${gross.length} <span style="font-size:0.85rem;font-weight:500;">achieved</span>`;
            if (fineEl) fineEl.innerHTML = `${fineDone}/${fine.length} <span style="font-size:0.85rem;font-weight:500;">achieved</span>`;

            // Overall progress
            var totalDone = grossDone + fineDone;
            var totalAll = gross.length + fine.length;
            var overallEl = document.getElementById('motor-overall-progress');
            if (overallEl) {
                var pct = totalAll > 0 ? Math.round((totalDone / totalAll) * 100) : 0;
                overallEl.innerHTML = pct + '% <span style="font-size:0.85rem;font-weight:500;">complete</span>';
            }

            // Render doughnut chart
            var doughnutEl = document.getElementById('motor-progress-doughnut');
            if (doughnutEl && typeof Chart !== 'undefined' && totalAll > 0) {
                new Chart(doughnutEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Gross Achieved', 'Fine Achieved', 'Remaining'],
                        datasets: [{ data: [grossDone, fineDone, totalAll - totalDone], backgroundColor: ['#22c55e', '#3b82f6', '#e2e8f0'], borderWidth: 0, cutout: '70%' }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: true,
                        plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } } }
                    }
                });
            }
            var descEl = document.getElementById('motor-chart-desc');
            if (descEl && totalAll > 0) {
                var pct2 = Math.round((totalDone / totalAll) * 100);
                descEl.textContent = totalDone + ' of ' + totalAll + ' milestones achieved (' + pct2 + '%). ' + (pct2 >= 75 ? 'Excellent progress! 🎉' : pct2 >= 50 ? 'Good progress — keep going!' : 'Keep encouraging daily practice.');
            }

            var d = window.dashboardData || {};
            var cd = d.children ? d.children[window._selectedChildIndex] : null;
            if (cd && cd.growth_history && typeof plotChart === 'function' && document.getElementById('motor-chart')) {
                const cdob = new Date(cd.dob);
                let motorDataObj = [];
                cd.growth_history.forEach(r => {
                    const rdate = new Date(r.recorded_at);
                    const ageM = (rdate - cdob) / (1000 * 60 * 60 * 24 * 30.44);
                    if (r.motor_milestones_score !== null && r.motor_milestones_score !== undefined) {
                        motorDataObj.push({ x: ageM, y: parseFloat(r.motor_milestones_score) });
                    }
                });
                plotChart('motor-chart', 'bar', 'Motor Development Milestones', 'Score', 'Age (months)', motorDataObj, [], '#10b981', true);
            }
        } catch (e) {
            container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:#64748b;"><div style="font-size:2rem;margin-bottom:0.5rem;">🏃</div><p>Could not load motor milestones.</p></div>';
        }
    }

    window.toggleMilestone = async function (milestoneId, childId, isAchieved) {
        try {
            const res = await fetch('../../api_motor.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ milestone_id: milestoneId, child_id: childId, is_achieved: isAchieved ? 1 : 0 })
            });
            const data = await res.json();
            if (data.success && isAchieved && data.points_awarded) {
                showBadgeToast('Milestone achieved! +' + data.points_awarded + ' points 🎉');
            }
            // Refresh the checklist
            loadMotorMilestones(childId);
        } catch (e) { console.error('Toggle milestone error:', e); }
    };

    function getActivitiesView() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[0] || null;
        const childParam = child ? child.child_id : '';

        // Load AI recommendations after rendering
        setTimeout(() => loadAIRecommendations(childParam), 200);

        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Activity Center 🎨</h1>
                        <p class="dashboard-subtitle">Age-based personalized recommendations for ${child ? child.first_name : 'your child'}</p>
                    </div>
                </div>

                <!-- AI Recommendations Container -->
                <div id="ai-recommendations">
                    <div class="ai-loading-state">
                        <div class="ai-shimmer-container">
                            <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                            <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                            <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                        </div>
                        <p style="text-align:center;color:var(--slate-500);margin-top:1.5rem;font-size:1rem;">
                            <span style="font-size:1.5rem;">🤖</span> AI is analyzing ${child ? child.first_name + "'s" : "your child's"} data and preparing personalized recommendations...
                        </p>
                    </div>
                </div>

                <!-- Activity History -->
                <div style="margin-top:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:1rem;">
                        <h3 class="section-heading" style="margin:0;">Completed Activities</h3>
                        <div style="display:flex;gap:0.5rem;" class="activity-tabs">
                            <button class="btn btn-sm btn-gradient active" onclick="loadActivityHistory('${childParam}', 'all', this)">All Time</button>
                            <button class="btn btn-sm btn-outline" onclick="loadActivityHistory('${childParam}', 'daily', this)">Daily</button>
                            <button class="btn btn-sm btn-outline" onclick="loadActivityHistory('${childParam}', 'weekly', this)">Weekly</button>
                            <button class="btn btn-sm btn-outline" onclick="loadActivityHistory('${childParam}', 'monthly', this)">Monthly</button>
                        </div>
                    </div>
                    <div id="activity-history-list">
                        <div class="dashboard-card" style="padding:1.5rem;text-align:center;color:var(--slate-500);">Loading history...</div>
                    </div>
                </div>
            </div>
        `;
    }

    // ── AI Recommendations Loader ─────────────────────────────
    window.loadAIRecommendations = async function (childId) {
        const container = document.getElementById('ai-recommendations');
        const btn = document.getElementById('ai-refresh-btn');
        if (!container) return;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

        container.innerHTML = `
            <div class="ai-loading-state">
                <div class="ai-shimmer-container">
                    <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                    <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                    <div class="ai-shimmer-card"><div class="shimmer"></div></div>
                </div>
                <p style="text-align:center;color:var(--slate-500);margin-top:1.5rem;">
                    <span style="font-size:1.5rem;">🤖</span> AI is generating personalized recommendations...
                </p>
            </div>`;

        try {
            const res = await fetch('../../api_activities.php?action=recommend&child_id=' + childId);
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();

            if (data.error) {
                container.innerHTML = `<div class="dashboard-card" style="padding:2rem;text-align:center;">
                    <p style="color:var(--red-500);margin-bottom:1rem;">⚠️ ${data.error}</p>
                    <button class="btn btn-outline" onclick="loadAIRecommendations('${childId}')">Try Again</button>
                </div>`;
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                return;
            }

            const rec = data.recommendations;
            let html = '';

            // Articles Section
            if (rec.articles && rec.articles.length) {
                html += `<h3 class="section-heading" style="display:flex;align-items:center;gap:0.5rem;">📚 Recommended Articles</h3>
                <div class="ai-cards-grid">`;
                rec.articles.forEach((art, i) => {
                    const catColors = { parenting: '#6366f1', development: '#8b5cf6', health: '#22c55e', nutrition: '#f59e0b' };
                    const color = catColors[art.category] || '#6366f1';
                    const esTitle = art.title.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const esDesc = (art.summary || '').replace(/'/g, "\\'").replace(/"/g, "&quot;");

                    html += `<div class="ai-card ai-card-article" style="--accent:${color}">
                        <div class="ai-card-badge" style="background:${color}15;color:${color}">${art.category || 'article'}</div>
                        <h4 class="ai-card-title">${art.title}</h4>
                        <p class="ai-card-desc">${art.summary}</p>
                        <div class="ai-card-footer">
                            <span class="ai-card-meta">📖 ${art.read_time || '5 min read'}</span>
                            <button class="btn btn-outline btn-sm" onclick="openArticleModal(${childId}, ${i}, '${esTitle}', '${esDesc}')">Read Article</button>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }

            // Real-Life Activities Section
            if (rec.real_life_activities && rec.real_life_activities.length) {
                html += `<h3 class="section-heading" style="display:flex;align-items:center;gap:0.5rem;margin-top:2rem;">🎯 Real-Life Activities</h3>
                <div class="ai-cards-grid">`;
                rec.real_life_activities.forEach((act, i) => {
                    const catIcons = { motor: '💪', speech: '🗣️', cognitive: '🧠', social: '🤝' };
                    const icon = catIcons[act.category] || '🎯';
                    const diffColors = { easy: '#22c55e', medium: '#f59e0b', hard: '#ef4444' };
                    const diffColor = diffColors[act.difficulty] || '#f59e0b';
                    const esTitle = act.title.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const esDesc = (act.description || '').replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const esMat = (act.materials || '').replace(/'/g, "\\'").replace(/"/g, "&quot;");

                    html += `<div class="ai-card ai-card-activity">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                            <span style="font-size:1.75rem;">${icon}</span>
                            <span class="ai-card-badge" style="background:${diffColor}15;color:${diffColor}">${act.difficulty || 'medium'}</span>
                        </div>
                        <h4 class="ai-card-title">${act.title}</h4>
                        <p class="ai-card-desc">${act.description}</p>
                        ${act.materials ? `<p style="font-size:0.8rem;color:var(--slate-500);margin-top:0.5rem;">🧰 Materials: ${act.materials}</p>` : ''}
                        <div class="ai-card-footer">
                            <span class="ai-card-meta">⏱️ ${act.duration || '15 min'}</span>
                            <button class="btn btn-gradient btn-sm" onclick="openActivityModal(${childId}, ${i}, '${esTitle}', '${esDesc}', '${esMat}', '${act.category}')">View Details</button>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }

            // Website Games Section
            if (rec.website_games && rec.website_games.length) {
                html += `<h3 class="section-heading" style="display:flex;align-items:center;gap:0.5rem;margin-top:2rem;">🎮 Website Games & Interactive Activities</h3>
                <div class="ai-cards-grid">`;
                rec.website_games.forEach((game, i) => {
                    const typeIcons = { interactive: '🕹️', quiz: '❓', creative: '🎨' };
                    const icon = typeIcons[game.type] || '🎮';
                    const esTitle = game.title.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    html += `<div class="ai-card ai-card-game">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                            <span style="font-size:1.75rem;">${icon}</span>
                            <span class="ai-card-badge" style="background:#8b5cf615;color:#8b5cf6">${game.type || 'interactive'}</span>
                        </div>
                        <h4 class="ai-card-title">${game.title}</h4>
                        <p class="ai-card-desc">${game.description}</p>
                        <div class="ai-card-footer">
                            <span class="ai-card-meta">🎯 ${game.skill_focus || 'Development'} • ${game.duration || '10 min'}</span>
                            <button class="btn btn-outline btn-sm" onclick="openGameModal(${childId}, ${i}, '${esTitle}')">Play ▶</button>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }

            container.innerHTML = html;

            // Load activity history
            loadActivityHistory(childId);

        } catch (e) {
            container.innerHTML = `<div class="dashboard-card" style="padding:2rem;text-align:center;">
                <p style="color:var(--red-500);margin-bottom:1rem;">⚠️ Failed to load recommendations</p>
                <button class="btn btn-outline" onclick="loadAIRecommendations('${childId}')">Try Again</button>
            </div>`;
        }
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    };

    window.completeActivity = async function (childId, category, index) {
        try {
            const res = await fetch('../../api_activities.php?action=history&child_id=' + childId);
            const data = await res.json();
            const activities = (data.activities || []).filter(a => !a.is_completed);

            let activityId = null;
            let count = 0;
            for (const act of activities) {
                if (act.category === category || (category === 'real_life' && ['motor', 'speech', 'cognitive', 'social'].includes(act.category))) {
                    if (count === index) { activityId = act.activity_id; break; }
                    count++;
                }
            }

            if (!activityId) {
                if (activities.length > 0) activityId = activities[0].activity_id;
            }

            if (activityId) {
                const res2 = await fetch('../../api_activities.php?action=complete', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ activity_id: activityId, child_id: childId })
                });
                const result = await res2.json();
                if (result.success) {
                    showBadgeToast('Activity completed! +15 points 🎉');
                    streakCheckIn();
                    loadNotifCount();
                    // reload History
                    loadActivityHistory(childId, 'all', document.querySelector('.activity-tabs button.active'));
                }
            }
        } catch (e) { console.error('Complete activity error:', e); }
    };

    window.openArticleModal = function (childId, index, title, summary) {
        let existing = document.getElementById('act-modal');
        if (existing) existing.remove();
        const randImg = 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=600&h=300';

        const modal = document.createElement('div');
        modal.id = 'act-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:600px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
                    <button onclick="document.getElementById('act-modal').remove()" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,0.8);backdrop-filter:blur(4px);border:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;color:#0f172a;box-shadow:0 2px 5px rgba(0,0,0,0.1);">✕</button>
                    <img src="${randImg}" style="width:100%;height:200px;object-fit:cover;" />
                    <div style="padding:2rem;overflow-y:auto;flex:1;">
                        <span class="badge badge-blue" style="margin-bottom:1rem;display:inline-block;">Article</span>
                        <h2 style="font-size:1.75rem;font-weight:800;color:var(--slate-900);margin:0 0 1rem;">${title}</h2>
                        <div style="font-size:1rem;color:var(--slate-700);line-height:1.7;margin-bottom:2rem;">
                            <p style="font-weight:600;font-size:1.1rem;color:var(--slate-800);">${summary}</p>
                            <p>Reading together with your child opens up a world of imagination and learning. This article emphasizes practical ways to introduce the topic into your daily routine. Take 5 minutes today to focus on these concepts while avoiding distractions.</p>
                            <p>Consistency is key. The more you revisit these ideas, the stronger the conceptual connections become in your child's developing mind.</p>
                        </div>
                        <button id="mark-read-btn" onclick="markArticleRead(${childId}, ${index}, '${title.replace(/'/g, "\\'")}', this)" class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1.1rem;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Mark as Read & Complete</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    window.openActivityModal = function (childId, index, title, desc, mat, category) {
        let existing = document.getElementById('act-modal');
        if (existing) existing.remove();
        const randImg = 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&q=80&w=600&h=300';

        const modal = document.createElement('div');
        modal.id = 'act-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:600px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
                    <button onclick="document.getElementById('act-modal').remove()" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,0.8);backdrop-filter:blur(4px);border:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;color:#0f172a;box-shadow:0 2px 5px rgba(0,0,0,0.1);">✕</button>
                    <img src="${randImg}" style="width:100%;height:200px;object-fit:cover;" />
                    <div style="padding:2rem;overflow-y:auto;flex:1;">
                        <span class="badge badge-green" style="margin-bottom:1rem;display:inline-block;text-transform:capitalize">${category.replace('_', ' ')} Activity</span>
                        <h2 style="font-size:1.75rem;font-weight:800;color:var(--slate-900);margin:0 0 0.5rem;">${title}</h2>
                        <p style="font-size:1.1rem;color:var(--slate-600);margin-bottom:1.5rem;line-height:1.6">${desc}</p>
                        
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;margin-bottom:2rem;">
                            <h4 style="font-weight:700;margin-bottom:0.75rem;display:flex;align-items:center;gap:8px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Instructions
                            </h4>
                            <p style="margin-bottom:1rem;line-height:1.6;color:var(--slate-700)">Set up a comfortable space. Gather the following materials, then follow the core activity steps slowly so your child can engage.</p>
                            ${mat ? `<div style="background:#fff;padding:1rem;border-radius:8px;border:1px solid #e2e8f0;font-weight:600;color:var(--slate-800);"><span style="color:var(--slate-500);font-weight:500;">Materials needed:</span><br/>${mat}</div>` : ''}
                        </div>

                        <button onclick="
                            completeActivity(${childId}, '${category}', ${index});
                            document.getElementById('act-modal').remove();
                        " class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1.1rem;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Complete Activity</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    window.openGameModal = function (childId, index, title) {
        let existing = document.getElementById('act-modal');
        if (existing) existing.remove();

        // Define different game templates based on the game title
        let gameHtml = '';
        let gameScript = '';

        if (title.toLowerCase().includes('shape') || title.toLowerCase().includes('sort')) {
            // Shape Sorter Game
            gameHtml = `
                <div style="display:flex;justify-content:center;gap:2rem;margin-bottom:2rem;" id="shape-targets">
                    <div class="shape-target" data-shape="circle" style="width:80px;height:80px;border:3px dashed #cbd5e1;border-radius:50%;display:flex;align-items:center;justify-content:center;"></div>
                    <div class="shape-target" data-shape="square" style="width:80px;height:80px;border:3px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;"></div>
                    <div class="shape-target" data-shape="triangle" style="width:0;height:0;border-left:40px solid transparent;border-right:40px solid transparent;border-bottom:80px dashed #cbd5e1;position:relative;"></div>
                </div>
                <div style="display:flex;justify-content:center;gap:1.5rem;" id="shape-sources">
                    <div class="shape-draggable" data-shape="square" style="width:60px;height:60px;background:#3b82f6;cursor:pointer;transition:transform 0.2s;" onclick="handleShapeClick(this)"></div>
                    <div class="shape-draggable" data-shape="triangle" style="width:0;height:0;border-left:35px solid transparent;border-right:35px solid transparent;border-bottom:60px solid #ef4444;cursor:pointer;transition:transform 0.2s;" onclick="handleShapeClick(this)"></div>
                    <div class="shape-draggable" data-shape="circle" style="width:60px;height:60px;background:#22c55e;border-radius:50%;cursor:pointer;transition:transform 0.2s;" onclick="handleShapeClick(this)"></div>
                </div>
                <p id="shape-msg" style="color:#64748b;margin-top:1.5rem;">Click a shape to sort it to the matching spot!</p>
            `;
            window.shapeState = { sorted: 0 };
            window.handleShapeClick = function (el) {
                const shape = el.dataset.shape;
                const targets = document.querySelectorAll('.shape-target');
                targets.forEach(t => {
                    if (t.dataset.shape === shape && !t.classList.contains('filled')) {
                        t.style.borderStyle = 'solid';
                        t.style.borderColor = shape === 'circle' ? '#22c55e' : (shape === 'square' ? '#3b82f6' : '#ef4444');
                        t.style.backgroundColor = t.style.borderColor;
                        t.classList.add('filled');
                        el.style.visibility = 'hidden';
                        window.shapeState.sorted++;
                        if (window.shapeState.sorted === 3) {
                            document.getElementById('shape-msg').textContent = 'Great Job! All shapes sorted.';
                            document.getElementById('game-finish-btn').disabled = false;
                            document.getElementById('game-finish-btn').innerHTML = 'Claim Points 🎉';
                            document.getElementById('game-finish-btn').classList.add('btn-gradient');
                        }
                    }
                });
            };
        } else if (title.toLowerCase().includes('color') || title.toLowerCase().includes('balloon')) {
            // Balloon Pop
            gameHtml = `
                <div style="position:relative;height:250px;background:#f8fafc;border-radius:12px;overflow:hidden;" id="balloon-container">
                    <p style="text-align:center;color:#94a3b8;position:absolute;top:50%;left:0;right:0;margin-top:-10px;pointer-events:none;">Pop 5 balloons!</p>
                </div>
            `;
            setTimeout(() => {
                const c = document.getElementById('balloon-container');
                if (!c) return;
                window.balloonState = { popped: 0 };
                window.popBalloon = function (el) {
                    el.style.transform = 'scale(0)';
                    setTimeout(() => el.remove(), 200);
                    window.balloonState.popped++;
                    if (window.balloonState.popped >= 5) {
                        document.getElementById('game-finish-btn').disabled = false;
                        document.getElementById('game-finish-btn').innerHTML = 'Claim Points 🎉';
                        document.getElementById('game-finish-btn').classList.add('btn-gradient');
                    }
                };
                for (let i = 0; i < 6; i++) {
                    const b = document.createElement('div');
                    const colors = ['#ef4444', '#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6'];
                    b.style.cssText = `position:absolute;width:40px;height:50px;border-radius:50%;background:${colors[i % 5]};bottom:-60px;left:${15 + Math.random() * 70}%;cursor:pointer;transition:transform 0.2s;animation:float ${3 + Math.random() * 2}s linear infinite;box-shadow:inset -5px -5px 10px rgba(0,0,0,0.1);`;
                    b.onclick = function () { window.popBalloon(this); };
                    c.appendChild(b);
                }
            }, 100);
        } else {
            // Default Memory Match
            const emojis = ['🌟', '🍎', '🐶', '🚗', '🌟', '🍎', '🐶', '🚗'].sort(() => Math.random() - 0.5);
            gameHtml = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:1.5rem 0;" id="memory-board">';
            emojis.forEach((e) => {
                gameHtml += `<button class="memory-card" data-emoji="${e}" onclick="handleMemoryClick(this)" style="height:70px;font-size:1.8rem;background:#f1f5f9;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:all 0.3s;color:transparent;">❓</button>`;
            });
            gameHtml += '</div>';

            window.memoryState = { opened: [], matched: 0 };
            window.handleMemoryClick = function (btn) {
                if (btn.classList.contains('matched') || btn.classList.contains('opened') || window.memoryState.opened.length >= 2) return;
                btn.style.background = '#fff';
                btn.style.color = '#000';
                btn.textContent = btn.dataset.emoji;
                btn.classList.add('opened');
                window.memoryState.opened.push(btn);

                if (window.memoryState.opened.length === 2) {
                    if (window.memoryState.opened[0].dataset.emoji === window.memoryState.opened[1].dataset.emoji) {
                        window.memoryState.opened.forEach(b => { b.classList.add('matched'); b.classList.remove('opened'); b.style.borderColor = '#22c55e'; b.style.background = '#dcfce7'; });
                        window.memoryState.opened = [];
                        window.memoryState.matched += 2;
                        if (window.memoryState.matched === emojis.length) {
                            document.getElementById('game-finish-btn').disabled = false;
                            document.getElementById('game-finish-btn').innerHTML = 'Claim Points 🎉';
                            document.getElementById('game-finish-btn').classList.add('btn-gradient');
                        }
                    } else {
                        setTimeout(() => {
                            window.memoryState.opened.forEach(b => {
                                b.classList.remove('opened');
                                b.textContent = '❓';
                                b.style.color = 'transparent';
                                b.style.background = '#f1f5f9';
                            });
                            window.memoryState.opened = [];
                        }, 800);
                    }
                }
            };
        }

        const modal = document.createElement('div');
        modal.id = 'act-modal';
        modal.innerHTML = `
            <style>
                @keyframes float { 0% { transform: translateY(0); } 100% { transform: translateY(-350px); } }
            </style>
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.8);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:500px;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.5);overflow:hidden;text-align:center;padding:3rem 2rem;position:relative;">
                    <button onclick="document.getElementById('act-modal').remove()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;color:#64748b;font-size:1.5rem;">✕</button>
                    <h2 style="font-size:1.75rem;font-weight:800;color:var(--slate-900);margin:0 0 0.5rem;letter-spacing:-0.03em;">🎮 ${title}</h2>
                    <p style="color:var(--slate-500);margin-bottom:1rem;font-size:0.9rem;">Play the game to earn rewards!</p>
                    
                    ${gameHtml}

                    <button id="game-finish-btn" disabled onclick="
                        completeActivity(${childId}, 'website_game', ${index});
                        document.getElementById('act-modal').remove();
                    " class="btn" style="width:100%;padding:1rem;font-size:1.1rem;background:#e2e8f0;color:#94a3b8;cursor:not-allowed;margin-top:1.5rem;">Complete Game First!</button>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    window.loadActivityHistory = async function (childId, period = 'all', btnEl = null) {
        if (btnEl) {
            const tabs = btnEl.parentElement.querySelectorAll('button');
            tabs.forEach(b => {
                b.classList.remove('active', 'btn-gradient');
                b.classList.add('btn-outline');
            });
            btnEl.classList.remove('btn-outline');
            btnEl.classList.add('active', 'btn-gradient');
        }
        const container = document.getElementById('activity-history-list');
        if (!container) return;
        try {
            const res = await fetch(`../../api_activities.php?action=history&child_id=${childId}&period=${period}`);
            const data = await res.json();
            const completed = (data.activities || []).filter(a => a.is_completed == 1);
            if (completed.length === 0) {
                container.innerHTML = '<div class="dashboard-card" style="padding:1.5rem;text-align:center;color:var(--slate-500);">No completed activities yet. Start by completing a recommendation above!</div>';
                return;
            }
            container.innerHTML = completed.slice(0, 10).map(a => {
                const dt = new Date(a.completed_at);
                const dateStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const catIcons = { article: '📚', real_life: '🎯', website_game: '🎮', motor: '💪', speech: '🗣️', cognitive: '🧠', social: '🤝' };
                const icon = catIcons[a.category] || '✅';
                return `<div class="dashboard-card" style="padding:1rem;margin-bottom:0.5rem;display:flex;align-items:center;gap:1rem;">
                    <span style="font-size:1.5rem;">${icon}</span>
                    <div style="flex:1;"><h4 style="font-weight:600;margin-bottom:0.25rem;">${a.title}</h4>
                    <span style="font-size:0.8rem;color:var(--slate-500);">${dateStr} • +${a.points_earned} pts</span></div>
                    <span class="badge badge-green">Completed</span>
                </div>`;
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="dashboard-card" style="padding:1rem;text-align:center;color:var(--slate-500);">Could not load history</div>';
        }
    }

    function getClinicView() {
        const d = window.dashboardData || {};
        const appts = d.appointments || [];

        setTimeout(async () => {
            const specList = document.getElementById('specialist-list');
            if (!specList) return;
            try {
                const res = await fetch('../../api_specialists.php');
                const data = await res.json();
                if (data.success && data.specialists && data.specialists.length > 0) {
                    window._allSpecialists = data.specialists;

                    // Extract unique specializations and locations for filter drops
                    let specs = [...new Set(data.specialists.map(s => s.specialization).filter(Boolean))];
                    let locs = [...new Set(data.specialists.map(s => s.location).filter(Boolean))];

                    let sHtml = '<option value="">All Specialties</option>' + specs.map(s => `<option value="${s}">${s}</option>`).join('');
                    let lHtml = '<option value="">All Locations</option>' + locs.map(l => `<option value="${l}">${l}</option>`).join('');

                    const specF = document.getElementById('spec-filter');
                    const locF = document.getElementById('loc-filter');
                    if (specF) specF.innerHTML = sHtml;
                    if (locF) locF.innerHTML = lHtml;

                    window.renderSpecialists();
                } else {
                    specList.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">No specialists found.</div>';
                }
            } catch (e) {
                console.error(e);
                specList.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:#ef4444;">Failed to load specialists.</div>';
            }
        }, 50);

        window.renderSpecialists = function () {
            const specList = document.getElementById('specialist-list');
            if (!specList || !window._allSpecialists) return;

            const q = (document.getElementById('spec-search')?.value || '').toLowerCase();
            const sf = document.getElementById('spec-filter')?.value || '';
            const lf = document.getElementById('loc-filter')?.value || '';

            let filtered = window._allSpecialists.filter(s => {
                let matchQ = !q || (s.first_name + ' ' + s.last_name + ' ' + s.clinic_name + ' ' + s.specialization).toLowerCase().includes(q);
                let matchS = !sf || s.specialization === sf;
                let matchL = !lf || s.location === lf;
                return matchQ && matchS && matchL;
            });

            if (filtered.length === 0) {
                specList.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">No specialists match your filters.</div>';
                return;
            }

            let h = '';
            filtered.forEach(s => {
                const avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(s.first_name)}+${encodeURIComponent(s.last_name)}&background=random`;
                h += `
                <div class="dashboard-card" style="display: flex; flex-direction:column; padding: 0; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #e2e8f0; overflow:hidden;" onmouseover="this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05)'">
                    <div style="display: flex; gap: 1.5rem; padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                        <img src="${avatar}" style="width: 5.5rem; height: 5.5rem; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border:3px solid #fff;">
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.25rem;">
                                <div>
                                   <div style="color:var(--slate-500); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700;">Doctor</div>
                                   <h3 style="font-size: 1.25rem; font-weight: 800; color:var(--blue-600); margin:0;">${s.first_name} ${s.last_name}</h3>
                                </div>
                            </div>
                            <p style="color: var(--slate-700); font-weight: 600; font-size: 0.95rem; margin: 0.25rem 0 0.5rem;">${s.specialization || 'Specialist'}</p>
                            
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1rem;">
                                <div style="display:flex;align-items:center;background:#fff5f5;color:#e53e3e;padding:0.25rem 0.6rem;border-radius:6px;font-size:0.8rem;font-weight:700;">⭐ ${s.rating || 'New'} Rating</div>
                                <div style="display:flex;align-items:center;background:#f0fdf4;color:#16a34a;padding:0.25rem 0.6rem;border-radius:6px;font-size:0.8rem;font-weight:700;">💼 ${s.experience_years || 0} Years Exp</div>
                                <div style="display:flex;align-items:center;background:#eff6ff;color:#2563eb;padding:0.25rem 0.6rem;border-radius:6px;font-size:0.8rem;font-weight:700;">💵 Fees: 200 EGP</div>
                            </div>

                            <p style="color: var(--slate-600); font-size: 0.85rem; margin: 0; display:flex; align-items:center; gap:0.35rem;">
                                📍 <strong>Clinic:</strong> ${s.clinic_name || 'Independent'}, ${s.location || 'Online'}
                            </p>
                        </div>
                    </div>
                    <div style="background:#f8fafc; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <span style="font-size:0.85rem; color:var(--slate-500); font-weight:600;">🕒 Next available slot: Tomorrow, 10:00 AM</span>
                        <div style="display:flex; gap:0.5rem;">
                            <button class="btn btn-outline btn-sm" onclick="viewDoctorInfo(${s.specialist_id})" style="padding:0.5rem 1rem; border-color:var(--slate-300); color:var(--slate-700);">View Doctor Profile</button>
                            <button class="btn btn-gradient btn-sm" onclick="bookSpecialist(${s.specialist_id}, 'Dr. ${s.first_name} ${s.last_name}')" style="padding:0.5rem 1.25rem; background:linear-gradient(135deg, #e11d48, #be123c); color:white; border:none; box-shadow:0 10px 15px -3px rgba(225, 29, 72, 0.3);">Book Now</button>
                        </div>
                    </div>
                </div>`;
            });
            specList.innerHTML = h;
        };

        let apptHtml = '';
        if (appts.length > 0) {
            appts.forEach(a => {
                const dt = new Date(a.scheduled_at);
                const dateStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                apptHtml += `
                <div class="appointment-item" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:1.25rem; margin-bottom:1rem; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <span class="badge ${a.status === 'Scheduled' ? 'badge-blue' : 'badge-green'}" style="font-size:0.75rem;">${a.status}</span>
                        <span style="font-weight: 600; color:var(--slate-600); font-size:0.8rem;">${a.type === 'onsite' ? '📍 Clinic Visit' : '💻 Online Session'}</span>
                    </div>
                    
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:0.5rem; text-align:center; min-width:65px; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                            <div style="font-size:0.75rem; color:#ef4444; font-weight:800; text-transform:uppercase;">${dt.toLocaleDateString('en-US', { month: 'short' })}</div>
                            <div style="font-size:1.75rem; font-weight:800; color:var(--slate-800); line-height:1; padding:0.2rem 0;">${dt.getDate()}</div>
                        </div>
                        <div style="flex:1;">
                            <h4 style="margin:0 0 0.35rem; font-size:1.05rem; font-weight:800; color:var(--slate-900);">Dr. ${a.doc_fname} ${a.doc_lname}</h4>
                            <div style="font-size: 0.85rem; color: var(--slate-500); display:flex; align-items:center; gap:0.35rem;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ${dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</div>
                        </div>
                    </div>
                </div>`;
            });
        } else {
            apptHtml = '<p style="color:var(--slate-500);text-align:center;padding:1.5rem 0;">No upcoming appointments</p>';
        }

        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Book Appointments 🏥</h1>
                        <p class="dashboard-subtitle">Connect with trusted healthcare providers</p>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: 3fr 2fr; gap: 2rem;">
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <!-- Filters & Search Bar -->
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <div style="position: relative; flex: 2; min-width: 200px;">
                                <input type="text" id="spec-search" placeholder="Search by name, clinic..." onkeyup="window.renderSpecialists()"
                                    style="width: 100%; padding: 1.15rem 1rem 1.15rem 3.5rem; border: 2px solid var(--slate-200); border-radius: 16px; font-size: 0.95rem; outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'" onblur="this.style.borderColor='var(--slate-200)'">
                                <svg style="position: absolute; left: 1.25rem; top: 50%; transform:translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--slate-400);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <select id="spec-filter" onchange="window.renderSpecialists()" style="width: 100%; padding: 1.15rem 1rem; border: 2px solid var(--slate-200); border-radius: 16px; font-size: 0.95rem; outline:none; background:#fff; cursor:pointer;">
                                    <option value="">All Specialties</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <select id="loc-filter" onchange="window.renderSpecialists()" style="width: 100%; padding: 1.15rem 1rem; border: 2px solid var(--slate-200); border-radius: 16px; font-size: 0.95rem; outline:none; background:#fff; cursor:pointer;">
                                    <option value="">All Locations</option>
                                </select>
                            </div>
                        </div>

                        <!-- Doctors List -->
                        <div id="specialist-list" style="display:flex; flex-direction:column; gap:1.25rem;">
                            <div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin:0 auto 0.5rem;display:block;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                Loading specialists...
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel: Upcoming -->
                    <div>
                        <div class="dashboard-card" style="position:sticky; top:2rem;">
                            <div class="card-header" style="padding: 1.5rem 1.5rem 1rem;">
                                <h3 class="card-title" style="font-size: 1.1rem; display:flex; align-items:center; gap:0.5rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> Your Appointments</h3>
                            </div>
                            <div class="card-content" style="padding: 0 1.5rem 1.5rem;">
                                ${apptHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        `;
    }

    window.viewDoctorInfo = function (specialistId) {
        if (!window._allSpecialists) return;
        const s = window._allSpecialists.find(x => x.specialist_id == specialistId);
        if (!s) return;

        let existing = document.getElementById('act-modal');
        if (existing) existing.remove();

        const avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(s.first_name)}+${encodeURIComponent(s.last_name)}&background=random`;

        const modal = document.createElement('div');
        modal.id = 'act-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:16px;width:100%;max-width:750px;display:flex;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out; position:relative;">
                    <button onclick="document.getElementById('act-modal').remove()" style="position:absolute;top:1rem;right:1rem;background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;color:#64748b;font-weight:bold;transition:background 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">✕</button>
                    
                    <div style="width:250px;background:#f8fafc;padding:2.5rem 2rem;display:flex;flex-direction:column;align-items:center;border-right:1px solid #e2e8f0;">
                        <img src="${avatar}" style="width:120px;height:120px;border-radius:50%;object-fit:cover;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);margin-bottom:1.5rem;" />
                        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);margin:0 0 0.25rem;text-align:center;">Dr. ${s.first_name} ${s.last_name}</h2>
                        <p style="color:var(--blue-600);font-weight:600;font-size:0.9rem;margin:0 0 1.25rem;text-align:center;">${s.specialization || 'Specialist'}</p>
                        <div style="display:flex;align-items:center;gap:0.35rem;background:#fff;padding:0.5rem 1rem;border-radius:20px;border:1px solid #e2e8f0;font-size:0.9rem;font-weight:700;color:var(--slate-700);box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                            <span style="color:#eab308;font-size:1.1rem;">★</span> ${s.rating || 'New'} Rating
                        </div>
                    </div>
                    
                    <div style="flex:1;padding:2.5rem;display:flex;flex-direction:column;background:#ffffff;">
                        <div style="flex:1;">
                            <h4 style="font-weight:800;color:var(--slate-800);margin-bottom:1.5rem;font-size:1.15rem;border-bottom:2px solid #f1f5f9;padding-bottom:0.75rem;">Doctor Information</h4>
                            
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
                                <div>
                                    <div style="font-size:0.8rem;color:var(--slate-500);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">Experience</div>
                                    <div style="font-weight:700;color:var(--slate-800);font-size:1.1rem;">${s.experience_years || 0} Years</div>
                                </div>
                                <div>
                                    <div style="font-size:0.8rem;color:var(--slate-500);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">Consultation Fee</div>
                                    <div style="font-weight:700;color:var(--slate-800);font-size:1.1rem;">From 200 EGP</div>
                                </div>
                                <div style="grid-column:1/-1;">
                                    <div style="font-size:0.8rem;color:var(--slate-500);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">Clinic Location</div>
                                    <div style="font-weight:700;color:var(--slate-800);font-size:1.05rem;display:flex;align-items:flex-start;gap:0.5rem;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="margin-top:2px;flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> 
                                        ${s.clinic_name}, ${s.location}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button onclick="document.getElementById('act-modal').remove(); bookSpecialist(${s.specialist_id}, 'Dr. ${s.first_name} ${s.last_name}')" class="btn btn-gradient" style="width:100%;padding:1.1rem;font-size:1.1rem;font-weight:700;border-radius:12px;box-shadow:0 10px 15px -3px rgba(37,99,235,0.3);transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 15px 20px -3px rgba(37,99,235,0.4)';" onmouseout="this.style.transform='';this.style.boxShadow='0 10px 15px -3px rgba(37,99,235,0.3)';">
                            Book Appointment Now
                        </button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    function getReportsView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[window._selectedChildIndex || 0] || children[0] || null;
        const childParam = child ? '&child_id=' + child.child_id : '';

        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                     <div>
                        <h1 class="dashboard-title">Reports & Insights 📄</h1>
                        <p class="dashboard-subtitle">Download summaries for your healthcare provider</p>
                    </div>
                     <button class="btn btn-gradient" onclick="window.open('../../api_export_pdf.php?type=full-report${childParam}','_blank')">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Generate Full Report
                     </button>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                    <!-- Full Report Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                        <div style="height: 120px; background: linear-gradient(135deg, #6C63FF20, #a78bfa20); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <svg style="width: 3rem; height: 3rem; color: #6C63FF;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Full Development Report</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Growth, appointments & complete profile</p>
                            <span class="badge badge-purple" style="margin-bottom: 1rem;">Full Assessment</span>
                            <div style="margin-top: 1rem;">
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('../../api_export_pdf.php?type=full-report${childParam}','_blank')">📥 Download PDF</button>
                            </div>
                        </div>
                    </div>

                     <!-- Growth Report Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                         <div style="height: 120px; background: linear-gradient(135deg, #22c55e20, #86efac20); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <svg style="width: 3rem; height: 3rem; color: #22c55e;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Growth Report</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Weight, height & head measurements</p>
                             <span class="badge badge-green" style="margin-bottom: 1rem;">Growth Data</span>
                            <div style="margin-top: 1rem;">
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('../../api_export_pdf.php?type=growth-report${childParam}','_blank')">📥 Download PDF</button>
                            </div>
                        </div>
                    </div>

                    <!-- Child Profile Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                         <div style="height: 120px; background: linear-gradient(135deg, #3b82f620, #93c5fd20); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <svg style="width: 3rem; height: 3rem; color: #3b82f6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Child Profile</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Basic info, badges & points</p>
                             <span class="badge badge-blue" style="margin-bottom: 1rem;">Profile Data</span>
                            <div style="margin-top: 1rem;">
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('../../api_export_pdf.php?type=child-report${childParam}','_blank')">📥 Download PDF</button>
                            </div>
                        </div>
                    </div>

                    <!-- Speech Report Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                         <div style="height: 120px; background: linear-gradient(135deg, #7c3aed20, #e879f920); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <span style="font-size: 3rem;">🗣️</span>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Speech Report</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Vocabulary, clarity & full transcripts</p>
                             <span class="badge" style="background:#f3e8ff;color:#9333ea;margin-bottom: 1rem;">Speech Data</span>
                            <div style="margin-top: 1rem;">
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('../../api_export_pdf.php?type=speech-report${childParam}','_blank')">📥 Download PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getSettingsView() {
        const d = window.dashboardData || {};
        const p = d.parent || {};
        const child = (d.children || [])[0] || null;
        const parentName = (p.fname || '') + ' ' + (p.lname || '');
        const parentEmail = p.email || '';
        const childName = child ? child.first_name : '';
        const childBirth = child ? `${child.birth_year}-${String(child.birth_month).padStart(2, '0')}-${String(child.birth_day).padStart(2, '0')}` : '';
        const settings = d.user_settings || {};
        const isDark = (settings.theme === 'dark') || document.documentElement.getAttribute('data-theme') === 'dark';
        const lang = settings.language || localStorage.getItem('language') || 'en';
        const sub = d.subscription || {};

        return `
        <div class="dashboard-content">
                <h1 class="dashboard-title">Settings ⚙️</h1>
                <p class="dashboard-subtitle" style="margin-bottom: 2rem;">Manage your account and app preferences</p>
                
                <div class="settings-layout">
                    <!-- Account Section -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <span class="settings-section-icon">👤</span>
                            <div>
                                <h3 class="settings-section-title">Account</h3>
                                <p class="settings-section-desc">Manage your personal information</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="settings-row">
                                <div class="settings-row-left">
                                    <div class="settings-avatar">${((p.fname || 'U')[0] + (p.lname || 'S')[0]).toUpperCase()}</div>
                                    <div>
                                        <h4 class="settings-row-title">${parentName}</h4>
                                        <p class="settings-row-sub">${parentEmail}</p>
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" onclick="openEditProfileModal('${p.fname || ''}', '${p.lname || ''}', '${p.email || ''}', '${p.phone || ''}')">Edit Profile</button>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title">Child Profile</h4>
                                    <p class="settings-row-sub">${childName || 'No child added'} ${childBirth ? '• Born ' + childBirth : ''}</p>
                                </div>
                                <button class="btn btn-outline btn-sm" onclick="openAddChildModal(${child ? 'window.dashboardData?.children?.[0]' : ''})">Edit</button>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title">Change Password</h4>
                                    <p class="settings-row-sub">Update your account password</p>
                                </div>
                                <button class="btn btn-outline btn-sm" onclick="openChangePasswordModal()">Change</button>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Section -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <span class="settings-section-icon">🎨</span>
                            <div>
                                <h3 class="settings-section-title">Appearance</h3>
                                <p class="settings-section-desc">Customize how the app looks</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title">Dark Mode</h4>
                                    <p class="settings-row-sub">Switch between light and dark themes</p>
                                </div>
                                <label class="settings-toggle">
                                    <input type="checkbox" id="setting-dark-mode" ${isDark ? 'checked' : ''} onchange="handleThemeToggle(this.checked)">
                                    <span class="toggle-slider">
                                        <span class="toggle-icon-sun">☀️</span>
                                        <span class="toggle-icon-moon">🌙</span>
                                    </span>
                                </label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title">Language</h4>
                                    <p class="settings-row-sub">Choose your preferred language</p>
                                </div>
                                <div class="settings-lang-picker">
                                    <button class="lang-btn ${lang === 'en' ? 'active' : ''}" onclick="handleLangChange('en')" id="lang-en">🇬🇧 English</button>
                                    <button class="lang-btn ${lang === 'ar' ? 'active' : ''}" onclick="handleLangChange('ar')" id="lang-ar">🇸🇦 عربي</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Section -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <span class="settings-section-icon">🔔</span>
                            <div>
                                <h3 class="settings-section-title">Notifications</h3>
                                <p class="settings-section-desc">Control how you receive updates</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Push Notifications</h4><p class="settings-row-sub">Receive in-app notifications</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.push_notifications != 0 ? 'checked' : ''} onchange="saveSettingToggle('push_notifications', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Email Notifications</h4><p class="settings-row-sub">Get updates sent to your email</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.email_notifications != 0 ? 'checked' : ''} onchange="saveSettingToggle('email_notifications', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Appointment Reminders</h4><p class="settings-row-sub">Get notified before appointments</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.appointment_reminders != 0 ? 'checked' : ''} onchange="saveSettingToggle('appointment_reminders', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Daily Activity Reminders</h4><p class="settings-row-sub">Get daily suggestions for activities</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.daily_reminders != 0 ? 'checked' : ''} onchange="saveSettingToggle('daily_reminders', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Milestone Alerts</h4><p class="settings-row-sub">Get notified when milestones approach</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.milestone_alerts != 0 ? 'checked' : ''} onchange="saveSettingToggle('milestone_alerts', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy & Data -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <span class="settings-section-icon">🔒</span>
                            <div><h3 class="settings-section-title">Privacy & Data</h3><p class="settings-section-desc">Manage your data and privacy</p></div>
                        </div>
                        <div class="settings-card">
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Data Sharing</h4><p class="settings-row-sub">Share anonymized data to improve the platform</p></div>
                                <label class="settings-toggle"><input type="checkbox" ${settings.data_sharing != 0 ? 'checked' : ''} onchange="saveSettingToggle('data_sharing', this.checked)"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="settings-divider"></div>
                            <div class="settings-row">
                                <div><h4 class="settings-row-title">Export Your Data</h4><p class="settings-row-sub">Download all your data as a file</p></div>
                                <button class="btn btn-outline btn-sm" onclick="window.open('../../api_export_pdf.php?type=full-report','_blank')">Export</button>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <span class="settings-section-icon">💎</span>
                            <div><h3 class="settings-section-title">Subscription</h3><p class="settings-section-desc">Manage your plan</p></div>
                        </div>
                        <div class="settings-card">
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title">Current Plan: <span style="color:var(--blue-600);font-weight:700;">${sub.plan_name || 'Free'}</span></h4>
                                    <p class="settings-row-sub">${sub.price && sub.price !== '0.00' ? '$' + sub.price + '/' + (sub.plan_period || 'month') : 'Free plan — upgrade for more features'}</p>
                                </div>
                                <button class="btn btn-gradient btn-sm" onclick="window.location.href='../../payment.php'">${sub.plan_name === 'Premium' ? 'Manage' : 'Upgrade'}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="settings-section">
                        <div class="settings-card" style="border: 2px solid var(--red-200);">
                            <div class="settings-row">
                                <div>
                                    <h4 class="settings-row-title" style="color:var(--red-600);">Delete Account</h4>
                                    <p class="settings-row-sub">Permanently delete your account and all data. This action cannot be undone.</p>
                                </div>
                                <button class="btn btn-outline btn-sm" style="color:var(--red-600);border-color:var(--red-200);" onclick="confirmDeleteAccount()">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // ── Settings Handlers ─────────────────────────────────────
    window.handleThemeToggle = function (isDark) {
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
        saveSettingToggle('theme', isDark ? 'dark' : 'light');
    };

    window.handleLangChange = function (lang) {
        document.getElementById('lang-en').classList.toggle('active', lang === 'en');
        document.getElementById('lang-ar').classList.toggle('active', lang === 'ar');
        if (lang === 'ar' && document.documentElement.getAttribute('lang') !== 'ar') {
            toggleLanguage();
        } else if (lang === 'en' && document.documentElement.getAttribute('lang') === 'ar') {
            toggleLanguage();
        }
        saveSetting('language', lang);
    };

    window.openEditProfileModal = function (fname, lname, email, phone) {
        let existing = document.getElementById('edit-profile-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'edit-profile-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:500px;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
                    <div style="padding:1.5rem 2rem;border-bottom:1px solid var(--slate-100);display:flex;justify-content:space-between;align-items:center;">
                        <h2 style="font-size:1.5rem;font-weight:700;color:var(--slate-900);margin:0;">Edit Profile</h2>
                        <button onclick="document.getElementById('edit-profile-modal').remove()" style="background:none;border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--slate-500);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M18 6L6 18M6 6l12 12"></path></svg></button>
                    </div>
                    <div style="padding:2rem;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                            <div>
                                <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">First Name</label>
                                <input type="text" id="ep-fname" value="${fname}" style="width:100%;padding:0.75rem 1rem;border:1px solid var(--slate-200);border-radius:12px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'" onblur="this.style.borderColor='var(--slate-200)'">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Last Name</label>
                                <input type="text" id="ep-lname" value="${lname}" style="width:100%;padding:0.75rem 1rem;border:1px solid var(--slate-200);border-radius:12px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'" onblur="this.style.borderColor='var(--slate-200)'">
                            </div>
                        </div>
                        <div style="margin-bottom:1.5rem;">
                            <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Email Address</label>
                            <input type="email" id="ep-email" value="${email}" style="width:100%;padding:0.75rem 1rem;border:1px solid var(--slate-200);border-radius:12px;outline:none;background:var(--slate-50);color:var(--slate-500);" disabled>
                            <p style="font-size:0.75rem;color:var(--slate-500);margin-top:0.5rem;">Email cannot be changed here. Contact support to update.</p>
                        </div>
                        <div style="margin-bottom:2.5rem;">
                            <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Phone Number</label>
                            <input type="text" id="ep-phone" value="${phone === 'null' ? '' : phone}" style="width:100%;padding:0.75rem 1rem;border:1px solid var(--slate-200);border-radius:12px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'" onblur="this.style.borderColor='var(--slate-200)'">
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:1rem;">
                            <button onclick="document.getElementById('edit-profile-modal').remove()" class="btn btn-outline">Cancel</button>
                            <button onclick="saveProfileChanges()" class="btn btn-gradient" id="ep-save-btn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    window.saveProfileChanges = async function () {
        const btn = document.getElementById('ep-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:1rem;height:1rem;margin-right:0.5rem;"></span> Saving...';

        const fname = document.getElementById('ep-fname').value;
        const lname = document.getElementById('ep-lname').value;
        const phone = document.getElementById('ep-phone').value;

        try {
            const formData = new FormData();
            formData.append('update_profile', '1');
            formData.append('fname', fname);
            formData.append('lname', lname);
            formData.append('phone', phone);

            const response = await fetch('../../profile.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                showBadgeToast('Profile updated successfully!');
                document.getElementById('edit-profile-modal').remove();
                fetchDashboardData();
            } else {
                throw new Error('Update failed');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            btn.disabled = false;
            btn.innerHTML = 'Save Changes';
            alert('Failed to update profile. Please try again.');
        }
    };

    window.openChangePasswordModal = function () {
        let existing = document.getElementById('pwd-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'pwd-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:450px;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;text-align:center;padding:3rem 2rem;">
                    <div style="background:var(--blue-50);color:var(--blue-600);width:4rem;height:4rem;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <h2 style="font-size:1.5rem;font-weight:700;color:var(--slate-900);margin:0 0 0.5rem;">Change Password</h2>
                    <p style="color:var(--slate-500);margin-bottom:2rem;line-height:1.5;">To keep your account secure, we will send a password change verification link to your registered email address.</p>
                    
                    <button id="send-pwd-email-btn" onclick="sendPasswordVerificationEmail()" class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1rem;margin-bottom:1rem;">Send Verification Email</button>
                    <button onclick="document.getElementById('pwd-modal').remove()" class="btn btn-ghost" style="width:100%;">Cancel</button>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    window.sendPasswordVerificationEmail = function () {
        const btn = document.getElementById('send-pwd-email-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:1rem;height:1rem;margin-right:0.5rem;"></span> Sending...';

        try {
            const formData = new FormData();
            formData.append('action', 'request_password_change');

            fetch('../../api_auth.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="margin-right:8px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Email Sent!';
                btn.classList.remove('btn-gradient');
                btn.style.background = 'var(--green-500)';
                btn.style.color = '#fff';

                setTimeout(() => {
                    document.getElementById('pwd-modal').remove();
                    showBadgeToast('Verification email sent to your inbox.');
                }, 2000);
            });
        } catch (e) {
            console.error(e);
            btn.innerHTML = 'Error Sending';
        }
    };

    window.saveSettingToggle = function (key, value) {
        const payload = {};
        payload[key] = typeof value === 'boolean' ? (value ? 1 : 0) : value;
        fetch('../../api_settings.php?action=update', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).catch(e => console.warn('Settings save error:', e));
    };

    function saveSetting(key, value) {
        const payload = {};
        payload[key] = value;
        fetch('../../api_settings.php?action=update', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).catch(e => console.warn('Settings save error:', e));
    }

    window.confirmDeleteAccount = function () {
        let existing = document.getElementById('delete-account-modal');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'delete-account-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#fff;border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                    <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;color:var(--red-600);">Delete Account?</h2>
                    <p style="color:var(--slate-500);font-size:0.9rem;margin-bottom:1.5rem;">This will permanently delete your account and all associated data. This action cannot be undone.</p>
                    <div style="display:flex;gap:1rem;">
                        <button onclick="document.getElementById('delete-account-modal').remove()" style="flex:1;padding:0.875rem;background:var(--slate-100);border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Cancel</button>
                        <button style="flex:1;padding:0.875rem;background:var(--red-600);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;" onclick="alert('Account deletion requires admin approval. Please contact support.')">Delete</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    };

    // ── Notifications View ──────────────────────────────────────
    function getNotificationsView() {
        // Load notifications async after rendering
        setTimeout(loadNotifications, 100);
        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div>
                <h1 class="dashboard-title">Notifications 🔔</h1>
                <p class="dashboard-subtitle">Stay updated on your child's progress</p>
            </div>
            <button class="btn btn-outline" onclick="markAllRead()">Mark All Read</button>
            </div>
            <div id="notifications-list"><div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--slate-500);">Loading notifications...</div></div>
        </div>`;
    }

    // Expose switchView globally for sidebar footer buttons
    window.switchView = switchView;

    // ── Notification helpers ─────────────────────────────────────
    window.markNotifRead = async function(id) {
        try {
            await fetch('../../api_notifications.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'read', notification_id: id }) });
            loadNotifications();
            if (typeof loadNotifCount === 'function') loadNotifCount();
        } catch(e){}
    };
    
    window.markAllRead = async function() {
        try {
            await fetch('../../api_notifications.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'read' }) });
            loadNotifications();
            if (typeof loadNotifCount === 'function') loadNotifCount();
        } catch(e){}
    };

    async function loadNotifCount() {
        try {
            const res = await fetch('../../api_notifications.php?action=list&limit=1');
            const data = await res.json();
            // Update sidebar badge
            const badge = document.getElementById('nav-notif-badge');
            if (badge && data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = 'flex';
            }
            // Update top bar badge
            const topBadge = document.getElementById('topbar-notif-badge');
            if (topBadge) {
                if (data.unread_count > 0) {
                    topBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    topBadge.style.display = 'flex';
                } else {
                    topBadge.style.display = 'none';
                }
            }
        } catch (e) { /* silent */ }
    }
    window.loadNotifCount = loadNotifCount;

    window.loadNotifications = async function () {
        const container = document.getElementById('notifications-list');
        if (!container) return;
        try {
            const res = await fetch('../../api_notifications.php?action=list&limit=30');
            const data = await res.json();
            const notifs = data.notifications || [];
            if (notifs.length === 0) {
                container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;"><p style="color:var(--slate-500);">No notifications yet</p></div>';
                return;
            }
            const typeIcons = { appointment_reminder: '📅', payment_success: '💳', growth_alert: '📏', milestone: '🏆', system: '🔔' };
            container.innerHTML = notifs.map(n => {
                const dt = new Date(n.created_at);
                const timeStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                const icon = typeIcons[n.type] || '🔔';
                const unreadStyle = n.is_read == 0 ? 'border-left:4px solid var(--blue-500);' : '';
                return `<div class="dashboard-card" style="padding:1.25rem;margin-bottom:0.75rem;display:flex;gap:1rem;align-items:flex-start;${unreadStyle}cursor:pointer;" onclick="markNotifRead(${n.notification_id})">
                    <div style="font-size:1.5rem;">${icon}</div>
                    <div style="flex:1;"><h4 style="font-weight:600;margin-bottom:0.25rem;">${n.title}</h4><p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.25rem;">${n.message}</p><span style="font-size:0.75rem;color:var(--slate-400);">${timeStr}</span></div>
                </div>`;
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--red-500);">Could not load notifications</div>';
        }
    }

    window.markNotifRead = async function (id) {
        try {
            await fetch('../../api_notifications.php?action=read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: id })
            });
            window.loadNotifications();
            loadNotifCount();
        } catch (e) { }
    };

    window.markAllRead = async function () {
        try {
            await fetch('../../api_notifications.php?action=read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            window.loadNotifications();
            loadNotifCount();
        } catch (e) { }
    };

    // ── WHO Comparison helper ────────────────────────────────────
    async function fetchWHOComparison(childId) {
        try {
            const res = await fetch('../../api_who_compare.php?child_id=' + childId);
            const data = await res.json();
            if (data.measurements) {
                const m = data.measurements;
                const statusColors = { green: 'badge-green', yellow: 'badge-yellow', red: 'badge-red' };
                if (m.weight) {
                    const wb = document.getElementById('who-weight-badge');
                    if (wb) { wb.textContent = m.weight.percentile + 'th Percentile'; wb.className = 'badge ' + (statusColors[m.weight.status] || 'badge-blue'); }
                }
                if (m.height) {
                    const hb = document.getElementById('who-height-badge');
                    if (hb) { hb.textContent = m.height.percentile + 'th Percentile'; hb.className = 'badge ' + (statusColors[m.height.status] || 'badge-blue'); }
                }
                if (m.head_circumference) {
                    const hcb = document.getElementById('who-head-badge');
                    if (hcb) { hcb.textContent = m.head_circumference.percentile + 'th Percentile'; hcb.className = 'badge ' + (statusColors[m.head_circumference.status] || 'badge-blue'); }
                }
                // Show WHO summary card
                const summaryCard = document.getElementById('who-summary');
                const summaryContent = document.getElementById('who-summary-content');
                if (summaryCard && summaryContent) {
                    let html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">';
                    for (const [key, val] of Object.entries(m)) {
                        const label = key === 'head_circumference' ? 'Head Circ.' : key.charAt(0).toUpperCase() + key.slice(1);
                        const color = val.status === 'green' ? '#22c55e' : val.status === 'yellow' ? '#f59e0b' : '#ef4444';
                        html += `<div style="padding:1rem;border-radius:12px;border:2px solid ${color}20;background:${color}08;">
                            <div style="font-weight:600;color:${color};margin-bottom:0.25rem;">${val.label}</div>
                            <div style="font-size:0.875rem;color:var(--slate-600);">${label}: ${val.value}</div>
                            <div style="font-size:0.8rem;color:var(--slate-500);">WHO median: ${val.who_median}</div>
                            <div style="font-size:0.8rem;color:var(--slate-500);">Z-score: ${val.z_score}</div>
                        </div>`;
                    }
                    html += '</div>';
                    summaryContent.innerHTML = html;
                    summaryCard.style.display = 'block';

                    // Generate WHO charts if canvas script is loaded
                    if (typeof Chart !== 'undefined' && data.historical_records && data.who_curve_points) {
                        const chartsHtml = `
                            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(450px, 1fr));gap:2rem;">
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📊 Weight-for-Age</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="who-weight-chart"></canvas></div>
                                    <div id="desc-weight" style="padding:0.75rem 1.5rem 1.25rem;font-size:0.8rem;color:#64748b;line-height:1.5;border-top:1px solid #f1f5f9;"></div>
                                </div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📏 Length/Height-for-Age</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="who-height-chart"></canvas></div>
                                    <div id="desc-height" style="padding:0.75rem 1.5rem 1.25rem;font-size:0.8rem;color:#64748b;line-height:1.5;border-top:1px solid #f1f5f9;"></div>
                                </div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">⚖️ Weight-for-Length</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="who-wl-chart"></canvas></div>
                                    <div id="desc-wl" style="padding:0.75rem 1.5rem 1.25rem;font-size:0.8rem;color:#64748b;line-height:1.5;border-top:1px solid #f1f5f9;"></div>
                                </div>
                                <div id="bmi-gauge-container" style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:380px;padding:1.5rem;">Loading BMI...</div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">🧠 Head Circumference-for-Age</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="who-head-chart"></canvas></div>
                                    <div id="desc-head" style="padding:0.75rem 1.5rem 1.25rem;font-size:0.8rem;color:#64748b;line-height:1.5;border-top:1px solid #f1f5f9;"></div>
                                </div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">💪 Arm Circumference-for-Age</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="who-arm-chart"></canvas></div>
                                    <div id="desc-arm" style="padding:0.75rem 1.5rem 1.25rem;font-size:0.8rem;color:#64748b;line-height:1.5;border-top:1px solid #f1f5f9;"></div>
                                </div>

                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📈 Weight Velocity</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="vel-weight-chart"></canvas></div>
                                </div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📈 Length Velocity</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="vel-height-chart"></canvas></div>
                                </div>
                                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">📈 Head Circumference Velocity</h4></div>
                                    <div style="position:relative;height:300px;padding:1rem;"><canvas id="vel-head-chart"></canvas></div>
                                </div>
                            </div>
                        `;
                        const chartContainer = document.getElementById('who-charts-container');
                        if (chartContainer) {
                            chartContainer.innerHTML = chartsHtml;

                            const dChild = window.dashboardData.children[window._selectedChildIndex || 0];
                            const cdob = new Date(dChild.birth_year, dChild.birth_month - 1, dChild.birth_day);
                            const now = new Date();
                            const ageInMonths = Math.floor((now - cdob) / (1000 * 60 * 60 * 24 * 30.44));
                            const ageYears = Math.floor(ageInMonths / 12);
                            const ageRemMonths = ageInMonths % 12;

                            const weightDataObj = []; const heightDataObj = []; const headDataObj = [];
                            const wlDataObj = []; const bmiDataObj = []; const armDataObj = [];
                            const subDataObj = []; const triDataObj = []; const motorDataObj = [];

                            const velWeight = []; const velHeight = []; const velHead = [];

                            let prevRec = null;
                            data.historical_records.forEach(r => {
                                const rdate = new Date(r.recorded_at);
                                const ageM = (rdate - cdob) / (1000 * 60 * 60 * 24 * 30.44);

                                if (r.weight) weightDataObj.push({ x: ageM, y: parseFloat(r.weight) });
                                if (r.height) heightDataObj.push({ x: ageM, y: parseFloat(r.height) });
                                if (r.weight && r.height) {
                                    wlDataObj.push({ x: parseFloat(r.height), y: parseFloat(r.weight) });
                                    const hm = parseFloat(r.height) / 100;
                                    if (hm > 0) bmiDataObj.push({ x: ageM, y: parseFloat(r.weight) / (hm * hm) });
                                }
                                if (r.head_circumference) headDataObj.push({ x: ageM, y: parseFloat(r.head_circumference) });
                                if (r.arm_circumference) armDataObj.push({ x: ageM, y: parseFloat(r.arm_circumference) });
                                if (r.motor_milestones_score) motorDataObj.push({ x: ageM, y: parseFloat(r.motor_milestones_score) });

                                // Calculate velocity
                                if (prevRec) {
                                    const pdate = new Date(prevRec.recorded_at);
                                    const monthsDiff = (rdate - pdate) / (1000 * 60 * 60 * 24 * 30.44);
                                    if (monthsDiff > 0) {
                                        if (r.weight && prevRec.weight) velWeight.push({ x: ageM, y: (r.weight - prevRec.weight) / monthsDiff });
                                        if (r.height && prevRec.height) velHeight.push({ x: ageM, y: (r.height - prevRec.height) / monthsDiff });
                                        if (r.head_circumference && prevRec.head_circumference) velHead.push({ x: ageM, y: (r.head_circumference - prevRec.head_circumference) / monthsDiff });
                                    }
                                }
                                prevRec = r;
                            });

                            const gender = data.gender || 'male';
                            const whoPoints = data.who_curve_points;
                            const mapWho = (whoSet) => whoSet ? Object.keys(whoSet).map(age => ({ x: parseFloat(age), y: whoSet[age].median })) : [];

                            const plotChart = (ctxId, type, title, yLabel, xLabel, childLine, whoLine, color, isFill) => {
                                const el = document.getElementById(ctxId);
                                if (!el) return;
                                const ds = [{ label: dChild.first_name, data: childLine, borderColor: color, backgroundColor: isFill ? color + '40' : color, fill: isFill, tension: 0.3 }];
                                if (whoLine && whoLine.length > 0) {
                                    ds.push({ label: 'WHO Standard', data: whoLine, borderColor: '#94a3b8', borderDash: [5, 5], tension: 0.3, pointRadius: 0, fill: false });
                                }
                                new Chart(el, {
                                    type: type,
                                    data: { datasets: ds },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { title: { display: true, text: title, font: { size: 14, family: 'Inter' }, color: '#1e293b' }, legend: { position: 'bottom' } },
                                        scales: {
                                            x: { type: 'linear', title: { display: true, text: xLabel } },
                                            y: { title: { display: true, text: yLabel } }
                                        }
                                    }
                                });
                            };

                            setTimeout(() => {
                                plotChart('who-weight-chart', 'line', 'Weight-for-Age', 'Weight (kg)', 'Age (months)', weightDataObj, mapWho(whoPoints.weight_for_age[gender]), '#6C63FF', false);
                                plotChart('who-height-chart', 'line', 'Length/Height-for-Age', 'Height (cm)', 'Age (months)', heightDataObj, mapWho(whoPoints.height_for_age[gender]), '#ec4899', false);
                                plotChart('who-wl-chart', 'line', 'Weight-for-Length', 'Weight (kg)', 'Length (cm)', wlDataObj, mapWho(whoPoints.weight_for_length[gender]), '#14b8a6', false);
                                // BMI Gauge Scale
                                renderBMIGauge(bmiDataObj, dChild.first_name, ageYears, ageRemMonths);
                                plotChart('who-head-chart', 'line', 'Head Circumference-for-Age', 'Head Circ. (cm)', 'Age (months)', headDataObj, mapWho(whoPoints.head_for_age[gender]), '#8b5cf6', false);
                                plotChart('who-arm-chart', 'line', 'Arm Circumference-for-Age', 'Arm Circ. (cm)', 'Age (months)', armDataObj, mapWho(whoPoints.arm_for_age[gender]), '#3b82f6', false);

                                plotChart('motor-chart', 'bar', 'Motor Development Milestones', 'Score', 'Age (months)', motorDataObj, [], '#10b981', true);
                                // Velocity charts: only render if 2+ data points, else show message
                                var showVelMsg = function (canvasId, label) {
                                    var c = document.getElementById(canvasId);
                                    if (c && c.parentElement) {
                                        c.parentElement.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#94a3b8;text-align:center;padding:2rem;"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><p style="margin:1rem 0 0;font-size:0.85rem;line-height:1.5;">' + label + ' velocity requires <strong>at least 2 measurements</strong> recorded on different dates.</p></div>';
                                    }
                                };
                                if (velWeight.length >= 2) plotChart('vel-weight-chart', 'line', 'Weight Velocity', 'Gain (kg/mo)', 'Age (months)', velWeight, [], '#6366f1', true);
                                else showVelMsg('vel-weight-chart', 'Weight');
                                if (velHeight.length >= 2) plotChart('vel-height-chart', 'line', 'Length Velocity', 'Gain (cm/mo)', 'Age (months)', velHeight, [], '#c026d3', true);
                                else showVelMsg('vel-height-chart', 'Length');
                                if (velHead.length >= 2) plotChart('vel-head-chart', 'line', 'Head Circumference Velocity', 'Gain (cm/mo)', 'Age (months)', velHead, [], '#0ea5e9', true);
                                else showVelMsg('vel-head-chart', 'Head circumference');

                                // Populate dynamic descriptions
                                var cn = dChild.first_name;
                                var whoM = m || {};
                                function setDesc(id, text) { var el = document.getElementById(id); if (el) el.textContent = text; }

                                var latestW = weightDataObj.length > 0 ? weightDataObj[weightDataObj.length - 1].y : null;
                                var latestH = heightDataObj.length > 0 ? heightDataObj[heightDataObj.length - 1].y : null;
                                var latestHC = headDataObj.length > 0 ? headDataObj[headDataObj.length - 1].y : null;

                                if (latestW && whoM.weight) {
                                    setDesc('desc-weight', `${cn}'s weight is ${latestW} kg. Their z-score is ${whoM.weight.z_score}, placing them at the ${whoM.weight.percentile}th percentile. The WHO median for their age is ${whoM.weight.who_median}. ${whoM.weight.status === 'green' ? 'This is a healthy weight!' : whoM.weight.status === 'yellow' ? 'This is within a cautionary range, monitor closely.' : 'Please consult your pediatrician for guidance.'}`);
                                } else { setDesc('desc-weight', 'Log weight measurements to see how ' + cn + ' compares against WHO standards for their age group.'); }

                                if (latestH && whoM.height) {
                                    setDesc('desc-height', `${cn} is ${latestH} cm tall with a z-score of ${whoM.height.z_score} (${whoM.height.percentile}th percentile). The WHO median for their age is ${whoM.height.who_median}. ${whoM.height.status === 'green' ? 'Height growth is tracking well.' : 'Consider discussing growth patterns with your pediatrician.'}`);
                                } else { setDesc('desc-height', "Log height measurements to track how " + cn + "'s stature compares to other children their age."); }

                                if (latestW && latestH) {
                                    const wlStatus = whoM.weight_for_length ? whoM.weight_for_length.status : '';
                                    setDesc('desc-wl', `This chart shows how ${cn}'s weight relates to their height. A consistent curve means proportional growth. ${wlStatus === 'green' ? 'Proportions look healthy.' : wlStatus === 'yellow' ? 'Slight disproportion detected, should be monitored.' : wlStatus === 'red' ? 'Consult pediatrician regarding growth proportions.' : ''}`);
                                } else { setDesc('desc-wl', 'Log both weight and height to see the weight-for-length relationship.'); }

                                if (latestHC && whoM.head_circumference) {
                                    setDesc('desc-head', cn + "'s head measures " + latestHC + " cm (" + whoM.head_circumference.percentile + "th percentile). " + (whoM.head_circumference.status === 'green' ? 'Brain growth is progressing normally. Consistent growth along a percentile curve is what matters most.' : 'Discuss head circumference trends with your pediatrician.'));
                                } else { setDesc('desc-head', "Head circumference tracks brain growth. Log measurements to see " + cn + "'s progress."); }

                                setDesc('desc-arm', armDataObj.length > 0 ? cn + "'s arm circumference indicates muscle and fat mass development. " + (armDataObj.length > 1 ? 'The trend shows ' + (armDataObj[armDataObj.length - 1].y > armDataObj[0].y ? 'healthy growth.' : 'a decline — consult your pediatrician.') : 'More measurements are needed to establish a trend.') : 'Arm circumference is best measured by a specialist during clinic visits.');
                            }, 100);
                        }
                    }
                }
            } else {
                // No growth data
                ['who-weight-badge', 'who-height-badge', 'who-head-badge'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.textContent = 'No data'; el.className = 'badge'; }
                });
            }
        } catch (e) {
            ['who-weight-badge', 'who-height-badge', 'who-head-badge'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = 'N/A'; el.className = 'badge'; }
            });
        }
    }

    // ── Change Password Modal ───────────────────────────────────
    window.openChangePasswordModal = function () {
        let existing = document.getElementById('change-pwd-modal');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'change-pwd-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#fff;border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Change Password</h2>
                    <p style="color:var(--slate-500);font-size:0.9rem;margin-bottom:1.5rem;">Enter your current and new password</p>
                    <input type="password" id="cp-current" placeholder="Current password" style="width:100%;padding:0.875rem;border:2px solid var(--slate-200,#e2e8f0);border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                    <input type="password" id="cp-new" placeholder="New password (min 8 chars)" style="width:100%;padding:0.875rem;border:2px solid var(--slate-200,#e2e8f0);border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                    <button onclick="changePassword()" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#6C63FF,#a78bfa);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Update Password</button>
                    <div id="cp-error" style="color:#ef4444;font-size:0.85rem;margin-top:0.5rem;"></div>
                    <div id="cp-success" style="color:#22c55e;font-size:0.85rem;margin-top:0.5rem;"></div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    };

    window.changePassword = async function () {
        const current = document.getElementById('cp-current').value;
        const newPwd = document.getElementById('cp-new').value;
        const err = document.getElementById('cp-error');
        const suc = document.getElementById('cp-success');
        err.textContent = ''; suc.textContent = '';
        if (!current || !newPwd) { err.textContent = 'Both fields are required'; return; }
        if (newPwd.length < 8) { err.textContent = 'New password must be at least 8 characters'; return; }
        try {
            const res = await fetch('../../api_email_verify.php?action=change-password', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: current, new_password: newPwd })
            });
            const data = await res.json();
            if (data.success) {
                suc.textContent = data.message;
                setTimeout(() => { const m = document.getElementById('change-pwd-modal'); if (m) m.remove(); }, 2000);
            } else { err.textContent = data.error; }
        } catch (e) { err.textContent = 'Network error'; }
    };

    window.openSpeechDetailModal = function (entryJson) {
        let existing = document.getElementById('speech-detail-modal');
        if (existing) existing.remove();

        let entry;
        try {
            entry = JSON.parse(entryJson);
        } catch (e) {
            console.error("Failed to parse entry details:", e);
            return;
        }

        const dt = new Date(entry.sent_at);
        const timeStr = dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ' at ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        const vocabScore = entry.vocabulary_score ? Math.round(entry.vocabulary_score) : 0;
        const clarityScore = entry.clarify_score ? Math.round(entry.clarify_score * 100) : 0;

        let clarityMeaning = 'Developing clear speech patterns.';
        if (clarityScore >= 100) clarityMeaning = 'Very clear pronunciation, aligning perfectly with milestones.';
        else if (clarityScore >= 75) clarityMeaning = 'Good clarity, typical for this developmental stage.';

        let vocabMeaning = 'Still building core vocabulary.';
        if (entry.status && (entry.status.includes('Within') || entry.status.includes('Above'))) {
            vocabMeaning = 'Vocabulary size is right on track or advanced for their age!';
        }

        const modal = document.createElement('div');
        modal.id = 'speech-detail-modal';
        modal.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.remove()">
            <div style="background:var(--surface-light,#fff);border-radius:20px;padding:2rem;max-width:550px;width:100%;box-shadow:0 25px 50px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;text-align:left;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;">
                    <div>
                        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Speech Analysis Details</h2>
                        <p style="color:var(--slate-500);font-size:0.9rem;">Recorded on ${timeStr}</p>
                    </div>
                    <button onclick="document.getElementById('speech-detail-modal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);line-height:1;">&times;</button>
                </div>
                
                <div style="margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Listen to Recording</h3>
                    <audio controls style="width:100%;height:40px;border-radius:8px;" src="../../${entry.audio_url || ''}">
                        Your browser does not support the audio element.
                    </audio>
                </div>

                <div style="background:var(--slate-50,#f8fafc);border:1px solid var(--slate-200,#e2e8f0);border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Full Transcript</h3>
                    <div style="max-height:180px;overflow-y:auto;padding-right:0.5rem;">
                        <p style="font-style:italic;color:var(--slate-600);line-height:1.6;margin:0;">"${entry.transcript || 'No speech detected.'}"</p>
                    </div>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div style="background:#ede9fe;border-radius:12px;padding:1.25rem;">
                        <span style="display:block;font-size:0.8rem;color:#6b21a8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;">Vocabulary Score</span>
                        <div style="font-size:1.75rem;font-weight:800;color:#581c87;margin-bottom:0.5rem;">${vocabScore} <span style="font-size:1rem;font-weight:500;">words</span></div>
                        <p style="font-size:0.8rem;color:#4c1d95;line-height:1.4;">${vocabMeaning}</p>
                    </div>
                    <div style="background:#dcfce7;border-radius:12px;padding:1.25rem;">
                        <span style="display:block;font-size:0.8rem;color:#166534;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;">Clarity Score</span>
                        <div style="font-size:1.75rem;font-weight:800;color:#14532d;margin-bottom:0.5rem;">${clarityScore}%</div>
                        <p style="font-size:0.8rem;color:#15803d;line-height:1.4;">${clarityMeaning}</p>
                    </div>
                </div>
                
                <button onclick="document.getElementById('speech-detail-modal').remove()" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Close</button>
            </div>
        </div>`;
        document.body.appendChild(modal);
    };

    // Initialize
    initNav();
    const urlParams = new URLSearchParams(window.location.search);
    const initialView = urlParams.get('view') || 'home';

    if (document.getElementById('dashboard-content')) {
        switchView(initialView);
    } else {
        // We are on a different page
        const path = window.location.pathname;
        if (path.includes('settings.php')) {
            document.querySelectorAll('.nav-item').forEach(item => {
                if (item.dataset.view === 'settings') {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }
    }



    // Handle logout – Premium modal
    window.handleLogout = function () {
        // Remove existing modal if any
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
        // Trigger entrance animation
        requestAnimationFrame(() => modal.classList.add('show'));
    }

    window.closeLogoutModal = function () {
        const modal = document.getElementById('logout-modal');
        if (modal) {
            modal.classList.remove('show');
            modal.classList.add('hide');
            setTimeout(() => modal.remove(), 300);
        }
    }

    window.confirmLogout = function () {
        clearAuth();
        window.location.href = '../../logout.php';
    }

    // ══════════════════════════════════════════════════════════════
    // ── Add / Edit Child Modal ─────────────────────────────────
    // ══════════════════════════════════════════════════════════════
    window.openAddChildModal = function (childDataOrEmpty) {
        let childData = childDataOrEmpty;
        const children = window.dashboardData?.children || [];

        // Remove existing
        const old = document.getElementById('add-child-modal');
        if (old) old.remove();

        // Build switcher HTML if there are children
        let switcherHtml = '';
        if (children.length > 0) {
            switcherHtml = `
        <div style="display:flex; overflow-x:auto; gap:0.5rem; padding: 1rem 2rem 0; align-items:center;">
            <span style="font-size:0.8rem; font-weight:600; color:var(--slate-500); margin-right:0.5rem;">Select Profile:</span>
            ${children.map((c, idx) => `
                <button type="button" onclick="openAddChildModal(window.dashboardData.children[${idx}])" style="padding:0.4rem 0.8rem; border-radius:12px; border:1px solid ${(childData && childData.child_id === c.child_id) ? 'var(--blue-500)' : 'var(--slate-200)'}; background:${(childData && childData.child_id === c.child_id) ? 'var(--blue-50)' : '#fff'}; color:${(childData && childData.child_id === c.child_id) ? 'var(--blue-600)' : 'var(--slate-600)'}; font-weight:600; font-size:0.8rem; cursor:pointer; transition:all 0.2s;">
                    ${c.first_name}
                </button>
            `).join('')}
            <button type="button" onclick="openAddChildModal(null)" style="padding:0.4rem 0.8rem; border-radius:12px; border:1px dashed var(--slate-300); background:#fff; color:var(--slate-600); font-weight:600; font-size:0.8rem; cursor:pointer;">
                + New Child
            </button>
        </div>`;
        }

        const isEdit = !!childData;
        let birthVal = '';
        if (isEdit && childData.birth_year) {
            const y = childData.birth_year;
            const m = String(childData.birth_month).padStart(2, '0');
            const d = String(childData.birth_day).padStart(2, '0');
            birthVal = `${y}-${m}-${d}`;
        }

        const modal = document.createElement('div');
        modal.id = 'add-child-modal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease;';
        modal.innerHTML = `
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);" onclick="closeAddChildModal()"></div>
        <div id="acm-card" style="position:relative;background:var(--bg-card,#fff);border-radius:24px;padding:0;width:95%;max-width:520px;max-height:90vh;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.15);transform:scale(0.9) translateY(20px);transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1),box-shadow 0.3s ease;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#6C63FF 0%,#a78bfa 100%);padding:1.75rem 2rem 1.5rem;position:relative;">
                <button onclick="closeAddChildModal()" style="position:absolute;right:1rem;top:1rem;background:rgba(255,255,255,0.2);border:none;color:#fff;width:2rem;height:2rem;border-radius:50%;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:3rem;height:3rem;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h2 style="color:#fff;margin:0;font-size:1.35rem;font-weight:700;">${isEdit ? 'Edit Child Profile' : 'Add Child Profile'}</h2>
                        <p style="color:rgba(255,255,255,0.8);margin:0;font-size:0.85rem;">${isEdit ? 'Update your child\'s information' : 'Start tracking your child\'s development'}</p>
                    </div>
                </div>
            </div>
            ${switcherHtml}
            <!-- Body -->
            <div style="padding:1.75rem 2rem;overflow-y:auto;max-height:calc(90vh - 200px);">
                <div id="acm-status" style="margin-bottom:1rem;display:none;"></div>
                <form id="acm-form" onsubmit="event.preventDefault();submitChildModal();">
                    <input type="hidden" id="acm-child-id" value="${childData?.child_id || ''}">
                    
                    <!-- Basic Info -->
                    <div style="margin-bottom:1.5rem;">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--slate-400,#94a3b8);margin-bottom:0.75rem;">Basic Information</div>
                        <div style="display:grid;grid-template-columns:1fr;gap:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">First Name *</label>
                                <input type="text" id="acm-fname" required value="${childData?.first_name || ''}" placeholder="Enter first name" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);transition:border-color 0.2s,box-shadow 0.2s;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF';this.style.boxShadow='0 0 0 3px rgba(108,99,255,0.1)'" onblur="this.style.borderColor='';this.style.boxShadow=''">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Date of Birth *</label>
                                <input type="date" id="acm-dob" required value="${birthVal}" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);transition:border-color 0.2s,box-shadow 0.2s;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF';this.style.boxShadow='0 0 0 3px rgba(108,99,255,0.1)'" onblur="this.style.borderColor='';this.style.boxShadow=''">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Gender</label>
                                <select id="acm-gender" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);transition:border-color 0.2s;outline:none;box-sizing:border-box;cursor:pointer;" onfocus="this.style.borderColor='#6C63FF'" onblur="this.style.borderColor=''">
                                    <option value="male" ${childData?.gender === 'male' ? 'selected' : ''}>Male</option>
                                    <option value="female" ${childData?.gender === 'female' ? 'selected' : ''}>Female</option>
                                    <option value="other" ${childData?.gender === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>



                    <!-- Actions -->
                    <div style="display:flex;gap:0.75rem;padding-top:0.5rem;">
                        <button type="button" onclick="closeAddChildModal()" style="flex:1;padding:0.75rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:transparent;color:var(--text-primary,#475569);font-size:0.9rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='var(--bg-main,#f8fafc)'" onmouseout="this.style.background='transparent'">Cancel</button>
                        <button type="submit" id="acm-submit" style="flex:2;padding:0.75rem;border:none;border-radius:12px;background:linear-gradient(135deg,#6C63FF,#a78bfa);color:#fff;font-size:0.9rem;font-weight:600;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 15px rgba(108,99,255,0.3);" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px rgba(108,99,255,0.4)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(108,99,255,0.3)'">${isEdit ? '✏️ Save Changes' : '✨ Add Child'}</button>
                    </div>
                </form>
            </div>
        </div>`;

        document.body.appendChild(modal);

        // Animate in
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            const card = document.getElementById('acm-card');
            if (card) {
                card.style.transform = 'scale(1) translateY(0)';
            }
        });

        // Close on Escape
        modal._escHandler = function (e) { if (e.key === 'Escape') closeAddChildModal(); };
        document.addEventListener('keydown', modal._escHandler);
    };

    window.closeAddChildModal = function () {
        const modal = document.getElementById('add-child-modal');
        if (!modal) return;
        const card = document.getElementById('acm-card');
        if (card) card.style.transform = 'scale(0.9) translateY(20px)';
        modal.style.opacity = '0';
        document.removeEventListener('keydown', modal._escHandler);
        setTimeout(() => modal.remove(), 300);
    };

    window.submitChildModal = async function () {
        const btn = document.getElementById('acm-submit');
        const status = document.getElementById('acm-status');
        const childId = document.getElementById('acm-child-id').value;

        const payload = {
            child_id: childId || null,
            first_name: document.getElementById('acm-fname').value.trim(),
            birth_date: document.getElementById('acm-dob').value,
            gender: document.getElementById('acm-gender').value
        };

        if (!payload.first_name || !payload.birth_date) {
            status.style.display = 'block';
            status.innerHTML = '<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">Please fill in all required fields.</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:0.5rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving...</span>';
        status.style.display = 'none';

        try {
            const action = childId ? 'edit' : 'add';
            const res = await fetch('../../api_child.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                status.style.display = 'block';
                status.innerHTML = `<div style="padding:0.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#16a34a;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                ${data.message}</div>`;

                // Reload page after short delay to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                status.style.display = 'block';
                status.innerHTML = `<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">${data.error || 'Something went wrong'}</div>`;
                btn.disabled = false;
                btn.textContent = childId ? '✏️ Save Changes' : '✨ Add Child';
            }
        } catch (e) {
            status.style.display = 'block';
            status.innerHTML = '<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">Network error. Please try again.</div>';
            btn.disabled = false;
            btn.textContent = childId ? '✏️ Save Changes' : '✨ Add Child';
        }
    };

    // Spinner animation
    if (!document.getElementById('acm-spinner-style')) {
        const style = document.createElement('style');
        style.id = 'acm-spinner-style';
        style.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}} @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(style);
    }

    // ══════════════════════════════════════════════════════════════
    // ── Log Measurement Modal (historical – always INSERT) ──────
    // ══════════════════════════════════════════════════════════════
    window.openLogMeasurementModal = function (childId, recordId = null, w = '', h = '', hc = '') {
        let existing = document.getElementById('log-growth-modal');
        if (existing) existing.remove();

        const isEdit = !!recordId;
        const modal = document.createElement('div');
        modal.id = 'log-growth-modal';
        modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('log-growth-modal').remove()">
        <div style="background:#ffffff;border-radius:24px;width:100%;max-width:480px;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
            <div style="background:linear-gradient(135deg,#22c55e,#16a34a);padding:1.75rem 2rem 1.5rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:3rem;height:3rem;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">📏</div>
                    <div>
                        <h2 style="color:#fff;margin:0;font-size:1.35rem;font-weight:700;">${isEdit ? 'Edit Measurement' : 'Log New Measurement'}</h2>
                        <p style="color:rgba(255,255,255,0.8);margin:0;font-size:0.85rem;">${isEdit ? 'Update previous data' : 'This adds a new record — your old data is preserved!'}</p>
                    </div>
                </div>
            </div>
            <div style="padding:2rem;">
                <input type="hidden" id="lgm-record-id" value="${recordId || ''}">
                <div id="lgm-status" style="display:none;margin-bottom:1rem;"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--slate-700);margin-bottom:0.35rem;">Weight (kg)</label>
                        <input type="number" step="0.1" id="lgm-weight" value="${w}" placeholder="e.g. 10.5" style="width:100%;padding:0.7rem;border:1.5px solid var(--slate-200);border-radius:12px;font-size:0.95rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor=''">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--slate-700);margin-bottom:0.35rem;">Height (cm)</label>
                        <input type="number" step="0.1" id="lgm-height" value="${h}" placeholder="e.g. 76" style="width:100%;padding:0.7rem;border:1.5px solid var(--slate-200);border-radius:12px;font-size:0.95rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor=''">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--slate-700);margin-bottom:0.35rem;">Head (cm)</label>
                        <input type="number" step="0.1" id="lgm-head" value="${hc}" placeholder="e.g. 45" style="width:100%;padding:0.7rem;border:1.5px solid var(--slate-200);border-radius:12px;font-size:0.95rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor=''">
                    </div>
                </div>
                ${isEdit ? '' : '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:0.75rem 1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">' +
                '<span style="font-size:1.25rem;">🎯</span>' +
                '<span style="font-size:0.85rem;color:#166534;">Each measurement earns <strong>+25 points</strong>. All previous records are kept for history!</span>' +
                '</div>'}
                <div style="display:flex;gap:0.75rem;">
                    <button onclick="document.getElementById('log-growth-modal').remove()" style="flex:1;padding:0.75rem;border:1.5px solid var(--slate-200);border-radius:12px;background:transparent;color:var(--slate-600);font-size:0.9rem;font-weight:600;cursor:pointer;">Cancel</button>
                    <button id="lgm-submit" onclick="submitLogMeasurement(${childId})" style="flex:2;padding:0.75rem;border:none;border-radius:12px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-size:0.9rem;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(34,197,94,0.3);">📏 Save Measurement</button>
                </div>
            </div>
        </div>
    </div>`;
        document.body.appendChild(modal);
    };

    window.submitLogMeasurement = async function (childId) {
        const btn = document.getElementById('lgm-submit');
        const status = document.getElementById('lgm-status');
        const recordId = document.getElementById('lgm-record-id')?.value;
        const weight = document.getElementById('lgm-weight').value;
        const height = document.getElementById('lgm-height').value;
        const head = document.getElementById('lgm-head').value;

        if (!weight && !height && !head) {
            status.style.display = 'block';
            status.innerHTML = '<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">Please enter at least one measurement.</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:0.5rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving...</span>';

        try {
            const formData = new FormData();
            formData.append('child_id', childId);
            if (recordId) formData.append('record_id', recordId);
            if (weight !== '') formData.append('weight', weight);
            if (height !== '') formData.append('height', height);
            if (head !== '') formData.append('head_circumference', head);

            const res = await fetch('../../api_add_growth.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                status.style.display = 'block';
                status.innerHTML = '<div style="padding:0.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#16a34a;font-size:0.85rem;">✅ ' + data.message + '</div>';
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                status.style.display = 'block';
                status.innerHTML = '<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">' + (data.error || 'Error saving') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '📏 Save Measurement';
            }
        } catch (e) {
            status.style.display = 'block';
            status.innerHTML = '<div style="padding:0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#dc2626;font-size:0.85rem;">Network error. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = '📏 Save Measurement';
        }
    };

    // ══════════════════════════════════════════════════════════════
    // ── Book Specialist Modal ──────────────────────────────────────
    // ══════════════════════════════════════════════════════════════
    window.bookSpecialist = function (specId, specName) {
        let existing = document.getElementById('book-modal');
        if (existing) existing.remove();

        const dt = new Date();
        dt.setDate(dt.getDate() + 1);
        const minDate = dt.toISOString().split('T')[0];

        const modal = document.createElement('div');
        modal.id = 'book-modal';
        modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('book-modal').remove()">
        <div style="background:#ffffff;border-radius:24px;width:100%;max-width:500px;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;display:flex;flex-direction:column;">
            <div style="background:var(--blue-50);padding:1.5rem 2rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--blue-100);">
                <div>
                    <h2 style="font-size:1.25rem;font-weight:700;color:var(--slate-900);margin:0;">Book Appointment</h2>
                    <p style="margin:0;font-size:0.85rem;color:var(--blue-600);font-weight:600;">with ${specName}</p>
                </div>
                <button onclick="document.getElementById('book-modal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);">&times;</button>
            </div>
            
            <div id="bk-step-1" style="padding:2rem;">
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Consultation Type</label>
                    <select id="bk-type" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-size:0.95rem;">
                        <option value="onsite">On-site (Clinic Visit)</option>
                        <option value="online">Online (Video Session)</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Date</label>
                        <input type="date" id="bk-date" min="${minDate}" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-family:inherit;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Time</label>
                        <select id="bk-time" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-family:inherit;">
                            <option value="09:00">09:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:30">11:30 AM</option>
                            <option value="13:00">01:00 PM</option>
                            <option value="15:00">03:00 PM</option>
                            <option value="16:30">04:30 PM</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Notes for Specialist (Optional)</label>
                    <textarea id="bk-comment" rows="2" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;resize:none;outline:none;box-sizing:border-box;font-family:inherit;" placeholder="Briefly describe your concern..."></textarea>
                </div>
                <button onclick="window.goToBookingStep2()" class="btn btn-gradient" style="width:100%;padding:1rem;">Continue to Payment</button>
            </div>

            <div id="bk-step-2" style="padding:2rem;display:none;">
                <button onclick="window.goToBookingStep1()" style="background:none;border:none;color:var(--slate-500);cursor:pointer;font-size:0.85rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.25rem;padding:0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back</button>
                
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span style="color:var(--slate-500);">Consultation Fee</span><span style="font-weight:600;">$50.00</span></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span style="color:var(--slate-500);">Discount</span><span style="font-weight:600;">$0.00</span></div>
                    <div style="height:1px;background:#e2e8f0;margin:0.75rem 0;"></div>
                    <div style="display:flex;justify-content:space-between;"><span style="font-weight:700;color:var(--slate-800);">Total to Pay</span><span style="font-weight:800;color:var(--slate-900);font-size:1.1rem;">$50.00</span></div>
                </div>

                <div style="margin-bottom:2rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:var(--slate-700);">Payment Method</label>
                    <label style="display:flex;align-items:center;padding:1rem;border:1.5px solid var(--blue-500);border-radius:12px;background:var(--blue-50);margin-bottom:0.5rem;cursor:pointer;" onclick="this.style.borderColor='var(--blue-500)';this.style.background='var(--blue-50)';this.nextElementSibling.style.borderColor='var(--slate-200)';this.nextElementSibling.style.background='#fff';">
                        <input type="radio" name="bk-payment" value="Credit Card" checked style="margin-right:1rem;accent-color:var(--blue-600);">
                        <div>
                            <div style="font-weight:600;color:var(--blue-900);">Credit Card</div>
                            <div style="font-size:0.75rem;color:var(--blue-600);">Pay securely online</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;padding:1rem;border:1.5px solid var(--slate-200);border-radius:12px;cursor:pointer;background:#fff;" onclick="this.style.borderColor='var(--blue-500)';this.style.background='var(--blue-50)';this.previousElementSibling.style.borderColor='var(--slate-200)';this.previousElementSibling.style.background='#fff';">
                        <input type="radio" name="bk-payment" value="Cash" style="margin-right:1rem;accent-color:var(--blue-600);">
                        <div>
                            <div style="font-weight:600;color:var(--slate-800);">Cash at Clinic</div>
                            <div style="font-size:0.75rem;color:var(--slate-500);">Pay during your visit</div>
                        </div>
                    </label>
                </div>

                <button id="bk-submit-btn" onclick="window.submitBooking(${specId})" class="btn btn-gradient" style="width:100%;padding:1rem;">Confirm & Pay $50.00</button>
            </div>
            
            <div id="bk-step-3" style="padding:3rem 2rem;display:none;text-align:center;">
                <div style="width:4rem;height:4rem;background:#dcfce7;color:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;">✓</div>
                <h2 style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.5rem;">Booking Confirmed!</h2>
                <p style="color:var(--slate-500);margin-bottom:2rem;">Your appointment has been successfully scheduled. We will send you a reminder beforehand.</p>
                <button onclick="document.getElementById('book-modal').remove();fetchDashboardData();" class="btn btn-gradient" style="width:100%;">Done</button>
            </div>
        </div>
    </div>`;
        document.body.appendChild(modal);

        window.goToBookingStep1 = function () {
            document.getElementById('bk-step-1').style.display = 'block';
            document.getElementById('bk-step-2').style.display = 'none';
            document.getElementById('bk-step-3').style.display = 'none';
        };

        window.goToBookingStep2 = function () {
            const d = document.getElementById('bk-date').value;
            const t = document.getElementById('bk-time').value;
            if (!d || !t) { alert("Please select a valid date and time."); return; }

            document.getElementById('bk-step-1').style.display = 'none';
            document.getElementById('bk-step-2').style.display = 'block';
            document.getElementById('bk-step-3').style.display = 'none';
        };

        window.submitBooking = async function (sid) {
            const btn = document.getElementById('bk-submit-btn');
            btn.disabled = true;
            const origText = btn.innerHTML;
            btn.innerHTML = 'Processing...';

            const type = document.getElementById('bk-type').value;
            const date = document.getElementById('bk-date').value;
            const time = document.getElementById('bk-time').value;
            const comment = document.getElementById('bk-comment').value;
            const method = document.querySelector('input[name="bk-payment"]:checked').value;

            const schedAt = date + ' ' + time + ':00';

            try {
                const fd = new FormData();
                fd.append('specialist_id', sid);
                fd.append('type', type);
                fd.append('scheduled_at', schedAt);
                fd.append('payment_method', method);
                fd.append('comment', comment);

                const res = await fetch('../../api_book_appointment.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('bk-step-1').style.display = 'none';
                    document.getElementById('bk-step-2').style.display = 'none';
                    document.getElementById('bk-step-3').style.display = 'block';
                    if (typeof showBadgeToast === 'function') showBadgeToast("Appointment scheduled! 📅");
                } else {
                    alert(data.error || "Failed to book appointment.");
                    btn.disabled = false;
                    btn.innerHTML = origText;
                }
            } catch (e) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        };
    };

    // ══════════════════════════════════════════════════════════════
    // ── Points Wallet Popup with Vouchers & Offers ──────────────
    // ══════════════════════════════════════════════════════════════
    window.openPointsWalletPopup = function () {
        let existing = document.getElementById('wallet-modal');
        if (existing) existing.remove();

        const d = window.dashboardData || {};
        const child = (d.children || [])[window._selectedChildIndex || 0] || {};
        const totalPoints = child.total_points || 0;

        const vouchers = [
            { name: 'Free Consultation', points: 500, icon: '🩺', desc: 'Book a free specialist session', color: '#3b82f6' },
            { name: '1 Month Premium', points: 1000, icon: '💎', desc: 'Upgrade to Premium plan for a month', color: '#8b5cf6' },
            { name: 'Activity Pack', points: 200, icon: '🎨', desc: 'Unlock exclusive activity materials', color: '#f59e0b' },
            { name: 'Growth Report PDF', points: 150, icon: '📊', desc: 'Download detailed growth analysis', color: '#22c55e' },
            { name: 'Certificate Badge', points: 300, icon: '🏅', desc: 'Earn a printable achievement certificate', color: '#ec4899' },
        ];

        const voucherHtml = vouchers.map(v => {
            const canRedeem = totalPoints >= v.points;
            return `<div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:${canRedeem ? '#f8fafc' : '#fafafa'};border-radius:16px;border:1px solid ${canRedeem ? v.color + '30' : '#e2e8f0'};transition:all 0.2s;" ${canRedeem ? 'onmouseover="this.style.transform=\'translateX(4px)\'" onmouseout="this.style.transform=\'\'"' : ''}>
            <div style="width:3rem;height:3rem;background:${v.color}15;color:${v.color};border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">${v.icon}</div>
            <div style="flex:1;">
                <h4 style="font-weight:700;margin:0 0 0.25rem;font-size:0.95rem;">${v.name}</h4>
                <p style="margin:0;font-size:0.8rem;color:var(--slate-500);">${v.desc}</p>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-weight:800;color:${canRedeem ? v.color : '#94a3b8'};font-size:0.95rem;">${v.points} pts</div>
                <button style="margin-top:0.25rem;padding:0.3rem 0.75rem;border-radius:8px;font-size:0.75rem;font-weight:600;cursor:${canRedeem ? 'pointer' : 'not-allowed'};border:none;background:${canRedeem ? v.color : '#e2e8f0'};color:${canRedeem ? '#fff' : '#94a3b8'};" ${canRedeem ? '' : 'disabled'}>${canRedeem ? 'Redeem' : 'Need ' + (v.points - totalPoints) + ' more'}</button>
            </div>
        </div>`;
        }).join('');

        const modal = document.createElement('div');
        modal.id = 'wallet-modal';
        modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(12px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('wallet-modal').remove()">
        <div style="background:#ffffff;border-radius:28px;width:100%;max-width:520px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 30px 60px rgba(0,0,0,0.2);overflow:hidden;animation:slideUp 0.4s ease-out;">
            <div style="background:linear-gradient(135deg,#6C63FF,#a78bfa);padding:2rem;text-align:center;position:relative;">
                <button onclick="document.getElementById('wallet-modal').remove()" style="position:absolute;right:1rem;top:1rem;background:rgba(255,255,255,0.2);border:none;color:#fff;width:2rem;height:2rem;border-radius:50%;cursor:pointer;font-size:1.2rem;">&times;</button>
                <div style="font-size:3rem;margin-bottom:0.5rem;">💎</div>
                <h2 style="color:#fff;font-size:1.75rem;font-weight:800;margin:0 0 0.25rem;">Points Wallet</h2>
                <div style="background:rgba(255,255,255,0.2);display:inline-block;padding:0.5rem 1.5rem;border-radius:99px;margin-top:0.5rem;">
                    <span style="font-size:2rem;font-weight:800;color:#fff;">${totalPoints}</span>
                    <span style="color:rgba(255,255,255,0.8);margin-left:0.25rem;">points</span>
                </div>
            </div>
            <div style="padding:1.5rem;overflow-y:auto;flex:1;">
                <h3 style="font-weight:700;font-size:1.1rem;margin:0 0 1rem;color:var(--slate-800);">🎁 Redeem Rewards</h3>
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    ${voucherHtml}
                </div>
                <div style="margin-top:1.5rem;padding:1rem;background:#eff6ff;border-radius:12px;border:1px solid #bfdbfe;">
                    <p style="margin:0;font-size:0.85rem;color:#1e40af;line-height:1.5;"><strong>💡 How to earn points:</strong> Log growth (+25), complete activities (+10-50), read articles (+5), maintain streaks (bonus points!)</p>
                </div>
            </div>
        </div>
    </div>`;
        document.body.appendChild(modal);
    };

    // ══════════════════════════════════════════════════════════════
    // ── Multi-Child Switcher ────────────────────────────────────
    // ══════════════════════════════════════════════════════════════
    window._selectedChildIndex = 0;

    window.switchChild = function (index) {
        window._selectedChildIndex = index;
        const children = (window.dashboardData || {}).children || [];
        const child = children[index];

        // Re-render current view
        const activeNav = document.querySelector('.nav-item.active');
        const currentView = activeNav ? activeNav.dataset.view : 'home';
        switchView(currentView);

        // If switching to home view, reload activities
        if (currentView === 'home') {
            setTimeout(() => loadHomeActivities(), 50);
        }

        // Update topbar data
        if (child) {
            const ptsEl = document.getElementById('topbar-points-count');
            if (ptsEl) ptsEl.textContent = child.total_points || 0;
            const badgeEl = document.getElementById('topbar-badge-count');
            if (badgeEl) badgeEl.textContent = child.badge_count || 0;
        }

        // Refresh chatbot greeting with new child context
        refreshChatbotGreeting();

        // Close dropdown
        const dd = document.getElementById('child-switcher-dropdown');
        if (dd) dd.style.display = 'none';
    };

    // ══════════════════════════════════════════════════════════════
    // ── Mark Article as Read & Complete ─────────────────────────
    // ══════════════════════════════════════════════════════════════
    window.markArticleRead = async function (childId, index, title, btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin-right:8px"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Marking...';
        try {
            // Mark as read
            await fetch('../../api_activities.php?action=mark-read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: title })
            });
            // Complete activity
            await completeActivity(childId, 'article', index);
            // Show success
            btn.innerHTML = '✅ Completed!';
            btn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
            showBadgeToast('Article read! +15 points 📖');
            setTimeout(() => {
                const modal = document.getElementById('act-modal');
                if (modal) modal.remove();
            }, 1500);
        } catch (e) {
            btn.innerHTML = '❌ Error — try again';
            btn.disabled = false;
        }
    };

    // ══════════════════════════════════════════════════════════════
    // ── Messages View (Specialist + Community) ──────────────────
    // ══════════════════════════════════════════════════════════════
    window.getMessagesView = function () {
        setTimeout(() => initParentMessages(), 100);
        return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Messages</h1>
            <p class="dashboard-subtitle">Communicate with your specialists</p>
        </div></div>
        <div style="display:flex;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;height:550px;background:#fff;">
            <!-- Conversation List -->
            <div style="width:320px;border-right:1px solid #e2e8f0;display:flex;flex-direction:column;flex-shrink:0;">
                <div style="padding:1rem;border-bottom:1px solid #f1f5f9;">
                    <input type="text" id="msg-search" placeholder="Search conversations..." style="width:100%;padding:0.6rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:0.85rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'" oninput="filterParentConvos(this.value)">
                </div>
                <div id="parent-convo-list" style="flex:1;overflow-y:auto;"></div>
            </div>
            <!-- Chat Window -->
            <div style="flex:1;display:flex;flex-direction:column;">
                <div id="parent-chat-header" style="padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:1rem;">
                    <div id="pch-avatar" style="width:2.75rem;height:2.75rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.9rem;flex-shrink:0;">SA</div>
                    <div style="flex:1;"><div id="pch-name" style="font-weight:700;font-size:0.95rem;color:#1e293b;">Dr. Sarah Ahmed</div><div id="pch-detail" style="font-size:0.8rem;color:#94a3b8;">Pediatric Specialist</div></div>
                </div>
                <div id="parent-chat-messages" style="flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:0.75rem;background:#f8fafc;"></div>
                <div style="padding:0.75rem 1rem;border-top:1px solid #f1f5f9;display:flex;gap:0.5rem;align-items:flex-end;">
                    <textarea id="parent-chat-input" rows="1" placeholder="Type your message..." style="flex:1;padding:0.65rem 1rem;border:1.5px solid #e2e8f0;border-radius:12px;font-size:0.875rem;outline:none;resize:none;font-family:inherit;max-height:100px;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendParentMsg()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button onclick="sendParentMsg()" style="width:40px;height:40px;border:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    };

    window._parentChatData = {
        sarah: {
            name: 'Dr. Sarah Ahmed', initials: 'SA', detail: 'Pediatric Specialist', gradient: 'linear-gradient(135deg,#6366f1,#8b5cf6)',
            messages: [
                { from: 'them', text: "Hello! I've reviewed your child's latest growth data. Everything looks great!", time: '10:30 AM' },
                { from: 'me', text: "Thank you Dr. Sarah! Should we continue with the same feeding plan?", time: '10:45 AM' },
                { from: 'them', text: "Yes, the current plan is working well. I'd suggest adding more iron-rich foods as your child grows.", time: '11:00 AM' },
                { from: 'me', text: "Got it! I'll make those changes. When should we schedule the next check-up?", time: '11:15 AM' },
                { from: 'them', text: "Let's do a follow-up in 3 months. You can book through the clinic section.", time: '11:20 AM' }
            ]
        },
        mohamed: {
            name: 'Dr. Mohamed Ali', initials: 'MA', detail: 'Speech Therapist', gradient: 'linear-gradient(135deg,#8b5cf6,#ec4899)',
            messages: [
                { from: 'them', text: "I've prepared new speech exercises for your child based on our last session.", time: 'Yesterday' },
                { from: 'me', text: "Thanks! How often should we practice these exercises?", time: 'Yesterday' },
                { from: 'them', text: "Twice daily for 10-15 minutes each session. Consistency is key at this age.", time: 'Yesterday' }
            ]
        },
        hana: {
            name: 'Dr. Hana Ibrahim', initials: 'HI', detail: 'Child Psychologist', gradient: 'linear-gradient(135deg,#14b8a6,#06b6d4)',
            messages: [
                { from: 'them', text: "Great progress in social interactions! The play-date activities are really helping.", time: 'Mar 30' },
                { from: 'me', text: "That is so good to hear! She seems much more confident now.", time: 'Mar 30' }
            ]
        }
    };
    window._parentCurrentChat = 'sarah';

    window.initParentMessages = function () {
        renderParentConvoList();
        selectParentConvo('sarah');
        var input = document.getElementById('parent-chat-input');
        if (input) input.addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 100) + 'px'; });
    };

    window.renderParentConvoList = function () {
        var list = document.getElementById('parent-convo-list');
        if (!list) return;
        var html = '';
        for (var key in window._parentChatData) {
            var c = window._parentChatData[key];
            var lastMsg = c.messages[c.messages.length - 1];
            var preview = lastMsg.text.length > 35 ? lastMsg.text.substring(0, 35) + '...' : lastMsg.text;
            var isActive = key === window._parentCurrentChat;
            html += '<div data-convo="' + key + '" onclick="selectParentConvo(\'' + key + '\')" style="padding:1rem 1.25rem;cursor:pointer;display:flex;align-items:center;gap:0.75rem;border-bottom:1px solid #f1f5f9;transition:all 0.15s;' + (isActive ? 'background:#f0f0ff;border-left:3px solid #6366f1;' : 'border-left:3px solid transparent;') + '" onmouseover="if(!this.classList.contains(\'active-convo\'))this.style.background=\'#f8fafc\'" onmouseout="if(!this.classList.contains(\'active-convo\'))this.style.background=\'\'">'
                + '<div style="width:2.5rem;height:2.5rem;background:' + c.gradient + ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:700;flex-shrink:0;">' + c.initials + '</div>'
                + '<div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.15rem;"><span style="font-weight:' + (isActive ? '700' : '600') + ';font-size:0.85rem;color:#1e293b;">' + c.name + '</span><span style="font-size:0.7rem;color:#94a3b8;">' + lastMsg.time + '</span></div>'
                + '<p style="margin:0;font-size:0.78rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + preview + '</p></div></div>';
        }
        list.innerHTML = html;
    };

    window.selectParentConvo = function (key) {
        window._parentCurrentChat = key;
        var data = window._parentChatData[key];
        if (!data) return;
        // Update header
        var avatar = document.getElementById('pch-avatar'); if (avatar) { avatar.textContent = data.initials; avatar.style.background = data.gradient; }
        var name = document.getElementById('pch-name'); if (name) name.textContent = data.name;
        var detail = document.getElementById('pch-detail'); if (detail) detail.textContent = data.detail;
        // Render messages
        var container = document.getElementById('parent-chat-messages');
        if (container) {
            var html = '<div style="text-align:center;margin:0.5rem 0;"><span style="background:#e2e8f0;padding:0.2rem 0.75rem;border-radius:20px;font-size:0.7rem;color:#64748b;font-weight:600;">Today</span></div>';
            data.messages.forEach(function (msg) {
                var isMine = msg.from === 'me';
                html += '<div style="display:flex;' + (isMine ? 'justify-content:flex-end;' : '') + '">'
                    + '<div style="max-width:75%;padding:0.75rem 1rem;border-radius:14px;font-size:0.875rem;line-height:1.5;'
                    + (isMine ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-bottom-right-radius:4px;' : 'background:#fff;color:#1e293b;border:1px solid #e2e8f0;border-bottom-left-radius:4px;') + '">'
                    + '<div>' + msg.text + '</div>'
                    + '<div style="font-size:0.65rem;margin-top:0.35rem;' + (isMine ? 'color:rgba(255,255,255,0.7);text-align:right;' : 'color:#94a3b8;') + '">' + msg.time + '</div>'
                    + '</div></div>';
            });
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }
        renderParentConvoList();
    };

    window.sendParentMsg = function () {
        var input = document.getElementById('parent-chat-input');
        var text = input ? input.value.trim() : '';
        if (!text) return;
        var data = window._parentChatData[window._parentCurrentChat];
        if (data) {
            var now = new Date();
            data.messages.push({ from: 'me', text: text, time: now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) });
        }
        input.value = ''; input.style.height = 'auto';
        selectParentConvo(window._parentCurrentChat);
    };

    window.filterParentConvos = function (q) {
        var items = document.querySelectorAll('#parent-convo-list > div');
        q = q.toLowerCase();
        items.forEach(function (item) { var name = item.textContent.toLowerCase(); item.style.display = name.includes(q) ? '' : 'none'; });
    };

    window.loadMessages = function () {
        initParentMessages();
    };


    // ══════════════════════════════════════════════════════════════
    // ── BMI Gauge / Scale Widget ────────────────────────────────
    // ══════════════════════════════════════════════════════════════
    window.renderBMIGauge = function (bmiDataObj, childName, ageY, ageM) {
        const container = document.getElementById('bmi-gauge-container');
        if (!container) return;

        const latestBMI = bmiDataObj.length > 0 ? bmiDataObj[bmiDataObj.length - 1].y : null;

        if (!latestBMI) {
            container.innerHTML = '<div style="text-align:center;color:#64748b;padding:2rem;"><div style="font-size:2.5rem;margin-bottom:1rem;">⚖️</div><h4 style="font-weight:700;margin-bottom:0.5rem;color:#1e293b;">BMI Not Available</h4><p style="font-size:0.85rem;line-height:1.5;">Log both <strong>weight</strong> and <strong>height</strong> measurements to calculate and display the BMI gauge.</p></div>';
            return;
        }

        const bmi = parseFloat(latestBMI.toFixed(1));
        let category = '', color = '', desc = '';
        if (bmi < 14) { category = 'Underweight'; color = '#3b82f6'; desc = childName + "'s BMI is below the healthy range. Consider nutrient-rich foods and consult your pediatrician."; }
        else if (bmi < 18.5) { category = 'Healthy Weight'; color = '#22c55e'; desc = childName + "'s BMI is within the healthy range — great job! Keep up balanced nutrition."; }
        else if (bmi < 25) { category = 'At Risk'; color = '#f59e0b'; desc = childName + "'s BMI is slightly above optimal. Encourage active play and monitor diet."; }
        else { category = 'Overweight'; color = '#ef4444'; desc = childName + "'s BMI is above recommended. Please consult your pediatrician."; }

        // Render with Chart.js doughnut
        container.innerHTML = '<div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;width:100%;box-sizing:border-box;"><h4 style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;">⚖️ BMI-for-Age</h4><p style="margin:0.25rem 0 0;font-size:0.8rem;color:#94a3b8;">' + childName + ' · Age: ' + ageY + 'y ' + ageM + 'm</p></div>'
            + '<div style="position:relative;width:240px;height:140px;margin:1.5rem auto 0;"><canvas id="bmi-doughnut-chart"></canvas>'
            + '<div style="position:absolute;bottom:10px;left:50%;transform:translateX(-50%);text-align:center;"><span style="font-size:2.25rem;font-weight:800;color:' + color + ';">' + bmi + '</span><span style="font-size:0.8rem;color:#94a3b8;margin-left:4px;">kg/m²</span></div></div>'
            + '<div style="text-align:center;margin:0.75rem 0;"><span style="display:inline-flex;align-items:center;gap:0.35rem;background:' + color + '18;padding:0.4rem 1rem;border-radius:20px;font-size:0.9rem;font-weight:700;color:' + color + ';">' + category + '</span></div>'
            + '<p style="font-size:0.8rem;color:#64748b;text-align:center;margin:0 1.5rem 1.25rem;line-height:1.5;">' + desc + '</p>';

        setTimeout(function () {
            var el = document.getElementById('bmi-doughnut-chart');
            if (!el || typeof Chart === 'undefined') return;
            // Map BMI to needle angle: BMI 10=leftmost, BMI 35=rightmost
            var minB = 10, maxB = 35;
            var clamped = Math.max(minB, Math.min(maxB, bmi));
            var pct = (clamped - minB) / (maxB - minB);
            // Zones: Underweight(16%), Normal(34%), At Risk(26%), Overweight(24%) of the half circle
            var zones = [16, 34, 26, 24];
            var colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444'];
            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: ['Underweight', 'Healthy', 'At Risk', 'Overweight'],
                    datasets: [{
                        data: zones,
                        backgroundColor: colors,
                        borderWidth: 0,
                        cutout: '75%'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    rotation: -90, circumference: 180,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                },
                plugins: [{
                    id: 'bmiNeedle',
                    afterDraw: function (chart) {
                        var ctx = chart.ctx;
                        var meta = chart.getDatasetMeta(0);
                        var arc = meta.data[0];
                        if (!arc) return;
                        var cx = chart.chartArea.left + (chart.chartArea.right - chart.chartArea.left) / 2;
                        var cy = chart.chartArea.bottom;
                        var radius = arc.outerRadius * 0.65;
                        var angle = Math.PI + (pct * Math.PI);
                        var nx = cx + radius * Math.cos(angle);
                        var ny = cy + radius * Math.sin(angle);
                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(cx, cy);
                        ctx.lineTo(nx, ny);
                        ctx.strokeStyle = '#1e293b';
                        ctx.lineWidth = 3;
                        ctx.lineCap = 'round';
                        ctx.stroke();
                        ctx.beginPath();
                        ctx.arc(cx, cy, 6, 0, Math.PI * 2);
                        ctx.fillStyle = '#1e293b';
                        ctx.fill();
                        ctx.restore();
                    }
                }]
            });
        }, 150);
    };

    // ── Notifications Popup ──────────────────────────────────────
    window.openNotificationsPopup = async function () {
        let existing = document.getElementById('notif-popup-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'notif-popup-modal';
        modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(12px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('notif-popup-modal').remove()">
        <div style="background:#ffffff;border-radius:24px;width:100%;max-width:560px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 30px 60px rgba(0,0,0,0.2);overflow:hidden;">
            <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:1.75rem 2rem;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 style="color:#fff;font-size:1.5rem;font-weight:800;margin:0;">All Notifications</h2>
                    <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;margin:0.25rem 0 0;">Stay updated on your child's progress</p>
                </div>
                <button onclick="document.getElementById('notif-popup-modal').remove()" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:2.25rem;height:2.25rem;border-radius:50%;cursor:pointer;font-size:1.2rem;">&times;</button>
            </div>
            <div style="padding:1rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:flex-end;">
                <button onclick="markAllRead().then(function(){openNotificationsPopup()})" style="background:#eff6ff;color:#3b82f6;border:none;padding:0.5rem 1rem;border-radius:8px;font-size:0.8rem;font-weight:600;cursor:pointer;">Mark All as Read</button>
            </div>
            <div id="notif-popup-list" style="flex:1;overflow-y:auto;padding:1rem 1.5rem;">
                <div style="text-align:center;padding:2rem;color:#94a3b8;">Loading notifications...</div>
            </div>
        </div>
    </div>`;
        document.body.appendChild(modal);

        try {
            const res = await fetch('../../api_notifications.php?action=list&limit=50');
            const data = await res.json();
            const notifs = data.notifications || [];
            const container = document.getElementById('notif-popup-list');
            if (!container) return;
            if (notifs.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:3rem;"><div style="font-size:3rem;margin-bottom:1rem;">🔕</div><h3 style="color:#64748b;font-weight:600;">No notifications yet</h3><p style="color:#94a3b8;font-size:0.875rem;">Updates about your child will appear here</p></div>';
                return;
            }
            const typeIcons = { appointment_reminder: '📅', payment_success: '💳', growth_alert: '📏', milestone: '🏆', system: '🔔', badge_earned: '🏅', streak: '🔥' };
            container.innerHTML = notifs.map(function (n) {
                var dt = new Date(n.created_at);
                var timeStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                var icon = typeIcons[n.type] || '🔔';
                var isUnread = n.is_read == 0;
                return '<div style="padding:1rem;margin-bottom:0.75rem;border-radius:16px;background:' + (isUnread ? '#eff6ff' : '#f8fafc') + ';border:1px solid ' + (isUnread ? '#bfdbfe' : '#e2e8f0') + ';display:flex;gap:1rem;align-items:flex-start;cursor:pointer;transition:all 0.2s;" onclick="markNotifRead(' + n.notification_id + ').then(function(){openNotificationsPopup()})">' +
                    '<div style="width:2.75rem;height:2.75rem;border-radius:12px;background:' + (isUnread ? '#dbeafe' : '#f1f5f9') + ';display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">' + icon + '</div>' +
                    '<div style="flex:1;min-width:0;">' +
                    '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">' +
                    '<h4 style="font-weight:' + (isUnread ? '700' : '600') + ';font-size:0.95rem;margin:0;color:#1e293b;">' + n.title + '</h4>' +
                    (isUnread ? '<span style="width:8px;height:8px;border-radius:50%;background:#3b82f6;flex-shrink:0;"></span>' : '') +
                    '</div>' +
                    '<p style="color:#64748b;font-size:0.85rem;margin:0 0 0.35rem;line-height:1.4;">' + n.message + '</p>' +
                    '<span style="font-size:0.75rem;color:#94a3b8;">' + timeStr + '</span>' +
                    '</div>' +
                    '</div>';
            }).join('');
        } catch (e) {
            var container = document.getElementById('notif-popup-list');
            if (container) container.innerHTML = '<div style="text-align:center;padding:2rem;color:#ef4444;">Could not load notifications</div>';
        }
    };

    // ══════════════════════════════════════════════════════════════
    // ── BOOTSTRAP (must be at end of file, after all functions) ──
    // ══════════════════════════════════════════════════════════════
    window._dashboardInitNav = initNav;
    window._dashboardSwitchView = switchView;

    try {
        initNav();
        var _bsUrlParams = new URLSearchParams(window.location.search);
        var _bsInitialView = _bsUrlParams.get('view') || 'home';
        switchView(_bsInitialView);
    } catch (e) {
        var _bsEl = document.getElementById('dashboard-content');
        if (_bsEl) _bsEl.innerHTML = '<div style="padding:2rem;color:red;font-size:1.2rem;"><strong>Dashboard Error:</strong> ' + e.message + '<br><pre>' + e.stack + '</pre></div>';
        console.error('Dashboard bootstrap error:', e);
    }
})();
