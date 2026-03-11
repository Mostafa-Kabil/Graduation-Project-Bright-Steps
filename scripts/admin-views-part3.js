// Revenue view has been consolidated into loadSubscriptionsView in admin-dashboard.js

// ═══ SYSTEM HEALTH ═══
async function loadSystemHealthView(main) {
    try {
        const [md, ld] = await Promise.all([apiGet('system_health.php?action=metrics'), apiGet('system_health.php?action=logs')]);
        const m = md.metrics, logs = ld.logs||[];
        const statusColor = m.uptime_status==='healthy'?'var(--green-500)':m.uptime_status==='degraded'?'var(--yellow-500)':'var(--red-500)';
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">System Health & Logs</h1><p class="dashboard-subtitle">Monitor system performance and error logs</p></div>
            <div class="header-actions-inline"><button class="btn btn-outline" onclick="downloadLogs('csv')">↓ CSV</button><button class="btn btn-outline" onclick="downloadLogs('txt')">↓ TXT</button></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="admin-stat-card" style="border-left:4px solid ${statusColor};"><div class="admin-stat-info"><div class="admin-stat-value" style="color:${statusColor};">${m.uptime_status.toUpperCase()}</div><div class="admin-stat-label">System Status (${m.uptime_percent}% uptime)</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">${m.active_sessions}</div><div class="admin-stat-label">Active Sessions</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${m.avg_response_ms}ms</div><div class="admin-stat-label">Avg Response Time</div></div></div>
            <div class="admin-stat-card admin-stat-indigo" style="border-left:4px solid var(--red-500);"><div class="admin-stat-info"><div class="admin-stat-value">${m.errors_24h}</div><div class="admin-stat-label">Errors (24h)</div></div></div>
        </div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Recent Logs</h2>
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
            return `<div class="plan-card"><div class="plan-header"><h3 class="plan-name">${r.name}</h3></div>
                <p style="color:var(--text-secondary);font-size:.875rem;">${r.description||''}</p>
                <ul class="plan-features">${perms.map(p=>`<li>${p}</li>`).join('')}</ul>
                <div style="display:flex;gap:.5rem;margin-top:auto;"><button class="btn btn-outline" style="flex:1;" onclick="editRole2(${r.id},'${r.name.replace(/'/g,"\\'")}','${(r.description||'').replace(/'/g,"\\'")}','${r.permissions}')">Edit</button></div>
            </div>`;
        }).join('')}</div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Admin Team</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Admin</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                ${admins.map(a => `<tr>
                    <td><div class="table-user"><div class="patient-avatar" style="${avatarColors.admin}">${getInitials(a.first_name,a.last_name)}</div><div><div class="patient-name">${a.first_name} ${a.last_name}</div></div></div></td>
                    <td>${a.email}</td><td><span class="role-badge role-admin">${a.role_name||'Admin'}</span></td>
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

function showAddRoleModal2() {
    const allPerms = ['overview','users','clinics','subscriptions','points','reports','marketing','notifications','moderation','revenue','system_health','roles','tickets','banners'];
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
    const allPerms = ['overview','users','clinics','subscriptions','points','reports','marketing','notifications','moderation','revenue','system_health','roles','tickets','banners'];
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
