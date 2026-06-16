const fs = require('fs');
const payload = {"success":true,"data":[{"child_id":16,"child_first_name":"Laila","child_last_name":"Family","gender":"female","birth_year":2018,"birth_month":11,"birth_day":4,"parent_first_name":"Noha","parent_last_name":"Mansour","parent_id":85,"last_appointment_status":"confirmed","last_appointment_date":"2026-06-28 19:01:03"},{"child_id":15,"child_first_name":"Farida","child_last_name":"Family","gender":"female","birth_year":2023,"birth_month":11,"birth_day":14,"parent_first_name":"Noha","parent_last_name":"Mansour","parent_id":85,"last_appointment_status":"confirmed","last_appointment_date":"2026-06-28 19:01:03"},{"child_id":18,"child_first_name":"Yassin","child_last_name":"Family","gender":"male","birth_year":2020,"birth_month":8,"birth_day":24,"parent_first_name":"Seif","parent_last_name":"Osman","parent_id":86,"last_appointment_status":"pending","last_appointment_date":"2026-06-27 19:02:15"},{"child_id":17,"child_first_name":"Adam","child_last_name":"Family","gender":"male","birth_year":2020,"birth_month":5,"birth_day":16,"parent_first_name":"Seif","parent_last_name":"Osman","parent_id":86,"last_appointment_status":"pending","last_appointment_date":"2026-06-27 19:02:15"},{"child_id":209,"child_first_name":"Yassin","child_last_name":"Moaz","gender":"male","birth_year":2025,"birth_month":10,"birth_day":1,"parent_first_name":"Moaz","parent_last_name":"Ali","parent_id":76,"last_appointment_status":"confirmed","last_appointment_date":"2026-06-25 19:01:03"},{"child_id":207,"child_first_name":"Omar","child_last_name":"Moaz","gender":"male","birth_year":2022,"birth_month":3,"birth_day":10,"parent_first_name":"Moaz","parent_last_name":"Ali","parent_id":76,"last_appointment_status":"confirmed","last_appointment_date":"2026-06-25 19:01:03"},{"child_id":208,"child_first_name":"Laila","child_last_name":"Moaz","gender":"female","birth_year":2024,"birth_month":5,"birth_day":15,"parent_first_name":"Moaz","parent_last_name":"Ali","parent_id":76,"last_appointment_status":"confirmed","last_appointment_date":"2026-06-25 19:01:03"},{"child_id":14,"child_first_name":"Ahmed","child_last_name":"Family","gender":"male","birth_year":2022,"birth_month":11,"birth_day":25,"parent_first_name":"Khaled","parent_last_name":"Shawky","parent_id":84,"last_appointment_status":"pending","last_appointment_date":"2026-06-24 19:04:02"},{"child_id":13,"child_first_name":"Mariam","child_last_name":"Family","gender":"female","birth_year":2019,"birth_month":5,"birth_day":23,"parent_first_name":"Khaled","parent_last_name":"Shawky","parent_id":84,"last_appointment_status":"pending","last_appointment_date":"2026-06-24 19:04:02"},{"child_id":2,"child_first_name":"Habiba","child_last_name":"Family","gender":"female","birth_year":2018,"birth_month":7,"birth_day":16,"parent_first_name":"Khaled","parent_last_name":"Kamel","parent_id":77,"last_appointment_status":"pending","last_appointment_date":"2026-06-22 19:04:02"},{"child_id":5,"child_first_name":"Dina","child_last_name":"Family","gender":"female","birth_year":2021,"birth_month":11,"birth_day":10,"parent_first_name":"Aya","parent_last_name":"Roshdy","parent_id":79,"last_appointment_status":"pending","last_appointment_date":"2026-06-21 19:02:15"},{"child_id":3,"child_first_name":"Ahmed","child_last_name":"Family","gender":"male","birth_year":2019,"birth_month":3,"birth_day":8,"parent_first_name":"Aya","parent_last_name":"Mahmoud","parent_id":78,"last_appointment_status":"pending","last_appointment_date":"2026-06-20 19:04:02"},{"child_id":4,"child_first_name":"Youssef","child_last_name":"Family","gender":"male","birth_year":2018,"birth_month":7,"birth_day":7,"parent_first_name":"Aya","parent_last_name":"Mahmoud","parent_id":78,"last_appointment_status":"pending","last_appointment_date":"2026-06-20 19:04:02"},{"child_id":11,"child_first_name":"Mohamed","child_last_name":"Family","gender":"male","birth_year":2022,"birth_month":8,"birth_day":20,"parent_first_name":"Youssef","parent_last_name":"Kamal","parent_id":83,"last_appointment_status":"pending","last_appointment_date":"2026-06-20 19:02:15"},{"child_id":12,"child_first_name":"Mona","child_last_name":"Family","gender":"female","birth_year":2022,"birth_month":7,"birth_day":1,"parent_first_name":"Youssef","parent_last_name":"Kamal","parent_id":83,"last_appointment_status":"pending","last_appointment_date":"2026-06-20 19:02:15"},{"child_id":6,"child_first_name":"Ibrahim","child_last_name":"Family","gender":"male","birth_year":2018,"birth_month":4,"birth_day":28,"parent_first_name":"Farida","parent_last_name":"Saleh","parent_id":80,"last_appointment_status":"Completed","last_appointment_date":"2026-06-12 19:02:15"},{"child_id":7,"child_first_name":"Amr","child_last_name":"Family","gender":"male","birth_year":2023,"birth_month":1,"birth_day":25,"parent_first_name":"Farida","parent_last_name":"Saleh","parent_id":80,"last_appointment_status":"Completed","last_appointment_date":"2026-06-12 19:02:15"},{"child_id":9,"child_first_name":"Ahmed","child_last_name":"Family","gender":"male","birth_year":2021,"birth_month":1,"birth_day":15,"parent_first_name":"Noha","parent_last_name":"Tariq","parent_id":81,"last_appointment_status":"Completed","last_appointment_date":"2026-05-26 19:04:01"},{"child_id":8,"child_first_name":"Noha","child_last_name":"Family","gender":"female","birth_year":2018,"birth_month":12,"birth_day":6,"parent_first_name":"Noha","parent_last_name":"Tariq","parent_id":81,"last_appointment_status":"Completed","last_appointment_date":"2026-05-26 19:04:01"},{"child_id":10,"child_first_name":"Yasmin","child_last_name":"Family","gender":"female","birth_year":2022,"birth_month":4,"birth_day":25,"parent_first_name":"Farida","parent_last_name":"Mahmoud","parent_id":82,"last_appointment_status":"Completed","last_appointment_date":"2025-12-29 18:02:15"},{"child_id":99,"child_first_name":"Sara","child_last_name":"Family","gender":"female","birth_year":2022,"birth_month":3,"birth_day":15,"parent_first_name":"Heba","parent_last_name":"Sami","parent_id":135,"last_appointment_status":"Completed","last_appointment_date":"2023-03-09 14:31:20"}],"this_week_kpi":8};
const patients = payload.data;

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
