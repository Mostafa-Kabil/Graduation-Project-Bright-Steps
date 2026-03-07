<?php
require_once "includes/auth_check.php";

$parentId = $_SESSION['id'];
$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name'] ?? '');
    $lname = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fname === '' || $lname === '' || $email === '') {
        $errorMsg = 'All fields are required.';
    } else {
        // Check email uniqueness
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :uid LIMIT 1");
        $stmt->execute(['email' => $email, 'uid' => $parentId]);
        if ($stmt->rowCount() > 0) {
            $errorMsg = 'This email is already in use.';
        } else {
            $stmt = $connect->prepare("UPDATE users SET first_name = :fname, last_name = :lname, email = :email WHERE user_id = :uid");
            $stmt->execute(['fname' => $fname, 'lname' => $lname, 'email' => $email, 'uid' => $parentId]);
            $_SESSION['fname'] = $fname;
            $_SESSION['lname'] = $lname;
            $_SESSION['email'] = $email;
            $successMsg = 'Profile updated successfully!';
        }
    }
}

// Fetch current data
$stmt = $connect->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = :uid");
$stmt->execute(['uid' => $parentId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fname = $user['first_name'] ?? $_SESSION['fname'];
$lname = $user['last_name'] ?? $_SESSION['lname'];
$email = $user['email'] ?? $_SESSION['email'];
$initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));

// Subscription plan
$stmt = $connect->prepare("SELECT s.plan_name FROM parent_subscription ps INNER JOIN subscription s ON ps.subscription_id = s.subscription_id WHERE ps.parent_id = :pid LIMIT 1");
$stmt->execute(['pid' => $parentId]);
$planname = $stmt->fetchColumn() ?: 'Free';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/profile.css">
    <style>
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>
    <div class="dashboard-layout">
        <?php include "includes/sidebar.php"; ?>

        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="profile-header">
                    <button class="back-btn" onclick="navigateTo('settings')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Back to Settings
                    </button>
                    <h1 class="dashboard-title">My Profile</h1>
                </div>

                <div class="profile-content">
                    <div class="profile-picture-section">
                        <div class="profile-picture-large">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                        <button class="btn btn-outline">Change Photo</button>
                    </div>

                    <?php if ($successMsg): ?>
                        <div class="alert-success">
                            <?php echo htmlspecialchars($successMsg); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert-error">
                            <?php echo htmlspecialchars($errorMsg); ?>
                        </div>
                    <?php endif; ?>

                    <form class="profile-form" id="profile-form" method="POST">
                        <div class="form-section">
                            <h3 class="form-section-title">Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="first-name">First Name</label>
                                    <input type="text" id="first-name" name="first_name" class="form-input"
                                        value="<?php echo htmlspecialchars($fname); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="last-name">Last Name</label>
                                    <input type="text" id="last-name" name="last_name" class="form-input"
                                        value="<?php echo htmlspecialchars($lname); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input"
                                    value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline"
                                onclick="navigateTo('settings')">Cancel</button>
                            <button type="submit" class="btn btn-gradient">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

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

    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
</body>

</html>