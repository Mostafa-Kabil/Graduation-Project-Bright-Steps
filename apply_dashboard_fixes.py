import re
import sys

file_path = "c:/xampp/htdocs/Bright Steps Website/dashboards/parent/dashboard.js"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. 29.99 -> 250
content = content.replace("29.99", "250")

# 2. $${total} -> ${total} EGP
content = content.replace("$${total}", "${total} EGP")

# 3. window.showPremiumModal actionHtml
content = content.replace(
    """<button onclick="document.getElementById('premium-modal').remove(); window.showPaymentModal('Premium', 250)" style="width:100%;padding:1.1rem;background:linear-gradient(135deg, #7c3aed, #4f46e5);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:1rem;cursor:pointer;margin-bottom:1rem;box-shadow:0 10px 15px -3px rgba(124,58,237,0.3);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">Upgrade to Premium</button>""",
    """<button onclick="document.getElementById('premium-modal').remove(); window.showPaymentModal('Premium', 250)" style="width:100%;padding:1.1rem;background:linear-gradient(135deg, #7c3aed, #4f46e5);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:1rem;cursor:pointer;margin-bottom:1rem;box-shadow:0 10px 15px -3px rgba(124,58,237,0.3);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">Upgrade to Premium — 250 EGP/month</button>"""
)

# 4. triggerPaymentUI
content = content.replace(
    "total = (window.dashboardData || {}).subscription?.price || '24.99';",
    "total = (window.dashboardData || {}).subscription?.price || '250';"
)

# 5. growth trial removal in showPremiumModal
content = content.replace(
    "if (feature === 'motor' || feature === 'speech' || feature === 'growth') {",
    "if (feature === 'motor' || feature === 'speech') {"
)
content = content.replace(
    "if ((feature === 'motor' || feature === 'speech' || feature === 'growth') && left > 0) {",
    "if ((feature === 'motor' || feature === 'speech') && left > 0) {"
)

# 6. checkSubscriptionAccess speech trial fetch override
speech_fetch_orig = """        if (feature === 'speech') {
            try {
                const res = await fetch(`../../api_speech_history.php?child_id=${child.child_id}`);
                const data = await res.json();
                const count = (data.analyses || []).length;
                localStorage.setItem('bs_trial_speech', count);
            } catch (e) {}
        }"""
speech_fetch_new = """        if (feature === 'speech') {
            try {
                const res = await fetch(`../../api_speech_history.php?child_id=${child.child_id}`);
                const data = await res.json();
                const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
                const recentCount = (data.analyses || []).filter(a => new Date(a.sent_at) > sevenDaysAgo).length;
                localStorage.setItem('bs_trial_speech', recentCount);
            } catch (e) {}
        }"""
content = content.replace(speech_fetch_orig, speech_fetch_new)

# 7. incrementTrial
inc_trial_orig = """    window.incrementTrial = function (feature) {
        window['_access_granted_' + feature] = true;
        
        // Don't increment trial count for speech analysis as we use actual speech history count
        if (feature === 'speech') {
            return;
        }

        const trialKey = 'bs_trial_' + feature;
        let used = parseInt(localStorage.getItem(trialKey) || '0');
        localStorage.setItem(trialKey, used + 1);
    };"""
inc_trial_new = """    window.incrementTrial = function(feature) {
        window['_access_granted_' + feature] = true;
        
        const trialKey = 'bs_trial_' + feature;
        const windowKey = 'bs_trial_window_' + feature;
        const now = Date.now();
        const windowStart = parseInt(localStorage.getItem(windowKey) || '0');
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        
        // Reset counter if 7-day window has expired
        if (now - windowStart > sevenDays) {
            localStorage.setItem(trialKey, '0');
            localStorage.setItem(windowKey, String(now));
        }
        
        // Don't increment for speech (speech uses actual DB count)
        if (feature === 'speech') return;
        
        const used = parseInt(localStorage.getItem(trialKey) || '0');
        localStorage.setItem(trialKey, String(used + 1));
    };"""
content = content.replace(inc_trial_orig, inc_trial_new)

# 8. showPremiumModal 7-day window
show_prem_orig = """        const maxTrials = 3;
        const trialKey = feature ? 'bs_trial_' + feature : null;
        let used = trialKey ? parseInt(localStorage.getItem(trialKey) || '0') : 0;
        
        // Safeguard to clear corrupted local storage data from previous bug
        if (used > maxTrials) {
            used = 0;
            if (trialKey) localStorage.setItem(trialKey, '0');
        }"""
show_prem_new = """        const maxTrials = 3;
        const trialKey = feature ? 'bs_trial_' + feature : null;
        let used = trialKey ? parseInt(localStorage.getItem(trialKey) || '0') : 0;
        
        // Safeguard to clear corrupted local storage data from previous bug
        if (used > maxTrials) {
            used = 0;
            if (trialKey) localStorage.setItem(trialKey, '0');
        }

        const windowKey = feature ? 'bs_trial_window_' + feature : null;
        if (windowKey) {
            const windowStart = parseInt(localStorage.getItem(windowKey) || '0');
            const sevenDays = 7 * 24 * 60 * 60 * 1000;
            if (Date.now() - windowStart > sevenDays) {
                localStorage.setItem(trialKey, '0');
                localStorage.setItem(windowKey, String(Date.now()));
                used = 0;
            }
        }"""
content = content.replace(show_prem_orig, show_prem_new)

# 9. checkSubscriptionAccess 7-day window
check_sub_orig = """        const trialKey = 'bs_trial_' + feature;
        let used = parseInt(localStorage.getItem(trialKey) || '0');
        
        // Always show the modal for free users unless they've clicked 'Use Free Trial' this session"""
check_sub_new = """        const trialKey = 'bs_trial_' + feature;
        let used = parseInt(localStorage.getItem(trialKey) || '0');
        
        const windowKey = 'bs_trial_window_' + feature;
        const windowStart = parseInt(localStorage.getItem(windowKey) || '0');
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        if (Date.now() - windowStart > sevenDays) {
            // Window expired — reset
            localStorage.setItem(trialKey, '0');
            localStorage.setItem(windowKey, String(Date.now()));
            used = 0;
        }
        
        // Always show the modal for free users unless they've clicked 'Use Free Trial' this session"""
content = content.replace(check_sub_orig, check_sub_new)

# 10. Language support label in getSpeechView
speech_subtitle = """<p class="dashboard-subtitle">Track ${child ? child.first_name + "'s" : ''} vocabulary and pronunciation progress</p>"""
speech_subtitle_new = """<p class="dashboard-subtitle">Track ${child ? child.first_name + "'s" : ''} vocabulary and pronunciation progress</p>
                    <p style="font-size:0.8rem;color:var(--slate-500);margin-top:0.25rem;display:flex;align-items:center;gap:0.4rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 1 0 20 14.5 14.5 0 0 1 0-20"/><path d="M2 12h20"/></svg>
                        Supports <strong>English 🇬🇧</strong> and <strong>Arabic 🇪🇬</strong>
                    </p>"""
content = content.replace(speech_subtitle, speech_subtitle_new)

# 11. Growth record_id in getGrowthView()
growth_grouped_orig = """if (!grouped[dateStr]) grouped[dateStr] = { weight: '', height: '', head: '' };"""
growth_grouped_new = """if (!grouped[dateStr]) grouped[dateStr] = { record_id: r.record_id, weight: '', height: '', head: '' };"""
content = content.replace(growth_grouped_orig, growth_grouped_new)

# 12. streakCheckIn session guard
nav_init_orig = """        // Streak check-in
        streakCheckIn();"""
nav_init_new = """        // Streak check-in
        const todayKey = 'bs_streak_checkin_' + new Date().toDateString();
        if (!sessionStorage.getItem(todayKey)) {
            sessionStorage.setItem(todayKey, '1');
            streakCheckIn();
        }"""
content = content.replace(nav_init_orig, nav_init_new)

# 14. badge toast dedup
badge_toast_orig = """                // Show new badge notifications
                if (data.new_badges && data.new_badges.length > 0) {
                    data.new_badges.forEach(b => showBadgeToast(b));
                    if (!child.badges) child.badges = [];
                    data.new_badges.forEach(bName => {
                        if (!child.badges.find(b => b.name === bName)) {
                            child.badges.push({ name: bName, redeemed_at: new Date().toISOString() });
                        }
                    });"""
badge_toast_new = """                // Show new badge notifications
                if (data.new_badges && data.new_badges.length > 0) {
                    const existingNames = (child.badges || []).map(b => b.name);
                    const trulyNew = data.new_badges.filter(bName => !existingNames.includes(bName));
                    trulyNew.forEach(b => showBadgeToast(b));
                    if (!child.badges) child.badges = [];
                    trulyNew.forEach(bName => {
                        if (!child.badges.find(b => b.name === bName)) {
                            child.badges.push({ name: bName, redeemed_at: new Date().toISOString() });
                        }
                    });"""
content = content.replace(badge_toast_orig, badge_toast_new)

# 15. loadView post-render hook for settings
loadview_orig = """        // Post-render hooks
        if (viewId === 'home' || !viewId) {"""
loadview_new = """        // Post-render hooks
        if (viewId === 'settings') {
            setTimeout(() => {
                if (typeof loadInvoiceHistory === 'function') loadInvoiceHistory();
            }, 150);
        }
        if (viewId === 'home' || !viewId) {"""
content = content.replace(loadview_orig, loadview_new)

# 16. getSettingsView loading spinner instead of manual invoice load
invoice_btn_orig = """<button class="btn btn-outline btn-sm" onclick="loadInvoiceHistory()">Load Invoice History</button>"""
invoice_btn_new = """<button class="btn btn-outline btn-sm" onclick="loadInvoiceHistory()" id="invoice-refresh-btn">🔄 Refresh</button>"""
content = content.replace(invoice_btn_orig, invoice_btn_new)

invoice_msg_orig = """<div style="text-align:center;color:#64748b;font-style:italic;padding:2rem;">Click 'Load Invoice History' to view your payment records</div>"""
invoice_msg_new = """<div style="text-align:center;color:#64748b;padding:2rem;">Loading...</div>"""
content = content.replace(invoice_msg_orig, invoice_msg_new)

# 17. Download Report premium check
for dtype in ['full-report', 'growth-report', 'speech-report']:
    orig = f"""<button class="btn btn-gradient btn-sm" onclick="window.open('../../api_export_pdf.php?type={dtype}${{childParam}}','_blank')">📥 Download</button>"""
    new_btn = f"""<button class="btn btn-gradient btn-sm" onclick="if(!(window.dashboardData&&window.dashboardData.subscription&&window.dashboardData.subscription.plan_name==='Premium')){{window.showPremiumModal('reports');return;}} window.open('../../api_export_pdf.php?type={dtype}${{childParam}}','_blank')">📥 Download</button>"""
    content = content.replace(orig, new_btn)

# 18. Settings view subscription expiry visually
sub_setup_orig = """        const sub = (window.dashboardData || {}).subscription || {};
        const isPremium = sub.plan_name === 'Premium';"""
sub_setup_new = """        const sub = (window.dashboardData || {}).subscription || {};
        const isPremium = sub.plan_name === 'Premium';
        const expiredPremium = sub.expired_premium;
        const expiresAt = sub.expires_at ? new Date(sub.expires_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) : null;"""
content = content.replace(sub_setup_orig, sub_setup_new)

sub_plan_display_orig = """                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                            <div>
                                <h4 style="font-weight:700;font-size:1.1rem;color:var(--slate-900);margin-bottom:0.25rem;">${sub.plan_name} Plan</h4>
                                <p style="font-size:0.9rem;color:var(--slate-500);margin:0;">Active Subscription</p>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.25rem;">$${total} EGP<span style="font-size:0.85rem;color:var(--slate-500);font-weight:500;">/mo</span></div>
                            </div>
                        </div>"""
sub_plan_display_new = """                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                            <div>
                                <h4 style="font-weight:700;font-size:1.1rem;color:var(--slate-900);margin-bottom:0.25rem;">${sub.plan_name} Plan</h4>
                                <p style="font-size:0.9rem;color:var(--slate-500);margin:0;">Active Subscription</p>
                                <div style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">
                                    ${expiresAt ? `Expires: ${expiresAt}` : ''}
                                    ${expiredPremium ? '<span style="color:#ef4444;font-weight:700;margin-left:0.5rem;">⚠️ Subscription Expired</span>' : ''}
                                </div>
                                ${expiredPremium ? `<button class="btn btn-gradient btn-sm" style="margin-top:0.75rem;background:linear-gradient(135deg,#7c3aed,#4f46e5);" onclick="window.showPaymentModal('Premium', 250)">🔄 Renew Subscription — 250 EGP/month</button>` : ''}
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.25rem;">${total} EGP<span style="font-size:0.85rem;color:var(--slate-500);font-weight:500;">/mo</span></div>
                            </div>
                        </div>"""
# Since I already changed "$${total}" to "${total} EGP", I need to match that
content = content.replace(
    """<div style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.25rem;">${total} EGP<span style="font-size:0.85rem;color:var(--slate-500);font-weight:500;">/mo</span></div>""",
    """<div style="font-size:1.5rem;font-weight:800;color:var(--slate-900);margin-bottom:0.25rem;">${total} EGP<span style="font-size:0.85rem;color:var(--slate-500);font-weight:500;">/mo</span></div>"""
)
# Actually, let's just do a more targeted replace for the expiry UI:
content = content.replace(
    """<p style="font-size:0.9rem;color:var(--slate-500);margin:0;">Active Subscription</p>""",
    """<p style="font-size:0.9rem;color:var(--slate-500);margin:0;">Active Subscription</p>
                                <div style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">
                                    ${expiresAt ? `Expires: ${expiresAt}` : ''}
                                    ${expiredPremium ? '<span style="color:#ef4444;font-weight:700;margin-left:0.5rem;">⚠️ Subscription Expired</span>' : ''}
                                </div>
                                ${expiredPremium ? `<button class="btn btn-gradient btn-sm" style="margin-top:0.75rem;background:linear-gradient(135deg,#7c3aed,#4f46e5);" onclick="window.showPaymentModal('Premium', 250)">🔄 Renew Subscription — 250 EGP/month</button>` : ''}"""
)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
print("Changes applied!")
