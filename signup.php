<?php
session_start();
include "validation.php";
include "connection.php";
$fname = $lname = $email = $phone = '';
$fnameErr = $lnameErr = $emailErr = $phoneErr = $passErr = $confirmpassErr = $termsErr = '';
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

    if (empty($_POST["phone"])) {
        $phoneErr = "Phone number is required";
        $formValid = false;
    } else {
        $phone = validate_input($_POST["phone"]);
        if (!preg_match("/^[0-9\-\+\s]{8,15}$/", $phone)) {
            $phoneErr = "Invalid phone number format";
            $formValid = false;
        } else {
            $stmt = $connect->prepare("SELECT phone FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->rowCount() > 0) {
                $phoneErr = "Phone number already exists";
                $formValid = false;
            }
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
            (first_name, last_name, email, phone, password, role) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fname, $lname, $email, $phone, $hashedPassword, $role]);
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
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=12">
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
    <div class="auth-page auth-split-layout">
        <div class="auth-form-side">
            <button class="back-button" onclick="navigateTo('index')" style="position: absolute; top: 1.5rem; left: 1.5rem;">
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
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" placeholder="+1234567890"
                            class="form-input <?= !empty($phoneErr) ? 'input-error' : '' ?>"
                            value="<?= htmlspecialchars($phone) ?>">
                        <div class="error"><?= $phoneErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" placeholder="••••••••"
                                class="form-input <?= !empty($passErr) ? 'input-error' : '' ?>">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" aria-label="Toggle password visibility">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div id="password-strength" class="password-strength"></div>
                        <div class="error"><?= $passErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirmpass" placeholder="••••••••"
                                class="form-input <?= !empty($confirmpassErr) ? 'input-error' : '' ?>">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" aria-label="Toggle password visibility">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
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

                <div class="modern-auth-footer">
                    <p class="auth-footer-text">
                        Already have an account? 
                        <a href="#" class="auth-link font-semibold" onclick="navigateTo('login'); return false;">Log in</a>
                    </p>
                    <div class="footer-divider"></div>
                    <p class="auth-footer-text" style="color: var(--slate-500, #64748b);">
                        Are you a healthcare provider? 
                        <a href="doctor-login.php" class="auth-link doctor-link">Doctor Portal</a>
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
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
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
        <img src="assets/signup-illustration.png" class="auth-illustration" alt="Bright Steps Growth and Care">
    </div>
</div>

    

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/password-strength.js?v=8"></script>
    <script>
        function togglePassword(btn) {
            const wrapper = btn.closest('.password-input-wrapper');
            const input = wrapper.querySelector('input');
            const icon = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'; // Eye
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'; // Eye-off
            }
        }
    </script>
</body>

</html>