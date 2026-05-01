// Navigation utility
function navigateTo(page) {
    console.log('navigateTo called with:', page);
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');

    const pageMap = {
        'index': 'index.php',
        'login': 'login.php',
        'signup': 'signup.php',
        'dashboard': 'dashboard.php',
        'admin-dashboard': 'admin-dashboard.php',
        'clinic-dashboard': 'clinic-dashboard.php',
        'doctor-login': 'doctor-login.php',
        'doctor-signup': 'doctor-signup.php',
        'doctor-dashboard': 'doctor-dashboard.php',
        'settings': 'settings.php',
        'profile': 'profile.php',
        'child-profile': 'child-profile.php',
        'about': 'about.php',
        'contact': 'contact.php',
        'privacy': 'privacy.php',
        'terms': 'terms.php',
        'help': 'help.php',
        'features': 'features.php',
        'pricing': 'pricing.php',
        'demo': 'demo.php'
    };

    const targetPage = pageMap[page] || page + '.php';
    console.log('Navigating to:', baseUrl + targetPage);
    window.location.href = baseUrl + targetPage;
}

// Check authentication
function checkAuth() {
    return sessionStorage.getItem('isAuthenticated') === 'true';
}

// Set authentication
function setAuth(value) {
    sessionStorage.setItem('isAuthenticated', value.toString());
}

// Clear authentication
function clearAuth() {
    sessionStorage.removeItem('isAuthenticated');
    sessionStorage.removeItem('userData');
}

// Get user data
function getUserData() {
    const data = sessionStorage.getItem('userData');
    return data ? JSON.parse(data) : null;
}

// Set user data
function setUserData(data) {
    sessionStorage.setItem('userData', JSON.stringify(data));
}

// Protect dashboard page
function protectDashboard() {
    if (!checkAuth()) {
        navigateTo('login');
    }
}

// ── Contact & Support Popup ─────────────────────────────────────────
function showSupportPopup() {
    // Determine API path relative to current URL
    const isRoot = window.location.pathname.endsWith('/') || window.location.pathname.endsWith('.php') && !window.location.pathname.includes('/dashboards/');
    const apiPath = isRoot ? 'api_support_tickets.php' : '../../api_support_tickets.php';

    const overlay = document.createElement('div');
    overlay.className = 'support-modal-overlay';
    overlay.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); 
        display: flex; align-items: center; justify-content: center; 
        z-index: 10000; opacity: 0; transition: opacity 0.3s ease;
    `;
    
    overlay.innerHTML = `
        <div class="support-modal glass-effect" style="
            background: var(--bg-card, #ffffff); width: 90%; max-width: 500px; 
            border-radius: 20px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.15); 
            transform: scale(0.95) translateY(20px); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative; overflow: hidden; border: 1px solid rgba(99, 102, 241, 0.1);
        ">
            <!-- Header decoration -->
            <div style="position:absolute; top:0; left:0; right:0; height:6px; background: linear-gradient(90deg, #4f46e5, #ec4899);"></div>
            
            <button onclick="this.closest('.support-modal-overlay').remove()" style="
                position: absolute; top: 1.25rem; right: 1.25rem; background: none; border: none; 
                font-size: 1.5rem; color: var(--text-secondary, #64748b); cursor: pointer; transition: color 0.2s;
            " onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">×</button>
            
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom: 1.5rem;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(236,72,153,0.1)); display:flex; align-items:center; justify-content:center; color: #4f46e5;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <div>
                    <h2 style="margin:0; font-size: 1.25rem; color: var(--text-primary, #1e293b);">Contact Support</h2>
                    <p style="margin:0; font-size: 0.85rem; color: var(--text-secondary, #64748b);">We're here to help you</p>
                </div>
            </div>
            
            <div id="support-status" style="margin-bottom: 1rem; font-size: 0.9rem; display:none; padding: 0.75rem; border-radius: 8px;"></div>
            
            <form id="support-form" onsubmit="event.preventDefault(); submitSupportTicket('${apiPath}');">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 500; color: var(--text-primary, #334155);">Subject</label>
                    <input type="text" id="ticket-subject" required placeholder="What do you need help with?" style="
                        width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color, #e2e8f0); 
                        border-radius: 10px; font-size: 0.95rem; background: var(--bg-primary, #f8fafc); 
                        color: var(--text-primary, #1e293b); transition: border-color 0.2s; box-sizing: border-box;
                    " onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='var(--border-color, #e2e8f0)'">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 500; color: var(--text-primary, #334155);">Priority</label>
                    <select id="ticket-priority" style="
                        width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color, #e2e8f0); 
                        border-radius: 10px; font-size: 0.95rem; background: var(--bg-primary, #f8fafc); 
                        color: var(--text-primary, #1e293b); transition: border-color 0.2s; box-sizing: border-box;
                    ">
                        <option value="low">Low (General Question)</option>
                        <option value="medium" selected>Medium (Issue/Bug)</option>
                        <option value="high">High (Urgent Problem)</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 500; color: var(--text-primary, #334155);">Message</label>
                    <textarea id="ticket-message" required rows="4" placeholder="Describe your issue in detail..." style="
                        width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color, #e2e8f0); 
                        border-radius: 10px; font-size: 0.95rem; background: var(--bg-primary, #f8fafc); 
                        color: var(--text-primary, #1e293b); transition: border-color 0.2s; box-sizing: border-box; 
                        resize: vertical; font-family: inherit;
                    " onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='var(--border-color, #e2e8f0)'"></textarea>
                </div>
                
                <button type="submit" id="ticket-submit-btn" style="
                    width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #4f46e5, #6366f1); 
                    color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; 
                    cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; 
                    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(79, 70, 229, 0.4)'" 
                   onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 12px rgba(79, 70, 229, 0.3)'">Submit Ticket</button>
            </form>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Trigger animations
    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        overlay.querySelector('.support-modal').style.transform = 'scale(1) translateY(0)';
    });
}

async function submitSupportTicket(apiPath) {
    const btn = document.getElementById('ticket-submit-btn');
    const status = document.getElementById('support-status');
    const subject = document.getElementById('ticket-subject').value;
    const message = document.getElementById('ticket-message').value;
    const priority = document.getElementById('ticket-priority').value;
    
    btn.disabled = true;
    btn.textContent = 'Submitting...';
    btn.style.opacity = '0.7';
    
    try {
        const res = await fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create_ticket', subject, message, priority })
        });
        const data = await res.json();
        
        status.style.display = 'block';
        if (data.success) {
            status.style.background = 'rgba(34, 197, 94, 0.1)';
            status.style.color = '#15803d';
            status.innerHTML = '✅ ' + data.message;
            document.getElementById('support-form').reset();
            setTimeout(() => {
                const overlay = document.querySelector('.support-modal-overlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                    setTimeout(() => overlay.remove(), 300);
                }
            }, 2000);
        } else {
            status.style.background = 'rgba(239, 68, 68, 0.1)';
            status.style.color = '#b91c1c';
            status.innerHTML = '❌ ' + (data.error || 'Failed to submit ticket');
            btn.disabled = false;
            btn.textContent = 'Submit Ticket';
            btn.style.opacity = '1';
        }
    } catch (e) {
        status.style.display = 'block';
        status.style.background = 'rgba(239, 68, 68, 0.1)';
        status.style.color = '#b91c1c';
        status.innerHTML = '❌ Network error. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Submit Ticket';
        btn.style.opacity = '1';
    }
}
