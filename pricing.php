<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - Bright Steps</title>
    <meta name="description" content="Simple, transparent pricing for Bright Steps child development monitoring.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/landing.css">
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
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Simple, Transparent Pricing</h1>
            <p class="page-subtitle">Start free, upgrade when you're ready</p>
        </div>

        <div class="pricing-grid">
            <div class="pricing-card card-free">
                <h3 class="pricing-plan-title">Free Forever</h3>
                <p class="pricing-plan-subtitle">Essential tracking for every parent</p>
                <div class="pricing-amount">$0</div>
                <button class="btn btn-outline btn-lg btn-full" onclick="navigateTo('signup')">Get Started
                    Free</button>
                <div class="pricing-features">
                    <div class="pricing-feature">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Growth tracking & WHO comparisons</span>
                    </div>
                    <div class="pricing-feature">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Basic milestone checklists</span>
                    </div>
                    <div class="pricing-feature">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Traffic-light alerts</span>
                    </div>
                    <div class="pricing-feature">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Gamification & badges</span>
                    </div>
                    <div class="pricing-feature">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Single child profile</span>
                    </div>
                </div>
            </div>

            <div class="pricing-card card-premium">
                <div class="popular-badge">Most Popular</div>
                <h3 class="pricing-plan-title text-white">Premium</h3>
                <p class="pricing-plan-subtitle text-light">Complete AI-powered monitoring</p>
                <div class="pricing-amount text-white">
                    $9.99<span class="pricing-period">/month</span>
                </div>
                <button class="btn btn-white btn-lg btn-full" onclick="navigateTo('signup')">Start 7-Day Free
                    Trial</button>
                <div class="pricing-features">
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Everything in Free, plus:</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>AI speech & language analysis</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Motor skills video assessment</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Personalized recommendations</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Doctor-ready PDF reports</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Clinic booking integration</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Unlimited child profiles</span>
                    </div>
                    <div class="pricing-feature text-white">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Priority support</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section" style="margin-top: 3rem; text-align: center;">
            <h2>Frequently Asked Questions</h2>
            <p style="margin-bottom: 2rem;">Have questions? We've got answers.</p>
            <div style="text-align: left; max-width: 700px; margin: 0 auto;">
                <p><strong style="color: white;">Can I cancel anytime?</strong><br>Yes! You can cancel your Premium
                    subscription at any time with no penalties.</p>
                <p><strong style="color: white;">Is there a free trial?</strong><br>Premium comes with a 7-day free
                    trial. No credit card required to start.</p>
                <p><strong style="color: white;">What payment methods do you accept?</strong><br>We accept all major
                    credit cards, PayPal, and Apple Pay.</p>
            </div>
        </div>
    </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <div class="footer-logo">
                        <img src="assets/logo.png" alt="Bright Steps Logo">
                    </div>
                    <p class="footer-text">AI-powered child development monitoring for ages 0-5</p>
                </div>
                <div class="footer-column">
                    <h4 class="footer-heading">Product</h4>
                    <ul class="footer-links">
                        <li><a href="features.php">Features</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                        <li><a href="signup.php">Get Started</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
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
                        <li><a href="contact.php">For Clinics</a></li>
                        <li><a href="about.php">Careers</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                © 2025 Bright Steps. All rights reserved.
            </div>
        </div>
    </footer> <!-- Floating Theme Toggle -->
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