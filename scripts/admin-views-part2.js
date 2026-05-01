// ═══ NOTIFICATIONS MANAGEMENT (Modernized) ═══
async function loadNotificationsView(main) {
    try {
        const data = await apiGet('notifications_mgmt.php?action=list');
        const notifs = data.notifications || [];
        const sent = notifs.filter(n => n.status==='sent').length;
        const scheduled = notifs.filter(n => n.status==='scheduled').length;
        const failed = notifs.filter(n => n.status==='failed').length;
        const totalRecipients = notifs.reduce((s,n) => s + (parseInt(n.recipient_count)||0), 0);
        main.innerHTML = `<div class="dashboard-content">
        <!-- Hero Header -->
        <div style="background:linear-gradient(135deg,#10b981,#059669,#047857);border-radius:20px;padding:2rem 2.5rem;color:white;margin-bottom:1.5rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:20px;font-size:80px;opacity:.12;">🔔</div>
            <div style="position:absolute;bottom:-30px;right:80px;width:100px;height:100px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 .25rem;color:white !important;">Notification Center</h1>
                    <p style="opacity:.85;margin:0;font-size:.95rem;color:white !important;">Compose, schedule & track notifications — in-app and email</p>
                </div>
                <button class="btn" onclick="showComposeNotification()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);font-size:.85rem;padding:.6rem 1.5rem;border-radius:12px;cursor:pointer;font-weight:600;">+ Compose</button>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03));border:1px solid rgba(16,185,129,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-3px)'" onmouseleave="this.style.transform=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;font-size:1rem;">📨</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);">${notifs.length}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Total Notifications</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(99,102,241,0.03));border:1px solid rgba(99,102,241,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-3px)'" onmouseleave="this.style.transform=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;">✅</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);">${sent}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Sent Successfully</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));border:1px solid rgba(245,158,11,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-3px)'" onmouseleave="this.style.transform=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:1rem;">⏰</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);">${scheduled}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Scheduled</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(236,72,153,0.1),rgba(236,72,153,0.03));border:1px solid rgba(236,72,153,0.15);border-radius:16px;padding:1.25rem;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-3px)'" onmouseleave="this.style.transform=''">
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;"><div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;font-size:1rem;">👥</div></div>
                <div style="font-size:1.75rem;font-weight:800;color:var(--text-primary);">${fmtNum(totalRecipients)}</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.2rem;">Total Recipients</div>
            </div>
        </div>

        <!-- Notifications Table -->
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Sent Notifications</h2><span style="font-size:.7rem;background:var(--bg-secondary);padding:4px 10px;border-radius:6px;color:var(--text-secondary);">${notifs.length} total</span></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Recipients</th><th>Status</th><th>Sent</th><th>Actions</th></tr></thead><tbody>
                ${notifs.map(n => `<tr style="transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <td><strong>${n.title}</strong>${n.first_name ? `<div style="font-size:.65rem;color:var(--text-secondary);margin-top:.15rem;">by ${n.first_name} ${n.last_name}</div>` : ''}</td>
                    <td><span style="font-size:.7rem;padding:3px 8px;border-radius:6px;background:${n.type==='email'?'rgba(99,102,241,0.1)':n.type==='both'?'rgba(236,72,153,0.1)':'rgba(16,185,129,0.1)'};color:${n.type==='email'?'var(--indigo-500)':n.type==='both'?'#ec4899':'var(--green-500)'};">${n.type==='in_app'?'📱 In-App':n.type==='email'?'📧 Email':'📱📧 Both'}</span></td>
                    <td><span style="font-size:.7rem;padding:3px 8px;border-radius:6px;background:${n.priority==='urgent'?'rgba(239,68,68,0.1)':n.priority==='high'?'rgba(245,158,11,0.1)':'rgba(148,163,184,0.1)'};color:${n.priority==='urgent'?'var(--red-500)':n.priority==='high'?'var(--yellow-500)':'var(--text-secondary)'};">${n.priority}</span></td>
                    <td><strong>${n.recipient_count||0}</strong></td>
                    <td><span class="status-badge ${n.status==='sent'?'status-active':n.status==='scheduled'?'status-warning':n.status==='cancelled'?'status-danger':'status-default'}">${n.status}</span></td>
                    <td>${n.sent_at ? fmtDate(n.sent_at) : (n.scheduled_at ? '⏰ '+fmtDate(n.scheduled_at) : '—')}</td>
                    <td><div class="action-btns">
                        <button class="btn btn-sm btn-outline" onclick="viewNotification(${n.id})">View</button>
                        ${n.status==='scheduled'?`<button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="cancelNotification(${n.id})">Cancel</button>`:''}
                        ${n.status==='failed'?`<button class="btn btn-sm btn-outline" onclick="resendNotification(${n.id})">Resend</button>`:''}
                    </div></td>
                </tr>`).join('')}
                ${notifs.length===0?'<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);"><div style="font-size:2rem;margin-bottom:.5rem;">🔔</div>No notifications sent yet</td></tr>':''}
            </tbody></table></div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function showComposeNotification() {
    showModal('Compose Notification', `
        <div class="form-group"><label>Title <span style="color:var(--red-500);">*</span></label><input type="text" id="cn-title" placeholder="Notification title"></div>
        <div class="form-group"><label>Message <span style="color:var(--red-500);">*</span></label><textarea id="cn-body" rows="4" placeholder="Write your notification message..."></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Type</label><select id="cn-type"><option value="in_app">In-App</option><option value="email">Email</option><option value="both">Both</option></select></div>
            <div class="form-group"><label>Priority</label><select id="cn-priority"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
        </div>
        <div class="form-group"><label>Target</label><select id="cn-target"><option value="all">All Users</option><option value="segment">By Role</option></select></div>
        <div class="form-group" id="cn-segment-wrap" style="display:none;"><label>Role</label><select id="cn-segment-role"><option value="parent">Parents</option><option value="specialist">Specialists</option><option value="admin">Admins</option></select></div>
        <div class="form-group"><label>Schedule (leave empty for immediate)</label><input type="datetime-local" id="cn-schedule"></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="cn-send">Send Notification</button>`);
    document.getElementById('cn-target').onchange = function() { document.getElementById('cn-segment-wrap').style.display = this.value==='segment'?'block':'none'; };
    document.getElementById('cn-send').onclick = async () => {
        const d = { action:'compose', title:document.getElementById('cn-title').value, body:document.getElementById('cn-body').value, type:document.getElementById('cn-type').value, priority:document.getElementById('cn-priority').value, target_type:document.getElementById('cn-target').value, scheduled_at:document.getElementById('cn-schedule').value||null };
        if (d.target_type==='segment') d.target_filter = {role:document.getElementById('cn-segment-role').value};
        if (!d.title||!d.body) { showAlert('Title and message required','warning'); return; }
        try { const r = await apiPost('notifications_mgmt.php',d); if(r.success){let msg=`Notification sent to ${r.recipients} users!`;if(r.emails_sent>0)msg+=` ${r.emails_sent} email(s) delivered.`;if(r.emails_failed>0)msg+=` ${r.emails_failed} email(s) failed.`;showAlert(msg,'success');setTimeout(()=>{closeModal();showAdminView('notifications_mgmt');},1500);}else showAlert(r.error||'Failed','error'); } catch(e){showAlert('Error: '+e.message,'error');}
    };
}

async function viewNotification(id) {
    try {
        const d = await apiGet('notifications_mgmt.php?action=view&id='+id);
        const n = d.notification, recips = d.recipients||[];
        const readCount = recips.filter(r => r.read_at).length;
        showModal(`📬 ${n.title}`, `
            <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;padding:1.25rem;color:white;margin-bottom:1.25rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:.7rem;text-transform:uppercase;opacity:.8;letter-spacing:.5px;margin-bottom:.25rem;">Notification Details</div>
                        <div style="font-weight:700;font-size:1.1rem;">${n.title}</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:800;">${d.open_rate}%</div>
                        <div style="font-size:.65rem;opacity:.8;">Open Rate</div>
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem;">
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.75rem;text-align:center;">
                    <div style="font-size:.65rem;color:var(--text-secondary);margin-bottom:.25rem;">Type</div>
                    <div style="font-weight:600;font-size:.85rem;">${n.type==='in_app'?'📱 In-App':n.type==='email'?'📧 Email':'📱📧 Both'}</div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.75rem;text-align:center;">
                    <div style="font-size:.65rem;color:var(--text-secondary);margin-bottom:.25rem;">Priority</div>
                    <div style="font-weight:600;font-size:.85rem;color:${n.priority==='urgent'?'var(--red-500)':n.priority==='high'?'var(--yellow-500)':'var(--text-primary)'};">${n.priority.toUpperCase()}</div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.75rem;text-align:center;">
                    <div style="font-size:.65rem;color:var(--text-secondary);margin-bottom:.25rem;">Recipients</div>
                    <div style="font-weight:600;font-size:.85rem;">${n.recipient_count} users</div>
                </div>
            </div>
            <div style="background:var(--bg-secondary);border-radius:10px;padding:1rem;margin-bottom:1rem;">
                <div style="font-size:.75rem;color:var(--text-secondary);margin-bottom:.35rem;font-weight:600;">Message</div>
                <p style="margin:0;font-size:.9rem;line-height:1.5;color:var(--text-primary);">${n.body}</p>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                <h4 style="margin:0;font-size:.85rem;">Recipients (${recips.length})</h4>
                <span style="font-size:.7rem;color:var(--text-secondary);">${readCount} read • ${recips.length - readCount} unread</span>
            </div>
            <div style="max-height:180px;overflow-y:auto;">
                ${recips.map(r=>`<div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem .5rem;border-bottom:1px solid var(--border);transition:background .15s;" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                    <div><span style="font-size:.85rem;font-weight:500;">${r.first_name} ${r.last_name}</span><span style="font-size:.7rem;color:var(--text-secondary);margin-left:.5rem;">${r.email}</span></div>
                    <span style="font-size:.7rem;padding:2px 8px;border-radius:4px;background:${r.read_at?'rgba(16,185,129,0.1)':'rgba(148,163,184,0.1)'};color:${r.read_at?'var(--green-500)':'var(--text-secondary)'};">${r.read_at?'✓ Read':'Unread'}</span>
                </div>`).join('')}
            </div>
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch(e){showAlert('Error loading notification','error');}
}

function cancelNotification(id) { showConfirm('Cancel this scheduled notification?', async()=>{ try{const r=await apiPost('notifications_mgmt.php',{action:'cancel',notification_id:id});if(r.success){showAlert('Cancelled!','success');setTimeout(()=>{closeModal();showAdminView('notifications_mgmt');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}});}

function resendNotification(id) { showConfirm('Resend this notification?', async()=>{ try{const r=await apiPost('notifications_mgmt.php',{action:'resend',notification_id:id});if(r.success){showAlert('Resent!','success');setTimeout(()=>{closeModal();showAdminView('notifications_mgmt');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}});}

// ═══ CONTENT MODERATION ═══
async function loadModerationView(main) {
    try {
        const [sd, ld, logD] = await Promise.all([apiGet('moderation.php?action=stats'), apiGet('moderation.php?action=list'), apiGet('moderation.php?action=log')]);
        const stats = sd.stats, items = ld.items||[], logs = logD.log||[];
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Content Moderation</h1><p class="dashboard-subtitle">Review flagged content and manage violations</p></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${stats.pending}</div><div class="admin-stat-label">Pending Review</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-info"><div class="admin-stat-value">${stats.reviewed}</div><div class="admin-stat-label">Reviewed</div></div></div>
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">${stats.removed}</div><div class="admin-stat-label">Removed</div></div></div>
        </div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Flagged Content</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Content</th><th>User</th><th>Reason</th><th>Flagged By</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                ${items.map(i => `<tr>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;">${(i.content_text||'').substring(0,60)}...</td>
                    <td>${i.first_name||''} ${i.last_name||''}</td>
                    <td>${i.reason||'—'}</td><td>${i.flagged_by}</td>
                    <td><span class="status-badge ${i.status==='pending'?'status-warning':i.status==='removed'?'status-danger':'status-active'}">${i.status}</span></td>
                    <td><div class="action-btns">
                        <button class="btn btn-sm btn-outline" onclick="viewFlaggedContent(${i.id})">View</button>
                        ${i.status==='pending'?`<button class="btn btn-sm btn-outline" style="color:var(--green-500);" onclick="moderateContent(${i.id},'approved')">Approve</button><button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="moderateContent(${i.id},'removed')">Remove</button>`:''}
                    </div></td>
                </tr>`).join('')}
                ${items.length===0?'<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">No flagged content</td></tr>':''}
            </tbody></table></div></div>
        <div class="section-card" style="margin-top:1.5rem;"><div class="section-card-header"><h2 class="section-heading">Moderation Log</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Admin</th><th>Action</th><th>Note</th><th>Date</th></tr></thead><tbody>
                ${logs.map(l=>`<tr><td>${l.admin_name||''} ${l.admin_last||''}</td><td><span class="status-badge ${l.action==='removed'||l.action==='ban'?'status-danger':'status-active'}">${l.action}</span></td><td>${l.note||'—'}</td><td>${fmtDate(l.created_at)}</td></tr>`).join('')}
                ${logs.length===0?'<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-secondary);">No moderation actions yet</td></tr>':''}
            </tbody></table></div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function viewFlaggedContent(id) {
    try {
        const d = await apiGet('moderation.php?action=view&id='+id);
        const i = d.item;
        showModal('Review Flagged Content', `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${i.content_type}</span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${i.status==='pending'?'status-warning':'status-active'}">${i.status}</span></span></div>
                <div class="detail-row"><span class="detail-label">Posted By</span><span class="detail-value">${i.first_name||''} ${i.last_name||''} (${i.email||''})</span></div>
                <div class="detail-row"><span class="detail-label">User Status</span><span class="detail-value">${i.user_status||'—'}</span></div>
                <div class="detail-row"><span class="detail-label">Reason</span><span class="detail-value">${i.reason||'—'}</span></div>
                <div class="detail-row"><span class="detail-label">Previous Violations</span><span class="detail-value">${i.previous_violations}</span></div>
            </div>
            <div style="background:var(--bg-secondary);border-radius:8px;padding:1rem;"><strong>Content:</strong><p style="margin:.5rem 0 0;white-space:pre-wrap;">${i.content_text||'No text'}</p></div>
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>
            ${i.status==='pending'?`<button class="btn btn-gradient" style="background:var(--green-500);" onclick="moderateContent(${id},'approved')">Approve</button>
            <button class="btn btn-gradient" style="background:var(--red-500);" onclick="moderateContent(${id},'removed')">Remove</button>
            <button class="btn btn-gradient" style="background:var(--yellow-500);" onclick="moderateContent(${id},'warned')">Warn User</button>`:''}`);
    } catch(e){showAlert('Error','error');}
}

function moderateContent(id, action) {
    showConfirm(`Are you sure you want to <strong>${action}</strong> this content?`, async()=>{
        try{const r=await apiPost('moderation.php',{action:'moderate',content_id:id,mod_action:action});if(r.success){showAlert('Action completed!','success');setTimeout(()=>{closeModal();showAdminView('moderation');},1000);}else showAlert(r.error||'Failed','error');}catch(e){showAlert('Error','error');}
    }, action==='removed'?'error':'warning');
}
