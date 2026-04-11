<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright Steps - For Doctors & Specialists</title>
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .page-content { padding: 4rem 1.5rem; max-width: 1280px; margin: 0 auto; }
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .hero h1 { font-size: 3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; line-height: 1.1; }
        .hero p { font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; }
        .hero-img { width: 100%; border-radius: var(--radius-2xl); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .feature-tabs { display: flex; gap: 1rem; border-bottom: 1px solid var(--border-color); margin-bottom: 2rem; }
        .tab-btn { padding: 1rem 2rem; background: none; border: none; font-size: 1.125rem; font-weight: 600; color: var(--text-secondary); cursor: pointer; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--blue-600); border-bottom-color: var(--blue-600); }
        .tab-content { display: none; background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-xl); border: 1px solid var(--border-color); }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media(max-width:900px){ .hero { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 5rem;">
        <div class="hero">
            <div>
                <h1>Empower your practice with <span style="color:var(--blue-600);">AI-driven Insights</span></h1>
                <p>Bright Steps provides pediatricians and specialists with state-of-the-art tools to accurately track child development. Utilizing advanced AI speech and motor milestone analytics, you can make diagnostic decisions confidently backed by real data.</p>
                <div style="display:flex;gap:1rem;">
                    <button class="btn btn-gradient btn-lg" onclick="navigateTo('doctor-signup')">Join the Network</button>
                    <button class="btn btn-outline btn-lg" onclick="document.getElementById('explore').scrollIntoView({behavior:'smooth'})">Explore Features</button>
                </div>
            </div>
            <div>
                <img src="assets/doctor_info_graphic.png" alt="Doctor Dashboard Illustration" class="hero-img">
            </div>
        </div>

        <div id="explore" class="interactive-section" style="margin-top:4rem;">
            <h2 style="font-size: 2.25rem; font-weight:800; text-align:center; margin-bottom: 3rem;">Specialist Capabilities</h2>
            <div class="feature-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'tab1')">Speech Analytics</button>
                <button class="tab-btn" onclick="openTab(event, 'tab2')">Growth Percentiles</button>
                <button class="tab-btn" onclick="openTab(event, 'tab3')">Behavioral Reports</button>
            </div>
            
            <div id="tab1" class="tab-content active">
                <h3 style="font-size:1.5rem; margin-bottom:1rem;">AI Audio Transcription & Diagnostics</h3>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:1rem;">Upload voice samples provided by parents or take recordings during the session. Our AI assesses syllable complexity, articulation pace, and vocabulary range to map against developmental milestones for ages 0-5.</p>
                <ul style="color:var(--text-secondary); margin-left: 1.5rem; line-height:1.6;">
                    <li>Detects early signs of speech delay.</li>
                    <li>Generates automated phonetics breakdown.</li>
                </ul>
            </div>
            <div id="tab2" class="tab-content">
                <h3 style="font-size:1.5rem; margin-bottom:1rem;">WHO Standard Growth Tracking</h3>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:1rem;">Monitor weight, height, and head circumference mapped beautifully on standard WHO percentiles. Receive instant alerts when a child's trend line deviates significantly.</p>
                <ul style="color:var(--text-secondary); margin-left: 1.5rem; line-height:1.6;">
                    <li>Interactive 3D charting capabilities.</li>
                    <li>Easily printable PDF reports for patient files.</li>
                </ul>
            </div>
            <div id="tab3" class="tab-content">
                <h3 style="font-size:1.5rem; margin-bottom:1rem;">Behavioral Milestone Monitoring</h3>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:1rem;">Leverage parent-reported data synced constantly to your dashboard. Understand social, cognitive, and fine motor skills progression before the patient even walks into your clinic.</p>
                <ul style="color:var(--text-secondary); margin-left: 1.5rem; line-height:1.6;">
                    <li>Automated questionnaires for parents sent pre-visit.</li>
                    <li>Red-flag system for critical delays.</li>
                </ul>
            </div>
        </div>
    </main>

    
    <script>
        function openTab(evt, tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
    </script>

    <?php include 'includes/public_footer.php'; ?>

    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>

</html>
