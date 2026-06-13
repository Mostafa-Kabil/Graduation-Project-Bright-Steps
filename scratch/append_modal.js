
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
                    <p style="margin:0;color:var(--slate-600);">Booking with <strong>${specName}</strong></p>
                    <p style="margin:0;color:var(--blue-600);font-weight:600;">Consultation Fee: ${fee} EGP</p>
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
