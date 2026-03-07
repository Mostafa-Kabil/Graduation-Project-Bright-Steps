// ─────────────────────────────────────────────────────────────
//  Clinic Dashboard – View Controller
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initClinicNav();
    showClinicView('specialists'); // default view
});

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
                <button class="btn btn-gradient">
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
                <input type="text" class="search-input" placeholder="Search specialists...">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Specialist</th><th>Specialization</th><th>Experience</th><th>Patients</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar">SM</div><div><div class="patient-name">Dr. Sarah Mitchell</div><div class="patient-details">sarah.m@citykids.com</div></div></div></td>
                            <td>Pediatrician</td><td>12 years</td><td>34</td>
                            <td><span class="rating-badge">★ 4.9</span></td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">MC</div><div><div class="patient-name">Dr. Michael Chen</div><div class="patient-details">michael.c@citykids.com</div></div></div></td>
                            <td>Child Psychologist</td><td>8 years</td><td>22</td>
                            <td><span class="rating-badge">★ 4.7</span></td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">AR</div><div><div class="patient-name">Dr. Aisha Rahman</div><div class="patient-details">aisha.r@citykids.com</div></div></div></td>
                            <td>Speech Therapist</td><td>6 years</td><td>18</td>
                            <td><span class="rating-badge">★ 4.8</span></td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">JW</div><div><div class="patient-name">Dr. James Wilson</div><div class="patient-details">james.w@citykids.com</div></div></div></td>
                            <td>Occupational Therapist</td><td>10 years</td><td>28</td>
                            <td><span class="rating-badge">★ 4.6</span></td>
                            <td><span class="status-badge status-away">On Leave</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #ec4899, #db2777);">LP</div><div><div class="patient-name">Dr. Lisa Park</div><div class="patient-details">lisa.p@citykids.com</div></div></div></td>
                            <td>Developmental Pediatrician</td><td>15 years</td><td>41</td>
                            <td><span class="rating-badge">★ 4.9</span></td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td><button class="btn btn-sm btn-outline">View</button></td>
                        </tr>
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
                <button class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar View
                </button>
                <button class="btn btn-gradient">+ New Appointment</button>
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
                <div class="patient-row">
                    <div class="appointment-time-badge">
                        <div class="apt-time">10:30 AM</div>
                        <div class="apt-date">Today</div>
                    </div>
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">LT</div>
                    <div class="patient-info">
                        <div class="patient-name">Liam Thompson</div>
                        <div class="patient-details">with Dr. Michael Chen • Behavioral Assessment</div>
                    </div>
                    <div class="patient-status status-yellow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l-4 4l6 6"/><path d="M21 10H9"/></svg>
                        Online
                    </div>
                    <button class="btn btn-sm btn-outline">Details</button>
                </div>
                <div class="patient-row">
                    <div class="appointment-time-badge">
                        <div class="apt-time">2:00 PM</div>
                        <div class="apt-date">Today</div>
                    </div>
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">OW</div>
                    <div class="patient-info">
                        <div class="patient-name">Olivia Williams</div>
                        <div class="patient-details">with Dr. Aisha Rahman • Speech Therapy Session</div>
                    </div>
                    <div class="patient-status status-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Onsite
                    </div>
                    <button class="btn btn-sm btn-outline">Details</button>
                </div>
                <div class="patient-row">
                    <div class="appointment-time-badge">
                        <div class="apt-time">3:30 PM</div>
                        <div class="apt-date">Tomorrow</div>
                    </div>
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">NS</div>
                    <div class="patient-info">
                        <div class="patient-name">Noah Smith</div>
                        <div class="patient-details">with Dr. Lisa Park • Development Review</div>
                    </div>
                    <div class="patient-status status-yellow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l-4 4l6 6"/><path d="M21 10H9"/></svg>
                        Online
                    </div>
                    <button class="btn btn-sm btn-outline">Details</button>
                </div>
            </div>
        </div>
    </div>`;
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
                <input type="text" class="search-input" placeholder="Search by name or parent...">
            </div>
            <div class="clinic-table-wrap">
                <table class="clinic-table">
                    <thead>
                        <tr><th>Child</th><th>Age</th><th>Parent/Guardian</th><th>Assigned Specialist</th><th>Status</th><th>Last Visit</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar">EJ</div><div><div class="patient-name">Emma Johnson</div><div class="patient-details">SSN: ***-**-1234</div></div></div></td>
                            <td>15 months</td>
                            <td>Sarah Johnson</td>
                            <td>Dr. Sarah Mitchell</td>
                            <td><span class="status-badge status-active">On Track</span></td>
                            <td>Nov 20, 2025</td>
                            <td><button class="btn btn-sm btn-outline">View Records</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">LT</div><div><div class="patient-name">Liam Thompson</div><div class="patient-details">SSN: ***-**-5678</div></div></div></td>
                            <td>18 months</td>
                            <td>Michael Thompson</td>
                            <td>Dr. Michael Chen</td>
                            <td><span class="status-badge status-warning">Needs Review</span></td>
                            <td>Nov 15, 2025</td>
                            <td><button class="btn btn-sm btn-outline">View Records</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">OW</div><div><div class="patient-name">Olivia Williams</div><div class="patient-details">SSN: ***-**-9012</div></div></div></td>
                            <td>12 months</td>
                            <td>Jennifer Williams</td>
                            <td>Dr. Aisha Rahman</td>
                            <td><span class="status-badge status-active">On Track</span></td>
                            <td>Nov 22, 2025</td>
                            <td><button class="btn btn-sm btn-outline">View Records</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">NS</div><div><div class="patient-name">Noah Smith</div><div class="patient-details">SSN: ***-**-3456</div></div></div></td>
                            <td>24 months</td>
                            <td>David Smith</td>
                            <td>Dr. Lisa Park</td>
                            <td><span class="status-badge status-active">On Track</span></td>
                            <td>Nov 18, 2025</td>
                            <td><button class="btn btn-sm btn-outline">View Records</button></td>
                        </tr>
                        <tr>
                            <td><div class="table-user"><div class="patient-avatar" style="background: linear-gradient(135deg, #ec4899, #db2777);">SA</div><div><div class="patient-name">Sophia Ahmed</div><div class="patient-details">SSN: ***-**-7890</div></div></div></td>
                            <td>9 months</td>
                            <td>Fatima Ahmed</td>
                            <td>Dr. James Wilson</td>
                            <td><span class="status-badge status-danger">Needs Attention</span></td>
                            <td>Nov 10, 2025</td>
                            <td><button class="btn btn-sm btn-outline">View Records</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
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
                <button class="btn btn-outline">Export Report</button>
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
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = 'index.php';
    }
}
