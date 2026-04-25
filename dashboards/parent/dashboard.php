<?php
session_start();
include "../../connection.php";
if (!isset($_SESSION['email'])) {
    header("Location: ../../login.php");
    exit();
}

$parentId = $_SESSION['id'] ?? null;
$planname = 'Free';
$dashboardData = [
    'parent' => [
        'id' => $parentId,
        'fname' => $_SESSION['fname'],
        'lname' => $_SESSION['lname'],
        'email' => $_SESSION['email']
    ],
    'subscription' => ['plan_name' => 'Free', 'price' => '0.00', 'plan_period' => ''],
    'children' => [],
    'appointments' => [],
    'streaks' => [],
    'user_settings' => null
];

if ($parentId) {
    // Subscription
    try {
        $sql = "SELECT s.plan_name, s.price, s.plan_period
                FROM parent_subscription ps
                INNER JOIN subscription s ON ps.subscription_id = s.subscription_id
                WHERE ps.parent_id = :parent_id LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['parent_id' => $parentId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($plan) {
            $planname = $plan['plan_name'];
            $dashboardData['subscription'] = $plan;
        }
    } catch (Exception $e) { /* subscription query failed gracefully */ }

    // Children
    $children = [];
    try {
        $sql = "SELECT child_id, first_name, last_name, birth_day, birth_month, birth_year, gender, ssn
                FROM child WHERE parent_id = :parent_id ORDER BY child_id ASC";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['parent_id' => $parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($children as &$ch) {
            $bd = mktime(0, 0, 0, $ch['birth_month'], $ch['birth_day'], $ch['birth_year']);
            $ageM = floor((time() - $bd) / (30.44 * 86400));
            $ch['age_months'] = (int) $ageM;
            $ch['age_display'] = $ageM >= 24 ? floor($ageM / 12) . ' years old' : $ageM . ' months old';
            $ch['birth_date_formatted'] = date('M d, Y', $bd);

            // Latest growth
            try {
                $s2 = $connect->prepare("SELECT height, weight, head_circumference, recorded_at FROM growth_record WHERE child_id = :cid ORDER BY recorded_at DESC LIMIT 1");
                $s2->execute(['cid' => $ch['child_id']]);
                $ch['growth'] = $s2->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Exception $e) { $ch['growth'] = null; }

            // All growth history
            try {
                $s3 = $connect->prepare("SELECT height, weight, head_circumference, recorded_at FROM growth_record WHERE child_id = :cid ORDER BY recorded_at ASC");
                $s3->execute(['cid' => $ch['child_id']]);
                $ch['growth_history'] = $s3->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $ch['growth_history'] = []; }

            // Badge count
            try {
                $s4 = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = :cid");
                $s4->execute(['cid' => $ch['child_id']]);
                $ch['badge_count'] = (int) $s4->fetchColumn();
            } catch (Exception $e) { $ch['badge_count'] = 0; }

            // Points
            try {
                $s5 = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = :cid LIMIT 1");
                $s5->execute(['cid' => $ch['child_id']]);
                $pts = $s5->fetchColumn();
                $ch['total_points'] = $pts !== false ? (int) $pts : 0;
            } catch (Exception $e) { $ch['total_points'] = 0; }

            // Latest speech analysis
            try {
                $s6 = $connect->prepare("
                    SELECT sa.vocabulary_score, sa.clarify_score, sa.transcript, vs.feedback, vs.sent_at
                    FROM speech_analysis sa
                    INNER JOIN voice_sample vs ON sa.sample_id = vs.sample_id
                    WHERE vs.child_id = :cid
                    ORDER BY vs.sent_at DESC LIMIT 1
                ");
                $s6->execute(['cid' => $ch['child_id']]);
                $speechData = $s6->fetch(PDO::FETCH_ASSOC);
                if ($speechData) {
                    $ch['_speech'] = $speechData;
                } else {
                    $ch['_speech'] = null;
                }
            } catch (Exception $e) { $ch['_speech'] = null; }

            // Motor milestones completion percentage
            try {
                $s7 = $connect->prepare("
                    SELECT COUNT(*) FROM milestones WHERE category IN ('gross_motor','fine_motor')
                ");
                $s7->execute();
                $motorTotal = (int)$s7->fetchColumn();

                $s8 = $connect->prepare("
                    SELECT COUNT(*) FROM child_milestones cm
                    JOIN milestones m ON cm.milestone_id = m.milestone_id
                    WHERE cm.child_id = :cid AND m.category IN ('gross_motor','fine_motor') AND cm.is_achieved = 1
                ");
                $s8->execute(['cid' => $ch['child_id']]);
                $motorDone = (int)$s8->fetchColumn();

                $ch['_motorPct'] = $motorTotal > 0 ? round(($motorDone / $motorTotal) * 100) : 0;
            } catch (Exception $e) { $ch['_motorPct'] = 0; }
        }
        unset($ch);
    } catch (Exception $e) { $children = []; }
    $dashboardData['children'] = $children;

    // Appointments
    try {
        $sql = "SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.report, a.comment,
                       s.first_name AS doc_fname, s.last_name AS doc_lname, s.specialization,
                       c.clinic_name, c.location AS clinic_location
                FROM appointment a
                INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                INNER JOIN clinic c ON s.clinic_id = c.clinic_id
                WHERE a.parent_id = :parent_id AND a.scheduled_at >= NOW()
                ORDER BY a.scheduled_at ASC LIMIT 10";
        $stmt = $connect->prepare($sql);
        $stmt->execute(['parent_id' => $parentId]);
        $dashboardData['appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $dashboardData['appointments'] = []; }

    // Streaks and badges for the currently selected child (frontend will switch)
    if (!empty($children)) {
        // Load streaks and badges for ALL children, keyed by child_id
        $allStreaks = [];
        $allBadges = [];
        foreach ($children as $ch) {
            $cid = $ch['child_id'];
            try {
                $streakStmt = $connect->prepare(
                    "SELECT streak_type, current_count, longest_count, last_activity_date FROM streaks WHERE child_id = ?"
                );
                $streakStmt->execute([$cid]);
                $streakRows = $streakStmt->fetchAll(PDO::FETCH_ASSOC);
                $streakMap = [];
                foreach ($streakRows as $sr) {
                    $streakMap[$sr['streak_type']] = $sr;
                }
                $allStreaks[$cid] = $streakMap;

                $badgeStmt = $connect->prepare(
                    "SELECT b.badge_id, b.name, b.description, b.icon, cb.redeemed_at
                     FROM child_badge cb
                     INNER JOIN badge b ON cb.badge_id = b.badge_id
                     WHERE cb.child_id = ? ORDER BY cb.redeemed_at DESC"
                );
                $badgeStmt->execute([$cid]);
                $allBadges[$cid] = $badgeStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $allStreaks[$cid] = [];
                $allBadges[$cid] = [];
            }
        }
        $dashboardData['all_streaks'] = $allStreaks;
        $dashboardData['all_badges'] = $allBadges;

        // Also set streaks/badges for first child (for backwards compatibility)
        $firstChildId = $children[0]['child_id'];
        $dashboardData['streaks'] = $allStreaks[$firstChildId] ?? [];
        $dashboardData['badges'] = $allBadges[$firstChildId] ?? [];
    }

    // User settings (wrapped in try-catch for when table doesn't exist yet)
    try {
        $settingsStmt = $connect->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $settingsStmt->execute([$parentId]);
        $userSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        if (!$userSettings) {
            try {
                $connect->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)")->execute([$parentId]);
            } catch (Exception $e2) { /* ignore */ }
            $userSettings = ['theme' => 'light', 'language' => 'en', 'push_notifications' => 1, 'email_notifications' => 1, 'appointment_reminders' => 1, 'daily_reminders' => 1, 'milestone_alerts' => 1, 'data_sharing' => 1];
        }
    } catch (Exception $e) {
        $userSettings = ['theme' => 'light', 'language' => 'en', 'push_notifications' => 1, 'email_notifications' => 1, 'appointment_reminders' => 1, 'daily_reminders' => 1, 'milestone_alerts' => 1, 'data_sharing' => 1];
    }
    $dashboardData['user_settings'] = $userSettings;

    // Banners
    try {
        $bStmt = $connect->query("SELECT message, style, link FROM announcement_banners WHERE is_active=1 AND target_audience IN ('parents', 'all') ORDER BY created_at DESC LIMIT 3");
        $dashboardData['banners'] = $bStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) { $dashboardData['banners'] = []; }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="../../assets/logo.png">
    <link rel="stylesheet" href="../../styles/globals.css">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Mobile Header (visible only on mobile) -->
    <header class="dashboard-mobile-header">
        <a href="dashboard.php">
            <img src="../../assets/logo.png" alt="Bright Steps" style="height:2rem;width:auto;">
        </a>
        <button class="hamburger-btn" id="hamburger-btn" onclick="toggleDashboardSidebar()" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </header>
    <div class="dashboard-sidebar-overlay" id="dashboard-sidebar-overlay" onclick="toggleDashboardSidebar()"></div>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <img src="../../assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile">
                    <?php
                    $text1 = $_SESSION['fname'];
                    $fletter = $text1[0];
                    $text2 = $_SESSION['lname'];
                    $lletter = $text2[0];
                    ?>
                    <div class="user-avatar"><?php echo htmlspecialchars($fletter . $lletter); ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo ($_SESSION['fname']) ?> <?php echo ($_SESSION['lname']) ?>
                        </div>
                        <div class="user-badge-text"><?php echo ($planname) ?> Member</div>
                    </div>
                    <div class="user-badge-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                            <path d="M4 22h16" />
                            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav" id="sidebar-nav">
                <!-- Nav items will be populated by JavaScript -->
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" data-view="settings" onclick="switchView('settings')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M12 1v6m0 6v6m-9-9h6m6 0h6" />
                    </svg>
                    <span>Settings</span>
                </button>
                <button class="nav-item" onclick="showSupportPopup()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Contact Support</span>
                </button>
                <button class="nav-item nav-item-logout" onclick="handleLogout()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14l5-5-5-5m5 5H9" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div id="dashboard-content">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </main>
    </div>

    <!-- Dashboard View Templates (Hidden) -->
    <template id="home-view-template">
        <div class="dashboard-content">
            <div class="dashboard-header-section">
                <div>
                    <h1 class="dashboard-title">Welcome back, Sarah! 👋</h1>
                    <p class="dashboard-subtitle">Here's Emma's progress today</p>
                </div>
                <div class="streak-cards">
                    <div class="streak-card streak-orange">
                        <div class="streak-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2c3.5 2.5 7 7 7 11a7 7 0 1 1-14 0c0-4 3.5-8.5 7-11z" />
                            </svg>
                        </div>
                        <div class="streak-info">
                            <div class="streak-number">14</div>
                            <div class="streak-label">Day Streak</div>
                        </div>
                    </div>
                    <div class="streak-card streak-yellow">
                        <div class="streak-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </div>
                        <div class="streak-info">
                            <div class="streak-number">8</div>
                            <div class="streak-label">Badges</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="child-profile-card">
                <div class="child-avatar">E</div>
                <div class="child-info">
                    <h2 class="child-name">Emma Johnson</h2>
                    <div class="child-details">
                        <span>15 months old</span>
                        <span>•</span>
                        <span>Born: Aug 23, 2024</span>
                    </div>
                </div>
                <div class="child-stats">
                    <div class="stat-box">
                        <div class="stat-label">Weight</div>
                        <div class="stat-value">11.1 kg</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Height</div>
                        <div class="stat-value">78 cm</div>
                    </div>
                </div>
            </div>

            <h2 class="section-heading">Development Status</h2>
            <div class="development-grid">
                <div class="development-card card-green">
                    <div class="development-header">
                        <div class="development-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                            </svg>
                        </div>
                        <div class="development-status status-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="development-title">Growth Tracking</h3>
                    <p class="development-description">Height and weight are on track with WHO standards</p>
                    <span class="development-badge badge-green">On Track - Green</span>
                </div>

                <div class="development-card card-yellow">
                    <div class="development-header">
                        <div class="development-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10" />
                            </svg>
                        </div>
                        <div class="development-status status-yellow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="development-title">Speech Development</h3>
                    <p class="development-description">Vocabulary is developing. Continue daily practice</p>
                    <span class="development-badge badge-yellow">Needs Attention - Yellow</span>
                </div>

                <div class="development-card card-green">
                    <div class="development-header">
                        <div class="development-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <circle cx="12" cy="12" r="6" />
                                <circle cx="12" cy="12" r="2" />
                            </svg>
                        </div>
                        <div class="development-status status-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="development-title">Motor Skills</h3>
                    <p class="development-description">Excellent progress in fine and gross motor skills</p>
                    <span class="development-badge badge-green">On Track - Green</span>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            Today's Recommended Activities
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="activity-item activity-blue">
                            <div class="activity-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10" />
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4 class="activity-title">Reading Time</h4>
                                <p class="activity-description">Read a picture book together. Point to objects and say
                                    their names clearly.</p>
                                <span class="activity-duration">⏱ 15 minutes</span>
                            </div>
                        </div>

                        <div class="activity-item activity-purple">
                            <div class="activity-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <circle cx="12" cy="12" r="6" />
                                    <circle cx="12" cy="12" r="2" />
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4 class="activity-title">Stacking Blocks</h4>
                                <p class="activity-description">Practice hand-eye coordination by stacking colorful
                                    blocks together.</p>
                                <span class="activity-duration">⏱ 10 minutes</span>
                            </div>
                        </div>

                        <div class="activity-item activity-green">
                            <div class="activity-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4 class="activity-title">Outdoor Walk</h4>
                                <p class="activity-description">Take a walk outside. Encourage walking on different
                                    surfaces.</p>
                                <span class="activity-duration">⏱ 20 minutes</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-column">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                                Upcoming Appointments
                            </h3>
                        </div>
                        <div class="card-content">
                            <div class="appointment-item">
                                <div class="appointment-icon icon-blue-bg">
                                    📅
                                </div>
                                <div class="appointment-info">
                                    <div class="appointment-title">MMR Vaccination</div>
                                    <div class="appointment-date">Nov 28, 2025 at 10:00 AM</div>
                                    <div class="appointment-location">Dr. Smith - City Pediatrics</div>
                                </div>
                            </div>

                            <div class="appointment-item">
                                <div class="appointment-icon icon-purple-bg">
                                    📅
                                </div>
                                <div class="appointment-info">
                                    <div class="appointment-title">15-Month Checkup</div>
                                    <div class="appointment-date">Dec 15, 2025 at 2:30 PM</div>
                                    <div class="appointment-location">Dr. Johnson - Health Center</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">This Month's Progress</h3>
                        </div>
                        <div class="card-content">
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Motor Skills</span>
                                    <span>4/5</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 80%"></div>
                                </div>
                            </div>

                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Language</span>
                                    <span>6/8</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 75%"></div>
                                </div>
                            </div>

                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Social Skills</span>
                                    <span>5/6</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 83%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-actions-card">
                <h3 class="section-heading">Quick Actions</h3>
                <div class="quick-actions-grid">
                    <button class="quick-action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        <span>Log Growth</span>
                    </button>
                    <button class="quick-action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10" />
                        </svg>
                        <span>Record Speech</span>
                    </button>
                    <button class="quick-action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="6" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                        <span>Add Activity</span>
                    </button>
                    <button class="quick-action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <span>Book Clinic</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <script>
        try {
            window.dashboardData = <?php echo json_encode($dashboardData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        } catch(e) {
            console.error('Failed to parse dashboard data:', e);
            window.dashboardData = {parent:{},children:[],appointments:[],streaks:[],badges:[],subscription:{plan_name:'Free'}};
        }
    </script>
    <script src="../../scripts/theme-toggle.js?v=3"></script>
    <script src="../../scripts/language-toggle.js?v=5"></script>
    <script src="../../scripts/navigation.js?v=3"></script>
    <script src="dashboard.js?t=<?php echo time(); ?>"></script>
    <script>
        // Inline bootstrap fallback
        (function() {
            var el = document.getElementById('dashboard-content');
            if (!el) return;
            // Check if content already loaded by dashboard.js bootstrap
            if (el.innerHTML.trim() !== '' && !el.innerHTML.includes('Content will be loaded')) {
                return; // dashboard.js already handled it
            }
            // dashboard.js bootstrap didn't run - call hoisted functions directly
            try {
                if (typeof initNav === 'function') {
                    initNav();
                    switchView('home');
                } else {
                    el.innerHTML = '<div style="padding:2rem;color:red;font-size:1.2rem;">ERROR: initNav function not found.</div>';
                }
            } catch(e) {
                el.innerHTML = '<div style="padding:2rem;color:red;font-size:1.2rem;"><strong>Error:</strong> ' + e.message + '</div>';
            }
        })();
    </script>
    <script src="../../scripts/chatbot.js"></script>
    <script>
        // Dashboard sidebar toggle for mobile
        function toggleDashboardSidebar() {
            const sidebar = document.querySelector('.dashboard-sidebar');
            const overlay = document.getElementById('dashboard-sidebar-overlay');
            const hamburger = document.getElementById('hamburger-btn');
            if (!sidebar) return;
            const isOpen = sidebar.classList.contains('sidebar-open');
            if (isOpen) {
                sidebar.classList.remove('sidebar-open');
                overlay.classList.remove('open');
                hamburger.classList.remove('open');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('sidebar-open');
                overlay.classList.add('open');
                hamburger.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
        }
    </script>
</body>

</html>