<?php
session_start();
// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['email']) && isset($_SESSION['id'])) {
    $role = $_SESSION['role'] ?? 'parent';
    switch ($role) {
        case 'admin':
            header("Location: admin-dashboard.php");
            exit();
        case 'specialist':
        case 'doctor':
            header("Location: doctor-dashboard.php");
            exit();
        case 'clinic':
            header("Location: dashboards/clinic/clinic-dashboard.php");
            exit();
        default:
            header("Location: dashboards/parent/dashboard.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright Steps - AI-Powered Child Development Monitoring</title>
    <meta name="description"
        content="Monitor your child's growth, speech, and development with AI-powered insights. Get personalized recommendations and early alerts.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
</head>

<body>
    <?php include 'includes/public_header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-text">
                    <span class="badge badge-purple">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        AI-Powered Development Tracking
                    </span>
                    <h1 class="hero-title">Your child's <span>bright future</span> starts here</h1>
                    <p class="hero-description">
                        Monitor your child's growth, speech, and development with AI-powered insights. Get personalized
                        recommendations and early alerts to ensure every step is a bright one.
                    </p>
                    <div class="hero-buttons">
                        <button class="btn btn-gradient btn-lg" onclick="navigateTo('signup')">Start Tracking
                            Free</button>
                        <button class="btn btn-outline btn-lg" onclick="navigateTo('demo')">Watch Demo</button>
                    </div>
                    <div class="hero-features">
                        <div class="feature-check">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            <span>Free 7-day trial</span>
                        </div>
                        <div class="feature-check">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            <span>No credit card required</span>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-card-wrapper">
                        <div class="hero-card">
                            <div class="card-profile">
                                <div class="profile-avatar"></div>
                                <div class="profile-info">
                                    <div class="profile-bar bar-long"></div>
                                    <div class="profile-bar bar-short"></div>
                                </div>
                            </div>
                            <div class="card-stats">
                                <div class="stat-item stat-green">
                                    <div class="stat-circle"></div>
                                    <div class="stat-bar"></div>
                                </div>
                                <div class="stat-item stat-blue">
                                    <div class="stat-circle"></div>
                                    <div class="stat-bar"></div>
                                </div>
                                <div class="stat-item stat-purple">
                                    <div class="stat-circle"></div>
                                    <div class="stat-bar"></div>
                                </div>
                            </div>
                            <div class="card-lines">
                                <div class="line line-full"></div>
                                <div class="line line-long"></div>
                                <div class="line line-medium"></div>
                            </div>
                        </div>
                        <div class="floating-badge badge-yellow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </div>
                        <div class="floating-badge badge-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sliding Information Carousel -->
    <section class="slider-section" style="padding: 5rem 1.5rem; background: var(--bg-secondary); overflow: hidden;">
        <div class="slider-container" style="max-width: 1280px; margin: 0 auto;">
            <div class="section-header" style="text-align: center; margin-bottom: 3.5rem;">
                <h2 class="section-title">Empowering Every Connection</h2>
                <p class="section-subtitle">Our platform adapts to provide vital insights and data for whoever needs it most.</p>
            </div>
            
            <div id="auto-carousel" class="carousel-track-wrapper" style="overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 2rem; -webkit-overflow-scrolling: touch; scrollbar-width: none; display: flex; gap: 2rem;">
                <!-- Slide 1 -->
                <div class="carousel-card" style="scroll-snap-align: center; width: 380px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); overflow: hidden; flex-shrink: 0; transition: transform 0.3s ease;">
                    <img src="assets/parent_info_graphic.png" style="width: 100%; height: 220px; object-fit: cover;" alt="Parents">
                    <div style="padding: 2rem;">
                        <h3 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">For Parents</h3>
                        <p style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6;">Track your child's milestones effortlessly with our AI-guided development tracking system and actionable growth alerts.</p>
                    </div>
                </div>
                <!-- Slide 2 -->
                <div class="carousel-card" style="scroll-snap-align: center; width: 380px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); overflow: hidden; flex-shrink: 0; transition: transform 0.3s ease;">
                    <img src="assets/doctor_info_graphic.png" style="width: 100%; height: 220px; object-fit: cover;" alt="Doctors">
                    <div style="padding: 2rem;">
                        <h3 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">For Doctors</h3>
                        <p style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6;">Access comprehensive, AI-analyzed reports instantly, enabling faster diagnosis and data-driven clinical treatments.</p>
                    </div>
                </div>
                <!-- Slide 3 -->
                <div class="carousel-card" style="scroll-snap-align: center; width: 380px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); overflow: hidden; flex-shrink: 0; transition: transform 0.3s ease;">
                    <img src="assets/clinic_info_graphic.png" style="width: 100%; height: 220px; object-fit: cover;" alt="Clinics">
                    <div style="padding: 2rem;">
                        <h3 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">For Clinics</h3>
                        <p style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6;">Boost operational efficiency and patient retention with our high-ROI clinic administration and direct booking suite.</p>
                    </div>
                </div>
            </div>
            
            <style>
                #auto-carousel::-webkit-scrollbar { display: none; }
                .carousel-card:hover { transform: translateY(-5px); }
            </style>
            
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    const track = document.getElementById("auto-carousel");
                    let dir = 1;
                    if(track) {
                        setInterval(() => {
                            if(track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) dir = -1;
                            else if(track.scrollLeft <= 10) dir = 1;
                            track.scrollBy({ left: 400 * dir, behavior: 'smooth' });
                        }, 3500);
                    }
                });
            </script>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Everything you need in one place</h2>
                <p class="section-subtitle">Comprehensive AI-powered tools for your child's development</p>
            </div>
            <div class="features-grid">
                <div class="feature-card card-blue">
                    <div class="feature-icon icon-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Growth Tracking</h3>
                    <p class="feature-description">Monitor height, weight, and head circumference against WHO standards
                        with AI-powered analytics and early alerts.</p>
                    <span class="feature-badge badge-blue">Always Free</span>
                </div>

                <div class="feature-card card-purple">
                    <div class="feature-icon icon-purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M12 2a10 10 0 0 1 10 10v1a3.5 3.5 0 0 1-6.39 1.97M2 12C2 6.48 6.48 2 12 2m0 18a10 10 0 0 1-10-10v-1a3.5 3.5 0 0 1 6.39-1.97M22 12c0 5.52-4.48 10-10 10" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Speech Analysis</h3>
                    <p class="feature-description">Upload voice recordings and get AI-driven evaluation of vocabulary,
                        pronunciation, and grammar development.</p>
                    <span class="feature-badge badge-purple">Premium</span>
                </div>

                <div class="feature-card card-green">
                    <div class="feature-icon icon-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="6" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Motor Skills</h3>
                    <p class="feature-description">AI analyzes activity videos to detect motor delays and provides
                        personalized exercises for improvement.</p>
                    <span class="feature-badge badge-green">Premium</span>
                </div>

                <div class="feature-card card-orange">
                    <div class="feature-icon icon-orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Smart Recommendations</h3>
                    <p class="feature-description">Get personalized daily and weekly activities, exercises, and
                        milestone checklists tailored to your child's age.</p>
                    <span class="feature-badge badge-orange">Premium</span>
                </div>

                <div class="feature-card card-pink">
                    <div class="feature-icon icon-pink">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Clinic Booking</h3>
                    <p class="feature-description">Book appointments with pediatricians and therapists directly. Share
                        progress reports with healthcare providers.</p>
                    <span class="feature-badge badge-pink">Premium</span>
                </div>

                <div class="feature-card card-cyan">
                    <div class="feature-icon icon-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Secure & Private</h3>
                    <p class="feature-description">Your child's data is encrypted and securely stored. You have complete
                        control over data sharing.</p>
                    <span class="feature-badge badge-cyan">All Plans</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Traffic Light System -->
    <section class="traffic-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Simple, clear insights you can trust</h2>
                <p class="section-subtitle">Our traffic-light system makes understanding development easy</p>
            </div>
            <div class="traffic-grid">
                <div class="traffic-card card-green-border">
                    <div class="traffic-icon icon-green-bg">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </div>
                    <h3 class="traffic-title">Green - On Track</h3>
                    <p class="traffic-description">Your child is meeting age-appropriate milestones. Keep up the great
                        work!</p>
                </div>

                <div class="traffic-card card-yellow-border">
                    <div class="traffic-icon icon-yellow-bg">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="6" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                    </div>
                    <h3 class="traffic-title">Yellow - Needs Attention</h3>
                    <p class="traffic-description">Some areas need monitoring. We'll provide exercises and activities to
                        help.</p>
                </div>

                <div class="traffic-card card-red-border">
                    <div class="traffic-icon icon-red-bg">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                    </div>
                    <h3 class="traffic-title">Red - Seek Help</h3>
                    <p class="traffic-description">We recommend consulting a healthcare professional. We'll help you
                        book an appointment.</p>
                </div>
            </div>
        </div>
    </section>



    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2 class="cta-title">Start your child's bright journey today</h2>
            <p class="cta-subtitle">Join thousands of parents who trust Bright Steps for their child's development</p>
            <button class="btn btn-gradient btn-lg btn-xl" onclick="navigateTo('signup')">Get Started Free - No Credit
                Card Required</button>
        </div>
    </section>

    <?php include 'includes/public_footer.php'; ?>



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

    <script src="scripts/language-toggle.js?v=9"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
    <script src="scripts/floating-emojis.js?v=8"></script>
</body>

</html>