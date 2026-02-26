<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bright Steps</title>
    <meta name="description" content="Learn about Bright Steps child development platform.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/landing.css">
</head>

<body>
    <header class="header">
        <div class="header-container">
            <div class="header-logo">
                <a href="index.html">
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

    <main class="page-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">About Bright Steps</h1>
                <p class="page-subtitle">Empowering parents with AI-powered insights</p>
            </div>

            <div class="content-section">
                <h2>Our Mission</h2>
                <p>Every child deserves the best start in life. We combine AI with pediatric research to give parents
                    clear insights about their child's growth, speech, and motor skills.</p>
            </div>

            <div class="content-section">
                <h2>Our Story</h2>
                <p>Founded by parents, pediatricians, and AI researchers who saw a gap in detecting developmental delays
                    early. We created Bright Steps to help parents track development continuously and spot issues early.
                </p>
            </div>

            <div class="content-section">
                <h2>What Sets Us Apart</h2>
                <ul>
                    <li>AI-Powered Insights from growth, speech, and motor skills analysis</li>
                    <li>WHO Standards for growth comparison</li>
                    <li>Easy traffic-light system (Green, Yellow, Red)</li>
                    <li>Privacy-first with industry-leading security</li>
                    <li>Expert-backed recommendations</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>Join Our Team</h2>
                <p>We're looking for passionate people in AI, pediatrics, design, and engineering.</p>
                <button class="btn btn-gradient btn-lg" onclick="navigateTo('contact')">View Positions</button>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <div class="footer-logo">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </div>
                    <p class="footer-text">AI-powered child development tracking for ages 0-5. Supporting every
                        milestone.</p>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Product</h4>
                    <ul class="footer-links">
                        <li><a href="features.php">Features</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                        <li><a href="signup.php">Get Started</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Resources</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="help.php">Guidelines</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="doctor-login.php">For Clinics</a></li>
                        <li><a href="about.php">Careers</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                © 2025 Bright Steps. All rights reserved.
            </div>
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