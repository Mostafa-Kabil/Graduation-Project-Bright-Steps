<?php
ob_start();
session_start();
include 'connection.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin-dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'doctor') {
        header("Location: doctor-dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'clinic') {
        header("Location: clinic-dashboard.php");
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}

$errors = [];

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // basic validation
    if ($email === '') {
        $errors[] = "Email is required.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (count($errors) === 0) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
<<<<<<< HEAD
                // credentials are correct – set session
                $_SESSION['id'] = $user['user_id'];
                $_SESSION['fname'] = $user['first_name'];
                $_SESSION['lname'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // route by role
                if ($user['role'] === 'admin') {
                    header("Location: admin-dashboard.php");
                    exit;
                } elseif ($user['role'] === 'doctor') {
                    header("Location: doctor-dashboard.php");
                    exit;
                } elseif ($user['role'] === 'clinic') {
                    header("Location: clinic-dashboard.php");
                    exit;
                } elseif ($user['role'] === 'parent') {
                    header("Location: dashboard.php");
=======
                if ($user['role'] === 'parent') {
                    // credentials are correct – set session and redirect
                    $_SESSION['id'] = $user['user_id'];
                    $_SESSION['fname'] = $user['first_name'];
                    $_SESSION['lname'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Check if parent has any children
                    $checkChildStmt = $connect->prepare("SELECT number_of_children FROM parent WHERE parent_id = :pid");
                    $checkChildStmt->execute(['pid' => $user['user_id']]);
                    $parentData = $checkChildStmt->fetch(PDO::FETCH_ASSOC);

                    if ($parentData && $parentData['number_of_children'] > 0) {
                        header("Location: dashboard.php");
                    } else {
                        header("Location: add-child.php?setup=1");
                    }
>>>>>>> 3b183b9b2382326ae3269829540b85c5a35c1ef0
                    exit;
                } else {
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $errors[] = "Incorrect email or password.";
            }
        } else {
            $errors[] = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/auth.css">
    <style>
        .input-error {
            border: 1px solid #e53935 !important;
        }

        .error-message {
            color: #e53935;
            font-size: 13px;
            margin-top: 6px;
        }
<<<<<<< HEAD
=======

        /* Forgot password modal */
        .forgot-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .forgot-overlay.show {
            display: flex;
        }

        .forgot-card {
            background: var(--white, #fff);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp .3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .forgot-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--slate-900, #1e293b);
        }

        .forgot-card p {
            color: var(--slate-500, #64748b);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .forgot-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--slate-200, #e2e8f0);
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }

        .forgot-input:focus {
            border-color: #6C63FF;
        }

        .forgot-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #6C63FF, #a78bfa);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .forgot-btn:disabled {
            opacity: 0.5;
        }

        .forgot-error {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .forgot-success {
            color: #22c55e;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .forgot-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--slate-400);
            cursor: pointer;
        }

        .forgot-steps {
            display: none;
        }

        .forgot-steps.active {
            display: block;
        }

        .code-row {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .code-row input {
            width: 3rem;
            height: 3.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid var(--slate-200, #e2e8f0);
            border-radius: 12px;
            outline: none;
        }

        .code-row input:focus {
            border-color: #6C63FF;
        }
>>>>>>> 3b183b9b2382326ae3269829540b85c5a35c1ef0
    </style>
</head>

<body>
    <div class="auth-page">
        <button class="back-button" onclick="navigateTo('index')">
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
                    <h1 class="auth-title">Welcome back!</h1>
                    <p class="auth-subtitle">Continue your child's development journey</p>
                </div>

                <form novalidate id="login-form" method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
<<<<<<< HEAD
                        <input type="email" name="email" id="email" class="form-input" placeholder="parent@example.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label" for="password">Password</label>
                            <a href="#" class="form-link">Forgot?</a>
                        </div>
                        <input type="password" name="password" id="password" class="form-input" placeholder="••••••••"
                            required>
                    </div>

                    <button type="submit" name="login" class="btn btn-gradient btn-lg btn-full">Log In</button>
                </form>

                <?php if (!empty($errors)): ?>
                    <script>
                        alert(<?php echo json_encode(implode("\n", $errors)); ?>);
                    </script>
                <?php endif; ?>
=======

                        <input type="email" name="email" id="email" class="form-input 
            <?php
            foreach ($errors as $error) {
                if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password." || $error === "This portal is for parents only.") {
                    echo "input-error";
                    break;
                }
            }
            ?>" placeholder="parent@example.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password." || $error === "This portal is for parents only."): ?>
                                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label" for="password">Password</label>
                            <a href="#" class="form-link" onclick="openForgotModal(); return false;">Forgot?</a>
                        </div>

                        <input type="password" name="password" id="password" class="form-input 
            <?php
            foreach ($errors as $error) {
                if ($error === "Password is required." || $error === "Incorrect email or password.") {
                    echo "input-error";
                    break;
                }
            }
            ?>" placeholder="••••••••" required>

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Password is required." || $error === "Incorrect email or password."): ?>
                                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="login" class="btn btn-gradient btn-lg btn-full">Log In</button>
                </form>
>>>>>>> 3b183b9b2382326ae3269829540b85c5a35c1ef0

                <div class="auth-footer">
                    <span class="auth-footer-text">Don't have an account? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('signup'); return false;">Sign up</a>
                </div>

                <div class="auth-divider">
                    <span>Or continue with</span>
                </div>

                <div class="social-buttons">
                    <button class="btn btn-outline btn-social" onclick="openSocialLogin('google')">
                        <svg class="social-icon" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Continue with Google
                    </button>
                    <button class="btn btn-outline btn-social" onclick="openSocialLogin('facebook')">
                        <svg class="social-icon" viewBox="0 0 24 24">
                            <path fill="#1877F2"
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Continue with Facebook
                    </button>
                </div>

                <!-- Social Login Modal -->
                <div class="forgot-overlay" id="social-modal">
                    <div class="forgot-card" style="position:relative;">
                        <button class="forgot-close" onclick="closeSocialModal()">&times;</button>
                        <div id="social-icon-area"
                            style="width:3.5rem;height:3.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        </div>
                        <h2 id="social-title">Continue with Google</h2>
                        <p style="color:var(--slate-500,#64748b);font-size:0.9rem;margin-bottom:1.5rem;">Enter your
                            email to sign in or create an account</p>
                        <input type="email" class="forgot-input" id="social-email" placeholder="your.email@gmail.com">
                        <input type="text" class="forgot-input" id="social-name"
                            placeholder="Full Name (for new accounts)" style="display:none;">
                        <button class="forgot-btn" id="social-btn" onclick="submitSocialLogin()">Continue</button>
                        <div class="forgot-error" id="social-error"></div>
                        <div class="forgot-success" id="social-success"></div>
                    </div>
                </div>

                <div class="auth-divider">
                    <span>Or</span>
                </div>

                <div class="doctor-login-link" style="text-align: center; font-size: 14px; margin-top: 15px;">
                    <span style="color: #64748b;">Are you a healthcare provider? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('doctor-login'); return false;">Doctor Portal</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>

    <!-- Floating Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path
                d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <!-- Forgot Password Modal -->
    <div class="forgot-overlay" id="forgot-modal">
        <div class="forgot-card" style="position:relative;">
            <button class="forgot-close" onclick="closeForgotModal()">&times;</button>

            <!-- Step 1: Enter Email -->
            <div class="forgot-steps active" id="forgot-step1">
                <h2>Forgot Password?</h2>
                <p>Enter your email and we'll send you a reset code.</p>
                <input type="email" class="forgot-input" id="forgot-email" placeholder="parent@example.com">
                <button class="forgot-btn" onclick="sendForgotCode()">Send Reset Code</button>
                <div class="forgot-error" id="forgot-error1"></div>
                <div class="forgot-success" id="forgot-success1"></div>
            </div>

            <!-- Step 2: Enter Code -->
            <div class="forgot-steps" id="forgot-step2">
                <h2>Enter Reset Code</h2>
                <p>We sent a 6-digit code to your email.</p>
                <div class="code-row" id="reset-code-row">
                    <input type="text" maxlength="1" inputmode="numeric">
                    <input type="text" maxlength="1" inputmode="numeric">
                    <input type="text" maxlength="1" inputmode="numeric">
                    <input type="text" maxlength="1" inputmode="numeric">
                    <input type="text" maxlength="1" inputmode="numeric">
                    <input type="text" maxlength="1" inputmode="numeric">
                </div>
                <input type="password" class="forgot-input" id="new-password" placeholder="New password (min 8 chars)">
                <div id="forgot-pwd-strength" class="password-strength"></div>
                <button class="forgot-btn" onclick="resetPassword()" style="margin-top:0.5rem;">Reset Password</button>
                <div class="forgot-error" id="forgot-error2"></div>
                <div class="forgot-success" id="forgot-success2"></div>
            </div>

            <!-- Step 3: Success -->
            <div class="forgot-steps" id="forgot-step3">
                <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
                <h2>Password Reset!</h2>
                <p>Your password has been updated. You can now log in.</p>
                <button class="forgot-btn" onclick="closeForgotModal()">Back to Login</button>
            </div>
        </div>
    </div>

    <script src="scripts/language-toggle.js?v=5"></script>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <script src="scripts/password-strength.js"></script>
    <script>
        let forgotEmail = '';
        function openForgotModal() {
            document.getElementById('forgot-modal').classList.add('show');
            document.getElementById('forgot-step1').classList.add('active');
            document.getElementById('forgot-step2').classList.remove('active');
            document.getElementById('forgot-step3').classList.remove('active');
        }
        function closeForgotModal() {
            document.getElementById('forgot-modal').classList.remove('show');
        }
        async function sendForgotCode() {
            const email = document.getElementById('forgot-email').value.trim();
            const err = document.getElementById('forgot-error1');
            const suc = document.getElementById('forgot-success1');
            err.textContent = ''; suc.textContent = '';
            if (!email) { err.textContent = 'Enter your email'; return; }
            try {
                const res = await fetch('api_email_verify.php?action=forgot', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                if (data.success) {
                    forgotEmail = email;
                    suc.textContent = data.message;
                    if (data.dev_code) suc.textContent += ' (Dev: ' + data.dev_code + ')';
                    setTimeout(() => {
                        document.getElementById('forgot-step1').classList.remove('active');
                        document.getElementById('forgot-step2').classList.add('active');
                        // Wire up code inputs auto-advance
                        document.querySelectorAll('#reset-code-row input').forEach((inp, i, all) => {
                            inp.addEventListener('input', () => { if (inp.value && i < all.length - 1) all[i + 1].focus(); });
                            inp.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !inp.value && i > 0) all[i - 1].focus(); });
                        });
                    }, 1500);
                } else { err.textContent = data.error; }
            } catch (e) { err.textContent = 'Network error'; }
        }
        async function resetPassword() {
            const inputs = document.querySelectorAll('#reset-code-row input');
            const code = Array.from(inputs).map(i => i.value).join('');
            const password = document.getElementById('new-password').value;
            const err = document.getElementById('forgot-error2');
            err.textContent = '';
            if (code.length < 6) { err.textContent = 'Enter all 6 digits'; return; }
            if (password.length < 8) { err.textContent = 'Password must be at least 8 characters'; return; }
            try {
                const res = await fetch('api_email_verify.php?action=reset', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: forgotEmail, code, password })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('forgot-step2').classList.remove('active');
                    document.getElementById('forgot-step3').classList.add('active');
                } else { err.textContent = data.error; }
            } catch (e) { err.textContent = 'Network error'; }
        }
        // ── Social Login ──
        let socialProvider = 'google';
        function openSocialLogin(provider) {
            socialProvider = provider;
            document.getElementById('social-modal').classList.add('show');
            const icon = document.getElementById('social-icon-area');
            const title = document.getElementById('social-title');
            document.getElementById('social-error').textContent = '';
            document.getElementById('social-success').textContent = '';
            document.getElementById('social-email').value = '';
            document.getElementById('social-name').value = '';
            document.getElementById('social-name').style.display = 'none';
            if (provider === 'google') {
                icon.style.background = '#fff';
                icon.innerHTML = '<svg viewBox="0 0 24 24" width="28" height="28"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
                title.textContent = 'Continue with Google';
                document.getElementById('social-btn').style.background = '#4285F4';
            } else {
                icon.style.background = '#1877F2';
                icon.innerHTML = '<svg viewBox="0 0 24 24" width="28" height="28"><path fill="white" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
                title.textContent = 'Continue with Facebook';
                document.getElementById('social-btn').style.background = '#1877F2';
            }
        }
        function closeSocialModal() { document.getElementById('social-modal').classList.remove('show'); }
        async function submitSocialLogin() {
            const email = document.getElementById('social-email').value.trim();
            const name = document.getElementById('social-name').value.trim();
            const err = document.getElementById('social-error');
            const suc = document.getElementById('social-success');
            err.textContent = ''; suc.textContent = '';
            if (!email) { err.textContent = 'Please enter your email.'; return; }
            try {
                const res = await fetch('api_social_login.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name, provider: socialProvider })
                });
                const data = await res.json();
                if (data.error && data.error.includes('name')) {
                    document.getElementById('social-name').style.display = 'block';
                    err.textContent = data.error;
                    return;
                }
                if (data.success) {
                    suc.textContent = data.message;
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                } else { err.textContent = data.error || 'Something went wrong.'; }
            } catch (e) { err.textContent = 'Network error. Please try again.'; }
        }
    </script>
</body>

</html>