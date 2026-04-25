<?php
ob_start();
session_start();
include 'connection.php';

// If already logged in as clinic, redirect to dashboard
if (isset($_SESSION['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'clinic') {
    header("Location: dashboards/clinic/clinic-dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Clinic - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=12">
    <style>
        .input-error {
            border: 1px solid #e53935 !important;
        }

        .error-message {
            color: #e53935;
            font-size: 13px;
            margin-top: 6px;
        }

        .success-card {
            text-align: center;
            padding: 2rem 0;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green-100), var(--green-50));
            border: 2px solid var(--green-300);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon svg {
            width: 32px;
            height: 32px;
            color: var(--green-600);
        }

        [data-theme="dark"] .success-icon {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-secondary);
        }

        .upload-area:hover {
            border-color: var(--blue-400);
            background: var(--blue-50);
        }

        [data-theme="dark"] .upload-area:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .upload-area svg {
            width: 2rem;
            height: 2rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .upload-area p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }

        .file-name {
            font-size: 0.8rem;
            color: var(--green-600);
            margin-top: 0.25rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="auth-page auth-split-layout">
        <div class="auth-form-side signup-form-side">
        <button class="back-button" onclick="navigateTo('for-clinics')">
            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Back
        </button>

        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <a href="index.php" class="auth-logo">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </a>
                    <span class="doctor-badge-small" style="background: linear-gradient(135deg, var(--cyan-100, #cffafe), var(--green-100, #dcfce7)); color: var(--cyan-800, #155e75); border-color: var(--cyan-200, #a5f3fc);">New Clinic Registration</span>
                    <h1 class="auth-title">Register Your Clinic</h1>
                    <p class="auth-subtitle">Join our network and manage your practice digitally</p>
                </div>

                <!-- Registration Form -->
                <form id="clinic-signup-form" class="auth-form" novalidate enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="clinic_name">Clinic Name</label>
                        <input type="text" name="clinic_name" id="clinic_name" class="form-input" placeholder="e.g. City Kids Healthcare" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Clinic Email</label>
                        <input type="email" name="email" id="email" class="form-input" placeholder="contact@yourclinic.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location / Address</label>
                        <input type="text" name="location" id="location" class="form-input" placeholder="123 Health St, Cairo, Egypt" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Verification Document <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                        <div class="upload-area" onclick="document.getElementById('verification_doc').click()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <p>Click to upload license or registration document</p>
                            <div id="file-display" class="file-name"></div>
                        </div>
                        <input type="file" name="verification_doc" id="verification_doc" style="display:none;" accept=".pdf,.jpg,.jpeg,.png" onchange="showFileName(this)">
                    </div>

                    <div id="signup-error" style="display:none;" class="error-message"></div>

                    <button type="submit" class="btn btn-gradient btn-lg btn-full" id="signup-btn">Submit Application</button>
                </form>

                <!-- Success Message (hidden by default) -->
                <div id="signup-success" class="success-card" style="display:none;">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h2 style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:0.75rem;">Application Submitted!</h2>
                    <p style="color:var(--text-secondary);margin-bottom:1.5rem;line-height:1.6;">
                        Your clinic registration is now pending admin review. 
                        You'll receive an email once your account has been approved.
                    </p>
                    <a href="clinic-login.php" class="btn btn-gradient btn-lg">Go to Login</a>
                </div>

                <div class="modern-auth-footer" id="signup-footer">
                    <p class="auth-footer-text">
                        Already registered? 
                        <a href="#" class="auth-link font-semibold" onclick="navigateTo('clinic-login'); return false;">Sign In</a>
                    </p>
                    <div class="footer-divider"></div>
                    <p class="auth-footer-text" style="color: var(--slate-500, #64748b);">
                        Healthcare provider? 
                        <a href="#" class="auth-link doctor-link" onclick="navigateTo('doctor-login'); return false;">Doctor Portal</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right side image and toggles -->
    <div class="auth-image-side">
        <div class="auth-top-nav">
            <button class="nav-link language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                عربي
            </button>

            <button class="nav-link theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="12" cy="12" r="5" />
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                </svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
            </button>
        </div>
        <img src="assets/clinic-login-illustration.png" class="auth-illustration" alt="Bright Steps Clinic Registration">
    </div>
</div>

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script>
        function showFileName(input) {
            const display = document.getElementById('file-display');
            if (input.files.length > 0) {
                display.textContent = '📎 ' + input.files[0].name;
            } else {
                display.textContent = '';
            }
        }

        document.getElementById('clinic-signup-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const errorDiv = document.getElementById('signup-error');
            const btn = document.getElementById('signup-btn');
            errorDiv.style.display = 'none';

            const name = document.getElementById('clinic_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!name || !email || !location) {
                errorDiv.textContent = 'Clinic name, email, and location are required.';
                errorDiv.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Submitting...';

            try {
                const formData = new FormData(this);
                const res = await fetch('api_clinic_signup.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('clinic-signup-form').style.display = 'none';
                    document.getElementById('signup-footer').style.display = 'none';
                    document.getElementById('signup-success').style.display = 'block';
                } else {
                    errorDiv.textContent = data.error || 'Registration failed. Please try again.';
                    errorDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Submit Application';
                }
            } catch (err) {
                errorDiv.textContent = 'Network error. Please try again.';
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Submit Application';
            }
        });
    </script>
</body>

</html>
