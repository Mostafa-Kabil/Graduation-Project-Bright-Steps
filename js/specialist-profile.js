document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('profile-root');
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
        root.innerHTML = '<div style="padding:3rem;text-align:center;color:#ef4444;font-weight:600;">Invalid Specialist ID.</div>';
        return;
    }

    fetch(`api/api_get_specialist.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            renderProfile(data);
        })
        .catch(err => {
            root.innerHTML = `<div style="padding:3rem;text-align:center;color:#ef4444;font-weight:600;">Error: ${err.message}</div>`;
        });

    function renderProfile(data) {
        const logo = data.logo_url ? `<img src="${data.logo_url}" class="profile-logo" alt="${data.full_name}">` : `<div class="profile-logo">${data.full_name.charAt(4)}</div>`;
        
        let specialtiesHtml = data.specialties.map(s => `<span style="background:var(--primary);color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;">${s}</span>`).join(' ');
        
        const clinicInfo = data.clinic ? `
            <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1rem;color:#475569;">
                <span style="font-size:1.5rem;">🏥</span>
                <div>
                    <div style="font-weight:600;color:#1e293b;">${data.clinic.name}</div>
                    <div style="font-size:0.85rem;">${data.clinic.location}</div>
                </div>
            </div>
        ` : '';

        // About Tab
        const aboutHtml = `
            <div id="tab-about" class="tab-content active">
                <div class="glass-card">
                    <h3 style="margin-top:0;color:#0f172a;">Biography</h3>
                    <p style="line-height:1.6;color:#475569;">${data.bio || 'No biography provided.'}</p>
                    <p style="line-height:1.6;color:#475569;">${data.description || ''}</p>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px,1fr));gap:1rem;">
                    <div class="glass-card">
                        <h4 style="margin-top:0;color:#0f172a;">Focus Areas</h4>
                        <ul style="padding-left:1.25rem;color:#475569;margin-bottom:0;">
                            ${data.focus_areas && data.focus_areas.length ? data.focus_areas.map(f => `<li>${f}</li>`).join('') : '<li>Not specified</li>'}
                        </ul>
                    </div>
                    <div class="glass-card">
                        <h4 style="margin-top:0;color:#0f172a;">Therapy Approaches</h4>
                        <ul style="padding-left:1.25rem;color:#475569;margin-bottom:0;">
                            ${data.therapy_approaches && data.therapy_approaches.length ? data.therapy_approaches.map(t => `<li>${t}</li>`).join('') : '<li>Not specified</li>'}
                        </ul>
                    </div>
                    <div class="glass-card">
                        <h4 style="margin-top:0;color:#0f172a;">Consultation Modes</h4>
                        <ul style="padding-left:1.25rem;color:#475569;margin-bottom:0;">
                            ${data.consultation_types && data.consultation_types.length ? data.consultation_types.map(t => `<li>${t}</li>`).join('') : '<li>Not specified</li>'}
                        </ul>
                    </div>
                    <div class="glass-card">
                        <h4 style="margin-top:0;color:#0f172a;">Session Preferences</h4>
                        <ul style="padding-left:1.25rem;color:#475569;margin-bottom:0;">
                            ${data.session_preferences ? `<li>Duration: ${data.session_preferences.duration}</li><li>Frequency: ${data.session_preferences.frequency}</li>` : '<li>Not specified</li>'}
                        </ul>
                    </div>
                </div>
            </div>
        `;

        // Certificates Tab
        const certHtml = `
            <div id="tab-certs" class="tab-content">
                ${data.certificates && data.certificates.length ? data.certificates.map(c => `
                    <div class="glass-card" style="display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-weight:600;color:#1e293b;">🎓 ${c.name}</div>
                        <div style="font-size:0.85rem;color:#64748b;">${c.date}</div>
                    </div>
                `).join('') : '<div class="glass-card">No certificates listed.</div>'}
            </div>
        `;

        // Schedule Tab
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        let scheduleHtml = `<div id="tab-schedule" class="tab-content">`;
        if (data.availability && data.availability.length) {
            days.forEach(day => {
                const slots = data.availability.filter(a => a.day === day);
                if (slots.length > 0) {
                    scheduleHtml += `
                    <div class="timeline-row">
                        <div class="timeline-day">${day}</div>
                        <div class="timeline-bar">
                            ${slots.map(s => {
                                const parseTime = t => {
                                    const [h, m] = t.split(':').map(Number);
                                    return h + m/60;
                                };
                                const start = parseTime(s.start);
                                const end = parseTime(s.end);
                                // Map 08:00-20:00 to 0-100%
                                const dayStart = 8;
                                const dayEnd = 20;
                                const left = Math.max(0, ((start - dayStart) / (dayEnd - dayStart)) * 100);
                                const width = Math.min(100 - left, ((end - start) / (dayEnd - dayStart)) * 100);
                                return `<div class="timeline-slot" style="left:${left}%;width:${width}%;" onclick="alert('Feature to book slot ${s.start} - ${s.end} coming soon!')" title="Book this slot">${s.start} - ${s.end}</div>`;
                            }).join('')}
                        </div>
                    </div>`;
                }
            });
        } else {
            scheduleHtml += '<div class="glass-card">No availability configured.</div>';
        }
        scheduleHtml += '</div>';

        // Reviews Tab
        const stars = (rating) => {
            let html = `<div class="star-rating" data-rating="${Math.round(rating)}">`;
            for(let i=0; i<5; i++) {
                html += `<svg viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>`;
            }
            html += `</div>`;
            return html;
        };

        const reviewsHtml = `
            <div id="tab-reviews" class="tab-content">
                <div class="glass-card" style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;">
                    <div style="font-size:3rem;font-weight:800;color:var(--primary);">${data.average_rating}</div>
                    <div>
                        ${stars(data.average_rating)}
                        <div style="color:#64748b;font-size:0.9rem;margin-top:0.25rem;">Based on ${data.review_count} reviews</div>
                    </div>
                </div>
                ${data.reviews && data.reviews.length ? data.reviews.map(r => `
                    <div class="glass-card">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                            <strong style="color:#1e293b;">${r.parent}</strong>
                            <span style="color:#64748b;font-size:0.85rem;">${r.date.split(' ')[0]}</span>
                        </div>
                        ${stars(r.rating)}
                        <p style="color:#475569;margin:0.5rem 0 0;font-size:0.95rem;">${r.comment}</p>
                    </div>
                `).join('') : '<div class="glass-card">No reviews yet.</div>'}
            </div>
        `;

        root.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <a href="javascript:history.back()" class="back-btn" style="margin-bottom:0;">← Back</a>
                <button class="btn btn-gradient" onclick="window.location.href='dashboards/parent/dashboard.php?view=clinic&book_specialist=${data.id}'">Book Appointment</button>
            </div>
            <div class="profile-header">
                ${logo}
                <div class="profile-title">
                    <h1 class="profile-name">${data.full_name}</h1>
                    <h2 class="profile-subtitle">${data.years_experience} Years Experience • ${data.patient_age_group}</h2>
                    <div style="margin-top:1rem;">${specialtiesHtml}</div>
                    ${clinicInfo}
                </div>
            </div>

            <div class="profile-tabs">
                <button class="tab-btn active" data-target="tab-about">About</button>
                <button class="tab-btn" data-target="tab-certs">Certificates</button>
                <button class="tab-btn" data-target="tab-schedule">Schedule</button>
                <button class="tab-btn" data-target="tab-reviews">Reviews</button>
            </div>

            <div class="tab-container">
                ${aboutHtml}
                ${certHtml}
                ${scheduleHtml}
                ${reviewsHtml}
            </div>
        `;

        // Attach tab listeners
        const btns = root.querySelectorAll('.tab-btn');
        const contents = root.querySelectorAll('.tab-content');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.getAttribute('data-target')).classList.add('active');
            });
        });
    }
});
