// ─────────────────────────────────────────────────────────────
//  Bright Steps – Mobile Menu (Hamburger)
// ─────────────────────────────────────────────────────────────

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-menu-overlay');
    const hamburger = document.getElementById('hamburger-btn');

    if (!menu) return;

    const isOpen = menu.classList.contains('open');

    if (isOpen) {
        menu.classList.remove('open');
        overlay.classList.remove('open');
        hamburger.classList.remove('open');
        document.body.style.overflow = '';
    } else {
        menu.classList.add('open');
        overlay.classList.add('open');
        hamburger.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

// Close menu when clicking a nav item
document.addEventListener('click', (e) => {
    if (e.target.closest('#mobile-menu .mobile-nav-item') || e.target.closest('#mobile-menu-overlay')) {
        const menu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-menu-overlay');
        const hamburger = document.getElementById('hamburger-btn');
        if (menu && menu.classList.contains('open')) {
            menu.classList.remove('open');
            overlay.classList.remove('open');
            if (hamburger) hamburger.classList.remove('open');
            document.body.style.overflow = '';
        }
    }
});
