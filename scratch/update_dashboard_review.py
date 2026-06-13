import re

with open('dashboards/parent/dashboard.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the existing window.reviewDoctor function with the combined modal
pattern = r'window\.reviewDoctor = function\(appointmentId, specialistId, docName, clinicId=0, isOnsite=false\) \{.*?\n        \}\);\n    \};'

new_func = """window.reviewDoctor = function(appointmentId, specialistId, docName, clinicId=0, isOnsite=false) {
        let existing = document.getElementById('review-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'review-modal';
        
        let clinicSection = '';
        if (isOnsite && clinicId) {
            clinicSection = `
                <div style="margin-top:2rem;padding-top:2rem;border-top:1px solid var(--slate-200);">
                    <h3 style="font-size:1.15rem;font-weight:700;color:var(--slate-900);margin:0 0 0.5rem;">🏥 Rate the Clinic</h3>
                    <p style="color:var(--slate-500);margin-bottom:1rem;font-size:0.85rem;">How was the facility, staff, and environment?</p>
                    <div style="margin-bottom:1rem;text-align:center;">
                        <div id="clinic-star-rating" style="display:flex;justify-content:center;gap:0.5rem;font-size:2.5rem;color:var(--slate-300);cursor:pointer;">
                            <span data-val="1">★</span><span data-val="2">★</span><span data-val="3">★</span><span data-val="4">★</span><span data-val="5">★</span>
                        </div>
                        <input type="hidden" id="clinic-rev-rating" value="0">
                    </div>
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Clinic Comment (Optional)</label>
                        <textarea id="clinic-rev-comment" rows="2" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;resize:none;outline:none;font-family:inherit;font-size:0.9rem;" placeholder="Clean facility? Friendly staff?"></textarea>
                    </div>
                </div>
            `;
        }

        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:500px;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;overflow-y:auto;max-height:90vh;animation:slideUp 0.3s ease-out;padding:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);margin:0;">Rate Your Visit</h2>
                        <button onclick="document.getElementById('review-modal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);">&times;</button>
                    </div>
                    
                    <div>
                        <h3 style="font-size:1.15rem;font-weight:700;color:var(--slate-900);margin:0 0 0.5rem;">👨‍⚕️ Rate the Specialist</h3>
                        <p style="color:var(--slate-500);margin-bottom:1rem;font-size:0.85rem;">How was your experience with ${docName}?</p>
                        <div style="margin-bottom:1rem;text-align:center;">
                            <div id="star-rating" style="display:flex;justify-content:center;gap:0.5rem;font-size:2.5rem;color:var(--slate-300);cursor:pointer;">
                                <span data-val="1">★</span><span data-val="2">★</span><span data-val="3">★</span><span data-val="4">★</span><span data-val="5">★</span>
                            </div>
                            <input type="hidden" id="rev-rating" value="0">
                        </div>
                        <div style="margin-bottom:1.5rem;">
                            <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Specialist Comment (Optional)</label>
                            <textarea id="rev-comment" rows="2" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;resize:none;outline:none;font-family:inherit;font-size:0.9rem;" placeholder="Share your thoughts..."></textarea>
                        </div>
                    </div>

                    ${clinicSection}

                    <button id="submit-rev-btn" class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1.05rem;border-radius:12px;">Submit Review(s)</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        const setupStars = (containerId, inputId) => {
            const stars = document.querySelectorAll('#' + containerId + ' span');
            const ratingInput = document.getElementById(inputId);
            if (!stars.length) return;
            stars.forEach(s => {
                s.addEventListener('click', () => {
                    const val = parseInt(s.getAttribute('data-val'));
                    ratingInput.value = val;
                    stars.forEach((st, i) => { st.style.color = i < val ? '#f59e0b' : 'var(--slate-300)'; });
                });
                s.addEventListener('mouseover', () => {
                    const val = parseInt(s.getAttribute('data-val'));
                    stars.forEach((st, i) => { st.style.color = i < val ? '#fcd34d' : 'var(--slate-300)'; });
                });
                s.addEventListener('mouseout', () => {
                    const val = parseInt(ratingInput.value);
                    stars.forEach((st, i) => { st.style.color = i < val ? '#f59e0b' : 'var(--slate-300)'; });
                });
            });
        };

        setupStars('star-rating', 'rev-rating');
        if (isOnsite && clinicId) {
            setupStars('clinic-star-rating', 'clinic-rev-rating');
        }

        document.getElementById('submit-rev-btn').addEventListener('click', () => {
            const rating = parseInt(document.getElementById('rev-rating').value);
            if (rating < 1 || rating > 5) { alert('Please select a star rating for the specialist.'); return; }
            
            const payload = {
                appointment_id: appointmentId,
                specialist_id: specialistId,
                rating: rating,
                comment: document.getElementById('rev-comment').value
            };

            if (isOnsite && clinicId) {
                const clinicRating = parseInt(document.getElementById('clinic-rev-rating').value);
                if (clinicRating >= 1 && clinicRating <= 5) {
                    payload.clinic_id = clinicId;
                    payload.clinic_rating = clinicRating;
                    payload.clinic_comment = document.getElementById('clinic-rev-comment').value;
                } else if (clinicRating !== 0) {
                    alert('Please select a valid star rating for the clinic.');
                    return;
                }
            }

            document.getElementById('submit-rev-btn').disabled = true;
            document.getElementById('submit-rev-btn').innerHTML = 'Submitting...';

            fetch('../../api_doctor_review.php?action=submit', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showReviewThankYou();
                } else {
                    alert(data.error || 'Failed to submit review.');
                    document.getElementById('submit-rev-btn').disabled = false;
                    document.getElementById('submit-rev-btn').innerHTML = 'Submit Review(s)';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error submitting review.');
                document.getElementById('submit-rev-btn').disabled = false;
                document.getElementById('submit-rev-btn').innerHTML = 'Submit Review(s)';
            });
        });
    };"""

content = re.sub(pattern, new_func, content, flags=re.DOTALL)

with open('dashboards/parent/dashboard.js', 'w', encoding='utf-8') as f:
    f.write(content)
