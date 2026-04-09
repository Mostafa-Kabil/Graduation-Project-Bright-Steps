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
async function loadRolesView(main) {
    try {
        const data = await apiGet('roles.php?action=list');
        const roles = data.roles||[], admins = data.admins||[];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Roles & Permissions</h1><p class="dashboard-subtitle">Manage admin roles and access control</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showAddRoleModal2()">+ Add Role</button></div></div>
        <div class="plans-grid">${roles.map(r => {
            const perms = JSON.parse(r.permissions||'[]');
            const isSuperAdmin = r.name.toLowerCase() === 'super admin';
            const permCount = perms.includes('all') ? 'Full Access' : perms.length + ' permissions';
            return `<div class="plan-card"><div class="plan-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div><h3 class="plan-name">${r.name}</h3><span style="font-size:.75rem;color:var(--text-secondary);">${permCount}</span></div>
                ${isSuperAdmin ? '<span class="status-badge status-active" style="font-size:.7rem;">Protected</span>' : ''}
            </div>
                <p style="color:var(--text-secondary);font-size:.875rem;">${r.description||''}</p>
                <ul class="plan-features">${perms.map(p=>`<li>${p}</li>`).join('')}</ul>
                <div style="display:flex;gap:.5rem;margin-top:auto;">
                    <button class="btn btn-outline" style="flex:1;" onclick="editRole2(${r.id},'${r.name.replace(/'/g,"\\\\'")}','${(r.description||'').replace(/'/g,"\\\\'")}','${(r.permissions||'[]').replace(/"/g,"&quot;")}')">Edit</button>
                    ${!isSuperAdmin ? `<button class="btn btn-outline" style="color:var(--red-500);padding:.5rem .75rem;" onclick="deleteRole(${r.id},'${r.name.replace(/'/g,"\\\\'")}')" title="Delete Role">🗑</button>` : ''}
                </div>
            </div>`;
        }).join('')}</div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Admin Team</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Admin</th><th>Email</th><th>Role</th><th>Permissions</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                ${admins.map(a => `<tr>
                    <td><div class="table-user"><div class="patient-avatar" style="${avatarColors.admin}">${getInitials(a.first_name,a.last_name)}</div><div><div class="patient-name">${a.first_name} ${a.last_name}</div></div></div></td>
                    <td>${a.email}</td>
                    <td><select class="search-input" style="width:auto;padding:.25rem .5rem;font-size:.8rem;" onchange="assignRole(${a.user_id},this.value)">
                        ${roles.map(r => `<option value="${r.id}" ${r.id == a.role_level ? 'selected' : ''}>${r.name}</option>`).join('')}
                    </select></td>
                    <td><div style="display:flex;flex-wrap:wrap;gap:.25rem;">${(a.role_permissions||[]).slice(0,3).map(p => `<span style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:.7rem;">${p}</span>`).join('')}${(a.role_permissions||[]).length > 3 ? `<span style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:.7rem;">+${(a.role_permissions||[]).length-3}</span>` : ''}</div></td>
                    <td><span class="status-badge ${a.status==='active'?'status-active':'status-danger'}">${a.status}</span></td>
                    <td><div class="action-btns">
                        <button class="btn btn-sm btn-outline" onclick="viewAdminProfile(${a.user_id})">View</button>
                        <button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="revokeAdmin(${a.user_id},'${a.first_name} ${a.last_name}')">Revoke</button>
                    </div></td>
                </tr>`).join('')}
            </tbody></table></div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function assignRole(userId, roleId) {
    try {
        const r = await apiPost('roles.php', {action:'assign_role', user_id:userId, role_id:parseInt(roleId)});
        if(r.success) showAlert('Role assigned!','success');
        else showAlert(r.error||'Failed','error');
    } catch(e) { showAlert('Error assigning role','error'); }
}

function deleteRole(id, name) {
    showConfirm(`Delete the <strong>${name}</strong> role? Admins with this role will lose their permissions.`, async()=>{
        try{const r=await apiPost('roles.php',{action:'delete_role',role_id:id});if(r.success){showAlert('Role deleted!','success');setTimeout(()=>{closeModal();showAdminView('roles');},1000);}else showAlert(r.error||'Failed','error');}catch(e){showAlert('Error','error');}
    },'error');
}

function showAddRoleModal2() {
    const allPerms = ['overview','users','clinics','subscriptions','points','reports','notifications','moderation','revenue','system_health','roles','tickets','banners'];
    showModal('Add New Role', `
        <div class="form-group"><label>Role Name <span style="color:var(--red-500);">*</span></label><input type="text" id="ar2-name" placeholder="e.g. Content Manager"></div>
        <div class="form-group"><label>Description</label><textarea id="ar2-desc" rows="2" placeholder="Brief role description"></textarea></div>
        <div class="form-group"><label>Permissions</label><div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">${allPerms.map(p=>`<label style="display:flex;gap:.5rem;align-items:center;font-size:.875rem;"><input type="checkbox" class="ar2-perm" value="${p}"> ${p}</label>`).join('')}</div></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ar2-save">Create Role</button>`);
    document.getElementById('ar2-save').onclick = async () => {
        const perms = [...document.querySelectorAll('.ar2-perm:checked')].map(c=>c.value);
        const d = {action:'add_role',name:document.getElementById('ar2-name').value,description:document.getElementById('ar2-desc').value,permissions:perms};
        if(!d.name){showAlert('Name required','warning');return;}
        try{const r=await apiPost('roles.php',d);if(r.success){showAlert('Role created!','success');setTimeout(()=>{closeModal();showAdminView('roles');},1000);}else showAlert(r.error||'Failed','error');}catch(e){showAlert('Error','error');}
    };
}

function editRole2(id, name, desc, permsJson) {
    const allPerms = ['overview','users','clinics','subscriptions','points','reports','notifications','moderation','revenue','system_health','roles','tickets','banners'];
    const current = JSON.parse(permsJson||'[]');
    showModal('Edit Role', `
        <div class="form-group"><label>Role Name</label><input type="text" id="er2-name" value="${name}"></div>
        <div class="form-group"><label>Description</label><textarea id="er2-desc" rows="2">${desc}</textarea></div>
        <div class="form-group"><label>Permissions</label><div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">${allPerms.map(p=>`<label style="display:flex;gap:.5rem;align-items:center;font-size:.875rem;"><input type="checkbox" class="er2-perm" value="${p}" ${current.includes(p)||current.includes('all')?'checked':''}> ${p}</label>`).join('')}</div></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="er2-save">Save</button>`);
    document.getElementById('er2-save').onclick = async () => {
        const perms = [...document.querySelectorAll('.er2-perm:checked')].map(c=>c.value);
        try{const r=await apiPost('roles.php',{action:'update_role',role_id:id,name:document.getElementById('er2-name').value,description:document.getElementById('er2-desc').value,permissions:perms});if(r.success){showAlert('Updated!','success');setTimeout(()=>{closeModal();showAdminView('roles');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
    };
}

async function viewAdminProfile(id) {
    try {
        const d = await apiGet('roles.php?action=view&id='+id);
        const a = d.admin, audit = d.audit||[];
        showModal(`Admin — ${a.first_name} ${a.last_name}`, `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${a.first_name} ${a.last_name}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${a.email}</span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">${a.status}</span></div>
                <div class="detail-row"><span class="detail-label">Joined</span><span class="detail-value">${fmtDate(a.created_at)}</span></div>
            </div>
            <h4 style="margin:1rem 0 .5rem;">Recent Actions</h4>
            <div style="max-height:200px;overflow-y:auto;">${audit.map(l=>`<div class="activity-item"><div class="activity-dot ${getActivityDotColor(l.activity_type)}"></div><div class="activity-info"><div class="activity-text">${l.description}</div><div class="activity-time">${timeAgo(l.created_at)}</div></div></div>`).join('')}${audit.length===0?'<p style="color:var(--text-secondary);">No actions recorded</p>':''}</div>
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch(e){showAlert('Error','error');}
}

function revokeAdmin(userId,name) { showConfirm(`Revoke admin access for <strong>${name}</strong>?`,async()=>{try{const r=await apiPost('roles.php',{action:'revoke_access',user_id:userId});if(r.success){showAlert('Access revoked!','success');setTimeout(()=>{closeModal();showAdminView('roles');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}},'error'); }
