// ─────────────────────────────────────────────────────────────
//  Bright Steps – Language Toggle  (En ↔ Ar — Offline Dictionary + API)
// ─────────────────────────────────────────────────────────────

const TRANSLATION_API = 'http://127.0.0.1:8001';
const BATCH_SIZE = 40;
const CACHE_KEY = 'brightStepsTranslationCache';

// Built-in offline dictionary for all common UI strings
const OFFLINE_DICTIONARY = {
    // Header / Nav
    'Doctor Portal': 'بوابة الطبيب',
    'Log In': 'تسجيل الدخول',
    'Get Started Free': 'ابدأ مجاناً',
    'عربي': 'EN',

    // Hero
    'AI-Powered Development Tracking': 'تتبع النمو بالذكاء الاصطناعي',
    "Your child's": 'مستقبل طفلك',
    'bright future': 'المشرق',
    'starts here': 'يبدأ هنا',
    'Monitor your child\'s growth, speech, and development with AI-powered insights. Get personalized recommendations and early alerts to ensure every step is a bright one.': 'راقب نمو طفلك والكلام والتطور مع رؤى مدعومة بالذكاء الاصطناعي. احصل على توصيات مخصصة وتنبيهات مبكرة لضمان أن كل خطوة مشرقة.',
    'Start Tracking Free': 'ابدأ التتبع مجاناً',
    'Watch Demo': 'شاهد العرض',
    'Free 7-day trial': 'تجربة مجانية لمدة 7 أيام',
    'No credit card required': 'لا تحتاج بطاقة ائتمان',

    // Features Section
    'Everything you need in one place': 'كل ما تحتاجه في مكان واحد',
    "Comprehensive AI-powered tools for your child's development": 'أدوات شاملة مدعومة بالذكاء الاصطناعي لتطور طفلك',
    'Growth Tracking': 'تتبع النمو',
    'Monitor height, weight, and head circumference against WHO standards with AI-powered analytics and early alerts.': 'راقب الطول والوزن ومحيط الرأس مقارنة بمعايير منظمة الصحة العالمية مع التحليلات والتنبيهات المبكرة.',
    'Always Free': 'مجاني دائماً',
    'Speech Analysis': 'تحليل الكلام',
    'Upload voice recordings and get AI-driven evaluation of vocabulary, pronunciation, and grammar development.': 'حمّل تسجيلات صوتية واحصل على تقييم بالذكاء الاصطناعي للمفردات والنطق وتطور القواعد.',
    'Premium': 'مميز',
    'Motor Skills': 'المهارات الحركية',
    'AI analyzes activity videos to detect motor delays and provides personalized exercises for improvement.': 'يحلل الذكاء الاصطناعي فيديوهات النشاط لاكتشاف التأخر الحركي ويقدم تمارين مخصصة للتحسين.',
    'Smart Recommendations': 'توصيات ذكية',
    "Get personalized daily and weekly activities, exercises, and milestone checklists tailored to your child's age.": 'احصل على أنشطة مخصصة يومية وأسبوعية وتمارين وقوائم معالم مصممة حسب عمر طفلك.',
    'Clinic Booking': 'حجز العيادة',
    'Book appointments with pediatricians and therapists directly. Share progress reports with healthcare providers.': 'احجز مواعيد مع أطباء الأطفال والمعالجين مباشرة. شارك تقارير التقدم مع مقدمي الرعاية الصحية.',
    'Secure & Private': 'آمن وخاص',
    "Your child's data is encrypted and securely stored. You have complete control over data sharing.": 'بيانات طفلك مشفرة ومخزنة بأمان. لديك تحكم كامل في مشاركة البيانات.',
    'All Plans': 'جميع الخطط',

    // Traffic Light
    'Simple, clear insights you can trust': 'رؤى بسيطة وواضحة يمكنك الوثوق بها',
    'Our traffic-light system makes understanding development easy': 'نظام إشارات المرور يجعل فهم التطور أمراً سهلاً',
    'Green - On Track': 'أخضر - على المسار',
    "Your child is meeting age-appropriate milestones. Keep up the great work!": 'طفلك يحقق المعالم المناسبة لعمره. واصل العمل الرائع!',
    'Yellow - Needs Attention': 'أصفر - يحتاج انتباه',
    "Some areas need monitoring. We'll provide exercises and activities to help.": 'بعض المجالات تحتاج مراقبة. سنقدم تمارين وأنشطة للمساعدة.',
    'Red - Seek Help': 'أحمر - اطلب المساعدة',
    "We recommend consulting a healthcare professional. We'll help you book an appointment.": 'نوصي باستشارة متخصص في الرعاية الصحية. سنساعدك في حجز موعد.',

    // Pricing
    'Simple, transparent pricing': 'أسعار بسيطة وشفافة',
    "Start free, upgrade when you're ready": 'ابدأ مجاناً، وترقّى عندما تكون جاهزاً',
    'Free Forever': 'مجاني للأبد',
    'Essential tracking for every parent': 'تتبع أساسي لكل والد',
    'Most Popular': 'الأكثر شعبية',
    'Complete AI-powered monitoring': 'مراقبة شاملة بالذكاء الاصطناعي',
    'Growth tracking & WHO comparisons': 'تتبع النمو ومقارنات منظمة الصحة العالمية',
    'Basic milestone checklists': 'قوائم معالم أساسية',
    'Traffic-light alerts': 'تنبيهات إشارات المرور',
    'Gamification & badges': 'التلعيب والشارات',
    'Everything in Free, plus:': 'كل ما في المجاني، بالإضافة إلى:',
    'AI speech & language analysis': 'تحليل الكلام واللغة بالذكاء الاصطناعي',
    'Motor skills video assessment': 'تقييم فيديو المهارات الحركية',
    'Personalized recommendations': 'توصيات مخصصة',
    'Doctor-ready PDF reports': 'تقارير PDF جاهزة للطبيب',
    'Clinic booking integration': 'تكامل حجز العيادة',
    'Start 7-Day Free Trial': 'ابدأ تجربة مجانية لمدة 7 أيام',

    // CTA
    "Start your child's bright journey today": 'ابدأ رحلة طفلك المشرقة اليوم',
    "Join thousands of parents who trust Bright Steps for their child's development": 'انضم لآلاف الآباء الذين يثقون بـ Bright Steps لتطور أطفالهم',
    'Get Started Free - No Credit Card Required': 'ابدأ مجاناً - لا حاجة لبطاقة ائتمان',

    // Footer
    'AI-powered child development monitoring for ages 0-5': 'مراقبة نمو الطفل بالذكاء الاصطناعي للأعمار 0-5',
    'Product': 'المنتج',
    'Features': 'الميزات',
    'Pricing': 'الأسعار',
    'Get Started': 'ابدأ',
    'Dashboard': 'لوحة القيادة',
    'Resources': 'الموارد',
    'Help Center': 'مركز المساعدة',
    'Guidelines': 'الإرشادات',
    'Privacy Policy': 'سياسة الخصوصية',
    'Terms of Service': 'شروط الخدمة',
    'Company': 'الشركة',
    'About Us': 'من نحن',
    'Contact': 'اتصل بنا',
    'For Clinics': 'للعيادات',
    'Careers': 'وظائف',
    '© 2025 Bright Steps. All rights reserved.': '© 2025 Bright Steps. جميع الحقوق محفوظة.',

    // Dashboard
    'Welcome back, Sarah! 👋': 'مرحباً بعودتك، سارة! 👋',
    "Here's Emma's progress today": 'إليك تقدم إيما اليوم',
    'Day Streak': 'أيام متتالية',
    'Badges': 'شارات',
    'Emma Johnson': 'إيما جونسون',
    '15 months old': 'عمرها 15 شهراً',
    'Born: Aug 23, 2024': 'تاريخ الميلاد: 23 أغسطس 2024',
    'Weight': 'الوزن',
    'Height': 'الطول',
    'Development Status': 'حالة التطور',
    'Height and weight are on track with WHO standards': 'الطول والوزن على المسار مع معايير منظمة الصحة العالمية',
    'On Track - Green': 'على المسار - أخضر',
    'Speech Development': 'تطور الكلام',
    'Vocabulary is developing. Continue daily practice': 'المفردات تتطور. واصل الممارسة اليومية',
    'Needs Attention - Yellow': 'يحتاج انتباه - أصفر',
    'Excellent progress in fine and gross motor skills': 'تقدم ممتاز في المهارات الحركية الدقيقة والكبيرة',
    "Today's Recommended Activities": 'الأنشطة الموصى بها اليوم',
    'Reading Time': 'وقت القراءة',
    'Read a picture book together. Point to objects and say their names clearly.': 'اقرأ كتاب صور معاً. أشر إلى الأشياء وقل أسماءها بوضوح.',
    'Stacking Blocks': 'تكديس المكعبات',
    'Practice hand-eye coordination by stacking colorful blocks together.': 'تدرب على التنسيق بين اليد والعين من خلال تكديس المكعبات الملونة.',
    'Outdoor Walk': 'المشي في الخارج',
    'Take a walk outside. Encourage walking on different surfaces.': 'خذ نزهة في الخارج. شجع المشي على أسطح مختلفة.',
    'Upcoming Appointments': 'المواعيد القادمة',
    'MMR Vaccination': 'تطعيم MMR',
    'Nov 28, 2025 at 10:00 AM': '28 نوفمبر 2025 الساعة 10:00 صباحاً',
    'Dr. Smith - City Pediatrics': 'د. سميث - طب الأطفال',
    '15-Month Checkup': 'فحص الـ 15 شهر',
    'Dec 15, 2025 at 2:30 PM': '15 ديسمبر 2025 الساعة 2:30 مساءً',
    'Dr. Johnson - Health Center': 'د. جونسون - المركز الصحي',
    "This Month's Progress": 'تقدم هذا الشهر',
    'Language': 'اللغة',
    'Social Skills': 'المهارات الاجتماعية',
    'Quick Actions': 'إجراءات سريعة',
    'Log Growth': 'تسجيل النمو',
    'Record Speech': 'تسجيل الكلام',
    'Add Activity': 'إضافة نشاط',
    'Book Clinic': 'حجز عيادة',

    // Sidebar
    'Home': 'الرئيسية',
    'Child Profile': 'ملف الطفل',
    'Growth': 'النمو',
    'Speech': 'الكلام',
    'Activities': 'الأنشطة',
    'Reports': 'التقارير',
    'Settings': 'الإعدادات',
    'Log Out': 'تسجيل الخروج',
    'Sarah Johnson': 'سارة جونسون',
    'Premium Member': 'عضو مميز',

    // Contact page
    'Contact Us': 'اتصل بنا',
    "We'd love to hear from you": 'نود أن نسمع منك',
    'Get In Touch': 'تواصل معنا',
    'Have questions, feedback, or need support? Reach out to us using the form below or contact us directly.': 'لديك أسئلة أو ملاحظات أو تحتاج دعم؟ تواصل معنا باستخدام النموذج أدناه أو اتصل بنا مباشرة.',
    'Your Name': 'اسمك',
    'Email Address': 'البريد الإلكتروني',
    'Subject': 'الموضوع',
    'Message': 'الرسالة',
    'Send Message': 'إرسال الرسالة',
    'Other Ways to Reach Us': 'طرق أخرى للتواصل معنا',

    // Demo page
    'Watch Demo': 'شاهد العرض',
    'See Bright Steps in Action': 'شاهد Bright Steps أثناء العمل',
    'Watch how our AI-powered platform helps parents track and support their child\'s development journey.': 'شاهد كيف تساعد منصتنا المدعومة بالذكاء الاصطناعي الآباء في تتبع ودعم رحلة تطور أطفالهم.',
    'Ready to start?': 'هل أنت مستعد للبدء؟',
    'Start Your Free Trial': 'ابدأ تجربتك المجانية',

    // About
    'About': 'من نحن',
    'Privacy': 'الخصوصية',

    // Settings
    'Manage your account preferences': 'إدارة تفضيلات حسابك',
    'Account': 'الحساب',
    'My Profile': 'ملفي الشخصي',
    'View and edit your personal information': 'عرض وتعديل معلوماتك الشخصية',
    "Manage your child's information": 'إدارة معلومات طفلك',
    'Change Password': 'تغيير كلمة المرور',
    'Update your account password': 'تحديث كلمة مرور حسابك',
    'Notifications': 'الإشعارات',
    'Push Notifications': 'إشعارات الدفع',
    'Receive activity reminders on your device': 'تلقي تذكيرات النشاط على جهازك',
    'Email Updates': 'تحديثات البريد الإلكتروني',
    'Weekly progress reports via email': 'تقارير تقدم أسبوعية عبر البريد الإلكتروني',
    'Appointment Reminders': 'تذكيرات المواعيد',
    'Get notified before scheduled appointments': 'احصل على إشعارات قبل المواعيد المحددة',
    'Preferences': 'التفضيلات',
    'Choose your preferred language': 'اختر لغتك المفضلة',
    'Data Sharing': 'مشاركة البيانات',
    'Share progress with healthcare providers': 'مشاركة التقدم مع مقدمي الرعاية الصحية',
    'Subscription': 'الاشتراك',
    'Manage Subscription': 'إدارة الاشتراك',

    // Logout modal
    'Are you sure you want to log out?': 'هل أنت متأكد من تسجيل الخروج؟',
    'You will need to sign in again to access your dashboard.': 'ستحتاج إلى تسجيل الدخول مرة أخرى للوصول إلى لوحة القيادة.',
    'Cancel': 'إلغاء',
    'Yes, Log Out': 'نعم، تسجيل الخروج',
};

// In-memory map: englishText → arabicText
let translationCache = {};

// Store original English texts so we can restore them
let originalTexts = new Map();

// ── Helpers ──────────────────────────────────────────────────

function loadCache() {
    try {
        const stored = localStorage.getItem(CACHE_KEY);
        if (stored) translationCache = JSON.parse(stored);
    } catch (_) { /* ignore corrupt cache */ }
    // Merge offline dictionary into cache (dictionary takes priority for known strings)
    Object.assign(translationCache, OFFLINE_DICTIONARY);
}

function saveCache() {
    try {
        localStorage.setItem(CACHE_KEY, JSON.stringify(translationCache));
    } catch (_) { /* quota exceeded – silently ignore */ }
}

function getTranslatableElements() {
    const SKIP_TAGS = new Set(['SCRIPT', 'STYLE', 'SVG', 'NOSCRIPT', 'CODE', 'PRE', 'IMG', 'INPUT', 'TEXTAREA', 'SELECT']);
    const elements = [];

    function walk(node) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            if (SKIP_TAGS.has(node.tagName)) return;
            if (node.classList && (node.classList.contains('language-toggle') || node.classList.contains('theme-toggle'))) return;

            let hasDirectText = false;
            for (const child of node.childNodes) {
                if (child.nodeType === Node.TEXT_NODE && child.textContent.trim().length > 0) {
                    hasDirectText = true;
                    break;
                }
            }

            if (hasDirectText) {
                elements.push(node);
            }

            for (const child of node.children) {
                walk(child);
            }
        }
    }

    walk(document.body);
    return elements;
}

function getDirectText(el) {
    let text = '';
    for (const child of el.childNodes) {
        if (child.nodeType === Node.TEXT_NODE) {
            text += child.textContent;
        }
    }
    return text.trim();
}

function setDirectText(el, newText) {
    const textNodes = [];
    for (const child of el.childNodes) {
        if (child.nodeType === Node.TEXT_NODE && child.textContent.trim().length > 0) {
            textNodes.push(child);
        }
    }
    if (textNodes.length === 1) {
        textNodes[0].textContent = newText;
    } else if (textNodes.length > 1) {
        textNodes[0].textContent = newText;
        for (let i = 1; i < textNodes.length; i++) {
            textNodes[i].textContent = '';
        }
    }
}

// ── Translation via API (with fallback) ────────────────────

async function translateBatch(texts) {
    try {
        const res = await fetch(`${TRANSLATION_API}/translate/batch`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ texts, source: 'en', target: 'ar' })
        });
        if (!res.ok) throw new Error(`API responded ${res.status}`);
        const data = await res.json();
        return data.translations.map(t => t.translated);
    } catch (err) {
        console.warn('[Translation API] Offline or error, using dictionary fallback.', err.message);
        return null;
    }
}

// ── Core: translate the entire page ──────────────────────────

async function translatePageToArabic() {
    const elements = getTranslatableElements();

    const allTexts = [];
    const needsApi = [];

    // Phase 1: Apply offline dictionary translations INSTANTLY (synchronous)
    elements.forEach((el, i) => {
        const text = getDirectText(el);
        allTexts.push(text);

        if (!text) return;

        if (!originalTexts.has(el)) {
            originalTexts.set(el, text);
        }

        if (translationCache[text]) {
            // Already cached (includes offline dictionary) — apply NOW
            setDirectText(el, translationCache[text]);
        } else {
            // Not in cache or dictionary — need API
            needsApi.push({ index: i, text, el });
        }
    });

    // Apply placeholder translations instantly
    document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(el => {
        const ph = el.getAttribute('placeholder');
        if (ph && translationCache[ph]) {
            el.setAttribute('data-original-placeholder', ph);
            el.setAttribute('placeholder', translationCache[ph]);
        }
    });

    // Phase 2: Fetch remaining strings from API in background (non-blocking)
    if (needsApi.length > 0) {
        showTranslationLoading(true);

        const textsToSend = needsApi.map(t => t.text);
        let apiSuccess = false;

        for (let start = 0; start < textsToSend.length; start += BATCH_SIZE) {
            const batch = textsToSend.slice(start, start + BATCH_SIZE);
            const translated = await translateBatch(batch);

            if (translated) {
                apiSuccess = true;
                batch.forEach((original, j) => {
                    translationCache[original] = translated[j];
                });
            }
        }

        if (apiSuccess) {
            saveCache();
            // Apply API translations to remaining elements
            needsApi.forEach(({ text, el }) => {
                if (translationCache[text] && document.body.contains(el)) {
                    setDirectText(el, translationCache[text]);
                }
            });
        }
        showTranslationLoading(false);
    }
}

function restorePageToEnglish() {
    originalTexts.forEach((originalText, el) => {
        if (document.body.contains(el)) {
            setDirectText(el, originalText);
        }
    });

    document.querySelectorAll('[data-original-placeholder]').forEach(el => {
        el.setAttribute('placeholder', el.getAttribute('data-original-placeholder'));
        el.removeAttribute('data-original-placeholder');
    });
}

// ── Loading indicator ────────────────────────────────────────

function showTranslationLoading(show) {
    let indicator = document.getElementById('translation-loading');
    if (show) {
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'translation-loading';
            indicator.innerHTML = 'جارٍ الترجمة... <span class="spinner"></span>';
            Object.assign(indicator.style, {
                position: 'fixed',
                top: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                color: '#fff',
                padding: '10px 24px',
                borderRadius: '30px',
                fontSize: '14px',
                fontWeight: '600',
                zIndex: '99999',
                boxShadow: '0 4px 20px rgba(99,102,241,0.4)',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                animation: 'fadeInDown 0.3s ease'
            });
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeInDown { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
                @keyframes spin { to { transform: rotate(360deg); } }
                #translation-loading .spinner {
                    display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.3);
                    border-top-color:#fff; border-radius:50%; animation: spin 0.6s linear infinite;
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(indicator);
        }
    } else {
        if (indicator) {
            indicator.style.animation = 'fadeInDown 0.3s ease reverse';
            setTimeout(() => indicator.remove(), 300);
        }
    }
}

// ── Public API ───────────────────────────────────────────────

function toggleLanguage() {
    const html = document.documentElement;
    const currentLang = html.getAttribute('lang') || 'en';

    if (currentLang === 'en') {
        html.setAttribute('lang', 'ar');
        html.setAttribute('dir', 'rtl');
        localStorage.setItem('language', 'ar');
        localStorage.setItem('direction', 'rtl');
        updateLanguageToggleButton('ar');
        translatePageToArabic();
    } else {
        html.setAttribute('lang', 'en');
        html.setAttribute('dir', 'ltr');
        localStorage.setItem('language', 'en');
        localStorage.setItem('direction', 'ltr');
        updateLanguageToggleButton('en');
        restorePageToEnglish();
    }
}

function updateLanguageToggleButton(lang) {
    document.querySelectorAll('.language-toggle').forEach(toggleBtn => {
        toggleBtn.innerHTML = lang === 'ar'
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>EN`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>عربي`;
    });
}

// ── Initialize on page load ──────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    loadCache();

    const savedLang = localStorage.getItem('language') || 'en';
    const savedDir = localStorage.getItem('direction') || 'ltr';

    document.documentElement.setAttribute('lang', savedLang);
    document.documentElement.setAttribute('dir', savedDir);
    updateLanguageToggleButton(savedLang);

    if (savedLang === 'ar') {
        translatePageToArabic();
    }
});
