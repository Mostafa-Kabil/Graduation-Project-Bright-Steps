<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright Steps - For Clinics</title>
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .page-content { padding: 4rem 1.5rem; max-width: 1280px; margin: 0 auto; }
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .hero h1 { font-size: 3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; line-height: 1.1; }
        .hero p { font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; }
        .hero-img { width: 100%; border-radius: var(--radius-2xl); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .metric-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 4rem; z-index: 2; position: relative; }
        .metric-card { background: var(--bg-card); padding: 3rem 2rem; border-radius: 24px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 30px 50px -15px rgba(0,0,0,0.08); }
        .metric-card h3 { font-size: 4rem; font-weight: 800; background: linear-gradient(135deg, var(--blue-500), var(--purple-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; display: flex; align-items: baseline; justify-content: center; }
        
        .calculator { background: var(--bg-secondary); padding: 3rem; border-radius: var(--radius-2xl); margin-top: 5rem; border: 1px solid var(--border-color); }
        .calc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;}
        .calc-input { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
        .calc-input input { padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 1rem; }
        .calc-result { display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800; color: var(--green-600); }
        @media(max-width:900px){ .hero, .calc-grid, .metric-cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 5rem;">
        <div class="hero">
            <div>
                <h1>Modernize your <br><span style="color:var(--purple-600);">Pediatric Clinic</span></h1>
                <p>Equip your administration and specialists with a unified management system. Bright Steps for Clinics handles patient flows, subscriptions, doctor assignments, and aggregated revenue analytics smoothly and securely.</p>
                <div style="display:flex;gap:1rem;">
                    <button class="btn btn-gradient btn-lg" onclick="navigateTo('contact')">Request Enterprise Demo</button>
                </div>
            </div>
            <div>
                <img src="assets/clinic_info_graphic.png" alt="Clinic Dashboard Illustration" class="hero-img">
            </div>
        </div>

        <div class="metric-cards">
            <div class="metric-card">
                <h3><div class="counter" data-target="40">0</div><span style="font-size: 2rem; margin-left: 5px;">%</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">Decrease in admin overhead</p>
            </div>
            <div class="metric-card">
                <h3><div class="counter" data-target="2.5">0.0</div><span style="font-size: 2rem; margin-left: 5px;">x</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">Faster patient onboarding</p>
            </div>
            <div class="metric-card">
                <h3><div class="counter" data-target="100">0</div><span style="font-size: 2rem; margin-left: 5px;">%</span></h3>
                <p style="color:var(--text-secondary);font-weight:600; font-size: 1.1rem;">HIPAA Compliant Infrastructure</p>
            </div>
        </div>

        <div class="calculator">
            <h2 style="font-size: 2rem; font-weight:800; margin-bottom: 2rem;">Calculate Your ROI</h2>
            <div class="calc-grid">
                <div>
                    <div class="calc-input">
                        <label style="font-weight:600;">Monthly Pediatric Appointments</label>
                        <input type="number" id="appts" value="1000" oninput="calculateROI()">
                    </div>
                    <div class="calc-input">
                        <label style="font-weight:600;">Current Admin Rate per patient ($)</label>
                        <input type="number" id="rate" value="15" oninput="calculateROI()">
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <h4 style="color:var(--text-secondary); margin-bottom:0.5rem;">Estimated Monthly Savings</h4>
                    <div class="calc-result">$<span id="savings">6000</span></div>
                    <p style="text-align:center; color:var(--text-secondary); margin-top:1rem; font-size:0.875rem;">Bright Steps automates tracking and reduces reporting time by up to 40%.</p>
                </div>
            </div>
        </div>
    </main>

    
    <script>
        document.querySelectorAll('.counter').forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const speed = 200;
                const inc = target / speed;
                if(count < target) {
                    counter.innerText = (count + inc).toFixed(target % 1 !== 0 ? 1 : 0);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });

        function calculateROI() {
            const appts = document.getElementById('appts').value || 0;
            const rate = document.getElementById('rate').value || 0;
            // 40% savings in admin time estimated
            const savingsDom = document.getElementById('savings');
            const total = Math.round(appts * rate * 0.40);
            savingsDom.innerText = total.toLocaleString();
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
