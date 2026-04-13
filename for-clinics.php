<?php
ob_start();
session_start();
include 'connection.php';

// If already logged in as clinic, redirect
if (isset($_SESSION['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'clinic') {
    header("Location: clinic-dashboard.php");
    exit;
}

$login_errors = [];

if (isset($_POST['clinic_login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === '') $login_errors[] = "Email is required.";
    if ($password === '') $login_errors[] = "Password is required.";

    if (count($login_errors) === 0) {
        $sql = "SELECT * FROM clinic WHERE email = :email LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'pending') {
                    $login_errors[] = "Your clinic is pending approval. You will receive an email once approved.";
                } else {
                    $_SESSION['id'] = $user['clinic_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = 'clinic';
                    $_SESSION['name'] = $user['clinic_name'];
                    header("Location: clinic-dashboard.php");
                    exit;
                }
            } else {
                $login_errors[] = "Incorrect password.";
            }
        } else {
            $login_errors[] = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright Steps - For Clinics</title>
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .page-content { padding: 4rem 1.5rem; max-width: 1280px; margin: 0 auto; }
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .hero h1 { font-size: 3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; line-height: 1.1; }
        .hero p { font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; }
        .hero-img { width: 100%; border-radius: var(--radius-2xl); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .metric-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 4rem; z-index: 2; position: relative; }
        .metric-card { background: var(--bg-card); padding: 3rem 2rem; border-radius: 24px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 30px 50px -15px rgba(0,0,0,0.08); }
        .metric-card h3 { font-size: 4rem; font-weight: 800; background: linear-gradient(135deg, var(--blue-500), var(--purple-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; display: flex; align-items: baseline; justify-content: center; }
        
        .calculator { background: var(--bg-secondary); padding: 3rem; border-radius: var(--radius-2xl); margin-top: 5rem; margin-bottom: 4rem; border: 1px solid var(--border-color); }
        .calc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;}
        .calc-input { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
        .calc-input input { padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 1rem; }
        .calc-result { display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800; color: var(--green-600); }
        
        /* Clinic Auth Styles */
        .clinic-auth-container { max-width: 600px; margin: 0 auto 5rem; background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
        .clinic-auth-tabs { display: flex; border-bottom: 1px solid var(--border-color); }
        .auth-tab { flex: 1; padding: 1.5rem; text-align: center; font-weight: 700; font-size: 1.125rem; color: var(--text-secondary); cursor: pointer; transition: all 0.2s ease; background: var(--bg-secondary); }
        .auth-tab.active { color: var(--purple-600); background: var(--bg-card); border-bottom: 3px solid var(--purple-600); }
        .auth-tab:hover:not(.active) { color: var(--text-primary); }
        .auth-panel { display: none; padding: 3rem; }
        .auth-panel.active { display: block; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary); }
        .form-input { width: 100%; padding: 0.875rem 1rem; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-size: 1rem; transition: all 0.2s ease; box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: var(--purple-500); box-shadow: 0 0 0 4px rgba(168,85,247,0.1); }
        .btn-full { width: 100%; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1.125rem; }
        .error-box { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        
        .file-upload-wrapper { border: 2px dashed var(--slate-300); border-radius: 12px; padding: 2rem; text-align: center; background: var(--bg-tertiary); cursor: pointer; transition: all 0.2s ease; }
        .file-upload-wrapper:hover { border-color: var(--purple-500); }
        [data-theme="dark"] .file-upload-wrapper { border-color: var(--border-color); background: var(--bg-secondary); }

        @media(max-width:900px){ .hero, .calc-grid, .metric-cards { grid-template-columns: 1fr; } .auth-panel { padding: 2rem; } }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 5rem;">
        <div class="hero">
            <div>
                <h1>Modernize your <br><span style="color:var(--purple-600);">Pediatric Clinic</span></h1>
                <p>Equip your administration and specialists with a unified management system. Bright Steps for Clinics handles patient flows, subscriptions, doctor assignments, and aggregated revenue analytics smoothly and securely.</p>
                <div style="display:flex;gap:1rem;">
                    <button class="btn btn-gradient btn-lg" onclick="navigateTo('contact')">Request Enterprise Demo</button>
                </div>
            </div>
            <div>
                <img src="assets/clinic_info_graphic.png" alt="Clinic Dashboard Illustration" class="hero-img">
            </div>
        </div>

        <div class="metric-cards">
            <div class="metric-card">
                <h3><div class="counter" data-target="40">0</div><span style="font-size: 2rem; margin-left: 5px;">%</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">Decrease in admin overhead</p>
            </div>
            <div class="metric-card">
                <h3><div class="counter" data-target="2.5">0.0</div><span style="font-size: 2rem; margin-left: 5px;">x</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">Faster patient onboarding</p>
            </div>
            <div class="metric-card">
                <h3><div class="counter" data-target="100">0</div><span style="font-size: 2rem; margin-left: 5px;">%</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">HIPAA Compliant Infrastructure</p>
            </div>
        </div>

        <div class="calculator">
            <h2 style="font-size: 2rem; font-weight:800; margin-bottom: 2rem;">Calculate Your ROI</h2>
            <div class="calc-grid">
                <div>
                    <div class="calc-input">
                        <label style="font-weight:600;">Monthly Pediatric Appointments</label>
                        <input type="number" id="appts" value="1000" oninput="calculateROI()">
                    </div>
                    <div class="calc-input">
                        <label style="font-weight:600;">Current Admin Rate per patient ($)</label>
                        <input type="number" id="rate" value="15" oninput="calculateROI()">
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <h4 style="color:var(--text-secondary); margin-bottom:0.5rem;">Estimated Monthly Savings</h4>
                    <div class="calc-result">$<span id="savings">6000</span></div>
                    <p style="text-align:center; color:var(--text-secondary); margin-top:1rem; font-size:0.875rem;">Bright Steps automates tracking and reduces reporting time by up to 40%.</p>
                </div>
            </div>
        </div>

        <div id="clinic-portal" class="clinic-auth-container">
            <div class="clinic-auth-tabs">
                <div class="auth-tab <?php echo empty($login_errors) ? 'active' : ''; ?>" onclick="switchAuthTab('login')">Clinic Login</div>
                <div class="auth-tab <?php echo !empty($login_errors) ? 'active' : ''; ?>" onclick="switchAuthTab('register')">Register Clinic</div>
            </div>

            <!-- Login Panel -->
            <div id="panel-login" class="auth-panel <?php echo empty($login_errors) ? 'active' : ''; ?>">
                <h2 style="font-size:1.75rem; font-weight:800; margin-bottom:0.5rem;">Welcome Back</h2>
                <p style="color:var(--text-secondary); margin-bottom:2rem;">Log in to manage your clinic dashboard.</p>
                
                <?php if (!empty($login_errors)): ?>
                    <div class="error-box">
                        <ul style="margin:0; padding-left:1.5rem;">
                            <?php foreach ($login_errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="for-clinics.php#clinic-portal">
                    <div class="form-group">
                        <label for="login_email">Email Address</label>
                        <input type="email" id="login_email" name="email" class="form-input" placeholder="admin@domain.com" required>
                    </div>
                    <div class="form-group">
                        <label for="login_pwd">Password</label>
                        <input type="password" id="login_pwd" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="clinic_login" class="btn btn-gradient btn-full">Login</button>
                    <div style="text-align:center;margin-top:1rem;">
                        <a href="#" style="color:var(--purple-600);font-weight:600;font-size:0.9rem;" onclick="event.preventDefault(); alert('Password reset coming soon');">Forgot Password?</a>
                    </div>
                </form>
            </div>

            <!-- Register Panel -->
            <div id="panel-register" class="auth-panel <?php echo !empty($login_errors) ? 'active' : ''; ?>">
                <h2 style="font-size:1.75rem; font-weight:800; margin-bottom:0.5rem;">Partner with Us</h2>
                <p style="color:var(--text-secondary); margin-bottom:2rem;">Submit your clinic application to join Bright Steps.</p>
                
                <form id="clinic-register-form" onsubmit="handleClinicSignup(event)">
                    <div class="form-group">
                        <label for="clinic_name">Clinic Name</label>
                        <input type="text" id="clinic_name" class="form-input" placeholder="e.g. Hope Child Center" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_email">Admin Email Address</label>
                        <input type="email" id="reg_email" class="form-input" placeholder="admin@clinic.com" required>
                    </div>
                    <div class="form-group">
                        <label for="location">City / Location</label>
                        <input type="text" id="location" class="form-input" placeholder="e.g. Cairo, Egypt" required>
                    </div>
                    <div class="form-group">
                        <label>Verification Document</label>
                        <div class="file-upload-wrapper" id="drop-zone" onclick="document.getElementById('verification_doc').click()">
                            <svg style="width:32px;height:32px;color:var(--purple-500);margin-bottom:0.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <div style="font-weight:600;">Upload medical license</div>
                            <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:0.25rem;">PDF, JPG, PNG (Max. 5MB)</div>
                            <input type="file" id="verification_doc" hidden accept=".pdf,.jpg,.jpeg,.png">
                            <div id="file-name" style="margin-top:0.5rem;font-size:0.85rem;color:var(--purple-600);font-weight:600;"></div>
                        </div>
                    </div>
                    <div id="form-error" class="error-box" style="display:none;"></div>
                    <button id="submit-btn" type="submit" class="btn btn-gradient btn-full">Submit Application</button>
                </form>

                <div id="success-state" style="display:none;text-align:center;padding:2rem 0;">
                    <div style="width:64px;height:64px;border-radius:50%;background:rgba(16, 185, 129, 0.1);color:#10b981;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:32px;height:32px;"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Application Received!</h2>
                    <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.5;">Your clinic registration has been submitted and is currently under review. We will contact you once approved.</p>
                </div>
            </div>
        </div>
    </main>

    
    <script>
        document.querySelectorAll('.counter').forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const speed = 200;
                const inc = target / speed;
                if(count < target) {
                    counter.innerText = (count + inc).toFixed(target % 1 !== 0 ? 1 : 0);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });

        function calculateROI() {
            const appts = document.getElementById('appts').value || 0;
            const rate = document.getElementById('rate').value || 0;
            // 40% savings in admin time estimated
            const savingsDom = document.getElementById('savings');
            const total = Math.round(appts * rate * 0.40);
            savingsDom.innerText = total.toLocaleString();
        }

        // Clinic Auth Logic
        function switchAuthTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
            
            if (tab === 'login') {
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
                document.getElementById('panel-login').classList.add('active');
            } else {
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
                document.getElementById('panel-register').classList.add('active');
            }
        }

        const fileInp = document.getElementById('verification_doc');
        const fileName = document.getElementById('file-name');
        if(fileInp) {
            fileInp.addEventListener('change', (e) => {
                if(e.target.files.length) fileName.textContent = e.target.files[0].name;
            });
        }

        async function handleClinicSignup(e) {
            e.preventDefault();
            const form = document.getElementById('clinic-register-form');
            const err = document.getElementById('form-error');
            const sub = document.getElementById('submit-btn');
            
            const name = document.getElementById('clinic_name').value.trim();
            const email = document.getElementById('reg_email').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!name || !email || !location) {
                err.textContent = 'Please fill all required fields';
                err.style.display = 'block';
                return;
            }

            err.style.display = 'none';
            sub.innerHTML = 'Submitting...';
            sub.disabled = true;

            try {
                let body = new FormData();
                body.append('clinic_name', name);
                body.append('email', email);
                body.append('location', location);
                if (fileInp && fileInp.files[0]) body.append('verification_doc', fileInp.files[0]);

                const res = await fetch('api_clinic_signup.php', { method: 'POST', body: body });
                const data = await res.json();
                
                if (data.success) {
                    form.style.display = 'none';
                    document.getElementById('success-state').style.display = 'block';
                } else {
                    err.textContent = data.error || 'Registration failed';
                    err.style.display = 'block';
                    sub.innerHTML = 'Submit Application';
                    sub.disabled = false;
                }
            } catch (error) {
                err.textContent = 'Network error. Please try again.';
                err.style.display = 'block';
                sub.innerHTML = 'Submit Application';
                sub.disabled = false;
            }
        }
    </script>

    <?php include 'includes/public_footer.php'; ?>

    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>

</html>
