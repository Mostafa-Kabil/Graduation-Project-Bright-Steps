// Doctor Dashboard JavaScript — Core + Patients + Reports + Appointments
const SPECIALIST_ID = (typeof SESSION_SPECIALIST_ID !== 'undefined') ? SESSION_SPECIALIST_ID : 0;
let messagesPollInterval = null;

document.addEventListener('DOMContentLoaded', function () {
    initDoctorNav();
    loadPatientsData();
});

// ─── Navigation ─────────────────────────────────────
function initDoctorNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const footerItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');
    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) { setActiveNav(this); footerItems.forEach(f=>f.classList.remove('active')); showDoctorView(view); }
        });
    });
    footerItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) { navItems.forEach(n => n.classList.remove('active')); footerItems.forEach(f=>f.classList.remove('active')); this.classList.add('active'); showDoctorView(view); }
        });
    });
}

function setActiveNav(activeItem) {
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(i => i.classList.remove('active'));
    activeItem.classList.add('active');
}

function showDoctorView(viewId) {
    if (messagesPollInterval) { clearInterval(messagesPollInterval); messagesPollInterval = null; }
    const main = document.querySelector('.dashboard-main');
    if (!main) return;
    const views = { patients: getPatientsView, reports: getReportsView, appointments: getAppointmentsView, messages: getMessagesView, analytics: getAnalyticsView, settings: getSettingsView };
    const fn = views[viewId];
    if (fn) { main.innerHTML = fn(); if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage(); }
}

// ─── Utilities ──────────────────────────────────────
function calculateAge(year, month, day) {
    if (!year || !month) return 'Unknown age';
    const now = new Date();
    let months = (now.getFullYear() - year) * 12 + (now.getMonth() - (month - 1));
    if (months < 0) months = 0;
    if (months < 24) return `${months} month${months !== 1 ? 's' : ''}`;
    const years = Math.floor(months / 12);
    return `${years} year${years !== 1 ? 's' : ''}`;
}

function formatRelativeDate(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Updated today';
    if (diffDays === 1) return 'Updated yesterday';
    if (diffDays < 7) return `Updated ${diffDays} days ago`;
    if (diffDays < 30) return `Updated ${Math.floor(diffDays / 7)} week${Math.floor(diffDays / 7) > 1 ? 's' : ''} ago`;
    return `Updated on ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
}

function showToast(message, type) {
    const existing = document.querySelector('.dr-toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = `dr-toast dr-toast-${type}`;
    const icon = type === 'success'
        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
        : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
    toast.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.25rem;height:1.25rem;flex-shrink:0;">${icon}</svg><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

function navigateToView(viewId) {
    const navBtn = document.querySelector(`.sidebar-nav .nav-item[data-view="${viewId}"]`);
    if (navBtn) { setActiveNav(navBtn); showDoctorView(viewId); }
}

// ═══════════════════════════════════════════════════
// PATIENTS PAGE
// ═══════════════════════════════════════════════════
function getPatientsView() {
    setTimeout(() => loadPatientsData(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">My Patients</h1>
            <p class="dashboard-subtitle" id="patientsSubtitle">View and manage your connected patients</p>
        </div></div>
        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-active-patients">--</div><div class="stat-card-label">Active Patients</div></div></div>
            <div class="stat-card stat-card-green"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-on-track">--</div><div class="stat-card-label">On Track</div></div></div>
            <div class="stat-card stat-card-yellow"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-needs-attention">--</div><div class="stat-card-label">Needs Attention</div></div></div>
            <div class="stat-card stat-card-purple"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-this-week-patients">--</div><div class="stat-card-label">This Week</div></div></div>
        </div>
        <div class="section-card"><div class="section-card-header">
            <h2 class="section-heading">Recent Patients</h2>
            <div style="display:flex;gap:0.75rem;align-items:center;">
                <select class="search-input" id="patientGenderFilter" onchange="filterPatientsByGender()" style="width:140px;">
                    <option value="">All Genders</option><option value="male">Male</option><option value="female">Female</option>
                </select>
                <input type="text" class="search-input" id="patientSearchInput" placeholder="Search patients..." oninput="searchPatients(this.value)">
            </div>
        </div>
        <div class="patients-list" id="patientsListContainer"><div style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading patients...</div></div>
        </div></div>`;
}

let allPatientsCache = [];
function loadPatientsData() {
    fetch(`doctor-dashboard.php?ajax=1&section=patients&action=get_patients&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            if (result.success && result.data) { allPatientsCache = result.data; renderPatientsList(result.data); updatePatientsStats(result.data); }
            else renderPatientsEmpty();
        }).catch(() => renderPatientsEmpty());
}

function renderPatientsList(patients) {
    const container = document.getElementById('patientsListContainer');
    if (!container) return;
    if (!patients.length) { renderPatientsEmpty(); return; }
    let html = '';
    patients.forEach(p => {
        const initials = (p.child_first_name?.charAt(0) || '') + (p.child_last_name?.charAt(0) || '');
        const age = calculateAge(p.birth_year, p.birth_month, p.birth_day);
        const status = p.last_appointment_status || 'scheduled';
        const statusClass = status === 'completed' ? 'status-green' : (status === 'cancelled' ? 'status-red' : 'status-yellow');
        const statusLabel = status === 'completed' ? 'On Track' : (status === 'cancelled' ? 'Cancelled' : 'Needs Review');
        const lastDate = p.last_appointment_date ? formatRelativeDate(p.last_appointment_date) : 'No appointments';
        const childFullName = `${p.child_first_name} ${p.child_last_name}`.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        html += `<div class="patient-row">
            <div class="patient-avatar">${initials}</div>
            <div class="patient-info"><div class="patient-name">${p.child_first_name} ${p.child_last_name}</div>
                <div class="patient-details">${age} • ${p.gender || 'N/A'} • Parent: ${p.parent_first_name} ${p.parent_last_name}</div></div>
            <div class="patient-status ${statusClass}">${statusLabel}</div>
            <div class="patient-last-update">${lastDate}</div>
            <div style="display:flex;gap:0.5rem;">
                <button class="btn btn-sm btn-outline" onclick="viewPatientDetail(${p.child_id})" title="View child profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View
                </button>
                <button class="btn btn-sm btn-outline" style="color:var(--purple-500);border-color:var(--purple-500);" onclick="openReportForChild(${p.child_id},'${childFullName}')" title="Write report for child">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Report
                </button>
                <button class="btn btn-sm btn-outline" style="color:var(--green-500);border-color:var(--green-500);" onclick="chatWithParent(${p.parent_id})" title="Chat with parent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Chat
                </button>
            </div></div>`;
    });
    container.innerHTML = html;
}

function renderPatientsEmpty() {
    const c = document.getElementById('patientsListContainer');
    if (c) c.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);">No patients found. Patients will appear here once they book appointments with you.</div>';
}

function updatePatientsStats(patients) {
    const total = patients.length;
    const onTrack = patients.filter(p => p.last_appointment_status === 'completed').length;
    const needsAttention = patients.filter(p => p.last_appointment_status !== 'completed').length;
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    const thisWeek = patients.filter(p => p.last_appointment_date && new Date(p.last_appointment_date) >= weekAgo).length;
    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    el('stat-active-patients', total); el('stat-on-track', onTrack); el('stat-needs-attention', needsAttention); el('stat-this-week-patients', thisWeek);
    const sub = document.getElementById('patientsSubtitle');
    if (sub) sub.textContent = `You have ${total} patient${total !== 1 ? 's' : ''} assigned to your care`;
}

let searchTimeout = null;
function searchPatients(query) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyPatientFilters();
    }, 300);
}

function filterPatientsByGender() {
    applyPatientFilters();
}

function applyPatientFilters() {
    const query = (document.getElementById('patientSearchInput')?.value || '').trim().toLowerCase();
    const gender = document.getElementById('patientGenderFilter')?.value;
    
    let filtered = allPatientsCache;
    
    if (gender) {
        filtered = filtered.filter(p => {
            const g = (p.gender || '').toLowerCase();
            return g === gender || g === gender.charAt(0);
        });
    }
    
    if (query) {
        filtered = filtered.filter(p => 
            `${p.child_first_name} ${p.child_last_name}`.toLowerCase().includes(query) ||
            `${p.parent_first_name} ${p.parent_last_name}`.toLowerCase().includes(query)
        );
    }
    
    renderPatientsList(filtered);
}

function openReportForChild(childId, childName) {
    // Navigate to Reports tab and open the write-report modal for this child
    navigateToView('reports');
    setTimeout(() => {
        openReportModal(childName, childId, '', 0, 0, '', '', '');
    }, 300);
}

function chatWithParent(parentId) {
    navigateToView('messages');
    setTimeout(() => { if (typeof selectConversationById === 'function') selectConversationById(parentId); }, 300);
}

function viewPatientDetail(childId) {
    fetch(`doctor-dashboard.php?ajax=1&section=patients&action=get_patient_detail&specialist_id=${SPECIALIST_ID}&child_id=${childId}`)
        .then(r => r.json()).then(result => {
            if (result.success && result.data) showPatientDetailModal(result.data);
            else showToast('Failed to load patient details', 'error');
        }).catch(() => showToast('Connection error', 'error'));
}

function showPatientDetailModal(data) {
    const c = data.child;
    if (!c) return;
    const age = calculateAge(c.birth_year, c.birth_month, c.birth_day);
    const initials = (c.first_name?.charAt(0) || '') + (c.last_name?.charAt(0) || '');
    const childFullName = `${c.first_name} ${c.last_name}`;
    const safeName = childFullName.replace(/'/g, "\\'").replace(/"/g, "&quot;");

    // Growth section
    let growthHtml = data.growth_records?.length > 0
        ? `<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:0.5rem;">
            <div style="background:var(--bg-secondary);padding:0.75rem;border-radius:0.5rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;color:var(--blue-500);">${data.growth_records[0].height || '--'}</div><div style="font-size:0.8rem;color:var(--text-secondary);">cm Height</div></div>
            <div style="background:var(--bg-secondary);padding:0.75rem;border-radius:0.5rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;color:var(--green-500);">${data.growth_records[0].weight || '--'}</div><div style="font-size:0.8rem;color:var(--text-secondary);">kg Weight</div></div>
            <div style="background:var(--bg-secondary);padding:0.75rem;border-radius:0.5rem;text-align:center;">
                <div style="font-size:1.25rem;font-weight:700;color:var(--purple-500);">${data.growth_records[0].head_circumference || '--'}</div><div style="font-size:0.8rem;color:var(--text-secondary);">cm Head</div></div>
           </div>
           <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:0.5rem;">Last recorded: ${new Date(data.growth_records[0].recorded_at).toLocaleDateString()}</p>`
        : '<p style="color:var(--text-secondary);">No growth records available.</p>';

    // Milestones section
    let milestonesHtml = data.milestones?.length > 0
        ? data.milestones.slice(0, 5).map(m => `<div style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
            <div><strong>${m.title}</strong> <span style="color:var(--text-secondary);font-size:0.85rem;">(${m.category})</span></div>
            <div style="font-size:0.8rem;color:var(--text-secondary);">${m.achieved_at ? new Date(m.achieved_at).toLocaleDateString() : 'In progress'}</div>
           </div>`).join('')
        : '<p style="color:var(--text-secondary);">No milestones recorded yet.</p>';

    // Reports section
    let reportsHtml = data.doctor_reports?.length > 0
        ? data.doctor_reports.slice(0, 3).map(r => `<div style="padding:0.75rem;margin-bottom:0.5rem;background:var(--bg-secondary);border-radius:0.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                <div style="font-weight:600;font-size:0.9rem;">${new Date(r.report_date).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'})}</div>
            </div>
            <div style="font-size:0.85rem;"><strong>Notes:</strong> ${(r.doctor_notes||'').substring(0, 150)}${(r.doctor_notes||'').length > 150 ? '...' : ''}</div>
            ${r.recommendations ? `<div style="font-size:0.85rem;margin-top:0.25rem;"><strong>Recommendations:</strong> ${r.recommendations.substring(0, 100)}${r.recommendations.length > 100 ? '...' : ''}</div>` : ''}
           </div>`).join('')
        : '<p style="color:var(--text-secondary);">No reports written for this patient.</p>';

    // Appointments section
    let appointmentsHtml = data.appointments?.length > 0
        ? data.appointments.slice(0, 5).map(a => {
            const st = a.status || 'scheduled';
            const stClass = st === 'completed' ? 'color:var(--green-500)' : (st === 'cancelled' ? 'color:var(--red-500)' : 'color:var(--yellow-500)');
            const typeIcon = a.type === 'online' ? '🖥' : '🏥';
            return `<div style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
                <div>${typeIcon} ${a.scheduled_at ? new Date(a.scheduled_at).toLocaleDateString('en-US', {weekday:'short',month:'short',day:'numeric'}) : 'No date'}</div>
                <span style="font-size:0.85rem;font-weight:600;${stClass}">${st.charAt(0).toUpperCase() + st.slice(1)}</span>
            </div>`;
          }).join('')
        : '<p style="color:var(--text-secondary);">No appointment history.</p>';

    const overlay = document.createElement('div');
    overlay.className = 'report-modal-overlay active';
    overlay.id = 'patientDetailModal';
    overlay.innerHTML = `<div class="report-modal" style="max-width:720px;">
        <div class="report-modal-header"><h3 style="display:flex;align-items:center;gap:0.75rem;"><div class="patient-avatar" style="width:2.5rem;height:2.5rem;font-size:0.9rem;">${initials}</div>${childFullName}</h3>
        <button class="report-modal-close" onclick="document.getElementById('patientDetailModal').remove()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="report-modal-body" style="max-height:70vh;overflow-y:auto;">
            <!-- Child Info Grid -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem 1.5rem;margin-bottom:1.5rem;padding:1rem;background:var(--bg-secondary);border-radius:0.75rem;">
                <div><span style="color:var(--text-secondary);font-size:0.85rem;">Age</span><div style="font-weight:600;">${age}</div></div>
                <div><span style="color:var(--text-secondary);font-size:0.85rem;">Gender</span><div style="font-weight:600;">${c.gender || 'N/A'}</div></div>
                <div><span style="color:var(--text-secondary);font-size:0.85rem;">Parent</span><div style="font-weight:600;">${c.parent_first_name} ${c.parent_last_name}</div></div>
                <div><span style="color:var(--text-secondary);font-size:0.85rem;">Total Appointments</span><div style="font-weight:600;">${data.appointments?.length || 0}</div></div>
            </div>

            <h4 style="margin-bottom:0.5rem;color:var(--blue-500);display:flex;align-items:center;gap:0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Latest Growth Record</h4>
            ${growthHtml}

            <h4 style="margin:1.5rem 0 0.5rem;color:var(--green-500);display:flex;align-items:center;gap:0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Milestones</h4>
            ${milestonesHtml}

            <h4 style="margin:1.5rem 0 0.5rem;color:var(--purple-500);display:flex;align-items:center;gap:0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Specialist Reports</h4>
            ${reportsHtml}

            <h4 style="margin:1.5rem 0 0.5rem;color:var(--yellow-600);display:flex;align-items:center;gap:0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Appointment History</h4>
            ${appointmentsHtml}

            <!-- Action Buttons -->
            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                <button class="btn btn-gradient" style="flex:1;" onclick="document.getElementById('patientDetailModal').remove(); openReportForChild(${c.child_id}, '${safeName}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Write Report
                </button>
                <button class="btn btn-outline" style="flex:1;color:var(--green-500);border-color:var(--green-500);" onclick="document.getElementById('patientDetailModal').remove(); chatWithParent(${c.parent_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Chat with Parent
                </button>
            </div>
        </div></div>`;
    document.body.appendChild(overlay);
}

// ═══════════════════════════════════════════════════
// REPORTS PAGE — Data-driven
// ═══════════════════════════════════════════════════
function getReportsView() {
    setTimeout(() => { initReportsPage(); loadReportsData(); }, 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Reports</h1>
            <p class="dashboard-subtitle">Review shared child reports and write medical assessments</p>
        </div></div>
        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-total-reports">--</div><div class="stat-card-label">Total Reports</div></div></div>
            <div class="stat-card stat-card-yellow"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-pending">--</div><div class="stat-card-label">Pending Review</div></div></div>
            <div class="stat-card stat-card-green"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-completed-reports">--</div><div class="stat-card-label">Completed</div></div></div>
            <div class="stat-card stat-card-purple"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-this-month">--</div><div class="stat-card-label">This Month</div></div></div>
        </div>
        <div class="reports-tabs">
            <button class="reports-tab active" data-tab="shared" onclick="switchReportsTab('shared')">Shared Child Reports</button>
            <button class="reports-tab" data-tab="mine" onclick="switchReportsTab('mine')">My Reports</button>
        </div>
        <div class="reports-tab-content active" id="tab-shared"><div class="reports-list" id="sharedReportsList"><div style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading shared reports...</div></div></div>
        <div class="reports-tab-content" id="tab-mine"><div class="reports-list" id="myReportsList"><div style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading your reports...</div></div></div>
        <div class="report-modal-overlay" id="reportModal"><div class="report-modal">
            <div class="report-modal-header"><h3>Write Medical Report</h3><button class="report-modal-close" onclick="closeReportModal()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
            <div class="report-modal-body">
                <div class="report-form-context" id="reportFormContext"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><span>Writing report for: <strong id="reportChildName">—</strong></span></div>
                <form id="doctorReportForm" onsubmit="submitDoctorReport(event)">
                    <input type="hidden" id="reportChildId" value=""><input type="hidden" id="reportChildReport" value="">
                    <div class="report-form-group"><label class="report-form-label" for="doctorNotes">Specialist Notes <span style="color:var(--red-500);">*</span></label><textarea id="doctorNotes" class="report-form-textarea" rows="5" placeholder="Enter your clinical observations..." required></textarea></div>
                    <div class="report-form-group"><label class="report-form-label" for="recommendations">Recommendations</label><textarea id="recommendations" class="report-form-textarea" rows="4" placeholder="Enter treatment recommendations..."></textarea></div>
                    <div class="report-form-group"><label class="report-form-label" for="reportDate">Report Date</label><input type="date" id="reportDate" class="report-form-input" value=""></div>
                    <div class="report-form-actions"><button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button><button type="submit" class="btn btn-gradient">Submit Report</button></div>
                </form></div></div></div></div>`;
}

function loadReportsData() {
    fetch(`doctor-dashboard.php?ajax=1&section=reports&action=get_report_stats&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            if (result.success && result.data) {
                const d = result.data;
                const el = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
                el('stat-total-reports', d.total_reports + d.shared_total);
                el('stat-pending', d.pending_review);
                el('stat-completed-reports', d.total_reports);
                el('stat-this-month', d.this_month);
            }
        }).catch(() => {});
    fetch(`doctor-dashboard.php?ajax=1&section=reports&action=get_shared_reports&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('sharedReportsList');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                container.innerHTML = result.data.map(r => {
                    const initials = (r.child_first_name?.charAt(0) || '') + (r.child_last_name?.charAt(0) || '');
                    const age = calculateAge(r.birth_year, r.birth_month);
                    const apptDate = r.appointment_date ? new Date(r.appointment_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No appointment';
                    const apptStatus = r.appointment_status ? r.appointment_status.charAt(0).toUpperCase() + r.appointment_status.slice(1) : '';
                    const isReplied = r.doctor_reply && r.doctor_reply.trim().length > 0;
                    const statusBadge = isReplied
                        ? '<span class="report-status report-status-completed">Replied</span>'
                        : '<span class="report-status report-status-pending">Pending Review</span>';
                    const replySection = isReplied
                        ? `<div style="margin-top:0.75rem;padding:0.75rem;background:var(--green-50,#f0fdf4);border-radius:8px;border-left:3px solid var(--green-500,#22c55e);">
                              <div style="font-size:0.8rem;font-weight:600;color:var(--green-700,#15803d);margin-bottom:0.25rem;">Your Reply (${new Date(r.doctor_reply_date).toLocaleDateString('en-US',{month:'short',day:'numeric'})}):</div>
                              <div style="font-size:0.85rem;color:var(--text-primary);">${r.doctor_reply}</div>
                           </div>`
                        : '';
                    const reportTypeLabel = {
                        'full-report': '📋 Full Development Report',
                        'growth-report': '📊 Growth Report',
                        'speech-report': '🗣️ Speech Report',
                        'child-report': '👤 Child Profile Report',
                        'uploaded-pdf': '📎 Uploaded PDF'
                    }[r.report_type] || ('📄 ' + r.report_type);
                    const viewUrl = r.file_path ? r.file_path.replace(/'/g, "\\'") : '';
                    const childNameSafe = (r.child_first_name || '') + ' ' + (r.child_last_name || '');
                    const safeName = childNameSafe.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const reportContext = 'Shared Report: ' + r.report_type;
                    return `<div class="report-card"><div class="report-card-header">
                        <div class="report-child-avatar">${initials}</div>
                        <div class="report-card-info"><div class="report-child-name">${childNameSafe}</div><div class="report-meta">Shared by ${r.parent_first_name} ${r.parent_last_name} • ${age} • ${reportTypeLabel}</div></div>
                        ${statusBadge}</div>
                        <div class="report-card-body">
                          <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
                            <div class="report-detail-row"><span class="report-detail-label">📅 Appointment:</span><span>${apptDate} ${apptStatus ? '(' + apptStatus + ')' : ''}</span></div>
                            <div class="report-detail-row"><span class="report-detail-label">👶 Gender:</span><span>${r.gender || 'N/A'}</span></div>
                            <div class="report-detail-row"><span class="report-detail-label">📂 Type:</span><span>${r.report_type}</span></div>
                          </div>
                          ${replySection}
                        </div>
                        <div class="report-card-footer">
                          <span class="report-date">Shared ${new Date(r.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'})}</span>
                          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <button class="btn btn-sm btn-outline" style="color:var(--blue-500);border-color:var(--blue-500);" onclick="viewSharedReport('${viewUrl}', '${r.report_type}')">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View
                            </button>
                            <button class="btn btn-sm btn-outline" style="color:var(--green-500);border-color:var(--green-500);" onclick="downloadSharedReport('${viewUrl}', '${r.report_type}')">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download
                            </button>
                            <button class="btn btn-sm btn-gradient" onclick="openReportModal('${safeName}', ${r.child_id}, '${reportContext.replace(/'/g, "\\'")}', ${r.report_id})">
                              ${isReplied ? '✏️ Edit Report' : '💬 Write Report'}
                            </button>
                          </div>
                        </div></div>`;
                }).join('');
            } else {
                container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);">No shared reports found. Reports will appear here when parents share them with you.</div>';
            }
        }).catch(() => {});
    fetch(`doctor-dashboard.php?ajax=1&section=reports&action=get_doctor_reports&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('myReportsList');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                container.innerHTML = result.data.map(r => {
                    const childNameSafe = (r.child_first_name || '') + ' ' + (r.child_last_name || '');
                    const safeName = childNameSafe.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const safeNotes = (r.doctor_notes || '').replace(/'/g, "\\'").replace(/"/g, "&quot;").replace(/\n/g, '\\n').replace(/\r/g, '');
                    const safeRecs = (r.recommendations || '').replace(/'/g, "\\'").replace(/"/g, "&quot;").replace(/\n/g, '\\n').replace(/\r/g, '');
                    const safeChildReport = (r.child_report || '').replace(/'/g, "\\'").replace(/"/g, "&quot;").replace(/\n/g, '\\n').replace(/\r/g, '');
                    const rDate = r.report_date || '';

                    return `<div class="report-card"><div class="report-card-header">
                    <div class="report-card-icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                    <div class="report-card-info"><div class="report-child-name">Report for ${childNameSafe}</div><div class="report-meta">Written on ${new Date(r.report_date || r.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div></div>
                    <span class="report-status report-status-completed">Completed</span></div>
                    <div class="report-card-body"><div class="report-detail-row"><span class="report-detail-label">Specialist Notes:</span><span>${r.doctor_notes}</span></div>
                    ${r.recommendations ? `<div class="report-detail-row"><span class="report-detail-label">Recommendations:</span><span>${r.recommendations}</span></div>` : ''}</div>
                    <div class="report-card-footer" style="border-top:1px solid var(--border-color);padding-top:1rem;margin-top:1rem;display:flex;justify-content:flex-end;">
                        <button class="btn btn-sm btn-outline" style="color:var(--blue-500);border-color:var(--blue-500);" onclick="openReportModal('${safeName}', ${r.child_id}, '${safeChildReport}', 0, ${r.report_id}, '${safeNotes}', '${safeRecs}', '${rDate}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit Report
                        </button>
                    </div></div>`;
                }).join('');
            } else {
                container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);">No reports written yet.</div>';
            }
        }).catch(() => {});
}

function initReportsPage() {
    const d = document.getElementById('reportDate');
    if (d) d.value = new Date().toISOString().split('T')[0];
}

function switchReportsTab(tab) {
    document.querySelectorAll('.reports-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.reports-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.reports-tab[data-tab="${tab}"]`)?.classList.add('active');
    document.getElementById(`tab-${tab}`)?.classList.add('active');
}

function openReportModal(childName, childId, childReport, sharedReportId = 0, existingReportId = 0, notes = '', recs = '', rDate = '') {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.classList.add('active');
        document.getElementById('reportChildName').textContent = childName || '—';
        document.getElementById('reportChildId').value = childId || '';
        document.getElementById('reportChildReport').value = childReport || '';
        document.getElementById('doctorNotes').value = notes || '';
        document.getElementById('recommendations').value = recs || '';
        if (rDate) {
            document.getElementById('reportDate').value = rDate;
        } else {
            const d = document.getElementById('reportDate');
            if (d) d.value = new Date().toISOString().split('T')[0];
        }
        
        let hiddenSharedId = document.getElementById('sharedReportId');
        if (!hiddenSharedId) {
            hiddenSharedId = document.createElement('input');
            hiddenSharedId.type = 'hidden';
            hiddenSharedId.id = 'sharedReportId';
            document.getElementById('doctorReportForm').appendChild(hiddenSharedId);
        }
        hiddenSharedId.value = sharedReportId;

        let hiddenReportId = document.getElementById('existingReportId');
        if (!hiddenReportId) {
            hiddenReportId = document.createElement('input');
            hiddenReportId.type = 'hidden';
            hiddenReportId.id = 'existingReportId';
            document.getElementById('doctorReportForm').appendChild(hiddenReportId);
        }
        hiddenReportId.value = existingReportId;
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) { modal.classList.remove('active'); document.getElementById('doctorReportForm')?.reset(); const d = document.getElementById('reportDate'); if (d) d.value = new Date().toISOString().split('T')[0]; }
}

function submitDoctorReport(e) {
    e.preventDefault();
    const sharedReportIdEl = document.getElementById('sharedReportId');
    const existingReportIdEl = document.getElementById('existingReportId');
    const data = {
        action: 'submit_report', specialist_id: SPECIALIST_ID,
        doctor_report_id: existingReportIdEl ? existingReportIdEl.value : 0,
        child_id: document.getElementById('reportChildId').value,
        child_report: document.getElementById('reportChildReport').value,
        doctor_notes: document.getElementById('doctorNotes').value,
        recommendations: document.getElementById('recommendations').value,
        report_date: document.getElementById('reportDate').value,
        shared_report_id: sharedReportIdEl ? sharedReportIdEl.value : 0
    };
    fetch('doctor-dashboard.php?ajax=1&section=reports', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    }).then(r => r.json()).then(result => {
        if (result.success) { closeReportModal(); showToast('Report saved successfully!', 'success'); loadReportsData(); }
        else showToast('Error: ' + (result.error || 'Failed to save'), 'error');
    }).catch(err => { 
        console.error(err);
        showToast('Server connection error. Please try again.', 'error'); 
    });
}

// ═══════════════════════════════════════════════════
// SHARED REPORT ACTIONS — View / Download / Reply
// ═══════════════════════════════════════════════════
function viewSharedReport(filePath, reportType) {
    if (!filePath) { showToast('No report file available', 'error'); return; }
    window.open(filePath, '_blank');
}

function downloadSharedReport(filePath, reportType) {
    if (!filePath) { showToast('No report file available', 'error'); return; }
    const a = document.createElement('a');
    a.href = filePath;
    a.download = 'report_' + reportType + '_' + Date.now() + '.pdf';
    a.target = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}



// ═══════════════════════════════════════════════════
// APPOINTMENTS PAGE
// ═══════════════════════════════════════════════════
function getAppointmentsView() {
    setTimeout(() => loadAppointmentsData(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Appointments</h1>
            <p class="dashboard-subtitle" id="appointmentsSubtitle">Manage your schedule and patient appointments</p>
        </div></div>
        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-total-appts">--</div><div class="stat-card-label">Total</div></div></div>
            <div class="stat-card stat-card-green"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-upcoming-appts">--</div><div class="stat-card-label">Upcoming</div></div></div>
            <div class="stat-card stat-card-yellow"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-completed-appts">--</div><div class="stat-card-label">Completed</div></div></div>
            <div class="stat-card stat-card-purple"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="stat-week-appts">--</div><div class="stat-card-label">This Week</div></div></div>
        </div>
        <div class="reports-tabs">
            <button class="reports-tab active" data-tab="all" onclick="filterAppointments('')">All</button>
            <button class="reports-tab" data-tab="scheduled" onclick="filterAppointments('scheduled')">Upcoming</button>
            <button class="reports-tab" data-tab="completed" onclick="filterAppointments('completed')">Completed</button>
            <button class="reports-tab" data-tab="cancelled" onclick="filterAppointments('cancelled')">Cancelled</button>
        </div>
        <div class="section-card"><div class="section-card-header"><h2 class="section-heading">Appointment List</h2>
            <input type="text" class="search-input" placeholder="Search appointments..." oninput="searchAppointmentsLocal(this.value)"></div>
        <div class="patients-list" id="appointmentsListContainer"><div style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading appointments...</div></div></div></div>`;
}

let allAppointmentsCache = [];
function loadAppointmentsData(statusFilter) {
    let url = `doctor-dashboard.php?ajax=1&section=appointments&action=get_appointments&specialist_id=${SPECIALIST_ID}`;
    if (statusFilter) url += `&status=${statusFilter}`;
    fetch(url).then(r => r.json()).then(result => {
        if (result.success) { allAppointmentsCache = result.data || []; renderAppointmentsList(allAppointmentsCache); if (result.counts) updateAppointmentStats(result.counts); }
        else renderAppointmentsEmpty();
    }).catch(() => renderAppointmentsEmpty());
}

function renderAppointmentsList(appointments) {
    const container = document.getElementById('appointmentsListContainer');
    if (!container) return;
    if (!appointments.length) { renderAppointmentsEmpty(); return; }
    let html = '';
    appointments.forEach(a => {
        const parentName = `${a.parent_first_name} ${a.parent_last_name}`;
        const date = a.scheduled_at ? new Date(a.scheduled_at) : null;
        const dateStr = date ? date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : 'No date';
        const timeStr = date ? date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) : '';
        const status = a.status || 'scheduled';
        const statusClass = status === 'completed' ? 'status-green' : (status === 'cancelled' ? 'status-red' : 'status-yellow');
        const typeIcon = a.type === 'online' ? '🖥' : '🏥';
        const typeLabel = a.type === 'online' ? 'Online' : 'On-site';
        const initials = (a.parent_first_name?.charAt(0) || '') + (a.parent_last_name?.charAt(0) || '');
        const isActive = status !== 'completed' && status !== 'cancelled';
        html += `<div class="patient-row">
            <div class="patient-avatar">${initials}</div>
            <div class="patient-info"><div class="patient-name">${parentName}</div>
                <div class="patient-details">${a.children_names || 'No children listed'} • ${typeIcon} ${typeLabel}</div></div>
            <div class="patient-status ${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</div>
            <div class="patient-last-update">${dateStr}${timeStr ? ' at ' + timeStr : ''}</div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                ${a.type === 'online' && isActive ? `<button class="btn btn-sm btn-join-meeting" onclick="joinMeeting(${a.appointment_id})">Join Meeting</button>` : ''}
                ${isActive ? `<button class="btn btn-sm btn-gradient" onclick="updateAppointmentStatus(${a.appointment_id},'completed')">Complete</button>` : ''}
                ${isActive ? `<button class="btn btn-sm btn-outline" style="color:var(--red-500);border-color:var(--red-500);" onclick="cancelAppointment(${a.appointment_id})">Cancel</button>` : ''}
                <button class="btn btn-sm btn-outline" style="color:var(--green-500);border-color:var(--green-500);" onclick="chatWithParent(${a.parent_id})">Chat</button>
            </div></div>`;
    });
    container.innerHTML = html;
}

function renderAppointmentsEmpty() {
    const c = document.getElementById('appointmentsListContainer');
    if (c) c.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);">No appointments found.</div>';
}

function updateAppointmentStats(counts) {
    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val ?? 0; };
    el('stat-total-appts', counts.total); el('stat-upcoming-appts', counts.upcoming);
    el('stat-completed-appts', counts.completed); el('stat-week-appts', counts.this_week);
}

function filterAppointments(status) {
    document.querySelectorAll('.reports-tabs .reports-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.reports-tab[data-tab="${status || 'all'}"]`)?.classList.add('active');
    loadAppointmentsData(status);
}

function searchAppointmentsLocal(query) {
    const q = query.toLowerCase();
    if (!q) { renderAppointmentsList(allAppointmentsCache); return; }
    renderAppointmentsList(allAppointmentsCache.filter(a =>
        `${a.parent_first_name} ${a.parent_last_name}`.toLowerCase().includes(q) ||
        (a.children_names || '').toLowerCase().includes(q)
    ));
}

function joinMeeting(appointmentId) {
    window.open('https://meet.google.com/new', '_blank');
    showToast('Opening meeting room...', 'success');
}

function updateAppointmentStatus(appointmentId, newStatus) {
    fetch('doctor-dashboard.php?ajax=1&section=appointments', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_appointment', appointment_id: appointmentId, status: newStatus })
    }).then(r => r.json()).then(result => {
        if (result.success) { showToast('Appointment updated!', 'success'); loadAppointmentsData(); }
        else showToast('Failed to update: ' + (result.error || ''), 'error');
    }).catch(() => showToast('Connection error', 'error'));
}

function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    fetch('doctor-dashboard.php?ajax=1&section=appointments', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel_appointment', appointment_id: appointmentId })
    }).then(r => r.json()).then(result => {
        if (result.success) { showToast('Appointment cancelled', 'success'); loadAppointmentsData(); }
        else showToast('Failed: ' + (result.error || ''), 'error');
    }).catch(() => showToast('Connection error', 'error'));
}

function getSettingsView() {
    const name = (typeof SESSION_DOCTOR_NAME !== 'undefined') ? SESSION_DOCTOR_NAME : 'Doctor';
    const email = (typeof SESSION_DOCTOR_EMAIL !== 'undefined') ? SESSION_DOCTOR_EMAIL : '';
    const spec = (typeof SESSION_SPECIALIZATION !== 'undefined') ? SESSION_SPECIALIZATION : 'Specialist';
    setTimeout(() => initSettingsPage(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Settings</h1>
            <p class="dashboard-subtitle">Manage your account preferences and profile</p>
        </div></div>
        <div class="settings-layout">
            <div class="settings-sidebar">
                <div class="settings-profile-card">
                    <div class="settings-avatar doctor-avatar">${name.split(' ').filter(w=>w).map(w=>w[0]).slice(0,2).join('').toUpperCase()}</div>
                    <div class="settings-profile-name">${name}</div>
                    <div class="settings-profile-role">${spec}</div>
                    <div class="settings-profile-email">${email}</div>
                </div>
                <nav class="settings-nav">
                    <button class="settings-nav-item active" data-settings-tab="account" onclick="switchSettingsTab('account', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Account
                    </button>
                    <button class="settings-nav-item" data-settings-tab="notifications" onclick="switchSettingsTab('notifications', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Notifications
                    </button>
                    <button class="settings-nav-item" data-settings-tab="preferences" onclick="switchSettingsTab('preferences', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Preferences
                    </button>
                    <button class="settings-nav-item" data-settings-tab="security" onclick="switchSettingsTab('security', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Security
                    </button>
                </nav>
                <div style="margin-top:1.5rem;">
                    <a href="dr-settings.php" class="btn btn-outline" style="width:100%;justify-content:center;display:flex;gap:0.5rem;align-items:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Full Profile
                    </a>
                </div>
            </div>
            <div class="settings-content">
                <!-- Account Tab -->
                <div class="settings-tab-panel active" id="settings-tab-account">
                    <div class="settings-section">
                        <h2 class="settings-section-title">Account Information</h2>
                        <p class="settings-section-subtitle">Your practitioner details and contact information</p>
                        <div class="settings-form">
                            <div class="settings-field-group">
                                <label class="settings-label">Full Name</label>
                                <input type="text" class="settings-input" id="settings-name" value="${name}" placeholder="Your full name">
                            </div>
                            <div class="settings-field-group">
                                <label class="settings-label">Email Address</label>
                                <input type="email" class="settings-input" id="settings-email" value="${email}" placeholder="Email address">
                            </div>
                            <div class="settings-field-group">
                                <label class="settings-label">Specialization</label>
                                <input type="text" class="settings-input" id="settings-specialization" value="${spec}" placeholder="e.g. Pediatrician">
                            </div>
                            <div class="settings-field-group">
                                <label class="settings-label">Phone Number</label>
                                <input type="tel" class="settings-input" id="settings-phone" placeholder="+1 (555) 000-0000">
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button class="btn btn-gradient" onclick="saveAccountSettings()">Save Changes</button>
                        </div>
                    </div>
                    <div id="dr-profile-section"><div class="settings-section" style="text-align:center;padding:2rem;color:var(--text-secondary);font-size:0.9rem;">Loading profile…</div></div>
                    <div class="settings-section settings-danger-zone">
                        <h2 class="settings-section-title danger">Danger Zone</h2>
                        <p class="settings-section-subtitle">Irreversible and destructive actions</p>
                        <button class="btn btn-outline" style="color:var(--red-500);border-color:var(--red-500);" onclick="showToast('Please contact admin to delete your account', 'error')">
                            Delete Account
                        </button>
                    </div>
                </div>
                <!-- Notifications Tab -->
                <div class="settings-tab-panel" id="settings-tab-notifications">
                    <div class="settings-section">
                        <h2 class="settings-section-title">Notification Preferences</h2>
                        <p class="settings-section-subtitle">Control how and when you receive notifications</p>
                        <div class="settings-toggles">
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">New Appointment</div><div class="settings-toggle-desc">Alert when a parent books an appointment</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="notif-appointment" checked><span class="settings-toggle-slider"></span></label>
                            </div>
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">New Message</div><div class="settings-toggle-desc">Alert when a parent sends a message</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="notif-message" checked><span class="settings-toggle-slider"></span></label>
                            </div>
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">Report Shared</div><div class="settings-toggle-desc">Alert when a parent shares a child report</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="notif-report" checked><span class="settings-toggle-slider"></span></label>
                            </div>
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">Email Notifications</div><div class="settings-toggle-desc">Receive summaries to your email</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="notif-email"><span class="settings-toggle-slider"></span></label>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button class="btn btn-gradient" onclick="showToast('Notification preferences saved!', 'success')">Save Preferences</button>
                        </div>
                    </div>
                </div>
                <!-- Preferences Tab -->
                <div class="settings-tab-panel" id="settings-tab-preferences">
                    <div class="settings-section">
                        <h2 class="settings-section-title">Application Preferences</h2>
                        <p class="settings-section-subtitle">Customize your dashboard experience</p>
                        <div class="settings-toggles">
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">Dark Mode</div><div class="settings-toggle-desc">Use dark theme across the dashboard</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="pref-dark" onchange="toggleTheme()"><span class="settings-toggle-slider"></span></label>
                            </div>
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">Auto-refresh Messages</div><div class="settings-toggle-desc">Automatically poll new messages every 5 seconds</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="pref-autopolling" checked><span class="settings-toggle-slider"></span></label>
                            </div>
                            <div class="settings-toggle-row">
                                <div><div class="settings-toggle-label">Compact View</div><div class="settings-toggle-desc">Show more content with a denser layout</div></div>
                                <label class="settings-toggle-switch"><input type="checkbox" id="pref-compact"><span class="settings-toggle-slider"></span></label>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button class="btn btn-gradient" onclick="showToast('Preferences saved!', 'success')">Save Preferences</button>
                        </div>
                    </div>
                </div>
                <!-- Security Tab -->
                <div class="settings-tab-panel" id="settings-tab-security">
                    <div class="settings-section">
                        <h2 class="settings-section-title">Change Password</h2>
                        <p class="settings-section-subtitle">Update your login credentials</p>
                        <div class="settings-form">
                            <div class="settings-field-group">
                                <label class="settings-label">Current Password</label>
                                <input type="password" class="settings-input" id="settings-current-pw" placeholder="••••••••">
                            </div>
                            <div class="settings-field-group">
                                <label class="settings-label">New Password</label>
                                <input type="password" class="settings-input" id="settings-new-pw" placeholder="Min. 8 characters">
                            </div>
                            <div class="settings-field-group">
                                <label class="settings-label">Confirm New Password</label>
                                <input type="password" class="settings-input" id="settings-confirm-pw" placeholder="Repeat new password">
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button class="btn btn-gradient" onclick="changePassword()">Update Password</button>
                        </div>
                    </div>
                    <div class="settings-section">
                        <h2 class="settings-section-title">Active Sessions</h2>
                        <p class="settings-section-subtitle">You are currently logged in on this device.</p>
                        <button class="btn btn-outline" onclick="handleLogout()">Sign Out All Devices</button>
                    </div>
                </div>
            </div>
        </div></div>`;
}

function initSettingsPage() {
    const darkPref = document.getElementById('pref-dark');
    if (darkPref) darkPref.checked = document.documentElement.getAttribute('data-theme') === 'dark';
    loadProfileData();
}

function switchSettingsTab(tab, btn) {
    document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.settings-tab-panel').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const panel = document.getElementById(`settings-tab-${tab}`);
    if (panel) panel.classList.add('active');
}

function saveAccountSettings() {
    const fullName = (document.getElementById('settings-name')?.value || '').trim();
    const email = (document.getElementById('settings-email')?.value || '').trim();
    const spec = (document.getElementById('settings-specialization')?.value || '').trim();
    if (!fullName || !email) { showToast('Name and email are required', 'error'); return; }
    const parts = fullName.replace(/^Dr\.?\s*/i, '').split(' ');
    const first_name = parts[0] || '';
    const last_name = parts.slice(1).join(' ') || '';
    fetch('doctor-dashboard.php?ajax=1&section=settings', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save_profile', first_name, last_name, email, specialization: spec, experience_years: 0, certificate_of_experience: '' })
    }).then(r => r.json()).then(res => {
        if (res.success) showToast('Account settings saved successfully!', 'success');
        else showToast(res.error || 'Failed to save', 'error');
    }).catch(() => showToast('Connection error', 'error'));
}

function changePassword() {
    const cur = document.getElementById('settings-current-pw')?.value;
    const nw = document.getElementById('settings-new-pw')?.value;
    const conf = document.getElementById('settings-confirm-pw')?.value;
    if (!cur || !nw || !conf) { showToast('Please fill all password fields', 'error'); return; }
    if (nw !== conf) { showToast('New passwords do not match', 'error'); return; }
    if (nw.length < 6) { showToast('Password must be at least 6 characters', 'error'); return; }
    fetch('doctor-dashboard.php?ajax=1&section=settings', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'change_password', current_password: cur, new_password: nw })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            showToast('Password updated successfully!', 'success');
            document.getElementById('settings-current-pw').value = '';
            document.getElementById('settings-new-pw').value = '';
            document.getElementById('settings-confirm-pw').value = '';
        } else showToast(res.error || 'Password change failed', 'error');
    }).catch(() => showToast('Connection error', 'error'));
}

// ── My Profile (embedded in Settings) ──────────────────
function loadProfileData() {
    const sec = document.getElementById('dr-profile-section');
    fetch('dr-settings.php?ajax=1&action=get_profile')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                renderProfileSection(res.data);
            } else {
                if (sec) sec.innerHTML = `<div class="settings-section"><p style="color:var(--text-secondary);padding:1rem 0;">Profile data unavailable: ${res.error || 'Not found'}. Make sure your account has a linked specialist record.</p></div>`;
            }
        })
        .catch(err => {
            if (sec) sec.innerHTML = `<div class="settings-section"><p style="color:var(--text-secondary);padding:1rem 0;">Could not load profile. Please refresh and try again.</p></div>`;
        });
}

function renderProfileSection(d) {
    const initials = ((d.first_name||'').charAt(0) + (d.last_name||'').charAt(0)).toUpperCase();
    const fullName = ('Dr. ' + (d.first_name||'') + ' ' + (d.last_name||'')).trim();
    const spec = d.specialization || '';
    const activeSlots = (d.slots || []).map(s => parseInt(s.day_of_week));
    const startTime = d.slots && d.slots[0] ? (d.slots[0].start_time||'09:00').substring(0,5) : '09:00';
    const endTime   = d.slots && d.slots[0] ? (d.slots[0].end_time||'17:00').substring(0,5) : '17:00';
    const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const daysHtml = dayNames.map((day,i) => `<div class="dr-day-checkbox"><input type="checkbox" id="dd-day-${i}" value="${i}" ${activeSlots.includes(i)?'checked':''}><label for="dd-day-${i}">${day}</label></div>`).join('');
    const specVals = ['pediatrician','child-psychiatrist','developmental-pediatrician','neurologist','speech-therapist','occupational-therapist','behavioral-therapist','psychologist','other'];
    const specLabels = ['Pediatrician','Child Psychiatrist','Developmental Pediatrician','Pediatric Neurologist','Speech-Language Pathologist','Occupational Therapist','Behavioral Therapist','Child Psychologist','Other'];
    const specOpts = specVals.map((v,i) => `<option value="${v}" ${spec===v?'selected':''}>${specLabels[i]}</option>`).join('');

    const html = `<div class="settings-section" style="padding:0;background:none;box-shadow:none;border:none;">
        <div class="dr-profile-photo-section">
            <div class="dr-avatar-wrapper" onclick="document.getElementById('dd-photo-upload').click()" title="Change profile photo">
                <div class="dr-avatar-large" id="dd-avatar-display">${initials}</div>
                <div class="dr-avatar-overlay"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
                <input type="file" id="dd-photo-upload" accept="image/*" style="display:none;">
            </div>
            <div class="dr-profile-info">
                <h2>${fullName}</h2>
                <p class="dr-specialty-text">${spec}</p>
                <p class="dr-verified-text"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Verified Healthcare Provider</p>
            </div>
        </div>
        <form class="dr-profile-form" id="dd-profile-form" novalidate>
            <div class="dr-form-section">
                <div class="dr-form-section-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><h3 class="dr-form-section-title">Personal Information</h3></div>
                <div class="dr-form-grid">
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-fullname">Full Name <span class="required">*</span></label><input type="text" id="dd-fullname" class="dr-form-input" value="${fullName}" required></div>
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-email">Email Address <span class="required">*</span></label><input type="email" id="dd-email" class="dr-form-input" value="${d.email||''}" required></div>
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-phone">Phone Number</label><input type="tel" id="dd-phone" class="dr-form-input" value="${d.phone||''}"></div>
                </div>
                <button type="button" class="dr-password-toggle-btn" id="dd-toggle-pw-btn" onclick="toggleDrPasswordFields()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Change Password</button>
                <div class="dr-password-fields" id="dd-password-fields">
                    <div class="dr-form-grid">
                        <div class="dr-form-group"><label class="dr-form-label" for="dd-cur-pw">Current Password</label><input type="password" id="dd-cur-pw" class="dr-form-input" placeholder="Current password"></div>
                        <div class="dr-form-group"><label class="dr-form-label" for="dd-new-pw">New Password</label><input type="password" id="dd-new-pw" class="dr-form-input" placeholder="New password"></div>
                        <div class="dr-form-group"><label class="dr-form-label" for="dd-confirm-pw">Confirm Password</label><input type="password" id="dd-confirm-pw" class="dr-form-input" placeholder="Confirm password"></div>
                    </div>
                </div>
            </div>
            <div class="dr-form-section">
                <div class="dr-form-section-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg><h3 class="dr-form-section-title">Professional Information</h3></div>
                <div class="dr-form-grid">
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-specialty">Specialty <span class="required">*</span></label><select id="dd-specialty" class="dr-form-select" required onchange="handleDrSpecialtyChange()">${specOpts}</select><input type="text" id="dd-specialty-other" class="dr-form-input" placeholder="Enter your specialty" style="display:none;margin-top:0.5rem;"></div>
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-experience">Years of Experience <span class="required">*</span></label><input type="number" id="dd-experience" class="dr-form-input" value="${d.experience_years||0}" min="0" max="60" required></div>
                    <div class="dr-form-group full-width"><label class="dr-form-label" for="dd-cert">Certifications</label><input type="text" id="dd-cert" class="dr-form-input" value="${d.certificate_of_experience||''}" placeholder="e.g. MD, FAAP, Board Certified"></div>
                    <div class="dr-form-group full-width"><label class="dr-form-label" for="dd-bio">Bio</label><textarea id="dd-bio" class="dr-form-input dr-form-textarea" placeholder="Write a short bio about your practice…"></textarea></div>
                </div>
            </div>
            <div class="dr-form-section">
                <div class="dr-form-section-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg><h3 class="dr-form-section-title">Clinic Information</h3><span class="dr-readonly-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span></div>
                <div class="dr-form-grid">
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-clinic-name">Clinic Name</label><input type="text" id="dd-clinic-name" class="dr-form-input readonly" value="${d.clinic_name||''}" readonly></div>
                    <div class="dr-form-group"><label class="dr-form-label" for="dd-clinic-loc">Clinic Location</label><input type="text" id="dd-clinic-loc" class="dr-form-input readonly" value="${d.clinic_location||''}" readonly></div>
                </div>
            </div>
            <div class="dr-form-section">
                <div class="dr-form-section-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><h3 class="dr-form-section-title">Availability Settings</h3></div>
                <label class="dr-form-label" style="margin-bottom:0.75rem;display:block;">Working Days</label>
                <div class="dr-days-grid">${daysHtml}</div>
                <label class="dr-form-label" style="margin:1rem 0 0.75rem;display:block;">Working Hours</label>
                <div class="dr-hours-row"><label for="dd-start-time">From</label><input type="time" id="dd-start-time" class="dr-time-input" value="${startTime}"><span class="dr-hours-separator">—</span><label for="dd-end-time">To</label><input type="time" id="dd-end-time" class="dr-time-input" value="${endTime}"></div>
                <label class="dr-form-label" style="margin:1.25rem 0 0.5rem;display:block;">Consultation Types</label>
                <div class="dr-consult-types">
                    <div class="dr-consult-toggle"><input type="checkbox" id="dd-consult-online" checked><label for="dd-consult-online"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14"/><rect x="1" y="6" width="14" height="12" rx="2" ry="2"/></svg> Online</label></div>
                    <div class="dr-consult-toggle"><input type="checkbox" id="dd-consult-onsite" checked><label for="dd-consult-onsite"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> On-site</label></div>
                </div>
            </div>
            <div class="dr-form-actions">
                <button type="button" class="btn btn-outline" onclick="loadProfileData()">Reset</button>
                <button type="submit" class="btn btn-gradient">Save Changes</button>
            </div>
        </form>
    </div>`;

    const sec = document.getElementById('dr-profile-section');
    if (sec) sec.innerHTML = html;

    const photoInput = document.getElementById('dd-photo-upload');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { showToast('Image must be smaller than 5MB', 'error'); return; }
            const reader = new FileReader();
            reader.onload = ev => { const av = document.getElementById('dd-avatar-display'); if (av) av.innerHTML = `<img src="${ev.target.result}" alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`; };
            reader.readAsDataURL(file);
        });
    }
    const form = document.getElementById('dd-profile-form');
    if (form) form.addEventListener('submit', submitDrProfileForm);
    handleDrSpecialtyChange();
}

function handleDrSpecialtyChange() {
    const sel = document.getElementById('dd-specialty');
    const other = document.getElementById('dd-specialty-other');
    if (!sel || !other) return;
    other.style.display = sel.value === 'other' ? 'block' : 'none';
    if (sel.value !== 'other') other.value = '';
}

function toggleDrPasswordFields() {
    const fields = document.getElementById('dd-password-fields');
    const btn = document.getElementById('dd-toggle-pw-btn');
    if (!fields) return;
    fields.classList.toggle('visible');
    if (fields.classList.contains('visible')) {
        if (btn) btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel Password Change';
        document.getElementById('dd-cur-pw')?.focus();
    } else {
        if (btn) btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Change Password';
        ['dd-cur-pw','dd-new-pw','dd-confirm-pw'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    }
}

function submitDrProfileForm(e) {
    e.preventDefault();
    const nameParts = (document.getElementById('dd-fullname')?.value||'').trim().replace(/^Dr\.\s*/i,'').split(' ');
    const first_name = nameParts[0]||'';
    const last_name  = nameParts.slice(1).join(' ')||'';
    const email = (document.getElementById('dd-email')?.value||'').trim();
    const phone = (document.getElementById('dd-phone')?.value||'').trim();
    const sel   = document.getElementById('dd-specialty');
    const other = document.getElementById('dd-specialty-other');
    const specialization = (sel?.value === 'other' ? other?.value : sel?.value)||'';
    const experience_years = parseInt(document.getElementById('dd-experience')?.value||0);
    const certificate_of_experience = (document.getElementById('dd-cert')?.value||'').trim();

    if (!first_name || !email) { showToast('Name and email are required', 'error'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Please enter a valid email', 'error'); return; }

    const pwVisible = document.getElementById('dd-password-fields')?.classList.contains('visible');
    if (pwVisible) {
        const cur = document.getElementById('dd-cur-pw')?.value;
        const nw  = document.getElementById('dd-new-pw')?.value;
        const conf= document.getElementById('dd-confirm-pw')?.value;
        if (!cur || !nw) { showToast('Enter current and new password', 'error'); return; }
        if (nw.length < 6) { showToast('Password must be at least 6 characters', 'error'); return; }
        if (nw !== conf) { showToast('Passwords do not match', 'error'); return; }
        fetch('dr-settings.php?ajax=1&action=change_password', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({current_password:cur, new_password:nw})
        }).then(r=>r.json()).then(res => {
            if (res.success) { showToast('Password changed!','success'); toggleDrPasswordFields(); }
            else showToast(res.error||'Password change failed','error');
        }).catch(()=>showToast('Connection error','error'));
    }

    fetch('dr-settings.php?ajax=1&action=save_profile', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({first_name, last_name, email, phone, specialization, experience_years, certificate_of_experience})
    }).then(r=>r.json()).then(res => {
        if (res.success) showToast('Profile saved successfully!','success');
        else showToast(res.error||'Save failed','error');
    }).catch(()=>showToast('Connection error','error'));

    const selectedDays = [];
    for (let i=0;i<=6;i++) { if (document.getElementById(`dd-day-${i}`)?.checked) selectedDays.push(i); }
    const startTime = document.getElementById('dd-start-time')?.value;
    const endTime   = document.getElementById('dd-end-time')?.value;
    if (selectedDays.length > 0 && startTime && endTime) {
        fetch('dr-settings.php?ajax=1&action=save_slots', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({days:selectedDays, start_time:startTime, end_time:endTime, slot_duration:30})
        }).catch(()=>{});
    }
}

function handleLogout() {
    // Remove existing modal if any
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
    // Trigger entrance animation
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

// ═══════════════════════════════════════════════════
// MESSAGES PAGE — Database-driven
// ═══════════════════════════════════════════════════
let currentPartnerId = null;

function getMessagesView() {
    setTimeout(() => loadConversations(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Messages</h1>
            <p class="dashboard-subtitle">Communicate with patients and parents</p>
        </div></div>
        <div class="messages-container">
            <div class="conversation-list">
                <div class="conversation-list-header">
                    <input type="text" class="conversation-search" placeholder="Search conversations..." oninput="filterConversations(this.value)">
                </div>
                <div class="conversation-items" id="conversationItems">
                    <div style="text-align:center;padding:2rem;color:var(--text-secondary);font-size:0.875rem;">Loading conversations...</div>
                </div>
            </div>
            <div class="chat-window" id="chatWindow">
                <div class="chat-header" id="chatHeader">
                    <div class="chat-header-info">
                        <div class="conversation-avatar chat-header-avatar">?</div>
                        <div><div class="chat-header-name">Select a conversation</div>
                        <div class="chat-header-detail">Choose a parent from the list to start chatting</div></div>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-secondary);">
                        <div style="text-align:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:3rem;height:3rem;margin-bottom:1rem;opacity:0.5;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <p>Select a conversation to view messages</p>
                        </div>
                    </div>
                </div>
                <div class="chat-input-bar">
                    <div class="chat-input-wrapper">
                        <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1" onkeydown="handleChatKeydown(event)"></textarea>
                        <button class="chat-send-btn" onclick="sendMessage()" title="Send message">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div></div>`;
}

let allConversationsCache = [];

function loadConversations() {
    fetch(`doctor-dashboard.php?ajax=1&section=messages&action=get_conversations&user_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('conversationItems');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                allConversationsCache = result.data;
                renderConversationList(result.data);
                selectConversationById(result.data[0].partner_id);
            } else {
                container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);font-size:0.875rem;">No conversations yet.</div>';
            }
        }).catch(() => {});
    if (messagesPollInterval) clearInterval(messagesPollInterval);
    messagesPollInterval = setInterval(() => {
        if (currentPartnerId) loadChatMessages(currentPartnerId, true);
    }, 5000);
}

function renderConversationList(conversations) {
    const container = document.getElementById('conversationItems');
    if (!container) return;
    container.innerHTML = conversations.map(c => {
        const initials = (c.partner_first_name?.charAt(0) || '') + (c.partner_last_name?.charAt(0) || '');
        const time = c.last_message_time ? formatMessageTime(c.last_message_time) : '';
        const preview = c.last_message ? (c.last_message.length > 35 ? c.last_message.substring(0, 35) + '...' : c.last_message) : 'No messages';
        const unread = parseInt(c.unread_count) || 0;
        const isActive = currentPartnerId == c.partner_id;
        return `<div class="conversation-item ${isActive ? 'active' : ''}" data-partner="${c.partner_id}" onclick="selectConversationById(${c.partner_id})">
            <div class="conversation-avatar">${initials}</div>
            <div class="conversation-info">
                <div class="conversation-name-row"><span class="conversation-name">${c.partner_first_name} ${c.partner_last_name}</span><span class="conversation-time">${time}</span></div>
                <div class="conversation-preview-row"><span class="conversation-preview">${preview}</span>${unread > 0 ? `<span class="conversation-unread">${unread}</span>` : ''}</div>
            </div></div>`;
    }).join('');
}

function formatMessageTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return date.toLocaleDateString('en-US', { weekday: 'short' });
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function selectConversationById(partnerId) {
    currentPartnerId = partnerId;
    lastMessageId = 0; // Reset message tracker for new conversation
    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
    document.querySelector(`.conversation-item[data-partner="${partnerId}"]`)?.classList.add('active');
    const activeItem = document.querySelector(`.conversation-item[data-partner="${partnerId}"]`);
    const badge = activeItem?.querySelector('.conversation-unread');
    if (badge) badge.remove();
    const conv = allConversationsCache.find(c => c.partner_id == partnerId);
    if (conv) {
        const header = document.getElementById('chatHeader');
        if (header) {
            const initials = (conv.partner_first_name?.charAt(0) || '') + (conv.partner_last_name?.charAt(0) || '');
            header.querySelector('.chat-header-info').innerHTML = `
                <div class="conversation-avatar chat-header-avatar">${initials}</div>
                <div><div class="chat-header-name">${conv.partner_first_name} ${conv.partner_last_name}</div>
                <div class="chat-header-detail">${conv.partner_role === 'parent' ? 'Parent' : conv.partner_role || 'User'}</div></div>`;
        }
    }
    loadChatMessages(partnerId);
    fetch('doctor-dashboard.php?ajax=1&section=messages', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_read', user_id: SPECIALIST_ID, partner_id: partnerId })
    }).catch(() => {});
}

let lastMessageId = 0;

function loadChatMessages(partnerId, silent) {
    fetch(`doctor-dashboard.php?ajax=1&section=messages&action=get_messages&user_id=${SPECIALIST_ID}&partner_id=${partnerId}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('chatMessages');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                if (silent && lastMessageId > 0) {
                    // Only append truly new messages (avoid full re-render)
                    const newMsgs = result.data.filter(m => m.message_id > lastMessageId);
                    if (newMsgs.length > 0) {
                        const isAtBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < 30;
                        newMsgs.forEach(m => {
                            const isSent = m.sender_id == SPECIALIST_ID;
                            const time = new Date(m.sent_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                            const fileHtml = m.file_path ? `<div class="message-attachment"><a href="${m.file_path}" target="_blank" style="color:inherit;text-decoration:underline;">📎 Attachment</a></div>` : '';
                            const bubble = document.createElement('div');
                            bubble.className = `message-bubble ${isSent ? 'message-sent' : 'message-received'}`;
                            bubble.innerHTML = `<div class="message-content">${m.content}${fileHtml}</div><div class="message-time">${time}</div>`;
                            container.appendChild(bubble);
                        });
                        lastMessageId = Math.max(...result.data.map(m => m.message_id));
                        if (isAtBottom) container.scrollTop = container.scrollHeight;
                    }
                } else {
                    // Full render (first load or non-silent)
                    let html = '<div class="chat-date-divider"><span>Conversation</span></div>';
                    result.data.forEach(m => {
                        const isSent = m.sender_id == SPECIALIST_ID;
                        const time = new Date(m.sent_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                        const fileHtml = m.file_path ? `<div class="message-attachment"><a href="${m.file_path}" target="_blank" style="color:inherit;text-decoration:underline;">📎 Attachment</a></div>` : '';
                        html += `<div class="message-bubble ${isSent ? 'message-sent' : 'message-received'}">
                            <div class="message-content">${m.content}${fileHtml}</div>
                            <div class="message-time">${time}</div></div>`;
                    });
                    container.innerHTML = html;
                    lastMessageId = Math.max(...result.data.map(m => m.message_id));
                    container.scrollTop = container.scrollHeight;
                }
            } else if (!silent) {
                container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-secondary);"><p>No messages yet. Start the conversation!</p></div>';
                lastMessageId = 0;
            }
        }).catch(() => {});
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input?.value.trim();
    if (!text || !currentPartnerId) return;
    const container = document.getElementById('chatMessages');
    if (container) {
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble message-sent';
        bubble.innerHTML = `<div class="message-content">${text}</div><div class="message-time">Just now</div>`;
        container.appendChild(bubble);
        container.scrollTop = container.scrollHeight;
    }
    const activeItem = document.querySelector(`.conversation-item[data-partner="${currentPartnerId}"]`);
    if (activeItem) {
        const preview = activeItem.querySelector('.conversation-preview');
        if (preview) preview.textContent = text.length > 35 ? text.substring(0, 35) + '...' : text;
        const timeEl = activeItem.querySelector('.conversation-time');
        if (timeEl) timeEl.textContent = 'Just now';
    }
    input.value = '';
    input.style.height = 'auto';
    fetch('doctor-dashboard.php?ajax=1&section=messages', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send_message', sender_id: SPECIALIST_ID, receiver_id: currentPartnerId, content: text })
    }).then(r => r.json()).then(result => {
        if (!result.success) showToast('Failed to send message', 'error');
    }).catch(() => {});
}

function handleChatKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function filterConversations(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.conversation-item').forEach(item => {
        const name = item.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
        item.style.display = name.includes(q) ? '' : 'none';
    });
}

// ═══════════════════════════════════════════════════
// ANALYTICS PAGE — With Chart.js
// ═══════════════════════════════════════════════════
function getAnalyticsView() {
    setTimeout(() => loadAnalyticsData(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Analytics</h1>
            <p class="dashboard-subtitle">Practice insights and patient statistics</p>
        </div></div>
        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="analytics-patients">--</div><div class="stat-card-label">Total Patients</div></div></div>
            <div class="stat-card stat-card-green"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="analytics-reports">--</div><div class="stat-card-label">Reports Written</div></div></div>
            <div class="stat-card stat-card-purple"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="analytics-week">--</div><div class="stat-card-label">This Week</div></div></div>
            <div class="stat-card stat-card-yellow"><div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div><div class="stat-card-info"><div class="stat-card-value" id="analytics-rating">--</div><div class="stat-card-label">Avg Rating</div></div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
            <div class="section-card" style="padding:1.5rem;">
                <h3 style="margin-bottom:1rem;">Monthly Appointment Trend</h3>
                <div class="chart-container"><canvas id="monthlyTrendChart"></canvas></div>
            </div>
            <div class="section-card" style="padding:1.5rem;">
                <h3 style="margin-bottom:1rem;">Appointment Distribution</h3>
                <div class="chart-container"><canvas id="appointmentPieChart"></canvas></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div class="section-card" style="padding:1.5rem;">
                <h3 style="margin-bottom:1rem;">Appointment Overview</h3>
                <div id="analytics-appt-overview" style="color:var(--text-secondary);">Loading...</div>
            </div>
            <div class="section-card" style="padding:1.5rem;">
                <h3 style="margin-bottom:1rem;">Activity This Month</h3>
                <div id="analytics-monthly" style="color:var(--text-secondary);">Loading...</div>
            </div>
        </div></div>`;
}

let trendChartInstance = null;
let pieChartInstance = null;

function loadAnalyticsData() {
    fetch(`doctor-dashboard.php?ajax=1&section=analytics&action=get_analytics&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            if (!result.success || !result.data) return;
            const d = result.data;
            const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
            el('analytics-patients', d.total_patients);
            el('analytics-reports', d.total_reports);
            el('analytics-week', d.appointments_this_week);
            el('analytics-rating', d.avg_rating ? d.avg_rating + ' ★' : 'N/A');
            const overview = document.getElementById('analytics-appt-overview');
            if (overview) {
                overview.innerHTML = `<div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <div class="analytics-row"><span>Total Appointments</span><strong>${d.total_appointments}</strong></div>
                    <div class="analytics-row"><span>Completed</span><strong style="color:var(--green-500);">${d.completed_appointments}</strong></div>
                    <div class="analytics-row"><span>Upcoming</span><strong style="color:var(--blue-500);">${d.upcoming_appointments}</strong></div>
                    <div class="analytics-row"><span>Cancelled</span><strong style="color:var(--red-500);">${d.cancelled_appointments}</strong></div>
                    <div class="analytics-row"><span>Reviews</span><strong>${d.total_reviews}</strong></div>
                    ${d.avg_rating ? `<div class="analytics-row"><span>Rating</span><strong class="star-rating">${renderStars(d.avg_rating)}</strong></div>` : ''}
                </div>`;
            }
            const monthly = document.getElementById('analytics-monthly');
            if (monthly) {
                monthly.innerHTML = `<div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <div class="analytics-row"><span>Appointments</span><strong>${d.appointments_this_month}</strong></div>
                    <div class="analytics-row"><span>Reports Written</span><strong>${d.reports_this_month}</strong></div>
                    <div class="analytics-row"><span>Messages Sent</span><strong>${d.messages_this_month}</strong></div>
                    <div class="analytics-row"><span>Total Messages</span><strong>${d.total_messages}</strong></div>
                </div>`;
            }
            renderMonthlyTrendChart(d.monthly_trend || []);
            renderAppointmentPieChart(d.completed_appointments, d.upcoming_appointments, d.cancelled_appointments);
        }).catch(() => {});
}

function renderStars(rating) {
    const full = Math.floor(rating);
    const half = rating - full >= 0.5;
    let html = '';
    for (let i = 0; i < 5; i++) {
        if (i < full) html += '<span class="star star-full">★</span>';
        else if (i === full && half) html += '<span class="star star-half">★</span>';
        else html += '<span class="star star-empty">☆</span>';
    }
    return html + ` <span style="font-size:0.85rem;color:var(--text-secondary);">${rating}/5</span>`;
}

function renderMonthlyTrendChart(data) {
    const ctx = document.getElementById('monthlyTrendChart');
    if (!ctx) return;
    if (trendChartInstance) trendChartInstance.destroy();
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#e2e8f0' : '#475569';
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    const labels = data.map(d => {
        const [y, m] = d.month.split('-');
        return new Date(y, m - 1).toLocaleDateString('en-US', { month: 'short' });
    });
    const values = data.map(d => d.count);
    trendChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels.length ? labels : ['No data'], datasets: [{ label: 'Appointments', data: values.length ? values : [0], backgroundColor: 'rgba(59, 130, 246, 0.7)', borderColor: 'rgba(59, 130, 246, 1)', borderWidth: 2, borderRadius: 8, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } }, x: { ticks: { color: textColor }, grid: { display: false } } } }
    });
}

function renderAppointmentPieChart(completed, upcoming, cancelled) {
    const ctx = document.getElementById('appointmentPieChart');
    if (!ctx) return;
    if (pieChartInstance) pieChartInstance.destroy();
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const total = (completed || 0) + (upcoming || 0) + (cancelled || 0);
    pieChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: ['Completed', 'Upcoming', 'Cancelled'], datasets: [{ data: total > 0 ? [completed || 0, upcoming || 0, cancelled || 0] : [1], backgroundColor: total > 0 ? ['rgba(34, 197, 94, 0.8)', 'rgba(59, 130, 246, 0.8)', 'rgba(239, 68, 68, 0.8)'] : ['rgba(148, 163, 184, 0.3)'], borderColor: isDark ? '#1a1a24' : '#ffffff', borderWidth: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { color: isDark ? '#e2e8f0' : '#475569', padding: 16, usePointStyle: true, pointStyleWidth: 12 } } } }
    });
}

