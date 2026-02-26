<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bright Steps</title>
    <meta name="description" content="Get in touch with the Bright Steps team.">
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
                <h1 class="page-title">Contact Us</h1>
                <p class="page-subtitle">We'd love to hear from you</p>
            </div>

            <div class="content-section">
                <h2>Get In Touch</h2>
                <p>Have questions, feedback, or need support? Reach out to us using the form below or contact us
                    directly.</p>

                <form class="contact-form"
                    onsubmit="event.preventDefault(); alert('Thank you! We will get back to you soon.');">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required placeholder="How can we help?">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required placeholder="Tell us more..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-gradient btn-lg btn-full">Send Message</button>
                </form>
            </div>

            <div class="content-section">
                <h2>Other Ways to Reach Us</h2>
                <ul>
                    <li><strong>General Support:</strong> support@brightsteps.com</li>
                    <li><strong>Privacy Questions:</strong> privacy@brightsteps.com</li>
                    <li><strong>For Clinics & Partners:</strong> partners@brightsteps.com</li>
                    <li><strong>Press Inquiries:</strong> press@brightsteps.com</li>
                </ul>
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