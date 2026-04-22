// ─────────────────────────────────────────────────────────────
//  Clinic Dashboard – View Controller
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    console.log("Clinic Dashboard v2.0 - Add Specialist Feature Ready");
    initClinicNav();
    showClinicView('specialists'); // default view
});

function showClinicAlert(title, message) {
    const alertOverlay = document.createElement('div');
    alertOverlay.className = 'clinic-modal-overlay active';
    alertOverlay.style.zIndex = '1100'; // Above other modals
    
    alertOverlay.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 400px; padding: 2rem; text-align: center; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div style="width: 60px; height: 60px; background: rgba(13, 148, 136, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #0d9488;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h3 style="margin: 0 0 0.5rem; color: var(--text-primary); font-size: 1.25rem;">${title}</h3>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">${message}</p>
            <button class="btn btn-gradient" style="width: 100%;" onclick="this.closest('.clinic-modal-overlay').remove()">Dismiss</button>
        </div>
    `;
    
    document.body.appendChild(alertOverlay);
}

// Ensure the animation exists in CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes modalPop {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
`;
document.head.appendChild(style);

let clinicSpecialists = []; // Cache for searching
let clinicAppointments = []; // Cache for appointments
let clinicPatients = []; // Cache for patients
let currentCalendarMonth = new Date().getMonth();
let currentCalendarYear = new Date().getFullYear();

function initClinicNav() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const footerItems = document.querySelectorAll('.sidebar-footer .nav-item[data-view]');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                navItems.forEach(n => n.classList.remove('active'));
                footerItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                showClinicView(view);
            }
        });
    });

    footerItems.forEach(item => {
        item.addEventListener('click', function () {
            const view = this.dataset.view;
            if (view) {
                navItems.forEach(n => n.classList.remove('active'));
                footerItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                showClinicView(view);
            }
        });
    });
}

function showClinicView(viewId) {
    const main = document.getElementById('clinic-main-content');
    if (!main) return;

    const views = {
        'specialists': getSpecialistsView,
        'appointments': getAppointmentsView,
        'patients': getPatientsView,
        'revenue': getRevenueView,
        'reviews': getReviewsView,
        'settings': getSettingsView
    };

    const fn = views[viewId];
    if (fn) {
        main.innerHTML = fn();
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
        
        // Load actual data from API after rendering the skeleton
        refreshClinicData(viewId);
    }
}

async function refreshClinicData(viewId) {
    try {
        const response = await fetch('../../api_get_clinic_data.php');
        const data = await response.json();
        
        if (data.success) {
            console.log("Clinic Data Loaded:", data);
            clinicSpecialists = data.specialists || [];
            clinicPatients = data.patients || [];
            
            // Update Clinic Name in UI if found
            if (data.clinic_name) {
                const subtitle = document.querySelector('.dashboard-subtitle');
                if (subtitle) subtitle.textContent = `Healthcare Management - ${data.clinic_name}`;
            }

            if (viewId === 'specialists') {
                renderSpecialistsTable(data);
            } else if (viewId === 'appointments') {
                fetchAppointments(); // dynamically load appointments
            } else if (viewId === 'patients') {
                renderPatientsTable(clinicPatients);
            }
        }
    } catch (err) {
        console.error("Error fetching clinic data:", err);
    }
}

async function fetchAppointments() {
    try {
        const response = await fetch('../../api_get_clinic_appointments.php');
        const data = await response.json();
        if (!data.error) {
            clinicAppointments = data;
            renderAppointmentsList(data);
        } else {
            console.error("Error fetching appointments:", data.error);
            renderAppointmentsList([]);
        }
    } catch (err) {
        console.error("Failed to fetch appointments", err);
    }
}

function renderAppointmentsList(appointments) {
    const container = document.getElementById('appointments-view-container');
    if (!container) return;

    if (appointments.length === 0) {
        container.innerHTML = '<div style="padding: 40px; text-align: center; color: rgba(255,255,255,0.5);">No upcoming appointments</div>';
        return;
    }

    let html = '<div class="patients-list">';
    
    appointments.forEach(apt => {
        const dt = new Date(apt.scheduled_at);
        const timeStr = dt.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const dateStr = dt.toLocaleDateString();
        
        let statusClass = "status-yellow";
        let statusIcon = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
        if (apt.status === 'completed') {
            statusClass = "status-green";
            statusIcon = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>';
        } else if (apt.status === 'cancelled') {
            statusClass = "status-danger";
            statusIcon = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
        }

        html += `
            <div class="patient-row">
                <div class="appointment-time-badge">
                    <div class="apt-time">${timeStr}</div>
                    <div class="apt-date">${dateStr}</div>
                </div>
                <div class="patient-avatar" style="background: linear-gradient(135deg, #009688, #00bcd4);">
                    ${apt.child_fname[0]}${apt.child_lname[0]}
                </div>
                <div class="patient-info">
                    <div class="patient-name">${apt.child_fname} ${apt.child_lname}</div>
                    <div class="patient-details">with Dr. ${apt.specialist_fname} ${apt.specialist_lname} • ${apt.type}</div>
                </div>
                <div class="patient-status ${statusClass}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${statusIcon}</svg>
                    ${apt.status}
                </div>
                <button class="btn btn-sm btn-outline" onclick="viewAppointmentDetails(${apt.appointment_id})">Details</button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewAppointmentDetails(id) {
    const apt = clinicAppointments.find(a => a.appointment_id == id);
    if (!apt) return;

    const dt = new Date(apt.scheduled_at);
    const timeStr = dt.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const dateStr = dt.toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'});

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'view-appointment-modal';
    
    const initial = ((apt.child_fname ? apt.child_fname[0] : '') + (apt.child_lname ? apt.child_lname[0] : '')) || 'PT';
    
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 600px; width: 95%;">
            <div class="clinic-modal-header" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.1), rgba(45, 212, 191, 0.05)); border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="patient-avatar" style="width: 3.5rem; height: 3.5rem; font-size: 1.25rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); box-shadow: 0 8px 15px rgba(139, 92, 246, 0.2);">
                        ${initial.toUpperCase()}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; color: var(--text-primary);">${apt.child_fname} ${apt.child_lname}</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Appointment ID: #APT-${apt.appointment_id}</p>
                    </div>
                </div>
                <button class="clinic-modal-close" onclick="closeViewAppointmentModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <div class="clinic-modal-body" style="padding: 2rem; background: var(--bg-secondary);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="profile-info-item">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Parent Name</label>
                        <div style="font-weight: 600; color: var(--text-primary);">${apt.parent_fname} ${apt.parent_lname}</div>
                    </div>
                    <div class="profile-info-item">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Assigned Specialist</label>
                        <div style="font-weight: 600; color: var(--text-primary);">Dr. ${apt.specialist_fname} ${apt.specialist_lname}</div>
                    </div>
                    <div class="profile-info-item">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Date & Time</label>
                        <div style="font-weight: 600; color: var(--text-primary);">${dateStr} at ${timeStr}</div>
                    </div>
                    <div class="profile-info-item">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Type / Status</label>
                        <div>
                            <span class="status-badge" style="background: rgba(13, 148, 136, 0.1); color: #0d9488; text-transform: capitalize;">${apt.type}</span>
                            <span class="status-badge" style="background: #f0fdf4; color: #16a34a; text-transform: capitalize;">${apt.status}</span>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px dashed var(--border-color); padding-top: 1.5rem;">
                    <h4 style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">Additional Comments</h4>
                    <div style="background: white; padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color); color: var(--text-primary); line-height: 1.6; font-size: 0.9rem;">
                        ${apt.comment || 'No additional comments provided for this appointment.'}
                    </div>
                </div>
            </div>
            
            <div class="clinic-modal-footer" style="padding: 1.25rem 2rem; border-top: 1px solid var(--border-color); background: white;">
                <button class="btn btn-outline" style="width: 100%;" onclick="closeViewAppointmentModal()">Close Details</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeViewAppointmentModal() {
    const modal = document.getElementById('view-appointment-modal');
    if (modal) modal.remove();
}

function filterSpecialists(query) {
    const q = query.toLowerCase();
    const filtered = clinicSpecialists.filter(s => 
        s.first_name.toLowerCase().includes(q) || 
        s.last_name.toLowerCase().includes(q) || 
        s.specialization.toLowerCase().includes(q)
    );
    renderSpecialistsTable({ specialists: filtered }, false); // Don't update counter during search
}

function renderSpecialistsTable(data, updateCounter = true) {
    const specialists = data.specialists || [];
    
    // Update counter boxes
    if (updateCounter) {
        const countBox = document.querySelector('.stat-card-yellow .stat-card-value') || 
                        document.querySelector('.stat-card-blue .stat-card-value'); // fallback
        if (countBox) countBox.textContent = specialists.length;
    }

    const tbody = document.querySelector('.clinic-table tbody');
    if (!tbody) return;

    if (specialists.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">No specialists registered yet.</td></tr>';
        return;
    }

    tbody.innerHTML = specialists.map(spec => `
        <tr>
            <td>
                <div class="table-user">
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #009688, #00bcd4);">
                        ${spec.first_name[0]}${spec.last_name[0]}
                    </div>
                    <div>
                        <div class="patient-name">Dr. ${spec.first_name} ${spec.last_name}</div>
                        <div class="patient-details">${spec.email || 'specialist@clinic.com'}</div>
                    </div>
                </div>
            </td>
            <td>${spec.specialization}</td>
            <td>${spec.experience_years || 0} years</td>
            <td>${spec.patients_count || Math.floor(Math.random() * 50)}</td>
            <td><span class="rating-badge">★ ${spec.rating || (4 + Math.random()).toFixed(1)}</span></td>
            <td><span class="status-badge status-active">Active</span></td>
            <td><button class="btn btn-sm btn-outline" onclick="viewSpecialistDetails(${spec.specialist_id})">View</button></td>
        </tr>
    `).join('');
}

function viewSpecialistDetails(id) {
    const spec = clinicSpecialists.find(s => s.specialist_id == id);
    if (!spec) return;

    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'view-specialist-modal';
    
    const initial = (spec.first_name[0] || '') + (spec.last_name ? spec.last_name[0] : '');
    const clinicName = document.querySelector('.user-name')?.textContent || 'Bright Steps Clinic';
    
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 850px; width: 95%;">
            <div class="clinic-modal-header" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.1), rgba(45, 212, 191, 0.05)); border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="patient-avatar" style="width: 4.5rem; height: 4.5rem; font-size: 1.5rem; background: linear-gradient(135deg, #0d9488, #2dd4bf); box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);">
                        ${initial.toUpperCase()}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem; color: var(--text-primary);">Dr. ${spec.first_name} ${spec.last_name}</h3>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                            <span style="color: #0d9488; font-weight: 600; font-size: 0.9rem;">${spec.specialization}</span>
                            <span style="width: 4px; height: 4px; background: var(--text-muted); border-radius: 50%;"></span>
                            <span style="color: var(--text-secondary); font-size: 0.9rem;">Specialist ID: #SP-${spec.specialist_id}</span>
                        </div>
                    </div>
                </div>
                <button class="clinic-modal-close" onclick="closeViewModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <div class="clinic-modal-body" style="padding: 0; background: var(--bg-secondary); display: grid; grid-template-columns: 300px 1fr;">
                <!-- Sidebar Info -->
                <div style="padding: 2rem; border-right: 1px solid var(--border-color); background: rgba(255,255,255,0.02);">
                    <div class="info-section" style="margin-bottom: 2rem;">
                        <h4 style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Contact Information</h4>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary); word-break: break-all;">${spec.email || 'N/A'}</div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary);">+20 102 345 6789</div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="color: #0d9488;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                                <div style="font-size: 0.85rem; color: var(--text-primary);">${clinicName}</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4 style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Professional Summary</h4>
                        <div style="background: var(--bg-primary); padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color);">
                            <div style="margin-bottom: 0.75rem;">
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Experience</div>
                                <div style="font-weight: 600; color: var(--text-primary);">${spec.experience_years || 0} Years</div>
                            </div>
                            <div style="margin-bottom: 0.75rem;">
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Success Rate</div>
                                <div style="font-weight: 600; color: #16a34a;">98.2%</div>
                            </div>
                            <div>
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Rating</div>
                                <div style="font-weight: 600; color: #f59e0b;">★ ${spec.rating || '4.8'}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Details Area -->
                <div style="padding: 2rem; overflow-y: auto; max-height: 60vh;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Current Workload</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">24 Patients</div>
                            <div style="font-size: 0.75rem; color: #16a34a; margin-top: 0.25rem;">↑ 3 from last month</div>
                        </div>
                        <div style="background: white; padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Avg. Session Time</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">45 Mins</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Standard consultation</div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Professional Certificates
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="width: 36px; height: 36px; background: #f0fdfa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div style="font-size: 0.9rem; font-weight: 600;">Board Certified Pediatrician</div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Verified</span>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="width: 36px; height: 36px; background: #f0fdfa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #0d9488;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div style="font-size: 0.9rem; font-weight: 600;">Medical Experience Certificate</div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Verified</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="clinic-modal-footer" style="padding: 1.25rem 2rem; border-top: 1px solid var(--border-color); background: white;">
                <button class="btn btn-outline" onclick="closeViewModal()" style="min-width: 120px;">Close</button>
                <div style="display: flex; gap: 0.75rem;">
                    <button class="btn btn-outline" style="border-color: #0d9488; color: #0d9488;" onclick="showClinicAlert('Contact Specialist', 'Phone: +20 102 345 6789')">Call Now</button>
                    <button class="btn btn-gradient" style="min-width: 160px;" onclick="showClinicAlert('Specialist Email', 'Direct Email: ${spec.email || 'N/A'}')">Email Specialist</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeViewModal() {
    const modal = document.getElementById('view-specialist-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function viewPatientRecords(childId, childName) {
    const modal = document.createElement('div');
    modal.className = 'clinic-modal-overlay active';
    modal.id = 'patient-records-modal';
    
    const names = childName.split(' ');
    const initials = (names[0][0] || '') + (names[1] ? names[1][0] : '');
    
    modal.innerHTML = `
        <div class="clinic-modal glass-effect" style="max-width: 800px; width: 95%;">
            <div class="clinic-modal-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="patient-avatar" style="width: 3rem; height: 3rem; font-size: 1.1rem; background: linear-gradient(135deg, #0d9488, #2dd4bf);">
                        ${initials.toUpperCase()}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem;">${childName}</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Medical History & Patient Records</p>
                    </div>
                </div>
                <button class="clinic-modal-close" onclick="closeRecordsModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body" id="records-modal-body" style="padding: 1.5rem; max-height: 70vh; overflow-y: auto; background: var(--bg-secondary);">
                <div style="text-align:center; padding: 60px 20px;">
                    <div class="loading-spinner" style="width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: #0d9488; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Retrieving patient medical history...</p>
                </div>
            </div>
            <div class="clinic-modal-footer">
                <button class="btn btn-outline" onclick="closeRecordsModal()">Close Records</button>
            </div>
        </div>
        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
            .record-group { margin-bottom: 2rem; }
            .record-group-title { 
                font-size: 0.75rem; 
                font-weight: 700; 
                text-transform: uppercase; 
                letter-spacing: 0.05em; 
                color: var(--text-secondary);
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .record-group-title::after { content: ""; flex: 1; height: 1px; background: var(--border-color); }
            
            .record-item-card {
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-lg);
                padding: 1.25rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .record-item-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                border-color: #0d948844;
            }
            .record-item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
            .record-item-badge { 
                font-size: 0.7rem; 
                font-weight: 700; 
                padding: 0.25rem 0.625rem; 
                border-radius: 6px; 
                text-transform: uppercase;
            }
            .badge-diagnosis { background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1; }
            .badge-prescription { background: #fdf2f8; color: #db2777; border: 1px solid #fce7f3; }
            
            .record-item-date { font-size: 0.75rem; color: var(--text-secondary); font-weight: 500; }
            .record-item-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
            .record-item-text { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5; }
            .record-item-meta { 
                margin-top: 1rem; 
                padding-top: 0.75rem; 
                border-top: 1px dashed var(--border-color); 
                display: flex; 
                justify-content: space-between;
                font-size: 0.75rem;
                color: var(--text-muted);
            }
            .empty-history { text-align: center; padding: 2rem; color: var(--text-secondary); font-style: italic; font-size: 0.9rem; }
        </style>
    `;
    
    document.body.appendChild(modal);

    // Fetch actual data
    fetch(`../../clinic/api/management/history.php?action=child&child_id=${childId}`)
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Unauthorized access') });
            return res.json();
        })
        .then(data => {
            if (data.success) {
                renderRecordsContent(data);
            } else {
                throw new Error(data.error || 'Failed to load records');
            }
        })
        .catch(err => {
            console.error('Error fetching records:', err);
            document.getElementById('records-modal-body').innerHTML = `
                <div style="text-align:center; padding: 60px 20px;">
                    <div style="width: 48px; height: 48px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h4 style="color: var(--text-primary); margin-bottom: 8px;">Unable to Load Records</h4>
                    <p style="color: #ef4444; font-size: 0.9rem; margin-bottom: 20px;">${err.message}</p>
                    <button class="btn btn-outline" onclick="closeRecordsModal()">Close</button>
                </div>
            `;
        });
}

function renderRecordsContent(data) {
    const container = document.getElementById('records-modal-body');
    const recs = data.medical_records || [];
    const prescriptions = data.prescriptions || [];
    const appointments = data.appointments || [];

    let html = '';

    // 1. Diagnoses Section
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Diagnoses & Medical Reports</h4>
            ${recs.length === 0 ? '<div class="empty-history">No medical records found for this patient.</div>' : 
                recs.map(r => `
                    <div class="record-item-card">
                        <div class="record-item-header">
                            <span class="record-item-badge badge-diagnosis">Medical Record</span>
                            <span class="record-item-date">${new Date(r.created_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</span>
                        </div>
                        <div class="record-item-title">${r.diagnosis}</div>
                        <div class="record-item-text">
                            <strong>Symptoms:</strong> ${r.symptoms || 'Not specified'}<br>
                            ${r.notes ? `<strong>Notes:</strong> ${r.notes}` : ''}
                        </div>
                        <div class="record-item-meta">
                            <span>Specialist: Dr. ${r.doctor_first_name} ${r.doctor_last_name}</span>
                            <span>${r.specialization}</span>
                        </div>
                    </div>
                `).join('')
            }
        </div>
    `;

    // 2. Prescriptions Section
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Prescriptions</h4>
            ${prescriptions.length === 0 ? '<div class="empty-history">No active or past prescriptions found.</div>' : 
                prescriptions.map(p => `
                    <div class="record-item-card">
                        <div class="record-item-header">
                            <span class="record-item-badge badge-prescription">Prescription</span>
                            <span class="record-item-date">${new Date(p.created_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</span>
                        </div>
                        <div class="record-item-title">${p.medication_name} ${p.dosage ? `<span style="font-weight:400; color:var(--text-secondary); font-size:0.9rem;">— ${p.dosage}</span>` : ''}</div>
                        <div class="record-item-text">
                            <strong>Frequency:</strong> ${p.frequency || 'As directed'}<br>
                            ${p.instructions ? `<strong>Instructions:</strong> ${p.instructions}` : ''}
                        </div>
                        <div class="record-item-meta">
                            <span>Prescribed by: Dr. ${p.doctor_first_name} ${p.doctor_last_name}</span>
                        </div>
                    </div>
                `).join('')
            }
        </div>
    `;

    // 3. Appointment Timeline
    html += `
        <div class="record-group">
            <h4 class="record-group-title">Appointment History</h4>
            ${appointments.length === 0 ? '<div class="empty-history">No appointment history available.</div>' : 
                appointments.slice(0, 10).map(a => `
                    <div class="record-item-card" style="display:flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem;">
                        <div style="display:flex; gap: 1rem; align-items: center;">
                            <div style="width: 40px; height: 40px; background: var(--bg-secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">Dr. ${a.doctor_first_name} ${a.doctor_last_name}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">${new Date(a.scheduled_at).toLocaleDateString()} • ${a.type}</div>
                            </div>
                        </div>
                        <span class="status-badge ${a.status === 'completed' ? 'status-active' : a.status === 'cancelled' ? 'status-danger' : 'status-warning'}" style="text-transform: capitalize;">
                            ${a.status}
                        </span>
                    </div>
                `).join('')
            }
        </div>
    `;

    container.innerHTML = html;
}

function closeRecordsModal() {
    const modal = document.getElementById('patient-records-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

// ── Specialists View ─────────────────────────────────────────
function getSpecialistsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Specialists Management</h1>
                <p class="dashboard-subtitle">Manage your clinic's healthcare team</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-gradient" onclick="openAddSpecialistModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Specialist
                </button>
            </div>
        </div>

        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">8</div><div class="stat-card-label">Active Specialists</div></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">156</div><div class="stat-card-label">Total Appointments</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">$24.5K</div><div class="stat-card-label">Monthly Revenue</div></div>
            </div>
            <div class="stat-card stat-card-purple">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">4.8</div><div class="stat-card-label">Avg Rating</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Specialists</h2>
                <input type="text" class="search-input" placeholder="Search specialists..." onkeyup="filterSpecialists(this.value)">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Specialist</th><th>Specialization</th><th>Experience</th><th>Patients</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">Loading team members...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

// ── Appointments View ────────────────────────────────────────
function getAppointmentsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Appointments</h1>
                <p class="dashboard-subtitle">Manage clinic schedule and patient appointments</p>
            </div>
            <div class="header-actions-inline">
                <button class="btn btn-outline" onclick="toggleCalendarView()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar View
                </button>
                <button class="btn btn-gradient" onclick="openNewAppointmentModal()">+ New Appointment</button>
            </div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">12</div><div class="stat-card-label">Today's Appointments</div></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">8</div><div class="stat-card-label">Completed</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">4</div><div class="stat-card-label">Pending</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">Upcoming Appointments</h2>
                <select class="search-input" style="width:auto;">
                    <option>All Specialists</option>
                    <option>Dr. Sarah Mitchell</option>
                    <option>Dr. Michael Chen</option>
                    <option>Dr. Aisha Rahman</option>
                </select>
            </div>
            <div id="appointments-view-container">
                <div class="patients-list">
                    <div class="patient-row">
                        <div class="appointment-time-badge">
                            <div class="apt-time">9:00 AM</div>
                            <div class="apt-date">Today</div>
                        </div>
                        <div class="patient-avatar">EJ</div>
                        <div class="patient-info">
                            <div class="patient-name">Emma Johnson</div>
                            <div class="patient-details">with Dr. Sarah Mitchell • Routine Checkup</div>
                        </div>
                        <div class="patient-status status-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Onsite
                        </div>
                        <button class="btn btn-sm btn-outline">Details</button>
                    </div>
                    <!-- Other rows ... -->
                </div>
            </div>
        </div>
    </div>`;
}

// ── New Appointment Modal ───────────────────────────────────────
function openNewAppointmentModal() {
    // Create modal if not exists
    let modal = document.getElementById('appointment-modal-overlay');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'appointment-modal-overlay';
        modal.className = 'clinic-modal-overlay';
        document.body.appendChild(modal);
    }

    modal.innerHTML = `
        <div class="clinic-modal">
            <div class="clinic-modal-header">
                <h3>+ Schedule New Appointment</h3>
                <button class="clinic-modal-close" onclick="closeClinicModal('appointment-modal-overlay')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="clinic-modal-body">
                <form id="new-appointment-form">
                    <div class="modal-form-group">
                        <label>Select Patient <span class="required-star">*</span></label>
                        <select id="apt-patient" class="form-input" required>
                            <option value="">Choose a patient...</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label>Select Specialist <span class="required-star">*</span></label>
                        <select id="apt-specialist" class="form-input" required>
                            <option value="">Choose a specialist...</option>
                        </select>
                    </div>
                    <div class="modal-form-row">
                        <div class="modal-form-group">
                            <label>Date <span class="required-star">*</span></label>
                            <input type="date" id="apt-date" class="form-input" required>
                        </div>
                        <div class="modal-form-group">
                            <label>Time <span class="required-star">*</span></label>
                            <input type="time" id="apt-time" class="form-input" required>
                        </div>
                    </div>
                    <div class="modal-form-group">
                        <label>Appointment Type</label>
                        <select id="apt-type" class="form-input">
                            <option value="onsite">Onsite Visit</option>
                            <option value="online">Online Session</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label>Additional Comments</label>
                        <textarea id="apt-comment" class="form-input" style="height:80px;resize:none;" placeholder="Notes for the specialist..."></textarea>
                    </div>
                </form>
            </div>
            <div class="clinic-modal-footer">
                <button class="btn btn-outline" onclick="closeClinicModal('appointment-modal-overlay')">Cancel</button>
                <button class="btn btn-gradient" onclick="submitNewAppointment()">Confirm Booking</button>
            </div>
        </div>
    `;

    modal.classList.add('active');
    
    // Fetch Data
    fetch('../../api_get_clinic_data.php')
        .then(res => res.json())
        .then(data => {
            console.log("Appointment Data Debug:", data.debug);
            if (data.error) {
                showClinicToast(data.error, 'error');
                return;
            }
            
            const pSelect = document.getElementById('apt-patient');
            const sSelect = document.getElementById('apt-specialist');
            
            if (data.patients && data.patients.length > 0) {
                data.patients.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.child_id;
                    opt.textContent = `${p.first_name} ${p.last_name || ''} (Parent: ${p.parent_fname})`;
                    pSelect.appendChild(opt);
                });
            } else {
                pSelect.innerHTML = '<option value="">No patients found...</option>';
            }
            
            if (data.specialists && data.specialists.length > 0) {
                data.specialists.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.specialist_id;
                    opt.textContent = `Dr. ${s.first_name} ${s.last_name || ''} - ${s.specialization || 'Specialist'}`;
                    sSelect.appendChild(opt);
                });
            } else {
                sSelect.innerHTML = '<option value="">No specialists found...</option>';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            showClinicToast('Failed to load clinic data', 'error');
        });
}

function submitNewAppointment() {
    const formData = new FormData();
    formData.append('child_id', document.getElementById('apt-patient').value);
    formData.append('specialist_id', document.getElementById('apt-specialist').value);
    formData.append('scheduled_at', document.getElementById('apt-date').value + ' ' + document.getElementById('apt-time').value);
    formData.append('type', document.getElementById('apt-type').value);
    formData.append('comment', document.getElementById('apt-comment').value);

    fetch('../../api_clinic_book_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showClinicToast('Appointment scheduled successfully!', 'success');
            closeClinicModal('appointment-modal-overlay');
            showClinicView('appointments'); // Refresh view
        } else {
            showClinicToast(data.error || 'Failed to book appointment', 'error');
        }
    });
}

function closeClinicModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('active');
}

// ── Calendar View ──────────────────────────────────────────────
let isCalendarView = false;
function toggleCalendarView() {
    const container = document.getElementById('appointments-view-container');
    if (!container) return;
    
    isCalendarView = !isCalendarView;
    
    if (isCalendarView) {
        renderCalendarGrid(container);
    } else {
        showClinicView('appointments');
    }
}

function changeCalendarMonth(delta) {
    currentCalendarMonth += delta;
    if (currentCalendarMonth > 11) {
        currentCalendarMonth = 0;
        currentCalendarYear++;
    } else if (currentCalendarMonth < 0) {
        currentCalendarMonth = 11;
        currentCalendarYear--;
    }
    const container = document.getElementById('appointments-view-container');
    if (container) renderCalendarGrid(container);
}

function renderCalendarGrid(container) {
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const daysInMonth = new Date(currentCalendarYear, currentCalendarMonth + 1, 0).getDate();
    const firstDay = new Date(currentCalendarYear, currentCalendarMonth, 1).getDay();
    
    // Filter appointments for this month
    const monthApts = clinicAppointments.filter(a => {
        const d = new Date(a.scheduled_at);
        return d.getMonth() === currentCalendarMonth && d.getFullYear() === currentCalendarYear;
    });

    let gridHtml = '';
    // Empty cells for days before the 1st
    for (let i = 0; i < firstDay; i++) {
        gridHtml += `<div style="background:var(--bg-secondary);opacity:0.3;"></div>`;
    }

    // Days of the month
    const today = new Date();
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = today.getDate() === day && today.getMonth() === currentCalendarMonth && today.getFullYear() === currentCalendarYear;
        
        // Find appointments for this specific day
        const dayApts = monthApts.filter(a => new Date(a.scheduled_at).getDate() === day);
        
        gridHtml += `
            <div style="background:var(--bg-primary);min-height:100px;padding:0.5rem;border:1px solid var(--border-color);${isToday ? 'background:rgba(13,148,136,0.05);' : ''}">
                <div style="font-weight:700;font-size:0.8rem;margin-bottom:0.5rem;${isToday ? 'color:#0d9488;' : ''}">${day}</div>
                <div style="display:flex;flex-direction:column;gap:4px;">
                    ${dayApts.map(a => `
                        <div class="calendar-apt-pill" 
                             onclick="viewAppointmentDetails(${a.appointment_id})"
                             style="font-size:0.65rem;background:#f0fdf4;color:#16a34a;padding:2px 6px;border-radius:4px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border:1px solid #dcfce7;"
                             title="${a.child_fname} - ${a.type}">
                            ${new Date(a.scheduled_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})} ${a.child_fname}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    container.innerHTML = `
        <div class="calendar-wrapper" style="padding:1rem;animation:fadeIn 0.3s ease;">
            <div class="calendar-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;background:white;padding:1rem;border-radius:12px;border:1px solid var(--border-color);">
                <div style="font-size:1.25rem;font-weight:700;color:var(--text-primary);">${monthNames[currentCalendarMonth]} ${currentCalendarYear}</div>
                <div style="display:flex;gap:0.75rem;">
                    <button class="btn btn-sm btn-outline" onclick="changeCalendarMonth(-1)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                        Prev
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="changeCalendarMonth(1)">
                        Next
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="calendar-grid-header" style="display:grid;grid-template-columns:repeat(7, 1fr);margin-bottom:2px;">
                ${['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => `<div style="background:var(--bg-secondary);padding:0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:var(--text-secondary);">${d}</div>`).join('')}
            </div>
            <div class="calendar-grid" style="display:grid;grid-template-columns:repeat(7, 1fr);gap:1px;background:var(--border-color);border:1px solid var(--border-color);border-radius:0 0 12px 12px;overflow:hidden;">
                ${gridHtml}
            </div>
        </div>
    `;
}

function showClinicToast(msg, type = 'success') {
    let toast = document.getElementById('clinic-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'clinic-toast';
        toast.className = 'clinic-toast';
        document.body.appendChild(toast);
    }
    
    toast.textContent = msg;
    toast.className = `clinic-toast show dr-toast-${type}`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}


// ── Patients View ────────────────────────────────────────────
function getPatientsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Patient Directory</h1>
                <p class="dashboard-subtitle">All registered patients at City Kids Care</p>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-heading">All Patients</h2>
                <input type="text" class="search-input" placeholder="Search by name or parent..." onkeyup="filterPatients(this.value)">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Child</th><th>Age</th><th>Parent/Guardian</th><th>Assigned Specialist</th><th>Status</th><th>Last Visit</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">Loading patients...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

function filterPatients(query) {
    const q = query.toLowerCase();
    const filtered = clinicPatients.filter(p => {
        const childName = (p.first_name + ' ' + (p.last_name || '')).toLowerCase();
        const parentName = (p.parent_fname + ' ' + (p.parent_lname || '')).toLowerCase();
        return childName.includes(q) || parentName.includes(q);
    });
    renderPatientsTable(filtered);
}

function renderPatientsTable(patients) {
    const tbody = document.querySelector('.clinic-table tbody');
    if (!tbody) return;

    if (patients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">No patients found matching your search.</td></tr>';
        return;
    }

    const gradients = [
        'background: linear-gradient(135deg, #009688, #00bcd4)',
        'background: linear-gradient(135deg, #06b6d4, #0891b2)',
        'background: linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'background: linear-gradient(135deg, #f59e0b, #d97706)',
        'background: linear-gradient(135deg, #ec4899, #db2777)'
    ];

    tbody.innerHTML = patients.map((p, i) => {
        const bg = gradients[i % gradients.length];
        const initial = (p.first_name[0] || '') + (p.last_name ? p.last_name[0] : '');
        return `
        <tr>
            <td>
                <div class="table-user">
                    <div class="patient-avatar" style="${bg};">${initial.toUpperCase()}</div>
                    <div>
                        <div class="patient-name">${p.first_name} ${p.last_name || ''}</div>
                        <div class="patient-details">Child ID: ${p.child_id}</div>
                    </div>
                </div>
            </td>
            <td>N/A</td>
            <td>${p.parent_fname} ${p.parent_lname || ''}</td>
            <td>Unassigned</td>
            <td><span class="status-badge status-active">Active</span></td>
            <td>N/A</td>
            <td><button class="btn btn-sm btn-outline" onclick="viewPatientRecords(${p.child_id}, '${p.first_name} ${p.last_name || ''}')">View Records</button></td>
        </tr>`;
    }).join('');
}

// ── Revenue View ─────────────────────────────────────────────
function getRevenueView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Revenue & Payments</h1>
                <p class="dashboard-subtitle">Financial overview and payment tracking</p>
            </div>
            <div class="header-actions-inline">
                <a href="../../api_clinic_revenue_export.php" target="_blank" class="btn btn-outline" style="text-decoration: none; display: inline-flex; align-items: center;">Export Report</a>
            </div>
        </div>

        <div class="doctor-stats-grid">
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">$24,500</div><div class="stat-card-label">This Month</div></div>
            </div>
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">+18%</div><div class="stat-card-label">Growth Rate</div></div>
            </div>
            <div class="stat-card stat-card-purple">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">89</div><div class="stat-card-label">Active Subscribers</div></div>
            </div>
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">3</div><div class="stat-card-label">Pending Payments</div></div>
            </div>
        </div>

        <div class="revenue-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Subscription Breakdown</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="revenue-row"><span class="revenue-plan">Premium Plan</span><span class="revenue-count">52 subscribers</span><span class="revenue-amount">$15,600/mo</span></div>
                    <div class="revenue-row"><span class="revenue-plan">Standard Plan</span><span class="revenue-count">37 subscribers</span><span class="revenue-amount">$7,400/mo</span></div>
                    <div class="revenue-row"><span class="revenue-plan">Free Trial</span><span class="revenue-count">24 users</span><span class="revenue-amount">$0/mo</span></div>
                    <div class="revenue-row revenue-total"><span class="revenue-plan">Total Revenue</span><span></span><span class="revenue-amount">$23,000/mo</span></div>
                </div>
            </div>
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Recent Payments</h2></div>
                <div class="patients-list">
                    <div class="patient-row"><div class="patient-info"><div class="patient-name">Sarah Johnson</div><div class="patient-details">Premium Plan • Credit Card</div></div><div style="font-weight:700;color:var(--green-600);">$300.00</div><div class="patient-last-update">Nov 25, 2025</div></div>
                    <div class="patient-row"><div class="patient-info"><div class="patient-name">Michael Thompson</div><div class="patient-details">Standard Plan • PayPal</div></div><div style="font-weight:700;color:var(--green-600);">$200.00</div><div class="patient-last-update">Nov 24, 2025</div></div>
                    <div class="patient-row"><div class="patient-info"><div class="patient-name">Jennifer Williams</div><div class="patient-details">Premium Plan • Credit Card</div></div><div style="font-weight:700;color:var(--green-600);">$300.00</div><div class="patient-last-update">Nov 23, 2025</div></div>
                    <div class="patient-row"><div class="patient-info"><div class="patient-name">David Smith</div><div class="patient-details">Standard Plan • Bank Transfer</div></div><div style="font-weight:700;color:var(--yellow-600);">Pending</div><div class="patient-last-update">Nov 22, 2025</div></div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Reviews View ─────────────────────────────────────────────
function getReviewsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Parent Reviews & Feedback</h1>
                <p class="dashboard-subtitle">See what parents say about your clinic</p>
            </div>
        </div>

        <div class="doctor-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">4.8/5</div><div class="stat-card-label">Overall Rating</div></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">94%</div><div class="stat-card-label">Positive Feedback</div></div>
            </div>
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <div class="stat-card-info"><div class="stat-card-value">47</div><div class="stat-card-label">Total Reviews</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header"><h2 class="section-heading">Recent Reviews</h2></div>
            <div class="reviews-list">
                <div class="review-card">
                    <div class="review-header">
                        <div class="table-user"><div class="patient-avatar">SJ</div><div><div class="patient-name">Sarah Johnson</div><div class="patient-details">Parent of Emma • Nov 20, 2025</div></div></div>
                        <div class="review-stars">★★★★★</div>
                    </div>
                    <p class="review-text">Dr. Mitchell has been incredible with Emma's development tracking. The speech therapy sessions with Dr. Rahman have made a huge difference. Highly recommend this clinic!</p>
                    <div class="review-specialist">About: Dr. Sarah Mitchell, Dr. Aisha Rahman</div>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">MT</div><div><div class="patient-name">Michael Thompson</div><div class="patient-details">Parent of Liam • Nov 18, 2025</div></div></div>
                        <div class="review-stars">★★★★☆</div>
                    </div>
                    <p class="review-text">Very professional and caring staff. Dr. Chen's behavioral assessments have been very thorough. The online appointment system is convenient.</p>
                    <div class="review-specialist">About: Dr. Michael Chen</div>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">FA</div><div><div class="patient-name">Fatima Ahmed</div><div class="patient-details">Parent of Sophia • Nov 15, 2025</div></div></div>
                        <div class="review-stars">★★★★★</div>
                    </div>
                    <p class="review-text">The team at City Kids Care goes above and beyond. Dr. Wilson's occupational therapy program has been transformative for Sophia. Thank you!</p>
                    <div class="review-specialist">About: Dr. James Wilson</div>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Settings View ────────────────────────────────────────────
function getSettingsView() {
    return `
    <div class="dashboard-content">
        <div class="dashboard-header-section">
            <div>
                <h1 class="dashboard-title">Clinic Settings</h1>
                <p class="dashboard-subtitle">Manage your clinic profile and preferences</p>
            </div>
        </div>

        <div class="settings-grid">
            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Clinic Information</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="form-grid">
                        <div class="form-group"><label>Clinic Name</label><input type="text" class="form-input" value="City Kids Care"></div>
                        <div class="form-group"><label>Email Address</label><input type="email" class="form-input" value="info@citykidscare.com"></div>
                        <div class="form-group"><label>Location</label><input type="text" class="form-input" value="123 Downtown Blvd, Suite 200"></div>
                        <div class="form-group"><label>Phone Numbers</label><input type="text" class="form-input" value="+1 (555) 123-4567, +1 (555) 987-6543"></div>
                    </div>
                    <button class="btn btn-gradient" style="margin-top:1.5rem;">Save Changes</button>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Operating Hours</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="hours-grid">
                        <div class="hours-row"><span class="hours-day">Monday - Friday</span><span class="hours-time">8:00 AM - 6:00 PM</span></div>
                        <div class="hours-row"><span class="hours-day">Saturday</span><span class="hours-time">9:00 AM - 2:00 PM</span></div>
                        <div class="hours-row"><span class="hours-day">Sunday</span><span class="hours-time">Closed</span></div>
                    </div>
                    <button class="btn btn-outline" style="margin-top:1.5rem;">Edit Hours</button>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header"><h2 class="section-heading">Notification Preferences</h2></div>
                <div style="padding: 1.5rem;">
                    <div class="toggle-row"><span>New appointment notifications</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Payment confirmations</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>New review alerts</span><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                    <div class="toggle-row"><span>Weekly summary email</span><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                </div>
            </div>

            <div class="section-card danger-card">
                <div class="section-card-header"><h2 class="section-heading" style="color:var(--red-600);">Danger Zone</h2></div>
                <div style="padding: 1.5rem;">
                    <p style="color:var(--text-secondary);margin-bottom:1rem;">These actions are permanent and cannot be undone.</p>
                    <button class="btn btn-outline" style="border-color:var(--red-400);color:var(--red-600);">Deactivate Clinic</button>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Logout ───────────────────────────────────────────────────
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
    window.location.href = '../../logout.php';
}

// ── Specialist Modal Implementation ──────────────────────────
function openAddSpecialistModal() {
    const existing = document.getElementById('specialist-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'specialist-modal';
    modal.className = 'clinic-modal-overlay';
    modal.innerHTML = `
        <div class="clinic-modal-container glass-effect premium-modal">
            <div class="clinic-modal-header">
                <div class="header-icon-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </div>
                <div class="header-text">
                    <h3>Add New Specialist</h3>
                    <p>Register a new professional to your clinic team</p>
                </div>
                <button class="clinic-modal-close-btn" onclick="closeSpecialistModal()">&times;</button>
            </div>
            <form id="add-specialist-form" class="clinic-modal-body" onsubmit="submitAddSpecialist(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <div class="input-with-icon">
                            <input type="text" name="first_name" required placeholder="Sarah">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <div class="input-with-icon">
                            <input type="text" name="last_name" required placeholder="Mitchell">
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Email Address</label>
                        <div class="input-with-icon">
                            <input type="email" name="email" required placeholder="sarah.m@clinic.com">
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Password</label>
                        <div class="input-with-icon">
                            <input type="password" name="password" required placeholder="••••••••••••">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <div class="input-with-icon">
                            <input type="text" name="specialization" required placeholder="Pediatrician">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <div class="input-with-icon">
                            <input type="number" name="experience" required min="1" placeholder="5">
                        </div>
                    </div>
                </div>
                <div class="clinic-modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeSpecialistModal()">Discard</button>
                    <button type="submit" class="btn-submit">
                        <span>Add Specialist</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Inject local styles for this modal if not present
    if (!document.getElementById('modal-premium-styles')) {
        const style = document.createElement('style');
        style.id = 'modal-premium-styles';
        style.innerHTML = `
            .premium-modal {
                max-width: 550px !important;
                background: rgba(255, 255, 255, 0.08) !important;
                backdrop-filter: blur(20px) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 24px !important;
                padding: 0 !important;
                overflow: hidden;
            }
            .clinic-modal-header {
                background: linear-gradient(135deg, rgba(0, 150, 136, 0.2), rgba(0, 188, 212, 0.1));
                padding: 30px;
                display: flex;
                align-items: center;
                gap: 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                position: relative;
            }
            .header-icon-circle {
                width: 50px;
                height: 50px;
                background: #009688;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }
            .header-icon-circle svg { width: 28px; height: 28px; }
            .header-text h3 { margin: 0; color: white; font-size: 20px; font-weight: 600; }
            .header-text p { margin: 5px 0 0; color: rgba(255,255,255,0.6); font-size: 14px; }
            .clinic-modal-close-btn {
                position: absolute;
                top: 20px;
                right: 20px;
                background: none;
                border: none;
                color: rgba(255,255,255,0.4);
                font-size: 28px;
                cursor: pointer;
            }
            .clinic-modal-body { padding: 30px; }
            .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .full-width { grid-column: span 2; }
            .form-group label { display: block; margin-bottom: 8px; color: rgba(255,255,255,0.8); font-size: 13px; font-weight: 500; }
            .input-with-icon input {
                width: 100%;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 12px;
                padding: 12px 16px;
                color: white;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            .input-with-icon input:focus {
                background: rgba(255,255,255,0.1);
                border-color: #009688;
                box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.1);
                outline: none;
            }
            .clinic-modal-actions { margin-top: 35px; display: flex; gap: 15px; justify-content: flex-end; }
            .btn-cancel {
                padding: 12px 24px;
                background: rgba(255,255,255,0.05);
                border: none;
                border-radius: 12px;
                color: rgba(255,255,255,0.6);
                cursor: pointer;
                transition: 0.3s;
            }
            .btn-cancel:hover { background: rgba(255,255,255,0.1); color: white; }
            .btn-submit {
                padding: 12px 30px;
                background: linear-gradient(135deg, #009688, #00bcd4);
                border: none;
                border-radius: 12px;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
                box-shadow: 0 10px 20px rgba(0, 150, 136, 0.2);
                transition: 0.3s;
            }
            .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(0, 150, 136, 0.3); }
            .btn-submit svg { width: 18px; height: 18px; }
        `;
        document.head.appendChild(style);
    }

    requestAnimationFrame(() => modal.classList.add('active'));
}

function closeSpecialistModal() {
    const modal = document.getElementById('specialist-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function submitAddSpecialist(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;
    submitBtn.innerText = 'Creating Account...';
    submitBtn.disabled = true;

    fetch('../../api_clinic_add_specialist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showClinicToast('Specialist added successfully!', 'success');
            closeSpecialistModal();
            showClinicView('specialists'); // Refresh list
        } else {
            showClinicToast(result.error || 'Failed to add specialist', 'error');
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        showClinicToast('Connection error. Please try again.', 'error');
        submitBtn.innerText = originalText;
        submitBtn.disabled = false;
    });
}

