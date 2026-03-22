// Dashboard JavaScript
(function () {
    'use strict';

    // Navigation items configuration
    const navItems = [
        { id: 'home', label: 'Home', icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
        { id: 'profile', label: 'Child Profile', icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' },
        { id: 'growth', label: 'Growth', icon: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>' },
        { id: 'speech', label: 'Speech', icon: '<path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/>' },
        { id: 'motor', label: 'Motor Skills', icon: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>' },
        { id: 'activities', label: 'Activities', icon: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>' },
        { id: 'clinic', label: 'Book Clinic', icon: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
        { id: 'reports', label: 'Reports', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>' }
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
        const streaks = d.streaks || {};
        const badges = d.badges || [];
        const dailyStreak = streaks.daily_login ? streaks.daily_login.current_count : 0;
        const badgeCount = badges.length || ((d.children || [])[0] || {}).badge_count || 0;
        const totalPoints = ((d.children || [])[0] || {}).total_points || 0;
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
                <div class="topbar-badges" title="Badges Earned" onclick="switchView('profile')" style="cursor:pointer">
                    <div class="topbar-badge-icon">🏆</div>
                    <div class="topbar-badge-info">
                        <span class="topbar-badge-count" id="topbar-badge-count">${badgeCount}</span>
                        <span class="topbar-badge-label">Badges</span>
                    </div>
                </div>
                <div class="topbar-divider"></div>
                <div class="topbar-points" title="Points Wallet" style="cursor:pointer">
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
                    <div class="notif-dropdown-footer" onclick="switchView('notifications');toggleNotifDropdown()">
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
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notif-dropdown');
            const trigger = document.getElementById('topbar-notification');
            if (dropdown && trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    window.toggleNotifDropdown = function() {
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
                const timeStr = dt.toLocaleDateString('en-US', {month:'short',day:'numeric'});
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
        } catch(e) {
            list.innerHTML = '<div class="notif-dropdown-empty">Error loading</div>';
        }
    }

    async function streakCheckIn() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[0] || null;
        if (!child) return;
        try {
            const res = await fetch('../../api_streaks.php?action=check-in', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({child_id: child.child_id})
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
        } catch(e) { /* silent */ }
    }

    function showBadgeToast(badgeName) {
        const toast = document.createElement('div');
        toast.className = 'badge-toast';
        toast.innerHTML = `<span class="badge-toast-icon">🏆</span><div><strong>New Badge!</strong><br>${badgeName}</div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
    }

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
        const container = document.getElementById('home-activities-list');
        if (!container) return;
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[0] || null;
        if (!child) return;

        try {
            const res = await fetch('../../api_activities.php?action=recommend&child_id=' + child.child_id);
            const data = await res.json();

            if (data.success && data.recommendations) {
                const recs = data.recommendations;
                const activities = recs.real_life_activities || [];
                const colors = ['activity-blue', 'activity-purple', 'activity-green', 'activity-orange'];
                const icons = [
                    '<path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/>',
                    '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
                    '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
                    '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'
                ];

                if (activities.length === 0) {
                    container.innerHTML = '<p style="color:var(--slate-500);padding:1rem;text-align:center;">No activities available. Check the Activities tab for more.</p>';
                    return;
                }

                container.innerHTML = activities.slice(0, 3).map((act, i) => `
                    <div class="activity-item ${colors[i % colors.length]}">
                        <div class="activity-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icons[i % icons.length]}</svg></div>
                        <div class="activity-info">
                            <h4 class="activity-title">${act.title}</h4>
                            <p class="activity-description">${act.description}</p>
                            <span class="activity-duration">⏱ ${act.duration || '15 min'}</span>
                        </div>
                    </div>`).join('');
            } else {
                container.innerHTML = '<p style="color:var(--slate-500);padding:1rem;text-align:center;">Activities will appear here once configured.</p>';
            }
        } catch (e) {
            container.innerHTML = '<p style="color:var(--slate-500);padding:1rem;text-align:center;">Could not load activities.</p>';
        }
    }

    // View templates
    function getHomeView() {
        const d = window.dashboardData || {};
        const p = d.parent || {};
        const children = d.children || [];
        const appts = d.appointments || [];
        const child = children[0] || null;

        if (!child) {
            return `<div class="dashboard-content">
                <div class="dashboard-header-section"><div>
                    <h1 class="dashboard-title">Welcome, ${p.fname || 'Parent'}! 👋</h1>
                    <p class="dashboard-subtitle">Get started by adding your child's profile</p>
                </div></div>
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
            <div class="child-profile-card">
                <div class="child-avatar">${initial}</div>
                <div class="child-info">
                    <h2 class="child-name">${fullName}</h2>
                    <div class="child-details">
                        <span>${child.age_display || ''}</span><span>•</span><span>Born: ${child.birth_date_formatted || ''}</span>
                    </div>
                </div>
                <div class="child-stats">
                    <div class="stat-box"><div class="stat-label">Weight</div><div class="stat-value">${weight}</div></div>
                    <div class="stat-box"><div class="stat-label">Height</div><div class="stat-value">${height}</div></div>
                </div>
            </div>
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header"><h3 class="card-title"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Today's Recommended Activities</h3></div>
                    <div class="card-content" id="home-activities-list">
                        <div style="text-align:center;padding:2rem;color:var(--slate-400);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin:0 auto 0.5rem;display:block;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            Loading activities...
                        </div>
                    </div>
                </div>
                <div class="dashboard-column">
                    <div class="dashboard-card">
                        <div class="card-header"><h3 class="card-title"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Upcoming Appointments</h3></div>
                        <div class="card-content">${apptHtml}</div>
                    </div>
                    <div class="dashboard-card" style="cursor:pointer" onclick="window.location.href='../../articles.php'">
                        <div class="card-header"><h3 class="card-title"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>Articles & Tips</h3></div>
                        <div class="card-content" style="text-align:center;padding:1.5rem;">
                            <div style="font-size:2.5rem;margin-bottom:0.5rem;">📚</div>
                            <p style="color:var(--slate-500);font-size:0.9rem;">Parenting tips, health guides, nutrition advice & more</p>
                            <span class="btn btn-outline btn-sm" style="margin-top:0.75rem;display:inline-block;">Browse Articles →</span>
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
                    <button class="quick-action-btn" onclick="switchView('clinic')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span>Book Clinic</span></button>
                </div>
            </div>
        </div>`;
    }

    function getProfileView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[0] || null;

        let selectorHtml = '';
        children.forEach((c, i) => {
            const init = (c.first_name || '?')[0].toUpperCase();
            const ageLabel = c.age_months >= 24 ? Math.floor(c.age_months / 12) + ' yo' : c.age_months + ' mo';
            const isActive = i === 0;
            selectorHtml += `<div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;${isActive ? '' : 'opacity:0.6;'}">
                <div style="width:4rem;height:4rem;background:${isActive ? 'var(--blue-600)' : 'var(--purple-100)'};color:${isActive ? 'white' : 'var(--purple-600)'};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;border:3px solid ${isActive ? 'var(--blue-200)' : 'transparent'};">${init}</div>
                <span style="margin-top:0.5rem;font-weight:600;">${c.first_name}</span>
                <span style="font-size:0.75rem;color:var(--slate-500);">${ageLabel}</span></div>`;
        });
        selectorHtml += `<div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;opacity:0.6;" onclick="openAddChildModal()">
            <div style="width:4rem;height:4rem;border:2px dashed var(--slate-300);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--slate-400);"><svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></div>
            <span style="margin-top:0.5rem;font-weight:500;color:var(--slate-500);">New</span></div>`;

        if (!child) {
            return `<div class="dashboard-content"><div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1><p class="dashboard-subtitle">No children yet</p></div>
            <a href="javascript:void(0)" onclick="openAddChildModal()" class="btn btn-outline">Add Child</a></div></div>`;
        }

        const g = child.growth || {};
        const fullName = (child.first_name || '') + ' ' + (child.last_name || '');
        const init = (child.first_name || '?')[0].toUpperCase();

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1><p class="dashboard-subtitle">Manage profiles and view progress</p></div>
            <a href="javascript:void(0)" onclick="openAddChildModal()" class="btn btn-outline"><svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add Child</a></div>
            <div class="dashboard-card" style="margin-bottom:2rem;"><div class="card-content"><h3 class="card-title" style="margin-bottom:1rem;font-size:1rem;">Select Child Profile</h3>
                <div style="display:flex;gap:1.5rem;overflow-x:auto;padding-bottom:0.5rem;">${selectorHtml}</div></div></div>
            <div class="child-profile-card" style="margin-bottom:2rem;"><div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="display:flex;gap:1.5rem;align-items:center;"><div class="child-avatar" style="width:5rem;height:5rem;font-size:2rem;">${init}</div>
                <div class="child-info"><h2 class="child-name" style="font-size:1.75rem;">${fullName}</h2>
                <div class="child-details"><span>${child.age_display || ''}</span><span>•</span><span>Born: ${child.birth_date_formatted || ''}</span></div></div></div>
                <a href="javascript:void(0)" onclick="openAddChildModal(window.dashboardData?.children?.[0])" class="btn btn-ghost">Edit Details</a></div></div>
            <div class="dashboard-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:2rem;">
                <div class="dashboard-card" style="text-align:center;padding:1.5rem;"><div style="font-size:0.875rem;color:var(--slate-500);margin-bottom:0.5rem;">Weight</div><div style="font-size:1.5rem;font-weight:700;">${g.weight ? g.weight + ' kg' : '—'}</div></div>
                <div class="dashboard-card" style="text-align:center;padding:1.5rem;"><div style="font-size:0.875rem;color:var(--slate-500);margin-bottom:0.5rem;">Height</div><div style="font-size:1.5rem;font-weight:700;">${g.height ? g.height + ' cm' : '—'}</div></div>
                <div class="dashboard-card" style="text-align:center;padding:1.5rem;"><div style="font-size:0.875rem;color:var(--slate-500);margin-bottom:0.5rem;">Badges</div><div style="font-size:1.5rem;font-weight:700;">${child.badge_count || 0}</div></div>
            </div></div>`;
    }

    function getGrowthView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[0] || null;

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

        // Build growth history table rows
        let historyRows = '';
        gh.slice().reverse().slice(0, 5).forEach(r => {
            const dt = new Date(r.recorded_at);
            const dateStr = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            if (r.weight) historyRows += `<tr style="border-bottom:1px solid var(--slate-50);"><td style="padding:1rem;">${dateStr}</td><td style="padding:1rem;">Weight</td><td style="padding:1rem;font-weight:600;">${r.weight} kg</td><td style="padding:1rem;"><span class="badge badge-blue" id="who-w-${r.recorded_at}">—</span></td></tr>`;
            if (r.height) historyRows += `<tr style="border-bottom:1px solid var(--slate-50);"><td style="padding:1rem;">${dateStr}</td><td style="padding:1rem;">Height</td><td style="padding:1rem;font-weight:600;">${r.height} cm</td><td style="padding:1rem;"><span class="badge badge-blue" id="who-h-${r.recorded_at}">—</span></td></tr>`;
        });
        if (!historyRows) historyRows = '<tr><td colspan="4" style="padding:1rem;color:var(--slate-500);">No measurements recorded yet</td></tr>';

        // After rendering, fetch WHO comparison
        setTimeout(() => fetchWHOComparison(child.child_id), 100);

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div>
                <h1 class="dashboard-title">Growth Tracking 📏</h1>
                <p class="dashboard-subtitle">Monitor ${child.first_name}'s development against WHO standards</p>
            </div>
            <a href="javascript:void(0)" onclick="openAddChildModal(window.dashboardData?.children?.[0])" class="btn btn-gradient">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Log Measurement
            </a></div>

            <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Current Weight</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.weight ? g.weight + ' kg' : '—'}</h3>
                    <span class="badge" id="who-weight-badge" style="margin-top:0.5rem;">Loading...</span>
                </div></div>
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Current Height</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.height ? g.height + ' cm' : '—'}</h3>
                    <span class="badge" id="who-height-badge" style="margin-top:0.5rem;">Loading...</span>
                </div></div>
                <div class="dashboard-card"><div class="card-content" style="text-align:center;padding:1.5rem;">
                    <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">Head Circumference</p>
                    <h3 style="font-size:2rem;font-weight:800;color:var(--slate-900);">${g.head_circumference ? g.head_circumference + ' cm' : '—'}</h3>
                    <span class="badge" id="who-head-badge" style="margin-top:0.5rem;">Loading...</span>
                </div></div>
            </div>

            <div class="dashboard-card" id="who-summary" style="margin-bottom:2rem;display:none;">
                <div class="card-content" style="padding:1.5rem;">
                    <h3 class="card-title" style="margin-bottom:1rem;">WHO Growth Assessment</h3>
                    <div id="who-summary-content"></div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header"><h3 class="card-title">Recent Measurements</h3></div>
                <table style="width:100%;border-collapse:collapse;margin-top:1rem;">
                    <thead><tr style="border-bottom:2px solid var(--slate-100);">
                        <th style="text-align:left;padding:1rem;color:var(--slate-500);">Date</th>
                        <th style="text-align:left;padding:1rem;color:var(--slate-500);">Type</th>
                        <th style="text-align:left;padding:1rem;color:var(--slate-500);">Value</th>
                        <th style="text-align:left;padding:1rem;color:var(--slate-500);">Percentile</th>
                    </tr></thead>
                    <tbody>${historyRows}</tbody>
                </table>
            </div>
        </div>`;
    }

    function getSpeechView() {
        const d = window.dashboardData || {};
        const child = (d.children || [])[0] || null;
        const childId = child ? child.child_id : null;
        setTimeout(() => loadSpeechHistory(childId), 100);
        return `<div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Speech Analysis 🗣️</h1>
                        <p class="dashboard-subtitle">Track vocabulary and pronunciation progress</p>
                    </div>
                    <button class="btn btn-gradient" onclick="openSpeechModal(${childId})">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                        New Recording
                    </button>
                </div>

                <div style="margin-bottom:2rem;">
                    <div style="background:linear-gradient(to right,#7c3aed,#2563eb);border-radius:var(--radius-xl,16px);padding:2rem;color:#fff;">
                        <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">AI Insight</h3>
                        <p id="insight-text" style="margin-bottom:1.5rem;opacity:0.9;">Loading latest analysis...</p>
                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <div style="background:rgba(255,255,255,0.2);padding:0.5rem 1rem;border-radius:12px;">
                                <span style="display:block;font-size:0.75rem;opacity:0.8;">Unique Words</span>
                                <span id="insight-words" style="font-size:1.25rem;font-weight:700;">–</span>
                            </div>
                            <div style="background:rgba(255,255,255,0.2);padding:0.5rem 1rem;border-radius:12px;">
                                <span style="display:block;font-size:0.75rem;opacity:0.8;">Status</span>
                                <span id="insight-status" style="font-size:1.1rem;font-weight:700;">–</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-heading">Recent Recordings</h3>
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
            const res = await fetch('../../api_speech_history.php?child_id=' + childId);
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

            container.innerHTML = entries.map(e => {
                const dt = new Date(e.sent_at);
                const timeStr = dt.toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) + ' · ' + dt.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'});
                const words = e.vocabulary_score ? Math.round(e.vocabulary_score) : '–';
                const clarify = e.clarify_score ? Math.round(e.clarify_score * 100) + '%' : '–';
                const transcript = e.transcript ? (e.transcript.length > 80 ? e.transcript.substring(0, 80) + '…' : e.transcript) : 'No transcript';
                const sColor = statusColor(e.status);
                const entryJson = encodeURIComponent(JSON.stringify(e));
                return `<div class="dashboard-card" style="display:flex;align-items:flex-start;padding:1.5rem;gap:1.5rem;margin-bottom:0.75rem;border-left:4px solid ${sColor};">
                    <div style="width:3rem;height:3rem;background:#ede9fe;color:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">🎙️</div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.25rem;flex-wrap:wrap;">
                            <h4 style="font-weight:700;">${timeStr}</h4>
                            <span style="background:${sColor}20;color:${sColor};padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:600;">${e.status || 'Unknown'}</span>
                        </div>
                        <p style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;font-style:italic;">"${transcript}"</p>
                        <div style="display:flex;gap:1.5rem;font-size:0.8rem;color:var(--slate-400);">
                            <span>📖 <strong>${words}</strong> unique words</span>
                            <span>🔊 Clarity: <strong>${clarify}</strong></span>
                        </div>
                    </div>
                    <button onclick="openSpeechDetailModal(decodeURIComponent('${entryJson}'))" style="flex-shrink:0;padding:0.5rem 1rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:10px;font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                        View Analysis
                    </button>
                </div>`;
            }).join('');
        } catch (err) {
            container.innerHTML = '<div class="dashboard-card" style="padding:2rem;text-align:center;color:var(--red-500);">Could not load speech history.</div>';
        }
    }

    window.openSpeechModal = function(childId) {
        let existing = document.getElementById('speech-modal');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'speech-modal';
        modal.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.remove()">
            <div style="background:var(--surface-light,#fff);border-radius:20px;padding:2.5rem;max-width:440px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">🎙️ New Speech Recording</h2>
                <p style="color:var(--slate-500);font-size:0.9rem;margin-bottom:1.5rem;">Upload an audio or video file of your child speaking</p>
                <input type="file" id="speech-file-input" accept="audio/*,video/*" style="width:100%;padding:0.75rem;border:2px dashed var(--slate-300,#cbd5e1);border-radius:12px;font-size:0.9rem;margin-bottom:1rem;cursor:pointer;box-sizing:border-box;">
                <button onclick="submitSpeechRecording(${childId})" style="width:100%;padding:0.875rem;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;" id="speech-submit-btn">Analyze Speech</button>
                <div id="speech-progress" style="margin-top:1rem;font-size:0.9rem;"></div>
            </div>
        </div>`;
        document.body.appendChild(modal);
    };

    window.submitSpeechRecording = async function(childId) {
        const fileInput = document.getElementById('speech-file-input');
        const btn = document.getElementById('speech-submit-btn');
        const progress = document.getElementById('speech-progress');

        if (!fileInput || !fileInput.files[0]) {
            if (progress) { progress.style.color = '#ef4444'; progress.textContent = 'Please select an audio file first.'; }
            return;
        }
        const formData = new FormData();
        formData.append('audio', fileInput.files[0]);
        formData.append('child_id', childId);

        btn.disabled = true;
        btn.textContent = 'Analyzing… (this may take a minute)';
        if (progress) { progress.style.color = '#6366f1'; progress.textContent = '🔬 Transcribing with AI…'; }

        try {
            const res = await fetch('../../api_speech_analysis.php', { method: 'POST', body: formData });
            const data = await res.json();
            const modal = document.getElementById('speech-modal');
            if (data.success) {
                if (progress) { progress.style.color = '#22c55e'; progress.textContent = '✅ ' + data.message; }
                btn.textContent = 'Done!';
                setTimeout(() => { if (modal) modal.remove(); loadSpeechHistory(childId); switchView('speech'); }, 2000);
            } else {
                if (progress) { progress.style.color = '#ef4444'; progress.textContent = '❌ ' + (data.error || 'Analysis failed'); }
                btn.disabled = false;
                btn.textContent = 'Analyze Speech';
            }
        } catch (e) {
            if (progress) { progress.style.color = '#ef4444'; progress.textContent = '❌ Network error. Ensure the Python server is running.'; }
            btn.disabled = false;
            btn.textContent = 'Analyze Speech';
        }
    };

    function getMotorView() {
        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Motor Skills</h1>
                        <p class="dashboard-subtitle">Gross and fine motor skill assessment</p>
                    </div>
                     <button class="btn btn-gradient">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <line x1="7" y1="2" x2="7" y2="22"></line>
                            <line x1="17" y1="2" x2="17" y2="22"></line>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <line x1="2" y1="7" x2="7" y2="7"></line>
                            <line x1="2" y1="17" x2="7" y2="17"></line>
                            <line x1="17" y1="17" x2="22" y2="17"></line>
                            <line x1="17" y1="7" x2="22" y2="7"></line>
                        </svg>
                        Upload Video
                    </button>
                </div>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Gross Motor</h3>
                            <span class="badge badge-green">On Track</span>
                        </div>
                        <div class="card-content">
                            <p style="margin-bottom: 1rem; color: var(--slate-600);">Emma is confidently walking and starting to run. She can climb onto furniture without assistance.</p>
                            <div class="progress-item" style="margin-bottom: 0.5rem;">
                                <div class="progress-label">
                                    <span>Walking Stability</span>
                                    <span>95%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 95%"></div>
                                </div>
                            </div>
                             <div class="progress-item">
                                <div class="progress-label">
                                    <span>Climbing</span>
                                    <span>80%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 80%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Fine Motor</h3>
                            <span class="badge badge-green">Advanced</span>
                        </div>
                        <div class="card-content">
                            <p style="margin-bottom: 1rem; color: var(--slate-600);">She is showing excellent pincer grasp control and can stack 4-5 blocks.</p>
                             <div class="progress-item" style="margin-bottom: 0.5rem;">
                                <div class="progress-label">
                                    <span>Pincer Grasp</span>
                                    <span>98%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 98%"></div>
                                </div>
                            </div>
                             <div class="progress-item">
                                <div class="progress-label">
                                    <span>Stacking</span>
                                    <span>90%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 90%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-heading" style="margin-top: 2rem;">Milestone Checklist (15 Months)</h3>
                <div class="dashboard-card">
                    <div style="display: grid; gap: 1px; background: var(--slate-100);">
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            <span style="flex: 1; text-decoration: line-through; color: var(--slate-500);">Walks without holding on</span>
                        </div>
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1; text-decoration: line-through; color: var(--slate-500);">Scribbles spontaneously</span>
                        </div>
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1;">Drinks from a cup</span>
                        </div>
                         <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1;">Uses a spoon (with some spilling)</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

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
                        <p class="dashboard-subtitle">AI-powered personalized recommendations for ${child ? child.first_name : 'your child'}</p>
                    </div>
                    <button class="btn btn-gradient" onclick="loadAIRecommendations('${childParam}')" id="ai-refresh-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="margin-right:6px">
                            <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>
                        Get New Recommendations
                    </button>
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
                    <h3 class="section-heading">Completed Activities</h3>
                    <div id="activity-history-list">
                        <div class="dashboard-card" style="padding:1.5rem;text-align:center;color:var(--slate-500);">Loading history...</div>
                    </div>
                </div>
            </div>
        `;
    }

    // ── AI Recommendations Loader ─────────────────────────────
    window.loadAIRecommendations = async function(childId) {
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
                    const catColors = {parenting:'#6366f1',development:'#8b5cf6',health:'#22c55e',nutrition:'#f59e0b'};
                    const color = catColors[art.category] || '#6366f1';
                    html += `<div class="ai-card ai-card-article" style="--accent:${color}">
                        <div class="ai-card-badge" style="background:${color}15;color:${color}">${art.category || 'article'}</div>
                        <h4 class="ai-card-title">${art.title}</h4>
                        <p class="ai-card-desc">${art.summary}</p>
                        <div class="ai-card-footer">
                            <span class="ai-card-meta">📖 ${art.read_time || '5 min read'}</span>
                            <button class="btn btn-outline btn-sm" onclick="completeActivity(${childId}, 'article', ${i})">Mark Read ✓</button>
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
                    const catIcons = {motor:'💪',speech:'🗣️',cognitive:'🧠',social:'🤝'};
                    const icon = catIcons[act.category] || '🎯';
                    const diffColors = {easy:'#22c55e',medium:'#f59e0b',hard:'#ef4444'};
                    const diffColor = diffColors[act.difficulty] || '#f59e0b';
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
                            <button class="btn btn-gradient btn-sm" onclick="completeActivity(${childId}, 'real_life', ${i})">Complete ✓</button>
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
                    const typeIcons = {interactive:'🕹️',quiz:'❓',creative:'🎨'};
                    const icon = typeIcons[game.type] || '🎮';
                    html += `<div class="ai-card ai-card-game">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                            <span style="font-size:1.75rem;">${icon}</span>
                            <span class="ai-card-badge" style="background:#8b5cf615;color:#8b5cf6">${game.type || 'interactive'}</span>
                        </div>
                        <h4 class="ai-card-title">${game.title}</h4>
                        <p class="ai-card-desc">${game.description}</p>
                        <div class="ai-card-footer">
                            <span class="ai-card-meta">🎯 ${game.skill_focus || 'Development'} • ${game.duration || '10 min'}</span>
                            <button class="btn btn-outline btn-sm" onclick="completeActivity(${childId}, 'website_game', ${i})">Play ▶</button>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }

            container.innerHTML = html;

            // Load activity history
            loadActivityHistory(childId);

        } catch(e) {
            container.innerHTML = `<div class="dashboard-card" style="padding:2rem;text-align:center;">
                <p style="color:var(--red-500);margin-bottom:1rem;">⚠️ Failed to load recommendations</p>
                <button class="btn btn-outline" onclick="loadAIRecommendations('${childId}')">Try Again</button>
            </div>`;
        }
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    };

    window.completeActivity = async function(childId, category, index) {
        try {
            // Get the latest activities for this child
            const res = await fetch('../../api_activities.php?action=history&child_id=' + childId);
            const data = await res.json();
            const activities = (data.activities || []).filter(a => !a.is_completed);

            // Find matching activity
            let activityId = null;
            let count = 0;
            for (const act of activities) {
                if (act.category === category || (category === 'real_life' && ['motor','speech','cognitive','social'].includes(act.category))) {
                    if (count === index) { activityId = act.activity_id; break; }
                    count++;
                }
            }

            if (!activityId) {
                // fallback: just complete the first uncompleted
                if (activities.length > 0) activityId = activities[0].activity_id;
            }

            if (activityId) {
                const res2 = await fetch('../../api_activities.php?action=complete', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({activity_id: activityId, child_id: childId})
                });
                const result = await res2.json();
                if (result.success) {
                    showBadgeToast('Activity completed! +15 points 🎉');
                    // Also trigger streak check
                    streakCheckIn();
                    loadNotifCount();
                }
            }
        } catch(e) { console.error('Complete activity error:', e); }
    };

    async function loadActivityHistory(childId) {
        const container = document.getElementById('activity-history-list');
        if (!container) return;
        try {
            const res = await fetch('../../api_activities.php?action=history&child_id=' + childId);
            const data = await res.json();
            const completed = (data.activities || []).filter(a => a.is_completed == 1);
            if (completed.length === 0) {
                container.innerHTML = '<div class="dashboard-card" style="padding:1.5rem;text-align:center;color:var(--slate-500);">No completed activities yet. Start by completing a recommendation above!</div>';
                return;
            }
            container.innerHTML = completed.slice(0, 10).map(a => {
                const dt = new Date(a.completed_at);
                const dateStr = dt.toLocaleDateString('en-US', {month:'short',day:'numeric'});
                const catIcons = {article:'📚',real_life:'🎯',website_game:'🎮',motor:'💪',speech:'🗣️',cognitive:'🧠',social:'🤝'};
                const icon = catIcons[a.category] || '✅';
                return `<div class="dashboard-card" style="padding:1rem;margin-bottom:0.5rem;display:flex;align-items:center;gap:1rem;">
                    <span style="font-size:1.5rem;">${icon}</span>
                    <div style="flex:1;"><h4 style="font-weight:600;margin-bottom:0.25rem;">${a.title}</h4>
                    <span style="font-size:0.8rem;color:var(--slate-500);">${dateStr} • +${a.points_earned} pts</span></div>
                    <span class="badge badge-green">Completed</span>
                </div>`;
            }).join('');
        } catch(e) {
            container.innerHTML = '<div class="dashboard-card" style="padding:1rem;text-align:center;color:var(--slate-500);">Could not load history</div>';
        }
    }

    function getClinicView() {
        return `
        <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Book Appointment 🏥</h1>
                        <p class="dashboard-subtitle">Connect with trusted healthcare providers</p>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <!-- Search Bar -->
                        <div style="position: relative;">
                            <input type="text" placeholder="Search by specialty, doctor, or clinic..." 
                                style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: 1px solid var(--slate-200); border-radius: var(--radius-lg); font-size: 1rem;">
                            <svg style="position: absolute; left: 1rem; top: 1rem; width: 1.25rem; height: 1.25rem; color: var(--slate-400);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>

                        <!-- Doctor Card 1 -->
                        <div class="dashboard-card" style="display: flex; gap: 1.5rem; padding: 1.5rem;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Smith&background=random" style="width: 4rem; height: 4rem; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between;">
                                    <h3 style="font-size: 1.1rem; font-weight: 700;">Dr. Sarah Smith</h3>
                                    <span class="badge badge-green">4.9 ★</span>
                                </div>
                                <p style="color: var(--blue-600); font-weight: 500; font-size: 0.9rem;">Pediatrician • 12 years exp</p>
                                <p style="color: var(--slate-500); font-size: 0.9rem; margin: 0.5rem 0 1rem;">City Kids Care, downtown</p>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-gradient btn-sm">Book Visit</button>
                                    <button class="btn btn-outline btn-sm">Profile</button>
                                </div>
                            </div>
                        </div>

                         <!-- Doctor Card 2 -->
                        <div class="dashboard-card" style="display: flex; gap: 1.5rem; padding: 1.5rem;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Chen&background=random" style="width: 4rem; height: 4rem; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between;">
                                    <h3 style="font-size: 1.1rem; font-weight: 700;">Dr. Michael Chen</h3>
                                    <span class="badge badge-green">4.8 ★</span>
                                </div>
                                <p style="color: var(--blue-600); font-weight: 500; font-size: 0.9rem;">Child Psychologist • 8 years exp</p>
                                <p style="color: var(--slate-500); font-size: 0.9rem; margin: 0.5rem 0 1rem;">Wellness Center, Westside</p>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-gradient btn-sm">Book Visit</button>
                                    <button class="btn btn-outline btn-sm">Profile</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel: Upcoming -->
                    <div>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title" style="font-size: 1rem;">Your Appointments</h3>
                            </div>
                            <div class="card-content">
                                <div class="appointment-item" style="border-bottom: 1px solid var(--slate-100); padding-bottom: 1rem; margin-bottom: 1rem;">
                                    <div style="font-weight: 600;">MMR Vaccination</div>
                                    <div style="font-size: 0.875rem; color: var(--slate-500);">Nov 28, 10:00 AM</div>
                                    <div style="font-size: 0.875rem; color: var(--blue-600);">Dr. Smith</div>
                                </div>
                                 <div class="appointment-item">
                                    <div style="font-weight: 600;">15-Month Checkup</div>
                                    <div style="font-size: 0.875rem; color: var(--slate-500);">Dec 15, 2:30 PM</div>
                                    <div style="font-size: 0.875rem; color: var(--blue-600);">Dr. Johnson</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getReportsView() {
        const d = window.dashboardData || {};
        const children = d.children || [];
        const child = children[0] || null;
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
                                <button class="btn btn-outline btn-sm" onclick="window.location.href='../../profile.php'">Edit Profile</button>
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
                                <button class="btn btn-gradient btn-sm" onclick="window.location.href='../../subscription.php'">${sub.plan_name === 'Premium' ? 'Manage' : 'Upgrade'}</button>
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
    window.handleThemeToggle = function(isDark) {
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
        saveSettingToggle('theme', isDark ? 'dark' : 'light');
    };

    window.handleLangChange = function(lang) {
        document.getElementById('lang-en').classList.toggle('active', lang === 'en');
        document.getElementById('lang-ar').classList.toggle('active', lang === 'ar');
        if (lang === 'ar' && document.documentElement.getAttribute('lang') !== 'ar') {
            toggleLanguage();
        } else if (lang === 'en' && document.documentElement.getAttribute('lang') === 'ar') {
            toggleLanguage();
        }
        saveSetting('language', lang);
    };

    window.saveSettingToggle = function(key, value) {
        const payload = {};
        payload[key] = typeof value === 'boolean' ? (value ? 1 : 0) : value;
        fetch('../../api_settings.php?action=update', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(e => console.warn('Settings save error:', e));
    };

    function saveSetting(key, value) {
        const payload = {};
        payload[key] = value;
        fetch('../../api_settings.php?action=update', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(e => console.warn('Settings save error:', e));
    }

    window.confirmDeleteAccount = function() {
        let existing = document.getElementById('delete-account-modal');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'delete-account-modal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:var(--white,#fff);border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
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

    async function loadNotifications() {
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
            loadNotifications();
            loadNotifCount();
        } catch (e) { }
    };

    window.markAllRead = async function () {
        try {
            await fetch('../../api_notifications.php?action=read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            loadNotifications();
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
                <div style="background:var(--white,#fff);border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
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

    window.openSpeechDetailModal = function(entryJson) {
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
        const timeStr = dt.toLocaleDateString('en-US', {month:'long',day:'numeric',year:'numeric'}) + ' at ' + dt.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'});
        
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
})();

// Handle logout – Premium modal
function handleLogout() {
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

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (modal) {
        modal.classList.remove('show');
        modal.classList.add('hide');
        setTimeout(() => modal.remove(), 300);
    }
}

function confirmLogout() {
    clearAuth();
    window.location.href = '../../logout.php';
}

// ══════════════════════════════════════════════════════════════
// ── Add / Edit Child Modal ─────────────────────────────────
// ══════════════════════════════════════════════════════════════
window.openAddChildModal = function(childData) {
    const isEdit = !!childData;
    const existing = document.getElementById('add-child-modal');
    if (existing) existing.remove();

    const birthVal = childData ?
        `${String(childData.birth_year).padStart(4,'0')}-${String(childData.birth_month).padStart(2,'0')}-${String(childData.birth_day).padStart(2,'0')}` : '';

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
            <!-- Body -->
            <div style="padding:1.75rem 2rem;overflow-y:auto;max-height:calc(90vh - 200px);">
                <div id="acm-status" style="margin-bottom:1rem;display:none;"></div>
                <form id="acm-form" onsubmit="event.preventDefault();submitChildModal();">
                    <input type="hidden" id="acm-child-id" value="${childData?.child_id || ''}">
                    
                    <!-- Basic Info -->
                    <div style="margin-bottom:1.5rem;">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--slate-400,#94a3b8);margin-bottom:0.75rem;">Basic Information</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">First Name *</label>
                                <input type="text" id="acm-fname" required value="${childData?.first_name || ''}" placeholder="Enter first name" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);transition:border-color 0.2s,box-shadow 0.2s;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF';this.style.boxShadow='0 0 0 3px rgba(108,99,255,0.1)'" onblur="this.style.borderColor='';this.style.boxShadow=''">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Last Name *</label>
                                <input type="text" id="acm-lname" required value="${childData?.last_name || ''}" placeholder="Enter last name" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);transition:border-color 0.2s,box-shadow 0.2s;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF';this.style.boxShadow='0 0 0 3px rgba(108,99,255,0.1)'" onblur="this.style.borderColor='';this.style.boxShadow=''">
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

                    <!-- Growth Measurements -->
                    <div style="margin-bottom:1.5rem;">
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                            <span style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--slate-400,#94a3b8);">Growth Measurements</span>
                            <span style="font-size:0.7rem;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff;padding:0.15rem 0.5rem;border-radius:20px;font-weight:600;">+25 pts</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Weight (kg)</label>
                                <input type="number" step="0.1" id="acm-weight" placeholder="0.0" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF'" onblur="this.style.borderColor=''">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Height (cm)</label>
                                <input type="number" step="0.1" id="acm-height" placeholder="0.0" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF'" onblur="this.style.borderColor=''">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-primary,#1e293b);margin-bottom:0.35rem;">Head (cm)</label>
                                <input type="number" step="0.1" id="acm-head" placeholder="0.0" style="width:100%;padding:0.65rem 0.85rem;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;font-size:0.9rem;background:var(--bg-main,#f8fafc);color:var(--text-primary,#1e293b);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#6C63FF'" onblur="this.style.borderColor=''">
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
    modal._escHandler = function(e) { if (e.key === 'Escape') closeAddChildModal(); };
    document.addEventListener('keydown', modal._escHandler);
};

window.closeAddChildModal = function() {
    const modal = document.getElementById('add-child-modal');
    if (!modal) return;
    const card = document.getElementById('acm-card');
    if (card) card.style.transform = 'scale(0.9) translateY(20px)';
    modal.style.opacity = '0';
    document.removeEventListener('keydown', modal._escHandler);
    setTimeout(() => modal.remove(), 300);
};

window.submitChildModal = async function() {
    const btn = document.getElementById('acm-submit');
    const status = document.getElementById('acm-status');
    const childId = document.getElementById('acm-child-id').value;
    
    const payload = {
        child_id: childId || null,
        first_name: document.getElementById('acm-fname').value.trim(),
        last_name: document.getElementById('acm-lname').value.trim(),
        birth_date: document.getElementById('acm-dob').value,
        gender: document.getElementById('acm-gender').value,
        weight: document.getElementById('acm-weight').value || null,
        height: document.getElementById('acm-height').value || null,
        head_circumference: document.getElementById('acm-head').value || null
    };

    if (!payload.first_name || !payload.last_name || !payload.birth_date) {
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
    style.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
}
