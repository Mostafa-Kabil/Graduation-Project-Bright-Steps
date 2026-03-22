<?php
ob_start();
session_start();
include 'connection.php';

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
        // Check in users table for doctor role
        $sql = "SELECT u.*, s.specialist_id, s.specialization, s.clinic_id
                 FROM users u
                 LEFT JOIN specialist s ON u.user_id = s.specialist_id
                 WHERE u.email = :email LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['role'] === 'doctor' || $user['role'] === 'specialist') {
                    $_SESSION['id'] = $user['user_id'];
                    $_SESSION['fname'] = $user['first_name'];
                    $_SESSION['lname'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['specialist_id'] = $user['specialist_id'] ?? null;
                    $_SESSION['specialization'] = $user['specialization'] ?? '';
                    $_SESSION['clinic_id'] = $user['clinic_id'] ?? null;

                    header("Location: dashboards/doctor/doctor-dashboard.php");
                    exit;
                } else {
                    $errors[] = "This portal is for healthcare providers only.";
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
    <title>Doctor Login - Bright Steps</title>
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
                    <span class="doctor-badge-small">Healthcare Provider</span>
                    <h1 class="auth-title">Doctor Portal</h1>
                    <p class="auth-subtitle">Access patient development reports and recommendations</p>
                </div>

                <form id="doctor-login-form" method="POST" class="auth-form" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="email">Professional Email</label>
                        <input type="email" name="email" id="email" class="form-input <?php
                        foreach ($errors as $error) {
                            if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password." || $error === "This portal is for healthcare providers only.") {
                                echo "input-error";
                                break;
                            }
                        }
                        ?>"
                        placeholder="doctor@clinic.com"
                        value="
                        <?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password." || $error === "This portal is for healthcare providers only."): ?>
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
                            <a href="#" class="form-link">Forgot?</a>
                        </div>
                        <input type="password" name="password" id="password" class="form-input <?php
                        foreach ($errors as $error) {
                            if ($error === "Password is required." || $error === "Incorrect email or password.") {
                                echo "input-error";
                                break;
                            }
                        }
                        ?>"
                        placeholder="••••••••" required>

                        <?php foreach ($errors as $error): ?>
                            <?php if ($error === "Password is required." || $error === "Incorrect email or password."): ?>
                                <p class="error-message">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="login" class="btn btn-gradient btn-lg btn-full">Sign In to
                        Portal</button>
                </form>

                <div class="auth-footer">
                    <span class="auth-footer-text">Need a provider account? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('doctor-signup'); return false;">Register</a>
                </div>

                <div class="auth-divider">
                    <span>Or</span>
                </div>

                <div class="parent-login-link">
                    <span>Looking for parent login? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('login'); return false;">Parent Portal</a>
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

    <script src="scripts/language-toggle.js?v=5"></script>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
</body>

</html>