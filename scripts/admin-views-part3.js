// Revenue view has been consolidated into loadSubscriptionsView in admin-dashboard.js

// ═══ SYSTEM HEALTH ═══
let _healthRefreshTimer = null;
function fmtBytes(b) { if(!b||b<0) return '0 B'; const u=['B','KB','MB','GB','TB']; let i=0; while(b>=1024&&i<u.length-1){b/=1024;i++;} return b.toFixed(i>0?1:0)+' '+u[i]; }

async function loadSystemHealthView(main) {
    if (_healthRefreshTimer) { clearInterval(_healthRefreshTimer); _healthRefreshTimer = null; }
    try {
        const [md, ld] = await Promise.all([apiGet('system_health.php?action=metrics'), apiGet('system_health.php?action=logs')]);
        const m = md.metrics, logs = ld.logs||[];
        const statusColor = m.uptime_status==='healthy'?'var(--green-500)':m.uptime_status==='degraded'?'var(--yellow-500)':'var(--red-500)';
        const memPercent = m.memory_limit > 0 ? Math.round((m.memory_usage / m.memory_limit) * 100) : 0;
        const memColor = memPercent > 80 ? 'var(--red-500)' : memPercent > 50 ? 'var(--yellow-500)' : 'var(--green-500)';
        const diskColor = m.disk_percent > 80 ? 'var(--red-500)' : m.disk_percent > 50 ? 'var(--yellow-500)' : 'var(--green-500)';

        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">System Health & Monitoring</h1><p class="dashboard-subtitle">Real-time server metrics and system logs</p></div>
            <div class="header-actions-inline"><span id="health-last-update" style="font-size:.8rem;color:var(--text-secondary);margin-right:.75rem;">Updated: ${m.server_time}</span><button class="btn btn-outline" onclick="showAdminView('system_health')" style="margin-right:.5rem;">⟳ Refresh</button><button class="btn btn-outline" onclick="downloadLogs('csv')">↓ CSV</button><button class="btn btn-outline" onclick="downloadLogs('txt')">↓ TXT</button></div></div>

        <!-- Status Banner -->
        <div style="background:linear-gradient(135deg,${statusColor}22,${statusColor}11);border:1px solid ${statusColor}44;border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;">
            <div style="width:48px;height:48px;border-radius:50%;background:${statusColor};display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="width:24px;height:24px;"><path d="${m.uptime_status==='healthy'?'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3':'M12 8v4M12 16h.01M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z'}"/>${m.uptime_status==='healthy'?'<polyline points="22 4 12 14.01 9 11.01"/>':''}</svg>
            </div>
            <div>
                <div style="font-weight:700;font-size:1.1rem;color:${statusColor};">${m.uptime_status.toUpperCase()}</div>
                <div style="font-size:.85rem;color:var(--text-secondary);">Uptime: ${m.uptime_percent}% over last 7 days • ${m.errors_24h} errors, ${m.warnings_24h} warnings in 24h</div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">${m.active_sessions}</div><div class="admin-stat-label">Active Sessions</div><div class="admin-stat-trend">${m.online_now} online now</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${m.avg_response_ms}ms</div><div class="admin-stat-label">Avg Response Time</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(m.total_users)}</div><div class="admin-stat-label">Total Users</div></div></div>
            <div class="admin-stat-card" style="border-left:4px solid var(--red-500);"><div class="admin-stat-info"><div class="admin-stat-value">${m.errors_24h}</div><div class="admin-stat-label">Errors (24h)</div></div></div>
        </div>

        <!-- Server Info Grid -->
        <div class="overview-grid" style="margin-top:1.5rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Server Information</h2></div><div style="padding:1.5rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="detail-row"><span class="detail-label">PHP Version</span><span class="detail-value" style="font-weight:600;color:var(--indigo-500);">${m.php_version}</span></div>
                    <div class="detail-row"><span class="detail-label">Server Software</span><span class="detail-value">${m.server_software}</span></div>
                    <div class="detail-row"><span class="detail-label">Server Time</span><span class="detail-value">${m.server_time}</span></div>
                    <div class="detail-row"><span class="detail-label">Timezone</span><span class="detail-value">${m.timezone}</span></div>
                </div>
            </div></div>

            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Database</h2></div><div style="padding:1.5rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="detail-row"><span class="detail-label">Database Size</span><span class="detail-value" style="font-weight:600;">${fmtBytes(m.db_size)}</span></div>
                    <div class="detail-row"><span class="detail-label">Tables</span><span class="detail-value">${m.db_tables}</span></div>
                    <div class="detail-row"><span class="detail-label">Total Rows</span><span class="detail-value">${fmtNum(m.db_rows)}</span></div>
                    <div class="detail-row"><span class="detail-label">Engine</span><span class="detail-value">MariaDB / InnoDB</span></div>
                </div>
            </div></div>
        </div>

        <!-- Memory & Disk -->
        <div class="overview-grid" style="margin-top:1.5rem;">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Memory Usage</h2></div><div style="padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;font-size:.85rem;"><span>Current: ${fmtBytes(m.memory_usage)}</span><span>Limit: ${m.memory_limit_str}</span></div>
                <div style="background:var(--bg-secondary);border-radius:8px;height:24px;overflow:hidden;">
                    <div style="height:100%;width:${memPercent}%;background:${memColor};border-radius:8px;transition:width .5s;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;font-weight:600;">${memPercent}%</div>
                </div>
                <div style="margin-top:.75rem;font-size:.8rem;color:var(--text-secondary);">Peak: ${fmtBytes(m.memory_peak)}</div>
            </div></div>

            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Disk Usage</h2></div><div style="padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;font-size:.85rem;"><span>Used: ${fmtBytes(m.disk_used)}</span><span>Total: ${fmtBytes(m.disk_total)}</span></div>
                <div style="background:var(--bg-secondary);border-radius:8px;height:24px;overflow:hidden;">
                    <div style="height:100%;width:${m.disk_percent}%;background:${diskColor};border-radius:8px;transition:width .5s;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;font-weight:600;">${m.disk_percent}%</div>
                </div>
                <div style="margin-top:.75rem;font-size:.8rem;color:var(--text-secondary);">Free: ${fmtBytes(m.disk_free)}</div>
            </div></div>
        </div>

        ${m.last_error ? `<div class="section-card" style="margin-top:1.5rem;border-left:4px solid var(--red-500);"><div style="padding:1rem 1.5rem;display:flex;align-items:center;gap:1rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--red-500)" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><div style="font-size:.85rem;font-weight:600;">Last Error</div><div style="font-size:.8rem;color:var(--text-secondary);">${m.last_error.message} — ${timeAgo(m.last_error.created_at)}</div></div>
        </div></div>` : ''}

        <!-- Logs Table -->
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Recent Logs</h2>
            <select class="search-input" style="width:auto;" id="log-level-filter" onchange="filterLogs()"><option value="">All Levels</option><option value="info">Info</option><option value="warning">Warning</option><option value="error">Error</option><option value="critical">Critical</option></select></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Level</th><th>Message</th><th>Endpoint</th><th>Response</th><th>Time</th><th>Actions</th></tr></thead><tbody>
                ${logs.map(l => `<tr>
                    <td><span class="status-badge ${l.level==='error'||l.level==='critical'?'status-danger':l.level==='warning'?'status-warning':'status-active'}">${l.level}</span></td>
                    <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;">${l.message}</td>
                    <td><code>${l.endpoint||'—'}</code></td>
                    <td>${l.response_time_ms?l.response_time_ms+'ms':'—'}</td>
                    <td>${timeAgo(l.created_at)}</td>
                    <td><button class="btn btn-sm btn-outline" onclick="viewLogDetail(${l.id})">View</button></td>
                </tr>`).join('')}
                ${logs.length===0?'<tr><td colspan="6" style="text-align:center;padding:2rem;">No logs</td></tr>':''}
            </tbody></table></div></div></div>`;

        // Auto-refresh every 30 seconds
        _healthRefreshTimer = setInterval(async () => {
            try {
                const d = await apiGet('system_health.php?action=metrics');
                const el = document.getElementById('health-last-update');
                if (el) el.textContent = 'Updated: ' + d.metrics.server_time;
            } catch(e) {}
        }, 30000);

        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function viewLogDetail(id) {
    try {
        const d = await apiGet('system_health.php?action=view_log&id='+id);
        const l = d.log;
        showModal('Log Detail', `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="detail-row"><span class="detail-label">Level</span><span class="detail-value"><span class="status-badge ${l.level==='error'?'status-danger':'status-active'}">${l.level}</span></span></div>
                <div class="detail-row"><span class="detail-label">Endpoint</span><span class="detail-value"><code>${l.endpoint||'—'}</code></span></div>
                <div class="detail-row"><span class="detail-label">Method</span><span class="detail-value">${l.method||'—'}</span></div>
                <div class="detail-row"><span class="detail-label">Response Time</span><span class="detail-value">${l.response_time_ms?l.response_time_ms+'ms':'—'}</span></div>
                <div class="detail-row"><span class="detail-label">User</span><span class="detail-value">${l.first_name?l.first_name+' '+l.last_name:'System'}</span></div>
                <div class="detail-row"><span class="detail-label">Time</span><span class="detail-value">${fmtDate(l.created_at)}</span></div>
            </div>
            <div style="margin-top:1rem;"><strong>Message:</strong><p>${l.message}</p></div>
            ${l.stack_trace?`<div style="margin-top:1rem;"><strong>Stack Trace:</strong><pre style="background:var(--bg-secondary);padding:1rem;border-radius:8px;overflow-x:auto;font-size:.8rem;">${l.stack_trace}</pre></div>`:''}
            ${l.request_payload?`<div style="margin-top:1rem;"><strong>Request Payload:</strong><pre style="background:var(--bg-secondary);padding:1rem;border-radius:8px;overflow-x:auto;font-size:.8rem;">${l.request_payload}</pre></div>`:''}
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch(e){showAlert('Error','error');}
}

async function filterLogs() { const lvl = document.getElementById('log-level-filter')?.value||''; showAdminView('system_health'); }

async function downloadLogs(fmt) {
    try {
        const d = await apiGet('system_health.php?action=download&format='+fmt);
        const logs = d.data||[];
        if (fmt==='csv') {
            const csv = 'ID,Level,Message,Endpoint,Method,Response(ms),Time\n' + logs.map(l=>`${l.id},"${l.message}",${l.endpoint||''},${l.method||''},${l.response_time_ms||''},"${l.created_at}"`).join('\n');
            const blob = new Blob([csv],{type:'text/csv'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='system-logs.csv'; a.click();
        } else {
            const txt = logs.map(l=>`[${l.created_at}] [${l.level}] ${l.message} (${l.endpoint||''})`).join('\n');
            const blob = new Blob([txt],{type:'text/plain'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='system-logs.txt'; a.click();
        }
        showAlert('Logs downloaded!','success');
    } catch(e){showAlert('Download failed','error');}
}

// ═══ ROLES & PERMISSIONS ═══

// ═══ ROLES & PERMISSIONS (REDESIGNED) ═══
async function loadRolesView(main) {
    try {
        const data = await apiGet('roles.php?action=list');
        const roles = data.roles||[], admins = data.admins||[];
        const allPerms = ['overview','users','clinics','subscriptions','points','reports','notifications','moderation','system_health','roles','tickets','banners','settings'];
        const permLabels = {overview:'Dashboard Overview',users:'User Management',clinics:'Clinic Management',subscriptions:'Subscriptions',points:'Points & Rewards',reports:'Reports & Analytics',notifications:'Notifications',moderation:'Content Moderation',system_health:'System Health',roles:'Roles & Permissions',tickets:'Support Tickets',banners:'Banners',settings:'Platform Settings'};
        const svgBase = 'stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"';
        const permIcons = {
            overview: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-4"/></svg>`,
            users: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>`,
            clinics: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3M12 2v3"/></svg>`,
            subscriptions: `<svg ${svgBase} viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`,
            points: `<svg ${svgBase} viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
            reports: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`,
            notifications: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`,
            moderation: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
            system_health: `<svg ${svgBase} viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>`,
            roles: `<svg ${svgBase} viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>`,
            tickets: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2-2 2 2 0 012-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 012 2 2 2 0 01-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4z"/></svg>`,
            banners: `<svg ${svgBase} viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
            settings: `<svg ${svgBase} viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>`
        };

        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Roles & Permissions</h1><p class="dashboard-subtitle">Define access controls, manage admin team, and assign granular permissions</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showCreateRole()">+ Create Role</button></div></div>

        <div class="admin-stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat-card" style="border-left:4px solid var(--indigo-500);"><div class="admin-stat-icon" style="background:var(--indigo-100);color:var(--indigo-600);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${roles.length}</div><div class="admin-stat-label">Total Roles</div></div></div>
            <div class="admin-stat-card" style="border-left:4px solid var(--teal-500);"><div class="admin-stat-icon" style="background:var(--teal-100);color:var(--teal-600);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${admins.length}</div><div class="admin-stat-label">Admin Members</div></div></div>
            <div class="admin-stat-card" style="border-left:4px solid var(--amber-500);"><div class="admin-stat-icon" style="background:var(--amber-100);color:var(--amber-600);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div><div class="admin-stat-info"><div class="admin-stat-value">${allPerms.length}</div><div class="admin-stat-label">Permission Types</div></div></div>
        </div>

        <div class="section-card" style="margin-top:1.5rem;padding:0;overflow:hidden;">
            <div style="display:flex;border-bottom:1px solid var(--border-color);">
                <button class="admin-tab-btn active" id="tab-btn-roles" onclick="switchRolesTab('roles')" style="flex:1;padding:1rem;border:none;background:transparent;font-weight:600;font-size:.9rem;color:var(--primary-color);border-bottom:3px solid var(--primary-color);cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Role Definitions</button>
                <button class="admin-tab-btn" id="tab-btn-team" onclick="switchRolesTab('team')" style="flex:1;padding:1rem;border:none;background:transparent;font-weight:600;font-size:.9rem;color:var(--text-secondary);border-bottom:3px solid transparent;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Admin Team</button>
            </div>
            <div id="roles-content" style="padding:1.5rem;min-height:350px;"></div>
        </div>
        </div>`;

        // Store data for tab rendering
        window._rolesData = { roles: roles, admins: admins, allPerms: allPerms, permLabels: permLabels, permIcons: permIcons };

        window.switchRolesTab = function(tab) {
            ['roles','team'].forEach(function(t) {
                var btn = document.getElementById('tab-btn-'+t);
                if(btn) { btn.style.color = t===tab?'var(--primary-color)':'var(--text-secondary)'; btn.style.borderBottomColor = t===tab?'var(--primary-color)':'transparent'; }
            });
            renderRolesTab(tab);
        };
        switchRolesTab('roles');
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = '<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>'+e.message+'</p></div>'; }
}

function renderRolesTab(tab) {
    var cont = document.getElementById('roles-content');
    var d = window._rolesData;

    if (tab === 'roles') {
        var ht = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem;">';
        d.roles.forEach(function(r) {
            var perms = JSON.parse(r.permissions||'[]');
            var isSuperAdmin = r.name.toLowerCase() === 'super admin';
            var isFullAccess = perms.includes('all');
            var permCount = isFullAccess ? d.allPerms.length : perms.length;
            var gradientMap = {'super admin':'linear-gradient(135deg,#6366f1,#818cf8)','content manager':'linear-gradient(135deg,#0d9488,#14b8a6)','moderator':'linear-gradient(135deg,#f59e0b,#fbbf24)','viewer':'linear-gradient(135deg,#64748b,#94a3b8)'};
            var gradient = gradientMap[r.name.toLowerCase()] || 'linear-gradient(135deg,var(--primary-color),#818cf8)';

            ht += '<div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;transition:transform .15s,box-shadow .15s;" onmouseenter="this.style.transform=\'translateY(-2px)\';this.style.boxShadow=\'0 8px 24px rgba(0,0,0,0.08)\';" onmouseleave="this.style.transform=\'none\';this.style.boxShadow=\'none\';">';
            ht += '<div style="background:'+gradient+';padding:1.25rem;color:#fff;position:relative;">';
            ht += '<div style="display:flex;justify-content:space-between;align-items:flex-start;">';
            ht += '<div><h3 style="margin:0;font-size:1.1rem;font-weight:700;">'+r.name+'</h3>';
            ht += '<p style="margin:.25rem 0 0;font-size:.8rem;opacity:.85;">'+(r.description||'No description')+'</p></div>';
            if (isSuperAdmin) ht += '<span style="background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:600;">Protected</span>';
            ht += '</div></div>';
            ht += '<div style="padding:1.25rem;">';
            ht += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;"><span style="font-size:.8rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">Permissions</span><span style="font-size:.75rem;color:var(--primary-color);font-weight:600;">'+permCount+'/'+d.allPerms.length+'</span></div>';
            ht += '<div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1rem;">';
            var displayPerms = isFullAccess ? d.allPerms : perms;
            displayPerms.slice(0,10).forEach(function(p) {
                ht += '<span style="background:var(--bg-secondary);padding:4px 8px;border-radius:6px;font-size:.7rem;color:var(--text-primary);display:flex;align-items:center;gap:.35rem;border:1px solid var(--border-color);">'+(d.permIcons[p]||`<svg stroke="currentColor" stroke-width="2" fill="none" style="width:14px;height:14px;"><circle cx="12" cy="12" r="3"/></svg>`)+' '+(d.permLabels[p]||p)+'</span>';
            });
            if (displayPerms.length > 10) ht += '<span style="background:var(--primary-color);color:#fff;padding:4px 8px;border-radius:6px;font-size:.7rem;display:flex;align-items:center;">+'+(displayPerms.length-10)+' more</span>';
            ht += '</div>';
            ht += '<div style="display:flex;gap:.5rem;">';
            ht += '<button class="btn btn-outline btn-sm" style="flex:1;" onclick="showEditRole('+r.id+')">Edit Role</button>';
            if (!isSuperAdmin) ht += '<button class="btn btn-outline btn-sm" style="color:var(--red-500);padding:.4rem .75rem;" onclick="deleteRole('+r.id+',\''+r.name.replace(/'/g,"\\'")+'\')" title="Delete">🗑</button>';
            ht += '</div></div></div>';
        });
        ht += '</div>';
        cont.innerHTML = ht;
    } else {
        var ht2 = '<div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Admin</th><th>Email</th><th>Role</th><th>Permissions</th><th>Status</th><th style="width:150px;">Actions</th></tr></thead><tbody>';
        d.admins.forEach(function(a) {
            ht2 += '<tr>';
            ht2 += '<td><div class="table-user"><div class="patient-avatar" style="'+avatarColors.admin+'">'+getInitials(a.first_name,a.last_name)+'</div><div><div class="patient-name">'+a.first_name+' '+a.last_name+'</div></div></div></td>';
            ht2 += '<td>'+a.email+'</td>';
            ht2 += '<td><select class="search-input" style="width:auto;padding:.35rem .5rem;font-size:.8rem;border-radius:8px;" onchange="assignRole('+a.user_id+',this.value)">';
            d.roles.forEach(function(r) { ht2 += '<option value="'+r.id+'" '+(r.id == a.role_level ? 'selected' : '')+'>'+r.name+'</option>'; });
            ht2 += '</select></td>';
            ht2 += '<td><div style="display:flex;flex-wrap:wrap;gap:.25rem;">';
            var ap = a.role_permissions||[];
            ap.slice(0,3).forEach(function(p) { ht2 += '<span style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:.7rem;display:inline-flex;align-items:center;gap:.2rem;">'+(d.permIcons[p]||'')+' '+(d.permLabels[p]||p)+'</span>'; });
            if (ap.length > 3) ht2 += '<span style="background:var(--primary-color);color:#fff;padding:2px 6px;border-radius:4px;font-size:.7rem;">+'+(ap.length-3)+'</span>';
            ht2 += '</div></td>';
            ht2 += '<td><span class="status-badge '+(a.status==='active'?'status-active':'status-danger')+'">'+a.status+'</span></td>';
            ht2 += '<td><div class="action-btns"><button class="btn btn-sm btn-outline" onclick="viewAdminProfile('+a.user_id+')">View</button><button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="revokeAdmin('+a.user_id+',\''+a.first_name+' '+a.last_name+'\')">Revoke</button></div></td>';
            ht2 += '</tr>';
        });
        ht2 += '</tbody></table></div>';
        cont.innerHTML = ht2;
    }
}

async function assignRole(userId, roleId) {
    try {
        var r = await apiPost('roles.php', {action:'assign_role', user_id:userId, role_id:parseInt(roleId)});
        if(r.success) {
            showAlert('Role assigned!','success');
            // Hard refresh the roles view so state updates
            showAdminView('roles');
            closeModal();
        } else {
            showAlert(r.error||'Failed','error');
        }
    } catch(e) { showAlert('Error assigning role','error'); }
}

function deleteRole(id, name) {
    showConfirm('Delete the <strong>'+name+'</strong> role? Admins with this role will lose their permissions.', async function(){
        try{var r=await apiPost('roles.php',{action:'delete_role',role_id:id});if(r.success){showAlert('Role deleted!','success');setTimeout(function(){closeModal();showAdminView('roles');},1000);}else showAlert(r.error||'Failed','error');}catch(e){showAlert('Error','error');}
    },'error');
}

function showCreateRole() {
    var d = window._rolesData;
    var body = '<div class="form-group"><label>Role Name <span style="color:var(--red-500);">*</span></label><input type="text" id="cr-name" placeholder="e.g. Content Manager"></div>';
    body += '<div class="form-group"><label>Description</label><textarea id="cr-desc" rows="2" placeholder="Brief description of this role"></textarea></div>';
    body += '<div class="form-group"><label style="margin-bottom:.75rem;display:block;">Permissions <span style="font-size:.75rem;color:var(--text-secondary);">(select what this role can access)</span></label>';
    body += '<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;padding:.5rem .75rem;background:var(--bg-secondary);border-radius:8px;"><input type="checkbox" id="cr-all" onchange="document.querySelectorAll(\'.cr-perm\').forEach(function(c){c.checked=document.getElementById(\'cr-all\').checked;})"><label for="cr-all" style="margin:0;cursor:pointer;font-weight:600;font-size:.85rem;">Select All Permissions</label></div>';
    body += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">';
    d.allPerms.forEach(function(p) {
        body += '<label style="display:flex;gap:.5rem;align-items:center;padding:.4rem .6rem;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;font-size:.85rem;transition:background .15s;" onmouseenter="this.style.background=\'var(--bg-secondary)\'" onmouseleave="this.style.background=\'transparent\'"><input type="checkbox" class="cr-perm" value="'+p+'"> '+(d.permIcons[p]||'')+' '+(d.permLabels[p]||p)+'</label>';
    });
    body += '</div>';
    body += '<div style="margin-top:1rem;border-top:1px solid var(--border-color);padding-top:1rem;"><label style="display:flex;align-items:center;gap:.5rem;">Custom Permissions <span style="font-size:.7rem;color:var(--text-secondary);background:var(--bg-secondary);padding:2px 6px;border-radius:4px;">Advanced</span></label>';
    body += '<p style="font-size:.75rem;color:var(--text-secondary);margin:.25rem 0 .75rem;">Enter application-specific capabilities (comma-separated). Example: <code>bypass_filters, export_financials, manage_api_keys</code></p>';
    body += '<input type="text" id="cr-custom" placeholder="e.g. bypass_filters, export_financials"></div>';
    body += '</div>';
    showModal('Create New Role', body, '<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="cr-save">Create Role</button>');
    document.getElementById('cr-save').onclick = async function() {
        var perms = [].slice.call(document.querySelectorAll('.cr-perm:checked')).map(function(c){return c.value;});
        var custom = document.getElementById('cr-custom').value.split(',').map(function(s){return s.trim();}).filter(function(s){return s;});
        perms = perms.concat(custom);
        var payload = {action:'add_role', name:document.getElementById('cr-name').value, description:document.getElementById('cr-desc').value, permissions:perms};
        if(!payload.name){showAlert('Name required','warning');return;}
        try{var r=await apiPost('roles.php',payload);if(r.success){showAlert('Role created!','success');setTimeout(function(){closeModal();showAdminView('roles');},1000);}else showAlert(r.error||'Failed','error');}catch(e){showAlert('Error','error');}
    };
}

function showEditRole(roleId) {
    var d = window._rolesData;
    var role = d.roles.find(function(r){return r.id === roleId;});
    if (!role) { showAlert('Role not found','error'); return; }
    var current = JSON.parse(role.permissions||'[]');
    var isAll = current.includes('all');
    var knownPerms = d.allPerms;
    var customPerms = current.filter(function(p){return p!=='all' && knownPerms.indexOf(p)===-1;});

    var body = '<div class="form-group"><label>Role Name</label><input type="text" id="er-name" value="'+role.name+'"></div>';
    body += '<div class="form-group"><label>Description</label><textarea id="er-desc" rows="2">'+(role.description||'')+'</textarea></div>';
    body += '<div class="form-group"><label style="margin-bottom:.75rem;display:block;">Permissions</label>';
    body += '<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;padding:.5rem .75rem;background:var(--bg-secondary);border-radius:8px;"><input type="checkbox" id="er-all" '+(isAll?'checked':'')+' onchange="document.querySelectorAll(\'.er-perm\').forEach(function(c){c.checked=document.getElementById(\'er-all\').checked;})"><label for="er-all" style="margin:0;cursor:pointer;font-weight:600;font-size:.85rem;">Select All</label></div>';
    body += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">';
    d.allPerms.forEach(function(p) {
        var checked = isAll || current.indexOf(p) !== -1;
        body += '<label style="display:flex;gap:.5rem;align-items:center;padding:.4rem .6rem;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;font-size:.85rem;transition:background .15s;" onmouseenter="this.style.background=\'var(--bg-secondary)\'" onmouseleave="this.style.background=\'transparent\'"><input type="checkbox" class="er-perm" value="'+p+'" '+(checked?'checked':'')+'> '+(d.permIcons[p]||'')+' '+(d.permLabels[p]||p)+'</label>';
    });
    body += '</div>';
    body += '<div style="margin-top:1rem;border-top:1px solid var(--border-color);padding-top:1rem;"><label style="display:flex;align-items:center;gap:.5rem;">Custom Permissions <span style="font-size:.7rem;color:var(--text-secondary);background:var(--bg-secondary);padding:2px 6px;border-radius:4px;">Advanced</span></label>';
    body += '<p style="font-size:.75rem;color:var(--text-secondary);margin:.25rem 0 .75rem;">Enter application-specific capabilities (comma-separated). Example: <code>bypass_filters, export_financials, manage_api_keys</code></p>';
    body += '<input type="text" id="er-custom" value="'+customPerms.join(', ')+'"></div>';
    body += '</div>';

    var isSuperAdmin = role.name.toLowerCase() === 'super admin';
    body += '<div style="margin-top:1rem;padding:1rem;background:var(--bg-secondary);border-radius:12px;">';
    body += '<h4 style="font-size:.875rem;font-weight:600;margin:0 0 .75rem;">Role Members</h4>';
    var members = d.admins.filter(function(a){return a.role_level == roleId;});
    if (members.length) {
        members.forEach(function(m) {
            body += '<div style="display:flex;justify-content:space-between;align-items:center;padding:.35rem 0;"><div style="display:flex;align-items:center;gap:.5rem;"><div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;">'+getInitials(m.first_name,m.last_name)+'</div><span style="font-size:.85rem;font-weight:500;">'+m.first_name+' '+m.last_name+'</span></div><button class="btn btn-sm" style="color:var(--red-500);padding:0;background:none;font-size:.7rem;" onclick="assignRole('+m.user_id+', 4)">Remove</button></div>';
        });
    } else body += '<p style="font-size:.85rem;color:var(--text-secondary);margin:0 0 .75rem;">No admins assigned to this role</p>';
    
    // Add dropdown to assign a new member to this role
    body += '<div style="margin-top:1rem;border-top:1px solid var(--border-color);padding-top:1rem;"><label style="font-size:.8rem;">Assign Admin to Role</label><div style="display:flex;gap:.5rem;margin-top:.25rem;"><select id="er-assign-admin" class="search-input" style="flex:1;"><option value="">-- Select Admin --</option>';
    d.admins.forEach(function(a) { 
        if(a.role_level != roleId && a.status === 'active') {
            body += '<option value="'+a.user_id+'">'+a.first_name+' '+a.last_name+' (Current: '+a.role_name+')</option>';
        }
    });
    body += '</select><button class="btn btn-sm" style="background:var(--primary-color);color:#fff;white-space:nowrap;" onclick="var s=document.getElementById(\'er-assign-admin\').value; if(s){assignRole(s,'+roleId+'); closeModal(); setTimeout(function(){showEditRole('+roleId+');},500);}">Assign</button></div></div>';
    
    body += '</div>';

    showModal('Edit Role — ' + role.name, body, '<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="er-save">Save Changes</button>');
    document.getElementById('er-save').onclick = async function() {
        var perms = [].slice.call(document.querySelectorAll('.er-perm:checked')).map(function(c){return c.value;});
        var custom = document.getElementById('er-custom').value.split(',').map(function(s){return s.trim();}).filter(function(s){return s;});
        perms = perms.concat(custom);
        try{var r=await apiPost('roles.php',{action:'update_role',role_id:roleId,name:document.getElementById('er-name').value,description:document.getElementById('er-desc').value,permissions:perms});if(r.success){showAlert('Updated!','success');setTimeout(function(){closeModal();showAdminView('roles');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
    };
}

async function viewAdminProfile(id) {
    try {
        var d = await apiGet('roles.php?action=view&id='+id);
        var a = d.admin, audit = d.audit||[];
        var body = '<div style="display:flex;gap:1.5rem;align-items:flex-start;">';
        body += '<div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;flex-shrink:0;">'+getInitials(a.first_name,a.last_name)+'</div>';
        body += '<div style="flex:1;"><h3 style="margin:0;font-size:1.1rem;">'+a.first_name+' '+a.last_name+'</h3>';
        body += '<p style="margin:.25rem 0 0;font-size:.85rem;color:var(--text-secondary);">'+a.email+'</p>';
        body += '<div style="display:flex;gap:.5rem;margin-top:.5rem;"><span class="status-badge '+(a.status==='active'?'status-active':'status-danger')+'">'+a.status+'</span><span style="font-size:.75rem;color:var(--text-secondary);">Joined '+fmtDate(a.created_at)+'</span></div></div></div>';
        body += '<div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border-color);"><h4 style="font-size:.875rem;font-weight:600;margin:0 0 .75rem;">Recent Activity</h4>';
        body += '<div style="max-height:200px;overflow-y:auto;">';
        if (audit.length) {
            audit.forEach(function(l) {
                body += '<div style="display:flex;align-items:flex-start;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border-color);"><div class="activity-dot '+getActivityDotColor(l.activity_type)+'" style="margin-top:4px;"></div><div><div style="font-size:.85rem;">'+l.description+'</div><div style="font-size:.75rem;color:var(--text-secondary);">'+timeAgo(l.created_at)+'</div></div></div>';
            });
        } else body += '<p style="color:var(--text-secondary);font-size:.85rem;margin:0;">No recent actions recorded</p>';
        body += '</div></div>';
        showModal('Admin Profile', body, '<button class="btn btn-outline" onclick="closeModal()">Close</button>');
    } catch(e){showAlert('Error loading profile','error');}
}

function revokeAdmin(userId,name) {
    showConfirm('Revoke admin access for <strong>'+name+'</strong>? They will lose all admin permissions.', async function(){
        try{var r=await apiPost('roles.php',{action:'revoke_access',user_id:userId});if(r.success){showAlert('Access revoked!','success');setTimeout(function(){closeModal();showAdminView('roles');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
    },'error');
}

window.hasPerm = typeof hasPermission !== "undefined" ? hasPermission : function(){return true;};
