// Dashboard JavaScript
(function () {
    'use strict';

    // Protect dashboard
    protectDashboard();

    // Navigation items configuration
    const navItems = [
        { id: 'home', label: 'Home', icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
        { id: 'profile', label: 'Child Profile', icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>' },
        { id: 'growth', label: 'Growth', icon: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>' },
        { id: 'speech', label: 'Speech', icon: '<path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/>' },
        { id: 'motor', label: 'Motor Skills', icon: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>' },
        { id: 'activities', label: 'Activities', icon: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>' },
        { id: 'clinic', label: 'Book Clinic', icon: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
        { id: 'reports', label: 'Reports', icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>' }
    ];

    // Initialize navigation
    function initNav() {
        const navContainer = document.getElementById('sidebar-nav');
        if (!navContainer) return;

        navContainer.innerHTML = navItems.map(item => `
            <button class="nav-item ${item.id === 'home' ? 'active' : ''}" data-view="${item.id}">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${item.icon}
                </svg>
                <span>${item.label}</span>
            </button>
        `).join('');

        // Add click handlers
        navContainer.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function () {
                const view = this.dataset.view;
                switchView(view);
            });
        });
    }

    // Switch view
    function switchView(viewId) {
        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.dataset.view === viewId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Load view content
        loadView(viewId);
    }

    // Load view content
    function loadView(viewId) {
        const contentContainer = document.getElementById('dashboard-content');
        if (!contentContainer) return;

        const views = {
            'home': getHomeView,
            'profile': getProfileView,
            'growth': getGrowthView,
            'speech': getSpeechView,
            'motor': getMotorView,
            'activities': getActivitiesView,
            'clinic': getClinicView,
            'reports': getReportsView,
            'settings': getSettingsView
        };

        const viewFunction = views[viewId] || views['home'];
        contentContainer.innerHTML = viewFunction();
    }

    // View templates
    function getHomeView() {
        const template = document.getElementById('home-view-template');
        return template ? template.innerHTML : '<p>Loading...</p>';
    }

    function getProfileView() {
        return `
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Child Profile 👶</h1>
                        <p class="dashboard-subtitle">Manage profiles and view progress</p>
                    </div>
                    <button class="btn btn-outline">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Add Child
                    </button>
                </div>

                <!-- Child Selector Section -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-content">
                        <h3 class="card-title" style="margin-bottom: 1rem; font-size: 1rem;">Select Child Profile</h3>
                        <div style="display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem;">
                            <!-- Active Profile -->
                            <div style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                                <div style="width: 4rem; height: 4rem; background: var(--blue-600); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; border: 3px solid var(--blue-200); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                                    E
                                </div>
                                <span style="margin-top: 0.5rem; font-weight: 600; color: var(--blue-600);">Emma</span>
                                <span style="font-size: 0.75rem; color: var(--slate-500);">15 mo</span>
                            </div>

                            <!-- Inactive Profile -->
                            <div style="display: flex; flex-direction: column; align-items: center; cursor: pointer; opacity: 0.6;">
                                <div style="width: 4rem; height: 4rem; background: var(--purple-100); color: var(--purple-600); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; border: 3px solid transparent;">
                                    L
                                </div>
                                <span style="margin-top: 0.5rem; font-weight: 600; color: var(--slate-600);">Liam</span>
                                <span style="font-size: 0.75rem; color: var(--slate-500);">3 yo</span>
                            </div>

                            <!-- Add New Placeholder -->
                            <div style="display: flex; flex-direction: column; align-items: center; cursor: pointer; opacity: 0.6;">
                                <div style="width: 4rem; height: 4rem; border: 2px dashed var(--slate-300); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--slate-400);">
                                    <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </div>
                                <span style="margin-top: 0.5rem; font-weight: 500; color: var(--slate-500);">New</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Content for Selected Child -->
                <div class="child-profile-card" style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; gap: 1.5rem; align-items: center;">
                            <div class="child-avatar" style="width: 5rem; height: 5rem; font-size: 2rem;">E</div>
                            <div class="child-info">
                                <h2 class="child-name" style="font-size: 1.75rem;">Emma Johnson</h2>
                                <div class="child-details">
                                    <span>15 months old</span>
                                    <span>•</span>
                                    <span>Born: Aug 23, 2024</span>
                                    <span>•</span>
                                    <span style="color: var(--green-600); font-weight: 600;">On Track</span>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-ghost">Edit Details</button>
                    </div>
                </div>

                <!-- High Level Stats -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
                    <div class="dashboard-card" style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 0.5rem;">Weight</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--slate-900);">11.1 kg</div>
                        <div class="badge badge-green" style="margin-top: 0.5rem;">75th %</div>
                    </div>
                    <div class="dashboard-card" style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 0.5rem;">Height</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--slate-900);">78 cm</div>
                        <div class="badge badge-green" style="margin-top: 0.5rem;">60th %</div>
                    </div>
                    <div class="dashboard-card" style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 0.5rem;">Words</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--slate-900);">42</div>
                        <div class="badge badge-blue" style="margin-top: 0.5rem;">+5 this week</div>
                    </div>
                    <div class="dashboard-card" style="text-align: center; padding: 1.5rem;">
                         <div style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 0.5rem;">Streak</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--orange-500);">14 Days</div>
                        <div style="font-size: 0.75rem; color: var(--slate-400); margin-top: 0.5rem;">Keep it up!</div>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr;">
                    <!-- Development Status Section (Traffic Light) -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <h3 class="section-heading">Development Areas</h3>
                        
                        <div class="development-card card-green">
                            <div class="development-header">
                                <div class="development-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                    </svg>
                                </div>
                                <div class="development-status status-green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="development-title">Growth & Physical</h3>
                            <p class="development-description">Height and weight are developing perfectly on track. Motor skills overlap with expected milestones for 15 months.</p>
                            <span class="development-badge badge-green">On Track - Green</span>
                        </div>

                        <div class="development-card card-yellow">
                            <div class="development-header">
                                <div class="development-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10"/>
                                    </svg>
                                </div>
                                <div class="development-status status-yellow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="development-title">Speech & Language</h3>
                            <p class="development-description">Expression is good, but vocabulary size is slightly below average range. Focus on "Reading Time" activities.</p>
                            <span class="development-badge badge-yellow">Needs Attention - Yellow</span>
                        </div>
                    </div>

                    <!-- Right Column: Activities & Appointments -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                         <h3 class="section-heading">Recommended Actions</h3>
                        
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Daily Activities</h3>
                            </div>
                            <div class="card-content">
                                <div class="activity-item activity-blue">
                                    <div class="activity-icon">📚</div>
                                    <div class="activity-info">
                                        <h4 class="activity-title">Reading Time</h4>
                                        <span class="activity-duration">15 min</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline">Start</button>
                                </div>
                                 <div class="activity-item activity-purple">
                                    <div class="activity-icon">🎨</div>
                                    <div class="activity-info">
                                        <h4 class="activity-title">Block Stacking</h4>
                                        <span class="activity-duration">10 min</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline">Start</button>
                                </div>
                            </div>
                        </div>

                         <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Coming Up</h3>
                            </div>
                            <div class="card-content">
                                <div class="appointment-item">
                                    <div class="appointment-icon icon-blue-bg">📅</div>
                                    <div class="appointment-info">
                                        <div class="appointment-title">MMR Vaccination</div>
                                        <div class="appointment-date">Nov 28, 10:00 AM</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getGrowthView() {
        return `
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Growth Tracking 📏</h1>
                        <p class="dashboard-subtitle">Monitor Emma's physical development against WHO standards</p>
                    </div>
                    <button class="btn btn-gradient">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Log Measurement
                    </button>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                     <div class="dashboard-card">
                        <div class="card-content" style="text-align: center; padding: 1.5rem;">
                            <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 0.5rem;">Current Weight</p>
                            <h3 style="font-size: 2rem; font-weight: 800; color: var(--slate-900);">11.1 kg</h3>
                            <span class="badge badge-green" style="margin-top: 0.5rem;">75th Percentile</span>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-content" style="text-align: center; padding: 1.5rem;">
                            <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 0.5rem;">Current Height</p>
                            <h3 style="font-size: 2rem; font-weight: 800; color: var(--slate-900);">78 cm</h3>
                            <span class="badge badge-green" style="margin-top: 0.5rem;">60th Percentile</span>
                        </div>
                    </div>
                     <div class="dashboard-card">
                        <div class="card-content" style="text-align: center; padding: 1.5rem;">
                            <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 0.5rem;">Head Circumference</p>
                            <h3 style="font-size: 2rem; font-weight: 800; color: var(--slate-900);">46 cm</h3>
                            <span class="badge badge-green" style="margin-top: 0.5rem;">50th Percentile</span>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="justify-content: space-between; display: flex; align-items: center;">
                        <h3 class="card-title">Weight History</h3>
                        <div class="tab-group" style="display: flex; gap: 0.5rem;">
                            <button class="badge badge-blue">Weight</button>
                            <button class="badge" style="background: transparent; border: 1px solid var(--slate-200);">Height</button>
                        </div>
                    </div>
                    <div class="card-content">
                        <!-- Simulated Chart Area -->
                        <div style="height: 300px; width: 100%; position: relative; border-bottom: 2px solid var(--slate-200); border-left: 2px solid var(--slate-200); box-sizing: border-box; margin: 1rem 0;">
                             <!-- Grid Lines -->
                             <div style="position: absolute; bottom: 25%; left: 0; right: 0; border-top: 1px dashed var(--slate-200);"></div>
                             <div style="position: absolute; bottom: 50%; left: 0; right: 0; border-top: 1px dashed var(--slate-200);"></div>
                             <div style="position: absolute; bottom: 75%; left: 0; right: 0; border-top: 1px dashed var(--slate-200);"></div>
                             
                             <!-- Chart Path (Simulated) -->
                             <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: visible;">
                                <path d="M0,250 L50,230 L100,210 L150,180 L200,160 L250,140 L300,120" fill="none" stroke="var(--blue-500)" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="0" cy="250" r="4" fill="white" stroke="var(--blue-500)" stroke-width="2"/>
                                <circle cx="100" cy="210" r="4" fill="white" stroke="var(--blue-500)" stroke-width="2"/>
                                <circle cx="200" cy="160" r="4" fill="white" stroke="var(--blue-500)" stroke-width="2"/>
                                <circle cx="300" cy="120" r="4" fill="white" stroke="var(--blue-500)" stroke-width="2"/>
                             </svg>
                             
                             <!-- Labels -->
                             <div style="position: absolute; bottom: -25px; left: 0;">Aug</div>
                             <div style="position: absolute; bottom: -25px; left: 33%;">Sep</div>
                             <div style="position: absolute; bottom: -25px; left: 66%;">Oct</div>
                             <div style="position: absolute; bottom: -25px; right: 0;">Nov</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Measurements</h3>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--slate-100);">
                                <th style="text-align: left; padding: 1rem; color: var(--slate-500);">Date</th>
                                <th style="text-align: left; padding: 1rem; color: var(--slate-500);">Type</th>
                                <th style="text-align: left; padding: 1rem; color: var(--slate-500);">Value</th>
                                <th style="text-align: left; padding: 1rem; color: var(--slate-500);">Percentile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--slate-50);">
                                <td style="padding: 1rem;">Nov 15, 2025</td>
                                <td style="padding: 1rem;">Weight</td>
                                <td style="padding: 1rem; font-weight: 600;">11.1 kg</td>
                                <td style="padding: 1rem;"><span class="badge badge-green">75th</span></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--slate-50);">
                                <td style="padding: 1rem;">Nov 15, 2025</td>
                                <td style="padding: 1rem;">Height</td>
                                <td style="padding: 1rem; font-weight: 600;">78 cm</td>
                                <td style="padding: 1rem;"><span class="badge badge-green">60th</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 1rem;">Oct 12, 2025</td>
                                <td style="padding: 1rem;">Weight</td>
                                <td style="padding: 1rem; font-weight: 600;">10.8 kg</td>
                                <td style="padding: 1rem;"><span class="badge badge-green">72nd</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function getSpeechView() {
        return `
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Speech Analysis 🗣️</h1>
                        <p class="dashboard-subtitle">Track vocabulary and pronunciation progress</p>
                    </div>
                    <button class="btn btn-gradient">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                        New Recording
                    </button>
                </div>

                <div class="dashboard-grid" style="margin-bottom: 2rem;">
                     <!-- AI Insight Card -->
                     <div style="grid-column: span 2; background: linear-gradient(to right, var(--purple-600), var(--blue-600)); border-radius: var(--radius-xl); padding: 2rem; color: white;">
                        <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">AI Insight</h3>
                        <p style="margin-bottom: 1.5rem; opacity: 0.9;">Emma has added <strong>5 new words</strong> this week! Her articulation of "b" and "m" sounds has improved significantly.</p>
                        <div style="display: flex; gap: 1rem;">
                            <div style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius-lg);">
                                <span style="display: block; font-size: 0.75rem; opacity: 0.8;">Total Words</span>
                                <span style="font-size: 1.25rem; font-weight: 700;">42</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius-lg);">
                                <span style="display: block; font-size: 0.75rem; opacity: 0.8;">Clarity Score</span>
                                <span style="font-size: 1.25rem; font-weight: 700;">85%</span>
                            </div>
                        </div>
                     </div>
                </div>

                <h3 class="section-heading">Recent Recordings</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="dashboard-card" style="display: flex; align-items: center; padding: 1.5rem; gap: 1.5rem;">
                        <div style="width: 3rem; height: 3rem; background: var(--purple-100); color: var(--purple-700); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            ▶️
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-weight: 700; margin-bottom: 0.25rem;">Morning Chatter</h4>
                            <p style="color: var(--slate-500); font-size: 0.875rem;">Today, 9:30 AM • 45s</p>
                        </div>
                        <div style="text-align: right;">
                             <span class="badge badge-green" style="margin-bottom: 0.25rem;">Positive</span>
                             <p style="font-size: 0.75rem; color: var(--slate-500);">Identified: "Mama", "Ball"</p>
                        </div>
                        <button class="btn btn-ghost">View Analysis</button>
                    </div>

                    <div class="dashboard-card" style="display: flex; align-items: center; padding: 1.5rem; gap: 1.5rem;">
                         <div style="width: 3rem; height: 3rem; background: var(--purple-100); color: var(--purple-700); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            ▶️
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-weight: 700; margin-bottom: 0.25rem;">Reading Time</h4>
                            <p style="color: var(--slate-500); font-size: 0.875rem;">Yesterday, 6:15 PM • 1m 20s</p>
                        </div>
                        <div style="text-align: right;">
                             <span class="badge badge-blue" style="margin-bottom: 0.25rem;">Developing</span>
                             <p style="font-size: 0.75rem; color: var(--slate-500);">Focus: "Cat", "Dog"</p>
                        </div>
                        <button class="btn btn-ghost">View Analysis</button>
                    </div>
                </div>
            </div>
        `;
    }

    function getMotorView() {
        return `
             <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Motor Skills 🏃‍♂️</h1>
                        <p class="dashboard-subtitle">Gross and fine motor skill assessment</p>
                    </div>
                     <button class="btn btn-gradient">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <line x1="7" y1="2" x2="7" y2="22"></line>
                            <line x1="17" y1="2" x2="17" y2="22"></line>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <line x1="2" y1="7" x2="7" y2="7"></line>
                            <line x1="2" y1="17" x2="7" y2="17"></line>
                            <line x1="17" y1="17" x2="22" y2="17"></line>
                            <line x1="17" y1="7" x2="22" y2="7"></line>
                        </svg>
                        Upload Video
                    </button>
                </div>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Gross Motor</h3>
                            <span class="badge badge-green">On Track</span>
                        </div>
                        <div class="card-content">
                            <p style="margin-bottom: 1rem; color: var(--slate-600);">Emma is confidently walking and starting to run. She can climb onto furniture without assistance.</p>
                            <div class="progress-item" style="margin-bottom: 0.5rem;">
                                <div class="progress-label">
                                    <span>Walking Stability</span>
                                    <span>95%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 95%"></div>
                                </div>
                            </div>
                             <div class="progress-item">
                                <div class="progress-label">
                                    <span>Climbing</span>
                                    <span>80%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 80%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Fine Motor</h3>
                            <span class="badge badge-green">Advanced</span>
                        </div>
                        <div class="card-content">
                            <p style="margin-bottom: 1rem; color: var(--slate-600);">She is showing excellent pincer grasp control and can stack 4-5 blocks.</p>
                             <div class="progress-item" style="margin-bottom: 0.5rem;">
                                <div class="progress-label">
                                    <span>Pincer Grasp</span>
                                    <span>98%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 98%"></div>
                                </div>
                            </div>
                             <div class="progress-item">
                                <div class="progress-label">
                                    <span>Stacking</span>
                                    <span>90%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 90%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-heading" style="margin-top: 2rem;">Milestone Checklist (15 Months)</h3>
                <div class="dashboard-card">
                    <div style="display: grid; gap: 1px; background: var(--slate-100);">
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            <span style="flex: 1; text-decoration: line-through; color: var(--slate-500);">Walks without holding on</span>
                        </div>
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1; text-decoration: line-through; color: var(--slate-500);">Scribbles spontaneously</span>
                        </div>
                        <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1;">Drinks from a cup</span>
                        </div>
                         <div style="background: white; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" style="width: 1.25rem; height: 1.25rem;">
                             <span style="flex: 1;">Uses a spoon (with some spilling)</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getActivitiesView() {
        return `
            <div class="dashboard-content">
                 <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Activity Center 🎨</h1>
                        <p class="dashboard-subtitle">Personalized play for development</p>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h3 class="section-heading">Today's Schedule</h3>
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <div style="background: var(--orange-100); height: 8px; width: 100%;"></div>
                            <div class="card-content">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 700; color: var(--orange-600);">Morning</span>
                                    <span style="color: var(--slate-500);">10:00 AM</span>
                                </div>
                                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Sensory Bin Dig</h4>
                                <p style="font-size: 0.9rem; color: var(--slate-600); margin-bottom: 1rem;">Fill a bin with rice or pasta and hide small toys for Emma to find. Great for fine motor skills.</p>
                                <button class="btn btn-outline btn-sm" style="width: 100%;">Mark Complete</button>
                            </div>
                        </div>

                         <div class="dashboard-card">
                            <div style="background: var(--blue-100); height: 8px; width: 100%;"></div>
                            <div class="card-content">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 700; color: var(--blue-600);">Afternoon</span>
                                    <span style="color: var(--slate-500);">3:30 PM</span>
                                </div>
                                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Music & Movement</h4>
                                <p style="font-size: 0.9rem; color: var(--slate-600); margin-bottom: 1rem;">Dance to favorite nursery rhymes. Encourage clapping and stomping.</p>
                                <button class="btn btn-outline btn-sm" style="width: 100%;">Mark Complete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-heading">Explore Collections</h3>
                <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--red-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🏃</div>
                        <span style="font-weight: 600;">Gross Motor</span>
                    </div>
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--purple-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🗣️</div>
                        <span style="font-weight: 600;">Speech</span>
                    </div>
                    <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--green-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🧩</div>
                        <span style="font-weight: 600;">Thinking</span>
                    </div>
                     <div style="cursor: pointer; text-align: center;">
                        <div style="background: var(--yellow-100); aspect-ratio: 1; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem;">🤝</div>
                        <span style="font-weight: 600;">Social</span>
                    </div>
                </div>
            </div>
        `;
    }

    function getClinicView() {
        return `
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Book Appointment 🏥</h1>
                        <p class="dashboard-subtitle">Connect with trusted healthcare providers</p>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <!-- Search Bar -->
                        <div style="position: relative;">
                            <input type="text" placeholder="Search by specialty, doctor, or clinic..." 
                                style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: 1px solid var(--slate-200); border-radius: var(--radius-lg); font-size: 1rem;">
                            <svg style="position: absolute; left: 1rem; top: 1rem; width: 1.25rem; height: 1.25rem; color: var(--slate-400);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>

                        <!-- Doctor Card 1 -->
                        <div class="dashboard-card" style="display: flex; gap: 1.5rem; padding: 1.5rem;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Smith&background=random" style="width: 4rem; height: 4rem; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between;">
                                    <h3 style="font-size: 1.1rem; font-weight: 700;">Dr. Sarah Smith</h3>
                                    <span class="badge badge-green">4.9 ★</span>
                                </div>
                                <p style="color: var(--blue-600); font-weight: 500; font-size: 0.9rem;">Pediatrician • 12 years exp</p>
                                <p style="color: var(--slate-500); font-size: 0.9rem; margin: 0.5rem 0 1rem;">City Kids Care, downtown</p>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-gradient btn-sm">Book Visit</button>
                                    <button class="btn btn-outline btn-sm">Profile</button>
                                </div>
                            </div>
                        </div>

                         <!-- Doctor Card 2 -->
                        <div class="dashboard-card" style="display: flex; gap: 1.5rem; padding: 1.5rem;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Chen&background=random" style="width: 4rem; height: 4rem; border-radius: 50%; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between;">
                                    <h3 style="font-size: 1.1rem; font-weight: 700;">Dr. Michael Chen</h3>
                                    <span class="badge badge-green">4.8 ★</span>
                                </div>
                                <p style="color: var(--blue-600); font-weight: 500; font-size: 0.9rem;">Child Psychologist • 8 years exp</p>
                                <p style="color: var(--slate-500); font-size: 0.9rem; margin: 0.5rem 0 1rem;">Wellness Center, Westside</p>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-gradient btn-sm">Book Visit</button>
                                    <button class="btn btn-outline btn-sm">Profile</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel: Upcoming -->
                    <div>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title" style="font-size: 1rem;">Your Appointments</h3>
                            </div>
                            <div class="card-content">
                                <div class="appointment-item" style="border-bottom: 1px solid var(--slate-100); padding-bottom: 1rem; margin-bottom: 1rem;">
                                    <div style="font-weight: 600;">MMR Vaccination</div>
                                    <div style="font-size: 0.875rem; color: var(--slate-500);">Nov 28, 10:00 AM</div>
                                    <div style="font-size: 0.875rem; color: var(--blue-600);">Dr. Smith</div>
                                </div>
                                 <div class="appointment-item">
                                    <div style="font-weight: 600;">15-Month Checkup</div>
                                    <div style="font-size: 0.875rem; color: var(--slate-500);">Dec 15, 2:30 PM</div>
                                    <div style="font-size: 0.875rem; color: var(--blue-600);">Dr. Johnson</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getReportsView() {
        return `
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                     <div>
                        <h1 class="dashboard-title">Reports & Insights 📄</h1>
                        <p class="dashboard-subtitle">Download summaries for your healthcare provider</p>
                    </div>
                     <button class="btn btn-gradient">Generate New Report</button>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                    <!-- Report Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                        <div style="height: 120px; background: var(--slate-100); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <svg style="width: 3rem; height: 3rem; color: var(--slate-400);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">15-Month Development Summary</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Created Nov 20, 2025</p>
                            <span class="badge badge-purple" style="margin-bottom: 1rem;">Full Assessment</span>
                            <div style="margin-top: auto; display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <button class="btn btn-outline btn-sm" style="flex: 1;">Preview</button>
                                <button class="btn btn-outline btn-sm">⬇</button>
                            </div>
                        </div>
                    </div>

                     <!-- Report Card -->
                    <div class="dashboard-card" style="display: flex; flex-direction: column;">
                         <div style="height: 120px; background: var(--slate-100); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <svg style="width: 3rem; height: 3rem; color: var(--slate-400);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="card-content" style="flex: 1;">
                            <h3 style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Growth Chart Only</h3>
                            <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem;">Created Oct 15, 2025</p>
                             <span class="badge badge-blue" style="margin-bottom: 1rem;">Growth Data</span>
                            <div style="margin-top: auto; display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <button class="btn btn-outline btn-sm" style="flex: 1;">Preview</button>
                                <button class="btn btn-outline btn-sm">⬇</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getSettingsView() {
        return `
            <div class="dashboard-content">
                <h1 class="dashboard-title">Settings ⚙️</h1>
                <p class="dashboard-subtitle" style="margin-bottom: 2rem;">Manage your account and app preferences</p>
                
                <div style="max-width: 800px;">
                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">Profile Settings</h3>
                        </div>
                        <div class="card-content">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Parent Name</label>
                                <input type="text" value="Sarah Johnson" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                            </div>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
                                <input type="email" value="sarah.johnson@example.com" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                            </div>
                            <button class="btn btn-gradient">Save Changes</button>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                         <div class="card-header">
                            <h3 class="card-title">Child's Information</h3>
                        </div>
                         <div class="card-content">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Child Name</label>
                                    <input type="text" value="Emma" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Birth Date</label>
                                    <input type="date" value="2024-08-23" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                                </div>
                            </div>
                            <button class="btn btn-outline">Update Child Profile</button>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">Notifications</h3>
                        </div>
                        <div class="card-content">
                             <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--slate-100);">
                                <div>
                                    <h4 style="font-weight: 600;">Daily Reminders</h4>
                                    <p style="font-size: 0.875rem; color: var(--slate-500);">Receive daily activity suggestions</p>
                                </div>
                                <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            </div>
                             <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0;">
                                <div>
                                    <h4 style="font-weight: 600;">Milestone Alerts</h4>
                                    <p style="font-size: 0.875rem; color: var(--slate-500);">Get notified when milestones are approaching</p>
                                </div>
                                <input type="checkbox" checked style="width: 1.25rem; height: 1.25rem;">
                            </div>
                        </div>
                    </div>

                     <div class="dashboard-card" style="border: 1px solid var(--red-200);">
                        <div class="card-content">
                            <h3 class="card-title" style="color: var(--red-600);">Danger Zone</h3>
                            <p style="color: var(--slate-500); margin-bottom: 1rem;">Irreversible actions</p>
                            <button class="btn btn-outline" style="color: var(--red-600); border-color: var(--red-200);">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Initialize
    initNav();
    loadView('home');
})();

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        clearAuth();
        navigateTo('index');
    }
}
