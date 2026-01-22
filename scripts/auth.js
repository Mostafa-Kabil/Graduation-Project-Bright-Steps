// Authentication JavaScript
(function() {
    'use strict';

    // Handle login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Mock authentication - in real app, this would call an API
            if (email && password) {
                // Set authentication
                setAuth(true);
                
                // Set user data
                setUserData({
                    email: email,
                    name: 'Sarah Johnson',
                    isPremium: true
                });
                
                // Redirect to dashboard
                navigateTo('dashboard');
            }
        });
    }

    // Handle signup form
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;
            
            // Validation
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            
            if (!terms) {
                alert('Please agree to the Terms of Service and Privacy Policy');
                return;
            }
            
            // Mock authentication - in real app, this would call an API
            if (email && password && name) {
                // Set authentication
                setAuth(true);
                
                // Set user data
                setUserData({
                    email: email,
                    name: name,
                    isPremium: false
                });
                
                // Redirect to dashboard
                navigateTo('dashboard');
            }
        });
    }

    // Form validation feedback
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '' && this.hasAttribute('required')) {
                this.style.borderColor = 'var(--red-500)';
            } else {
                this.style.borderColor = 'var(--slate-200)';
            }
        });
        
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--blue-500)';
        });
    });

    // Password strength indicator (for signup)
    const passwordInput = document.getElementById('password');
    if (passwordInput && window.location.pathname.includes('signup')) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // You can add visual feedback here
            console.log('Password strength:', strength);
        });
    }

    // Toggle password visibility
    function addPasswordToggle() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            const wrapper = input.parentElement;
            // You can add a show/hide button here if needed
        });
    }
    
    addPasswordToggle();
})();
