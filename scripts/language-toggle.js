// Language toggle functionality
function toggleLanguage() {
    const html = document.documentElement;
    const currentLang = html.getAttribute('lang') || 'en';

    if (currentLang === 'en') {
        html.setAttribute('lang', 'ar');
        html.setAttribute('dir', 'rtl');
        localStorage.setItem('language', 'ar');
        localStorage.setItem('direction', 'rtl');
        updateLanguageToggleButton('ar');
        translatePage('ar');
    } else {
        html.setAttribute('lang', 'en');
        html.setAttribute('dir', 'ltr');
        localStorage.setItem('language', 'en');
        localStorage.setItem('direction', 'ltr');
        updateLanguageToggleButton('en');
        translatePage('en');
    }
}

function updateLanguageToggleButton(lang) {
    const toggleBtn = document.querySelector('.language-toggle');
    if (toggleBtn) {
        toggleBtn.innerHTML = lang === 'ar'
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>EN`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>عربي`;
    }
}

// Comprehensive Arabic translations
const translations = {
    en: {
        // Navigation
        'Log In': 'Log In',
        'Get Started Free': 'Get Started Free',
        'Doctor Portal': 'Doctor Portal',
        'About Us': 'About Us',
        'Features': 'Features',
        'Pricing': 'Pricing',
        'Contact': 'Contact',
        // Hero
        'AI-Powered Development Tracking': 'AI-Powered Development Tracking',
        'Start Tracking Free': 'Start Tracking Free',
        'Watch Demo': 'Watch Demo',
        'Free 7-day trial': 'Free 7-day trial',
        'No credit card required': 'No credit card required',
        // Features
        'Premium': 'Premium',
        'All Plans': 'All Plans',
        'Growth Tracking': 'Growth Tracking',
        'Speech Analysis': 'Speech Analysis',
        'Smart Recommendations': 'Smart Recommendations',
        'Clinic Booking': 'Clinic Booking',
        'Secure & Private': 'Secure & Private',
        // Footer
        'Privacy Policy': 'Privacy Policy',
        'Terms of Service': 'Terms of Service',
        'Help Center': 'Help Center'
    },
    ar: {
        // Navigation
        'Log In': 'تسجيل الدخول',
        'Get Started Free': 'ابدأ مجاناً',
        'Doctor Portal': 'بوابة الطبيب',
        'About Us': 'من نحن',
        'Features': 'المميزات',
        'Pricing': 'الأسعار',
        'Contact': 'اتصل بنا',
        // Hero
        'AI-Powered Development Tracking': 'تتبع النمو المدعوم بالذكاء الاصطناعي',
        'Start Tracking Free': 'ابدأ التتبع مجاناً',
        'Watch Demo': 'شاهد العرض',
        'Free 7-day trial': 'تجربة مجانية لمدة 7 أيام',
        'No credit card required': 'لا حاجة لبطاقة ائتمان',
        // Features
        'Premium': 'مميز',
        'All Plans': 'جميع الخطط',
        'Growth Tracking': 'تتبع النمو',
        'Speech Analysis': 'تحليل الكلام',
        'Smart Recommendations': 'توصيات ذكية',
        'Clinic Booking': 'حجز العيادة',
        'Secure & Private': 'آمن وخاص',
        // Footer
        'Privacy Policy': 'سياسة الخصوصية',
        'Terms of Service': 'شروط الخدمة',
        'Help Center': 'مركز المساعدة'
    }
};

function translatePage(lang) {
    // Translate data-translate elements
    document.querySelectorAll('[data-translate]').forEach(el => {
        const key = el.getAttribute('data-translate');
        if (translations[lang][key]) {
            el.textContent = translations[lang][key];
        }
    });

    // Also try to translate buttons and links by text content
    const textMappings = translations[lang];
    const reverseMap = lang === 'ar' ? translations.en : translations.ar;

    document.querySelectorAll('a, button, span, h1, h2, h3, p').forEach(el => {
        const text = el.textContent.trim();
        // Check if text matches a known key in reverse map (opposite language)
        for (const [key, value] of Object.entries(reverseMap)) {
            if (text === value || text === key) {
                if (textMappings[key]) {
                    el.textContent = textMappings[key];
                }
                break;
            }
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    const savedLang = localStorage.getItem('language') || 'en';
    const savedDir = localStorage.getItem('direction') || 'ltr';

    document.documentElement.setAttribute('lang', savedLang);
    document.documentElement.setAttribute('dir', savedDir);
    updateLanguageToggleButton(savedLang);

    if (savedLang === 'ar') {
        translatePage('ar');
    }
});
