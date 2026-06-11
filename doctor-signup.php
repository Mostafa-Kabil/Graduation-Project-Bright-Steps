<?php
ob_start();
session_start();
include 'connection.php';
include 'validation.php';

$fname = $lname = $email = $phone = $specialty = $license = $clinic = '';
$fnameErr = $lnameErr = $emailErr = $phoneErr = $passErr = $specialtyErr = $licenseErr = $clinicErr = $termsErr = '';
$certifications = '';
$certificationsErr = $certificateErr = '';
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
        $raw_phone = validate_input($_POST["phone"]);
        $country_code = $_POST["country_code"] ?? '+20';
        
        $isValid = false;
        if ($country_code === '+20' && preg_match('/^1[0-9]{9}$/', $raw_phone)) $isValid = true;
        else if ($country_code === '+1' && preg_match('/^[0-9]{10}$/', $raw_phone)) $isValid = true;
        else if ($country_code === '+44' && preg_match('/^[0-9]{10}$/', $raw_phone)) $isValid = true;
        else if ($country_code === '+966' && preg_match('/^5[0-9]{8}$/', $raw_phone)) $isValid = true;
        else if ($country_code === '+971' && preg_match('/^5[0-9]{8}$/', $raw_phone)) $isValid = true;
        else if ($country_code === 'other' && preg_match('/^[0-9]{8,15}$/', $raw_phone)) $isValid = true;
        
        if (!$isValid) {
            $phoneErr = "Invalid phone number format for selected country";
            $formValid = false;
        } else {
            $phone = ($country_code === 'other' ? '' : $country_code) . $raw_phone;
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

    if (empty($_POST["certifications"])) {
        $certificationsErr = "Certifications are required (e.g. MD, FAAP)";
        $formValid = false;
    } else {
        $certifications = validate_input($_POST["certifications"]);
    }

    // Handle Certificate File Upload
    $certificatePath = null;
    $uniqueFileName = null;
    if (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] === UPLOAD_ERR_NO_FILE) {
        $certificateErr = "Certificate document is required";
        $formValid = false;
    } else {
        $file = $_FILES['certificate'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $certificateErr = "File upload failed";
            $formValid = false;
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($file['tmp_name']);
            
            if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedTypes)) {
                $certificateErr = "Only JPG, PNG, and PDF files are allowed";
                $formValid = false;
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $certificateErr = "File size must be less than 5MB";
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

    if (!isset($_POST['terms'])) {
        $termsErr = "You must agree to Terms and Conditions";
        $formValid = false;
    }

    if ($formValid) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = "doctor";

        // Insert into users table
        $stmt = $connect->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$fname, $lname, $email, $phone, $hashedPassword, $role]);
        $newUserId = $connect->lastInsertId();

        // Try to find or match clinic by name
        $clinicId = null;
        $stmtClinic = $connect->prepare("SELECT clinic_id FROM clinic WHERE clinic_name LIKE ? LIMIT 1");
        $stmtClinic->execute(["%$clinic%"]);
        $clinicRow = $stmtClinic->fetch(PDO::FETCH_ASSOC);
        if ($clinicRow) {
            $clinicId = $clinicRow['clinic_id'];
        } else {
            // Dynamically register the clinic if it doesn't exist yet
            $stmtAdmin = $connect->query("SELECT admin_id FROM admin LIMIT 1");
            $adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
            $adminId = $adminRow ? intval($adminRow['admin_id']) : 16;

            $stmtInsertClinic = $connect->prepare("INSERT INTO clinic (admin_id, clinic_name, location) VALUES (?, ?, 'Unknown Location')");
            $stmtInsertClinic->execute([$adminId, $clinic]);
            $clinicId = $connect->lastInsertId();
        }

        // Always insert into specialist table so admin can see and verify
        // Handle file move first
        $certUploadDir = __DIR__ . '/uploads/certificates/';
        if (!is_dir($certUploadDir)) {
            mkdir($certUploadDir, 0755, true);
        }
        $file = $_FILES['certificate'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uniqueFileName = 'cert_' . $newUserId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $certUploadDir . $uniqueFileName;
        
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $certificatePath = 'uploads/certificates/' . $uniqueFileName;
        }

        // Dynamically alter the specialist table if needed to support certifications and license_number
        try {
            $connect->exec("ALTER TABLE `specialist` ADD COLUMN `certifications` VARCHAR(255) DEFAULT NULL");
        } catch (Exception $e) {}
        try {
            $connect->exec("ALTER TABLE `specialist` ADD COLUMN `license_number` VARCHAR(100) DEFAULT NULL");
        } catch (Exception $e) {}

        $stmt = $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, certifications, license_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$newUserId, $clinicId, $fname, $lname, $specialty, $uniqueFileName, $certifications, $license]);

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

        /* ── Cert Upload exactly like Doctor Settings ── */
        .ds-cert-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .ds-cert-row .form-input {
            flex: 1;
            margin-bottom: 0 !important; /* prevent default margins */
        }
        
        .ds-cert-upload-btn {
            width: 2.75rem;
            height: 2.75rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--green-400, #4ade80);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.06), rgba(6, 182, 212, 0.06));
            color: var(--green-500, #22c55e);
            cursor: pointer;
            transition: all 0.2s ease;
            box-sizing: border-box;
            padding: 0;
        }
        
        .ds-cert-upload-btn:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(6, 182, 212, 0.15));
            border-color: var(--green-500, #22c55e);
            transform: scale(1.05);
        }
        
        .ds-cert-upload-btn svg {
            width: 1.25rem;
            height: 1.25rem;
            stroke: var(--green-500, #22c55e);
        }
        
        .ds-cert-file-info {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            color: var(--green-600, #16a34a);
            margin-top: 0.4rem;
            font-weight: 500;
        }
        
        .ds-cert-file-info svg {
            stroke: var(--green-600, #16a34a);
            flex-shrink: 0;
        }

        /* Dark mode overrides */
        [data-theme="dark"] .ds-cert-upload-btn {
            border-color: var(--green-600, #16a34a);
            background: rgba(34, 197, 94, 0.08);
        }
        [data-theme="dark"] .ds-cert-upload-btn:hover {
            background: rgba(34, 197, 94, 0.15);
        }
        [data-theme="dark"] .ds-cert-file-info {
            color: var(--green-400, #4ade80);
        }
        [data-theme="dark"] .ds-cert-file-info svg {
            stroke: var(--green-400, #4ade80);
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

                <form id="doctor-signup-form" class="auth-form" novalidate method="post" enctype="multipart/form-data"
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

                    <!-- Single Hybrid Certification Field exactly like Doctor Settings -->
                    <div class="form-group">
                        <label class="form-label" for="certifications">Certifications & Certificate <span style="color:#ef4444;">*</span></label>
                        <div class="ds-cert-row">
                            <input type="text" name="certifications" id="certifications"
                                class="form-input <?= !empty($certificationsErr) || !empty($certificateErr) ? 'input-error' : '' ?>" 
                                placeholder="e.g. MD, FAAP, Board Certified"
                                value="<?= htmlspecialchars($certifications) ?>">
                            <button type="button" class="ds-cert-upload-btn" onclick="document.getElementById('certificate').click()" title="Upload Certificate Document">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <input type="file" name="certificate" id="certificate" accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileSelect(this)" style="display:none">
                        </div>
                        <div id="file-preview-name" class="ds-cert-file-info">
                            <?php if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK && !empty($_FILES['certificate']['name'])): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <?= htmlspecialchars($_FILES['certificate']['name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="error" id="upload-error">
                            <?= !empty($certificationsErr) ? $certificationsErr : (!empty($certificateErr) ? $certificateErr : '') ?>
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
                        <div style="display:flex; gap:0.5rem;">
                            <select name="country_code" class="form-input" style="width:110px; margin-bottom:0;">
                                <option value="+20" <?= (isset($_POST['country_code']) && $_POST['country_code'] === '+20') ? 'selected' : '' ?>>EG (+20)</option>
                                <option value="+1" <?= (isset($_POST['country_code']) && $_POST['country_code'] === '+1') ? 'selected' : '' ?>>US (+1)</option>
                                <option value="+44" <?= (isset($_POST['country_code']) && $_POST['country_code'] === '+44') ? 'selected' : '' ?>>UK (+44)</option>
                                <option value="+966" <?= (isset($_POST['country_code']) && $_POST['country_code'] === '+966') ? 'selected' : '' ?>>SA (+966)</option>
                                <option value="+971" <?= (isset($_POST['country_code']) && $_POST['country_code'] === '+971') ? 'selected' : '' ?>>AE (+971)</option>
                                <option value="other" <?= (isset($_POST['country_code']) && $_POST['country_code'] === 'other') ? 'selected' : '' ?>>Other</option>
                            </select>
                            <input type="tel" name="phone" id="phone" placeholder="1001234567"
                                class="form-input <?= !empty($phoneErr) ? 'input-error' : '' ?>" style="flex:1; margin-bottom:0;"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
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
        function handleFileSelect(input) {
            const preview = document.getElementById('file-preview-name');
            const errEl = document.getElementById('upload-error');
            if (errEl) errEl.textContent = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                const allowedExts = ['.jpg', '.jpeg', '.png', '.pdf'];
                const ext = '.' + file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(file.type) && !allowedExts.includes(ext)) {
                    if (errEl) errEl.textContent = 'Invalid file type. Only JPG, PNG, and PDF are allowed.';
                    input.value = '';
                    preview.textContent = '';
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    if (errEl) errEl.textContent = 'File is too large. Maximum size is 5MB.';
                    input.value = '';
                    preview.textContent = '';
                    return;
                }
                
                preview.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> ' + file.name;
            } else {
                preview.textContent = '';
            }
        }

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