<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/auth.css?v=12">
    <style>
        .status-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--blue-50), var(--purple-50), var(--pink-50));
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .status-page {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
        }

        /* Floating decorations matching auth pages */
        .status-page::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -60px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--red-100), var(--pink-100));
            opacity: 0.5;
            filter: blur(60px);
            animation: float-decor 12s ease-in-out infinite;
        }
        .status-page::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -40px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-100), var(--blue-100));
            opacity: 0.5;
            filter: blur(60px);
            animation: float-decor 14s ease-in-out infinite reverse;
        }

        [data-theme="dark"] .status-page::before {
            background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(236,72,153,0.1));
            opacity: 0.8;
        }
        [data-theme="dark"] .status-page::after {
            background: linear-gradient(135deg, rgba(168,85,247,0.1), rgba(99,102,241,0.1));
            opacity: 0.8;
        }

        @keyframes float-decor {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-25px) scale(1.04); }
        }

        .status-card {
            position: relative;
            z-index: 10;
            max-width: 480px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-3xl);
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        [data-theme="dark"] .status-card {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        .icon-container {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--red-50), var(--red-100));
            border: 2px solid var(--red-200);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            animation: pulse-ring 2.5s ease-in-out infinite;
        }

        [data-theme="dark"] .icon-container {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.25);
        }

        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.15); }
            50% { box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
        }

        .icon-container svg {
            width: 38px;
            height: 38px;
            color: var(--red-500);
        }

        .status-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .status-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .info-box {
            background: var(--red-50);
            border: 1px solid var(--red-200);
            border-radius: var(--radius-xl);
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        [data-theme="dark"] .info-box {
            background: rgba(239, 68, 68, 0.06);
            border-color: rgba(239, 68, 68, 0.15);
        }

        .info-box-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--red-600);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        [data-theme="dark"] .info-box-title {
            color: var(--red-400);
        }

        .info-box-text {
            font-size: 0.825rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
        }

        .status-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-support {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            background: linear-gradient(135deg, var(--blue-600), var(--purple-600));
            color: white;
            border: none;
            border-radius: var(--radius-xl);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .btn-support:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); color: white; }

        .btn-home-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 2rem;
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
        }
        .btn-home-link:hover { background: var(--bg-tertiary); color: var(--text-primary); }

        .page-logo {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 20;
        }
        .page-logo img { height: 2.5rem; }

        @media (max-width: 640px) {
            .status-card { padding: 2rem 1.5rem; }
            .status-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="status-page">
        <a href="index.php" class="page-logo"><img src="assets/logo.png" alt="Bright Steps"></a>

        <div class="status-card">
            <div class="icon-container">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            <h1 class="status-title">Account Suspended</h1>
            <p class="status-desc">
                Your account has been temporarily suspended by an administrator. 
                This may be due to a policy violation or a security review.
            </p>

            <div class="info-box">
                <div class="info-box-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    What does this mean?
                </div>
                <p class="info-box-text">While your account is suspended, you cannot access any platform features. 
                   If you believe this is a mistake, please contact our support team for assistance.</p>
            </div>

            <div class="status-actions">
                <a href="mailto:support@brightsteps.com?subject=Account%20Suspension%20Appeal" class="btn-support">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Contact Support
                </a>
                <a href="index.php" class="btn-home-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="scripts/theme-toggle.js?v=8"></script>
    <script>
        // Apply saved theme so it matches the rest of the site
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
