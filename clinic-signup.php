<?php
ob_start();
session_start();
include 'connection.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') header("Location: admin-dashboard.php");
    elseif ($_SESSION['role'] === 'doctor') header("Location: doctor-dashboard.php");
    elseif ($_SESSION['role'] === 'clinic') header("Location: dashboards/clinic/clinic-dashboard.php");
    else header("Location: dashboards/parent/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Registration - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=12">
    <link rel="stylesheet" href="styles/auth.css?v=12">
    <style>
        .input-error { border: 1px solid #e53935 !important; }
        .error-message { color: #e53935; font-size: 13px; margin-top: 6px; }
        .file-upload-wrapper {
            border: 2px dashed var(--slate-300);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            background: var(--bg-tertiary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .file-upload-wrapper:hover { border-color: var(--primary-color); background: var(--bg-card-hover); }
        .file-upload-wrapper.dragover { border-color: var(--primary-color); background: rgba(99, 102, 241, 0.1); }
        [data-theme="dark"] .file-upload-wrapper { border-color: var(--border-color); background: var(--bg-secondary); }
    </style>
</head>
<body>
    <div class="auth-page auth-split-layout">
        <div class="auth-form-side" style="overflow-y: auto;">
            <button class="back-button" onclick="window.history.back()" style="position: absolute; top: 1.5rem; left: 1.5rem;">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" /></svg> Back
            </button>

            <div class="auth-container" style="margin: 4rem auto;">
                <div class="auth-card">
                    <div class="auth-header">
                        <a href="index.php" class="auth-logo"><img src="assets/logo.png" alt="Bright Steps" style="height: 4rem;"></a>
                        <h1 class="auth-title">Register Your Clinic</h1>
                        <p class="auth-subtitle">Join our network of elite child development specialists</p>
                    </div>

                    <form id="clinic-register-form" class="auth-form" onsubmit="handleClinicSignup(event)">
                        <div class="form-group">
                            <label class="form-label" for="clinic_name">Clinic Name</label>
                            <input type="text" id="clinic_name" class="form-input" placeholder="e.g. Hope Child Center" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Admin Email Address</label>
                            <input type="email" id="email" class="form-input" placeholder="admin@clinic.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="location">City / Location</label>
                            <input type="text" id="location" class="form-input" placeholder="e.g. Cairo, Egypt" required>
                        </div>
                        
                        <div class="form-group" style="display:none;" id="password-group">
                            <label class="form-label" for="password">Create Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="password" class="form-input" placeholder="Strong password (min 8 chars)">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword(this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Verification Documentation</label>
                            <div class="file-upload-wrapper" id="drop-zone" onclick="document.getElementById('verification_doc').click()">
                                <svg style="width:32px;height:32px;color:var(--primary-color);margin-bottom:0.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <div style="font-size:0.9rem;font-weight:600;">Click to upload medical license</div>
                                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.25rem;">PDF, JPG, PNG (Max. 5MB)</div>
                                <input type="file" id="verification_doc" hidden accept=".pdf,.jpg,.jpeg,.png">
                                <div id="file-name" style="margin-top:0.5rem;font-size:0.8rem;color:var(--primary-color);font-weight:600;"></div>
                            </div>
                        </div>

                        <div id="form-error" class="error-message" style="text-align:center;margin-bottom:1rem;display:none;"></div>

                        <button id="submit-btn" type="submit" class="btn btn-gradient btn-lg btn-full" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                            Submit Application <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </button>
                    </form>

                    <!-- Success State -->
                    <div id="success-state" style="display:none;text-align:center;padding:2rem 0;">
                        <div style="width:64px;height:64px;border-radius:50%;background:rgba(16, 185, 129, 0.1);color:#10b981;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:32px;height:32px;"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Application Received!</h2>
                        <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.5;margin-bottom:2rem;">Your clinic registration has been submitted and is currently under review by our administration team. We will contact you via email once approved.</p>
                        <button onclick="window.location.href='index.php'" class="btn btn-primary btn-full">Return to Home</button>
                    </div>

                    <div class="modern-auth-footer">
                        <div class="footer-divider"></div>
                        <p class="auth-footer-text">Already registered? <a href="login.php" class="auth-link font-semibold">Log in here</a></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-image-side">
            <div class="auth-top-nav">
                <button class="nav-link language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10" /><line x1="2" y1="12" x2="22" y2="12" /><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" /></svg> عربي
                </button>
                <button class="nav-link theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
                    <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="5" /><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" /></svg>
                    <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" /></svg>
                </button>
            </div>
            <img src="assets/auth-illustration.png" class="auth-illustration" alt="Bright Steps Growth and Care">
        </div>
    </div>

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script>
        const fileInp = document.getElementById('verification_doc');
        const fileName = document.getElementById('file-name');
        
        fileInp.addEventListener('change', (e) => {
            if(e.target.files.length) {
                fileName.textContent = e.target.files[0].name;
            }
        });

        async function handleClinicSignup(e) {
            e.preventDefault();
            const form = document.getElementById('clinic-register-form');
            const err = document.getElementById('form-error');
            const sub = document.getElementById('submit-btn');
            
            const name = document.getElementById('clinic_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!name || !email || !location) {
                err.textContent = 'Please fill all required fields';
                err.style.display = 'block';
                return;
            }

            err.style.display = 'none';
            sub.innerHTML = '<span class="spinner" style="width:18px;height:18px;border:2px solid #fff;border-bottom-color:transparent;border-radius:50%;display:inline-block;animation:spin 1s linear infinite;"></span> Submitting...';
            sub.disabled = true;

            try {
                // Submit to specific public sign-up handler
                let body = new FormData();
                body.append('clinic_name', name);
                body.append('email', email);
                body.append('location', location);
                if (fileInp.files[0]) {
                    body.append('verification_doc', fileInp.files[0]);
                }

                const res = await fetch('api_clinic_signup.php', { method: 'POST', body: body });
                const data = await res.json();
                
                if (data.success) {
                    form.style.display = 'none';
                    document.getElementById('success-state').style.display = 'block';
                } else {
                    err.textContent = data.error || 'Registration failed';
                    err.style.display = 'block';
                    sub.innerHTML = 'Submit Application <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
                    sub.disabled = false;
                }
            } catch (error) {
                console.error(error);
                err.textContent = 'Network error. Please try again later.';
                err.style.display = 'block';
                sub.innerHTML = 'Submit Application <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
                sub.disabled = false;
            }
        }
    </script>
    <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
</body>
</html>
