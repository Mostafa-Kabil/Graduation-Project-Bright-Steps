<?php
require_once "includes/auth_check.php";
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
    'appointments' => []
];

if ($parentId) {
    // Subscription
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

    // Children
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
        $s2 = $connect->prepare("SELECT height, weight, head_circumference, recorded_at FROM growth_record WHERE child_id = :cid ORDER BY recorded_at DESC LIMIT 1");
        $s2->execute(['cid' => $ch['child_id']]);
        $ch['growth'] = $s2->fetch(PDO::FETCH_ASSOC) ?: null;

        // All growth history
        $s3 = $connect->prepare("SELECT height, weight, head_circumference, recorded_at FROM growth_record WHERE child_id = :cid ORDER BY recorded_at ASC");
        $s3->execute(['cid' => $ch['child_id']]);
        $ch['growth_history'] = $s3->fetchAll(PDO::FETCH_ASSOC);

        // Badge count
        $s4 = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = :cid");
        $s4->execute(['cid' => $ch['child_id']]);
        $ch['badge_count'] = (int) $s4->fetchColumn();

        // Points
        $s5 = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = :cid LIMIT 1");
        $s5->execute(['cid' => $ch['child_id']]);
        $pts = $s5->fetchColumn();
        $ch['total_points'] = $pts !== false ? (int) $pts : 0;
    }
    unset($ch);
    $dashboardData['children'] = $children;

    // Appointments
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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <div class="dashboard-layout">
        <?php include "includes/sidebar.php"; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div id="dashboard-content">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </main>
    </div>
    <!-- Growth Measurement Modal -->
    <div class="modal-overlay" id="growth-modal" onclick="if(event.target===this) hideGrowthModal()"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:2000;align-items:center;justify-content:center;">
        <div class="modal-card"
            style="background:var(--surface-light);border-radius:20px;padding:2rem;width:90%;max-width:400px;text-align:left;position:relative;">
            <button type="button" onclick="hideGrowthModal()"
                style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text-color);cursor:pointer;opacity:0.6;">✕</button>
            <h2 style="margin-bottom:0.5rem;font-size:1.5rem;">Add Growth Record</h2>
            <p style="color:var(--slate-400);font-size:0.9rem;margin-bottom:1.5rem;">Earn <strong
                    style="color:#f59e0b;">+25 points</strong> for tracking your child's growth!</p>

            <form id="growth-form" onsubmit="submitGrowth(event)">
                <input type="hidden" id="growth-child-id" name="child_id">

                <div class="form-group" style="margin-bottom:1rem;">
                    <label class="form-label" style="display:block;margin-bottom:0.5rem;">Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" class="form-input"
                        style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-dark);color:var(--text-color);">
                </div>

                <div class="form-group" style="margin-bottom:1rem;">
                    <label class="form-label" style="display:block;margin-bottom:0.5rem;">Height (cm)</label>
                    <input type="number" step="0.1" name="height" class="form-input"
                        style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-dark);color:var(--text-color);">
                </div>

                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label class="form-label" style="display:block;margin-bottom:0.5rem;">Head Circumference
                        (cm)</label>
                    <input type="number" step="0.1" name="head_circumference" class="form-input"
                        style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-dark);color:var(--text-color);">
                </div>

                <div id="growth-msg" style="margin-bottom:1rem;font-size:0.9rem;font-weight:bold;text-align:center;">
                </div>

                <button type="submit" id="growth-submit-btn" class="btn btn-gradient btn-full"
                    style="width:100%;padding:1rem;border-radius:12px;font-weight:bold;cursor:pointer;border:none;">Save
                    Record & Get Points</button>
            </form>
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

    <script>
        window.dashboardData = <?php echo json_encode($dashboardData, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="scripts/theme-toggle.js?v=3"></script>
    <script src="scripts/language-toggle.js?v=5"></script>
    <script src="scripts/navigation.js?v=3"></script>
    <script src="scripts/dashboard.js?v=6"></script>
    <script src="scripts/growth-charts.js?v=1"></script>
    <script src="scripts/chatbot.js?v=1"></script>
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

        // Growth Gamification Modal Logic
        function showGrowthModal(childId = null) {
            // Find which child to attach to. Default to first child if not specified in param.
            if (!childId && window.dashboardData && window.dashboardData.children.length > 0) {
                childId = window.dashboardData.children[0].child_id;
            }
            if (childId) {
                document.getElementById('growth-child-id').value = childId;
                document.getElementById('growth-msg').textContent = '';
                document.getElementById('growth-modal').style.display = 'flex';
            } else {
                alert('No child selected.');
            }
        }

        function hideGrowthModal() {
            document.getElementById('growth-modal').style.display = 'none';
            document.getElementById('growth-form').reset();
        }

        async function submitGrowth(e) {
            e.preventDefault();
            const form = document.getElementById('growth-form');
            const btn = document.getElementById('growth-submit-btn');
            const msg = document.getElementById('growth-msg');
            const data = new FormData(form);

            if (!data.get('weight') && !data.get('height') && !data.get('head_circumference')) {
                msg.style.color = '#f87171';
                msg.textContent = 'Please enter at least one measurement.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Saving...';
            msg.textContent = '';

            try {
                const res = await fetch('api_add_growth.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();

                if (result.success) {
                    msg.style.color = '#4ade80';
                    msg.innerHTML = `🎉 ${result.message}`;
                    setTimeout(() => {
                        window.location.reload(); // Reload dashboard to fetch updated points and growth
                    }, 2000);
                } else {
                    msg.style.color = '#f87171';
                    msg.textContent = result.error || 'Failed to update growth.';
                    btn.disabled = false;
                    btn.textContent = 'Save Record & Get Points';
                }
            } catch (error) {
                msg.style.color = '#f87171';
                msg.textContent = 'Network error occurred.';
                btn.disabled = false;
                btn.textContent = 'Save Record & Get Points';
            }
        }
    </script>
</body>

</html>