<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Bright Steps</title>
    <meta name="description" content="Get help and find answers to common questions about Bright Steps.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 6rem 1.5rem 4rem; animation: fadeIn 0.5s ease-out; }
        .page-header { text-align: center; margin-bottom: 4rem; }
        .page-title { font-size: 3rem; font-weight: 800; background: linear-gradient(135deg, var(--blue-600), var(--cyan-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        .page-subtitle { font-size: 1.125rem; color: var(--text-secondary); font-weight: 500; }
        .content-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-2xl); padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .content-section:hover { transform: translateY(-2px); box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1); }
        .content-section h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .content-section h2::before { content: ''; display: block; width: 24px; height: 24px; background: linear-gradient(135deg, var(--cyan-100), var(--blue-100)); border-radius: 6px; border: 2px solid var(--cyan-300); }
        [data-theme="dark"] .content-section h2::before { background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(59,130,246,0.2)); border-color: var(--cyan-500); }
        .content-section p, .content-section ul { color: var(--text-secondary); line-height: 1.7; font-size: 1.05rem; margin-bottom: 1rem; }
        .content-section ul { padding-left: 1.5rem; }
        .content-section li { margin-bottom: 0.5rem; }
        .content-section li::marker { color: var(--blue-500); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'includes/public_header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Help Center</h1>
            <p class="page-subtitle">Find answers and get the support you need</p>
        </div>

        <div class="content-section">
            <h2>Getting Started</h2>
            <ul>
                <li>Create your free account to begin tracking your child's development</li>
                <li>Add your child's profile with their birth date and basic information</li>
                <li>Start logging growth measurements like height and weight</li>
                <li>Explore the dashboard to see insights and recommendations</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Using Growth Tracking</h2>
            <p>Our growth tracking feature compares your child's measurements against WHO (World Health
                Organization) standards to show you where they stand.</p>
            <ul>
                <li>Measure height, weight, and head circumference regularly</li>
                <li>Enter measurements in the app to see progress over time</li>
                <li>Green means on track, yellow means needs attention, red suggests seeking professional advice
                </li>
                <li>View charts and trends in your dashboard</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Speech Analysis (Premium)</h2>
            <p>Upload audio recordings of your child speaking to get AI-powered analysis of their language
                development.</p>
            <ul>
                <li>Record your child in a quiet environment for best results</li>
                <li>Upload recordings of at least 30 seconds</li>
                <li>Receive analysis of vocabulary, pronunciation, and grammar</li>
                <li>Get personalized exercises to support language development</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Motor Skills Assessment (Premium)</h2>
            <p>Upload videos of your child performing activities to assess their motor skill development.</p>
            <ul>
                <li>Record activities like walking, running, or playing</li>
                <li>Ensure good lighting and a clear view of your child</li>
                <li>Our AI will analyze coordination and movement patterns</li>
                <li>Receive customized exercises for improvement</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Account & Billing</h2>
            <ul>
                <li>Upgrade to Premium anytime from your account settings</li>
                <li>Cancel your subscription at any time with no penalties</li>
                <li>Download your data or delete your account in privacy settings</li>
                <li>Contact support for billing questions at support@brightsteps.com</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Need More Help?</h2>
            <p>Can't find what you're looking for? Our support team is here to help.</p>
            <button class="btn btn-gradient btn-lg" onclick="navigateTo('contact')">Contact Support</button>
        </div>
    </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/public_footer.php'; ?> <!-- Floating Theme Toggle -->
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

    
    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>

</html>