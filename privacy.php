<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Bright Steps</title>
    <meta name="description" content="Bright Steps Privacy Policy - How we protect your child's data.">
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
        .content-section h2::before { content: ''; display: block; width: 24px; height: 24px; background: linear-gradient(135deg, var(--green-100), var(--blue-100)); border-radius: 6px; border: 2px solid var(--green-300); }
        [data-theme="dark"] .content-section h2::before { background: linear-gradient(135deg, rgba(34,197,94,0.2), rgba(59,130,246,0.2)); border-color: var(--green-500); }
        .content-section p, .content-section ul { color: var(--text-secondary); line-height: 1.7; font-size: 1.05rem; margin-bottom: 1rem; }
        .content-section ul { padding-left: 1.5rem; }
        .content-section li { margin-bottom: 0.5rem; }
        .content-section li::marker { color: var(--green-500); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'includes/public_header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Privacy Policy</h1>
            <p class="page-subtitle">Last updated: February 2025</p>
        </div>

        <div class="content-section">
            <h2>Introduction</h2>
            <p>At Bright Steps, we take your privacy and your child's data security extremely seriously. This
                Privacy Policy explains how we collect, use, and protect your information when you use our child
                development monitoring platform.</p>
        </div>

        <div class="content-section">
            <h2>Information We Collect</h2>
            <p>We collect the following types of information:</p>
            <ul>
                <li><strong>Account Information:</strong> Email address, name, and password when you create an
                    account</li>
                <li><strong>Child Profile Data:</strong> Your child's name, birth date, and gender</li>
                <li><strong>Health Measurements:</strong> Height, weight, and head circumference data you enter</li>
                <li><strong>Media Files:</strong> Audio recordings and videos you upload for AI analysis (Premium)
                </li>
                <li><strong>Usage Data:</strong> How you interact with our platform to improve our services</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>How We Use Your Information</h2>
            <ul>
                <li>To provide growth tracking and development insights</li>
                <li>To power AI analysis of speech and motor skills (Premium features)</li>
                <li>To generate personalized recommendations for your child</li>
                <li>To send important updates about your child's development</li>
                <li>To improve our AI models and services (using anonymized data only)</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Data Security</h2>
            <p>We implement industry-leading security measures to protect your data:</p>
            <ul>
                <li>End-to-end encryption for all sensitive data</li>
                <li>Secure HTTPS connections for all communications</li>
                <li>Regular security audits and penetration testing</li>
                <li>Data stored in SOC 2 compliant data centers</li>
                <li>Strict access controls for our team members</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Data Sharing</h2>
            <p>We do not sell your personal data. We only share information:</p>
            <ul>
                <li>When you explicitly choose to share reports with healthcare providers</li>
                <li>With service providers who help us operate our platform (under strict agreements)</li>
                <li>When required by law or to protect safety</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Your Rights</h2>
            <p>You have complete control over your data:</p>
            <ul>
                <li>Access and download all your data at any time</li>
                <li>Correct or update your information</li>
                <li>Delete your account and all associated data</li>
                <li>Opt out of non-essential communications</li>
                <li>Request information about how your data is used</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>Children's Privacy</h2>
            <p>Bright Steps is designed for parents and guardians to track their children's development. We do not
                allow children under 13 to create accounts directly. All data about children is managed by their
                parent or guardian account.</p>
        </div>

        <div class="content-section">
            <h2>Contact Us</h2>
            <p>If you have questions about this Privacy Policy or our data practices, please contact us at:</p>
            <p><strong>Email:</strong> privacy@brightsteps.com</p>
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