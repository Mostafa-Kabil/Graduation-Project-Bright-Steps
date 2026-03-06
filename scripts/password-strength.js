/**
 * Password Strength Checker – Bright Steps
 * Provides real-time visual feedback on password strength.
 * Attach to any password input by setting its id to 'password'
 * and placing a <div id="password-strength"></div> after it.
 */
(function () {
    'use strict';

    function checkStrength(password) {
        let score = 0;
        const checks = {
            length8: password.length >= 8,
            length12: password.length >= 12,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
            noRepeat: !/(.)\1{2,}/.test(password),
        };

        if (checks.length8) score++;
        if (checks.length12) score++;
        if (checks.lowercase) score++;
        if (checks.uppercase) score++;
        if (checks.numbers) score++;
        if (checks.special) score++;
        if (checks.noRepeat) score++;

        let level, color, label, percent;
        if (password.length === 0) {
            return null;
        } else if (score <= 2) {
            level = 'weak';
            color = '#ef4444';
            label = 'Weak';
            percent = 25;
        } else if (score <= 4) {
            level = 'medium';
            color = '#f59e0b';
            label = 'Medium';
            percent = 55;
        } else if (score <= 5) {
            level = 'strong';
            color = '#22c55e';
            label = 'Strong';
            percent = 80;
        } else {
            level = 'very-strong';
            color = '#10b981';
            label = 'Very Strong';
            percent = 100;
        }

        return { level, color, label, percent, checks };
    }

    function createStrengthUI(container) {
        container.innerHTML = `
            <div class="pwd-strength-wrapper" style="margin-top:8px;">
                <div class="pwd-strength-bar-bg" style="
                    height:4px;
                    border-radius:4px;
                    background:rgba(255,255,255,0.08);
                    overflow:hidden;
                ">
                    <div class="pwd-strength-bar-fill" style="
                        height:100%;
                        width:0%;
                        border-radius:4px;
                        transition: width 0.4s ease, background 0.4s ease;
                    "></div>
                </div>
                <div class="pwd-strength-row" style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-top:4px;
                ">
                    <span class="pwd-strength-label" style="
                        font-size:12px;
                        font-weight:600;
                        letter-spacing:0.3px;
                        transition: color 0.3s ease;
                    "></span>
                    <div class="pwd-strength-checks" style="
                        display:flex;
                        gap:6px;
                        font-size:11px;
                        color:#64748b;
                    "></div>
                </div>
            </div>
        `;
    }

    function updateStrengthUI(container, result) {
        const fill = container.querySelector('.pwd-strength-bar-fill');
        const label = container.querySelector('.pwd-strength-label');
        const checksDiv = container.querySelector('.pwd-strength-checks');

        if (!result) {
            fill.style.width = '0%';
            fill.style.background = 'transparent';
            label.textContent = '';
            checksDiv.innerHTML = '';
            return;
        }

        fill.style.width = result.percent + '%';
        fill.style.background = result.color;
        label.textContent = result.label;
        label.style.color = result.color;

        const checkItems = [
            { key: 'uppercase', label: 'A-Z', ok: result.checks.uppercase },
            { key: 'lowercase', label: 'a-z', ok: result.checks.lowercase },
            { key: 'numbers', label: '0-9', ok: result.checks.numbers },
            { key: 'special', label: '#$!', ok: result.checks.special },
        ];

        checksDiv.innerHTML = checkItems.map(c =>
            `<span style="color:${c.ok ? '#22c55e' : '#64748b'}; transition:color 0.3s;">
                ${c.ok ? '✓' : '○'} ${c.label}
            </span>`
        ).join('');
    }

    function init() {
        // Find password inputs and their strength containers
        const pairs = [
            { input: 'password', container: 'password-strength' },
        ];

        pairs.forEach(({ input, container }) => {
            const inputEl = document.getElementById(input);
            const containerEl = document.getElementById(container);

            if (!inputEl || !containerEl) return;

            createStrengthUI(containerEl);

            inputEl.addEventListener('input', function () {
                const result = checkStrength(this.value);
                updateStrengthUI(containerEl, result);
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
