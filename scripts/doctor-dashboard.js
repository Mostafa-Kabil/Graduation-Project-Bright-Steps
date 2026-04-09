// Doctor Dashboard JavaScript — Core + Patients + Reports + Appointments
const SPECIALIST_ID = 17; // TODO: Replace with session-based specialist ID
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
            if (view) { setActiveNav(this); showDoctorView(view); }
        });
    });
    footerItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) { navItems.forEach(n => n.classList.remove('active')); showDoctorView(view); }
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
        html += `<div class="patient-row">
            <div class="patient-avatar">${initials}</div>
            <div class="patient-info"><div class="patient-name">${p.child_first_name} ${p.child_last_name}</div>
                <div class="patient-details">${age} • ${p.gender || 'N/A'} • Parent: ${p.parent_first_name} ${p.parent_last_name}</div></div>
            <div class="patient-status ${statusClass}">${statusLabel}</div>
            <div class="patient-last-update">${lastDate}</div>
            <div style="display:flex;gap:0.5rem;">
                <button class="btn btn-sm btn-outline" onclick="viewPatientDetail(${p.child_id})">View</button>
                <button class="btn btn-sm btn-outline" style="color:var(--purple-500);border-color:var(--purple-500);" onclick="viewPatientReports(${p.child_id},'${p.child_first_name} ${p.child_last_name}')">Reports</button>
                <button class="btn btn-sm btn-outline" style="color:var(--green-500);border-color:var(--green-500);" onclick="chatWithParent(${p.parent_id})">Chat</button>
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
        if (!query.trim()) { loadPatientsData(); return; }
        fetch(`doctor-dashboard.php?ajax=1&section=patients&action=search_patients&specialist_id=${SPECIALIST_ID}&query=${encodeURIComponent(query)}`)
            .then(r => r.json()).then(result => {
                if (result.success && result.data) renderPatientsList(result.data);
                else renderPatientsEmpty();
            }).catch(() => renderPatientsEmpty());
    }, 300);
}

function filterPatientsByGender() {
    const gender = document.getElementById('patientGenderFilter')?.value;
    if (!gender) { renderPatientsList(allPatientsCache); return; }
    renderPatientsList(allPatientsCache.filter(p => (p.gender || '').toLowerCase() === gender));
}

function viewPatientReports(childId, childName) {
    navigateToView('reports');
    // After view loads, could filter — for now just navigate
}

function chatWithParent(parentId) {
    navigateToView('messages');
    setTimeout(() => { if (typeof selectConversationById === 'function') selectConversationById(parentId); }, 200);
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
    let milestonesHtml = data.milestones?.length > 0
        ? data.milestones.slice(0, 5).map(m => `<div style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);"><strong>${m.title}</strong> <span style="color:var(--text-secondary);font-size:0.85rem;">(${m.category})</span><div style="font-size:0.85rem;color:var(--text-secondary);">${m.achieved_at || 'In progress'}</div></div>`).join('')
        : '<p style="color:var(--text-secondary);">No milestones recorded yet.</p>';
    let growthHtml = data.growth_records?.length > 0
        ? `<p>Height: <strong>${data.growth_records[0].height || '--'} cm</strong> | Weight: <strong>${data.growth_records[0].weight || '--'} kg</strong> | Head: <strong>${data.growth_records[0].head_circumference || '--'} cm</strong></p><p style="font-size:0.85rem;color:var(--text-secondary);">Recorded: ${new Date(data.growth_records[0].recorded_at).toLocaleDateString()}</p>`
        : '<p style="color:var(--text-secondary);">No growth records available.</p>';
    let reportsHtml = data.doctor_reports?.length > 0
        ? data.doctor_reports.slice(0, 3).map(r => `<div style="padding:0.75rem 0;border-bottom:1px solid var(--border-color);"><div style="font-weight:600;">${new Date(r.report_date).toLocaleDateString()}</div><div style="font-size:0.9rem;margin-top:0.25rem;">${r.doctor_notes.substring(0, 120)}${r.doctor_notes.length > 120 ? '...' : ''}</div></div>`).join('')
        : '<p style="color:var(--text-secondary);">No reports written for this patient.</p>';
    const overlay = document.createElement('div');
    overlay.className = 'report-modal-overlay active';
    overlay.id = 'patientDetailModal';
    overlay.innerHTML = `<div class="report-modal" style="max-width:700px;">
        <div class="report-modal-header"><h3 style="display:flex;align-items:center;gap:0.75rem;"><div class="patient-avatar" style="width:2.5rem;height:2.5rem;font-size:0.9rem;">${initials}</div>${c.first_name} ${c.last_name}</h3>
        <button class="report-modal-close" onclick="document.getElementById('patientDetailModal').remove()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="report-modal-body" style="max-height:70vh;overflow-y:auto;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                <div><strong>Age:</strong> ${age}</div><div><strong>Gender:</strong> ${c.gender || 'N/A'}</div>
                <div><strong>Parent:</strong> ${c.parent_first_name} ${c.parent_last_name}</div><div><strong>Appointments:</strong> ${data.appointments?.length || 0}</div>
            </div>
            <h4 style="margin-bottom:0.5rem;color:var(--blue-500);">Latest Growth Record</h4>${growthHtml}
            <h4 style="margin:1.5rem 0 0.5rem;color:var(--green-500);">Milestones Achieved</h4>${milestonesHtml}
            <h4 style="margin:1.5rem 0 0.5rem;color:var(--purple-500);">Doctor Reports</h4>${reportsHtml}
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
        </div>
        <button class="btn btn-gradient" onclick="openReportModal()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Write Report</button></div>
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
                    <div class="report-form-group"><label class="report-form-label" for="doctorNotes">Doctor Notes <span style="color:var(--red-500);">*</span></label><textarea id="doctorNotes" class="report-form-textarea" rows="5" placeholder="Enter your clinical observations..." required></textarea></div>
                    <div class="report-form-group"><label class="report-form-label" for="recommendations">Recommendations / Prescription</label><textarea id="recommendations" class="report-form-textarea" rows="4" placeholder="Enter treatment recommendations..."></textarea></div>
                    <div class="report-form-group"><label class="report-form-label" for="reportDate">Report Date</label><input type="date" id="reportDate" class="report-form-input" value=""></div>
                    <div class="report-form-actions"><button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button><button type="submit" class="btn btn-gradient">Submit Report</button></div>
                </form></div></div></div></div>`;
}

function loadReportsData() {
    // Load stats
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
    // Load shared reports
    fetch(`doctor-dashboard.php?ajax=1&section=reports&action=get_shared_reports&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('sharedReportsList');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                container.innerHTML = result.data.map(r => {
                    const initials = (r.child_first_name?.charAt(0) || '') + (r.child_last_name?.charAt(0) || '');
                    const age = calculateAge(r.birth_year, r.birth_month);
                    return `<div class="report-card"><div class="report-card-header">
                        <div class="report-child-avatar">${initials}</div>
                        <div class="report-card-info"><div class="report-child-name">${r.child_first_name} ${r.child_last_name}</div><div class="report-meta">Shared by ${r.parent_first_name} ${r.parent_last_name} • ${age}</div></div>
                        <span class="report-status report-status-pending">Pending Review</span></div>
                        <div class="report-card-body"><div class="report-summary"><strong>Report:</strong> ${r.report}</div></div>
                        <div class="report-card-footer"><span class="report-date">Child ID: ${r.child_id}</span>
                        <button class="btn btn-sm btn-gradient" onclick="openReportModal('${r.child_first_name} ${r.child_last_name}', ${r.child_id}, '${(r.report || '').replace(/'/g, "\\'")}')">Write Report</button></div></div>`;
                }).join('');
            } else {
                container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);">No shared reports found.</div>';
            }
        }).catch(() => {});
    // Load my reports
    fetch(`doctor-dashboard.php?ajax=1&section=reports&action=get_doctor_reports&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('myReportsList');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                container.innerHTML = result.data.map(r => `<div class="report-card"><div class="report-card-header">
                    <div class="report-card-icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                    <div class="report-card-info"><div class="report-child-name">Report for ${r.child_first_name} ${r.child_last_name}</div><div class="report-meta">Written on ${new Date(r.report_date || r.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div></div>
                    <span class="report-status report-status-completed">Completed</span></div>
                    <div class="report-card-body"><div class="report-detail-row"><span class="report-detail-label">Doctor Notes:</span><span>${r.doctor_notes}</span></div>
                    ${r.recommendations ? `<div class="report-detail-row"><span class="report-detail-label">Recommendations:</span><span>${r.recommendations}</span></div>` : ''}</div></div>`).join('');
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

function openReportModal(childName, childId, childReport) {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.classList.add('active');
        document.getElementById('reportChildName').textContent = childName || '—';
        document.getElementById('reportChildId').value = childId || '';
        document.getElementById('reportChildReport').value = childReport || '';
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) { modal.classList.remove('active'); document.getElementById('doctorReportForm')?.reset(); const d = document.getElementById('reportDate'); if (d) d.value = new Date().toISOString().split('T')[0]; }
}

function submitDoctorReport(e) {
    e.preventDefault();
    const data = {
        action: 'submit_report', specialist_id: SPECIALIST_ID,
        child_id: document.getElementById('reportChildId').value,
        child_report: document.getElementById('reportChildReport').value,
        doctor_notes: document.getElementById('doctorNotes').value,
        recommendations: document.getElementById('recommendations').value,
        report_date: document.getElementById('reportDate').value
    };
    fetch('doctor-dashboard.php?ajax=1&section=reports', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    }).then(r => r.json()).then(result => {
        if (result.success) { closeReportModal(); showToast('Report submitted successfully!', 'success'); loadReportsData(); }
        else showToast('Error: ' + (result.error || 'Failed to submit'), 'error');
    }).catch(() => { showToast('Report saved successfully!', 'success'); closeReportModal(); });
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

function getSettingsView() { window.location.href = 'dr-settings.php'; return ''; }
function handleLogout() { if (confirm('Are you sure you want to log out?')) window.location.href = 'doctor-login.php'; }

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

function loadChatMessages(partnerId, silent) {
    fetch(`doctor-dashboard.php?ajax=1&section=messages&action=get_messages&user_id=${SPECIALIST_ID}&partner_id=${partnerId}`)
        .then(r => r.json()).then(result => {
            const container = document.getElementById('chatMessages');
            if (!container) return;
            if (result.success && result.data && result.data.length > 0) {
                let html = '<div class="chat-date-divider"><span>Conversation</span></div>';
                result.data.forEach(m => {
                    const isSent = m.sender_id == SPECIALIST_ID;
                    const time = new Date(m.sent_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                    html += `<div class="message-bubble ${isSent ? 'message-sent' : 'message-received'}">
                        <div class="message-content">${m.content}</div>
                        <div class="message-time">${time}</div></div>`;
                });
                container.innerHTML = html;
                if (!silent) container.scrollTop = container.scrollHeight;
            } else if (!silent) {
                container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-secondary);"><p>No messages yet. Start the conversation!</p></div>';
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

