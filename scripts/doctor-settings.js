// Doctor Settings View — Integrated into Dashboard
function getSettingsView() {
    setTimeout(() => initSettingsPageNew(), 50);
    return `<div class="dashboard-content">
        <div class="dashboard-header-section"><div>
            <h1 class="dashboard-title">Settings</h1>
            <p class="dashboard-subtitle">Manage your profile, preferences & practice settings</p>
        </div></div>
        <div class="ds-tabs">
            <button class="ds-tab active" onclick="switchDsTab('profile',this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>My Profile</button>
            <button class="ds-tab" onclick="switchDsTab('preferences',this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>Preferences</button>
            <button class="ds-tab" onclick="switchDsTab('notifications',this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>Notifications</button>
            <button class="ds-tab" onclick="switchDsTab('security',this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Security</button>
        </div>
        <div class="ds-panel active" id="ds-panel-profile"><div style="text-align:center;padding:3rem;color:var(--text-secondary);">Loading profile…</div></div>
        <div class="ds-panel" id="ds-panel-preferences">${getDsPreferencesHTML()}</div>
        <div class="ds-panel" id="ds-panel-notifications">${getDsNotificationsHTML()}</div>
        <div class="ds-panel" id="ds-panel-security">${getDsSecurityHTML()}</div>
    </div>`;
}

function switchDsTab(tab, btn) {
    document.querySelectorAll('.ds-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ds-panel').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const p = document.getElementById('ds-panel-' + tab);
    if (p) p.classList.add('active');
}

function getDsPreferencesHTML() {
    return `<div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><div><h3>Patient Age Groups</h3><p>Select the age groups you specialize in</p></div></div>
        <div class="ds-card-body"><div class="ds-chip-grid" id="ds-age-groups">
            <label class="ds-chip"><input type="checkbox" value="newborn" checked><span>Newborn (0-1m)</span></label>
            <label class="ds-chip"><input type="checkbox" value="infant" checked><span>Infant (1-12m)</span></label>
            <label class="ds-chip"><input type="checkbox" value="toddler" checked><span>Toddler (1-3y)</span></label>
            <label class="ds-chip"><input type="checkbox" value="preschool"><span>Preschool (3-5y)</span></label>
        </div></div></div>
        <div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg><div><h3>Therapy Approaches</h3><p>Methods and techniques you practice</p></div></div>
        <div class="ds-card-body"><div class="ds-chip-grid" id="ds-therapy-approaches">
            <label class="ds-chip"><input type="checkbox" value="aba" checked><span>ABA Therapy</span></label>
            <label class="ds-chip"><input type="checkbox" value="cbt"><span>CBT</span></label>
            <label class="ds-chip"><input type="checkbox" value="play-therapy" checked><span>Play Therapy</span></label>
            <label class="ds-chip"><input type="checkbox" value="speech-therapy"><span>Speech Therapy</span></label>
            <label class="ds-chip"><input type="checkbox" value="occupational"><span>Occupational Therapy</span></label>
            <label class="ds-chip"><input type="checkbox" value="sensory"><span>Sensory Integration</span></label>
            <label class="ds-chip"><input type="checkbox" value="family-therapy"><span>Family Therapy</span></label>
            <label class="ds-chip"><input type="checkbox" value="group-therapy"><span>Group Therapy</span></label>
        </div></div></div>
        <div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><div><h3>Session Preferences</h3><p>Configure your default consultation settings</p></div></div>
        <div class="ds-card-body">
            <div class="ds-field-row">
                <div class="ds-field"><label>Default Session Duration</label><select class="ds-select" id="ds-session-duration">
                    <option value="15">15 minutes</option><option value="30" selected>30 minutes</option><option value="45">45 minutes</option><option value="60">60 minutes</option><option value="90">90 minutes</option>
                </select></div>
                <div class="ds-field"><label>Max Patients Per Day</label><select class="ds-select" id="ds-max-patients">
                    <option value="5">5 patients</option><option value="8">8 patients</option><option value="10" selected>10 patients</option><option value="15">15 patients</option><option value="20">20 patients</option>
                </select></div>
            </div>
            <div class="ds-field-row">
                <div class="ds-field"><label>Preferred Consultation Mode</label><select class="ds-select" id="ds-consult-mode">
                    <option value="both" selected>Online & On-site</option><option value="online">Online Only</option><option value="onsite">On-site Only</option>
                </select></div>
                <div class="ds-field"><label>Follow-up Reminder</label><select class="ds-select" id="ds-followup">
                    <option value="1week" selected>After 1 week</option><option value="2weeks">After 2 weeks</option><option value="1month">After 1 month</option><option value="custom">Custom</option>
                </select></div>
            </div>
            <label class="ds-field-label" style="margin-top:1.25rem">Working Days</label>
            <div class="dr-days-grid" id="ds-pref-days"></div>
            <label class="ds-field-label" style="margin-top:1rem">Working Hours</label>
            <div class="dr-hours-row"><label for="ds-pref-st">From</label><input type="time" id="ds-pref-st" class="dr-time-input" value="09:00"><span class="dr-hours-separator">—</span><label for="ds-pref-et">To</label><input type="time" id="ds-pref-et" class="dr-time-input" value="17:00"></div>
        </div></div>
        <div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><div><h3>Focus Areas</h3><p>Conditions and areas of special interest</p></div></div>
        <div class="ds-card-body"><div class="ds-chip-grid" id="ds-focus-areas">
            <label class="ds-chip"><input type="checkbox" value="autism" checked><span>Autism Spectrum</span></label>
            <label class="ds-chip"><input type="checkbox" value="adhd"><span>ADHD</span></label>
            <label class="ds-chip"><input type="checkbox" value="speech-delay" checked><span>Speech Delay</span></label>
            <label class="ds-chip"><input type="checkbox" value="learning-disability"><span>Learning Disabilities</span></label>
            <label class="ds-chip"><input type="checkbox" value="motor-delay"><span>Motor Delay</span></label>
            <label class="ds-chip"><input type="checkbox" value="behavioral"><span>Behavioral Issues</span></label>
            <label class="ds-chip"><input type="checkbox" value="anxiety"><span>Anxiety & Stress</span></label>
            <label class="ds-chip"><input type="checkbox" value="developmental"><span>Developmental Delays</span></label>
        </div></div></div>
        <div class="ds-actions"><button class="btn btn-gradient" onclick="dsSavePreferences()">Save Preferences</button></div>`;
}

function getDsNotificationsHTML() {
    const prefs = JSON.parse(localStorage.getItem(`dr_notif_prefs_${SPECIALIST_ID}`) || '{"new_appointment":true,"new_message":true,"report_shared":true}');
    return `<div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><div><h3>Notification Preferences</h3><p>Control how and when you receive alerts</p></div></div>
        <div class="ds-card-body"><div class="ds-toggle-list">
            <div class="ds-toggle-row"><div><strong>New Appointment</strong><p>Alert when a parent books an appointment</p></div><label class="ds-switch"><input type="checkbox" id="ds-notif-app" ${prefs.new_appointment ? 'checked' : ''}><span></span></label></div>
            <div class="ds-toggle-row"><div><strong>New Message</strong><p>Alert when a parent sends a message</p></div><label class="ds-switch"><input type="checkbox" id="ds-notif-msg" ${prefs.new_message ? 'checked' : ''}><span></span></label></div>
            <div class="ds-toggle-row"><div><strong>Report Shared</strong><p>Alert when a parent shares a child report</p></div><label class="ds-switch"><input type="checkbox" id="ds-notif-rep" ${prefs.report_shared ? 'checked' : ''}><span></span></label></div>
            <div class="ds-toggle-row"><div><strong>Appointment Reminders</strong><p>Get notified 30 min before appointments</p></div><label class="ds-switch"><input type="checkbox" checked disabled><span></span></label></div>
        </div></div></div>
        <div class="ds-actions"><button class="btn btn-gradient" onclick="dsSaveNotificationPrefs()">Save Preferences</button></div>`;
}

function dsSaveNotificationPrefs() {
    const prefs = {
        new_appointment: document.getElementById('ds-notif-app')?.checked || false,
        new_message: document.getElementById('ds-notif-msg')?.checked || false,
        report_shared: document.getElementById('ds-notif-rep')?.checked || false
    };
    localStorage.setItem(`dr_notif_prefs_${SPECIALIST_ID}`, JSON.stringify(prefs));
    showToast('Notification preferences saved!','success');
    if (typeof loadDoctorNotifications === 'function') loadDoctorNotifications();
}

function getDsSecurityHTML() {
    return `<div class="ds-card">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><div><h3>Change Password</h3><p>Update your login credentials</p></div></div>
        <div class="ds-card-body">
            <div class="ds-field"><label>Current Password</label><input type="password" class="ds-input" id="ds-cur-pw" placeholder="••••••••"></div>
            <div class="ds-field"><label>New Password</label><input type="password" class="ds-input" id="ds-new-pw" placeholder="Min. 6 characters"></div>
            <div class="ds-field"><label>Confirm New Password</label><input type="password" class="ds-input" id="ds-conf-pw" placeholder="Repeat new password"></div>
            <button class="btn btn-gradient" onclick="dsChangePassword()" style="margin-top:0.5rem;">Update Password</button>
        </div></div>
        <div class="ds-card ds-danger">
        <div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><div><h3>Danger Zone</h3><p>Irreversible actions</p></div></div>
        <div class="ds-card-body">
            <button class="btn btn-outline" style="color:var(--red-500);border-color:var(--red-500);" onclick="showToast('Please contact admin to delete your account','error')">Delete Account</button>
            <button class="btn btn-outline" onclick="handleLogout()" style="margin-left:0.75rem;">Sign Out All Devices</button>
        </div></div>`;
}

function dsChangePassword() {
    const cur = document.getElementById('ds-cur-pw')?.value;
    const nw = document.getElementById('ds-new-pw')?.value;
    const conf = document.getElementById('ds-conf-pw')?.value;
    if (!cur || !nw) { showToast('Fill all password fields', 'error'); return; }
    if (nw.length < 6) { showToast('Min 6 characters', 'error'); return; }
    if (nw !== conf) { showToast('Passwords do not match', 'error'); return; }
    fetch('doctor-dashboard.php?ajax=1&section=settings', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'change_password', current_password: cur, new_password: nw })
    }).then(r => r.json()).then(res => {
        if (res.success) { showToast('Password changed!', 'success'); ['ds-cur-pw','ds-new-pw','ds-conf-pw'].forEach(id => { const e = document.getElementById(id); if(e) e.value=''; }); }
        else showToast(res.error || 'Failed', 'error');
    }).catch(() => showToast('Connection error', 'error'));
}

function initSettingsPageNew() {
    loadDsProfile();
}

function loadDsProfile() {
    fetch('doctor-dashboard.php?ajax=1&section=settings&action=get_profile')
        .then(r => r.json()).then(res => {
            if (res.success) renderDsProfile(res.data);
            else document.getElementById('ds-panel-profile').innerHTML = '<div class="ds-card"><div class="ds-card-body"><p style="color:var(--red-500)">Error: ' + (res.error||'Profile unavailable') + '</p></div></div>';
        }).catch(err => {
            console.error('Profile load error:', err);
            document.getElementById('ds-panel-profile').innerHTML = '<div class="ds-card"><div class="ds-card-body"><p style="color:var(--red-500)">Failed to load profile. Check console for details.</p></div></div>';
        });
}

function renderDsProfile(d) {
    const initials = ((d.first_name||'D').charAt(0) + (d.last_name||'R').charAt(0)).toUpperCase();
    const fullName = 'Dr. ' + (d.first_name||'') + ' ' + (d.last_name||'');
    const spec = d.specialization || 'Specialist';
    const specVals = ['pediatrician','child-psychiatrist','developmental-pediatrician','neurologist','speech-therapist','occupational-therapist','behavioral-therapist','psychologist','other'];
    const specLabels = ['Pediatrician','Child Psychiatrist','Dev. Pediatrician','Pediatric Neurologist','Speech-Language Path.','Occupational Therapist','Behavioral Therapist','Child Psychologist','Other'];
    const specOpts = specVals.map((v,i) => `<option value="${v}" ${spec===v?'selected':''}>${specLabels[i]}</option>`).join('');

    document.getElementById('ds-panel-profile').innerHTML = `
        <div class="ds-profile-banner">
            <div class="ds-profile-avatar" onclick="document.getElementById('ds-photo').click()" title="Change photo">
                <div class="ds-avatar-circle" id="ds-avatar">${initials}</div>
                <div class="ds-avatar-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
                <input type="file" id="ds-photo" accept="image/*" style="display:none">
            </div>
            <div class="ds-profile-meta"><h2>${fullName}</h2><span class="ds-spec-badge">${spec}</span>
                <p class="ds-verified"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Verified Healthcare Provider</p>
            </div>
        </div>
        <form id="ds-profile-form" novalidate>
        <div class="ds-card"><div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><div><h3>Personal Information</h3><p>Your name & contact details</p></div></div>
        <div class="ds-card-body"><div class="ds-field-row">
            <div class="ds-field"><label>Full Name <span class="required">*</span></label><input type="text" class="ds-input" id="ds-fullname" value="${fullName}" required></div>
            <div class="ds-field"><label>Email <span class="required">*</span></label><input type="email" class="ds-input" id="ds-email" value="${d.email||''}" required></div>
            <div class="ds-field"><label>Phone</label><input type="tel" class="ds-input" id="ds-phone" value="${d.phone||''}"></div>
        </div><div class="ds-field-row">
            <div class="ds-field" style="width:100%"><label>Bio</label><textarea class="ds-input" id="ds-bio" rows="3" placeholder="Write a short bio about your practice...">${d.bio||''}</textarea></div>
        </div></div></div>
        <div class="ds-card"><div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg><div><h3>Professional Information</h3><p>Your specialty & credentials</p></div></div>
        <div class="ds-card-body"><div class="ds-field-row">
            <div class="ds-field"><label>Specialty <span class="required">*</span></label><select class="ds-select" id="ds-specialty" onchange="dsSpcChange()">${specOpts}</select><input type="text" class="ds-input" id="ds-spec-other" placeholder="Enter specialty" style="display:none;margin-top:0.5rem"></div>
            <div class="ds-field"><label>Years of Experience <span class="required">*</span></label><input type="number" class="ds-input" id="ds-exp" value="${d.experience_years||0}" min="0" max="60"></div>
        </div><div class="ds-field">
            <label>Certifications</label>
            <div class="ds-cert-row">
                <input type="text" class="ds-input" id="ds-cert" value="${d.certificate_of_experience||''}" placeholder="e.g. MD, FAAP, Board Certified">
                <button type="button" class="ds-cert-upload-btn" onclick="document.getElementById('ds-cert-file').click()" title="Upload certificate document">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <input type="file" id="ds-cert-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none">
            </div>
            <div id="ds-cert-file-name" class="ds-cert-file-info"></div>
        </div></div></div>
        <div class="ds-card"><div class="ds-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg><div><h3>Clinic Information</h3><p>Managed by your clinic admin</p></div></div>
        <div class="ds-card-body"><div class="ds-field-row">
            <div class="ds-field"><label>Clinic Name</label><input type="text" class="ds-input ds-readonly" value="${d.clinic_name||''}" readonly></div>
            <div class="ds-field"><label>Clinic Location</label><input type="text" class="ds-input ds-readonly" value="${d.clinic_location||''}" readonly></div>
        </div></div></div>
        <div class="ds-actions">
            <button type="button" class="btn btn-outline" onclick="loadDsProfile()">Reset</button>
            <button type="submit" class="btn btn-gradient">Save Changes</button>
        </div></form>`;

    // Photo upload
    document.getElementById('ds-photo')?.addEventListener('change', function(e) {
        const f = e.target.files[0]; if (!f) return;
        if (f.size > 5*1024*1024) { showToast('Max 5MB','error'); return; }
        const r = new FileReader();
        r.onload = ev => { const a = document.getElementById('ds-avatar'); if(a) a.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`; };
        r.readAsDataURL(f);
    });
    // Cert file upload
    document.getElementById('ds-cert-file')?.addEventListener('change', function(e) {
        const f = e.target.files[0]; if (!f) return;
        if (f.size > 10*1024*1024) { showToast('Max 10MB','error'); return; }
        const info = document.getElementById('ds-cert-file-name');
        if (info) info.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> ' + f.name + ' <button type="button" onclick="clearCertFile()" style="background:none;border:none;color:var(--red-500);cursor:pointer;font-weight:700;">&times;</button>';
        showToast('Certificate file selected','success');
    });
    // Populate preference days from profile data
    populatePrefDays(d);
    
    // Populate focus areas and consultation types
    try {
        if (d.focus_areas) {
            const focus = JSON.parse(d.focus_areas);
            document.querySelectorAll('#ds-focus-areas input[type="checkbox"]').forEach(cb => {
                cb.checked = focus.includes(cb.value);
            });
        }
        if (d.consultation_types) {
            const types = JSON.parse(d.consultation_types);
            const modeSelect = document.getElementById('ds-consult-mode');
            if (modeSelect) {
                if (types.includes('online') && types.includes('onsite')) modeSelect.value = 'both';
                else if (types.includes('online')) modeSelect.value = 'online';
                else if (types.includes('onsite')) modeSelect.value = 'onsite';
            }
        }
    } catch(e) {}

    // Form submit
    document.getElementById('ds-profile-form')?.addEventListener('submit', dsSubmitProfile);
    dsSpcChange();
}

function dsSpcChange() {
    const s = document.getElementById('ds-specialty');
    const o = document.getElementById('ds-spec-other');
    if (s && o) o.style.display = s.value === 'other' ? 'block' : 'none';
}

function dsSubmitProfile(e) {
    e.preventDefault();
    const np = (document.getElementById('ds-fullname')?.value||'').trim().replace(/^Dr\.\s*/i,'').split(' ');
    const fn = np[0]||'', ln = np.slice(1).join(' ')||'';
    const email = (document.getElementById('ds-email')?.value||'').trim();
    const sel = document.getElementById('ds-specialty');
    const oth = document.getElementById('ds-spec-other');
    const spec = (sel?.value==='other' ? oth?.value : sel?.value)||'';
    const exp = parseInt(document.getElementById('ds-exp')?.value||0);
    const cert = (document.getElementById('ds-cert')?.value||'').trim();
    const bio = (document.getElementById('ds-bio')?.value||'').trim();

    if (!fn || !email) { showToast('Name & email required','error'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Invalid email','error'); return; }

    fetch('doctor-dashboard.php?ajax=1&section=settings', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'save_profile', first_name:fn, last_name:ln, email, phone:(document.getElementById('ds-phone')?.value||''), specialization:spec, experience_years:exp, certificate_of_experience:cert, bio})
    }).then(r=>r.json()).then(res => {
        if (res.success) showToast('Profile saved!','success');
        else showToast(res.error||'Save failed','error');
    }).catch(()=>showToast('Connection error','error'));
}

function clearCertFile() {
    const input = document.getElementById('ds-cert-file');
    const info = document.getElementById('ds-cert-file-name');
    if (input) input.value = '';
    if (info) info.innerHTML = '';
}

function populatePrefDays(d) {
    const activeSlots = (d.slots||[]).map(s => parseInt(s.day_of_week));
    const st = d.slots&&d.slots[0] ? (d.slots[0].start_time||'09:00').substring(0,5) : '09:00';
    const et = d.slots&&d.slots[0] ? (d.slots[0].end_time||'17:00').substring(0,5) : '17:00';
    const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const container = document.getElementById('ds-pref-days');
    if (container) {
        container.innerHTML = dayNames.map((day,i) => `<div class="dr-day-checkbox"><input type="checkbox" id="ds-day-${i}" value="${i}" ${activeSlots.includes(i)?'checked':''}><label for="ds-day-${i}">${day}</label></div>`).join('');
    }
    const stEl = document.getElementById('ds-pref-st');
    const etEl = document.getElementById('ds-pref-et');
    if (stEl) stEl.value = st;
    if (etEl) etEl.value = et;
}

function dsSavePreferences() {
    // Save working days/hours
    const days = [];
    for (let i=0;i<=6;i++) { if (document.getElementById(`ds-day-${i}`)?.checked) days.push(i); }
    const st = document.getElementById('ds-pref-st')?.value;
    const et = document.getElementById('ds-pref-et')?.value;
    
    // Save focus areas
    const focusAreas = [];
    document.querySelectorAll('#ds-focus-areas input[type="checkbox"]:checked').forEach(cb => focusAreas.push(cb.value));

    // Save consultation type
    const mode = document.getElementById('ds-consult-mode')?.value || 'both';
    const consultTypes = [];
    if (mode === 'both' || mode === 'online') consultTypes.push('online');
    if (mode === 'both' || mode === 'onsite') consultTypes.push('onsite');

    if (days.length > 0 && st && et) {
        fetch('doctor-dashboard.php?ajax=1&section=settings', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'save_slots', days, start_time:st, end_time:et, slot_duration:30, focus_areas: focusAreas, consultation_types: consultTypes})
        }).then(r=>r.json()).then(res => {
            if (res.success) showToast('Preferences saved!','success');
            else showToast(res.error||'Save failed','error');
        }).catch(()=>showToast('Connection error','error'));
    } else {
        showToast('Preferences saved!','success');
    }
}
