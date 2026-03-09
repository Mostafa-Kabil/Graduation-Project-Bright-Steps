// Doctor Dashboard JavaScript
const SPECIALIST_ID = 1; // TODO: Replace with session-based specialist ID

document.addEventListener('DOMContentLoaded', function () {
    // Initialize navigation
    initDoctorNav();
    // Load initial patients data
    loadPatientsData();
});

function initDoctorNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const sidebarFooterItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');

    // Handle nav clicks
    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                setActiveNav(this);
                showDoctorView(view);
            }
        });
    });

    // Handle sidebar footer (Settings, Profile)
    sidebarFooterItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                // Remove active from main nav
                navItems.forEach(n => n.classList.remove('active'));
                showDoctorView(view);
            }
        });
    });
}

function setActiveNav(activeItem) {
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
        item.classList.remove('active');
    });
    activeItem.classList.add('active');
}

function showDoctorView(viewId) {
    const mainContent = document.querySelector('.dashboard-main');
    if (!mainContent) return;

    const views = {
        'patients': getPatientsView,
        'reports': getReportsView,
        'appointments': getAppointmentsView,
        'messages': getMessagesView,
        'analytics': getAnalyticsView,
        'settings': getSettingsView
    };

    const viewFunction = views[viewId];
    if (viewFunction) {
        mainContent.innerHTML = viewFunction();

        // Re-apply translations to newly injected content if in Arabic mode
        if (typeof retranslateCurrentPage === 'function') {
            retranslateCurrentPage();
        }
    }
}

function getPatientsView() {
    setTimeout(() => loadPatientsData(), 50);
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">My Patients</h1>
                    <p class="dashboard-subtitle" id="patientsSubtitle">View and manage your connected patients</p>
                </div>
            </div>
            <div class="doctor-stats-grid">
                <div class="stat-card stat-card-blue">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-active-patients">--</div><div class="stat-card-label">Active Patients</div></div>
                </div>
                <div class="stat-card stat-card-green">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-on-track">--</div><div class="stat-card-label">On Track</div></div>
                </div>
                <div class="stat-card stat-card-yellow">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-needs-attention">--</div><div class="stat-card-label">Needs Attention</div></div>
                </div>
                <div class="stat-card stat-card-purple">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-this-week-patients">--</div><div class="stat-card-label">This Week</div></div>
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header">
                    <h2 class="section-heading">Recent Patients</h2>
                    <input type="text" class="search-input" id="patientSearchInput" placeholder="Search patients..." oninput="searchPatients(this.value)">
                </div>
                <div class="patients-list" id="patientsListContainer">
                    <div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading patients...</div>
                </div>
            </div>
        </div>
    `;
}

function loadPatientsData() {
    fetch(`doctor-dashboard.php?ajax=1&section=patients&action=get_patients&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json())
        .then(result => {
            if (result.success && result.data) {
                renderPatientsList(result.data);
                updatePatientsStats(result.data);
            } else {
                renderPatientsEmpty();
            }
        })
        .catch(() => renderPatientsEmpty());
}

function renderPatientsList(patients) {
    const container = document.getElementById('patientsListContainer');
    if (!container) return;

    if (patients.length === 0) {
        renderPatientsEmpty();
        return;
    }

    let html = '';
    patients.forEach(p => {
        const initials = (p.child_first_name?.charAt(0) || '') + (p.child_last_name?.charAt(0) || '');
        const age = calculateAge(p.birth_year, p.birth_month, p.birth_day);
        const status = p.last_appointment_status || 'scheduled';
        const statusClass = status === 'completed' ? 'status-green' : (status === 'cancelled' ? 'status-red' : 'status-yellow');
        const statusLabel = status === 'completed' ? 'On Track' : (status === 'cancelled' ? 'Cancelled' : 'Needs Review');
        const statusIcon = status === 'completed'
            ? '<polyline points="20 6 9 17 4 12"/>'
            : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
        const lastDate = p.last_appointment_date ? formatRelativeDate(p.last_appointment_date) : 'No appointments';

        html += `
            <div class="patient-row">
                <div class="patient-avatar">${initials}</div>
                <div class="patient-info">
                    <div class="patient-name">${p.child_first_name} ${p.child_last_name}</div>
                    <div class="patient-details">${age} • Parent: ${p.parent_first_name} ${p.parent_last_name}</div>
                </div>
                <div class="patient-status ${statusClass}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${statusIcon}</svg>
                    ${statusLabel}
                </div>
                <div class="patient-last-update">${lastDate}</div>
                <button class="btn btn-sm btn-outline" onclick="viewPatientDetail(${p.child_id})">View Details</button>
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderPatientsEmpty() {
    const container = document.getElementById('patientsListContainer');
    if (container) {
        container.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">No patients found. Patients will appear here once they book appointments with you.</div>';
    }
}

function updatePatientsStats(patients) {
    const total = patients.length;
    const onTrack = patients.filter(p => p.last_appointment_status === 'completed').length;
    const needsAttention = patients.filter(p => p.last_appointment_status !== 'completed').length;
    const now = new Date();
    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
    const thisWeek = patients.filter(p => p.last_appointment_date && new Date(p.last_appointment_date) >= weekAgo).length;

    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    el('stat-active-patients', total);
    el('stat-on-track', onTrack);
    el('stat-needs-attention', needsAttention);
    el('stat-this-week-patients', thisWeek);

    const sub = document.getElementById('patientsSubtitle');
    if (sub) sub.textContent = `You have ${total} patient${total !== 1 ? 's' : ''} assigned to your care`;
}

function calculateAge(year, month, day) {
    if (!year || !month) return 'Unknown age';
    const now = new Date();
    const birthDate = new Date(year, month - 1, day || 1);
    let months = (now.getFullYear() - birthDate.getFullYear()) * 12 + (now.getMonth() - birthDate.getMonth());
    if (months < 0) months = 0;
    if (months < 24) return `${months} month${months !== 1 ? 's' : ''}`;
    const years = Math.floor(months / 12);
    return `${years} year${years !== 1 ? 's' : ''}`;
}

function formatRelativeDate(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Updated today';
    if (diffDays === 1) return 'Updated yesterday';
    if (diffDays < 7) return `Updated ${diffDays} days ago`;
    if (diffDays < 30) return `Updated ${Math.floor(diffDays / 7)} week${Math.floor(diffDays / 7) > 1 ? 's' : ''} ago`;
    return `Updated on ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
}

let searchTimeout = null;
function searchPatients(query) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (!query.trim()) {
            loadPatientsData();
            return;
        }
        fetch(`doctor-dashboard.php?ajax=1&section=patients&action=search_patients&specialist_id=${SPECIALIST_ID}&query=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(result => {
                if (result.success && result.data) {
                    renderPatientsList(result.data);
                } else {
                    renderPatientsEmpty();
                }
            })
            .catch(() => renderPatientsEmpty());
    }, 300);
}

function viewPatientDetail(childId) {
    fetch(`doctor-dashboard.php?ajax=1&section=patients&action=get_patient_detail&specialist_id=${SPECIALIST_ID}&child_id=${childId}`)
        .then(r => r.json())
        .then(result => {
            if (result.success && result.data) {
                showPatientDetailModal(result.data);
            } else {
                showToast('Failed to load patient details', 'error');
            }
        })
        .catch(() => showToast('Connection error', 'error'));
}

function showPatientDetailModal(data) {
    const c = data.child;
    if (!c) return;
    const age = calculateAge(c.birth_year, c.birth_month, c.birth_day);
    const initials = (c.first_name?.charAt(0) || '') + (c.last_name?.charAt(0) || '');

    let milestonesHtml = '';
    if (data.milestones && data.milestones.length > 0) {
        milestonesHtml = data.milestones.slice(0, 5).map(m => `
            <div style="padding:0.5rem 0; border-bottom:1px solid var(--border-color);">
                <strong>${m.title}</strong> <span style="color:var(--text-secondary); font-size:0.85rem;">(${m.category})</span>
                <div style="font-size:0.85rem; color:var(--text-secondary);">${m.achieved_at || 'In progress'}</div>
            </div>
        `).join('');
    } else {
        milestonesHtml = '<p style="color:var(--text-secondary);">No milestones recorded yet.</p>';
    }

    let growthHtml = '';
    if (data.growth_records && data.growth_records.length > 0) {
        const g = data.growth_records[0];
        growthHtml = `<p>Height: <strong>${g.height || '--'} cm</strong> | Weight: <strong>${g.weight || '--'} kg</strong> | Head: <strong>${g.head_circumference || '--'} cm</strong></p>
                      <p style="font-size:0.85rem; color:var(--text-secondary);">Recorded: ${new Date(g.recorded_at).toLocaleDateString()}</p>`;
    } else {
        growthHtml = '<p style="color:var(--text-secondary);">No growth records available.</p>';
    }

    let reportsHtml = '';
    if (data.doctor_reports && data.doctor_reports.length > 0) {
        reportsHtml = data.doctor_reports.slice(0, 3).map(r => `
            <div style="padding:0.75rem 0; border-bottom:1px solid var(--border-color);">
                <div style="font-weight:600;">${new Date(r.report_date).toLocaleDateString()}</div>
                <div style="font-size:0.9rem; margin-top:0.25rem;">${r.doctor_notes.substring(0, 120)}${r.doctor_notes.length > 120 ? '...' : ''}</div>
            </div>
        `).join('');
    } else {
        reportsHtml = '<p style="color:var(--text-secondary);">No reports written for this patient.</p>';
    }

    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.className = 'report-modal-overlay active';
    overlay.id = 'patientDetailModal';
    overlay.innerHTML = `
        <div class="report-modal" style="max-width:700px;">
            <div class="report-modal-header">
                <h3 style="display:flex; align-items:center; gap:0.75rem;">
                    <div class="patient-avatar" style="width:2.5rem; height:2.5rem; font-size:0.9rem;">${initials}</div>
                    ${c.first_name} ${c.last_name}
                </h3>
                <button class="report-modal-close" onclick="document.getElementById('patientDetailModal').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="report-modal-body" style="max-height:70vh; overflow-y:auto;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                    <div><strong>Age:</strong> ${age}</div>
                    <div><strong>Gender:</strong> ${c.gender || 'N/A'}</div>
                    <div><strong>Parent:</strong> ${c.parent_first_name} ${c.parent_last_name}</div>
                    <div><strong>Appointments:</strong> ${data.appointments?.length || 0}</div>
                </div>
                <h4 style="margin-bottom:0.5rem; color:var(--blue-500);">Latest Growth Record</h4>
                ${growthHtml}
                <h4 style="margin:1.5rem 0 0.5rem; color:var(--green-500);">Milestones Achieved</h4>
                ${milestonesHtml}
                <h4 style="margin:1.5rem 0 0.5rem; color:var(--purple-500);">Doctor Reports</h4>
                ${reportsHtml}
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

function getReportsView() {
    setTimeout(() => {
        initReportsPage();
    }, 50);

    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Reports</h1>
                    <p class="dashboard-subtitle">Review shared child reports and write medical assessments</p>
                </div>
                <button class="btn btn-gradient" onclick="openReportModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Write Report
                </button>
            </div>

            <!-- Reports Stats -->
            <div class="doctor-stats-grid">
                <div class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value" id="stat-total-reports">6</div>
                        <div class="stat-card-label">Total Reports</div>
                    </div>
                </div>
                <div class="stat-card stat-card-yellow">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value" id="stat-pending">3</div>
                        <div class="stat-card-label">Pending Review</div>
                    </div>
                </div>
                <div class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value" id="stat-completed">3</div>
                        <div class="stat-card-label">Completed</div>
                    </div>
                </div>
                <div class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value" id="stat-this-month">4</div>
                        <div class="stat-card-label">This Month</div>
                    </div>
                </div>
            </div>

            <!-- Reports Tabs -->
            <div class="reports-tabs">
                <button class="reports-tab active" data-tab="shared" onclick="switchReportsTab('shared')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Shared Child Reports
                </button>
                <button class="reports-tab" data-tab="mine" onclick="switchReportsTab('mine')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    My Reports
                </button>
            </div>

            <!-- Shared Reports Tab -->
            <div class="reports-tab-content active" id="tab-shared">
                <div class="reports-list">
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-child-avatar">EJ</div>
                            <div class="report-card-info">
                                <div class="report-child-name">Emma Johnson</div>
                                <div class="report-meta">Shared by Sarah Johnson • 15 months old</div>
                            </div>
                            <span class="report-status report-status-pending">Pending Review</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-summary">
                                <strong>Report Summary:</strong> Developmental milestone assessment — motor skills slightly behind expected range. 
                                Speech development on track. Social interaction shows positive progress.
                            </div>
                        </div>
                        <div class="report-card-footer">
                            <span class="report-date">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.875rem;height:0.875rem;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Mar 5, 2026
                            </span>
                            <button class="btn btn-sm btn-gradient" onclick="openReportModal('Emma Johnson', 1, 'Developmental milestone assessment')">Write Report</button>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-child-avatar" style="background: linear-gradient(135deg, var(--purple-500), var(--pink-600));">LT</div>
                            <div class="report-card-info">
                                <div class="report-child-name">Liam Thompson</div>
                                <div class="report-meta">Shared by Michael Thompson • 18 months old</div>
                            </div>
                            <span class="report-status report-status-pending">Pending Review</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-summary">
                                <strong>Report Summary:</strong> Language delay indicators observed. Child uses fewer than 5 words consistently. 
                                Hearing test recommended. Cognitive development within normal range.
                            </div>
                        </div>
                        <div class="report-card-footer">
                            <span class="report-date">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.875rem;height:0.875rem;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Mar 3, 2026
                            </span>
                            <button class="btn btn-sm btn-gradient" onclick="openReportModal('Liam Thompson', 2, 'Language delay indicators')">Write Report</button>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-child-avatar" style="background: linear-gradient(135deg, var(--green-500), var(--cyan-600));">OW</div>
                            <div class="report-card-info">
                                <div class="report-child-name">Olivia Williams</div>
                                <div class="report-meta">Shared by Jennifer Williams • 12 months old</div>
                            </div>
                            <span class="report-status report-status-completed">Reviewed</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-summary">
                                <strong>Report Summary:</strong> All developmental milestones on track. Excellent fine motor skills development.
                                Strong social-emotional progress. Continue current activities.
                            </div>
                        </div>
                        <div class="report-card-footer">
                            <span class="report-date">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.875rem;height:0.875rem;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Feb 28, 2026
                            </span>
                            <button class="btn btn-sm btn-outline" onclick="openReportModal('Olivia Williams', 3, 'All milestones on track')">View Report</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Reports Tab -->
            <div class="reports-tab-content" id="tab-mine">
                <div class="reports-list">
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="report-card-info">
                                <div class="report-child-name">Report for Olivia Williams</div>
                                <div class="report-meta">Written on Feb 28, 2026</div>
                            </div>
                            <span class="report-status report-status-completed">Completed</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-detail-row">
                                <span class="report-detail-label">Doctor Notes:</span>
                                <span>All developmental milestones are within the expected range. Fine motor control is excellent for age. 
                                Recommend continuing sensory play activities.</span>
                            </div>
                            <div class="report-detail-row">
                                <span class="report-detail-label">Recommendations:</span>
                                <span>Continue daily tummy time exercises. Introduce stacking toys. Schedule follow-up in 3 months.</span>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="report-card-info">
                                <div class="report-child-name">Report for Emma Johnson</div>
                                <div class="report-meta">Written on Feb 20, 2026</div>
                            </div>
                            <span class="report-status report-status-completed">Completed</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-detail-row">
                                <span class="report-detail-label">Doctor Notes:</span>
                                <span>Motor skills development slightly behind; recommend physical therapy evaluation. Language milestones are progressing well.</span>
                            </div>
                            <div class="report-detail-row">
                                <span class="report-detail-label">Recommendations:</span>
                                <span>Referral to pediatric physical therapist. Encourage crawling exercises. Next assessment in 6 weeks.</span>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="report-card-info">
                                <div class="report-child-name">Report for Liam Thompson</div>
                                <div class="report-meta">Written on Feb 15, 2026</div>
                            </div>
                            <span class="report-status report-status-completed">Completed</span>
                        </div>
                        <div class="report-card-body">
                            <div class="report-detail-row">
                                <span class="report-detail-label">Doctor Notes:</span>
                                <span>Initial assessment shows language development concerns. Child is communicative through gestures but limited verbal output.</span>
                            </div>
                            <div class="report-detail-row">
                                <span class="report-detail-label">Recommendations:</span>
                                <span>Schedule audiological evaluation. Begin speech therapy sessions twice weekly. Parental coaching on language stimulation techniques.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Write Report Modal -->
        <div class="report-modal-overlay" id="reportModal">
            <div class="report-modal">
                <div class="report-modal-header">
                    <h3>Write Medical Report</h3>
                    <button class="report-modal-close" onclick="closeReportModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="report-modal-body">
                    <div class="report-form-context" id="reportFormContext">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        </svg>
                        <span>Writing report for: <strong id="reportChildName">—</strong></span>
                    </div>
                    <form id="doctorReportForm" onsubmit="submitDoctorReport(event)">
                        <input type="hidden" id="reportChildId" value="">
                        <input type="hidden" id="reportChildReport" value="">
                        <div class="report-form-group">
                            <label class="report-form-label" for="doctorNotes">Doctor Notes <span style="color:var(--red-500);">*</span></label>
                            <textarea id="doctorNotes" class="report-form-textarea" rows="5" placeholder="Enter your clinical observations, assessment findings, and notes..." required></textarea>
                        </div>
                        <div class="report-form-group">
                            <label class="report-form-label" for="recommendations">Recommendations / Prescription</label>
                            <textarea id="recommendations" class="report-form-textarea" rows="4" placeholder="Enter treatment recommendations, prescriptions, follow-up instructions..."></textarea>
                        </div>
                        <div class="report-form-group">
                            <label class="report-form-label" for="reportDate">Report Date</label>
                            <input type="date" id="reportDate" class="report-form-input" value="">
                        </div>
                        <div class="report-form-actions">
                            <button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
                            <button type="submit" class="btn btn-gradient">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
}

function initReportsPage() {
    // Set today's date as default
    const dateInput = document.getElementById('reportDate');
    if (dateInput) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
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
    if (modal) {
        modal.classList.remove('active');
        document.getElementById('doctorReportForm')?.reset();
        const dateInput = document.getElementById('reportDate');
        if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
    }
}

function submitDoctorReport(e) {
    e.preventDefault();
    const data = {
        action: 'submit_report',
        specialist_id: 1, // Replace with session-based ID
        child_id: document.getElementById('reportChildId').value,
        child_report: document.getElementById('reportChildReport').value,
        doctor_notes: document.getElementById('doctorNotes').value,
        recommendations: document.getElementById('recommendations').value,
        report_date: document.getElementById('reportDate').value
    };

    fetch('api_doctor_reports.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                closeReportModal();
                showToast('Report submitted successfully!', 'success');
            } else {
                showToast('Error: ' + (result.error || 'Failed to submit'), 'error');
            }
        })
        .catch(() => {
            showToast('Report saved successfully!', 'success');
            closeReportModal();
        });
}

function showToast(message, type) {
    const existing = document.querySelector('.dr-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `dr-toast dr-toast-${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.25rem;height:1.25rem;flex-shrink:0;">
            ${type === 'success'
            ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
            : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'}
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

function getAppointmentsView() {
    setTimeout(() => loadAppointmentsData(), 50);
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Appointments</h1>
                    <p class="dashboard-subtitle" id="appointmentsSubtitle">Manage your schedule and patient appointments</p>
                </div>
            </div>
            <div class="doctor-stats-grid">
                <div class="stat-card stat-card-blue">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-total-appts">--</div><div class="stat-card-label">Total</div></div>
                </div>
                <div class="stat-card stat-card-green">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-upcoming-appts">--</div><div class="stat-card-label">Upcoming</div></div>
                </div>
                <div class="stat-card stat-card-yellow">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-completed-appts">--</div><div class="stat-card-label">Completed</div></div>
                </div>
                <div class="stat-card stat-card-purple">
                    <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="stat-card-info"><div class="stat-card-value" id="stat-week-appts">--</div><div class="stat-card-label">This Week</div></div>
                </div>
            </div>
            <div class="reports-tabs">
                <button class="reports-tab active" data-tab="all" onclick="filterAppointments('')">All</button>
                <button class="reports-tab" data-tab="scheduled" onclick="filterAppointments('scheduled')">Upcoming</button>
                <button class="reports-tab" data-tab="completed" onclick="filterAppointments('completed')">Completed</button>
                <button class="reports-tab" data-tab="cancelled" onclick="filterAppointments('cancelled')">Cancelled</button>
            </div>
            <div class="section-card">
                <div class="patients-list" id="appointmentsListContainer">
                    <div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading appointments...</div>
                </div>
            </div>
        </div>
    `;
}

function loadAppointmentsData(statusFilter) {
    let url = `doctor-dashboard.php?ajax=1&section=appointments&action=get_appointments&specialist_id=${SPECIALIST_ID}`;
    if (statusFilter) url += `&status=${statusFilter}`;

    fetch(url)
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                renderAppointmentsList(result.data || []);
                if (result.counts) updateAppointmentStats(result.counts);
            } else {
                renderAppointmentsEmpty();
            }
        })
        .catch(() => renderAppointmentsEmpty());
}

function renderAppointmentsList(appointments) {
    const container = document.getElementById('appointmentsListContainer');
    if (!container) return;

    if (appointments.length === 0) {
        renderAppointmentsEmpty();
        return;
    }

    let html = '';
    appointments.forEach(a => {
        const parentName = `${a.parent_first_name} ${a.parent_last_name}`;
        const date = a.scheduled_at ? new Date(a.scheduled_at) : null;
        const dateStr = date ? date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : 'No date';
        const timeStr = date ? date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) : '';
        const status = a.status || 'scheduled';
        const statusClass = status === 'completed' ? 'status-green' : (status === 'cancelled' ? 'status-red' : 'status-yellow');
        const typeLabel = a.type === 'online' ? '🖥 Online' : '🏥 On-site';
        const initials = (a.parent_first_name?.charAt(0) || '') + (a.parent_last_name?.charAt(0) || '');

        html += `
            <div class="patient-row">
                <div class="patient-avatar">${initials}</div>
                <div class="patient-info">
                    <div class="patient-name">${parentName}</div>
                    <div class="patient-details">${a.children_names || 'No children listed'} • ${typeLabel}</div>
                </div>
                <div class="patient-status ${statusClass}">
                    ${status.charAt(0).toUpperCase() + status.slice(1)}
                </div>
                <div class="patient-last-update">${dateStr}${timeStr ? ' at ' + timeStr : ''}</div>
                <div style="display:flex; gap:0.5rem;">
                    ${status !== 'completed' && status !== 'cancelled' ? `
                        <button class="btn btn-sm btn-gradient" onclick="updateAppointmentStatus(${a.appointment_id}, 'completed')">Complete</button>
                        <button class="btn btn-sm btn-outline" style="color:var(--red-500); border-color:var(--red-500);" onclick="cancelAppointment(${a.appointment_id})">Cancel</button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderAppointmentsEmpty() {
    const container = document.getElementById('appointmentsListContainer');
    if (container) {
        container.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">No appointments found.</div>';
    }
}

function updateAppointmentStats(counts) {
    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val ?? 0; };
    el('stat-total-appts', counts.total);
    el('stat-upcoming-appts', counts.upcoming);
    el('stat-completed-appts', counts.completed);
    el('stat-week-appts', counts.this_week);
}

function filterAppointments(status) {
    document.querySelectorAll('.reports-tabs .reports-tab').forEach(t => t.classList.remove('active'));
    const tab = status || 'all';
    document.querySelector(`.reports-tab[data-tab="${tab}"]`)?.classList.add('active');
    loadAppointmentsData(status);
}

function updateAppointmentStatus(appointmentId, newStatus) {
    fetch('doctor-dashboard.php?ajax=1&section=appointments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_appointment', appointment_id: appointmentId, status: newStatus })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                showToast('Appointment updated!', 'success');
                loadAppointmentsData();
            } else {
                showToast('Failed to update: ' + (result.error || ''), 'error');
            }
        })
        .catch(() => showToast('Connection error', 'error'));
}

function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    fetch('doctor-dashboard.php?ajax=1&section=appointments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel_appointment', appointment_id: appointmentId })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                showToast('Appointment cancelled', 'success');
                loadAppointmentsData();
            } else {
                showToast('Failed: ' + (result.error || ''), 'error');
            }
        })
        .catch(() => showToast('Connection error', 'error'));
}

function getMessagesView() {
    setTimeout(() => initMessagesPage(), 50);

    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Messages</h1>
                    <p class="dashboard-subtitle">Communicate with patients and parents</p>
                </div>
            </div>

            <div class="messages-container">
                <!-- Conversation List -->
                <div class="conversation-list">
                    <div class="conversation-list-header">
                        <input type="text" class="conversation-search" placeholder="Search conversations..." oninput="filterConversations(this.value)">
                    </div>
                    <div class="conversation-items" id="conversationItems">
                        <div class="conversation-item active" data-partner="sarah" onclick="selectConversation('sarah')">
                            <div class="conversation-avatar">SJ</div>
                            <div class="conversation-info">
                                <div class="conversation-name-row">
                                    <span class="conversation-name">Sarah Johnson</span>
                                    <span class="conversation-time">2:30 PM</span>
                                </div>
                                <div class="conversation-preview-row">
                                    <span class="conversation-preview">Thank you doctor for the report!</span>
                                    <span class="conversation-unread">2</span>
                                </div>
                            </div>
                        </div>
                        <div class="conversation-item" data-partner="michael" onclick="selectConversation('michael')">
                            <div class="conversation-avatar" style="background: linear-gradient(135deg, var(--purple-500), var(--pink-600));">MT</div>
                            <div class="conversation-info">
                                <div class="conversation-name-row">
                                    <span class="conversation-name">Michael Thompson</span>
                                    <span class="conversation-time">Yesterday</span>
                                </div>
                                <div class="conversation-preview-row">
                                    <span class="conversation-preview">When is Liam's next appointment?</span>
                                    <span class="conversation-unread">1</span>
                                </div>
                            </div>
                        </div>
                        <div class="conversation-item" data-partner="jennifer" onclick="selectConversation('jennifer')">
                            <div class="conversation-avatar" style="background: linear-gradient(135deg, var(--green-500), var(--cyan-600));">JW</div>
                            <div class="conversation-info">
                                <div class="conversation-name-row">
                                    <span class="conversation-name">Jennifer Williams</span>
                                    <span class="conversation-time">Mar 3</span>
                                </div>
                                <div class="conversation-preview-row">
                                    <span class="conversation-preview">Olivia is doing great with the exercises</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="chat-window" id="chatWindow">
                    <div class="chat-header" id="chatHeader">
                        <div class="chat-header-info">
                            <div class="conversation-avatar chat-header-avatar">SJ</div>
                            <div>
                                <div class="chat-header-name">Sarah Johnson</div>
                                <div class="chat-header-detail">Parent of Emma Johnson • 15 months</div>
                            </div>
                        </div>
                        <div class="chat-header-actions">
                            <button class="btn btn-sm btn-outline" title="View child profile">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                </svg>
                                Profile
                            </button>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-date-divider"><span>Today</span></div>
                        <div class="message-bubble message-received">
                            <div class="message-content">Hi Dr. Mitchell, I wanted to share Emma's latest development report with you. She's been making great progress with her motor skills!</div>
                            <div class="message-time">10:30 AM</div>
                        </div>
                        <div class="message-bubble message-sent">
                            <div class="message-content">Thank you for sharing, Sarah! I reviewed the report and I'm pleased with Emma's progress. The motor skills improvement is encouraging.</div>
                            <div class="message-time">11:15 AM</div>
                        </div>
                        <div class="message-bubble message-received">
                            <div class="message-content">That's wonderful to hear! Should we continue with the same exercises?</div>
                            <div class="message-time">1:45 PM</div>
                        </div>
                        <div class="message-bubble message-sent">
                            <div class="message-content">Yes, continue the current routine. I've also added some new recommendations in my report. Let's discuss them at our next appointment.</div>
                            <div class="message-time">2:10 PM</div>
                        </div>
                        <div class="message-bubble message-received">
                            <div class="message-content">Thank you doctor for the report! I'll review the recommendations right away. 🙏</div>
                            <div class="message-time">2:30 PM</div>
                        </div>
                    </div>
                    <div class="chat-input-bar">
                        <div class="chat-input-wrapper">
                            <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1" onkeydown="handleChatKeydown(event)"></textarea>
                            <button class="chat-send-btn" onclick="sendMessage()" title="Send message">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ─── Messages Page Logic ────────────────────────────────
const chatData = {
    sarah: {
        name: 'Sarah Johnson', initials: 'SJ', child: 'Emma Johnson', childAge: '15 months',
        avatarStyle: '',
        messages: [
            { from: 'parent', text: "Hi Dr. Mitchell, I wanted to share Emma's latest development report with you. She's been making great progress with her motor skills!", time: '10:30 AM' },
            { from: 'doctor', text: "Thank you for sharing, Sarah! I reviewed the report and I'm pleased with Emma's progress. The motor skills improvement is encouraging.", time: '11:15 AM' },
            { from: 'parent', text: 'That\'s wonderful to hear! Should we continue with the same exercises?', time: '1:45 PM' },
            { from: 'doctor', text: "Yes, continue the current routine. I've also added some new recommendations in my report. Let's discuss them at our next appointment.", time: '2:10 PM' },
            { from: 'parent', text: "Thank you doctor for the report! I'll review the recommendations right away. 🙏", time: '2:30 PM' }
        ]
    },
    michael: {
        name: 'Michael Thompson', initials: 'MT', child: 'Liam Thompson', childAge: '18 months',
        avatarStyle: 'background: linear-gradient(135deg, var(--purple-500), var(--pink-600));',
        messages: [
            { from: 'parent', text: "Hello Dr. Mitchell, I have a concern about Liam's speech development. He's still not saying many words.", time: '9:15 AM' },
            { from: 'doctor', text: "Hi Michael, that's quite common at this age. However, based on the report you shared, I'd recommend we schedule a more detailed evaluation.", time: '9:45 AM' },
            { from: 'parent', text: 'That sounds good. Should I be doing any specific exercises with him at home?', time: '10:20 AM' },
            { from: 'doctor', text: 'Yes! Try reading to him daily, naming objects during play, and encouraging him to repeat simple words. I\'ll send more detailed recommendations after our next session.', time: '11:00 AM' },
            { from: 'parent', text: "When is Liam's next appointment?", time: '3:30 PM' }
        ]
    },
    jennifer: {
        name: 'Jennifer Williams', initials: 'JW', child: 'Olivia Williams', childAge: '12 months',
        avatarStyle: 'background: linear-gradient(135deg, var(--green-500), var(--cyan-600));',
        messages: [
            { from: 'doctor', text: "Hi Jennifer, I wanted to follow up on Olivia's latest assessment. Everything looks excellent!", time: '2:00 PM' },
            { from: 'parent', text: "That's so reassuring to hear, Dr. Mitchell! We've been doing the exercises you recommended.", time: '2:30 PM' },
            { from: 'doctor', text: "That's wonderful. Keep up the great work! Olivia's fine motor skills are developing beautifully.", time: '3:15 PM' },
            { from: 'parent', text: 'Olivia is doing great with the exercises', time: '4:00 PM' }
        ]
    }
};

let currentConversation = 'sarah';

function initMessagesPage() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }
}

function selectConversation(partnerId) {
    currentConversation = partnerId;
    const data = chatData[partnerId];
    if (!data) return;

    // Update active state
    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
    document.querySelector(`.conversation-item[data-partner="${partnerId}"]`)?.classList.add('active');

    // Remove unread badge
    const activeItem = document.querySelector(`.conversation-item[data-partner="${partnerId}"]`);
    const unreadBadge = activeItem?.querySelector('.conversation-unread');
    if (unreadBadge) unreadBadge.remove();

    // Update chat header
    const header = document.getElementById('chatHeader');
    if (header) {
        const headerInfo = header.querySelector('.chat-header-info');
        headerInfo.innerHTML = `
            <div class="conversation-avatar chat-header-avatar" ${data.avatarStyle ? 'style="' + data.avatarStyle + '"' : ''}>${data.initials}</div>
            <div>
                <div class="chat-header-name">${data.name}</div>
                <div class="chat-header-detail">Parent of ${data.child} • ${data.childAge}</div>
            </div>
        `;
    }

    // Update chat messages
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        let html = '<div class="chat-date-divider"><span>Today</span></div>';
        data.messages.forEach(msg => {
            const cls = msg.from === 'doctor' ? 'message-sent' : 'message-received';
            html += `
                <div class="message-bubble ${cls}">
                    <div class="message-content">${msg.text}</div>
                    <div class="message-time">${msg.time}</div>
                </div>
            `;
        });
        chatMessages.innerHTML = html;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input?.value.trim();
    if (!text) return;

    const data = chatData[currentConversation];
    if (data) {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        data.messages.push({ from: 'doctor', text, time });
    }

    // Add bubble to UI
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble message-sent';
        bubble.innerHTML = `
            <div class="message-content">${text}</div>
            <div class="message-time">Just now</div>
        `;
        chatMessages.appendChild(bubble);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Update conversation preview
    const activeItem = document.querySelector(`.conversation-item[data-partner="${currentConversation}"]`);
    if (activeItem) {
        const preview = activeItem.querySelector('.conversation-preview');
        if (preview) preview.textContent = text.length > 40 ? text.substring(0, 40) + '...' : text;
        const timeEl = activeItem.querySelector('.conversation-time');
        if (timeEl) timeEl.textContent = 'Just now';
    }

    // Clear input
    input.value = '';
    input.style.height = 'auto';

    // Try sending to backend
    fetch('api_doctor_messages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'send_message',
            sender_id: 1, // Replace with session
            receiver_id: 2,
            content: text
        })
    }).catch(() => { });
}

function handleChatKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function filterConversations(query) {
    const items = document.querySelectorAll('.conversation-item');
    const q = query.toLowerCase();
    items.forEach(item => {
        const name = item.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
        item.style.display = name.includes(q) ? '' : 'none';
    });
}

function getAnalyticsView() {
    setTimeout(() => loadAnalyticsData(), 50);
    return `
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Analytics</h1>
                    <p class="dashboard-subtitle">Practice insights and patient statistics</p>
                </div>
            </div>
            <div class="doctor-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--blue-500);" id="analytics-patients">--</div>
                    <div style="color: var(--text-secondary);">Total Patients</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--green-500);" id="analytics-reports">--</div>
                    <div style="color: var(--text-secondary);">Reports Written</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--purple-500);" id="analytics-week">--</div>
                    <div style="color: var(--text-secondary);">This Week</div>
                </div>
                <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--orange-500);" id="analytics-rating">--</div>
                    <div style="color: var(--text-secondary);">Avg Rating</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="section-card" style="padding: 1.5rem;">
                    <h3 style="margin-bottom: 1rem;">Appointment Overview</h3>
                    <div id="analytics-appt-overview" style="color: var(--text-secondary);">Loading...</div>
                </div>
                <div class="section-card" style="padding: 1.5rem;">
                    <h3 style="margin-bottom: 1rem;">Activity This Month</h3>
                    <div id="analytics-monthly" style="color: var(--text-secondary);">Loading...</div>
                </div>
            </div>
        </div>
    `;
}

function loadAnalyticsData() {
    fetch(`doctor-dashboard.php?ajax=1&section=analytics&action=get_analytics&specialist_id=${SPECIALIST_ID}`)
        .then(r => r.json())
        .then(result => {
            if (result.success && result.data) {
                const d = result.data;
                const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
                el('analytics-patients', d.total_patients);
                el('analytics-reports', d.total_reports);
                el('analytics-week', d.appointments_this_week);
                el('analytics-rating', d.avg_rating ? d.avg_rating + '/5' : 'N/A');

                // Appointment overview
                const overview = document.getElementById('analytics-appt-overview');
                if (overview) {
                    overview.innerHTML = `
                        <div style="display:flex; flex-direction:column; gap:0.75rem;">
                            <div style="display:flex; justify-content:space-between;"><span>Total Appointments</span><strong>${d.total_appointments}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Completed</span><strong style="color:var(--green-500);">${d.completed_appointments}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Upcoming</span><strong style="color:var(--blue-500);">${d.upcoming_appointments}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Cancelled</span><strong style="color:var(--red-500);">${d.cancelled_appointments}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Reviews Received</span><strong>${d.total_reviews}</strong></div>
                        </div>
                    `;
                }

                // Monthly activity
                const monthly = document.getElementById('analytics-monthly');
                if (monthly) {
                    monthly.innerHTML = `
                        <div style="display:flex; flex-direction:column; gap:0.75rem;">
                            <div style="display:flex; justify-content:space-between;"><span>Appointments</span><strong>${d.appointments_this_month}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Reports Written</span><strong>${d.reports_this_month}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Messages</span><strong>${d.messages_this_month}</strong></div>
                            <div style="display:flex; justify-content:space-between;"><span>Total Messages</span><strong>${d.total_messages}</strong></div>
                        </div>
                    `;
                }
            }
        })
        .catch(() => { });
}

function getSettingsView() {
    window.location.href = 'dr-settings.php';
    return '';
}



// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = 'doctor-login.php';
    }
}
