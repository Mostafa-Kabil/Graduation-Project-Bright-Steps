<?php
session_start();
include "validation.php";
include "connection.php";
$fname = $lname = $email  = '';
$fnameErr =  $lnameErr = $emailErr = $passErr = $confirmpassErr = $termsErr = '';
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
        $role="parent";
        $stmt = $connect->prepare("INSERT INTO users 
            (first_name, last_name, email, password, role) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fname, $lname, $email, $hashedPassword, $role]);

        
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
    unset($_SESSION['signup_success']);
?>
    <div class="success-message">
        Account created successfully! Redirecting to login...
    </div>

    <script>
        setTimeout(function () {
            window.location.href = "login.php";
        }, 3000);
    </script>
<?php endif; ?>
                <form id="signup-form" class="auth-form" novalidate method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="name">First Name</label>
                        <input type="text" name="fname" placeholder="Sarah" class="form-input <?= !empty($fnameErr) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($fname) ?>">
                        <div class="error"><?= $fnameErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name">Last Name</label>
                        <input type="text" name="lname" placeholder="Johnson" class="form-input <?= !empty($lnameErr) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($lname) ?>">
                        <div class="error"><?= $lnameErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" placeholder="parent@example.com" class="form-input <?= !empty($emailErr) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($email) ?>">
                        <div class="error"><?= $emailErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" placeholder="••••••••" class="form-input <?= !empty($passErr) ? 'input-error' : '' ?>">
                        <div class="error"><?= $passErr ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Confirm Password</label>
                        <input type="password" name="confirmpass" placeholder="••••••••" class="form-input <?= !empty($confirmpassErr) ? 'input-error' : '' ?>">
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
    <script src="scripts/language-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <!-- <script src="scripts/auth.js"></script> -->
</body>

</html>