// ═══ VIEW USER MODAL ═══
async function viewUser(userId) {
    showModal('Loading...', '<div style="display:flex;justify-content:center;padding:2rem;"><div class="admin-loading-spinner"></div></div>', '');
    try {
        const data = await apiGet(`users.php?action=list`);
        const user = (data.users || []).find(u => u.user_id == userId);
        if (!user) { showAlert('User not found', 'error'); return; }
        showModal(`View User — ${user.first_name} ${user.last_name}`, `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value">${user.first_name || ''} ${user.last_name || ''}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${user.email || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value"><span class="role-badge role-${user.role}">${user.role}</span></span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${user.status === 'active' ? 'status-active' : 'status-danger'}">${user.status}</span></span></div>
                <div class="detail-row"><span class="detail-label">User ID</span><span class="detail-value">#${user.user_id}</span></div>
                <div class="detail-row"><span class="detail-label">Joined</span><span class="detail-value">${fmtDate(user.created_at)}</span></div>
            </div>`, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch (e) { showAlert('Error: ' + e.message, 'error'); }
}

// ═══ VIEW CLINIC MODAL ═══
function viewClinic(id, name, email, location, status, rating, specialists, patients) {
    showModal(`View Clinic — ${name}`, `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="detail-row"><span class="detail-label">Clinic Name</span><span class="detail-value">${name}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${email || '—'}</span></div>
            <div class="detail-row"><span class="detail-label">Location</span><span class="detail-value">${location || '—'}</span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${status === 'verified' ? 'status-active' : 'status-warning'}">${status}</span></span></div>
            <div class="detail-row"><span class="detail-label">Rating</span><span class="detail-value"><span class="rating-badge">★ ${Number(rating).toFixed(1)}</span></span></div>
            <div class="detail-row"><span class="detail-label">Specialists</span><span class="detail-value">${specialists}</span></div>
            <div class="detail-row"><span class="detail-label">Patients</span><span class="detail-value">${patients}</span></div>
            <div class="detail-row"><span class="detail-label">Clinic ID</span><span class="detail-value">#${id}</span></div>
        </div>`, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
}

// ═══ MARKETING ═══
async function loadMarketingView(main) {
    try {
        const data = await apiGet('marketing.php?action=stats');
        if (!data.success) throw new Error('Failed to load');
        const s = data.stats, campaigns = data.campaigns || [], audience = data.audience || [], trend = data.spend_trend || [];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Marketing Analytics</h1><p class="dashboard-subtitle">Meta (Facebook/Instagram) ad performance</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="syncMarketing()">⟳ Sync Now</button></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(5,1fr);">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">$${fmtNum(s.total_spend)}</div><div class="admin-stat-label">Total Spend</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.impressions)}</div><div class="admin-stat-label">Impressions</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-info"><div class="admin-stat-value">${fmtNum(s.clicks)}</div><div class="admin-stat-label">Clicks</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${s.ctr}%</div><div class="admin-stat-label">CTR</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">$${s.cpc}</div><div class="admin-stat-label">CPC</div></div></div>
        </div>
        <div class="overview-grid">
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Spend Over Time</h2></div><div style="padding:1.5rem;"><canvas id="chart-spend-trend" height="200"></canvas></div></div>
            <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Audience Demographics</h2></div><div style="padding:1.5rem;"><canvas id="chart-audience" height="200"></canvas></div></div>
        </div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Campaigns</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Campaign</th><th>Status</th><th>Spend</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>Conversions</th><th>Actions</th></tr></thead><tbody>
                ${campaigns.map(c => `<tr>
                    <td><strong>${c.name}</strong></td>
                    <td><span class="status-badge ${c.status === 'active' ? 'status-active' : c.status === 'paused' ? 'status-warning' : 'status-default'}">${c.status}</span></td>
                    <td>$${fmtNum(c.spend)}</td><td>${fmtNum(c.impressions)}</td><td>${fmtNum(c.clicks)}</td><td>${c.ctr}%</td><td>${c.conversions}</td>
                    <td><button class="btn btn-sm btn-outline" onclick="viewCampaign('${c.id}')">View</button></td>
                </tr>`).join('')}
            </tbody></table></div>
        </div></div>`;
        // Charts
        if (typeof Chart !== 'undefined') {
            new Chart(document.getElementById('chart-spend-trend'), { type:'line', data:{ labels:trend.map(t=>t.date.slice(5)), datasets:[{label:'Daily Spend ($)',data:trend.map(t=>t.spend),borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,0.1)',fill:true,tension:0.4}]}, options:{responsive:true,plugins:{legend:{display:false}}}});
            new Chart(document.getElementById('chart-audience'), { type:'doughnut', data:{ labels:audience.map(a=>a.segment), datasets:[{data:audience.map(a=>a.percentage),backgroundColor:['#6366f1','#0d9488','#f59e0b','#ec4899','#8b5cf6']}]}, options:{responsive:true}});
        }
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}
async function syncMarketing() { try { const r = await apiGet('marketing.php?action=sync'); showAlert(r.message || 'Synced!', 'success'); } catch(e) { showAlert('Sync failed', 'error'); } }
function viewCampaign(id) {
    showModal('Loading Campaign...', '<div style="display:flex;justify-content:center;padding:2rem;"><div class="admin-loading-spinner"></div></div>', '');
    apiGet('marketing.php?action=campaign_detail&campaign_id='+id).then(d => {
        const c = d.campaign;
        showModal(`Campaign — ${c.name}`, `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${c.status==='active'?'status-active':'status-warning'}">${c.status}</span></span></div>
                <div class="detail-row"><span class="detail-label">Spend</span><span class="detail-value">$${fmtNum(c.spend)}</span></div>
                <div class="detail-row"><span class="detail-label">Clicks</span><span class="detail-value">${fmtNum(c.clicks)}</span></div>
                <div class="detail-row"><span class="detail-label">CTR</span><span class="detail-value">${c.ctr}%</span></div>
                <div class="detail-row"><span class="detail-label">Conversions</span><span class="detail-value">${c.conversions}</span></div>
                <div class="detail-row"><span class="detail-label">Start Date</span><span class="detail-value">${fmtDate(c.start_date)}</span></div>
            </div>
            <h4 style="margin:1rem 0 .5rem;">Audience Breakdown</h4>
            ${(c.audience_breakdown||[]).map(a=>`<div class="distribution-row"><div class="dist-label">${a.segment}</div><div class="dist-bar"><div class="dist-fill" style="width:${a.percentage}%;background:#6366f1;"></div></div><div class="dist-value">${a.percentage}%</div></div>`).join('')}
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    }).catch(()=>showAlert('Failed to load campaign','error'));
}
