<?php
session_start();
$portal = $_GET['portal'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Under Review - Bright Steps</title>
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

        /* Floating decorations */
        .status-page::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -60px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--yellow-100), var(--orange-100));
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
            background: linear-gradient(135deg, var(--blue-100), var(--cyan-100));
            opacity: 0.5;
            filter: blur(60px);
            animation: float-decor 14s ease-in-out infinite reverse;
        }

        [data-theme="dark"] .status-page::before {
            background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(249,115,22,0.08));
            opacity: 0.8;
        }
        [data-theme="dark"] .status-page::after {
            background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(6,182,212,0.08));
            opacity: 0.8;
        }

        @keyframes float-decor {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-25px) scale(1.04); }
        }

        .status-card {
            position: relative;
            z-index: 10;
            max-width: 520px;
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
            background: linear-gradient(135deg, var(--yellow-50), var(--yellow-100));
            border: 2px solid var(--yellow-200);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            animation: pulse-ring 3s ease-in-out infinite;
        }

        [data-theme="dark"] .icon-container {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.25);
        }

        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.15); }
            50% { box-shadow: 0 0 0 16px rgba(245, 158, 11, 0); }
        }

        .icon-container svg {
            width: 38px;
            height: 38px;
            color: var(--yellow-500);
            animation: spin-slow 8s linear infinite;
        }

        @keyframes spin-slow {
            100% { transform: rotate(360deg); }
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
            margin-bottom: 2.25rem;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 2.25rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .step.completed .step-circle {
            background: linear-gradient(135deg, var(--green-500), var(--green-600));
            color: white;
            border: 2px solid var(--green-300);
        }

        .step.active .step-circle {
            background: linear-gradient(135deg, var(--yellow-400), var(--yellow-500));
            color: white;
            border: 2px solid var(--yellow-300);
            animation: pulse-ring 2s ease-in-out infinite;
        }

        .step.pending .step-circle {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }

        .step-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .step.completed .step-label { color: var(--green-600); }
        .step.active .step-label { color: var(--yellow-600); }

        [data-theme="dark"] .step.completed .step-label { color: var(--green-400); }
        [data-theme="dark"] .step.active .step-label { color: var(--yellow-400); }

        .step-line {
            width: 60px;
            height: 2px;
            margin-bottom: 1.5rem;
        }

        .step-line.completed { background: linear-gradient(90deg, var(--green-400), var(--green-500)); }
        .step-line.pending { background: var(--border-color); }

        .info-box {
            background: var(--yellow-50);
            border: 1px solid var(--yellow-200);
            border-radius: var(--radius-xl);
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        [data-theme="dark"] .info-box {
            background: rgba(245, 158, 11, 0.06);
            border-color: rgba(245, 158, 11, 0.15);
        }

        .info-box-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--yellow-700);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        [data-theme="dark"] .info-box-title {
            color: var(--yellow-400);
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

        .btn-home-main {
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
        .btn-home-main:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); color: white; }

        .btn-back-link {
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
        .btn-back-link:hover { background: var(--bg-tertiary); color: var(--text-primary); }

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
            .step-line { width: 40px; }
        }
    </style>
</head>
<body>
    <div class="status-page">
        <a href="index.php" class="page-logo"><img src="assets/logo.png" alt="Bright Steps"></a>

        <div class="status-card">
            <div class="icon-container">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>

            <h1 class="status-title">Application Under Review</h1>
            <p class="status-desc">
                Thank you for registering! Your <?= $portal === 'clinic' ? 'clinic' : 'provider' ?> application is currently being reviewed by our administration team.
            </p>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <span class="step-label">Applied</span>
                </div>
                <div class="step-line completed"></div>
                <div class="step active">
                    <div class="step-circle">2</div>
                    <span class="step-label">Under Review</span>
                </div>
                <div class="step-line pending"></div>
                <div class="step pending">
                    <div class="step-circle">3</div>
                    <span class="step-label">Approved</span>
                </div>
            </div>

            <div class="info-box">
                <div class="info-box-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    We'll notify you by email
                </div>
                <p class="info-box-text">Our team typically reviews applications within 24-48 hours. Once approved, you'll receive a confirmation email and gain full access to your <?= $portal === 'clinic' ? 'clinic dashboard' : 'provider portal' ?>.</p>
            </div>

            <div class="status-actions">
                <a href="index.php" class="btn-home-main">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Return to Home
                </a>
                <a href="<?= $portal === 'clinic' ? 'clinic-login.php' : 'doctor-login.php' ?>" class="btn-back-link">
                    ← Back to Login
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
