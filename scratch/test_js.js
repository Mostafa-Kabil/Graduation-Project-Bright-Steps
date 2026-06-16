const fs = require('fs');
const patients = [
    {
        "child_id": 241,
        "child_first_name": "Nour",
        "child_last_name": "Magdy",
        "gender": "female",
        "birth_year": 2023,
        "birth_month": 12,
        "birth_day": 18,
        "parent_first_name": "Omar",
        "parent_last_name": "Ali",
        "parent_id": 210,
        "last_appointment_status": "pending",
        "last_appointment_date": "2026-06-29 19:25:06"
    }
];

function calculateAge(year, month, day) { return "1 year"; }
function formatRelativeDate(d) { return "Tomorrow"; }
function viewPatientDetail() {}
function openReportForChild() {}
function chatWithParent() {}

// mock DOM
const document = {
    getElementById: (id) => {
        if (id === 'patientsListContainer') {
            return {
                set innerHTML(val) { console.log("HTML set length:", val.length); },
                get innerHTML() { return ""; }
            };
        }
        return { textContent: '' };
    }
};

function renderPatientsEmpty() { console.log("renderPatientsEmpty called!"); }

// copy renderPatientsList here
function renderPatientsList(patients) {
    const container = document.getElementById('patientsListContainer');
    if (!container) return;
    if (!patients.length) { renderPatientsEmpty(); return; }
    let html = '';
    patients.forEach(p => {
        const initials = (p.child_first_name?.charAt(0) || '') + (p.child_last_name?.charAt(0) || '');
        const age = calculateAge(p.birth_year, p.birth_month, p.birth_day);
        const status = (p.last_appointment_status || 'pending').toLowerCase();
        let statusClass = 'status-yellow';
        let statusLabel = 'Pending Review';
        if (status === 'completed') { statusClass = 'status-green'; statusLabel = 'On Track'; }
        else if (status === 'confirmed') { statusClass = 'status-blue'; statusLabel = 'Upcoming'; }
        else if (status === 'cancelled') { statusClass = 'status-red'; statusLabel = 'Cancelled'; }
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
                <button class="btn btn-sm btn-outline" style="color:var(--green-500);border-color:var(--green-500);" onclick="chatWithParent(${p.parent_id}, '${p.parent_first_name} ${p.parent_last_name}')" title="Chat with parent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Chat
                </button>
            </div></div>`;
    });
    container.innerHTML = html;
}

function updatePatientsStats(patients, thisWeekKpi) {
    const total = patients.length;
    const onTrack = patients.filter(p => (p.last_appointment_status || '').toLowerCase() === 'completed').length;
    const needsAttention = patients.filter(p => {
        const st = (p.last_appointment_status || 'pending').toLowerCase();
        return st === 'pending' || st === 'scheduled' || st === 'pending reschedule';
    }).length;
    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    el('stat-active-patients', total); el('stat-on-track', onTrack); el('stat-needs-attention', needsAttention); el('stat-this-week-patients', thisWeekKpi || 0);
    const sub = document.getElementById('patientsSubtitle');
    if (sub) sub.textContent = `You have ${total} active patient${total !== 1 ? 's' : ''}`;
}

try {
    renderPatientsList(patients);
    updatePatientsStats(patients, 0);
    console.log("Success");
} catch(e) {
    console.error(e);
}
