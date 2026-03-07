<?php
require_once "includes/auth_check.php";
$parentId = $_SESSION['id'];

$stmt = $connect->prepare("SELECT child_id, first_name FROM child WHERE parent_id = ?");
$stmt->execute([$parentId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Subscription plan
$stmt = $connect->prepare("SELECT s.plan_name FROM parent_subscription ps INNER JOIN subscription s ON ps.subscription_id = s.subscription_id WHERE ps.parent_id = :pid LIMIT 1");
$stmt->execute(['pid' => $parentId]);
$planname = $stmt->fetchColumn() ?: 'Free';

// Motor Skills Behaviors (assuming these will be dynamically mapped to DB)
$skills = [
    "Sits without support for extended periods",
    "Crawls on hands and knees",
    "Pulls to stand using furniture",
    "Walks independently without falling",
    "Uses pincer grasp (thumb and index finger) to pick up small objects"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motor Skills Evaluation - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .evaluation-card {
            background: var(--surface-light);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--surface-border);
        }

        .eval-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--surface-border);
        }

        .skill-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .skill-row:last-child {
            border-bottom: none;
        }

        .skill-name {
            flex: 1;
            min-width: 250px;
            color: var(--text-color);
            font-weight: 500;
        }

        .skill-controls {
            display: flex;
            gap: 1rem;
        }

        select.form-input {
            width: auto;
            min-width: 150px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: none;
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>
    <div class="dashboard-layout">
        <?php include "includes/sidebar.php"; ?>

        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Motor Skills Evaluation</h1>
                        <p class="dashboard-subtitle">Track and evaluate your child's physical development milestones
                        </p>
                    </div>
                </div>

                <div class="alert-success" id="alert-success">Evaluation saved successfully! Your child's profile has
                    been updated.</div>
                <div class="alert-error" id="alert-error">Failed to save evaluation. Please try again.</div>

                <div class="evaluation-card">
                    <form id="evaluation-form" onsubmit="submitEvaluation(event)">
                        <div class="eval-header">
                            <label class="form-label" style="display:block;margin-bottom:0.5rem;">Select Child</label>
                            <select id="child_id" name="child_id" class="form-input" required>
                                <?php if (empty($children)): ?>
                                    <option value="">No children found</option>
                                <?php else: ?>
                                    <?php foreach ($children as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['child_id']); ?>">
                                            <?php echo htmlspecialchars($c['first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="skills-list">
                            <?php foreach ($skills as $index => $skill): ?>
                                <div class="skill-row">
                                    <div class="skill-name">
                                        <?php echo htmlspecialchars($skill); ?>
                                    </div>
                                    <div class="skill-controls">
                                        <input type="hidden" name="behavior_details[]"
                                            value="<?php echo htmlspecialchars($skill); ?>">
                                        <select name="frequency[]" class="form-input" required>
                                            <option value="">Frequency</option>
                                            <option value="rarely">Rarely</option>
                                            <option value="sometimes">Sometimes</option>
                                            <option value="often">Often</option>
                                            <option value="always">Always</option>
                                        </select>
                                        <select name="severity[]" class="form-input" required>
                                            <option value="">Status</option>
                                            <option value="not_yet">Not Yet</option>
                                            <option value="emerging">Emerging</option>
                                            <option value="mastered">Mastered</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top:2rem;text-align:right;">
                            <button type="submit" class="btn btn-gradient" id="submit-btn" <?php echo empty($children) ? 'disabled' : ''; ?>>
                                Submit Evaluation
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

    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <script>
        async function submitEvaluation(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const alertSuccess = document.getElementById('alert-success');
            const alertError = document.getElementById('alert-error');

            alertSuccess.style.display = 'none';
            alertError.style.display = 'none';
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const form = document.getElementById('evaluation-form');
            const data = new FormData(form);

            try {
                const res = await fetch('api_save_behavior.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();

                if (result.success) {
                    alertSuccess.style.display = 'block';
                    form.reset();
                } else {
                    alertError.textContent = result.error || 'Failed to save evaluation.';
                    alertError.style.display = 'block';
                }
            } catch (error) {
                alertError.textContent = 'Network error occurred.';
                alertError.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Submit Evaluation';
            }
        }
    </script>
</body>

</html>