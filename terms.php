<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Bright Steps</title>
    <meta name="description" content="Bright Steps Terms of Service - Rules and guidelines for using our platform.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 6rem 1.5rem 4rem; animation: fadeIn 0.5s ease-out; }
        .page-header { text-align: center; margin-bottom: 4rem; }
        .page-title { font-size: 3rem; font-weight: 800; background: linear-gradient(135deg, var(--purple-600), var(--blue-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        .page-subtitle { font-size: 1.125rem; color: var(--text-secondary); font-weight: 500; }
        .content-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-2xl); padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .content-section:hover { transform: translateY(-2px); box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1); }
        .content-section h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .content-section h2::before { content: ''; display: block; width: 24px; height: 24px; background: linear-gradient(135deg, var(--purple-100), var(--blue-100)); border-radius: 6px; border: 2px solid var(--purple-300); }
        [data-theme="dark"] .content-section h2::before { background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(59,130,246,0.2)); border-color: var(--purple-500); }
        .content-section p, .content-section ul { color: var(--text-secondary); line-height: 1.7; font-size: 1.05rem; margin-bottom: 1rem; }
        .content-section ul { padding-left: 1.5rem; }
        .content-section li { margin-bottom: 0.5rem; }
        .content-section li::marker { color: var(--purple-500); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'includes/public_header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Terms of Service</h1>
            <p class="page-subtitle">Last updated: February 2025</p>
        </div>

        <div class="content-section">
            <h2>Agreement to Terms</h2>
            <p>By accessing or using Bright Steps, you agree to be bound by these Terms of Service. If you do not
                agree to these terms, please do not use our service.</p>
        </div>

        <div class="content-section">
            <h2>Description of Service</h2>
            <p>Bright Steps is an AI-powered child development monitoring platform designed to help parents track
                and support their child's growth, speech, and motor skill development. Our service includes:</p>
            <ul>
                <li>Growth tracking against WHO standards</li>
                <li>AI-powered speech and language analysis (Premium)</li>
                <li>Motor skills video assessment (Premium)</li>
                <li>Personalized development recommendations</li>
                <li>Healthcare provider integration (Premium)</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Medical Disclaimer</h2>
            <p><strong>Important:</strong> Bright Steps is not a medical device and does not provide medical advice,
                diagnosis, or treatment. Our AI-powered insights are informational tools to help you monitor your
                child's development, but they should never replace professional medical consultation.</p>
            <p>Always consult with qualified healthcare professionals for any concerns about your child's health or
                development. If our system indicates a "Red" status, we strongly recommend seeking professional
                medical evaluation.</p>
        </div>

        <div class="content-section">
            <h2>Account Responsibilities</h2>
            <ul>
                <li>You must be at least 18 years old to create an account</li>
                <li>You are responsible for maintaining the security of your account</li>
                <li>You must provide accurate information about yourself and your child</li>
                <li>You may not share your account with others</li>
                <li>You are responsible for all activity under your account</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the service for any unlawful purpose</li>
                <li>Upload content that is harmful, offensive, or violates others' rights</li>
                <li>Attempt to gain unauthorized access to our systems</li>
                <li>Interfere with or disrupt the service</li>
                <li>Use the service to collect information about others without consent</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Subscription and Billing</h2>
            <ul>
                <li>Free tier is available at no cost with limited features</li>
                <li>Premium subscriptions are billed monthly or annually</li>
                <li>You may cancel your subscription at any time</li>
                <li>Refunds are available within 14 days of initial purchase</li>
                <li>Prices may change with 30 days notice</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Intellectual Property</h2>
            <p>Bright Steps and its original content, features, and functionality are owned by Bright Steps Inc. and
                are protected by international copyright, trademark, and other intellectual property laws.</p>
        </div>

        <div class="content-section">
            <h2>Limitation of Liability</h2>
            <p>To the maximum extent permitted by law, Bright Steps shall not be liable for any indirect,
                incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether
                incurred directly or indirectly.</p>
        </div>

        <div class="content-section">
            <h2>Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. We will notify you of significant changes via
                email or through the service. Your continued use of Bright Steps after changes constitutes
                acceptance of the new terms.</p>
        </div>

        <div class="content-section">
            <h2>Contact</h2>
            <p>For questions about these Terms of Service, please contact us at:</p>
            <p><strong>Email:</strong> legal@brightsteps.com</p>
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