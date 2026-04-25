<?php
ob_start();
session_start();
include 'connection.php';

// If already logged in as clinic, redirect to dashboard
if (isset($_SESSION['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'clinic') {
    header("Location: dashboards/clinic/clinic-dashboard.php");
    exit;
}

$errors = [];

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === '') {
        $errors[] = "Email is required.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (count($errors) === 0) {
        $sql = "SELECT * FROM clinic WHERE email = :email LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'suspended') {
                    header("Location: account-suspended.php");
                    exit;
                } elseif ($user['status'] == 'pending') {
                    header("Location: account-pending.php?portal=clinic");
                    exit;
                } else {
                    $_SESSION['id'] = $user['clinic_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['clinic_name'] = $user['clinic_name'];
                    $_SESSION['role'] = 'clinic';

                    header("Location: dashboards/clinic/clinic-dashboard.php");
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
    <title>Clinic Login - Bright Steps</title>
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
    </style>
</head>

<body>
    <div class="auth-page auth-split-layout">
        <div class="auth-form-side">
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
                    <span class="doctor-badge-small" style="background: linear-gradient(135deg, var(--cyan-100, #cffafe), var(--green-100, #dcfce7)); color: var(--cyan-800, #155e75); border-color: var(--cyan-200, #a5f3fc);">Healthcare Clinic</span>
                    <h1 class="auth-title">Clinic Portal</h1>
                    <p class="auth-subtitle">Access your clinic management dashboard</p>
                </div>

                <form id="clinic-login-form" method="POST" class="auth-form" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="email">Clinic Email</label>
                        <input type="email" name="email" id="email" class="form-input <?php
                        foreach ($errors as $error) {
                            if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password.") {
                                echo "input-error";
                                break;
                            }
                        }
                        ?>"
                        placeholder="clinic@example.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password."): ?>
                                <p class="error-message">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label" for="password">Password</label>
                        </div>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" class="form-input <?php
                            foreach ($errors as $error) {
                                if ($error === "Password is required." || $error === "Incorrect email or password.") {
                                    echo "input-error";
                                    break;
                                }
                            }
                            ?>"
                            placeholder="••••••••" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" aria-label="Toggle password visibility">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Password is required." || $error === "Incorrect email or password."): ?>
                                <p class="error-message">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="login" class="btn btn-gradient btn-lg btn-full">Sign In to Dashboard</button>
                </form>

                <div class="modern-auth-footer">
                    <p class="auth-footer-text">
                        New clinic? 
                        <a href="#" class="auth-link font-semibold" onclick="navigateTo('clinic-signup'); return false;">Register</a>
                    </p>
                    <div class="footer-divider"></div>
                    <p class="auth-footer-text" style="color: var(--slate-500, #64748b);">
                        Looking for parent login? 
                        <a href="#" class="auth-link doctor-link" onclick="navigateTo('login'); return false;">Parent Portal</a>
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
        <img src="assets/clinic-login-illustration.png" class="auth-illustration" alt="Bright Steps Clinic Portal">
    </div>
</div>

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script>
        function togglePassword(btn) {
            const wrapper = btn.closest('.password-input-wrapper');
            const input = wrapper.querySelector('input');
            const icon = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            }
        }
    </script>
</body>

</html>
