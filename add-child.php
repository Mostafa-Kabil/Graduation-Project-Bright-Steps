<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}
$isSetup = isset($_GET['setup']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $isSetup ? 'Welcome! Add Your Child' : 'Add Child'; ?> - Bright Steps
    </title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=8">
    <style>
        .auth-card {
            max-width: 600px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .optional-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--slate-400);
            margin: 1.5rem 0 1rem;
        }

        .optional-divider::before,
        .optional-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--slate-200);
            margin: 0 .5rem;
        }

        /* SSN Modal Styles */
        .ssn-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .ssn-modal-card {
            background: var(--surface-light, #fff);
            border: 1px solid var(--surface-border, #e2e8f0);
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ssn-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--slate-500);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .ssn-modal-close:hover {
            background: var(--slate-100);
            color: var(--slate-800);
        }

        .gamification-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .help-icon {
            cursor: pointer;
            color: var(--primary-color, #6C63FF);
            margin-left: 6px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <?php if (!$isSetup): ?>
            <button class="back-button" onclick="window.history.back()">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                Back
            </button>
        <?php endif; ?>

        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <a href="index.php" class="auth-logo">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </a>
                    <h1 class="auth-title">
                        <?php echo $isSetup ? 'Welcome to Bright Steps!' : 'Add a New Child'; ?>
                    </h1>
                    <p class="auth-subtitle">
                        <?php echo $isSetup ? "Let's start by adding your child's details to personalize your dashboard." : "Enter your child's details below."; ?>
                    </p>
                </div>

                <form id="add-child-form" class="auth-form" novalidate onsubmit="submitChild(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" required
                                placeholder="Emma">
                            <div class="error-message" id="err-fname"
                                style="color:#ef4444;font-size:12px;margin-top:4px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" required
                                placeholder="Johnson">
                            <div class="error-message" id="err-lname"
                                style="color:#ef4444;font-size:12px;margin-top:4px;"></div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Social Security Number (SSN) *
                                <svg onclick="showSsnModal()" class="help-icon" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </label>
                            <input type="text" id="ssn" name="ssn" class="form-input" required
                                placeholder="e.g. 123456789">
                            <div class="error-message" id="err-ssn"
                                style="color:#ef4444;font-size:12px;margin-top:4px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" id="birth_date" name="birth_date" class="form-input" required
                                style="color:var(--text-color);">
                            <div class="error-message" id="err-dob"
                                style="color:#ef4444;font-size:12px;margin-top:4px;"></div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Gender *</label>
                            <select id="gender" name="gender" class="form-input" required
                                style="color:var(--text-color);">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <div class="error-message" id="err-gender"
                                style="color:#ef4444;font-size:12px;margin-top:4px;"></div>
                        </div>
                    </div>

                    <div class="optional-divider">Optional Growth Measurements</div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" id="weight" name="weight" class="form-input"
                                placeholder="e.g. 11.5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" step="0.1" id="height" name="height" class="form-input"
                                placeholder="e.g. 78.0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Head Circumference (cm)</label>
                        <input type="number" step="0.1" id="head_circumference" name="head_circumference"
                            class="form-input" placeholder="e.g. 45.0">
                    </div>

                    <div class="error-message" id="main-error"
                        style="text-align:center;margin-bottom:1rem;color:#ef4444;font-size:0.9rem;"></div>
                    <div class="success-message" id="main-success"
                        style="text-align:center;margin-bottom:1rem;color:#22c55e;font-size:0.9rem;"></div>

                    <button type="submit" id="submit-btn" class="btn btn-gradient btn-lg btn-full">
                        <?php echo $isSetup ? 'Complete Setup' : 'Save Child Profile'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Gamification SSN Modal -->
    <div class="ssn-modal-overlay" id="ssn-modal" onclick="if(event.target===this) hideSsnModal()">
        <div class="ssn-modal-card">
            <button type="button" class="ssn-modal-close" onclick="hideSsnModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="gamification-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
            </div>
            <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:0.75rem;color:var(--slate-900);">Why do we need your SSN?</h2>
            <p style="font-size:0.95rem;color:var(--slate-600);line-height:1.5;margin-bottom:1rem;">
                Your child's SSN securely links their profile so they can earn <strong>points, rewards, and milestone badges</strong> as they grow!
            </p>
            <p style="font-size:0.85rem;color:var(--slate-400);line-height:1.4;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                We use strict encryption. Your data is 100% safe and never shared.
            </p>
            <button type="button" class="btn btn-gradient btn-full" style="margin-top:1.5rem;" onclick="hideSsnModal()">I Understand</button>
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

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script>
        function showSsnModal() {
            document.getElementById('ssn-modal').style.display = 'flex';
        }
        function hideSsnModal() {
            document.getElementById('ssn-modal').style.display = 'none';
        }

        async function submitChild(e) {
            e.preventDefault();
            const form = document.getElementById('add-child-form');
            const data = new FormData(form);
            const btn = document.getElementById('submit-btn');
            const errMain = document.getElementById('main-error');
            const sucMain = document.getElementById('main-success');

            // clear errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            sucMain.textContent = '';

            // simple validation
            let hasErr = false;
            if (!data.get('first_name').trim()) { document.getElementById('err-fname').textContent = 'Required'; hasErr = true; }
            if (!data.get('last_name').trim()) { document.getElementById('err-lname').textContent = 'Required'; hasErr = true; }
            if (!data.get('ssn').trim()) { document.getElementById('err-ssn').textContent = 'Required'; hasErr = true; }
            if (!data.get('birth_date')) { document.getElementById('err-dob').textContent = 'Required'; hasErr = true; }
            if (!data.get('gender')) { document.getElementById('err-gender').textContent = 'Required'; hasErr = true; }

            if (hasErr) return;

            btn.disabled = true;
            btn.textContent = 'Saving...';

            try {
                const res = await fetch('api_save_child.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();

                if (result.success) {
                    sucMain.textContent = result.message;
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    errMain.textContent = result.errors ? result.errors.join(', ') : (result.error || 'Failed to save');
                    btn.disabled = false;
                    btn.textContent = '<?php echo $isSetup ? "Complete Setup" : "Save Child Profile"; ?>';
                }
            } catch (err) {
                errMain.textContent = 'Network error occurred.';
                btn.disabled = false;
                btn.textContent = '<?php echo $isSetup ? "Complete Setup" : "Save Child Profile"; ?>';
            }
        }
    </script>
</body>

</html>
