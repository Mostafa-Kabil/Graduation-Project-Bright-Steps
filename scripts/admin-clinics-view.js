// ═══ CLINIC MANAGEMENT (Modernized with Approval Workflow) ═══
async function loadClinicsView(main) {
    try {
        const [sd, ld] = await Promise.all([
            apiGet('clinics.php?action=stats'),
            apiGet('clinics.php?action=list')
        ]);
        renderClinicsView(main, sd.stats, ld.clinics, '');
    } catch (e) {
        main.innerHTML = `<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>${e.message}</p></div>`;
    }
}

function renderClinicsView(main, stats, clinics, currentSearch) {
    const pending = clinics.filter(c => c.status === 'pending');
    const active = clinics.filter(c => c.status === 'verified');
    const suspended = clinics.filter(c => c.status === 'suspended');
    const rejected = clinics.filter(c => c.status === 'rejected');
    const other = clinics.filter(c => !['pending','verified','suspended','rejected'].includes(c.status));

    const statusColor = s => s === 'verified' ? '#10b981' : s === 'pending' ? '#f59e0b' : s === 'suspended' ? '#ef4444' : s === 'rejected' ? '#6b7280' : '#6366f1';
    const statusBadge = s => {
        const cls = s === 'verified' ? 'status-active' : s === 'suspended' || s === 'rejected' ? 'status-danger' : 'status-warning';
        return `<span class="status-badge ${cls}">${(s||'pending').charAt(0).toUpperCase()+(s||'pending').slice(1)}</span>`;
    };

    function clinicCard(c) {
        const esc = str => (str||'').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        return `<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                <div style="display:flex;gap:.75rem;align-items:center;">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,${statusColor(c.status)},${statusColor(c.status)}aa);display:flex;align-items:center;justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:22px;height:22px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1rem;color:var(--text-primary);">${c.clinic_name}</div>
                        <div style="font-size:.8rem;color:var(--text-secondary);">${c.email||''}</div>
                    </div>
                </div>
                ${statusBadge(c.status)}
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:1rem;">
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">${c.specialist_count||0}</div>
                    <div style="font-size:.65rem;color:var(--text-secondary);">Doctors</div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">${c.patient_count||0}</div>
                    <div style="font-size:.65rem;color:var(--text-secondary);">Patients</div>
                </div>
                <div style="background:var(--bg-secondary);border-radius:10px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">★ ${Number(c.rating||0).toFixed(1)}</div>
                    <div style="font-size:.65rem;color:var(--text-secondary);">Rating</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="1.5" style="width:14px;height:14px;flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span style="font-size:.8rem;color:var(--text-secondary);">${c.location||'No location set'}</span>
            </div>
            <div style="font-size:.7rem;color:var(--text-secondary);margin-bottom:.75rem;">Registered: ${c.added_at ? fmtDate(c.added_at) : '—'}</div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                ${c.status === 'pending' ? `
                    <button class="btn btn-sm" style="background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;flex:1;border-radius:10px;font-weight:600;padding:.5rem;" onclick="approveClinic(${c.clinic_id})">✓ Approve</button>
                    <button class="btn btn-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;flex:1;border-radius:10px;font-weight:600;padding:.5rem;" onclick="rejectClinic(${c.clinic_id},'${esc(c.clinic_name)}')">✕ Reject</button>
                ` : ''}
                ${c.status === 'verified' ? `<button class="btn btn-sm btn-outline" style="flex:1;color:var(--yellow-500);" onclick="toggleClinicStatus(${c.clinic_id},'suspended')">⏸ Suspend</button>` : ''}
                ${c.status === 'suspended' ? `<button class="btn btn-sm btn-outline" style="flex:1;color:var(--green-500);" onclick="toggleClinicStatus(${c.clinic_id},'verified')">▶ Reactivate</button>` : ''}
                <button class="btn btn-sm btn-outline" style="flex:1;" onclick="viewClinicDetail(${c.clinic_id})">View Details</button>
            </div>
        </div>`;
    }

    main.innerHTML = `<div class="dashboard-content">
        <!-- Hero Header -->
        <div style="background:linear-gradient(135deg,#0d9488,#0891b2,#06b6d4);border-radius:20px;padding:1.5rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:20px;font-size:80px;opacity:.12;">🏥</div>
            <div style="position:absolute;bottom:-30px;right:80px;width:100px;height:100px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 .25rem;color:white !important;">Clinic Management</h1>
                    <p style="opacity:.85;margin:0;font-size:.95rem;color:white !important;">Oversee clinic registrations, approvals & performance</p>
                </div>
                <button class="btn" onclick="showRegisterClinicModal()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);font-size:.85rem;padding:.6rem 1.5rem;border-radius:12px;cursor:pointer;font-weight:600;">+ Register Clinic</button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1.5rem;">
            <div style="background:linear-gradient(135deg,rgba(13,148,136,0.1),rgba(13,148,136,0.03));border:1px solid rgba(13,148,136,0.15);border-radius:14px;padding:1rem;text-align:center;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);">${fmtNum(stats.total_clinics)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">🏥 Total</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03));border:1px solid rgba(16,185,129,0.15);border-radius:14px;padding:1rem;text-align:center;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.5rem;font-weight:800;color:var(--green-500);">${fmtNum(stats.verified)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">✅ Verified</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.03));border:1px solid rgba(245,158,11,0.15);border-radius:14px;padding:1rem;text-align:center;transition:transform .2s;${stats.pending > 0 ? 'animation:pulse-border 2s infinite;' : ''}" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.5rem;font-weight:800;color:var(--yellow-500);">${fmtNum(stats.pending)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">⏳ Pending</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.03));border:1px solid rgba(239,68,68,0.15);border-radius:14px;padding:1rem;text-align:center;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.5rem;font-weight:800;color:var(--red-500);">${fmtNum(stats.suspended)}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">🚫 Suspended</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(99,102,241,0.03));border:1px solid rgba(99,102,241,0.15);border-radius:14px;padding:1rem;text-align:center;transition:transform .2s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
                <div style="font-size:1.5rem;font-weight:800;color:var(--indigo-500);">★ ${stats.avg_rating||'0.0'}</div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.1rem;">📊 Avg Rating</div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;align-items:center;">
            <input type="text" class="search-input" placeholder="Search clinics by name, email or location..." id="admin-clinic-search" value="${currentSearch}" style="flex:1;">
            <select class="search-input" id="admin-clinic-status-filter" style="width:auto;min-width:140px;">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="suspended">Suspended</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <!-- Pending Approval Section -->
        ${pending.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--yellow-500);animation:pulse 2s infinite;"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Pending Approval (${pending.length})</h2>
                <span style="font-size:.75rem;color:var(--yellow-500);background:rgba(245,158,11,0.1);padding:3px 10px;border-radius:20px;font-weight:600;">Action Required</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${pending.map(c => clinicCard(c)).join('')}
            </div>
        </div>` : ''}

        <!-- Active Clinics Section -->
        ${active.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--green-500);"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Active Clinics (${active.length})</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${active.map(c => clinicCard(c)).join('')}
            </div>
        </div>` : ''}

        <!-- Suspended Clinics -->
        ${suspended.length > 0 ? `
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--red-500);"></div>
                <h2 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--text-primary);">Suspended (${suspended.length})</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
                ${suspended.map(c => clinicCard(c)).join('')}
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
                ${rejected.map(c => clinicCard(c)).join('')}
            </div>
        </div>` : ''}

        ${clinics.length === 0 ? '<div style="text-align:center;padding:3rem;color:var(--text-secondary);"><p>No clinics found</p></div>' : ''}
    </div>`;

    // Search handler
    let st;
    document.getElementById('admin-clinic-search').addEventListener('input', function() {
        clearTimeout(st);
        const val = this.value;
        const statusVal = document.getElementById('admin-clinic-status-filter').value;
        st = setTimeout(async () => {
            try {
                const [s, l] = await Promise.all([
                    apiGet('clinics.php?action=stats'),
                    apiGet('clinics.php?action=list&search=' + encodeURIComponent(val) + (statusVal !== 'all' ? '&status=' + statusVal : ''))
                ]);
                renderClinicsView(main, s.stats, l.clinics, val);
            } catch(e) {}
        }, 400);
    });
    document.getElementById('admin-clinic-status-filter').addEventListener('change', function() {
        const searchVal = document.getElementById('admin-clinic-search').value;
        const statusVal = this.value;
        (async () => {
            try {
                const [s, l] = await Promise.all([
                    apiGet('clinics.php?action=stats'),
                    apiGet('clinics.php?action=list&search=' + encodeURIComponent(searchVal) + (statusVal !== 'all' ? '&status=' + statusVal : ''))
                ]);
                renderClinicsView(main, s.stats, l.clinics, searchVal);
            } catch(e) {}
        })();
    });

    if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
}

// Reject clinic with reason modal
function rejectClinic(clinicId, clinicName) {
    showModal('Reject Clinic Signup', `
        <div style="text-align:center;margin-bottom:1rem;">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:28px;height:28px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <h3 style="margin:0 0 .5rem;font-size:1.1rem;">Reject <strong>${clinicName}</strong>?</h3>
            <p style="color:var(--text-secondary);font-size:.85rem;margin:0;">This clinic's signup will be rejected and they won't be able to access the platform.</p>
        </div>
        <div class="form-group"><label>Reason for Rejection</label><textarea id="reject-reason" rows="3" placeholder="e.g. Incomplete documentation, invalid license..."></textarea></div>
    `, `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn" id="reject-confirm-btn" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;">Reject Signup</button>`);

    document.getElementById('reject-confirm-btn').onclick = async () => {
        const reason = document.getElementById('reject-reason').value || 'No reason provided';
        try {
            const res = await apiPost('clinics.php', { action: 'reject', clinic_id: clinicId, reason: reason });
            if (res.success) {
                showAlert('Clinic signup rejected', 'success');
                setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200);
            } else showAlert(res.error || 'Failed', 'error');
        } catch(e) { showAlert('Error: ' + e.message, 'error'); }
    };
}

// View clinic detail modal
async function viewClinicDetail(clinicId) {
    try {
        const d = await apiGet('clinics.php?action=detail&clinic_id=' + clinicId);
        const c = d.clinic, specs = d.specialists || [], appts = d.appointment_count || 0;
        const body = `
        <div style="display:flex;gap:1.5rem;align-items:flex-start;margin-bottom:1.25rem;">
            <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#0d9488,#0891b2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:28px;height:28px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div style="flex:1;">
                <h3 style="margin:0;font-size:1.15rem;">${c.clinic_name}</h3>
                <p style="margin:.25rem 0 0;font-size:.85rem;color:var(--text-secondary);">${c.email}</p>
                <div style="display:flex;gap:.5rem;margin-top:.5rem;align-items:center;">
                    ${c.status === 'verified' ? '<span class="status-badge status-active">Verified</span>' : c.status === 'suspended' ? '<span class="status-badge status-danger">Suspended</span>' : c.status === 'rejected' ? '<span class="status-badge status-danger">Rejected</span>' : '<span class="status-badge status-warning">Pending</span>'}
                    <span style="font-size:.75rem;color:var(--text-secondary);">Since ${fmtDate(c.added_at)}</span>
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.25rem;">
            <div style="background:var(--bg-secondary);border-radius:12px;padding:.75rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;">${specs.length}</div><div style="font-size:.7rem;color:var(--text-secondary);">Specialists</div>
            </div>
            <div style="background:var(--bg-secondary);border-radius:12px;padding:.75rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;">${appts}</div><div style="font-size:.7rem;color:var(--text-secondary);">Appointments</div>
            </div>
            <div style="background:var(--bg-secondary);border-radius:12px;padding:.75rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;">★ ${Number(c.rating||0).toFixed(1)}</div><div style="font-size:.7rem;color:var(--text-secondary);">Rating</div>
            </div>
        </div>
        <div style="margin-bottom:1rem;"><strong style="font-size:.85rem;">Location:</strong> <span style="font-size:.85rem;color:var(--text-secondary);">${c.location||'Not set'}</span></div>
        ${specs.length > 0 ? `<div style="border-top:1px solid var(--border);padding-top:1rem;"><h4 style="font-size:.875rem;font-weight:600;margin:0 0 .75rem;">Specialists</h4>
            <div style="max-height:200px;overflow-y:auto;">
            ${specs.map(s => `<div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border);">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#818cf8);color:white;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;">${getInitials(s.first_name,s.last_name)}</div>
                <div><div style="font-size:.85rem;font-weight:500;">${s.first_name} ${s.last_name}</div><div style="font-size:.75rem;color:var(--text-secondary);">${s.specialization||'General'}</div></div>
            </div>`).join('')}
            </div>
        </div>` : '<p style="font-size:.85rem;color:var(--text-secondary);">No specialists assigned yet</p>'}`;

        showModal('Clinic Details', body, `
            <button class="btn btn-outline" onclick="closeModal()">Close</button>
            ${c.status === 'pending' ? `<button class="btn" style="background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;" onclick="closeModal();approveClinic(${c.clinic_id})">Approve</button>` : ''}
        `);
    } catch(e) { showAlert('Error loading clinic details', 'error'); }
}

// Approve clinic
function approveClinic(clinicId) {
    showConfirm('Are you sure you want to <strong>approve</strong> this clinic?', async () => {
        try {
            const res = await apiPost('clinics.php', { action: 'approve', clinic_id: clinicId });
            if (res.success) { showAlert('Clinic approved!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); }
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

// Toggle clinic status (suspend/reactivate)
function toggleClinicStatus(clinicId, newStatus) {
    showConfirm(`Are you sure you want to <strong>${newStatus === 'suspended' ? 'suspend' : 'verify'}</strong> this clinic?`, async () => {
        try {
            const actionUrl = newStatus === 'suspended' ? 'suspend' : 'reactivate';
            const res = await apiPost('clinics.php', { action: actionUrl, clinic_id: clinicId });
            if (res.success) { showAlert(`Clinic ${newStatus === 'suspended' ? 'suspended' : 'verified'}!`, 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1000); }
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    });
}

// Register new clinic modal
function showRegisterClinicModal() {
    showModal('Register New Clinic', `
        <div class="form-group"><label>Clinic Name</label><input type="text" id="rc-name" placeholder="Enter clinic name"></div>
        <div class="form-group"><label>Email</label><input type="email" id="rc-email" placeholder="clinic@example.com"></div>
        <div class="form-group"><label>Location</label><input type="text" id="rc-loc" placeholder="Enter address"></div>`,
        `<button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-gradient" id="rc-save">Register Clinic</button>`);
    document.getElementById('rc-save').onclick = async () => {
        const d = { action: 'register', clinic_name: document.getElementById('rc-name').value, email: document.getElementById('rc-email').value, location: document.getElementById('rc-loc').value };
        if (!d.clinic_name || !d.email) { showAlert('Clinic name and email are required.', 'warning'); return; }
        try {
            const res = await apiPost('clinics.php', d);
            if (res.success) { showAlert('Clinic registered!', 'success'); setTimeout(() => { closeModal(); showAdminView('clinics'); }, 1200); }
            else showAlert(res.error || 'Failed', 'error');
        } catch (e) { showAlert('Error: ' + e.message, 'error'); }
    };
}
