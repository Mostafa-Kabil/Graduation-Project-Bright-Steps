<?php
session_start();
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
    header('Location: login.php');
    exit();
}

// Redirect specialists who haven't configured credentials yet
if (isset($_SESSION['is_first_login']) && $_SESSION['is_first_login'] === 1) {
    header('Location: doctor-first-login.php');
    exit;
}

include 'connection.php';
$doctorId = intval($_SESSION['id']);
$specialistId = intval($_SESSION['specialist_id'] ?? $_SESSION['id']);

$specialty = '';
try {
    $stmt = $connect->prepare("SELECT specialization FROM specialist WHERE specialist_id = ? LIMIT 1");
    $stmt->execute([$specialistId]);
    $specialty = $stmt->fetchColumn() ?: '';
} catch (Exception $e) {}

$doctorName = htmlspecialchars(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialist Setup - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=8">
    <style>
        .wizard-container { width: 100%; max-width: 32rem; margin: auto; }
        .step { display: none; }
        .step.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-bar { display: flex; justify-content: space-between; margin-bottom: 2rem; position: relative; }
        .progress-bar::before {
            content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px;
            background: var(--slate-200); z-index: 0;
        }
        .progress-step {
            width: 2rem; height: 2rem; border-radius: 50%; background: white;
            border: 2px solid var(--slate-200); display: flex; align-items: center; justify-content: center;
            font-size: 0.875rem; font-weight: 600; color: var(--slate-400); z-index: 1; transition: all 0.3s ease;
        }
        .progress-step.active, .progress-step.completed { background: var(--blue-600); border-color: var(--blue-600); color: white; }
        .progress-step.completed { background: var(--green-500); border-color: var(--green-500); }
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .option-card {
            border: 2px solid var(--slate-200); border-radius: var(--radius-lg); padding: 1rem;
            cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column;
            align-items: center; text-align: center; gap: 0.5rem; background: white;
        }
        .option-card:hover { border-color: var(--blue-300); }
        .option-card.selected { border-color: var(--blue-600); background: rgba(59, 130, 246, 0.05); }
        .option-icon { font-size: 1.5rem; }
        .option-label { font-size: 0.875rem; font-weight: 600; color: var(--slate-700); }
        .wizard-buttons { display: flex; justify-content: space-between; margin-top: 2.5rem; }
        
        .day-btn {
            padding: 0.5rem 1rem; border: 1px solid var(--slate-200); border-radius: 2rem;
            background: white; cursor: pointer; transition: all 0.2s; font-weight: 500; font-size: 0.9rem;
        }
        .day-btn.selected { background: var(--blue-600); color: white; border-color: var(--blue-600); }
        .days-container { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }

        [data-theme="dark"] .option-card, [data-theme="dark"] .day-btn { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .option-card:hover { border-color: var(--blue-500); }
        [data-theme="dark"] .option-card.selected, [data-theme="dark"] .day-btn.selected { background: rgba(59, 130, 246, 0.15); border-color: var(--blue-400); color: var(--text-primary); }
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
                    <h1 class="auth-title">Welcome, Dr. <?php echo $doctorName; ?></h1>
                    <p class="auth-subtitle">Let's set up your professional profile</p>
                </div>

                <div class="progress-bar">
                    <div class="progress-step active" id="indicator-1">1</div>
                    <div class="progress-step" id="indicator-2">2</div>
                    <div class="progress-step" id="indicator-3">3</div>
                    <div class="progress-step" id="indicator-4">4</div>
                </div>

                <!-- Step 1: Professional Details -->
                <div class="step active" id="step-1">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.125rem; font-weight: 600;">Professional Details</h3>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" id="specialization" class="form-input" value="<?php echo htmlspecialchars($specialty); ?>" placeholder="e.g. Pediatrician, Speech Therapist" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Years of Experience</label>
                        <input type="number" id="experience_years" class="form-input" min="0" placeholder="e.g. 5" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Certifications / Degrees</label>
                        <input type="text" id="certifications" class="form-input" placeholder="e.g. MD Pediatrics, Board Certified" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Upload Certificate (PDF/JPG/PNG)</label>
                        <input type="file" id="certificate" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--slate-500); display: block; margin-top: 0.25rem;">Upload your primary degree or medical license to get the Verified Badge.</small>
                    </div>
                    <div class="wizard-buttons">
                        <div></div>
                        <button class="btn btn-primary" onclick="nextStep(1)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 2: Working Schedule -->
                <div class="step" id="step-2">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Working Schedule</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem;">Set your standard availability</p>
                    
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label">Working Days</label>
                        <div class="days-container" id="working_days">
                            <button type="button" class="day-btn" onclick="toggleDay(this, 1)">Mon</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 2)">Tue</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 3)">Wed</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 4)">Thu</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 5)">Fri</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 6)">Sat</button>
                            <button type="button" class="day-btn" onclick="toggleDay(this, 0)">Sun</button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Start Time</label>
                            <input type="time" id="start_time" class="form-input" value="09:00" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">End Time</label>
                            <input type="time" id="end_time" class="form-input" value="17:00" required>
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(2)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(2)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 3: Focus Areas -->
                <div class="step" id="step-3">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Focus Areas</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem;">What are your primary areas of expertise?</p>
                    <div class="options-grid" id="focus-grid">
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Speech Delay">
                            <div class="option-icon">🗣️</div><div class="option-label">Speech Delay</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Motor Skills">
                            <div class="option-icon">🏃</div><div class="option-label">Motor Skills</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Autism Spectrum">
                            <div class="option-icon">🧩</div><div class="option-label">Autism Spectrum</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Behavioral Therapy">
                            <div class="option-icon">🤝</div><div class="option-label">Behavioral Therapy</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="General Pediatrics">
                            <div class="option-icon">🩺</div><div class="option-label">General Pediatrics</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Sleep Disorders">
                            <div class="option-icon">😴</div><div class="option-label">Sleep Disorders</div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(3)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(3)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 4: Services & Goals -->
                <div class="step" id="step-4">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Consultation Types</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 1rem;">How do you prefer to conduct consultations?</p>
                    
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="consultation_types" value="Online" checked style="width: 1.25rem; height: 1.25rem;"> Online (Video)
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="consultation_types" value="On-site" checked style="width: 1.25rem; height: 1.25rem;"> On-site (Clinic)
                        </label>
                    </div>

                    <h3 style="margin-bottom: 0.5rem; font-size: 1.125rem; font-weight: 600;">Platform Goals</h3>
                    <div class="options-grid" id="goals-grid">
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Reach more patients">
                            <div class="option-icon">🌍</div><div class="option-label">Reach more patients</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Streamline appointments">
                            <div class="option-icon">📅</div><div class="option-label">Streamline appointments</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Digital record keeping">
                            <div class="option-icon">📁</div><div class="option-label">Digital record keeping</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Provide remote care">
                            <div class="option-icon">💻</div><div class="option-label">Provide remote care</div>
                        </div>
                    </div>
                    
                    <div id="finish-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 1rem; text-align: center;"></div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(4)">&larr; Back</button>
                        <button class="btn btn-gradient" id="finish-btn" onclick="submitOnboarding()">Complete Profile 🚀</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script>
        const formData = {
            specialization: '', experience_years: '', certifications: '',
            certificateFile: null,
            working_days: [], start_time: '', end_time: '',
            focus_areas: [],
            consultation_types: [],
            goals: []
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

        function toggleDay(element, dayNum) {
            element.classList.toggle('selected');
            const dayStr = dayNum.toString();
            if (element.classList.contains('selected')) {
                if (!formData.working_days.includes(dayStr)) formData.working_days.push(dayStr);
            } else {
                formData.working_days = formData.working_days.filter(d => d !== dayStr);
            }
        }

        function nextStep(current) {
            if (current === 1) {
                formData.specialization = document.getElementById('specialization').value.trim();
                formData.experience_years = document.getElementById('experience_years').value.trim();
                formData.certifications = document.getElementById('certifications').value.trim();
                const certInput = document.getElementById('certificate');
                if (certInput.files.length > 0) {
                    formData.certificateFile = certInput.files[0];
                }
                
                if (!formData.specialization || !formData.experience_years || !formData.certifications) {
                    alert('Please fill out all required fields (Certificate is optional).');
                    return;
                }
            } else if (current === 2) {
                formData.start_time = document.getElementById('start_time').value;
                formData.end_time = document.getElementById('end_time').value;
                if (formData.working_days.length === 0) {
                    alert('Please select at least one working day.');
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
            // Get consultation types
            formData.consultation_types = Array.from(document.querySelectorAll('input[name="consultation_types"]:checked')).map(cb => cb.value);
            
            const btn = document.getElementById('finish-btn');
            const err = document.getElementById('finish-error');
            
            btn.disabled = true;
            btn.textContent = 'Saving...';
            err.textContent = '';

            const fd = new FormData();
            fd.append('action', 'save');
            fd.append('specialization', formData.specialization);
            fd.append('experience_years', formData.experience_years);
            fd.append('certifications', formData.certifications);
            fd.append('start_time', formData.start_time);
            fd.append('end_time', formData.end_time);
            
            fd.append('working_days', JSON.stringify(formData.working_days));
            fd.append('focus_areas', JSON.stringify(formData.focus_areas));
            fd.append('consultation_types', JSON.stringify(formData.consultation_types));
            fd.append('goals', JSON.stringify(formData.goals));
            
            if (formData.certificateFile) {
                fd.append('certificate', formData.certificateFile);
            }

            try {
                const res = await fetch('api_doctor_onboarding.php', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();
                if (data.success) {
                    window.location.replace(data.redirect);
                } else {
                    err.textContent = data.error || 'Failed to complete setup.';
                    btn.disabled = false;
                    btn.textContent = 'Complete Profile 🚀';
                }
            } catch (e) {
                err.textContent = 'Network error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Complete Profile 🚀';
            }
        }
    </script>
</body>
</html>
