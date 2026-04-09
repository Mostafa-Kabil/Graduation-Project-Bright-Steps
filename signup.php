<?php
session_start();
include "validation.php";
include "connection.php";
$fname = $lname = $email = '';
$fnameErr = $lnameErr = $emailErr = $passErr = $confirmpassErr = $termsErr = '';
$formValid = true;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (empty($_POST["fname"]) || !validate_username($_POST["fname"])) {
        $fnameErr = "firstname must be more than 2 characters";
        $formValid = false;
    } else {
        $fname = validate_input($_POST["fname"]);
    }

    if (empty($_POST["lname"]) || !validate_username($_POST["lname"])) {
        $lnameErr = "lastname must be more than 2 characters";
        $formValid = false;
    } else {
        $lname = validate_input($_POST["lname"]);
    }

    if (empty($_POST["email"]) || !validate_email1($_POST["email"])) {
        $emailErr = "Invalid email format";
        $formValid = false;
    } else {
        $email = validate_input($_POST["email"]);
        $stmt = $connect->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $emailErr = "Email already exists";
            $formValid = false;
        }
    }

    if (empty($_POST["password"]) || !validatepassword($_POST["password"])) {
        $passErr = "Password must be at least 8 characters";
        $formValid = false;
    } else {
        $password = $_POST["password"];
    }

    if (empty($_POST["confirmpass"]) || !validateconfirmpassword($_POST["password"], $_POST["confirmpass"])) {
        $confirmpassErr = "Passwords do not match";
        $formValid = false;
    }
    if (!isset($_POST['terms'])) {
        $termsErr = "You must agree to Terms and Conditions";
        $formValid = false;
    }

    if ($formValid) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = "parent";
        $stmt = $connect->prepare("INSERT INTO users 
            (first_name, last_name, email, password, role) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fname, $lname, $email, $hashedPassword, $role]);
        $newUserId = $connect->lastInsertId();
        $no = 0;
        $stmt = $connect->prepare("INSERT INTO parent 
            (parent_id, number_of_children) 
            VALUES (?, ?)");
        $stmt->execute([$newUserId, $no]);

        $_SESSION['id'] = $newUserId;
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['signup_success'] = true;
        header("Location: signup.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/auth.css">
    <style>
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        .input-error {
            border: 2px solid red !important;
        }

        .checkbox-error {
            outline: 2px solid red;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }

        /* Verification Modal */
        .verify-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .verify-card {
            background: var(--white, #fff);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .verify-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, #6C63FF, #a78bfa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .verify-icon svg {
            width: 2rem;
            height: 2rem;
            color: white;
        }

        .verify-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--slate-900, #1e293b);
        }

        .verify-card p {
            color: var(--slate-500, #64748b);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .code-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .code-inputs input {
            width: 3rem;
            height: 3.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid var(--slate-200, #e2e8f0);
            border-radius: 12px;
            outline: none;
            transition: border-color 0.2s;
        }

        .code-inputs input:focus {
            border-color: #6C63FF;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
        }

        .verify-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #6C63FF, #a78bfa);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .verify-btn:hover {
            opacity: 0.9;
        }

        .verify-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .verify-error {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 0.75rem;
        }

        .verify-resend {
            color: var(--slate-500, #64748b);
            font-size: 0.85rem;
            margin-top: 1rem;
        }

        .verify-resend a {
            color: #6C63FF;
            cursor: pointer;
            text-decoration: underline;
        }

        .verify-dev-code {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            margin-top: 0.75rem;
        }
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
                    <h1 class="auth-title">Start your journey</h1>
                    <p class="auth-subtitle">Create your free account today</p>
                </div>
                <?php
                if (isset($_SESSION['signup_success'])):
                    $verifyEmail = $_SESSION['email'] ?? '';
                    unset($_SESSION['signup_success']);
                    ?>
                    <div class="success-message">Account created! Please verify your email.</div>
                    <!-- Email Verification Modal -->
                    <div class="verify-overlay" id="verify-modal">
                        <div class="verify-card">
                            <div class="verify-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                    <polyline points="22,6 12,13 2,6" />
                                </svg>
                            </div>
                            <h2>Verify Your Email</h2>
                            <p>We sent a 6-digit code to <strong><?php echo htmlspecialchars($verifyEmail); ?></strong></p>
                            <div class="code-inputs" id="code-inputs">
                                <input type="text" maxlength="1" inputmode="numeric" autofocus>
                                <input type="text" maxlength="1" inputmode="numeric">
                                <input type="text" maxlength="1" inputmode="numeric">
                                <input type="text" maxlength="1" inputmode="numeric">
                                <input type="text" maxlength="1" inputmode="numeric">
                                <input type="text" maxlength="1" inputmode="numeric">
                            </div>
                            <button class="verify-btn" id="verify-btn" onclick="verifyCode()">Verify Email</button>
                            <div class="verify-error" id="verify-error"></div>
                            <div class="verify-resend">Didn't receive it? <a onclick="sendCode()">Resend Code</a></div>
                            <div class="verify-dev-code" id="dev-code"></div>
                        </div>
                    </div>
                    <script>
                        const VERIFY_EMAIL = '<?php echo addslashes($verifyEmail); ?>';
                        // Auto-send verification code on load
                        sendCode();
                        // Code input auto-advance
                        document.querySelectorAll('.code-inputs input').forEach((inp, i, all) => {
                            inp.addEventListener('input', () => { if (inp.value && i < all.length - 1) all[i + 1].focus(); });
                            inp.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !inp.value && i > 0) all[i - 1].focus(); });
                        });
                        async function sendCode() {
                            try {
                                const res = await fetch('api_email_verify.php?action=send', {
                                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ email: VERIFY_EMAIL })
                                });
                                const data = await res.json();
                                if (data.dev_code) document.getElementById('dev-code').textContent = '🔧 Dev code: ' + data.dev_code;
                            } catch (e) { console.error(e); }
                        }
                        async function verifyCode() {
                            const inputs = document.querySelectorAll('.code-inputs input');
                            const code = Array.from(inputs).map(i => i.value).join('');
                            if (code.length < 6) { document.getElementById('verify-error').textContent = 'Please enter all 6 digits'; return; }
                            document.getElementById('verify-btn').disabled = true;
                            try {
                                const res = await fetch('api_email_verify.php?action=verify', {
                                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ email: VERIFY_EMAIL, code: code })
                                });
                                const data = await res.json();
                                if (data.success) { window.location.href = 'onboarding.php'; }
                                else { document.getElementById('verify-error').textContent = data.error || 'Invalid code'; }
                            } catch (e) { document.getElementById('verify-error').textContent = 'Error verifying'; }
                            document.getElementById('verify-btn').disabled = false;
                        }
                    </script>
                <?php endif; ?>
                <form id="signup-form" class="auth-form" novalidate method="post"
                    action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="name">First Name</label>
                        <input type="text" name="fname" placeholder="Sarah"
                            class="form-input <?= !empty($fnameErr) ? 'input-error' : '' ?>"
                            value="<?= htmlspecialchars($fname) ?>">
                        <div class="error"><?= $fnameErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name">Last Name</label>
                        <input type="text" name="lname" placeholder="Johnson"
                            class="form-input <?= !empty($lnameErr) ? 'input-error' : '' ?>"
                            value="<?= htmlspecialchars($lname) ?>">
                        <div class="error"><?= $lnameErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" placeholder="parent@example.com"
                            class="form-input <?= !empty($emailErr) ? 'input-error' : '' ?>"
                            value="<?= htmlspecialchars($email) ?>">
                        <div class="error"><?= $emailErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••"
                            class="form-input <?= !empty($passErr) ? 'input-error' : '' ?>">
                        <div id="password-strength" class="password-strength"></div>
                        <div class="error"><?= $passErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Confirm Password</label>
                        <input type="password" name="confirmpass" placeholder="••••••••"
                            class="form-input <?= !empty($confirmpassErr) ? 'input-error' : '' ?>">
                        <div class="error"><?= $confirmpassErr ?></div>
                    </div>

                    <div class="form-checkbox-group">
                        <input type="checkbox" id="terms" name="terms" <?= !empty($termsErr) ? 'class="checkbox-error"' : '' ?>>
                        <label for="terms" class="checkbox-label">
                            I agree to the <a href="terms.php" class="auth-link">Terms of Service</a> and <a
                                href="privacy.php" class="auth-link">Privacy Policy</a>
                        </label>
                        <div class="error"><?= $termsErr ?></div>
                    </div>

                    <button type="submit" class="btn btn-gradient btn-lg btn-full">Create Account</button>
                </form>

                <div class="auth-footer">
                    <span class="auth-footer-text">Already have an account? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('login'); return false;">Log in</a>
                </div>

                <div class="auth-divider">
                    <span>Or sign up with</span>
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
                <div id="social-modal"
                    style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:1001;display:none;align-items:center;justify-content:center;">
                    <div
                        style="background:var(--white,#fff);border-radius:20px;padding:2.5rem;max-width:400px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.25);position:relative;">
                        <button onclick="closeSocialModal()"
                            style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--slate-400);">&times;</button>
                        <div id="social-icon-area"
                            style="width:3.5rem;height:3.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        </div>
                        <h2 id="social-title" style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">Continue
                            with Google</h2>
                        <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem;">Enter your email to sign up or
                            sign in</p>
                        <input type="email" id="social-email" placeholder="your.email@gmail.com"
                            style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                        <input type="text" id="social-name" placeholder="Full Name"
                            style="width:100%;padding:0.875rem;border:2px solid #e2e8f0;border-radius:12px;font-size:1rem;outline:none;margin-bottom:0.75rem;box-sizing:border-box;">
                        <button id="social-btn" onclick="submitSocialLogin()"
                            style="width:100%;padding:0.875rem;background:#4285F4;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;">Continue</button>
                        <div id="social-error" style="color:#ef4444;font-size:0.85rem;margin-top:0.5rem;"></div>
                        <div id="social-success" style="color:#22c55e;font-size:0.85rem;margin-top:0.5rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>

    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/language-toggle.js?v=5"></script>
    <script src="scripts/navigation.js"></script>
    <script src="scripts/password-strength.js"></script>
    <script>
        let socialProvider = 'google';
        function openSocialLogin(provider) {
            socialProvider = provider;
            const modal = document.getElementById('social-modal');
            modal.style.display = 'flex';
            document.getElementById('social-error').textContent = '';
            document.getElementById('social-success').textContent = '';
            document.getElementById('social-email').value = '';
            const icon = document.getElementById('social-icon-area');
            const title = document.getElementById('social-title');
            const btn = document.getElementById('social-btn');
            if (provider === 'google') {
                icon.style.background = '#fff';
                icon.innerHTML = '<svg viewBox="0 0 24 24" width="28" height="28"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
                title.textContent = 'Continue with Google';
                btn.style.background = '#4285F4';
            } else {
                icon.style.background = '#1877F2';
                icon.innerHTML = '<svg viewBox="0 0 24 24" width="28" height="28"><path fill="white" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
                title.textContent = 'Continue with Facebook';
                btn.style.background = '#1877F2';
            }
        }
        function closeSocialModal() { document.getElementById('social-modal').style.display = 'none'; }
        async function submitSocialLogin() {
            const email = document.getElementById('social-email').value.trim();
            const name = document.getElementById('social-name').value.trim();
            const err = document.getElementById('social-error');
            const suc = document.getElementById('social-success');
            err.textContent = ''; suc.textContent = '';
            if (!email) { err.textContent = 'Please enter your email.'; return; }
            if (!name) { err.textContent = 'Please enter your full name.'; return; }
            try {
                const res = await fetch('api_social_login.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name, provider: socialProvider })
                });
                const data = await res.json();
                if (data.success) {
                    suc.textContent = data.message;
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                } else { err.textContent = data.error || 'Something went wrong.'; }
            } catch (e) { err.textContent = 'Network error. Please try again.'; }
        }
    </script>
</body>

</html>