// ═══ SUPPORT TICKETS ═══
async function loadTicketsView(main) {
    try {
        const [td, ad] = await Promise.all([apiGet('tickets.php?action=list'), apiGet('tickets.php?action=analytics')]);
        const tickets = td.tickets||[], an = ad.analytics||{};
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Support Tickets</h1><p class="dashboard-subtitle">Manage user support requests</p></div></div>
        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="admin-stat-card admin-stat-indigo"><div class="admin-stat-info"><div class="admin-stat-value">${an.total||0}</div><div class="admin-stat-label">Total Tickets</div></div></div>
            <div class="admin-stat-card admin-stat-amber"><div class="admin-stat-info"><div class="admin-stat-value">${an.open||0}</div><div class="admin-stat-label">Open</div></div></div>
            <div class="admin-stat-card admin-stat-emerald"><div class="admin-stat-info"><div class="admin-stat-value">${an.resolution_rate||0}%</div><div class="admin-stat-label">Resolution Rate</div></div></div>
            <div class="admin-stat-card admin-stat-teal"><div class="admin-stat-info"><div class="admin-stat-value">${an.avg_response_hours||0}h</div><div class="admin-stat-label">Avg Response</div></div></div>
        </div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">All Tickets</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Subject</th><th>User</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
                ${tickets.map(t => `<tr>
                    <td><strong>${t.subject}</strong></td>
                    <td>${t.first_name} ${t.last_name}</td>
                    <td><span class="status-badge ${t.priority==='critical'?'status-danger':t.priority==='high'?'status-warning':'status-default'}">${t.priority}</span></td>
                    <td><span class="status-badge ${t.status==='open'?'status-warning':t.status==='resolved'||t.status==='closed'?'status-active':'status-default'}">${t.status}</span></td>
                    <td>${t.assigned_first?t.assigned_first+' '+t.assigned_last:'Unassigned'}</td>
                    <td>${timeAgo(t.updated_at)}</td>
                    <td><button class="btn btn-sm btn-outline" onclick="viewTicket(${t.id})">View</button></td>
                </tr>`).join('')}
                ${tickets.length===0?'<tr><td colspan="7" style="text-align:center;padding:2rem;">No tickets</td></tr>':''}
            </tbody></table></div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

async function viewTicket(id) {
    try {
        const d = await apiGet('tickets.php?action=view&id='+id);
        const t = d.ticket, msgs = d.messages||[], prev = d.previous_tickets||[], admins = d.admins||[];
        showModal(`Ticket #${id} — ${t.subject}`, `
            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <div style="background:var(--bg-secondary);padding:1rem;border-radius:12px;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <span style="font-size:.75rem;color:var(--text-secondary);display:block;margin-bottom:.25rem;">User Info</span>
                        <strong>${t.first_name} ${t.last_name}</strong><br>
                        <span style="font-size:.85rem;">${t.email}</span><br>
                        <span class="role-badge role-${t.role}" style="margin-top:.5rem;display:inline-block;">${t.role}</span>
                    </div>
                    <div>
                        <span style="font-size:.75rem;color:var(--text-secondary);display:block;margin-bottom:.25rem;">Ticket Actions</span>
                        <div style="display:flex;flex-direction:column;gap:.5rem;">
                            <select class="search-input" style="width:100%;padding:.3rem .5rem;font-size:.8rem;" onchange="updateTicketStatus(${id},this.value)">
                                ${['open','in_progress','waiting','resolved','closed'].map(s=>`<option value="${s}" ${t.status===s?'selected':''}>${s.replace('_',' ')}</option>`).join('')}
                            </select>
                            <select class="search-input" style="width:100%;padding:.3rem .5rem;font-size:.8rem;" onchange="updateTicketPriority(${id},this.value)">
                                ${['low','medium','high','critical'].map(p=>`<option value="${p}" ${t.priority===p?'selected':''}>${p.toUpperCase()}</option>`).join('')}
                            </select>
                            <select class="search-input" style="width:100%;padding:.3rem .5rem;font-size:.8rem;" onchange="assignTicket(${id},this.value)">
                                <option value="">Assign to...</option>
                                ${admins.map(a=>`<option value="${a.user_id}" ${t.assigned_to==a.user_id?'selected':''}>${a.first_name} ${a.last_name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
                        <h4 style="margin:0;">Messages</h4>
                        ${prev.length > 0 ? `<span style="font-size:.75rem;color:var(--text-secondary);">${prev.length} previous tickets</span>` : ''}
                    </div>
                    <div class="ticket-chat" style="max-height:280px;overflow-y:auto;padding-right:.5rem;margin-bottom:1rem;">
                        ${msgs.map(m => `<div class="chat-msg ${m.sender_type}" style="margin-bottom:.75rem;">
                            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-secondary);margin-bottom:.25rem;">
                                <strong>${m.first_name} ${m.last_name}</strong><span>${timeAgo(m.created_at)}</span>
                            </div>
                            <div class="chat-bubble" style="background:${m.sender_type==='admin'?'linear-gradient(135deg,#6366f1,#8b5cf6)':'var(--bg-card)'};color:${m.sender_type==='admin'?'white':'var(--text-primary)'};padding:.75rem 1rem;border-radius:12px;border:${m.sender_type==='admin'?'none':'1px solid var(--border)'}">
                                ${m.message}${m.is_internal?'<br><em style="font-size:.75rem;opacity:.8;display:block;margin-top:.25rem;">(Internal note)</em>':''}
                            </div>
                        </div>`).join('')}
                        ${msgs.length===0?'<p style="text-align:center;color:var(--text-secondary);padding:1rem;">No conversation history</p>':''}
                    </div>
                    <div style="display:flex;gap:.5rem;align-items:flex-end;">
                        <div style="flex:1;">
                            <textarea id="tr-msg" rows="2" class="search-input" style="width:100%;font-size:.875rem;" placeholder="Type your reply..."></textarea>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.25rem;flex-shrink:0;">
                            <button class="btn btn-gradient btn-sm" onclick="replyTicket(${id},false)">Send Reply</button>
                            <button class="btn btn-outline btn-sm" style="font-size:.7rem;" onclick="replyTicket(${id},true)">+ Internal Note</button>
                        </div>
                    </div>
                </div>
            </div>
        `, `<button class="btn btn-outline" onclick="closeModal()">Close Window</button>`, 'admin-modal-wide');
    } catch(e){showAlert('Error loading ticket','error');}
}

async function replyTicket(id, isInternal) {
    const msg = document.getElementById('tr-msg')?.value;
    if(!msg){showAlert('Message required','warning');return;}
    try{const r=await apiPost('tickets.php',{action:'reply',ticket_id:id,message:msg,is_internal:isInternal?1:0});if(r.success){showAlert('Reply sent!','success');viewTicket(id);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
}
async function updateTicketStatus(id,status) { try{await apiPost('tickets.php',{action:'update_status',ticket_id:id,status});}catch(e){} }
async function updateTicketPriority(id,priority) { try{await apiPost('tickets.php',{action:'update_priority',ticket_id:id,priority});}catch(e){} }
async function assignTicket(id,adminId) { if(!adminId)return; try{await apiPost('tickets.php',{action:'assign',ticket_id:id,assign_to:adminId});showAlert('Assigned!','success');}catch(e){} }

// ═══ ANNOUNCEMENT BANNERS ═══
async function loadBannersView(main) {
    try {
        const data = await apiGet('banners.php?action=list');
        const banners = data.banners||[];
        const styleColors = {info:'#6366f1',warning:'#f59e0b',success:'#10b981',error:'#ef4444'};
        main.innerHTML = `<div class="dashboard-content">
        <div class="dashboard-header-section"><div><h1 class="dashboard-title">Announcement Banners</h1><p class="dashboard-subtitle">Manage site-wide announcements</p></div>
            <div class="header-actions-inline"><button class="btn btn-gradient" onclick="showAddBannerModal()">+ Add Banner</button></div></div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">All Banners</h2></div>
            <div class="clinic-table-wrap"><table class="clinic-table"><thead><tr><th>Preview</th><th>Style</th><th>Audience</th><th>Active</th><th>Created</th><th>Actions</th></tr></thead><tbody>
                ${banners.map(b => `<tr>
                    <td><div style="background:${styleColors[b.style]||'#6366f1'};color:white;padding:.5rem 1rem;border-radius:8px;font-size:.8rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${b.message}</div></td>
                    <td><span class="status-badge" style="background:${styleColors[b.style]}22;color:${styleColors[b.style]};">${b.style}</span></td>
                    <td>${b.target_audience}</td>
                    <td><label class="toggle-switch" style="transform:scale(.8);"><input type="checkbox" ${b.is_active?'checked':''} onchange="toggleBanner(${b.id})"><span class="toggle-slider"></span></label></td>
                    <td>${fmtDate(b.created_at)}</td>
                    <td><div class="action-btns">
                        <button class="btn btn-sm btn-outline" onclick="viewBanner(${b.id})">View</button>
                        <button class="btn btn-sm btn-outline" onclick="editBanner(${b.id},'${(b.message||'').replace(/'/g,"\\'")}','${b.style}','${b.link||''}','${b.target_audience}','${b.starts_at||''}','${b.ends_at||''}')">Edit</button>
                        <button class="btn btn-sm btn-outline" style="color:var(--red-500);" onclick="deleteBanner(${b.id})">Delete</button>
                    </div></td>
                </tr>`).join('')}
                ${banners.length===0?'<tr><td colspan="6" style="text-align:center;padding:2rem;">No banners</td></tr>':''}
            </tbody></table></div></div></div>`;
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch(e) { main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`; }
}

function showAddBannerModal() {
    showModal('Add Announcement Banner', `
        <div class="form-group"><label>Message <span style="color:var(--red-500);">*</span></label><textarea id="ab-msg" rows="3" placeholder="Banner message..." oninput="updateBannerPreview()"></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Style</label><select id="ab-style" onchange="updateBannerPreview()"><option value="info">Info</option><option value="warning">Warning</option><option value="success">Success</option><option value="error">Error</option></select></div>
            <div class="form-group"><label>Audience</label><select id="ab-target"><option value="all">All Users</option><option value="parents">Parents</option><option value="specialists">Specialists</option><option value="admins">Admins</option></select></div>
        </div>
        <div class="form-group"><label>Link (optional)</label><input type="url" id="ab-link" placeholder="https://..."></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Start Date</label><input type="datetime-local" id="ab-start"></div>
            <div class="form-group"><label>End Date</label><input type="datetime-local" id="ab-end"></div>
        </div>
        <div class="form-group"><label>Live Preview</label><div id="ab-preview" style="background:#6366f1;color:white;padding:.75rem 1rem;border-radius:8px;text-align:center;">Your banner message here</div></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="ab-save">Create Banner</button>`);
    document.getElementById('ab-save').onclick = async () => {
        const d = {action:'create',message:document.getElementById('ab-msg').value,style:document.getElementById('ab-style').value,link:document.getElementById('ab-link').value||null,target_audience:document.getElementById('ab-target').value,starts_at:document.getElementById('ab-start').value||null,ends_at:document.getElementById('ab-end').value||null};
        if(!d.message){showAlert('Message required','warning');return;}
        try{const r=await apiPost('banners.php',d);if(r.success){showAlert('Banner created!','success');setTimeout(()=>{closeModal();showAdminView('banners');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
    };
}

function updateBannerPreview() {
    const msg = document.getElementById('ab-msg')?.value || 'Your banner message here';
    const style = document.getElementById('ab-style')?.value || 'info';
    const colors = {info:'#6366f1',warning:'#f59e0b',success:'#10b981',error:'#ef4444'};
    const prev = document.getElementById('ab-preview');
    if(prev) { prev.style.background = colors[style]; prev.textContent = msg; }
}

function editBanner(id,msg,style,link,target,start,end) {
    showModal('Edit Banner', `
        <div class="form-group"><label>Message</label><textarea id="eb-msg" rows="3">${msg}</textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Style</label><select id="eb-style"><option value="info" ${style==='info'?'selected':''}>Info</option><option value="warning" ${style==='warning'?'selected':''}>Warning</option><option value="success" ${style==='success'?'selected':''}>Success</option><option value="error" ${style==='error'?'selected':''}>Error</option></select></div>
            <div class="form-group"><label>Audience</label><select id="eb-target"><option value="all" ${target==='all'?'selected':''}>All</option><option value="parents" ${target==='parents'?'selected':''}>Parents</option><option value="specialists" ${target==='specialists'?'selected':''}>Specialists</option><option value="admins" ${target==='admins'?'selected':''}>Admins</option></select></div>
        </div>
        <div class="form-group"><label>Link</label><input type="url" id="eb-link" value="${link}"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Start</label><input type="datetime-local" id="eb-start" value="${start}"></div>
            <div class="form-group"><label>End</label><input type="datetime-local" id="eb-end" value="${end}"></div>
        </div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="eb-save">Save</button>`);
    document.getElementById('eb-save').onclick = async () => {
        const d = {action:'update',banner_id:id,message:document.getElementById('eb-msg').value,style:document.getElementById('eb-style').value,link:document.getElementById('eb-link').value||null,target_audience:document.getElementById('eb-target').value,starts_at:document.getElementById('eb-start').value||null,ends_at:document.getElementById('eb-end').value||null};
        try{const r=await apiPost('banners.php',d);if(r.success){showAlert('Updated!','success');setTimeout(()=>{closeModal();showAdminView('banners');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}
    };
}

async function viewBanner(id) {
    try {
        const d = await apiGet('banners.php?action=view&id='+id);
        const b = d.banner;
        const colors = {info:'#6366f1',warning:'#f59e0b',success:'#10b981',error:'#ef4444'};
        showModal('View Banner', `
            <div style="background:${colors[b.style]||'#6366f1'};color:white;padding:1rem 1.25rem;border-radius:12px;text-align:center;font-size:1rem;margin-bottom:1.5rem;">${b.message}${b.link?` <a href="${b.link}" style="color:white;text-decoration:underline;">Learn more →</a>`:''}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="detail-row"><span class="detail-label">Style</span><span class="detail-value">${b.style}</span></div>
                <div class="detail-row"><span class="detail-label">Audience</span><span class="detail-value">${b.target_audience}</span></div>
                <div class="detail-row"><span class="detail-label">Active</span><span class="detail-value">${b.is_active?'Yes':'No'}</span></div>
                <div class="detail-row"><span class="detail-label">Created</span><span class="detail-value">${fmtDate(b.created_at)}</span></div>
                <div class="detail-row"><span class="detail-label">Starts</span><span class="detail-value">${b.starts_at?fmtDate(b.starts_at):'Immediately'}</span></div>
                <div class="detail-row"><span class="detail-label">Ends</span><span class="detail-value">${b.ends_at?fmtDate(b.ends_at):'No end date'}</span></div>
            </div>
        `, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch(e){showAlert('Error','error');}
}

async function toggleBanner(id) { try{await apiPost('banners.php',{action:'toggle',banner_id:id});}catch(e){showAlert('Error','error');} }

function deleteBanner(id) { showConfirm('Delete this banner?',async()=>{try{const r=await apiPost('banners.php',{action:'delete',banner_id:id});if(r.success){showAlert('Deleted!','success');setTimeout(()=>{closeModal();showAdminView('banners');},1000);}else showAlert('Failed','error');}catch(e){showAlert('Error','error');}},'error'); }
