<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bright Steps</title>
    <meta name="description" content="Get in touch with the Bright Steps team.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
</head>

<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="page-content" style="padding-top: 1rem;">
        <!-- Modern Hero Section for Contact -->
        <section class="hero-section" style="padding: 6rem 0 3rem 0; background: transparent; overflow: hidden; position: relative;">
            <div class="hero-container" style="max-width: 1200px; margin: 0 auto; text-align: center; padding: 0 2rem;">
                <h1 class="hero-title" style="font-size: 3.5rem; line-height: 1.1; margin-bottom: 1rem;">
                    Let's <span style="background: linear-gradient(135deg, var(--blue-500), var(--purple-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">connect</span>
                </h1>
                <p class="hero-description" style="font-size: 1.125rem; margin: 0 auto; max-width: 600px;">
                    Whether you have a question about the platform, pricing, or just want to explore AI pediatric capabilities, our team is ready to answer all your questions.
                </p>
            </div>
        </section>

        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem; position: relative; z-index: 2;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; margin-bottom: 5rem; align-items: start;">
                
                <!-- Contact Information Column -->
                <div>
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 30px; padding: 3rem; margin-bottom: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05);">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 2rem;">Other ways to reach us</h2>
                        
                        <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1.5rem;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue-500)" stroke-width="2" style="width: 20px; height: 20px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">General Support</h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem;">support@brightsteps.com</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1.5rem;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(168,85,247,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--purple-500)" stroke-width="2" style="width: 20px; height: 20px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </div>
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Privacy & Data</h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem;">privacy@brightsteps.com</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--green-500)" stroke-width="2" style="width: 20px; height: 20px;"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                            </div>
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">For Clinics & Partners</h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem;">partners@brightsteps.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form Column -->
                <div>
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 30px; padding: 3rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);">
                        <form class="contact-form" id="contact-form" onsubmit="event.preventDefault(); submitContact();" style="max-width: 100%;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="name" style="font-size: 0.9rem;">Your Name</label>
                                    <input type="text" id="name" name="name" required placeholder="John Doe" style="border-radius: 12px; padding: 1rem; background: var(--bg-primary);">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="email" style="font-size: 0.9rem;">Email Address</label>
                                    <input type="email" id="email" name="email" required placeholder="john@example.com" style="border-radius: 12px; padding: 1rem; background: var(--bg-primary);">
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="subject" style="font-size: 0.9rem;">Subject</label>
                                <input type="text" id="subject" name="subject" required placeholder="How can we help?" style="border-radius: 12px; padding: 1rem; background: var(--bg-primary);">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="message" style="font-size: 0.9rem;">Message</label>
                                <textarea id="message" name="message" required placeholder="Tell us more about your inquiry..." style="border-radius: 12px; padding: 1rem; min-height: 150px; background: var(--bg-primary);"></textarea>
                            </div>
                            
                            <button type="submit" id="contact-btn" class="btn btn-gradient btn-lg btn-full" style="border-radius: 12px;">Send Message</button>
                            <div id="contact-status" style="margin-top:1rem;text-align:center;font-size:0.9rem;"></div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

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

    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script>
        async function submitContact() {
            const btn = document.getElementById('contact-btn');
            const status = document.getElementById('contact-status');
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (!name || !email || !subject || !message) {
                status.innerHTML = '<span style="color:#ef4444;">Please fill in all fields.</span>';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Sending...';
            status.innerHTML = '';
            
            try {
                const res = await fetch('api_contact.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, subject, message })
                });
                const data = await res.json();
                if (data.success) {
                    status.innerHTML = '<span style="color:#22c55e;">✅ ' + data.message + '</span>';
                    document.getElementById('contact-form').reset();
                } else {
                    status.innerHTML = '<span style="color:#ef4444;">❌ ' + (data.error || 'Something went wrong') + '</span>';
                }
            } catch (e) {
                status.innerHTML = '<span style="color:#ef4444;">❌ Network error. Please try again.</span>';
            }
            
            btn.disabled = false;
            btn.textContent = 'Send Message';
        }
    </script>
</body>

</html>