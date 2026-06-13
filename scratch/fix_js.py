import re

with open('js/specialist-profile.js', 'r', encoding='utf-8') as f:
    content = f.read()

# I will recreate the file properly by pulling the first 120 lines from the actual file
# Wait, let me just reconstruct the renderProfile function cleanly.

new_content = """document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const specId = params.get('id');
    const root = document.getElementById('spec-profile-root');

    if (!specId) {
        root.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--slate-500);">No specialist selected.</div>';
        return;
    }

    fetch('api/api_get_specialist.php?id=' + specId)
        .then(res => res.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            renderProfile(data);
        })
        .catch(err => {
            root.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--red-500);font-weight:600;">Error: ${err.message}</div>`;
        });

    function renderProfile(data) {
        const logo = data.logo_url 
            ? `<img src="${data.logo_url}" style="width:140px;height:140px;border-radius:24px;object-fit:cover;border:4px solid #fff;box-shadow:0 10px 25px rgba(99,102,241,0.2);" alt="${data.full_name}">` 
            : `<div style="width:140px;height:140px;border-radius:24px;background:linear-gradient(135deg, #e0e7ff, #c7d2fe);color:var(--indigo-600);display:flex;align-items:center;justify-content:center;font-size:3.5rem;font-weight:800;border:4px solid #fff;box-shadow:0 10px 25px rgba(99,102,241,0.2);margin:0 auto;">${data.full_name.charAt(4).toUpperCase()}</div>`;
        
        let specialtiesHtml = data.specialties.map(s => `<span style="background:rgba(219,234,254,0.5);color:var(--blue-700);padding:0.5rem 1.25rem;border-radius:20px;font-size:0.9rem;font-weight:600;border:1px solid var(--blue-200);backdrop-filter:blur(4px);">${s}</span>`).join(' ');
        
        let clinicInfo = '';
        if (data.clinic_name) {
            clinicInfo = `
                <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;color:var(--slate-600);margin-top:1rem;font-weight:500;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    ${data.clinic_name}
                </div>
            `;
        }

        // About Tab
        const aboutHtml = `
            <div id="tab-about" class="tab-content" style="display:block;animation:fadeIn 0.3s ease-out;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:1.5rem;margin-bottom:1.5rem;">
                    <div style="background:#fff;border-radius:24px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">
                        <h3 style="color:var(--slate-900);font-size:1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">👋 About</h3>
                        <p style="color:var(--slate-600);font-size:1.05rem;line-height:1.7;margin:0;">${data.bio || data.description || 'No biography provided.'}</p>
                    </div>
                    <div style="background:#fff;border-radius:24px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">
                        <h3 style="color:var(--slate-900);font-size:1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">🧠 Approaches & Focus</h3>
                        <div style="margin-bottom:1.5rem;">
                            <div style="font-size:0.85rem;color:var(--slate-400);margin-bottom:0.75rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;">Therapy Approaches</div>
                            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                                ${data.therapy_approaches && data.therapy_approaches.length ? data.therapy_approaches.map(t => `<span style="background:var(--green-50);color:var(--green-700);padding:0.4rem 0.8rem;border-radius:8px;font-size:0.85rem;font-weight:500;border:1px solid var(--green-200);">${t}</span>`).join('') : '<span style="color:var(--slate-400);font-size:0.9rem;">Not specified</span>'}
                            </div>
                        </div>
                        <div>
                            <div style="font-size:0.85rem;color:var(--slate-400);margin-bottom:0.75rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;">Focus Areas</div>
                            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                                ${data.focus_areas && data.focus_areas.length ? data.focus_areas.map(f => `<span style="background:var(--slate-50);color:var(--slate-700);padding:0.4rem 0.8rem;border-radius:8px;font-size:0.85rem;font-weight:500;border:1px solid var(--slate-200);">${f}</span>`).join('') : '<span style="color:var(--slate-400);font-size:0.9rem;">Not specified</span>'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Certs Tab
        let certsList = '';
        if (data.certificates && data.certificates.length) {
            certsList = data.certificates.map(c => `
                <div style="display:flex;align-items:center;gap:1.25rem;padding:1.5rem;background:var(--slate-50);border:1px solid var(--slate-200);border-radius:16px;margin-bottom:1rem;transition:transform 0.2s;" onmouseover="this.style.transform='translateX(5px)'" onmouseout="this.style.transform='translateX(0)'">
                    <div style="width:48px;height:48px;border-radius:12px;background:#fff;color:var(--blue-600);display:flex;align-items:center;justify-content:center;font-size:1.5rem;box-shadow:0 4px 10px rgba(0,0,0,0.05);">🎓</div>
                    <div style="font-weight:600;color:var(--slate-800);font-size:1.1rem;">${c}</div>
                </div>
            `).join('');
        }
        let certFileHtml = '';
        if (data.certificate_url) {
            certFileHtml = `
                <div style="margin-top:2rem;padding-top:2rem;border-top:1px dashed var(--slate-200);">
                    <h4 style="margin-top:0;color:var(--slate-900);font-size:1.15rem;margin-bottom:1.25rem;">Official Documentation</h4>
                    <a href="${data.certificate_url}" target="_blank" style="display:inline-flex;align-items:center;gap:0.75rem;padding:1rem 2rem;background:var(--blue-50);color:var(--blue-700);text-decoration:none;border-radius:16px;font-weight:600;border:1px solid var(--blue-200);transition:all 0.2s;" onmouseover="this.style.background='var(--blue-100)'" onmouseout="this.style.background='var(--blue-50)'">
                        📄 View Certificate of Experience
                    </a>
                </div>
            `;
        }

        const certHtml = `
            <div id="tab-certs" class="tab-content" style="display:none;animation:fadeIn 0.3s ease-out;">
                <div style="background:#fff;border-radius:24px;padding:2.5rem;box-shadow:0 10px 30px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">
                    <h3 style="color:var(--slate-900);font-size:1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">🎓 Qualifications</h3>
                    ${certsList || (data.certificate_url ? '' : '<div style="color:var(--slate-500);text-align:center;padding:2rem;background:#f8fafc;border-radius:16px;">No certifications listed.</div>')}
                    ${certFileHtml}
                </div>
            </div>
        `;

        // Schedule Tab
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        let scheduleHtml = `<div id="tab-schedule" class="tab-content" style="display:none;animation:fadeIn 0.3s ease-out;">`;
        if (data.availability && data.availability.length) {
            scheduleHtml += `<div style="background:#fff;border-radius:24px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">`;
            days.forEach(day => {
                const slots = data.availability.filter(a => a.day === day);
                if (slots.length > 0) {
                    scheduleHtml += `
                    <div style="display:flex;margin-bottom:1.5rem;align-items:center;">
                        <div style="width:100px;font-weight:700;color:var(--slate-700);">\${day}</div>
                        <div style="flex:1;display:flex;gap:0.5rem;flex-wrap:wrap;">
                            \${slots.map(s => {
                                return `<div style="background:var(--slate-50);border:1px solid var(--slate-200);padding:0.5rem 1rem;border-radius:10px;font-size:0.9rem;color:var(--slate-700);font-weight:600;">\${s.start} - \${s.end}</div>`;
                            }).join('')}
                        </div>
                    </div>`;
                }
            });
            scheduleHtml += `</div>`;
        } else {
            scheduleHtml += '<div style="background:#fff;border-radius:24px;padding:4rem 2rem;text-align:center;color:var(--slate-500);box-shadow:0 10px 30px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">No availability configured.</div>';
        }
        scheduleHtml += '</div>';

        // Reviews always shown now
        const stars = (rating) => {
            let html = `<div style="display:flex;gap:0.25rem;color:#fbbf24;font-size:1.1rem;">`;
            for(let i=1; i<=5; i++) {
                html += i <= rating ? '★' : '<span style="color:#e2e8f0;">★</span>';
            }
            html += `</div>`;
            return html;
        };

        const reviewsHtml = `
            <div style="animation:fadeIn 0.3s ease-out; margin-top: 3rem;">
                <h3 style="color:var(--slate-900);font-size:1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">⭐ Patient Reviews</h3>
                <div style="background:linear-gradient(135deg, #4f46e5, #7c3aed);border-radius:24px;padding:2.5rem;margin-bottom:2rem;color:#fff;display:flex;align-items:center;gap:2rem;box-shadow:0 15px 30px rgba(99,102,241,0.2);">
                    <div style="font-size:4.5rem;font-weight:800;line-height:1;">\${data.average_rating}</div>
                    <div>
                        <div style="margin-bottom:0.5rem;">\${stars(data.average_rating)}</div>
                        <div style="color:rgba(255,255,255,0.8);font-size:1.1rem;font-weight:500;">Based on \${data.review_count} patient reviews</div>
                    </div>
                </div>
                
                <div style="display:grid;gap:1.5rem;">
                \${data.reviews && data.reviews.length ? data.reviews.map(r => `
                    <div style="background:#fff;border-radius:20px;padding:2rem;box-shadow:0 4px 15px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);">
                        <div style="display:flex;justify-content:space-between;margin-bottom:1rem;align-items:center;">
                            <div style="display:flex;align-items:center;gap:1rem;">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--slate-100);color:var(--slate-600);display:flex;align-items:center;justify-content:center;font-weight:700;">\${r.parent.charAt(0).toUpperCase()}</div>
                                <div>
                                    <strong style="color:var(--slate-900);font-size:1.05rem;display:block;">\${r.parent}</strong>
                                    <span style="color:var(--slate-400);font-size:0.85rem;">\${r.date.split(' ')[0]}</span>
                                </div>
                            </div>
                            \${stars(r.rating)}
                        </div>
                        <p style="color:var(--slate-600);margin:0;font-size:1rem;line-height:1.6;">"\${r.comment}"</p>
                    </div>
                `).join('') : '<div style="text-align:center;padding:3rem;color:var(--slate-500);background:#fff;border-radius:24px;border:1px solid var(--slate-200);">No reviews yet.</div>'}
                </div>
            </div>
        `;

        root.innerHTML = `
            <style>
                @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
                .modern-tab-btn { background:transparent; border:none; padding:1rem 1.5rem; font-size:1.05rem; font-weight:600; color:var(--slate-500); cursor:pointer; position:relative; transition:all 0.2s; border-bottom:3px solid transparent; }
                .modern-tab-btn:hover { color:var(--blue-600); }
                .modern-tab-btn.active { color:var(--blue-600); border-bottom-color:var(--blue-600); }
            </style>
            
            <div style="max-width:1000px;margin:0 auto;padding-bottom:4rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;background:#fff;color:var(--slate-700);text-decoration:none;border-radius:12px;font-weight:600;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border:1px solid var(--slate-200);transition:all 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        Back
                    </a>
                    <button class="btn btn-gradient" style="padding:0.75rem 2rem;font-size:1.05rem;border-radius:12px;box-shadow:0 10px 20px rgba(99,102,241,0.3);transform:translateY(0);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'" onclick="showLocalBookingModal(${data.specialist_id}, '${data.full_name.replace(/'/g, "\\'")}', ${data.consultation_fee || 200})">
                        📅 Book Appointment
                    </button>
                </div>

                <div style="background:#fff;border-radius:32px;padding:3rem;margin-bottom:2rem;box-shadow:0 20px 40px rgba(0,0,0,0.04);position:relative;overflow:hidden;border:1px solid rgba(226,232,240,0.8);">
                    <div style="position:absolute;top:0;left:0;right:0;height:140px;background:linear-gradient(135deg, #e0e7ff, #f3e8ff);z-index:0;"></div>
                    
                    <div style="position:relative;z-index:1;text-align:center;margin-top:20px;">
                        \${logo}
                        <h1 style="font-size:2.5rem;font-weight:800;color:var(--slate-900);margin:1.5rem 0 0.5rem;letter-spacing:-0.02em;">\${data.full_name}</h1>
                        <div style="font-size:1.15rem;color:var(--slate-500);font-weight:500;margin-bottom:1.5rem;">\${data.years_experience} Years Experience</div>
                        <div style="display:flex;justify-content:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                            \${specialtiesHtml}
                        </div>
                        \${clinicInfo}
                    </div>
                </div>

                <div style="background:#fff;border-radius:24px;padding:0.5rem;margin-bottom:2rem;display:flex;gap:0.5rem;box-shadow:0 4px 15px rgba(0,0,0,0.03);border:1px solid rgba(226,232,240,0.8);overflow-x:auto;">
                    <button class="modern-tab-btn active" data-target="tab-about">Overview</button>
                    <button class="modern-tab-btn" data-target="tab-certs">Certifications</button>
                    <button class="modern-tab-btn" data-target="tab-schedule">Availability</button>
                </div>

                <div class="tab-container">
                    \${aboutHtml}
                    \${certHtml}
                    \${scheduleHtml}
                </div>
                
                \${reviewsHtml}
            </div>
        `;

        // Attach tab listeners
        const btns = root.querySelectorAll('.modern-tab-btn');
        const contents = root.querySelectorAll('.tab-content');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                contents.forEach(c => c.style.display = 'none');
                btn.classList.add('active');
                document.getElementById(btn.getAttribute('data-target')).style.display = 'block';
            });
        });
    }
});

window.showLocalBookingModal = function(specId, specName, fee) {
    const existing = document.getElementById('local-book-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'local-book-modal';
    modal.innerHTML = `
        <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('local-book-modal').remove()">
            <div style="background:#ffffff;border-radius:24px;width:100%;max-width:500px;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;display:flex;flex-direction:column;padding:2rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                    <h2 style="margin:0;font-size:1.5rem;color:var(--slate-900);">Book Appointment</h2>
                    <button onclick="document.getElementById('local-book-modal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);">&times;</button>
                </div>
                
                <div style="margin-bottom:1.5rem;">
                    <p style="margin:0;color:var(--slate-600);">Booking with <strong>\${specName}</strong></p>
                    <p style="margin:0;color:var(--blue-600);font-weight:600;">Consultation Fee: \${fee} EGP</p>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Date & Time</label>
                    <input type="datetime-local" id="local-book-datetime" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-size:0.95rem;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Consultation Type</label>
                    <select id="local-book-type" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-size:0.95rem;">
                        <option value="online">Online Video Call</option>
                        <option value="onsite">On-site at Clinic</option>
                    </select>
                </div>

                <button id="local-book-submit" class="btn btn-gradient" style="width:100%;padding:0.85rem;border-radius:12px;font-size:1.05rem;font-weight:600;">Confirm Booking</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('local-book-submit').onclick = async function() {
        const datetime = document.getElementById('local-book-datetime').value;
        const type = document.getElementById('local-book-type').value;

        if (!datetime) {
            alert('Please select a date and time.');
            return;
        }

        const fd = new FormData();
        fd.append('specialist_id', specId);
        fd.append('type', type);
        fd.append('date', datetime.replace('T', ' ') + ':00');

        try {
            const btn = document.getElementById('local-book-submit');
            btn.innerHTML = 'Booking...';
            btn.disabled = true;

            const res = await fetch('api/api_book_appointment.php', { method: 'POST', body: fd });
            const result = await res.text();
            let data = null;
            try { data = JSON.parse(result); } catch(e) {}

            if (data && data.success) {
                modal.innerHTML = `
                    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
                        <div style="background:#ffffff;border-radius:24px;width:100%;max-width:400px;text-align:center;padding:3rem;">
                            <div style="width:60px;height:60px;background:#dcfce7;color:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;">✓</div>
                            <h2 style="margin:0 0 0.5rem;font-size:1.5rem;">Booking Confirmed!</h2>
                            <p style="color:var(--slate-600);margin-bottom:1.5rem;">Your appointment has been successfully booked.</p>
                            <button class="btn btn-gradient" onclick="document.getElementById('local-book-modal').remove()" style="padding:0.75rem 2rem;border-radius:12px;">Close</button>
                        </div>
                    </div>
                `;
            } else {
                alert('Failed to book: ' + (data ? data.error : result));
                btn.innerHTML = 'Confirm Booking';
                btn.disabled = false;
            }
        } catch (e) {
            alert('Error booking appointment.');
            document.getElementById('local-book-submit').innerHTML = 'Confirm Booking';
            document.getElementById('local-book-submit').disabled = false;
        }
    };
};
"""

with open('js/specialist-profile.js', 'w', encoding='utf-8') as f:
    f.write(new_content)
