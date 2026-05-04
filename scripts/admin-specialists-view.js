// ═══ SPECIALIST MANAGEMENT ═══
async function loadSpecialistsView(main) {
    try {
        const [ld] = await Promise.all([
            apiGet('specialists.php?action=list')
        ]);
        renderSpecialistsView(main, ld.specialists || [], '');
    } catch (e) {
        main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`;
    }
}

function renderSpecialistsView(main, specialists, currentSearch) {
    const pending = specialists.filter(s => s.status === 'pending');
    const active = specialists.filter(s => s.status === 'active');
    const rejected = specialists.filter(s => s.status === 'rejected');

    const statusColor = s => s === 'active' ? '#10b981' : s === 'pending' ? '#f59e0b' : s === 'rejected' ? '#6b7280' : '#6366f1';
    const statusBadge = s => {
        const cls = s === 'active' ? 'status-active' : s === 'rejected' ? 'status-danger' : 'status-warning';
        return `<span class="status-badge ${cls}">${(s||'pending').charAt(0).toUpperCase()+(s||'pending').slice(1)}</span>`;
    };

    function specialistCard(s) {
        const esc = str => (str||'').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        return `<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                <div style="display:flex;gap:.75rem;align-items:center;">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,${statusColor(s.status)},${statusColor(s.status)}aa);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:1.2rem;">
                        ${s.first_name ? s.first_name.charAt(0) : ''}${s.last_name ? s.last_name.charAt(0) : ''}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1rem;color:var(--text-primary);">Dr. ${s.first_name} ${s.last_name}</div>
                        <div style="font-size:.8rem;color:var(--text-secondary);">${s.email||''}</div>
                    </div>
                </div>
                ${statusBadge(s.status)}
            </div>
            
            <div style="margin-bottom:0.75rem;">
                <div style="font-size:0.85rem;color:var(--text-secondary);"><strong>Specialization:</strong> ${s.specialization || 'Not specified'}</div>
                <div style="font-size:0.85rem;color:var(--text-secondary);"><strong>Experience:</strong> ${s.experience_years || 0} years</div>
                <div style="font-size:0.85rem;color:var(--text-secondary);"><strong>Clinic:</strong> ${s.clinic_name || 'None'}</div>
                ${s.certificate_of_experience ? `<div style="margin-top:0.5rem;"><a href="../../uploads/certificates/${s.certificate_of_experience}" target="_blank" style="font-size:0.8rem;color:var(--indigo-500);text-decoration:underline;font-weight:600;">📄 View Certification</a></div>` : '<div style="font-size:0.8rem;color:var(--red-500);margin-top:0.5rem;">No certificate uploaded</div>'}
            </div>
            
            <div style="font-size:.7rem;color:var(--text-secondary);margin-bottom:.75rem;">Registered: ${s.created_at ? fmtDate(s.created_at) : '—'}</div>
            
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                ${s.status === 'pending' ? `
                    <button class="btn btn-sm" style="background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;flex:1;border-radius:10px;font-weight:600;padding:.5rem;" onclick="approveSpecialist(${s.specialist_id})">✓ Approve</button>
                    <button class="btn btn-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;flex:1;border-radius:10px;font-weight:600;padding:.5rem;" onclick="rejectSpecialist(${s.specialist_id},'${esc(s.first_name + ' ' + s.last_name)}')">✕ Reject</button>
                ` : ''}
            </div>
        </div>`;
    }

    main.innerHTML = `<div class="dashboard-content">
        <!-- Hero Header -->
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb,#1d4ed8);border-radius:20px;padding:1.5rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:20px;font-size:80px;opacity:.12;">👨‍⚕️</div>
            <div style="position:absolute;bottom:-30px;right:80px;width:100px;height:100px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 .25rem;color:white !important;">Specialist Management</h1>
                    <p style="opacity:.85;margin:0;font-size:.95rem;color:white !important;">Verify specialist credentials and certifications</p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem;">
            <div style="background:linear-gradient(135deg,rgba(59,130,246,0.1),rgba(59,130,246,0.03));border:1px solid rgba(59,130,246,0.15);border-radius:14px;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:var(--blue-500);">${specialists.length}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">👨‍⚕️ Total Specialists</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03));border:1px solid rgba(16,185,129,0.15);border-radius:14px;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:var(--green-500);">${active.length}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">✅ Verified</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));border:1px solid rgba(245,158,11,0.15);border-radius:14px;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:var(--yellow-500);">${pending.length}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">⏳ Pending</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(107,114,128,0.1),rgba(107,114,128,0.03));border:1px solid rgba(107,114,128,0.15);border-radius:14px;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:var(--slate-500);">${rejected.length}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">❌ Rejected</div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;align-items:center;">
            <input type="text" class="search-input" placeholder="Search specialists by name, clinic or email..." id="admin-specialist-search" value="${currentSearch}" style="flex:1;">
            <select class="search-input" id="admin-specialist-status-filter" style="width:auto;min-width:140px;">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="active">Verified (Active)</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <!-- Pending Approval Section -->
        ${pending.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--yellow-500);animation:pulse 2s infinite;"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Pending Verification (${pending.length})</h2>
                <span style="font-size:.75rem;color:var(--yellow-500);background:rgba(245,158,11,0.1);padding:3px 10px;border-radius:20px;font-weight:600;">Action Required</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${pending.map(s => specialistCard(s)).join('')}
            </div>
        </div>` : ''}

        <!-- Active Specialists Section -->
        ${active.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--green-500);"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Verified Specialists (${active.length})</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${active.map(s => specialistCard(s)).join('')}
            </div>
        </div>` : ''}
        
        <!-- Rejected -->
        ${rejected.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--text-secondary);"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Rejected (${rejected.length})</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${rejected.map(s => specialistCard(s)).join('')}
            </div>
        </div>` : ''}

        ${specialists.length === 0 ? '<div style="text-align:center;padding:3rem;color:var(--text-secondary);"><p>No specialists found</p></div>' : ''}
    </div>`;

    // Search handler
    let st;
    document.getElementById('admin-specialist-search').addEventListener('input', function() {
        clearTimeout(st);
        const val = this.value;
        const statusVal = document.getElementById('admin-specialist-status-filter').value;
        st = setTimeout(async () => {
            try {
                const ld = await apiGet('specialists.php?action=list&search=' + encodeURIComponent(val) + (statusVal !== 'all' ? '&status=' + statusVal : ''));
                renderSpecialistsView(main, ld.specialists || [], val);
            } catch(e) {}
        }, 400);
    });
    document.getElementById('admin-specialist-status-filter').addEventListener('change', function() {
        const searchVal = document.getElementById('admin-specialist-search').value;
        const statusVal = this.value;
        (async () => {
            try {
                const ld = await apiGet('specialists.php?action=list&search=' + encodeURIComponent(searchVal) + (statusVal !== 'all' ? '&status=' + statusVal : ''));
                renderSpecialistsView(main, ld.specialists || [], searchVal);
            } catch(e) {}
        })();
    });
}

// Approve specialist
function approveSpecialist(specId) {
    showConfirm('Are you sure you want to <strong>approve</strong> this specialist?', async () => {
        try {
            const res = await apiPost('specialists.php', { action: 'approve', specialist_id: specId });
            if (res.success) { showAlert('Specialist approved!', 'success'); setTimeout(() => { showAdminView('specialists'); }, 1200); }
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

// Reject specialist
function rejectSpecialist(specId, specName) {
    showModal('Reject Specialist Signup', `
        <div style="text-align:center;margin-bottom:1rem;">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:28px;height:28px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <h3 style="margin:0 0 .5rem;font-size:1.1rem;">Reject <strong>Dr. ${specName}</strong>?</h3>
            <p style="color:var(--text-secondary);font-size:.85rem;margin:0;">This specialist will not be able to log in or take appointments.</p>
        </div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn" id="reject-confirm-btn" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;">Reject Signup</button>`);

    document.getElementById('reject-confirm-btn').onclick = async () => {
        try {
            const res = await apiPost('specialists.php', { action: 'reject', specialist_id: specId });
            if (res.success) {
                showAlert('Specialist signup rejected', 'success');
                setTimeout(() => { closeModal(); showAdminView('specialists'); }, 1200);
            } else showAlert(res.error || 'Failed', 'error');
        } catch(e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// Expose all specialist functions globally for the SPA router
window.loadSpecialistsView = loadSpecialistsView;
window.approveSpecialist = approveSpecialist;
window.rejectSpecialist = rejectSpecialist;
