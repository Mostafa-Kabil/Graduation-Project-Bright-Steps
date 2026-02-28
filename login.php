<?php
ob_start();
session_start();
include 'connection.php';

$errors = [];

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // basic validation
    if ($email === '') {
        $errors[] = "Email is required.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (count($errors) === 0) {
        $sql  = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['role'] === 'parent') {
                    // credentials are correct – set session and redirect
                    $_SESSION['id']    = $user['user_id'];
                    $_SESSION['fname'] = $user['first_name'];
                    $_SESSION['lname'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role']  = $user['role'];

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $errors[] = "This portal is for parents only.";
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

        <input 
            type="email" 
            name="email" 
            id="email" 
            class="form-input 
            <?php 
                foreach ($errors as $error) {
                    if ($error === "Email is required." || $error === "Email not found." || $error === "Incorrect email or password." || $error === "This portal is for parents only.") {
                        echo "input-error";
                        break;
                    }
                }
            ?>" 
            placeholder="parent@example.com" 
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
            <a href="#" class="form-link">Forgot?</a>
        </div>

        <input 
            type="password" 
            name="password" 
            id="password" 
            class="form-input 
            <?php 
                foreach ($errors as $error) {
                    if ($error === "Password is required." || $error === "Incorrect email or password.") {
                        echo "input-error";
                        break;
                    }
                }
            ?>" 
            placeholder="••••••••" 
            required>

        <?php foreach ($errors as $error): ?>
            <?php if ($error === "Password is required." || $error === "Incorrect email or password."): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php break; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <button type="submit" name="login" class="btn btn-gradient btn-lg btn-full">Log In</button>
</form>

                <div class="auth-footer">
                    <span class="auth-footer-text">Don't have an account? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('signup'); return false;">Sign up</a>
                </div>

                <div class="auth-divider">
                    <span>Or continue with</span>
                </div>

                <div class="social-buttons">
                    <button class="btn btn-outline btn-social">
                        <svg class="social-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="currentColor"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="currentColor"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="currentColor"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Google
                    </button>
                    <button class="btn btn-outline btn-social">
                        <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Facebook
                    </button>
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

    <script src="scripts/language-toggle.js"></script>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <!-- <script src="scripts/auth.js"></script> -->
</body>

</html>