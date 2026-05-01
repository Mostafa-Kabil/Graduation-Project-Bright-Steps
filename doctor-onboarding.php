<?php
session_start();
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'specialist')) {
    header('Location: login.php');
    exit();
}
$doctorName = htmlspecialchars(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Onboarding - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=8">
    <style>
        .wizard-container {
            width: 100%;
            max-width: 36rem;
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
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--slate-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--slate-400);
            z-index: 1;
            transition: all 0.3s ease;
        }
        .progress-step.active {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-color: #6366f1;
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }
        .progress-step.completed {
            background: linear-gradient(135deg, #10b981, #059669);
            border-color: #059669;
            color: white;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }
        .step-header {
            margin-bottom: 1.5rem;
        }
        .step-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 0.35rem;
        }
        .step-header p {
            color: var(--slate-500);
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
            margin-top: 1rem;
        }
        .options-grid.three-col {
            grid-template-columns: 1fr 1fr 1fr;
        }
        .option-card {
            border: 2px solid var(--slate-200);
            border-radius: 14px;
            padding: 1.1rem 0.75rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.4rem;
            background: white;
            position: relative;
            overflow: hidden;
        }
        .option-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(14, 165, 233, 0.05));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .option-card:hover {
            border-color: var(--blue-300);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .option-card:hover::before { opacity: 1; }
        .option-card.selected {
            border-color: #6366f1;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(14, 165, 233, 0.05));
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
        }
        .option-card.selected::after {
            content: '✓';
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
        }
        .option-icon {
            font-size: 1.75rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08));
        }
        .option-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--slate-700);
            position: relative;
            z-index: 1;
        }
        .option-desc {
            font-size: 0.7rem;
            color: var(--slate-400);
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }
        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5rem;
        }
        .wizard-buttons .btn {
            min-width: 120px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(14, 165, 233, 0.08));
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 0.75rem;
        }
        .day-selector {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }
        .day-chip {
            padding: 0.5rem 1rem;
            border: 2px solid var(--slate-200);
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--slate-600);
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            user-select: none;
        }
        .day-chip:hover {
            border-color: var(--blue-300);
        }
        .day-chip.selected {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-color: #6366f1;
            box-shadow: 0 3px 10px rgba(99, 102, 241, 0.25);
        }
        .time-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .time-row label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--slate-600);
        }
        .time-row input[type="time"] {
            padding: 0.5rem 0.75rem;
            border: 2px solid var(--slate-200);
            border-radius: 10px;
            font-size: 0.85rem;
            color: var(--slate-700);
            font-weight: 500;
            background: white;
            transition: border-color 0.2s;
        }
        .time-row input[type="time"]:focus {
            outline: none;
            border-color: #6366f1;
        }
        .time-separator {
            font-weight: 700;
            color: var(--slate-400);
        }

        /* Certificate Upload */
        .upload-zone {
            border: 2px dashed var(--slate-300);
            border-radius: 14px;
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(99, 102, 241, 0.02);
            position: relative;
            margin-top: 0.5rem;
        }
        .upload-zone:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }
        .upload-zone.dragover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.08);
            transform: scale(1.01);
        }
        .upload-zone.has-file {
            border-color: #10b981;
            border-style: solid;
            background: rgba(16, 185, 129, 0.05);
        }
        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .upload-icon {
            font-size: 2rem;
            margin-bottom: 0.35rem;
        }
        .upload-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--slate-600);
        }
        .upload-hint {
            font-size: 0.72rem;
            color: var(--slate-400);
            margin-top: 0.25rem;
        }
        .file-preview {
            display: none;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            margin-top: 0.75rem;
        }
        .file-preview.visible {
            display: flex;
        }
        .file-preview-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .file-preview-info {
            flex: 1;
            min-width: 0;
        }
        .file-preview-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--slate-700);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-preview-size {
            font-size: 0.7rem;
            color: var(--slate-400);
        }
        .file-preview-remove {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            border: none;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
            flex-shrink: 0;
            z-index: 3;
            position: relative;
        }
        .file-preview-remove:hover {
            background: #ef4444;
            color: white;
        }
        .upload-error {
            font-size: 0.78rem;
            color: #ef4444;
            margin-top: 0.5rem;
            display: none;
        }
        .upload-error.visible {
            display: block;
        }

        /* Dark mode */
        [data-theme="dark"] .option-card { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .option-card:hover { border-color: var(--blue-500); }
        [data-theme="dark"] .option-card.selected { background: rgba(99, 102, 241, 0.15); border-color: var(--blue-400); }
        [data-theme="dark"] .option-label { color: var(--text-primary); }
        [data-theme="dark"] .option-desc { color: var(--text-secondary); }
        [data-theme="dark"] .progress-step { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .progress-bar::before { background: var(--border-color); }
        [data-theme="dark"] .day-chip { background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-secondary); }
        [data-theme="dark"] .time-row input[type="time"] { background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary); }
        [data-theme="dark"] .welcome-badge { background: rgba(99, 102, 241, 0.2); border-color: rgba(99, 102, 241, 0.3); }
        [data-theme="dark"] .upload-zone { border-color: var(--border-color); background: rgba(99, 102, 241, 0.04); }
        [data-theme="dark"] .upload-text { color: var(--text-primary); }
        [data-theme="dark"] .upload-hint { color: var(--text-secondary); }
        [data-theme="dark"] .file-preview { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.25); }
        [data-theme="dark"] .file-preview-name { color: var(--text-primary); }

        @media (max-width: 600px) {
            .options-grid { grid-template-columns: 1fr 1fr; }
            .options-grid.three-col { grid-template-columns: 1fr 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .wizard-container { padding: 0 0.5rem; }
        }
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
                    <div class="welcome-badge">🩺 Welcome, Dr. <?php echo htmlspecialchars($_SESSION['fname'] ?? ''); ?></div>
                    <h1 class="auth-title">Set up your practice profile</h1>
                    <p class="auth-subtitle">Help us tailor the Bright Steps experience for your practice</p>
                </div>

                <div class="progress-bar">
                    <div class="progress-step active" id="indicator-1">1</div>
                    <div class="progress-step" id="indicator-2">2</div>
                    <div class="progress-step" id="indicator-3">3</div>
                    <div class="progress-step" id="indicator-4">4</div>
                </div>

                <!-- Step 1: Professional Information -->
                <div class="step active" id="step-1">
                    <div class="step-header">
                        <h3>🏥 Professional Information</h3>
                        <p>Tell us about your medical background and expertise</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <select id="dr_specialization" class="form-input" required>
                            <option value="">Select your specialty</option>
                            <option value="pediatrician">Pediatrician</option>
                            <option value="child-psychiatrist">Child Psychiatrist</option>
                            <option value="developmental-pediatrician">Developmental Pediatrician</option>
                            <option value="neurologist">Pediatric Neurologist</option>
                            <option value="speech-therapist">Speech-Language Pathologist</option>
                            <option value="occupational-therapist">Occupational Therapist</option>
                            <option value="behavioral-therapist">Behavioral Therapist</option>
                            <option value="psychologist">Child Psychologist</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="dr_specialization_other" class="form-input" placeholder="Please specify your specialty" style="display:none; margin-top: 0.5rem;">
                    </div>
                    <div class="form-row" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Years of Experience</label>
                            <input type="number" id="dr_experience" class="form-input" placeholder="e.g. 5" min="0" max="60" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Certifications <span style="color:#ef4444;">*</span></label>
                            <div style="display:flex;gap:0.5rem;align-items:center;">
                                <input type="text" id="dr_certifications" class="form-input" placeholder="e.g. MD, FAAP" style="flex:1;">
                                <button type="button" onclick="document.getElementById('dr_certificate').click()" title="Upload certificate document" style="width:2.5rem;height:2.5rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:2px dashed #0ea5e9;border-radius:10px;background:rgba(14,165,233,0.05);color:#0ea5e9;cursor:pointer;transition:all .2s;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                                <input type="file" id="dr_certificate" accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileSelect(this)" style="display:none">
                            </div>
                            <div id="file-preview-name" style="margin-top:0.4rem;font-size:0.8rem;color:#0ea5e9;font-weight:500;"></div>
                            <div class="upload-error" id="upload-error" style="color:#ef4444;font-size:0.78rem;margin-top:0.5rem;display:none;"></div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <div></div>
                        <button class="btn btn-primary" onclick="nextStep(1)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 2: Specialization Focus -->
                <div class="step" id="step-2">
                    <div class="step-header">
                        <h3>🎯 Areas of Focus</h3>
                        <p>Select the developmental areas you specialize in treating</p>
                    </div>
                    <div class="options-grid" id="focus-grid">
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Speech & Language Delays">
                            <div class="option-icon">🗣️</div>
                            <div class="option-label">Speech & Language</div>
                            <div class="option-desc">Delays & disorders</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Motor Development">
                            <div class="option-icon">🏃</div>
                            <div class="option-label">Motor Development</div>
                            <div class="option-desc">Gross & fine motor</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Cognitive Development">
                            <div class="option-icon">🧠</div>
                            <div class="option-label">Cognitive</div>
                            <div class="option-desc">Learning & thinking</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Behavioral Issues">
                            <div class="option-icon">💡</div>
                            <div class="option-label">Behavioral</div>
                            <div class="option-desc">ADHD, Autism, etc.</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Social-Emotional">
                            <div class="option-icon">🤝</div>
                            <div class="option-label">Social-Emotional</div>
                            <div class="option-desc">Social skills & emotions</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'focus_areas')" data-value="Growth & Nutrition">
                            <div class="option-icon">📈</div>
                            <div class="option-label">Growth & Nutrition</div>
                            <div class="option-desc">Physical development</div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(2)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(2)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 3: Availability -->
                <div class="step" id="step-3">
                    <div class="step-header">
                        <h3>📅 Availability & Consultation</h3>
                        <p>Set your preferred working schedule and consultation types</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Working Days</label>
                        <div class="day-selector" id="days-selector">
                            <div class="day-chip" onclick="toggleDay(this)" data-value="0">Sun</div>
                            <div class="day-chip selected" onclick="toggleDay(this)" data-value="1">Mon</div>
                            <div class="day-chip selected" onclick="toggleDay(this)" data-value="2">Tue</div>
                            <div class="day-chip selected" onclick="toggleDay(this)" data-value="3">Wed</div>
                            <div class="day-chip selected" onclick="toggleDay(this)" data-value="4">Thu</div>
                            <div class="day-chip" onclick="toggleDay(this)" data-value="5">Fri</div>
                            <div class="day-chip" onclick="toggleDay(this)" data-value="6">Sat</div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1.25rem;">
                        <label class="form-label">Working Hours</label>
                        <div class="time-row">
                            <label for="dr_start_time">From</label>
                            <input type="time" id="dr_start_time" value="09:00">
                            <span class="time-separator">—</span>
                            <label for="dr_end_time">To</label>
                            <input type="time" id="dr_end_time" value="17:00">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1.25rem;">
                        <label class="form-label">Consultation Types</label>
                        <div class="options-grid">
                            <div class="option-card selected" onclick="toggleOption(this, 'consultation_types')" data-value="Online">
                                <div class="option-icon">🖥️</div>
                                <div class="option-label">Online</div>
                                <div class="option-desc">Video consultations</div>
                            </div>
                            <div class="option-card selected" onclick="toggleOption(this, 'consultation_types')" data-value="On-site">
                                <div class="option-icon">🏥</div>
                                <div class="option-label">On-site</div>
                                <div class="option-desc">In-clinic visits</div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(3)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(3)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 4: Goals -->
                <div class="step" id="step-4">
                    <div class="step-header">
                        <h3>🚀 Your Goals with Bright Steps</h3>
                        <p>What do you hope to achieve using our platform?</p>
                    </div>
                    <div class="options-grid" id="goals-grid">
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Monitor Patient Progress">
                            <div class="option-icon">📊</div>
                            <div class="option-label">Monitor Progress</div>
                            <div class="option-desc">Track patient milestones</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Communicate with Parents">
                            <div class="option-icon">💬</div>
                            <div class="option-label">Parent Communication</div>
                            <div class="option-desc">Message parents easily</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Write & Share Reports">
                            <div class="option-icon">📝</div>
                            <div class="option-label">Share Reports</div>
                            <div class="option-desc">Write medical reports</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Manage Appointments">
                            <div class="option-icon">📅</div>
                            <div class="option-label">Manage Appointments</div>
                            <div class="option-desc">Organize your schedule</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Review AI Insights">
                            <div class="option-icon">🤖</div>
                            <div class="option-label">AI Insights</div>
                            <div class="option-desc">Leverage AI analytics</div>
                        </div>
                        <div class="option-card" onclick="toggleOption(this, 'goals')" data-value="Grow My Practice">
                            <div class="option-icon">🌟</div>
                            <div class="option-label">Grow Practice</div>
                            <div class="option-desc">Expand patient base</div>
                        </div>
                    </div>
                    <div id="finish-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 1rem; text-align: center;"></div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(4)">&larr; Back</button>
                        <button class="btn btn-gradient" id="finish-btn" onclick="submitOnboarding()">Launch Dashboard 🚀</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script>
        const formData = {
            specialization: '',
            specialization_other: '',
            experience_years: 0,
            certifications: '',
            certificateFile: null,
            focus_areas: [],
            working_days: [1, 2, 3, 4],
            start_time: '09:00',
            end_time: '17:00',
            consultation_types: ['Online', 'On-site'],
            goals: []
        };

        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
        const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
        const ALLOWED_EXTS = ['.jpg', '.jpeg', '.png', '.pdf'];

        // Specialty other toggle
        document.getElementById('dr_specialization').addEventListener('change', function() {
            const otherField = document.getElementById('dr_specialization_other');
            otherField.style.display = this.value === 'other' ? 'block' : 'none';
            if (this.value !== 'other') otherField.value = '';
        });

        // ── Certificate Upload Handlers ──────────────────
        function handleFileSelect(input) {
            const errEl = document.getElementById('upload-error');
            errEl.style.display = 'none';
            errEl.textContent = '';

            if (!input.files || !input.files[0]) {
                removeFile();
                return;
            }

            const file = input.files[0];
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            // Validate type
            if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
                errEl.textContent = 'Invalid file type. Only JPG, PNG, and PDF are allowed.';
                errEl.style.display = 'block';
                input.value = '';
                removeFile();
                return;
            }

            // Validate size
            if (file.size > MAX_FILE_SIZE) {
                errEl.textContent = 'File is too large. Maximum size is 5MB.';
                errEl.style.display = 'block';
                input.value = '';
                removeFile();
                return;
            }

            // Show preview
            formData.certificateFile = file;
            document.getElementById('file-preview-name').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> ' + file.name + ' <button type="button" onclick="removeFile()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-weight:700;">&times;</button>';
        }

        function removeFile() {
            formData.certificateFile = null;
            document.getElementById('dr_certificate').value = '';
            document.getElementById('file-preview-name').innerHTML = '';
        }

        // ── Option & Day Toggles ──────────────────
        function toggleOption(element, category) {
            element.classList.toggle('selected');
            const value = element.getAttribute('data-value');
            if (element.classList.contains('selected')) {
                if (!formData[category].includes(value)) formData[category].push(value);
            } else {
                formData[category] = formData[category].filter(v => v !== value);
            }
        }

        function toggleDay(element) {
            element.classList.toggle('selected');
            const day = parseInt(element.getAttribute('data-value'));
            if (element.classList.contains('selected')) {
                if (!formData.working_days.includes(day)) formData.working_days.push(day);
            } else {
                formData.working_days = formData.working_days.filter(d => d !== day);
            }
        }

        // ── Step Navigation ──────────────────
        function nextStep(current) {
            if (current === 1) {
                const specSelect = document.getElementById('dr_specialization');
                const specOther = document.getElementById('dr_specialization_other');
                formData.specialization = specSelect.value === 'other' ? specOther.value.trim() : specSelect.value;
                formData.experience_years = parseInt(document.getElementById('dr_experience').value) || 0;
                formData.certifications = document.getElementById('dr_certifications').value.trim();

                if (!formData.specialization) {
                    alert('Please select your specialization.');
                    return;
                }

                // Certificate is required
                if (!formData.certificateFile) {
                    const errEl = document.getElementById('upload-error');
                    errEl.textContent = 'Please upload your certificate as proof of qualification.';
                    errEl.classList.add('visible');
                    return;
                }
            }
            if (current === 3) {
                formData.start_time = document.getElementById('dr_start_time').value;
                formData.end_time = document.getElementById('dr_end_time').value;
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

        // ── Submit with FormData (file upload) ──────────────────
        async function submitOnboarding() {
            const btn = document.getElementById('finish-btn');
            const err = document.getElementById('finish-error');
            
            btn.disabled = true;
            btn.textContent = 'Setting up...';
            err.textContent = '';

            formData.start_time = document.getElementById('dr_start_time').value;
            formData.end_time = document.getElementById('dr_end_time').value;

            // Build FormData for multipart upload
            const fd = new FormData();
            fd.append('specialization', formData.specialization);
            fd.append('experience_years', formData.experience_years);
            fd.append('certifications', formData.certifications);
            fd.append('focus_areas', JSON.stringify(formData.focus_areas));
            fd.append('working_days', JSON.stringify(formData.working_days));
            fd.append('start_time', formData.start_time);
            fd.append('end_time', formData.end_time);
            fd.append('consultation_types', JSON.stringify(formData.consultation_types));
            fd.append('goals', JSON.stringify(formData.goals));

            if (formData.certificateFile) {
                fd.append('certificate', formData.certificateFile);
            }

            try {
                const res = await fetch('api_doctor_onboarding.php?action=save', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();
                if (data.success) {
                    window.location.replace(data.redirect || 'doctor-dashboard.php');
                } else {
                    err.textContent = data.error || 'Failed to complete setup.';
                    btn.disabled = false;
                    btn.textContent = 'Launch Dashboard 🚀';
                }
            } catch (e) {
                err.textContent = 'Network error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Launch Dashboard 🚀';
            }
        }
    </script>
</body>
</html>
