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
        { id: 'notifications', label: 'Notifications', icon: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' },
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
                ${item.id === 'notifications' ? '<span class="notif-badge" id="nav-notif-badge" style="display:none;background:#ef4444;color:white;font-size:0.65rem;font-weight:700;min-width:1.1rem;height:1.1rem;border-radius:50%;display:none;align-items:center;justify-content:center;margin-left:auto;"></span>' : ''}
            </button>
        `).join('');
        // Load unread notification count
        loadNotifCount();

        // Add click handlers
        navContainer.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function () {
                const view = this.dataset.view;
                switchView(view);
            });
        });
    }

    // Switch view
    function switchView(viewId) {
        const contentContainer = document.getElementById('dashboard-content');
        if (!contentContainer) {
            window.location.href = 'dashboard.php?view=' + viewId;
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

        // Re-apply translations to newly injected content if in Arabic mode
        if (typeof retranslateCurrentPage === 'function') {
            retranslateCurrentPage();
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
                    <a href="child-profile.php" class="btn btn-gradient">Add Child Profile</a>
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
            <div class="streak-cards">
                <div class="streak-card streak-yellow">
                    <div class="streak-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                    <div class="streak-info"><div class="streak-number">${child.badge_count || 0}</div><div class="streak-label">Badges</div></div>
                </div>
            </div></div>
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
                    <div class="card-content">
                        <div class="activity-item activity-blue"><div class="activity-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/></svg></div>
                            <div class="activity-info"><h4 class="activity-title">Reading Time</h4><p class="activity-description">Read a picture book together. Point to objects and say their names clearly.</p><span class="activity-duration">⏱ 15 minutes</span></div></div>
                        <div class="activity-item activity-purple"><div class="activity-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></div>
                            <div class="activity-info"><h4 class="activity-title">Stacking Blocks</h4><p class="activity-description">Practice hand-eye coordination by stacking colorful blocks together.</p><span class="activity-duration">⏱ 10 minutes</span></div></div>
                    </div>
                </div>
                <div class="dashboard-column">
                    <div class="dashboard-card">
                        <div class="card-header"><h3 class="card-title"><svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Upcoming Appointments</h3></div>
                        <div class="card-content">${apptHtml}</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-header"><h3 class="card-title">Points Wallet</h3></div>
                        <div class="card-content" style="text-align:center;padding:1.5rem;">
                            <div style="font-size:2rem;font-weight:800;color:var(--blue-600);">${child.total_points || 0}</div>
                            <div style="color:var(--slate-500);">Total Points</div>
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
        selectorHtml += `<div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;opacity:0.6;" onclick="window.location.href='child-profile.php'">
            <div style="width:4rem;height:4rem;border:2px dashed var(--slate-300);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--slate-400);"><svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></div>
            <span style="margin-top:0.5rem;font-weight:500;color:var(--slate-500);">New</span></div>`;

        if (!child) {
            return `<div class="dashboard-content"><div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1><p class="dashboard-subtitle">No children yet</p></div>
            <a href="child-profile.php" class="btn btn-outline">Add Child</a></div></div>`;
        }

        const g = child.growth || {};
        const fullName = (child.first_name || '') + ' ' + (child.last_name || '');
        const init = (child.first_name || '?')[0].toUpperCase();

        return `<div class="dashboard-content">
            <div class="dashboard-header-section"><div><h1 class="dashboard-title">Child Profile</h1><p class="dashboard-subtitle">Manage profiles and view progress</p></div>
            <a href="child-profile.php" class="btn btn-outline"><svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add Child</a></div>
            <div class="dashboard-card" style="margin-bottom:2rem;"><div class="card-content"><h3 class="card-title" style="margin-bottom:1rem;font-size:1rem;">Select Child Profile</h3>
                <div style="display:flex;gap:1.5rem;overflow-x:auto;padding-bottom:0.5rem;">${selectorHtml}</div></div></div>
            <div class="child-profile-card" style="margin-bottom:2rem;"><div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="display:flex;gap:1.5rem;align-items:center;"><div class="child-avatar" style="width:5rem;height:5rem;font-size:2rem;">${init}</div>
                <div class="child-info"><h2 class="child-name" style="font-size:1.75rem;">${fullName}</h2>
                <div class="child-details"><span>${child.age_display || ''}</span><span>•</span><span>Born: ${child.birth_date_formatted || ''}</span></div></div></div>
                <a href="child-profile.php?child_id=${child.child_id}" class="btn btn-ghost">Edit Details</a></div></div>
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
                <a href="child-profile.php" class="btn btn-gradient">Add Child</a>
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
            <a href="child-profile.php?child_id=${child.child_id}" class="btn btn-gradient">
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
            const res = await fetch('api_speech_history.php?child_id=' + childId);
            const data = await res.json();
            const entries = data.analyses || [];

            // Update AI Insight card from most recent entry
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
            const res = await fetch('api_speech_analysis.php', { method: 'POST', body: formData });
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
                    <audio controls style="width:100%;height:40px;border-radius:8px;" src="${entry.audio_url || ''}">
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
        </div>`;
        document.body.appendChild(modal);
    };

    function getMotorView() {
        return `<div class="dashboard-content">
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
        return `<div class="dashboard-content">
                 <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Activity Center 🎨</h1>
                        <p class="dashboard-subtitle">Personalized play for development</p>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h3 class="section-heading">Today's Schedule</h3>
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <div style="background: var(--orange-100); height: 8px; width: 100%;"></div>
                            <div class="card-content">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 700; color: var(--orange-600);">Morning</span>
                                    <span style="color: var(--slate-500);">10:00 AM</span>
                                </div>
                                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Sensory Bin Dig</h4>
                                <p style="font-size: 0.9rem; color: var(--slate-600); margin-bottom: 1rem;">Fill a bin with rice or pasta and hide small toys for Emma to find. Great for fine motor skills.</p>
                                <button class="btn btn-outline btn-sm" style="width: 100%;">Mark Complete</button>
                            </div>
                        </div>

                         <div class="dashboard-card">
                            <div style="background: var(--blue-100); height: 8px; width: 100%;"></div>
                            <div class="card-content">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 700; color: var(--blue-600);">Afternoon</span>
                                    <span style="color: var(--slate-500);">3:30 PM</span>
                                </div>
                                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Music & Movement</h4>
                                <p style="font-size: 0.9rem; color: var(--slate-600); margin-bottom: 1rem;">Dance to favorite nursery rhymes. Encourage clapping and stomping.</p>
                                <button class="btn btn-outline btn-sm" style="width: 100%;">Mark Complete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-heading">Explore Collections</h3>
                <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--red-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><svg style="width:2rem;height:2rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><path d="m15 9-2-2-5 6.5L3 17.5m17-3.5-5.5-4.5-3 3.5 3 3"/></svg></div>
                        <span style="font-weight: 600;">Gross Motor</span>
                    </div>
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--purple-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🗣️</div>
                        <span style="font-weight: 600;">Speech</span>
                    </div>
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--green-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🧩</div>
                        <span style="font-weight: 600;">Thinking</span>
                    </div>
                     <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--yellow-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🤝</div>
                        <span style="font-weight: 600;">Social</span>
                    </div>
                </div>
            </div>
        `;
    }

    function getClinicView() {
        return `<div class="dashboard-content">
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
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('api_export_pdf.php?type=speech-report${childParam}','_blank')">📥 Download PDF</button>
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

        return `<div class="dashboard-content">
                <div class="dashboard-header-section">
                     <div>
                        <h1 class="dashboard-title">Reports & Insights 📄</h1>
                        <p class="dashboard-subtitle">Download summaries for your healthcare provider</p>
                    </div>
                     <button class="btn btn-gradient" onclick="window.open('api_export_pdf.php?type=full-report${childParam}','_blank')">
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
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('api_export_pdf.php?type=full-report${childParam}','_blank')">📥 Download PDF</button>
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
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('api_export_pdf.php?type=growth-report${childParam}','_blank')">📥 Download PDF</button>
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
                                <button class="btn btn-gradient btn-sm btn-full" onclick="window.open('api_export_pdf.php?type=child-report${childParam}','_blank')">📥 Download PDF</button>
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

        return `<div class="dashboard-content">
                <h1 class="dashboard-title">Settings ⚙️</h1>
                <p class="dashboard-subtitle" style="margin-bottom: 2rem;">Manage your account and app preferences</p>
                
                <div style="max-width: 800px;">
                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">Profile Settings</h3>
                        </div>
                        <div class="card-content">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Parent Name</label>
                                <input type="text" value="${parentName}" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                            </div>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
                                <input type="email" value="${parentEmail}" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                            </div>
                            <button class="btn btn-gradient" onclick="window.location.href='profile.php'">Edit Profile</button>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                         <div class="card-header">
                            <h3 class="card-title">Child's Information</h3>
                        </div>
                         <div class="card-content">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Child Name</label>
                                    <input type="text" value="${childName}" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);" readonly>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Birth Date</label>
                                    <input type="date" value="${childBirth}" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);" readonly>
                                </div>
                            </div>
                            <button class="btn btn-outline" onclick="window.location.href='child-profile.php${child ? '?child_id=' + child.child_id : ''}'">Edit Child Profile</button>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">Notifications</h3>
                        </div>
                        <div class="card-content">
                             <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--slate-100);">
                                <div>
                                    <h4 style="font-weight: 600;">Daily Reminders</h4>
                                    <p style="font-size: 0.875rem; color: var(--slate-500);">Receive daily activity suggestions</p>
                                </div>
                                <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            </div>
                             <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0;">
                                <div>
                                    <h4 style="font-weight: 600;">Milestone Alerts</h4>
                                    <p style="font-size: 0.875rem; color: var(--slate-500);">Get notified when milestones are approaching</p>
                                </div>
                                <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header"><h3 class="card-title">Security</h3></div>
                        <div class="card-content">
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;">
                                <div><h4 style="font-weight:600;">Change Password</h4><p style="font-size:0.875rem;color:var(--slate-500);">Update your account password</p></div>
                                <button class="btn btn-outline btn-sm" onclick="openChangePasswordModal()">Change</button>
                            </div>
                        </div>
                    </div>

                     <div class="dashboard-card" style="border: 1px solid var(--red-200);">
                        <div class="card-content">
                            <h3 class="card-title" style="color: var(--red-600);">Danger Zone</h3>
                            <p style="color: var(--slate-500); margin-bottom: 1rem;">Irreversible actions</p>
                            <button class="btn btn-outline" style="color: var(--red-600); border-color: var(--red-200);">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

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
            const res = await fetch('api_notifications.php?action=list&limit=1');
            const data = await res.json();
            const badge = document.getElementById('nav-notif-badge');
            if (badge && data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = 'flex';
            }
        } catch (e) { /* silent */ }
    }
    window.loadNotifCount = loadNotifCount;

    async function loadNotifications() {
        const container = document.getElementById('notifications-list');
        if (!container) return;
        try {
            const res = await fetch('api_notifications.php?action=list&limit=30');
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
            await fetch('api_notifications.php?action=read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: id })
            });
            loadNotifications();
            loadNotifCount();
        } catch (e) { }
    };

    window.markAllRead = async function () {
        try {
            await fetch('api_notifications.php?action=read', {
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
            const res = await fetch('api_who_compare.php?child_id=' + childId);
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
        modal.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1000;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)this.parentElement.remove()">
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
            const res = await fetch('api_email_verify.php?action=change-password', {
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

    // Initialize
    initNav();
    const urlParams = new URLSearchParams(window.location.search);
    const initialView = urlParams.get('view') || 'home';
    
    if (document.getElementById('dashboard-content')) {
        switchView(initialView);
    } else {
        // We are on a different page like settings.php
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
    modal.innerHTML = `<div class="logout-overlay" onclick="closeLogoutModal()"></div>
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
    window.location.href = 'logout.php';
}
