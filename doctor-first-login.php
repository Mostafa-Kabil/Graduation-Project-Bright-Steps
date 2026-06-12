<?php
ob_start();
session_start();
require_once 'connection.php';

// Auth: only logged in specialists with first login requirement can access
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
    header('Location: doctor-login.php');
    exit;
}
if (!isset($_SESSION['is_first_login']) || $_SESSION['is_first_login'] !== 1) {
    header('Location: doctor-dashboard.php');
    exit;
}

$user_id = intval($_SESSION['id']);
$errors = [];
$success = false;

// Pre-fill current email
$current_email = $_SESSION['email'] ?? '';

if (isset($_POST['update_credentials'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (count($errors) === 0) {
        try {
            // Check if email already in use
            $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "This email address is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $connect->prepare("UPDATE users SET email = ?, password = ?, is_first_login = 0 WHERE user_id = ?");
                $stmt->execute([$email, $hashed_password, $user_id]);

                // Update session variables
                $_SESSION['email'] = $email;
                $_SESSION['is_first_login'] = 0;
                
                // Redirect directly to doctor onboarding or dashboard
                header("Location: doctor-onboarding.php");
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup - Bright Steps</title>
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
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header" style="margin-bottom: 1.5rem;">
                        <a href="index.php" class="auth-logo">
                            <img src="assets/logo.png" alt="Bright Steps">
                        </a>
                        <span class="doctor-badge-small" style="background: linear-gradient(135deg, #0d9488, #2dd4bf); color: #fff;">Account Setup</span>
                    </div>

                    <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">Configure Your Credentials</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.5;">
                        Welcome to Bright Steps! As this is your first login, please verify your email address and set a secure password to activate your account.
                    </p>

                    <?php if (count($errors) > 0): ?>
                        <div class="error-summary" style="background: rgba(229, 57, 53, 0.08); border-left: 4px solid #e53935; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <ul style="margin: 0; padding-left: 1.2rem; color: #e53935; font-size: 0.9rem; line-height: 1.5;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="doctor-first-login.php">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label class="form-label" style="font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Email Address</label>
                            <input type="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($email ?? $current_email); ?>" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1.5px solid var(--border-color); font-size: 0.95rem;">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label class="form-label" style="font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">New Password</label>
                            <input type="password" name="password" class="form-input" required placeholder="At least 8 characters" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1.5px solid var(--border-color); font-size: 0.95rem;">
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label class="form-label" style="font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-input" required placeholder="Repeat your new password" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1.5px solid var(--border-color); font-size: 0.95rem;">
                        </div>

                        <button type="submit" name="update_credentials" class="btn-gradient" style="width: 100%; padding: 0.85rem; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; border: none; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: white; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3); transition: transform 0.2s, box-shadow 0.2s;">
                            Activate Account & Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="auth-visual-side" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.8), rgba(45, 212, 191, 0.8)), url('assets/visual.jpg') center/cover no-repeat;">
            <div class="visual-content">
                <h2>Empower Pediatric Care</h2>
                <p>Bright Steps connects clinics and specialists to deliver streamlined therapies and developmental support.</p>
            </div>
        </div>
    </div>
    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
</body>
</html>
