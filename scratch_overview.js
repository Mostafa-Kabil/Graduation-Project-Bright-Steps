// ═══ OVERVIEW ═══
async function loadOverviewView(main) {
    try {
        const data = await apiGet('overview.php');
        if (!data.success) throw new Error(data.error);
        const s = data.stats, dist = data.user_distribution;
        const topClinics = data.top_clinics || [], recentPayments = data.recent_payments || [];
        const subDist = data.subscription_distribution || [], signupChart = data.signup_chart || [];
        const revenueChart = data.revenue_chart || [];
        const sysLogs = data.system_logs || [], auditLogs = data.recent_audit || [];
        const totalDist = (dist.parent || 0) + (dist.specialist || 0) + (dist.admin || 0) + (dist.clinic || 0);

        // System log level icons/colors
        const logLevel = (l) => ({ info: {icon:'ℹ',cls:'log-info'}, warning: {icon:'⚠',cls:'log-warn'}, error: {icon:'✕',cls:'log-error'}, critical: {icon:'🔥',cls:'log-critical'} }[l] || {icon:'•',cls:'log-info'});

        main.innerHTML = `<div class="dashboard-content">
        <!-- Header -->
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Platform Overview</h1>
                <p class="dashboard-subtitle">System-wide analytics and insights</p>
            </div>
            <div class="header-actions-inline">
                <div class="overview-date-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    ${new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}
                </div>
            </div>
        </div>

        <!-- Primary KPI Cards (2 rows of 3) -->
        <div class="admin-stats-grid overview-stats-6">
            <div class="admin-stat-card admin-stat-indigo stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtNum(s.total_users)}</div>
                    <div class="admin-stat-label">Total Users</div>
                    <div class="admin-stat-trend ${s.users_trend >= 0 ? 'trend-up' : 'trend-down'}">${s.users_trend >= 0 ? '↑' : '↓'} ${Math.abs(s.users_trend)}% vs last month</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-purple stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 0-4 4v4a4 4 0 0 0 8 0V6a4 4 0 0 0-4-4z"/><path d="M16 10a4 4 0 0 1-8 0"/><path d="M12 18v4"/><path d="M8 22h8"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtNum(s.total_children)}</div>
                    <div class="admin-stat-label">Children Registered</div>
                    <div class="admin-stat-trend">Tracked on the platform</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-teal stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtNum(s.active_clinics)} / ${fmtNum(s.total_clinics)}</div>
                    <div class="admin-stat-label">Clinics (Active / Total)</div>
                    ${s.pending_clinics > 0 ? `<div class="admin-stat-trend trend-down">${s.pending_clinics} pending approval</div>` : '<div class="admin-stat-trend trend-up">All approved</div>'}
                </div>
            </div>
            <div class="admin-stat-card admin-stat-emerald stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtMoney(s.total_revenue)}</div>
                    <div class="admin-stat-label">Total Revenue</div>
                    <div class="admin-stat-trend ${s.revenue_trend >= 0 ? 'trend-up' : 'trend-down'}">${fmtMoney(s.revenue_this_month)} this month</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-amber stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtNum(s.active_subscriptions)}</div>
                    <div class="admin-stat-label">Active Subscriptions</div>
                    <div class="admin-stat-trend">${fmtNum(s.total_specialists)} specialists</div>
                </div>
            </div>
            <div class="admin-stat-card admin-stat-rose stat-card-hover">
                <div class="admin-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <div class="admin-stat-info">
                    <div class="admin-stat-value">${fmtNum(s.open_tickets)}</div>
                    <div class="admin-stat-label">Open Support Tickets</div>
                    ${s.open_tickets > 0 ? '<div class="admin-stat-trend trend-down">Needs attention</div>' : '<div class="admin-stat-trend trend-up">All clear</div>'}
                </div>
            </div>
        </div>

        <!-- Secondary Info Row -->
        <div class="overview-metrics-row overview-metrics-4">
            <div class="metric-mini-card">
                <div class="metric-mini-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                </div>
                <div><div class="metric-mini-value">${fmtNum(s.total_appointments)}</div><div class="metric-mini-label">Appointments</div></div>
                <div class="metric-mini-badge">${s.upcoming_appointments} upcoming</div>
            </div>
            <div class="metric-mini-card">
                <div class="metric-mini-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div><div class="metric-mini-value">${fmtNum(s.growth_records)}</div><div class="metric-mini-label">Growth Records</div></div>
                <div class="metric-mini-badge">+${s.growth_this_month} this mo</div>
            </div>
            <div class="metric-mini-card">
                <div class="metric-mini-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div><div class="metric-mini-value">${s.avg_rating > 0 ? '★ ' + s.avg_rating : '—'}</div><div class="metric-mini-label">Avg Rating</div></div>
                <div class="metric-mini-badge">${s.total_feedback} reviews</div>
            </div>
            <div class="metric-mini-card">
                <div class="metric-mini-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                </div>
                <div><div class="metric-mini-value">${fmtNum(s.users_this_month)}</div><div class="metric-mini-label">New Users (Month)</div></div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="overview-grid overview-grid-2col">
            <div class="section-card">
                <div class="section-card-header" style="justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                    <h2 class="section-heading" id="signup-chart-heading">User Signups</h2>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <div id="overview-signup-custom" style="display:none;gap:.25rem;">
                            <input type="date" id="overview-signup-start" class="search-input" style="padding:.25rem .5rem;font-size:.8rem;max-width:120px;">
                            <input type="date" id="overview-signup-end" class="search-input" style="padding:.25rem .5rem;font-size:.8rem;max-width:120px;">
                        </div>
                        <select class="settings-select" id="overview-signup-range" style="width:auto;padding:.25rem .5rem;font-size:.8rem;">
                            <option value="week" selected>Last 7 Days</option>
                            <option value="month">Last Month</option>
                            <option value="quarter">Last Quarter</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <button class="btn btn-sm btn-gradient" id="overview-signup-apply" style="padding:.25rem .75rem;display:none;">Apply</button>
                    </div>
                </div>
                <div style="padding:1.5rem;"><canvas id="overview-signup-chart" height="200"></canvas></div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Revenue Trend</h2></div>
                <div style="padding:1.5rem;"><canvas id="overview-revenue-chart" height="200"></canvas></div>
            </div>
        </div>

        <!-- Middle Row: Sub Dist + Top Clinics + User Dist -->
        <div class="overview-grid overview-grid-3col">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Subscription Plans</h2></div>
                <div style="padding:1.5rem;">
                    <canvas id="overview-sub-chart" height="180"></canvas>
                    <div style="margin-top:1rem;">
                        ${subDist.map(p => `<div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>${p.plan_name}</div><div class="dist-value">${fmtNum(p.user_count)} users</div></div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Top Clinics</h2></div>
                <div class="top-clinics-list">
                    ${topClinics.length > 0 ? topClinics.map((c, i) => `
                        <div class="top-clinic-row">
                            <div class="top-clinic-rank">${i + 1}</div>
                            <div class="top-clinic-info">
                                <div class="top-clinic-name">${c.clinic_name}</div>
                                <div class="top-clinic-meta">${c.specialist_count} specialists</div>
                            </div>
                            <div class="top-clinic-rating">★ ${Number(c.rating).toFixed(1)}</div>
                        </div>
                    `).join('') : '<div style="padding:1.5rem;text-align:center;color:var(--text-secondary);">No clinics yet</div>'}
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">User Distribution</h2></div>
                <div style="padding:1.5rem;">
                    <div class="distribution-bar-wrap">
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#6366f1;"></span>Parents</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.parent || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div></div><div class="dist-value">${fmtNum(dist.parent || 0)}</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#0d9488;"></span>Specialists</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.specialist || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#0d9488,#14b8a6);"></div></div><div class="dist-value">${fmtNum(dist.specialist || 0)}</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#d97706;"></span>Clinics</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.clinic || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#d97706,#f59e0b);"></div></div><div class="dist-value">${fmtNum(dist.clinic || 0)}</div></div>
                        <div class="distribution-row"><div class="dist-label"><span class="dist-dot" style="background:#ec4899;"></span>Admins</div><div class="dist-bar"><div class="dist-fill" style="width:${totalDist ? ((dist.admin || 0) / totalDist * 100) : 0}%;background:linear-gradient(90deg,#ec4899,#f472b6);"></div></div><div class="dist-value">${fmtNum(dist.admin || 0)}</div></div>
                    </div>
                    <div class="dist-total-row"><span>Total</span><span class="dist-total-value">${fmtNum(totalDist)}</span></div>
                </div>
            </div>
        </div>

        <!-- System Logs + Payments Row -->
        <div class="overview-grid overview-grid-2col">
            <div class="section-card">
                <div class="section-card-header">
                    <h2 class="section-heading">System Logs</h2>
                    <span class="section-card-count">${sysLogs.length} recent</span>
                </div>
                <div class="system-logs-feed">
                    ${sysLogs.length > 0 ? sysLogs.map(log => `<div class="sys-log-row">
                        <div class="sys-log-level ${logLevel(log.level).cls}">${logLevel(log.level).icon}</div>
                        <div class="sys-log-info">
                            <div class="sys-log-msg">${log.message}</div>
                            <div class="sys-log-meta">${log.endpoint ? `<code>${log.method || 'GET'} ${log.endpoint}</code>` : ''} ${log.response_time_ms ? `<span class="sys-log-time">${log.response_time_ms}ms</span>` : ''}</div>
                        </div>
                        <div class="sys-log-date">${timeAgo(log.created_at)}</div>
                    </div>`).join('') : '<div style="padding:2rem;text-align:center;color:var(--text-secondary);">No system logs found</div>'}
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header">
                    <h2 class="section-heading">Recent Payments</h2>
                    <span class="section-card-count">${recentPayments.length} latest</span>
                </div>
                <div class="recent-payments-list">
                    ${recentPayments.length > 0 ? recentPayments.map(p => `
                        <div class="payment-row">
                            <div class="payment-icon payment-icon-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <div class="payment-info">
                                <div class="payment-plan">${p.plan_name || 'Subscription'}</div>
                                <div class="payment-meta">${fmtDate(p.paid_at)} · ${p.method || 'Card'}</div>
                            </div>
                            <div class="payment-amount">$${Number(p.amount_post_discount || 0).toFixed(2)}</div>
                        </div>
                    `).join('') : '<div style="padding:2rem;text-align:center;color:var(--text-secondary);">No payments yet</div>'}
                </div>
            </div>
        </div>

        ${auditLogs.length > 0 ? `<!-- Audit Trail -->
        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">Recent Audit Trail</h2>
                <span class="section-card-count">${auditLogs.length} actions</span>
            </div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Action</th><th>Resource</th><th>User</th><th>IP Address</th><th>Time</th></tr></thead><tbody>
                ${auditLogs.map(a => `<tr>
                    <td><span class="status-badge status-active" style="font-size:.75rem;">${a.action}</span></td>
                    <td>${a.resource || '—'} ${a.resource_id ? '#'+a.resource_id : ''}</td>
                    <td>${a.first_name ? a.first_name + ' ' + a.last_name : '—'} ${a.role ? `<span class="role-badge role-${a.role}" style="font-size:.65rem;padding:1px 5px;margin-left:4px;">${a.role}</span>` : ''}</td>
                    <td style="font-family:monospace;font-size:.8rem;">${a.ip_address || '—'}</td>
                    <td>${timeAgo(a.created_at)}</td>
                </tr>`).join('')}
            </tbody></table></div>
        </div>` : ''}

        <!-- Quick Actions -->
        <div class="overview-quick-access">
            <h2 class="section-heading" style="margin-bottom:1rem;">Quick Actions</h2>
            <div class="quick-access-grid">
                <button class="quick-access-card" onclick="showAdminView('users')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <span>Manage Users</span>
                </button>
                <button class="quick-access-card" onclick="showAdminView('clinics')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#0d9488,#14b8a6);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
                    <span>View Clinics</span>
                </button>
                <button class="quick-access-card" onclick="showAdminView('subscriptions')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#059669,#34d399);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                    <span>Subscriptions</span>
                </button>
                <button class="quick-access-card" onclick="showAdminView('reports')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#d97706,#fbbf24);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                    <span>View Reports</span>
                </button>
                <button class="quick-access-card" onclick="showAdminView('tickets')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                    <span>Support Tickets</span>
                </button>
                <button class="quick-access-card" onclick="showAdminView('system_health')">
                    <div class="quick-access-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    <span>System Health</span>
                </button>
            </div>
        </div>
        </div>`;

        // ── Render Charts ────────────────────────────────
        if (typeof Chart !== 'undefined') {
            const chartText = getComputedStyle(document.body).getPropertyValue('--text-secondary')?.trim() || '#64748b';
            const chartGrid = getComputedStyle(document.body).getPropertyValue('--border-color')?.trim() || '#e2e8f0';
            // Signup chart
            const signupCtx = document.getElementById('overview-signup-chart');
            let signupChartInstance = null;
            if (signupCtx) {
                const renderSignupChart = (data, title) => {
                    if (signupChartInstance) signupChartInstance.destroy();
                    const heading = document.getElementById('signup-chart-heading');
                    if (heading) heading.textContent = title;
                    
                    let labels = [], vals = [];
                    // Pad last 7 days natively if data is 'week' range and too sparse, else use data dates
                    if (data.length <= 7 && title.includes('7 Days')) {
                        for (let i = 6; i >= 0; i--) { const d = new Date(); d.setDate(d.getDate() - i); const dStr = d.toISOString().split('T')[0]; labels.push(d.toLocaleDateString('en-US', { weekday: 'short' })); const found = data.find(r => r.signup_date === dStr); vals.push(found ? parseInt(found.count) : 0); }
                    } else {
                        labels = data.length ? data.map(r => fmtDate(r.signup_date)) : ['No data'];
                        vals = data.length ? data.map(r => parseInt(r.count)) : [0];
                    }

                    signupChartInstance = new Chart(signupCtx, { type: 'line', data: { labels, datasets: [{ label: 'Signups', data: vals, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#6366f1' }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: chartText, stepSize: 1 }, grid: { color: chartGrid } }, x: { ticks: { color: chartText }, grid: { display: false } } } } });
                };
                
                renderSignupChart(signupChart, 'User Signups (Last 7 Days)');

                // Interactivity
                const rangeSel = document.getElementById('overview-signup-range');
                const customDiv = document.getElementById('overview-signup-custom');
                const applyBtn = document.getElementById('overview-signup-apply');

                rangeSel.addEventListener('change', async function() {
                    const val = this.value;
                    if (val === 'custom') { customDiv.style.display = 'flex'; applyBtn.style.display = 'block'; return; }
                    customDiv.style.display = 'none'; applyBtn.style.display = 'none';
                    try {
                        let btnText = rangeSel.options[rangeSel.selectedIndex].text;
                        const res = await apiGet('overview.php?action=signup_chart&range=' + val);
                        renderSignupChart(res.chart, 'User Signups (' + btnText + ')');
                    } catch(e) { showAlert('Failed to load chart data', 'error'); }
                });

                applyBtn.addEventListener('click', async function() {
                    const df = document.getElementById('overview-signup-start').value;
                    const dt = document.getElementById('overview-signup-end').value;
                    if (!df || !dt) { showAlert('Please select both start and end dates', 'warning'); return; }
                    if (new Date(df) > new Date(dt)) { showAlert('Start date must be before end date', 'warning'); return; }
                    try {
                        const res = await apiGet(`overview.php?action=signup_chart&range=custom&date_from=${df}&date_to=${dt}`);
                        renderSignupChart(res.chart, `User Signups (${fmtDate(df)} - ${fmtDate(dt)})`);
                    } catch(e) { showAlert('Failed to load chart data', 'error'); }
                });
            }
            // Revenue chart
            const revenueCtx = document.getElementById('overview-revenue-chart');
            if (revenueCtx) {
                const rLabels = revenueChart.map(r => { const [y, m] = r.month.split('-'); return new Date(y, m - 1).toLocaleDateString('en-US', { month: 'short' }); });
                const rVals = revenueChart.map(r => parseFloat(r.revenue));
                new Chart(revenueCtx, { type: 'line', data: { labels: rLabels.length ? rLabels : ['No data'], datasets: [{ label: 'Revenue ($)', data: rVals.length ? rVals : [0], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#10b981' }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: chartText, callback: v => '$' + v }, grid: { color: chartGrid } }, x: { ticks: { color: chartText }, grid: { display: false } } } } });
            }
            // Sub chart
            const subCtx = document.getElementById('overview-sub-chart');
            if (subCtx && subDist.length > 0) {
                new Chart(subCtx, { type: 'doughnut', data: { labels: subDist.map(p => p.plan_name), datasets: [{ data: subDist.map(p => parseInt(p.user_count)), backgroundColor: ['#6366f1', '#0d9488', '#f59e0b', '#ec4899', '#8b5cf6'], borderWidth: 0, hoverOffset: 8 }] }, options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: chartText, padding: 12, usePointStyle: true, pointStyle: 'circle' } } } } });
            }
        }
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (err) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error loading overview</h2><p>${err.message}</p></div>`; }
}

