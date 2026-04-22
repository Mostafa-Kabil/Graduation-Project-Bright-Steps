// ─────────────────────────────────────────────────────────────
//  Clinic Dashboard – All buttons functional
// ─────────────────────────────────────────────────────────────

const CLINIC_ID = 1; // TODO: pull from PHP session via meta tag

/* ════════════════════════════════════════════════════════════
   BOOT
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    initClinicNav();
    showClinicView('specialists');
});

function initClinicNav() {
    const allNavBtns = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item[data-view]');

    allNavBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.dataset.view;
            if (!view) return;
            allNavBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            showClinicView(view);
        });
    });
}

function showClinicView(viewId) {
    const main = document.getElementById('clinic-main-content');
    if (!main) return;
    const views = {
        specialists:  loadSpecialistsView,
        appointments: loadAppointmentsView,
        patients:     loadPatientsView,
        revenue:      loadRevenueView,
        reviews:      loadReviewsView,
        settings:     loadSettingsView
    };
    const fn = views[viewId];
    if (!fn) return;
    main.innerHTML = skeletonHTML();
    fn(main);
}

/* ════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════ */
function skeletonHTML(msg = 'Loading…') {
    return `<div class="dashboard-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
        <div style="text-align:center;color:var(--text-secondary);">
            <svg style="width:3rem;height:3rem;margin-bottom:1rem;opacity:.5;animation:_spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            <p>${msg}</p>
        </div></div>
        <style>@keyframes _spin{to{transform:rotate(360deg)}}</style>`;
}

function errorHTML(msg) {
    return `<div class="dashboard-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
        <div style="text-align:center;color:var(--text-primary);">
            <svg style="width:2.5rem;height:2.5rem;margin-bottom:1rem;opacity:.6" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <p style="color:#dc2626;font-weight:600">${msg}</p>
            <button class="btn btn-outline" style="margin-top:1rem" onclick="showClinicView('specialists')">← Back</button>
        </div></div>`;
}

function statCard(color, icon, value, label) {
    return `<div class="stat-card stat-card-${color}">
        <div class="stat-icon-bg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icon}</svg>
        </div>
        <div class="stat-content">
            <div class="stat-value">${value}</div>
            <div class="stat-label">${label}</div>
        </div>
    </div>`;
}

function toast(msg, type = 'success') {
    document.querySelectorAll('.clinic-toast').forEach(t => t.remove());
    const t = document.createElement('div');
    t.className = `clinic-toast dr-toast-${type}`;
    const icon = type === 'success'
        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
        : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
    t.innerHTML = `<svg style="width:1.25rem;height:1.25rem;flex-shrink:0" viewBox="0 0 24 24" fill="none" stroke="${type==='success'?'#16a34a':'#dc2626'}" stroke-width="2">${icon}</svg><span>${msg}</span>`;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 3200);
}

function apiGet(url) { 
    return fetch(url)
        .then(r => {
            if (!r.ok) throw new Error(`HTTP error ${r.status}`);
            return r.json();
        })
        .catch(err => {
            console.error('API GET Error:', err);
            throw err;
        });
}
function apiPost(url, body) {
    return fetch(url, { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(body) 
    })
    .then(r => {
        if (!r.ok) throw new Error(`HTTP error ${r.status}`);
        return r.json();
    })
    .catch(err => {
        console.error('API POST Error:', err);
        throw err;
    });
}

function calcAge(year, month, day) {
    if (!year) return '—';
    const now = new Date(), birth = new Date(year, (month || 1) - 1, day || 1);
    let m = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (m < 0) m = 0;
    return m < 24 ? `${m} mo` : `${Math.floor(m / 12)} yr`;
}

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('active'); el.querySelectorAll('form').forEach(f => f.reset()); }
}

// Close modal on overlay click
document.addEventListener('click', e => {
    if (e.target.classList.contains('clinic-modal-overlay')) {
        e.target.classList.remove('active');
    }
});

/* ════════════════════════════════════════════════════════════
   SPECIALISTS
═══════════════════════════════════════════════════════════════ */
function loadSpecialistsView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=specialists&action=get_specialists&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { 
                main.innerHTML = errorHTML(res.error || 'Failed to load specialists'); 
                return; 
            }
            renderSpecialists(main, res.data, res.stats);
        })
        .catch(err => { 
            main.innerHTML = errorHTML(err.message === 'Unexpected token < in JSON at position 0' 
                ? 'Server Error (Invalid JSON)' 
                : 'Connection error: ' + err.message); 
        });
}

function renderSpecialists(main, list, stats) {
    const total  = stats?.total_specialists ?? list.length;
    const appts  = stats?.total_appointments ?? 0;
    const rating = stats?.avg_rating ?? '—';

    const rows = list.length === 0
        ? `<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary)">No specialists found.</td></tr>`
        : list.map(s => {
            const init = (s.first_name?.[0] || '') + (s.last_name?.[0] || '');
            const statusCls = s.status === 'active' ? 'status-active' : 'status-inactive';
            const exp = s.experience_years ? `${s.experience_years} yr` : '—';
            return `<tr>
                <td><div class="table-user">
                    <div class="patient-avatar">${init.toUpperCase()}</div>
                    <div><div class="patient-name">Dr. ${s.first_name} ${s.last_name}</div>
                    <div class="patient-details">${s.email}</div></div>
                </div></td>
                <td>${s.specialization || '—'}</td>
                <td>${exp}</td>
                <td>${s.total_appointments ?? 0}</td>
                <td><span class="rating-badge">${s.avg_rating ? '★ ' + s.avg_rating : '—'}</span></td>
                <td><span class="status-badge ${statusCls}">${s.status === 'active' ? 'Active' : 'Inactive'}</span></td>
                <td style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <button class="btn btn-sm btn-outline" onclick="viewSpecialist(${s.specialist_id},'${s.first_name} ${s.last_name}','${s.specialization||''}','${s.email}','${s.experience_years||0}')">View</button>
                    <button class="btn btn-sm ${s.status==='active'?'btn-danger':'btn-gradient'}" onclick="toggleSpecialistStatus(${s.specialist_id},'${s.status}')">
                        ${s.status === 'active' ? 'Deactivate' : 'Activate'}
                    </button>
                </td>
            </tr>`;
        }).join('');

    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Specialists</h1>
                <p class="dashboard-subtitle">Manage your clinic's healthcare team</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient" onclick="openModal('addSpecialistModal')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Specialist
                </button>
            </div>
        </div>

        <div class="doctor-stats-grid">
            ${statCard('blue','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',total,'Total Specialists')}
            ${statCard('green','<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',appts,'Total Appointments')}
            ${statCard('yellow','<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',rating,'Average Rating')}
            ${statCard('purple','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',list.filter(s=>s.status==='active').length,'Active Now')}
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Specialists</h2>
                <input class="search-input" id="specSearch" placeholder="Search…" oninput="filterTable('specialistsBody',this.value)">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead><tr><th>Specialist</th><th>Specialization</th><th>Experience</th><th>Appointments</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="specialistsBody">${rows}</tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Specialist Modal -->
    <div class="clinic-modal-overlay" id="addSpecialistModal">
        <div class="clinic-modal">
            <div class="clinic-modal-header">
                <h3>Add New Specialist</h3>
                <button class="clinic-modal-close" onclick="closeModal('addSpecialistModal')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body">
                <form id="addSpecForm" onsubmit="submitAddSpecialist(event)">
                    <div class="modal-form-row">
                        <div class="modal-form-group">
                            <label>First Name <span class="required-star">*</span></label>
                            <input type="text" id="sf_first" class="form-input" required placeholder="e.g. John">
                        </div>
                        <div class="modal-form-group">
                            <label>Last Name <span class="required-star">*</span></label>
                            <input type="text" id="sf_last" class="form-input" required placeholder="e.g. Smith">
                        </div>
                    </div>
                    <div class="modal-form-group">
                        <label>Email <span class="required-star">*</span></label>
                        <input type="email" id="sf_email" class="form-input" required placeholder="doctor@clinic.com">
                    </div>
                    <div class="modal-form-group">
                        <label>Password <span class="required-star">*</span></label>
                        <input type="password" id="sf_pass" class="form-input" required placeholder="Min 8 characters">
                    </div>
                    <div class="modal-form-row">
                        <div class="modal-form-group">
                            <label>Specialization <span class="required-star">*</span></label>
                            <input type="text" id="sf_spec" class="form-input" required placeholder="e.g. Pediatrician">
                        </div>
                        <div class="modal-form-group">
                            <label>Years of Experience</label>
                            <input type="number" id="sf_exp" class="form-input" min="0" max="60" placeholder="0">
                        </div>
                    </div>
                    <div class="clinic-modal-footer" style="padding:0;border:none;margin-top:1.25rem">
                        <button type="button" class="btn btn-outline" onclick="closeModal('addSpecialistModal')">Cancel</button>
                        <button type="submit" class="btn btn-gradient" id="addSpecBtn">Add Specialist</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Specialist Modal -->
    <div class="clinic-modal-overlay" id="viewSpecialistModal">
        <div class="clinic-modal">
            <div class="clinic-modal-header">
                <h3>Specialist Details</h3>
                <button class="clinic-modal-close" onclick="closeModal('viewSpecialistModal')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" id="viewSpecBody"></div>
            <div class="clinic-modal-footer">
                <button class="btn btn-outline" onclick="closeModal('viewSpecialistModal')">Close</button>
            </div>
        </div>
    </div>`;
}

function statCard(color, iconPath, value, label) {
    return `<div class="stat-card stat-card-${color}">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconPath}</svg></div>
        <div class="stat-card-info"><div class="stat-card-value">${value}</div><div class="stat-card-label">${label}</div></div>
    </div>`;
}

function filterTable(tbodyId, query) {
    const q = query.toLowerCase();
    document.querySelectorAll(`#${tbodyId} tr`).forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

function viewSpecialist(id, name, spec, email, exp) {
    document.getElementById('viewSpecBody').innerHTML = `
        <div style="text-align:center;margin-bottom:1.5rem">
            <div class="patient-avatar" style="width:4rem;height:4rem;font-size:1.25rem;margin:0 auto 0.75rem">${name.split(' ').map(n=>n[0]).join('').toUpperCase()}</div>
            <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary)">Dr. ${name}</div>
            <div style="color:var(--text-secondary);font-size:.875rem">${spec || 'Specialist'}</div>
        </div>
        <div class="record-detail-section">
            <div class="record-detail-row"><span class="record-detail-label">Email</span><span class="record-detail-value">${email}</span></div>
            <div class="record-detail-row"><span class="record-detail-label">Specialization</span><span class="record-detail-value">${spec || '—'}</span></div>
            <div class="record-detail-row"><span class="record-detail-label">Experience</span><span class="record-detail-value">${exp} years</span></div>
            <div class="record-detail-row"><span class="record-detail-label">Specialist ID</span><span class="record-detail-value">#${id}</span></div>
        </div>`;
    openModal('viewSpecialistModal');
}

function submitAddSpecialist(e) {
    e.preventDefault();
    const btn = document.getElementById('addSpecBtn');
    btn.disabled = true; btn.textContent = 'Adding…';

    apiPost(`clinic-dashboard.php?ajax=1&section=specialists&clinic_id=${CLINIC_ID}`, {
        action: 'add_specialist',
        first_name: document.getElementById('sf_first').value,
        last_name:  document.getElementById('sf_last').value,
        email:      document.getElementById('sf_email').value,
        password:   document.getElementById('sf_pass').value,
        specialization: document.getElementById('sf_spec').value,
        experience_years: document.getElementById('sf_exp').value || 0
    }).then(res => {
        btn.disabled = false; btn.textContent = 'Add Specialist';
        if (res.success) {
            closeModal('addSpecialistModal');
            toast('Specialist added successfully!');
            showClinicView('specialists');
        } else {
            toast(res.error || 'Failed to add specialist', 'error');
        }
    }).catch(() => { btn.disabled = false; btn.textContent = 'Add Specialist'; toast('Connection error', 'error'); });
}

function toggleSpecialistStatus(id, current) {
    const next = current === 'active' ? 'inactive' : 'active';
    if (!confirm(`${next === 'inactive' ? 'Deactivate' : 'Activate'} this specialist?`)) return;
    apiPost(`clinic-dashboard.php?ajax=1&section=specialists&clinic_id=${CLINIC_ID}`, {
        action: 'update_specialist_status', specialist_id: id, status: next
    }).then(res => {
        if (res.success) { toast(`Specialist ${next === 'active' ? 'activated' : 'deactivated'}`); showClinicView('specialists'); }
        else toast(res.error || 'Update failed', 'error');
    });
}

/* ════════════════════════════════════════════════════════════
   APPOINTMENTS
═══════════════════════════════════════════════════════════════ */
function loadAppointmentsView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=appointments&action=get_appointments&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { main.innerHTML = errorHTML('Failed to load appointments'); return; }
            renderAppointments(main, res.data, res.counts);
        })
        .catch(() => { main.innerHTML = errorHTML('Connection error'); });
}

function renderAppointments(main, list, counts) {
    const c = counts || {};
    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div><h1 class="dashboard-title">Appointments</h1>
            <p class="dashboard-subtitle">Clinic-wide appointment management</p></div>
        </div>

        <div class="doctor-stats-grid">
            ${statCard('blue','<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',c.today??0,'Today')}
            ${statCard('green','<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',c.upcoming??0,'Upcoming')}
            ${statCard('yellow','<polyline points="20 6 9 17 4 12"/>',c.completed??0,'Completed')}
            ${statCard('purple','<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>',c.total??0,'Total')}
        </div>

        <div class="reports-tabs">
            <button class="reports-tab active" onclick="filterAppts('',this)">All</button>
            <button class="reports-tab" onclick="filterAppts('scheduled',this)">Upcoming</button>
            <button class="reports-tab" onclick="filterAppts('completed',this)">Completed</button>
            <button class="reports-tab" onclick="filterAppts('cancelled',this)">Cancelled</button>
        </div>

        <div class="section-card">
            <div class="patients-list" id="apptList">${renderApptRows(list)}</div>
        </div>
    </div>`;
}

function renderApptRows(list) {
    if (!list || list.length === 0) return `<div style="text-align:center;padding:2.5rem;color:var(--text-secondary)">No appointments found.</div>`;
    return list.map(a => {
        const dt   = a.scheduled_at ? new Date(a.scheduled_at) : null;
        const date = dt ? dt.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) : '—';
        const time = dt ? dt.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'}) : '';
        const child  = a.child_first_name ? `${a.child_first_name} ${a.child_last_name}` : 'Unknown';
        const parent = `${a.parent_first_name||''} ${a.parent_last_name||''}`.trim() || '—';
        const doctor = `Dr. ${a.doctor_first_name||''} ${a.doctor_last_name||''}`.trim();
        const statusMap = { scheduled:'status-yellow', confirmed:'status-yellow', completed:'status-green', cancelled:'status-danger' };
        const sc = statusMap[a.status] || 'status-warning';
        const initials = child.charAt(0) + (child.split(' ')[1]?.[0] || '');
        const canAct = a.status === 'scheduled' || a.status === 'confirmed';
        return `<div class="patient-row" data-status="${a.status}">
            <div class="appointment-time-badge"><div class="apt-time">${time||'—'}</div><div class="apt-date">${date}</div></div>
            <div class="patient-avatar">${initials.toUpperCase()}</div>
            <div class="patient-info">
                <div class="patient-name">${child}</div>
                <div class="patient-details">Parent: ${parent} • ${doctor} • ${a.specialization||''}</div>
            </div>
            <span class="status-badge ${sc}" style="text-transform:capitalize">${a.status}</span>
            ${canAct ? `
            <button class="btn btn-sm btn-gradient" onclick="markApptDone(${a.appointment_id})">✓ Done</button>
            <button class="btn btn-sm btn-danger" onclick="cancelAppt(${a.appointment_id})">Cancel</button>` : ''}
        </div>`;
    }).join('');
}

function filterAppts(status, btn) {
    document.querySelectorAll('.reports-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('apptList').innerHTML = `<div style="text-align:center;padding:1.5rem;color:var(--text-secondary)">Loading…</div>`;
    const url = `clinic-dashboard.php?ajax=1&section=appointments&action=get_appointments&clinic_id=${CLINIC_ID}${status?`&status=${status}`:''}`;
    apiGet(url).then(res => {
        if (res.success) document.getElementById('apptList').innerHTML = renderApptRows(res.data);
    });
}

function markApptDone(id) {
    apiPost(`clinic-dashboard.php?ajax=1&section=appointments&clinic_id=${CLINIC_ID}`, {
        action: 'update_appointment', appointment_id: id, status: 'completed'
    }).then(res => {
        if (res.success) { toast('Marked as completed'); showClinicView('appointments'); }
        else toast(res.error || 'Failed', 'error');
    });
}

function cancelAppt(id) {
    if (!confirm('Cancel this appointment?')) return;
    apiPost(`clinic-dashboard.php?ajax=1&section=appointments&clinic_id=${CLINIC_ID}`, {
        action: 'update_appointment', appointment_id: id, status: 'cancelled'
    }).then(res => {
        if (res.success) { toast('Appointment cancelled'); showClinicView('appointments'); }
        else toast(res.error || 'Failed', 'error');
    });
}

/* ════════════════════════════════════════════════════════════
   PATIENTS
═══════════════════════════════════════════════════════════════ */
function loadPatientsView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=patients&action=get_patients&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { main.innerHTML = errorHTML('Failed to load patients'); return; }
            renderPatients(main, res.data);
        })
        .catch(() => { main.innerHTML = errorHTML('Connection error'); });
}

function renderPatients(main, list) {
    const rows = !list || list.length === 0
        ? `<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary)">No patients found.</td></tr>`
        : list.map(p => {
            const child  = `${p.child_first_name} ${p.child_last_name}`;
            const parent = `${p.parent_first_name} ${p.parent_last_name}`;
            const doc    = p.assigned_doctor_first ? `Dr. ${p.assigned_doctor_first} ${p.assigned_doctor_last}` : '—';
            const age    = calcAge(p.birth_year, p.birth_month, p.birth_day);
            const init   = child.charAt(0) + (p.child_last_name?.[0] || '');
            const status = p.last_status || 'scheduled';
            const { cls, lbl } = status === 'completed' ? { cls:'status-active', lbl:'On Track' }
                                : status === 'cancelled' ? { cls:'status-danger', lbl:'Cancelled' }
                                : { cls:'status-warning', lbl:'Pending' };
            const lastVisit = p.last_appointment_date
                ? new Date(p.last_appointment_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—';
            return `<tr>
                <td><div class="table-user">
                    <div class="patient-avatar">${init.toUpperCase()}</div>
                    <div><div class="patient-name">${child}</div><div class="patient-details">${p.gender||'—'}</div></div>
                </div></td>
                <td>${age}</td>
                <td>${parent}</td>
                <td>${doc}</td>
                <td><span class="status-badge ${cls}">${lbl}</span></td>
                <td>${lastVisit}</td>
                <td><button class="btn btn-sm btn-outline" onclick="viewPatientRecords(${p.child_id},'${child}')">View Records</button></td>
            </tr>`;
        }).join('');

    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div><h1 class="dashboard-title">Patient Directory</h1>
            <p class="dashboard-subtitle">${list?.length ?? 0} patient${(list?.length??0)!==1?'s':''} registered at this clinic</p></div>
        </div>
        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Patients</h2>
                <input class="search-input" placeholder="Search name or parent…" oninput="liveSearchPatients(this.value)">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead><tr><th>Child</th><th>Age</th><th>Parent</th><th>Specialist</th><th>Status</th><th>Last Visit</th><th>Actions</th></tr></thead>
                    <tbody id="patientsBody">${rows}</tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Patient Records Modal -->
    <div class="clinic-modal-overlay" id="patientRecordsModal">
        <div class="clinic-modal" style="max-width:700px">
            <div class="clinic-modal-header">
                <h3 id="patientRecordsTitle">Patient Records</h3>
                <button class="clinic-modal-close" onclick="closeModal('patientRecordsModal')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" id="patientRecordsBody">
                <div style="text-align:center;padding:2rem;color:var(--text-secondary)">Loading records…</div>
            </div>
            <div class="clinic-modal-footer">
                <button class="btn btn-outline" onclick="closeModal('patientRecordsModal')">Close</button>
            </div>
        </div>
    </div>`;
}

let patientSearchTimer = null;
function liveSearchPatients(q) {
    clearTimeout(patientSearchTimer);
    patientSearchTimer = setTimeout(() => {
        apiGet(`clinic-dashboard.php?ajax=1&section=patients&action=get_patients&clinic_id=${CLINIC_ID}&query=${encodeURIComponent(q)}`)
            .then(res => {
                if (res.success) {
                    const main = document.getElementById('clinic-main-content');
                    renderPatients(main, res.data);
                }
            });
    }, 350);
}

function viewPatientRecords(childId, childName) {
    document.getElementById('patientRecordsTitle').textContent = `Records — ${childName}`;
    document.getElementById('patientRecordsBody').innerHTML = skeletonHTML('Loading records…');
    openModal('patientRecordsModal');

    apiGet(`clinic/api/management/history.php?action=child&child_id=${childId}`)
        .then(res => {
            if (!res.success) { document.getElementById('patientRecordsBody').innerHTML = errorHTML('Records not found'); return; }
            const d = res;
            const recs = d.medical_records || [];
            const presc = d.prescriptions || [];
            const appts = d.appointments || [];

            const recsHtml = recs.length === 0 ? '<p style="color:var(--text-secondary)">No medical records.</p>'
                : recs.map(r => `<div class="record-appt-item"><strong>${r.diagnosis || 'No diagnosis'}</strong><br>
                    <span style="color:var(--text-secondary);font-size:.8rem">Dr. ${r.doctor_first_name} ${r.doctor_last_name} — ${r.created_at?.split('T')[0]||''}</span>
                    ${r.symptoms ? `<br><em style="font-size:.825rem;color:var(--text-secondary)">${r.symptoms}</em>` : ''}</div>`).join('');

            const prescHtml = presc.length === 0 ? '<p style="color:var(--text-secondary)">No prescriptions.</p>'
                : presc.map(p => `<div class="record-appt-item"><strong>${p.medication_name}</strong>
                    ${p.dosage?` — ${p.dosage}`:''} ${p.frequency?`(${p.frequency})`:''}<br>
                    <span style="color:var(--text-secondary);font-size:.8rem">Dr. ${p.doctor_first_name} ${p.doctor_last_name}</span></div>`).join('');

            const apptHtml = appts.length === 0 ? '<p style="color:var(--text-secondary)">No appointment history.</p>'
                : appts.slice(0,5).map(a => `<div class="record-appt-item" style="display:flex;justify-content:space-between;align-items:center">
                    <div><strong>Dr. ${a.doctor_first_name} ${a.doctor_last_name}</strong><br>
                    <span style="font-size:.8rem;color:var(--text-secondary)">${a.specialization} — ${a.scheduled_at?.split('T')[0]||''}</span></div>
                    <span class="status-badge ${a.status==='completed'?'status-active':a.status==='cancelled'?'status-danger':'status-warning'}" style="text-transform:capitalize">${a.status}</span>
                </div>`).join('');

            document.getElementById('patientRecordsBody').innerHTML = `
                <div class="record-detail-section"><h4>Medical Records (${recs.length})</h4>${recsHtml}</div>
                <div class="record-detail-section"><h4>Prescriptions (${presc.length})</h4>${prescHtml}</div>
                <div class="record-detail-section"><h4>Appointment History</h4>${apptHtml}</div>`;
        })
        .catch(() => {
            document.getElementById('patientRecordsBody').innerHTML = errorHTML('Failed to load records');
        });
}

/* ════════════════════════════════════════════════════════════
   REVENUE
═══════════════════════════════════════════════════════════════ */
function loadRevenueView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=revenue&action=get_revenue&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { main.innerHTML = errorHTML('Failed to load revenue'); return; }
            renderRevenue(main, res.breakdown, res.recent_payments, res.totals);
        })
        .catch(() => { main.innerHTML = errorHTML('Connection error'); });
}

function renderRevenue(main, breakdown, payments, totals) {
    const t = totals || {};
    const totalRev  = Number(t.total_monthly_revenue || 0).toFixed(2);
    const totalSubs = t.total_subscribers || 0;
    const freeUsers = t.free_users || 0;
    const pending   = t.pending_payments || 0;

    const breakdownHTML = breakdown && breakdown.length > 0
        ? breakdown.map(b => `<div class="revenue-row">
            <span class="revenue-plan">${b.plan_name}</span>
            <span class="revenue-count">${b.subscriber_count} subscriber${b.subscriber_count!=1?'s':''}</span>
            <span class="revenue-amount">$${Number(b.plan_revenue||0).toFixed(2)}/mo</span>
          </div>`).join('') + `<div class="revenue-row revenue-total">
            <span class="revenue-plan">Total Revenue</span><span></span>
            <span class="revenue-amount">$${totalRev}/mo</span></div>`
        : `<p style="padding:1rem;color:var(--text-secondary)">No subscription data yet.</p>`;

    const paymentsHTML = payments && payments.length > 0
        ? payments.map(p => {
            const name   = `${p.first_name} ${p.last_name}`;
            const amount = p.amount_post_discount ? `$${Number(p.amount_post_discount).toFixed(2)}` : 'Pending';
            const color  = p.status === 'paid' ? '#16a34a' : '#d97706';
            const date   = p.paid_at ? new Date(p.paid_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—';
            return `<div class="patient-row">
                <div class="patient-info"><div class="patient-name">${name}</div>
                <div class="patient-details">${p.plan_name} • ${p.method||'N/A'}</div></div>
                <div style="font-weight:700;color:${color}">${amount}</div>
                <div class="patient-last-update">${date}</div>
            </div>`;
        }).join('')
        : `<div style="text-align:center;padding:2rem;color:var(--text-secondary)">No payment records.</div>`;

    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div><h1 class="dashboard-title">Revenue & Payments</h1>
            <p class="dashboard-subtitle">Financial overview for clinic-associated patients</p></div>
            <button class="btn btn-outline" onclick="window.open('api_clinic_revenue_export.php', '_blank')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Report
            </button>
        </div>

        <div class="doctor-stats-grid">
            ${statCard('green','<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>','$'+totalRev,'Monthly Revenue')}
            ${statCard('blue','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',totalSubs,'Subscribers')}
            ${statCard('purple','<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',freeUsers,'Free Trial')}
            ${statCard('yellow','<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',pending,'Pending')}
        </div>

        <div class="revenue-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Subscription Breakdown</h2></div>
                <div style="padding:.5rem 1rem 1rem">${breakdownHTML}</div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Recent Payments</h2></div>
                <div class="patients-list">${paymentsHTML}</div>
            </div>
        </div>
    </div>`;
}

function exportRevenue() {
    const rows = [['Plan','Subscribers','Revenue']];
    document.querySelectorAll('.revenue-row:not(.revenue-total)').forEach(row => {
        const cells = row.querySelectorAll('span');
        if (cells.length >= 3) rows.push([cells[0].textContent, cells[1].textContent, cells[2].textContent]);
    });
    const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = `clinic-revenue-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    toast('Revenue report exported');
}

/* ════════════════════════════════════════════════════════════
   REVIEWS
═══════════════════════════════════════════════════════════════ */
function loadReviewsView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=reviews&action=get_reviews&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { main.innerHTML = errorHTML('Failed to load reviews'); return; }
            renderReviews(main, res.data, res.stats);
        })
        .catch(() => { main.innerHTML = errorHTML('Connection error'); });
}

function renderReviews(main, list, stats) {
    const s = stats || {};
    const avg      = s.avg_rating || '—';
    const total    = s.total_reviews || 0;
    const positive = s.positive_count || 0;
    const posP     = total > 0 ? Math.round((positive / total) * 100) : 0;

    const cards = list && list.length > 0
        ? list.map(r => {
            const parent = r.parent_first_name ? `${r.parent_first_name} ${r.parent_last_name}` : 'Anonymous';
            const doctor = `Dr. ${r.doctor_first_name} ${r.doctor_last_name}`;
            const stars  = '★'.repeat(r.rating || 0) + '☆'.repeat(5 - (r.rating || 0));
            const date   = r.submitted_at ? new Date(r.submitted_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '';
            const init   = parent.charAt(0) + (parent.split(' ')[1]?.[0] || '');
            return `<div class="review-card">
                <div class="review-header">
                    <div class="table-user">
                        <div class="patient-avatar">${init.toUpperCase()}</div>
                        <div><div class="patient-name">${parent}</div>
                        <div class="patient-details">About: ${doctor}${date ? ' • ' + date : ''}</div></div>
                    </div>
                    <div class="review-stars">${stars}</div>
                </div>
                ${r.content ? `<p class="review-text">"${r.content}"</p>` : ''}
                <div class="review-specialist">Specialization: ${r.specialization || '—'}</div>
            </div>`;
        }).join('')
        : `<div style="text-align:center;padding:3rem;color:var(--text-secondary)">No reviews yet for this clinic.</div>`;

    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div><h1 class="dashboard-title">Reviews & Feedback</h1>
            <p class="dashboard-subtitle">What parents say about your clinic</p></div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns:repeat(3,1fr)">
            ${statCard('yellow','<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',avg+'/5','Overall Rating')}
            ${statCard('green','<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/>',posP+'%','Positive Feedback')}
            ${statCard('blue','<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',total,'Total Reviews')}
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Reviews</h2>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <button class="reports-tab active" onclick="filterReviews(0,this)">All</button>
                    <button class="reports-tab" onclick="filterReviews(5,this)">★★★★★</button>
                    <button class="reports-tab" onclick="filterReviews(4,this)">★★★★</button>
                    <button class="reports-tab" onclick="filterReviews(3,this)">★★★ & below</button>
                </div>
            </div>
            <div class="reviews-list" id="reviewsList">${cards}</div>
        </div>
    </div>`;
}

function filterReviews(minRating, btn) {
    document.querySelectorAll('.reviews-list + .section-card-header .reports-tab, .section-card-header .reports-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.review-card').forEach(card => {
        if (minRating === 0) { card.style.display = ''; return; }
        const stars = (card.querySelector('.review-stars')?.textContent.match(/★/g) || []).length;
        card.style.display = (minRating === 3 ? stars <= 3 : stars >= minRating) ? '' : 'none';
    });
}

/* ════════════════════════════════════════════════════════════
   SETTINGS
═══════════════════════════════════════════════════════════════ */
function loadSettingsView(main) {
    apiGet(`clinic-dashboard.php?ajax=1&section=settings&clinic_id=${CLINIC_ID}`)
        .then(res => {
            if (!res.success) { main.innerHTML = errorHTML('Failed to load settings'); return; }
            renderSettings(main, res.data);
        })
        .catch(() => { main.innerHTML = errorHTML('Connection error'); });
}

function renderSettings(main, c) {
    c = c || {};
    main.innerHTML = `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div><h1 class="dashboard-title">Clinic Settings</h1>
            <p class="dashboard-subtitle">Manage clinic profile and preferences</p></div>
        </div>

        <div class="settings-grid">
            <!-- Clinic Info -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Clinic Information</h2></div>
                <div style="padding:1.5rem">
                    <form onsubmit="saveClinicSettings(event)">
                        <div class="form-grid">
                            <div class="form-group"><label>Clinic Name</label>
                                <input type="text" class="form-input" id="set_name" value="${esc(c.clinic_name)}"></div>
                            <div class="form-group"><label>Email Address</label>
                                <input type="email" class="form-input" id="set_email" value="${esc(c.email)}"></div>
                            <div class="form-group"><label>Location</label>
                                <input type="text" class="form-input" id="set_location" value="${esc(c.location)}"></div>
                            <div class="form-group"><label>Phone Numbers</label>
                                <input type="text" class="form-input" value="${esc(c.phones)}" readonly style="opacity:.7"></div>
                        </div>
                        <div style="margin-top:1.25rem;display:flex;gap:.75rem">
                            <button type="submit" class="btn btn-gradient">Save Changes</button>
                            <button type="button" class="btn btn-outline" onclick="loadSettingsView(document.getElementById('clinic-main-content'))">Reset</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Status Card -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Clinic Status</h2></div>
                <div style="padding:1.5rem">
                    <div class="toggle-row">
                        <span>Verification Status</span>
                        <span class="status-badge ${c.status==='verified'?'status-active':'status-warning'}">${c.status||'Unknown'}</span>
                    </div>
                    <div class="toggle-row">
                        <span>Rating</span>
                        <span style="font-weight:700;color:#d97706">★ ${c.rating||'—'}</span>
                    </div>
                    <div class="toggle-row">
                        <span>Member Since</span>
                        <span>${c.added_at ? new Date(c.added_at).toLocaleDateString('en-US',{month:'long',year:'numeric'}) : '—'}</span>
                    </div>
                    <div class="toggle-row" style="border-bottom:none">
                        <span>Notifications</span>
                        <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
                            <input type="checkbox" checked style="opacity:0;width:0;height:0" onchange="toast(this.checked?'Notifications enabled':'Notifications muted')">
                            <span style="position:absolute;inset:0;background:${`#0d9488`};border-radius:24px;transition:.3s"></span>
                            <span style="position:absolute;height:18px;width:18px;bottom:3px;left:3px;background:white;border-radius:50%;transition:.3s;transform:translateX(20px)"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Security</h2></div>
                <div style="padding:1.5rem">
                    <form onsubmit="changeClinicPassword(event)">
                        <div class="form-group" style="margin-bottom:1rem"><label>Current Password</label>
                            <input type="password" class="form-input" id="set_curr_pw" placeholder="Enter current password"></div>
                        <div class="form-grid" style="margin-bottom:1.25rem">
                            <div class="form-group"><label>New Password</label>
                                <input type="password" class="form-input" id="set_new_pw" placeholder="Min 8 characters"></div>
                            <div class="form-group"><label>Confirm Password</label>
                                <input type="password" class="form-input" id="set_conf_pw" placeholder="Repeat password"></div>
                        </div>
                        <button type="submit" class="btn btn-outline">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="section-card danger-card">
                <div class="section-card-header"><h2 class="section-heading" style="color:#dc2626">Danger Zone</h2></div>
                <div style="padding:1.5rem">
                    <p style="color:var(--text-secondary);margin:0 0 1rem">These actions are permanent and irreversible.</p>
                    <button class="btn btn-danger" onclick="deactivateClinic()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        Deactivate Clinic
                    </button>
                </div>
            </div>
        </div>
    </div>`;
}

function esc(s) { return (s || '').toString().replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

function saveClinicSettings(e) {
    e.preventDefault();
    const btn = e.submitter;
    btn.disabled = true; btn.textContent = 'Saving…';
    apiPost(`clinic-dashboard.php?ajax=1&section=settings&clinic_id=${CLINIC_ID}`, {
        action: 'update_clinic',
        clinic_name: document.getElementById('set_name').value,
        email:       document.getElementById('set_email').value,
        location:    document.getElementById('set_location').value
    }).then(res => {
        btn.disabled = false; btn.textContent = 'Save Changes';
        if (res.success) toast('Clinic settings saved!');
        else toast(res.error || 'Save failed', 'error');
    }).catch(() => { btn.disabled = false; btn.textContent = 'Save Changes'; toast('Connection error', 'error'); });
}

function changeClinicPassword(e) {
    e.preventDefault();
    const curr = document.getElementById('set_curr_pw').value;
    const newPw = document.getElementById('set_new_pw').value;
    const conf  = document.getElementById('set_conf_pw').value;
    if (!curr || !newPw) { toast('Fill in all password fields', 'error'); return; }
    if (newPw.length < 8) { toast('New password must be at least 8 characters', 'error'); return; }
    if (newPw !== conf)   { toast('Passwords do not match', 'error'); return; }

    apiPost(`clinic-dashboard.php?ajax=1&section=settings&clinic_id=${CLINIC_ID}`, {
        action: 'change_password', current_password: curr, new_password: newPw
    }).then(res => {
        if (res.success) { toast('Password changed successfully'); e.target.reset(); }
        else toast(res.error || 'Failed to change password', 'error');
    }).catch(() => toast('Connection error', 'error'));
}

function deactivateClinic() {
    if (!confirm('Are you SURE you want to deactivate this clinic? This cannot be undone.')) return;
    toast('Deactivation request sent to admin for review.', 'error');
}

function handleLogout() {
    const existing = document.getElementById('logout-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'logout-modal';
    modal.innerHTML = `
        <div class="logout-overlay" onclick="closeLogoutModal()"></div>
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
    window.location.href = 'logout.php';
}
