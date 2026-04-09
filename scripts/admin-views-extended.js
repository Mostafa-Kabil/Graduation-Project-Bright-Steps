// ═══ VIEW USER MODAL ═══
async function viewUser(userId) {
    showModal('Loading...', '<div style="display:flex;justify-content:center;padding:2rem;"><div class="admin-loading-spinner"></div></div>', '');
    try {
        const data = await apiGet(`users.php?action=list`);
        const user = (data.users || []).find(u => u.user_id == userId);
        if (!user) { showAlert('User not found', 'error'); return; }
        showModal(`View User — ${user.first_name} ${user.last_name}`, `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value">${user.first_name || ''} ${user.last_name || ''}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${user.email || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value"><span class="role-badge role-${user.role}">${user.role}</span></span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${user.status === 'active' ? 'status-active' : 'status-danger'}">${user.status}</span></span></div>
                <div class="detail-row"><span class="detail-label">User ID</span><span class="detail-value">#${user.user_id}</span></div>
                <div class="detail-row"><span class="detail-label">Joined</span><span class="detail-value">${fmtDate(user.created_at)}</span></div>
            </div>`, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
    } catch (e) { showAlert('Error: ' + e.message, 'error'); }
}

// ═══ VIEW CLINIC MODAL ═══
function viewClinic(id, name, email, location, status, rating, specialists, patients) {
    showModal(`View Clinic — ${name}`, `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="detail-row"><span class="detail-label">Clinic Name</span><span class="detail-value">${name}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${email || '—'}</span></div>
            <div class="detail-row"><span class="detail-label">Location</span><span class="detail-value">${location || '—'}</span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge ${status === 'verified' ? 'status-active' : 'status-warning'}">${status}</span></span></div>
            <div class="detail-row"><span class="detail-label">Rating</span><span class="detail-value"><span class="rating-badge">★ ${Number(rating).toFixed(1)}</span></span></div>
            <div class="detail-row"><span class="detail-label">Specialists</span><span class="detail-value">${specialists}</span></div>
            <div class="detail-row"><span class="detail-label">Patients</span><span class="detail-value">${patients}</span></div>
            <div class="detail-row"><span class="detail-label">Clinic ID</span><span class="detail-value">#${id}</span></div>
        </div>`, `<button class="btn btn-outline" onclick="closeModal()">Close</button>`);
}

