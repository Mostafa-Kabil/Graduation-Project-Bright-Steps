<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Demo - Bright Steps</title>
    <meta name="description"
        content="See Bright Steps in action. Watch how our AI-powered platform helps parents track and support their child's development.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/landing.css">
    <style>
        /* Demo Page Styles */
        .demo-hero {
            padding: 6rem 2rem 3rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(168, 85, 247, 0.08));
        }

        .demo-hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .demo-hero h1 span {
            background: linear-gradient(135deg, var(--blue-500), var(--purple-600));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .demo-hero p {
            font-size: 1.15rem;
            color: var(--text-secondary);
            max-width: 620px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
        }

        /* Video Container */
        .demo-video-wrap {
            max-width: 960px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .demo-video-frame {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            /* 16:9 */
            border-radius: 1.25rem;
            overflow: hidden;
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.2), 0 0 0 1px rgba(99, 102, 241, 0.1);
        }

        .demo-video-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            color: #e0e7ff;
        }

        .play-button {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue-500), var(--purple-600));
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
            transition: all 0.3s ease;
            animation: pulse-play 2s infinite;
        }

        .play-button:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.6);
        }

        .play-button svg {
            width: 32px;
            height: 32px;
            fill: white;
            margin-left: 4px;
        }

        .demo-video-placeholder span {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.85;
        }

        @keyframes pulse-play {

            0%,
            100% {
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5), 0 0 0 0 rgba(99, 102, 241, 0.4);
            }

            50% {
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5), 0 0 0 16px rgba(99, 102, 241, 0);
            }
        }

        /* Feature Highlights */
        .demo-features {
            max-width: 960px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .demo-features h2 {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2.5rem;
        }

        .demo-features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .demo-feature-card {
            padding: 1.75rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .demo-feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .demo-feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .demo-feature-icon svg {
            width: 28px;
            height: 28px;
            stroke-width: 2;
        }

        .icon-blue {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.15));
        }

        .icon-blue svg {
            stroke: #6366f1;
        }

        .icon-green {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.15));
        }

        .icon-green svg {
            stroke: #22c55e;
        }

        .icon-purple {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(139, 92, 246, 0.15));
        }

        .icon-purple svg {
            stroke: #a855f7;
        }

        .demo-feature-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .demo-feature-card p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* CTA Section */
        .demo-cta {
            text-align: center;
            padding: 3rem 2rem 5rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.06), rgba(168, 85, 247, 0.06));
        }

        .demo-cta h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .demo-cta p {
            color: var(--text-secondary);
            margin-bottom: 1.75rem;
            font-size: 1.05rem;
        }

        @media (max-width: 768px) {
            .demo-hero h1 {
                font-size: 2rem;
            }

            .demo-features-grid {
                grid-template-columns: 1fr;
            }

            .play-button {
                width: 64px;
                height: 64px;
            }

            .play-button svg {
                width: 26px;
                height: 26px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="header-logo">
                <a href="index.php">
                    <img src="assets/logo.png" alt="Bright Steps Logo">
                </a>
            </div>
            <div class="header-actions">
                <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <path
                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                    عربي
                </button>
                <button class="btn btn-outline-secondary" onclick="navigateTo('doctor-login')">Doctor Portal</button>
                <button class="btn btn-ghost" onclick="navigateTo('login')">Log In</button>
                <button class="btn btn-gradient" onclick="navigateTo('signup')">Get Started Free</button>
            </div>
            <button class="hamburger-btn" id="hamburger-btn" onclick="toggleMobileMenu()" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <div class="mobile-menu-overlay" id="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
    <nav class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <img src="assets/logo.png" alt="Bright Steps">
            <button class="mobile-menu-close" onclick="toggleMobileMenu()">✕</button>
        </div>
        <div class="mobile-menu-body">
            <button class="mobile-nav-item" onclick="toggleLanguage(); toggleMobileMenu();">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path
                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                عربي / English
            </button>
            <div class="mobile-nav-divider"></div>
            <button class="mobile-nav-item" onclick="navigateTo('doctor-login')">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Doctor Portal
            </button>
            <button class="mobile-nav-item" onclick="navigateTo('login')">
                <svg viewBox="0 0 24 24">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-5-4l5-5-5-5m5 5H3" />
                </svg>
                Log In
            </button>
            <button class="mobile-nav-item btn-gradient" onclick="navigateTo('signup')">
                <svg viewBox="0 0 24 24" stroke="white">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
                Get Started Free
            </button>
        </div>
    </nav>

    <!-- Demo Hero -->
    <section class="demo-hero">
        <h1>See <span>Bright Steps</span> in Action</h1>
        <p>Watch how our AI-powered platform helps parents track and support their child's development journey.</p>
    </section>

    <!-- Video -->
    <div class="demo-video-wrap">
        <div class="demo-video-frame">
            <div class="demo-video-placeholder">
                <button class="play-button" aria-label="Play demo video">
                    <svg viewBox="0 0 24 24">
                        <polygon points="5,3 19,12 5,21" />
                    </svg>
                </button>
                <span>Demo Coming Soon</span>
            </div>
        </div>
    </div>

    <!-- Feature Highlights -->
    <section class="demo-features">
        <h2>What You'll Discover</h2>
        <div class="demo-features-grid">
            <div class="demo-feature-card">
                <div class="demo-feature-icon icon-blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                    </svg>
                </div>
                <h3>Growth Tracking</h3>
                <p>See how AI monitors height, weight, and head circumference against WHO standards in real time.</p>
            </div>
            <div class="demo-feature-card">
                <div class="demo-feature-icon icon-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 2a10 10 0 0 1 10 10v1M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1" />
                    </svg>
                </div>
                <h3>Speech Analysis</h3>
                <p>Upload voice recordings and get AI-driven evaluation of vocabulary and pronunciation development.</p>
            </div>
            <div class="demo-feature-card">
                <div class="demo-feature-icon icon-purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                </div>
                <h3>Smart Recommendations</h3>
                <p>Get personalized daily activities and milestone checklists tailored to your child's age and progress.
                </p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="demo-cta">
        <h2>Ready to start?</h2>
        <p>Join thousands of parents who trust Bright Steps for their child's development.</p>
        <button class="btn btn-gradient btn-lg" onclick="navigateTo('signup')">Start Your Free Trial</button>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <div class="footer-logo">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </div>
                    <p class="footer-text">AI-powered child development monitoring for ages 0-5</p>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Product</h4>
                    <ul class="footer-links">
                        <li><a href="features.php">Features</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Resources</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="privacy.php">Privacy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">© 2025 Bright Steps. All rights reserved.</div>
        </div>
    </footer>

    <!-- Floating Theme Toggle -->
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

    <script src="scripts/language-toggle.js"></script>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>
    <script src="scripts/mobile-menu.js"></script>
</body>

</html>