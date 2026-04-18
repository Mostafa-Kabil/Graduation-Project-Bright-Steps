<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bright Steps</title>
    <meta name="description" content="Learn about Bright Steps child development platform.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
</head>

<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 1rem;">
        
        <!-- Modern Hero Section for About -->
        <section class="hero-section" style="padding: 6rem 0; background: transparent; overflow: hidden; position: relative;">
            <div class="hero-container" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
                <div class="hero-text" style="z-index: 1;">
                    <span class="badge badge-blue">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        Our Mission
                    </span>
                    <h1 class="hero-title" style="font-size: 3.5rem; line-height: 1.1; margin-bottom: 1.5rem;">Shaping a <span style="background: linear-gradient(135deg, var(--blue-500), var(--purple-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">brighter future</span> for early development</h1>
                    <p class="hero-description" style="font-size: 1.125rem; margin-bottom: 2rem;">
                        Founded by parents, pediatricians, and AI researchers who saw a gap in detecting developmental delays early. We created Bright Steps to help parents track development continuously and spot issues early.
                    </p>
                </div>
                
                <div class="hero-visual" style="position: relative;">
                    <div style="border-radius: 30px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255,255,255,0.1);">
                        <!-- Generated Graphic -->
                        <img src="assets/about_hero.png" alt="Bright Steps AI Platform" style="width: 100%; height: auto; display: block; object-fit: cover;">
                    </div>
                    
                    <!-- Decorative elements -->
                    <div class="floating-badge badge-green" style="bottom: -20px; left: -20px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem; position: relative; z-index: 2;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; margin-bottom: 5rem;">
                
                <!-- Card 1 -->
                <div class="content-section" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2.5rem; transition: transform 0.3s ease; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue-500)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem;">Pediatric Research</h2>
                    <p style="color: var(--text-secondary); line-height: 1.6;">Every milestone tracked by our platform is deeply rooted in globally recognized WHO pediatric standards and guidelines.</p>
                </div>

                <!-- Card 2 -->
                <div class="content-section" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2.5rem; transition: transform 0.3s ease; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(168,85,247,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--purple-500)" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem;">AI-Driven Insights</h2>
                    <p style="color: var(--text-secondary); line-height: 1.6;">We harness the power of artificial intelligence to analyze speech patterns and motor skills to spot nuances human eyes might miss.</p>
                </div>

                <!-- Card 3 -->
                <div class="content-section" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 2.5rem; transition: transform 0.3s ease; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--green-500)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem;">Uncompromised Privacy</h2>
                    <p style="color: var(--text-secondary); line-height: 1.6;">Your child's data is heavily encrypted. We utilize industry-leading security practices so you maintain total ownership over your data.</p>
                </div>

            </div>

            <!-- Join Us CTA -->
            <div style="background: linear-gradient(135deg, var(--slate-900), var(--slate-800)); border-radius: 30px; padding: 4rem; text-align: center; color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50%; left: -10%; width: 50%; height: 200%; background: radial-gradient(circle, rgba(168,85,247,0.15) 0%, transparent 70%);"></div>
                <div style="position: relative; z-index: 2;">
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">Join Our Team</h2>
                    <p style="font-size: 1.125rem; color: var(--slate-300); max-width: 600px; margin: 0 auto 2rem;">We are actively looking for passionate people in AI architecture, pediatrics, user design, and engineering to push boundaries.</p>
                    <button class="btn btn-gradient btn-lg" onclick="navigateTo('contact')">View Open Positions</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/public_footer.php'; ?>

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

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>
</html>