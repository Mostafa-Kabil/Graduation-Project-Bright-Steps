<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/auth.css">
    <style>
        .wizard-container {
            width: 100%;
            max-width: 32rem;
            margin: auto;
        }
        .step { display: none; }
        .step.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--slate-200);
            z-index: 0;
        }
        .progress-step {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--slate-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--slate-400);
            z-index: 1;
            transition: all 0.3s ease;
        }
        .progress-step.active, .progress-step.completed {
            background: var(--blue-600);
            border-color: var(--blue-600);
            color: white;
        }
        .progress-step.completed { background: var(--green-500); border-color: var(--green-500); }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        .option-card {
            border: 2px solid var(--slate-200);
            border-radius: var(--radius-lg);
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.5rem;
            background: white;
        }
        .option-card:hover { border-color: var(--blue-300); }
        .option-card.selected {
            border-color: var(--blue-600);
            background: rgba(59, 130, 246, 0.05);
        }
        .option-icon { font-size: 1.5rem; }
        .option-label { font-size: 0.875rem; font-weight: 600; color: var(--slate-700); }
        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5rem;
        }
        [data-theme="dark"] .option-card { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .option-card:hover { border-color: var(--blue-500); }
        [data-theme="dark"] .option-card.selected { background: rgba(59, 130, 246, 0.15); border-color: var(--blue-400); }
        [data-theme="dark"] .option-label { color: var(--text-primary); }
        [data-theme="dark"] .progress-step { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .progress-bar::before { background: var(--border-color); }
    </style>
</head>
<body>
    <div class="auth-page">
        <!-- Floating Theme Toggle -->
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
            <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5" />
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
            </svg>
            <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
            </svg>
        </button>

        <div class="wizard-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="assets/logo.png" alt="Bright Steps" class="auth-logo">
                    <h1 class="auth-title">Let's setup your profile</h1>
                    <p class="auth-subtitle">Help us personalize your Bright Steps experience</p>
                </div>

                <div class="progress-bar">
                    <div class="progress-step active" id="indicator-1">1</div>
                    <div class="progress-step" id="indicator-2">2</div>
                    <div class="progress-step" id="indicator-3">3</div>
                    <div class="progress-step" id="indicator-4">4</div>
                </div>

                <!-- Step 1: Child Info -->
                <div class="step active" id="step-1">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.125rem; font-weight: 600;">Tell us about your child</h3>
                    <div class="form-group">
                        <label class="form-label">Child's Name</label>
                        <input type="text" id="child_name" class="form-input" placeholder="e.g. Emma" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" id="child_dob" class="form-input" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Gender</label>
                        <select id="child_gender" class="form-input" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="wizard-buttons">
                        <div></div>
                        <button class="btn btn-primary" onclick="nextStep(1)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 2: Concerns -->
                <div class="step" id="step-2">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Main Focus Areas</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem;">Select the areas you want to track closely</p>
                    <div class="options-grid" id="concerns-grid">
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Speech">
                            <div class="option-icon">🗣️</div><div class="option-label">Speech</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Motor Skills">
                            <div class="option-icon">🏃</div><div class="option-label">Motor Skills</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Cognitive">
                            <div class="option-icon">🧠</div><div class="option-label">Cognitive</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Social">
                            <div class="option-icon">🤝</div><div class="option-label">Social</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Growth">
                            <div class="option-icon">📈</div><div class="option-label">Growth</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'concerns')" data-value="Sleep">
                            <div class="option-icon">😴</div><div class="option-label">Sleep</div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(2)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(2)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 3: Preferences -->
                <div class="step" id="step-3">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Activity Preferences</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem;">What kind of activities does your child enjoy?</p>
                    <div class="options-grid" id="activities-grid">
                        <div class="option-card" onclick="toggleOption(this, 'activities')" data-value="Interactive Games">
                            <div class="option-icon">🎮</div><div class="option-label">Interactive Games</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'activities')" data-value="Outdoor Play">
                            <div class="option-icon">🌳</div><div class="option-label">Outdoor Play</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'activities')" data-value="Reading/Books">
                            <div class="option-icon">📚</div><div class="option-label">Reading/Books</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'activities')" data-value="Arts & Crafts">
                            <div class="option-icon">🎨</div><div class="option-label">Arts & Crafts</div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(3)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(3)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 4: Goals -->
                <div class="step" id="step-4">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Your Goals</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem;">What do you want to achieve with Bright Steps?</p>
                    <div class="options-grid" id="goals-grid">
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Track Milestones">
                            <div class="option-icon">📝</div><div class="option-label">Track Milestones</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Consult Specialists">
                            <div class="option-icon">👨‍⚕️</div><div class="option-label">Consult Specialists</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Daily Activities">
                            <div class="option-icon">📅</div><div class="option-label">Daily Activities</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Speech analysis">
                            <div class="option-icon">🎙️</div><div class="option-label">Speech Analysis</div>
                        </div>
                    </div>
                    <div id="finish-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 1rem; text-align: center;"></div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(4)">&larr; Back</button>
                        <button class="btn btn-gradient" id="finish-btn" onclick="submitOnboarding()">Get Started 🚀</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="scripts/theme-toggle.js"></script>
    <script>
        const formData = {
            child_name: '', child_dob: '', child_gender: '',
            primary_concerns: [],
            preferred_activities: [],
            development_goals: []
        };

        function toggleOption(element, category) {
            element.classList.toggle('selected');
            const value = element.getAttribute('data-value');
            if (element.classList.contains('selected')) {
                formData[category].push(value);
            } else {
                formData[category] = formData[category].filter(v => v !== value);
            }
        }

        function nextStep(current) {
            if (current === 1) {
                formData.child_name = document.getElementById('child_name').value.trim();
                formData.child_dob = document.getElementById('child_dob').value.trim();
                formData.child_gender = document.getElementById('child_gender').value;
                if (!formData.child_name || !formData.child_dob || !formData.child_gender) {
                    alert('Please fill out all fields before continuing.');
                    return;
                }
            }
            document.getElementById('step-' + current).classList.remove('active');
            document.getElementById('step-' + (current + 1)).classList.add('active');
            
            document.getElementById('indicator-' + current).classList.add('completed');
            document.getElementById('indicator-' + current).classList.remove('active');
            document.getElementById('indicator-' + (current + 1)).classList.add('active');
        }

        function prevStep(current) {
            document.getElementById('step-' + current).classList.remove('active');
            document.getElementById('step-' + (current - 1)).classList.add('active');
            
            document.getElementById('indicator-' + current).classList.remove('active');
            document.getElementById('indicator-' + (current - 1)).classList.add('active');
            document.getElementById('indicator-' + (current - 1)).classList.remove('completed');
        }

        async function submitOnboarding() {
            const btn = document.getElementById('finish-btn');
            const err = document.getElementById('finish-error');
            
            btn.disabled = true;
            btn.textContent = 'Saving...';
            err.textContent = '';

            const payload = {
                child_name: formData.child_name,
                child_dob: formData.child_dob,
                child_gender: formData.child_gender,
                primary_concerns: formData.concerns || [],
                preferred_activities: formData.activities || [],
                development_goals: formData.goals || []
            };

            try {
                const res = await fetch('api_onboarding.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.success) {
                    window.location.replace(data.redirect);
                } else {
                    err.textContent = data.error || 'Failed to complete setup.';
                    btn.disabled = false;
                    btn.textContent = 'Get Started 🚀';
                }
            } catch (e) {
                err.textContent = 'Network error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Get Started 🚀';
            }
        }
    </script>
</body>
</html>
