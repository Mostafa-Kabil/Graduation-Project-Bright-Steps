<?php
session_start();
include "connection.php";
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$parentId = $_SESSION['id'];
$childId = isset($_GET['child_id']) ? (int) $_GET['child_id'] : null;
$successMsg = '';
$errorMsg = '';
$isNew = !$childId;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childId = !empty($_POST['child_id']) ? (int) $_POST['child_id'] : null;
    $cfname = trim($_POST['child_first_name'] ?? '');
    // Auto-populate last_name with father's name (parent's first name from session)
    $clname = trim($_SESSION['fname'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $weight = !empty($_POST['weight']) ? (float) $_POST['weight'] : null;
    $height = !empty($_POST['height']) ? (float) $_POST['height'] : null;
    $headCirc = !empty($_POST['head_circumference']) ? (float) $_POST['head_circumference'] : null;

    if ($cfname === '' || $birthDate === '') {
        $errorMsg = 'First name and date of birth are required.';
    } else {
        $parts = explode('-', $birthDate);
        $birthYear = (int) $parts[0];
        $birthMonth = (int) $parts[1];
        $birthDay = (int) $parts[2];

        try {
            $connect->beginTransaction();
            if ($childId) {
                // Verify ownership
                $stmt = $connect->prepare("SELECT child_id FROM child WHERE child_id = :cid AND parent_id = :pid");
                $stmt->execute(['cid' => $childId, 'pid' => $parentId]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $connect->prepare("UPDATE child SET first_name=:fn, last_name=:ln, gender=:g, birth_day=:bd, birth_month=:bm, birth_year=:by WHERE child_id=:cid AND parent_id=:pid");
                    $stmt->execute(['fn' => $cfname, 'ln' => $clname, 'g' => $gender, 'bd' => $birthDay, 'bm' => $birthMonth, 'by' => $birthYear, 'cid' => $childId, 'pid' => $parentId]);
                }
            } else {
                $ssn = 'BS-' . strtoupper(bin2hex(random_bytes(5)));
                $stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES (:ssn,:pid,:fn,:ln,:bd,:bm,:by,:g)");
                $stmt->execute(['ssn' => $ssn, 'pid' => $parentId, 'fn' => $cfname, 'ln' => $clname, 'bd' => $birthDay, 'bm' => $birthMonth, 'by' => $birthYear, 'g' => $gender]);
                $childId = (int) $connect->lastInsertId();
                $stmt = $connect->prepare("INSERT INTO points_wallet (child_id, total_points) VALUES (:cid, 0)");
                $stmt->execute(['cid' => $childId]);
            }
            if ($weight !== null || $height !== null || $headCirc !== null) {
                $stmt = $connect->prepare("INSERT INTO growth_record (child_id, height, weight, head_circumference) VALUES (:cid,:h,:w,:hc)");
                $stmt->execute(['cid' => $childId, 'h' => $height, 'w' => $weight, 'hc' => $headCirc]);

                // Gamification Points for Growth
                $pointsToAward = 25;
                $stmt = $connect->prepare("UPDATE points_wallet SET total_points = total_points + ? WHERE child_id = ?");
                $stmt->execute([$pointsToAward, $childId]);

                // Log Transaction
                $stmt = $connect->prepare("SELECT admin_id FROM admin LIMIT 1");
                $stmt->execute();
                $adminId = $stmt->fetchColumn();

                if ($adminId) {
                    $stmt = $connect->prepare("SELECT refrence_id FROM points_refrence WHERE action_name = 'Growth Update' LIMIT 1");
                    $stmt->execute();
                    $refId = $stmt->fetchColumn();

                    if (!$refId) {
                        $stmt = $connect->prepare("INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign) VALUES (?, 'Growth Update', ?, '+')");
                        $stmt->execute([$adminId, $pointsToAward]);
                        $refId = $connect->lastInsertId();
                    }

                    $stmt = $connect->prepare("SELECT wallet_id FROM points_wallet WHERE child_id = ?");
                    $stmt->execute([$childId]);
                    $walletId = $stmt->fetchColumn();

                    if ($walletId) {
                        $stmt = $connect->prepare("INSERT INTO points_transaction (refrence_id, wallet_id, points_change, transaction_type) VALUES (?, ?, ?, 'deposit')");
                        $stmt->execute([$refId, $walletId, $pointsToAward]);
                    }
                }

                $successMsg = "Child profile saved! +$pointsToAward Points earned for logging growth.";
            } else {
                $successMsg = 'Child profile saved successfully!';
            }
            $connect->commit();
            $isNew = false;
        } catch (Exception $e) {
            $connect->rollBack();
            $errorMsg = 'Error saving: ' . $e->getMessage();
        }
    }
}

// Fetch child data if editing
$childData = null;
$growthData = null;
if ($childId) {
    $stmt = $connect->prepare("SELECT * FROM child WHERE child_id = :cid AND parent_id = :pid");
    $stmt->execute(['cid' => $childId, 'pid' => $parentId]);
    $childData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($childData) {
        $stmt2 = $connect->prepare("SELECT * FROM growth_record WHERE child_id = :cid ORDER BY recorded_at DESC LIMIT 1");
        $stmt2->execute(['cid' => $childId]);
        $growthData = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}

$cfname = $childData['first_name'] ?? '';
$clname = $childData['last_name'] ?? '';
$gender = $childData['gender'] ?? '';
$birthDateVal = $childData ? sprintf('%04d-%02d-%02d', $childData['birth_year'], $childData['birth_month'], $childData['birth_day']) : '';
$weightVal = $growthData['weight'] ?? '';
$heightVal = $growthData['height'] ?? '';
$headVal = $growthData['head_circumference'] ?? '';
$initials = strtoupper(substr($_SESSION['fname'], 0, 1) . substr($_SESSION['lname'], 0, 1));

// Subscription
$stmt = $connect->prepare("SELECT s.plan_name FROM parent_subscription ps INNER JOIN subscription s ON ps.subscription_id = s.subscription_id WHERE ps.parent_id = :pid LIMIT 1");
$stmt->execute(['pid' => $parentId]);
$planname = $stmt->fetchColumn() ?: 'Free';

$childInitial = $cfname ? strtoupper($cfname[0]) : '?';
$ageDisplay = '';
if ($childData) {
    $bd = mktime(0, 0, 0, $childData['birth_month'], $childData['birth_day'], $childData['birth_year']);
    $ageM = floor((time() - $bd) / (30.44 * 86400));
    $ageDisplay = $ageM >= 24 ? floor($ageM / 12) . ' years old' : $ageM . ' months old';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $isNew ? 'Add Child' : 'Edit Child'; ?> - Bright Steps
    </title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/dashboard.css?v=8">
    <link rel="stylesheet" href="styles/settings.css?v=8">
    <link rel="stylesheet" href="styles/profile.css?v=8">
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
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo"><img src="assets/logo.png" alt="Bright Steps"
                        style="height:2.5rem;width:auto;"></a>
                <div class="user-profile" onclick="navigateTo('profile')" style="cursor:pointer;">
                    <div class="user-avatar">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name">
                            <?php echo htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?>
                        </div>
                        <div class="user-badge-text">
                            <?php echo htmlspecialchars($planname); ?> Member
                        </div>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item" onclick="navigateTo('dashboard')"><svg class="nav-icon" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg><span>Home</span></button>
            </nav>
            <div class="sidebar-footer">
                <button class="nav-item" onclick="navigateTo('settings')"><svg class="nav-icon" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0" />
                    </svg><span>Settings</span></button>
                <button class="nav-item nav-item-logout" onclick="window.location.href='logout.php'"><svg
                        class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg><span>Log Out</span></button>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="profile-header">
                    <button class="back-btn" onclick="navigateTo('settings')"><svg viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>Back to Settings</button>
                    <h1 class="dashboard-title">
                        <?php echo $isNew ? 'Add Child' : 'Child Profile'; ?>
                    </h1>
                </div>

                <div class="profile-content">
                    <?php if (!$isNew): ?>
                        <div class="profile-picture-section child-section">
                            <div class="profile-picture-large child-avatar-large">
                                <?php echo htmlspecialchars($childInitial); ?>
                            </div>
                            <div class="child-header-info">
                                <h2>
                                    <?php echo htmlspecialchars($cfname . ' ' . $clname); ?>
                                </h2>
                                <p>
                                    <?php echo htmlspecialchars($ageDisplay); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

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

                    <form class="profile-form" id="child-profile-form" method="POST">
                        <input type="hidden" name="child_id" value="<?php echo $childId ?: ''; ?>">
                        <div class="form-section">
                            <h3 class="form-section-title">Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="child-first-name">Child's First Name</label>
                                    <input type="text" id="child-first-name" name="child_first_name" class="form-input"
                                        value="<?php echo htmlspecialchars($cfname); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="child-last-name">Father's Name (Auto-filled)</label>
                                    <input type="text" id="child-last-name" name="child_last_name" class="form-input"
                                        value="<?php echo htmlspecialchars($clname); ?>" readonly
                                        style="background-color:var(--slate-100);cursor:not-allowed;">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="birth-date">Date of Birth</label>
                                    <input type="date" id="birth-date" name="birth_date" class="form-input"
                                        value="<?php echo htmlspecialchars($birthDateVal); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="gender">Gender</label>
                                    <select id="gender" name="gender" class="form-input">
                                        <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>
                                            Female
                                        </option>
                                        <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male
                                        </option>
                                        <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">
                                Growth Measurements
                                <span
                                    style="font-size:0.8rem;background:rgba(245,158,11,0.1);color:#fbbf24;padding:0.2rem 0.6rem;border-radius:12px;margin-left:0.5rem;vertical-align:middle;">
                                    Earn +25 Points
                                </span>
                            </h3>
                            <div class="form-grid form-grid-3">
                                <div class="form-group">
                                    <label class="form-label" for="weight">Weight (kg)</label>
                                    <input type="number" step="0.1" id="weight" name="weight" class="form-input"
                                        value="<?php echo htmlspecialchars($weightVal); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="height">Height (cm)</label>
                                    <input type="number" step="0.1" id="height" name="height" class="form-input"
                                        value="<?php echo htmlspecialchars($heightVal); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="head">Head Circumference (cm)</label>
                                    <input type="number" step="0.1" id="head" name="head_circumference"
                                        class="form-input" value="<?php echo htmlspecialchars($headVal); ?>">
                                </div>
                            </div>
                            <?php if ($growthData): ?>
                                <p class="form-hint">Last updated:
                                    <?php echo date('F d, Y', strtotime($growthData['recorded_at'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-outline"
                                onclick="navigateTo('settings')">Cancel</button>
                            <button type="submit" class="btn btn-gradient">
                                <?php echo $isNew ? 'Add Child' : 'Save Changes'; ?>
                            </button>
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

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script>
        // Auto-populate father's name for new child
        document.addEventListener('DOMContentLoaded', function() {
            const fatherNameField = document.getElementById('child-last-name');
            if (fatherNameField && !fatherNameField.value) {
                fatherNameField.value = '<?php echo htmlspecialchars($_SESSION['fname'] ?? ''); ?>';
            }
        });
    </script>
</body>

</html>