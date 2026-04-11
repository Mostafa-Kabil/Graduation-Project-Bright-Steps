<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright Steps - For Parents</title>
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .page-content { padding: 4rem 1.5rem; max-width: 1280px; margin: 0 auto; }
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .hero h1 { font-size: 3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; line-height: 1.1; }
        .hero p { font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; }
        .hero-img { width: 100%; border-radius: var(--radius-2xl); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        
        .timeline { position: relative; max-width: 800px; margin: 5rem auto; margin-left: 2rem;}
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--slate-200); border-radius: 4px; }
        [data-theme="dark"] .timeline::before { background: var(--slate-700); }
        .timeline-item { position: relative; margin-bottom: 3rem; padding-left: 3rem; }
        .timeline-dot { position: absolute; left: -10px; top: 0; width: 24px; height: 24px; border-radius: 50%; background: var(--green-500); border: 4px solid var(--bg-primary); transition: transform 0.3s ease; }
        .timeline-item:hover .timeline-dot { transform: scale(1.3); }
        .timeline-content h3 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .timeline-content p { color: var(--text-secondary); line-height: 1.6; }

        @media(max-width:900px){ .hero { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 5rem;">
        <div class="hero">
            <div>
                <h1>Peace of mind at <br><span style="color:var(--green-600);">every step</span></h1>
                <p>Welcome to Bright Steps! As a parent, tracking your child's growth, speech, and motor skills should be joyful and intuitive. Let our AI algorithms ease your worries by ensuring every milestone is beautifully logged and celebrated.</p>
                <div style="display:flex;gap:1rem;">
                    <button class="btn btn-gradient btn-lg" onclick="navigateTo('signup')">Create Free Parent Account</button>
                </div>
            </div>
            <div>
                <img src="assets/parent_info_graphic.png" alt="Parent and Child Illustration" class="hero-img">
            </div>
        </div>

        <div>
            <h2 style="font-size: 2.25rem; font-weight:800; text-align:center; margin-bottom: 2rem;">How the Journey Works</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>Signup & Daily Logs</h3>
                        <p>Simply register and add your child's basic information. We start tracking right away. Every day, answer simple interactive questions tailored specifically to your child's age group to keep the AI updated.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background:var(--blue-500);"></div>
                    <div class="timeline-content">
                        <h3>Playful AI Interactions</h3>
                        <p>Record your baby's giggles or your toddler's newly formed sentences. The AI securely analyzes pronunciation and speech complexity—providing you with a confidence score and fun feedback.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background:var(--purple-500);"></div>
                    <div class="timeline-content">
                        <h3>Visualizing Development</h3>
                        <p>Visit your dashboard to view easy-to-understand charts. We translate complex pediatric guidelines into friendly traffic-light visuals: Green for great, Yellow for keep practicing.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background:var(--orange-500);"></div>
                    <div class="timeline-content">
                        <h3>Seamless Doctor Syncing</h3>
                        <p>Concerned about a milestone? Your entire dashboard history can be instantly linked and shared securely with certified specialists registered on Bright Steps, enabling faster, more accurate advice.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    

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
