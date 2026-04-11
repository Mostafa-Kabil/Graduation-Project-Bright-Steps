<?php
ob_start();
session_start();
include 'connection.php';
include 'validation.php';

$fname = $lname = $email = $phone = $specialty = $license = $clinic = '';
$fnameErr = $lnameErr = $emailErr = $phoneErr = $passErr = $specialtyErr = $licenseErr = $clinicErr = $termsErr = '';
$formValid = true;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (empty($_POST["fname"]) || !validate_username($_POST["fname"])) {
        $fnameErr = "First name must be more than 2 characters";
        $formValid = false;
    } else {
        $fname = validate_input($_POST["fname"]);
    }

    if (empty($_POST["lname"]) || !validate_username($_POST["lname"])) {
        $lnameErr = "Last name must be more than 2 characters";
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

    if (empty($_POST["specialty"])) {
        $specialtyErr = "Please select a specialty";
        $formValid = false;
    } else {
        $specialty = validate_input($_POST["specialty"]);
    }

    if (empty($_POST["license"])) {
        $licenseErr = "License number is required";
        $formValid = false;
    } else {
        $license = validate_input($_POST["license"]);
    }

    if (empty($_POST["clinic"])) {
        $clinicErr = "Clinic name is required";
        $formValid = false;
    } else {
        $clinic = validate_input($_POST["clinic"]);
    }

    if (empty($_POST["password"]) || !validatepassword($_POST["password"])) {
        $passErr = "Password must be at least 8 characters";
        $formValid = false;
    } else {
        $password = $_POST["password"];
    }

    if (!isset($_POST['terms'])) {
        $termsErr = "You must agree to Terms and Conditions";
        $formValid = false;
    }

    if ($formValid) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = "doctor";

        // Insert into users table
        $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fname, $lname, $email, $phone, $hashedPassword, $role]);
        $newUserId = $connect->lastInsertId();

        // Try to find or match clinic by name
        $clinicId = null;
        $stmtClinic = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_name = ? LIMIT 1");
        $stmtClinic->execute([$clinic]);
        $clinicRow = $stmtClinic->fetch(PDO::FETCH_ASSOC);
        if ($clinicRow) {
            $clinicId = $clinicRow['clinic_id'];
        }

        // Insert into specialist table if clinic exists
        if ($clinicId) {
            $stmt = $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$newUserId, $clinicId, $fname, $lname, $specialty, $license]);
        }

        $_SESSION['signup_success'] = true;
        header("Location: doctor-signup.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
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
    </style>
</head>

<body>
    <div class="auth-page auth-split-layout">
        <div class="auth-form-side">
        <button class="back-button" onclick="navigateTo('doctor-login')">
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
                    <h1 class="auth-title">Provider Registration</h1>
                    <p class="auth-subtitle">Join our network of child development specialists</p>
                </div>

                <?php
                if (isset($_SESSION['signup_success'])):
                    unset($_SESSION['signup_success']);
                    ?>
                    <div class="success-message">
                        Registration submitted! Your credentials will be verified within 24-48 hours. Redirecting to
                        login...
                    </div>
                    <script>
                        setTimeout(function () {
                            window.location.href = "doctor-login.php";
                        }, 3000);
                    </script>
                <?php endif; ?>

                <form id="doctor-signup-form" class="auth-form" novalidate method="post"
                    action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first-name">First Name</label>
                            <input type="text" name="fname" id="first-name"
                                class="form-input <?= !empty($fnameErr) ? 'input-error' : '' ?>" placeholder="John"
                                value="<?= htmlspecialchars($fname) ?>">
                            <div class="error">
                                <?= $fnameErr ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last-name">Last Name</label>
                            <input type="text" name="lname" id="last-name"
                                class="form-input <?= !empty($lnameErr) ? 'input-error' : '' ?>" placeholder="Smith"
                                value="<?= htmlspecialchars($lname) ?>">
                            <div class="error">
                                <?= $lnameErr ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="specialty">Specialty</label>
                        <select name="specialty" id="specialty"
                            class="form-input <?= !empty($specialtyErr) ? 'input-error' : '' ?>">
                            <option value="">Select your specialty</option>
                            <option value="pediatrician" <?= $specialty === 'pediatrician' ? 'selected' : '' ?>
                                >Pediatrician</option>
                            <option value="speech-therapist" <?= $specialty === 'speech-therapist' ? 'selected' : '' ?>
                                >Speech Therapist</option>
                            <option value="occupational-therapist" <?= $specialty === 'occupational-therapist' ? 'selected' : '' ?>>Occupational Therapist</option>
                            <option value="developmental-pediatrician" <?= $specialty === 'developmental-pediatrician' ? 'selected' : '' ?>>Developmental Pediatrician</option>
                            <option value="child-psychologist" <?= $specialty === 'child-psychologist' ? 'selected' : '' ?>
                                >Child Psychologist</option>
                            <option value="other" <?= $specialty === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <div class="error">
                            <?= $specialtyErr ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="license">License Number</label>
                        <input type="text" name="license" id="license"
                            class="form-input <?= !empty($licenseErr) ? 'input-error' : '' ?>" placeholder="MD-12345"
                            value="<?= htmlspecialchars($license) ?>">
                        <div class="error">
                            <?= $licenseErr ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clinic">Clinic/Hospital Name</label>
                        <input type="text" name="clinic" id="clinic"
                            class="form-input <?= !empty($clinicErr) ? 'input-error' : '' ?>"
                            placeholder="City Pediatrics Clinic" value="<?= htmlspecialchars($clinic) ?>">
                        <div class="error">
                            <?= $clinicErr ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Professional Email</label>
                        <input type="email" name="email" id="email"
                            class="form-input <?= !empty($emailErr) ? 'input-error' : '' ?>"
                            placeholder="doctor@clinic.com" value="<?= htmlspecialchars($email) ?>">
                        <div class="error">
                            <?= $emailErr ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Professional Phone Number</label>
                        <input type="tel" name="phone" id="phone"
                            class="form-input <?= !empty($phoneErr) ? 'input-error' : '' ?>"
                            placeholder="+1234567890" value="<?= htmlspecialchars($phone) ?>">
                        <div class="error">
                            <?= $phoneErr ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password"
                                class="form-input <?= !empty($passErr) ? 'input-error' : '' ?>" placeholder="••••••••">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" aria-label="Toggle password visibility">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div id="password-strength" class="password-strength"></div>
                        <div class="error">
                            <?= $passErr ?>
                        </div>
                    </div>

                    <div class="form-checkbox-group">
                        <input type="checkbox" id="terms" name="terms" <?= !empty($termsErr) ? 'class="checkbox-error"' : '' ?>>
                        <label for="terms" class="checkbox-label">
                            I agree to the <a href="terms.php" class="auth-link">Terms of Service</a> and
                            <a href="privacy.php" class="auth-link">Privacy Policy</a>. I confirm my medical
                            credentials are valid.
                        </label>
                        <div class="error">
                            <?= $termsErr ?>
                        </div>
                    </div>

                    <!-- Verification Notice -->
                    <div class="verification-notice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <div class="notice-content">
                            <strong>Credential Verification Required</strong>
                            <p>Your account will be reviewed within 24-48 hours. We will verify your medical license
                                before granting full portal access.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient btn-lg btn-full">Create Provider Account</button>
                </form>

                <div class="modern-auth-footer">
                    <p class="auth-footer-text">
                        Already registered?  
                        <a href="#" class="auth-link font-semibold" onclick="navigateTo('doctor-login'); return false;">Sign In</a>
                    </p>
                    <div class="footer-divider"></div>
                    <p class="auth-footer-text" style="color: var(--slate-500, #64748b);">
                        Looking for parent registration? 
                        <a href="#" class="auth-link doctor-link" onclick="navigateTo('signup'); return false;">Parent Portal</a>
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
        <img src="assets/doctor-signup-illustration.png" class="auth-illustration" alt="Bright Steps Growth and Care">
    </div>
</div>

    

    

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
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