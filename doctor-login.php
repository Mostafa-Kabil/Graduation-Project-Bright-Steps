<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/auth.css">
</head>

<body>
    <div class="auth-page">
        <button class="back-button" onclick="navigateTo('index')">
            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Back
        </button>

        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <a href="index.php" class="auth-logo">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </a>
                    <span class="doctor-badge-small">Healthcare Provider</span>
                    <h1 class="auth-title">Doctor Portal</h1>
                    <p class="auth-subtitle">Access patient development reports and recommendations</p>
                </div>

                <form id="doctor-login-form" class="auth-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Professional Email</label>
                        <input type="email" id="email" class="form-input" placeholder="doctor@clinic.com" required>
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label" for="password">Password</label>
                            <a href="#" class="form-link">Forgot?</a>
                        </div>
                        <input type="password" id="password" class="form-input" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-gradient btn-lg btn-full">Sign In to Portal</button>
                </form>

                <div class="auth-footer">
                    <span class="auth-footer-text">Need a provider account? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('doctor-signup'); return false;">Register</a>
                </div>

                <div class="auth-divider">
                    <span>Or</span>
                </div>

                <div class="parent-login-link">
                    <span>Looking for parent login? </span>
                    <a href="#" class="auth-link" onclick="navigateTo('login'); return false;">Parent Portal</a>
                </div>
            </div>
        </div>
    </div>

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

    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>
    <script src="scripts/language-toggle.js"></script>

    <script>
        document.getElementById('doctor-login-form').addEventListener('submit', function (e) {
            e.preventDefault();
            window.location.href = 'doctor-dashboard.php';
        });
    </script>
</body>

</html>