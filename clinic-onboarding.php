<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    header('Location: clinic-login.php');
    exit();
}

include 'connection.php';
$clinicId = intval($_SESSION['id']);

// If not first login, redirect back to dashboard
try {
    $stmt = $connect->prepare("SELECT is_first_login, clinic_name FROM clinic WHERE clinic_id = ? LIMIT 1");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$clinic || $clinic['is_first_login'] != 1) {
        header('Location: dashboards/clinic/clinic-dashboard.php');
        exit();
    }
} catch (Exception $e) {}

$clinicName = htmlspecialchars($clinic['clinic_name'] ?? 'Clinic Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Setup - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=8">
    <style>
        .wizard-container { width: 100%; max-width: 36rem; margin: auto; }
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
        .wizard-buttons { display: flex; justify-content: space-between; margin-top: 2.5rem; }
        
        .image-preview-area {
            border: 2px dashed var(--slate-300); border-radius: var(--radius-lg); padding: 2rem;
            text-align: center; cursor: pointer; transition: all 0.2s; background: var(--slate-50);
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;
        }
        .image-preview-area:hover { border-color: var(--blue-400); background: white; }
        #logo-preview { max-width: 150px; max-height: 150px; border-radius: 8px; display: none; box-shadow: var(--shadow-sm); }
        
        [data-theme="dark"] .progress-step { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .progress-bar::before { background: var(--border-color); }
        [data-theme="dark"] .image-preview-area { background: var(--bg-tertiary); border-color: var(--border-color); }
        [data-theme="dark"] .image-preview-area:hover { border-color: var(--blue-500); }
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
                    <h1 class="auth-title">Welcome to Bright Steps!</h1>
                    <p class="auth-subtitle">Let's configure your clinic's public profile</p>
                </div>

                <div class="progress-bar">
                    <div class="progress-step active" id="indicator-1">1</div>
                    <div class="progress-step" id="indicator-2">2</div>
                    <div class="progress-step" id="indicator-3">3</div>
                </div>

                <!-- Step 1: Clinic Identity -->
                <div class="step active" id="step-1">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.125rem; font-weight: 600;">Clinic Identity</h3>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Official Clinic Name</label>
                        <input type="text" id="clinic_name" class="form-input" value="<?php echo htmlspecialchars($clinicName); ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Clinic Logo</label>
                        <input type="file" id="clinic_logo" accept="image/*" style="display: none;" onchange="previewLogo(this)">
                        <div class="image-preview-area" onclick="document.getElementById('clinic_logo').click()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; color: var(--slate-400);">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <span style="font-size: 0.9rem; color: var(--slate-600);">Click to upload your clinic's logo</span>
                            <img id="logo-preview" src="#" alt="Logo Preview">
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <div></div>
                        <button class="btn btn-primary" onclick="nextStep(1)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 2: Services & Bio -->
                <div class="step" id="step-2">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.125rem; font-weight: 600;">Services & Description</h3>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Clinic Bio / Description</label>
                        <textarea id="bio" class="form-input" rows="4" placeholder="Tell parents about your clinic's history, mission, and the care you provide..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Medical Specialties Provided</label>
                        <input type="text" id="specialties" class="form-input" placeholder="e.g. Pediatrics, Speech Therapy, Behavioral Therapy" required>
                        <small style="color: var(--slate-500); display: block; margin-top: 0.25rem;">Separate multiple specialties with commas.</small>
                    </div>

                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(2)">&larr; Back</button>
                        <button class="btn btn-primary" onclick="nextStep(2)">Next &rarr;</button>
                    </div>
                </div>

                <!-- Step 3: Location Details -->
                <div class="step" id="step-3">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.125rem; font-weight: 600;">Detailed Location</h3>
                    <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 1.5rem;">Help parents find your clinic easily.</p>

                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">City</label>
                            <input type="text" id="loc_city" class="form-input" placeholder="e.g. Cairo" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Area / District</label>
                            <input type="text" id="loc_area" class="form-input" placeholder="e.g. Maadi" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Street Address</label>
                        <input type="text" id="loc_street" class="form-input" placeholder="e.g. 15 Street 9" required>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label" style="display: flex; justify-content: space-between;">
                            Do you have other branches?
                            <label class="switch" style="font-size: 0.85rem; font-weight: normal; cursor: pointer;">
                                <input type="checkbox" id="has_branches" onchange="toggleBranches()" style="margin-right: 0.5rem;"> Yes
                            </label>
                        </label>
                    </div>

                    <div id="branches-container" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--slate-200);">
                        <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Additional Branches</h4>
                        <div id="branches-list"></div>
                        <button type="button" class="btn btn-outline" onclick="addBranch()" style="margin-top: 0.5rem; width: 100%; border-style: dashed;">+ Add Another Branch</button>
                    </div>
                    
                    <div id="finish-error" style="color: #ef4444; font-size: 0.85rem; margin-top: 1.5rem; text-align: center;"></div>
                    <div class="wizard-buttons">
                        <button class="btn btn-outline" onclick="prevStep(3)">&larr; Back</button>
                        <button class="btn btn-gradient" id="finish-btn" onclick="submitOnboarding()">Complete Setup 🚀</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script>
        let branchCount = 0;
        const formData = {
            clinic_name: '', logoFile: null,
            bio: '', specialties: '',
            loc_city: '', loc_area: '', loc_street: '', loc_building: ''
        };

        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('logo-preview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    formData.logoFile = input.files[0];
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function nextStep(current) {
            if (current === 1) {
                formData.clinic_name = document.getElementById('clinic_name').value.trim();
                if (!formData.clinic_name) {
                    alert('Clinic Name is required.');
                    return;
                }
            } else if (current === 2) {
                formData.bio = document.getElementById('bio').value.trim();
                formData.specialties = document.getElementById('specialties').value.trim();
                if (!formData.bio || !formData.specialties) {
                    alert('Please fill out the bio and specialties.');
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

        function toggleBranches() {
            const container = document.getElementById('branches-container');
            if (document.getElementById('has_branches').checked) {
                container.style.display = 'block';
                if (branchCount === 0) addBranch();
            } else {
                container.style.display = 'none';
            }
        }

        function addBranch() {
            branchCount++;
            const div = document.createElement('div');
            div.className = 'branch-item';
            div.style.cssText = 'padding: 1rem; border: 1px solid var(--slate-200); border-radius: var(--radius-md); margin-bottom: 1rem; position: relative;';
            div.innerHTML = `
                <button type="button" onclick="this.parentElement.remove()" style="position: absolute; right: 0.5rem; top: 0.5rem; background: none; border: none; color: var(--red-500); cursor: pointer;">✕</button>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Branch Name</label>
                    <input type="text" class="form-input branch-name" placeholder="e.g. Heliopolis Branch" required>
                </div>
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">City</label>
                        <input type="text" class="form-input branch-city" placeholder="e.g. Cairo" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Area / District</label>
                        <input type="text" class="form-input branch-area" placeholder="e.g. Heliopolis" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Street Address</label>
                    <input type="text" class="form-input branch-street" placeholder="e.g. 20 Roxy Square" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Building & Floor</label>
                    <input type="text" class="form-input branch-building" placeholder="e.g. Building 2, Floor 1" required>
                </div>
            `;
            document.getElementById('branches-list').appendChild(div);
        }

        async function submitOnboarding() {
            formData.loc_city = document.getElementById('loc_city').value.trim();
            formData.loc_area = document.getElementById('loc_area').value.trim();
            formData.loc_street = document.getElementById('loc_street').value.trim();
            formData.loc_building = document.getElementById('loc_building').value.trim();
            
            if (!formData.loc_city || !formData.loc_area || !formData.loc_street) {
                alert('Please fill out the main location details.');
                return;
            }

            // Combine location for the DB
            const fullLocation = `${formData.loc_building}, ${formData.loc_street}, ${formData.loc_area}, ${formData.loc_city}`;

            const btn = document.getElementById('finish-btn');
            const err = document.getElementById('finish-error');
            
            btn.disabled = true;
            btn.textContent = 'Saving Profile...';
            err.textContent = '';

            const fd = new FormData();
            fd.append('clinic_name', formData.clinic_name);
            fd.append('bio', formData.bio);
            fd.append('specialties', formData.specialties);
            
            // Main Location Data
            fd.append('city', formData.loc_city);
            fd.append('area', formData.loc_area);
            fd.append('street', formData.loc_street);
            fd.append('building', formData.loc_building);
            fd.append('location', fullLocation); // for backward compatibility

            // Additional Branches Data
            if (document.getElementById('has_branches').checked) {
                const branchItems = document.querySelectorAll('.branch-item');
                const branches = [];
                branchItems.forEach(item => {
                    const name = item.querySelector('.branch-name').value.trim();
                    const city = item.querySelector('.branch-city').value.trim();
                    const area = item.querySelector('.branch-area').value.trim();
                    const street = item.querySelector('.branch-street').value.trim();
                    const building = item.querySelector('.branch-building').value.trim();
                    if (name && city && area && street) {
                        branches.push({ name, city, area, street, building });
                    }
                });
                fd.append('additional_branches', JSON.stringify(branches));
            }
            
            if (formData.logoFile) {
                fd.append('profile_image', formData.logoFile);
            }

            try {
                const res = await fetch('api_clinic_onboarding.php', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();
                if (data.success) {
                    window.location.replace('dashboards/clinic/clinic-dashboard.php');
                } else {
                    err.textContent = data.error || 'Failed to save clinic profile.';
                    btn.disabled = false;
                    btn.textContent = 'Complete Setup 🚀';
                }
            } catch (e) {
                err.textContent = 'Network error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Complete Setup 🚀';
            }
        }
    </script>
</body>
</html>
