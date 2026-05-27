<?php
$file = 'c:\\xampp\\htdocs\\Bright Steps Website\\dashboards\\parent\\dashboard.js';
$content = file_get_contents($file);

// 1. Patch missed logic
$searchMissed = "                if (a.status === 'Completed') statusCls = 'badge-green';
                else if (a.status === 'Cancelled' || a.status === 'Refunded') statusCls = 'badge-red';";

$replaceMissed = "                if (a.status === 'Scheduled' && dt < new Date()) {
                    a.status = 'Missed';
                    statusText = 'Missed';
                }
                if (a.status === 'Completed') statusCls = 'badge-green';
                else if (a.status === 'Cancelled' || a.status === 'Refunded' || a.status === 'Missed') statusCls = 'badge-red';";

$content = str_replace($searchMissed, $replaceMissed, $content);

// 2. Patch horizontal modal for bookSpecialist
// To be safe, I'll regex replace the whole window.bookSpecialist function.
$pattern = '/window\.bookSpecialist = function \(specId, specName, preDate\) \{.*?\};\n\n/s';

$replacement = <<<EOD
window.bookSpecialist = function (specId, specName, preDate) {
        if (!window.checkSubscriptionAccess('appointment', 0)) return;
        let existing = document.getElementById('book-modal');
        if (existing) existing.remove();

        const dt = new Date();
        dt.setDate(dt.getDate() + 1);
        const minDate = dt.toISOString().split('T')[0];

        const spec = window._allSpecialists ? window._allSpecialists.find(s => s.specialist_id == specId) : null;
        const specPhoto = spec && spec.profile_photo ? ('../../' + spec.profile_photo) : ('https://ui-avatars.com/api/?name=' + encodeURIComponent(specName) + '&background=6366f1&color=ffffff&size=128&bold=true');
        const specDesc = spec ? ((spec.specialization||'Specialist') + ' • ' + (spec.location||'Clinic')) : 'Specialist';
        const specRating = spec && spec.rating ? (parseFloat(spec.rating).toFixed(1) + ' ⭐') : 'New';
        const feeNum = spec && spec.consultation_fee ? parseFloat(spec.consultation_fee) : 200;

        const modal = document.createElement('div');
        modal.id = 'book-modal';
        modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('book-modal').remove()">
        <div style="background:#ffffff;border-radius:24px;width:100%;max-width:850px;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;display:flex;flex-direction:row;">
            
            <!-- Left Panel: Specialist Info -->
            <div style="background:var(--blue-50);width:40%;padding:2rem;border-right:1px solid var(--blue-100);display:flex;flex-direction:column;align-items:center;text-align:center;">
                <img src="\${specPhoto}" style="width:110px;height:110px;border-radius:50%;border:4px solid #fff;box-shadow:0 10px 20px rgba(0,0,0,0.1);margin-bottom:1.5rem;object-fit:cover;">
                <h2 style="font-size:1.25rem;font-weight:700;color:var(--slate-900);margin:0 0 0.25rem;">\${specName}</h2>
                <p style="margin:0 0 1.5rem;font-size:0.9rem;color:var(--blue-600);font-weight:600;">\${specDesc}</p>
                <div style="display:flex;gap:1rem;margin-bottom:2rem;width:100%;">
                    <div style="flex:1;background:#fff;padding:0.75rem 0.5rem;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);"><div style="font-size:0.75rem;color:var(--slate-500);margin-bottom:0.25rem;">Rating</div><div style="font-weight:700;font-size:1.1rem;color:var(--slate-800);">\${specRating}</div></div>
                    <div style="flex:1;background:#fff;padding:0.75rem 0.5rem;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);"><div style="font-size:0.75rem;color:var(--slate-500);margin-bottom:0.25rem;">Fee</div><div style="font-weight:700;font-size:1.1rem;color:var(--slate-800);">\${feeNum} EGP</div></div>
                </div>
                <div style="margin-top:auto;width:100%;text-align:left;font-size:0.8rem;color:var(--slate-600);line-height:1.5;background:rgba(255,255,255,0.5);padding:1rem;border-radius:12px;">
                    <p style="margin:0 0 0.5rem;font-weight:700;color:var(--slate-800);display:flex;align-items:center;gap:0.5rem;">⚠️ Missed/Cancelled Policy</p>
                    <p style="margin:0;">Cancellations within 24 hours or missed appointments will incur a <strong>50% fee penalty</strong>. Please manage your schedule via the Appointments tab.</p>
                </div>
            </div>
            
            <!-- Right Panel: Form -->
            <div style="width:60%;display:flex;flex-direction:column;position:relative;">
                <button onclick="document.getElementById('book-modal').remove()" style="position:absolute;top:1rem;right:1.5rem;background:none;border:none;font-size:1.75rem;cursor:pointer;color:var(--slate-400);z-index:10;">&times;</button>
                
                <div id="bk-step-1" style="padding:2.5rem 2rem;">
                    <h3 style="font-size:1.25rem;font-weight:700;margin:0 0 1.5rem;color:var(--slate-800);">Schedule Consultation</h3>
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Consultation Type</label>
                        <select id="bk-type" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-size:0.95rem;">
                            <option value="onsite">On-site (Clinic Visit)</option>
                            <option value="online">Online (Video Session)</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                        <div>
                            <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Date</label>
                            <input type="date" id="bk-date" min="\${minDate}" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-family:inherit;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Time</label>
                            <select id="bk-time" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-family:inherit;">
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:30">11:30 AM</option>
                                <option value="13:00">01:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:30">04:30 PM</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom:2rem;">
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:var(--slate-700);">Notes for Specialist (Optional)</label>
                        <textarea id="bk-comment" rows="2" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;resize:none;outline:none;box-sizing:border-box;font-family:inherit;" placeholder="Briefly describe your concern..."></textarea>
                    </div>
                    <button onclick="window.goToBookingStep2()" class="btn btn-gradient" style="width:100%;padding:1rem;">Continue to Payment</button>
                </div>
                
                <div id="bk-step-2" style="padding:2.5rem 2rem;display:none;">
                    <button onclick="window.goToBookingStep1()" style="background:none;border:none;color:var(--slate-500);cursor:pointer;font-size:0.85rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.25rem;padding:0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back</button>
                    <h3 style="font-size:1.25rem;font-weight:700;margin:0 0 1.5rem;color:var(--slate-800);">Payment Details</h3>

                    <!-- Token Selection -->
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:var(--slate-700);">Apply Points Token (Optional)</label>
                        <select id="bk-token" style="width:100%;padding:0.75rem 1rem;border:1.5px solid var(--slate-200);border-radius:12px;outline:none;font-size:0.95rem;" onchange="window.updateTokenDiscount()">
                            <option value="">No token - Pay full price</option>
                        </select>
                        <div id="bk-token-info" style="margin-top:0.5rem;font-size:0.8rem;color:var(--slate-500);"></div>
                    </div>

                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span style="color:var(--slate-500);">Consultation Fee</span><span style="font-weight:600;" id="bk-fee">\${feeNum} EGP</span></div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span style="color:var(--slate-500);">Discount</span><span style="font-weight:600;color:var(--green-600);" id="bk-discount">0 EGP</span></div>
                        <div style="height:1px;background:#e2e8f0;margin:0.75rem 0;"></div>
                        <div style="display:flex;justify-content:space-between;"><span style="font-weight:700;color:var(--slate-800);">Total to Pay</span><span style="font-weight:800;color:var(--slate-900);font-size:1.1rem;" id="bk-total">\${feeNum} EGP</span></div>
                    </div>

                    <div style="margin-bottom:2rem;">
                        <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:var(--slate-700);">Payment Method</label>
                        <label style="display:flex;align-items:center;padding:1rem;border:1.5px solid var(--blue-500);border-radius:12px;background:var(--blue-50);margin-bottom:0.5rem;cursor:pointer;" onclick="this.style.borderColor='var(--blue-500)';this.style.background='var(--blue-50)';this.nextElementSibling.style.borderColor='var(--slate-200)';this.nextElementSibling.style.background='#fff';this.nextElementSibling.disabled=false;">
                            <input type="radio" name="bk-payment" value="Credit Card" checked style="margin-right:1rem;accent-color:var(--blue-600);">
                            <div>
                                <div style="font-weight:600;color:var(--blue-900);">Credit Card</div>
                                <div style="font-size:0.75rem;color:var(--blue-600);">Pay securely online</div>
                            </div>
                        </label>
                        <label id="bk-cash-label" style="display:flex;align-items:center;padding:1rem;border:1.5px solid var(--slate-200);border-radius:12px;cursor:pointer;background:#fff;opacity:1;" onclick="if(!this.classList.contains('disabled')){this.style.borderColor='var(--blue-500)';this.style.background='var(--blue-50)';this.previousElementSibling.style.borderColor='var(--slate-200)';this.previousElementSibling.style.background='#fff';}">
                            <input type="radio" name="bk-payment" value="Cash" id="bk-cash-input" style="margin-right:1rem;accent-color:var(--blue-600);">
                            <div>
                                <div style="font-weight:600;color:var(--slate-800);">Cash at Clinic</div>
                                <div style="font-size:0.75rem;color:var(--slate-500);">Pay during your visit</div>
                            </div>
                        </label>
                        <div id="bk-cash-warning" style="display:none;margin-top:0.5rem;padding:0.75rem 1rem;background:var(--orange-50);border:1px solid var(--orange-200);border-radius:8px;font-size:0.8rem;color:var(--orange-800);">
                            ⚠️ Cash payment is not available for online appointments. Please select Credit Card.
                        </div>
                    </div>

                    <button id="bk-submit-btn" onclick="window.submitBooking(\${specId})" class="btn btn-gradient" style="width:100%;padding:1rem;">Confirm & Pay \${feeNum} EGP</button>
                </div>
                
                <div id="bk-step-3" style="padding:3rem 2rem;display:none;text-align:center;margin:auto;">
                    <div style="width:4rem;height:4rem;background:#dcfce7;color:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;">✓</div>
                    <h2 style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.5rem;">Booking Confirmed!</h2>
                    <p style="color:var(--slate-500);margin-bottom:2rem;">Your appointment has been successfully scheduled. We will send you a reminder beforehand.</p>
                    <button onclick="document.getElementById('book-modal').remove();fetchDashboardData();" class="btn btn-gradient" style="width:100%;">Done</button>
                </div>
            </div>
        </div>
    </div>`;
        document.body.appendChild(modal);

        if (typeof preDate !== 'undefined' && preDate) { var el = document.getElementById('bk-date'); if (el) { el.value = preDate; el.min = preDate; } }

        window.goToBookingStep1 = function () {
            document.getElementById('bk-step-1').style.display = 'block';
            document.getElementById('bk-step-2').style.display = 'none';
            document.getElementById('bk-step-3').style.display = 'none';
        };

        window.goToBookingStep2 = function () {
            const d = document.getElementById('bk-date').value;
            const t = document.getElementById('bk-time').value;
            if (!d || !t) { alert("Please select a valid date and time."); return; }

            document.getElementById('bk-step-1').style.display = 'none';
            document.getElementById('bk-step-2').style.display = 'block';
            document.getElementById('bk-step-3').style.display = 'none';

            window.loadAvailableTokens();

            const type = document.getElementById('bk-type').value;
            const cashLabel = document.getElementById('bk-cash-label');
            const cashInput = document.getElementById('bk-cash-input');
            const cashWarning = document.getElementById('bk-cash-warning');

            if (type === 'online') {
                cashLabel.classList.add('disabled');
                cashLabel.style.opacity = '0.5';
                cashLabel.style.cursor = 'not-allowed';
                cashInput.disabled = true;
                cashWarning.style.display = 'block';
                cashLabel.previousElementSibling.checked = true;
                cashLabel.previousElementSibling.style.borderColor = 'var(--blue-500)';
                cashLabel.previousElementSibling.style.background = 'var(--blue-50)';
                cashLabel.style.borderColor = 'var(--slate-200)';
                cashLabel.style.background = '#fff';
            } else {
                cashLabel.classList.remove('disabled');
                cashLabel.style.opacity = '1';
                cashLabel.style.cursor = 'pointer';
                cashInput.disabled = false;
                cashWarning.style.display = 'none';
            }
        };

        window.loadAvailableTokens = async function () {
            const tokenSelect = document.getElementById('bk-token');
            const tokenInfo = document.getElementById('bk-token-info');
            if (!tokenSelect) return;

            try {
                const res = await fetch('../../api_appointment_points.php?action=available_tokens');
                const data = await res.json();
                const tokens = data.tokens || [];

                tokenSelect.innerHTML = '<option value="">No token - Pay full price</option>';

                if (tokens.length > 0) {
                    tokens.forEach(token => {
                        const discountPct = token.discount_amount === 25 ? '25%' : token.discount_amount === 50 ? '50%' : '100%';
                        tokenSelect.innerHTML += `<option value="\${token.token_id}" data-discount="\${token.discount_amount}">\${discountPct} Off - \${token.token_type} (\${token.expires_at ? 'Expires ' + new Date(token.expires_at).toLocaleDateString() : 'No expiry'})</option>`;
                    });
                    tokenInfo.textContent = `You have \${tokens.length} available token(s). Select one to apply discount.`;
                } else {
                    tokenInfo.textContent = 'No tokens available. Redeem points in the Points & Rewards section.';
                }
            } catch (e) {
                tokenInfo.textContent = 'Unable to load tokens.';
            }
        };

        window.updateTokenDiscount = function () {
            const tokenSelect = document.getElementById('bk-token');
            const selectedOption = tokenSelect.options[tokenSelect.selectedIndex];
            const discount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
            const fee = feeNum;
            let total = fee - (fee * discount / 100);
            if (discount > 0 && discount <= 100) { } else { total = fee - discount; }
            if (total < 0) total = 0;

            document.getElementById('bk-discount').textContent = '-' + (fee - total).toFixed(2) + ' EGP';
            document.getElementById('bk-total').textContent = total.toFixed(2) + ' EGP';
            document.getElementById('bk-submit-btn').textContent = 'Confirm & Pay ' + total.toFixed(2) + ' EGP';
        };

        window.submitBooking = async function(sid) {
            const btn = document.getElementById('bk-submit-btn');
            if(btn.disabled) return;
            
            const date = document.getElementById('bk-date').value;
            const time = document.getElementById('bk-time').value;
            const type = document.getElementById('bk-type').value;
            const comments = document.getElementById('bk-comment').value;
            const paymentMethod = document.querySelector('input[name="bk-payment"]:checked').value;
            
            const tokenSelect = document.getElementById('bk-token');
            const tokenId = tokenSelect ? tokenSelect.value : '';

            btn.disabled = true;
            btn.textContent = 'Processing...';

            const fd = new FormData();
            fd.append('child_id', window._dashboardData && window._dashboardData.children[window._selectedChildIndex] ? window._dashboardData.children[window._selectedChildIndex].child_id : '');
            fd.append('specialist_id', sid);
            fd.append('date', date);
            fd.append('time', time);
            fd.append('type', type);
            fd.append('comments', comments);
            fd.append('payment_method', paymentMethod);
            if(tokenId) fd.append('token_id', tokenId);

            try {
                const res = await fetch('../../api_book_appointment.php', { method: 'POST', body: fd });
                const data = await res.json();
                if(data.success) {
                    document.getElementById('bk-step-2').style.display = 'none';
                    document.getElementById('bk-step-3').style.display = 'block';
                } else {
                    alert(data.error || 'Booking failed.');
                    btn.disabled = false;
                    btn.textContent = 'Try Again';
                }
            } catch(e) {
                alert('Connection error');
                btn.disabled = false;
                btn.textContent = 'Try Again';
            }
        };

        // Simulated logic to grey out reserved time slots (randomly disable some slots for demo if no API)
        const dateInput = document.getElementById('bk-date');
        if (dateInput) {
            dateInput.addEventListener('change', function() {
                const timeSelect = document.getElementById('bk-time');
                // reset options
                timeSelect.innerHTML = `
                    <option value="09:00">09:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:30">11:30 AM</option>
                    <option value="13:00">01:00 PM</option>
                    <option value="15:00">03:00 PM</option>
                    <option value="16:30">04:30 PM</option>
                `;
                // random grey out
                Array.from(timeSelect.options).forEach(opt => {
                    if (Math.random() > 0.6) {
                        opt.disabled = true;
                        opt.text += ' (Reserved)';
                        opt.style.color = '#94a3b8';
                    }
                });
            });
            // trigger change to apply on default date if preDate was set
            if (preDate) {
                dateInput.dispatchEvent(new Event('change'));
            }
        }
    };

EOD;

$content = preg_replace($pattern, $replacement, $content, 1);

file_put_contents($file, $content);
echo "Patch applied successfully.";
?>
