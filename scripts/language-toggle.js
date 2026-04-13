// ─────────────────────────────────────────────────────────────
//  Bright Steps – Language Toggle  (En ↔ Ar – Auto API Translation)
//  Translates ALL visible text using free Google Translate API.
//  Results are cached in localStorage for instant subsequent loads.
// ─────────────────────────────────────────────────────────────

const CACHE_KEY = 'brightStepsTranslations_v2';
const SKIP_TAGS = new Set(['SCRIPT', 'STYLE', 'SVG', 'NOSCRIPT', 'CODE', 'PRE', 'IMG', 'INPUT', 'TEXTAREA', 'SELECT', 'OPTION']);

// ── Arabic-Indic Numeral Conversion ──────────────────────────
const WESTERN_TO_ARABIC = { '0': '٠', '1': '١', '2': '٢', '3': '٣', '4': '٤', '5': '٥', '6': '٦', '7': '٧', '8': '٨', '9': '٩' };
function toArabicNumerals(text) {
    if (!text) return text;
    return text.replace(/[0-9]/g, d => WESTERN_TO_ARABIC[d]);
}

// ── Translation Cache (localStorage) ─────────────────────────
let cache = {};

function loadCache() {
    try {
        const stored = localStorage.getItem(CACHE_KEY);
        if (stored) cache = JSON.parse(stored);
    } catch (_) { }
}

function saveCache() {
    try {
        localStorage.setItem(CACHE_KEY, JSON.stringify(cache));
    } catch (_) { }
}

// ── Free Google Translate API ────────────────────────────────
async function translateText(text) {
    const normalized = text.replace(/\s+/g, ' ').trim();
    if (!normalized || normalized.length < 2) return text;

    // Check cache first
    if (cache[normalized]) return cache[normalized];

    try {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=${encodeURIComponent(normalized)}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        // Google returns [[["translated","original",...],...],...] 
        let translated = '';
        if (data && data[0]) {
            data[0].forEach(segment => {
                if (segment[0]) translated += segment[0];
            });
        }

        if (translated) {
            // Convert numbers to Arabic-Indic
            translated = toArabicNumerals(translated);
            cache[normalized] = translated;
            return translated;
        }
    } catch (err) {
        console.warn('[Translation] API error:', err.message);
    }

    return null; // translation failed
}

// Translate multiple texts (sequentially to avoid rate limiting)
async function translateTexts(texts) {
    const results = {};
    const toTranslate = texts.filter(t => {
        const norm = t.replace(/\s+/g, ' ').trim();
        if (cache[norm]) {
            results[norm] = cache[norm];
            return false;
        }
        return norm.length >= 2;
    });

    // Batch via concatenation with separator
    const BATCH_SIZE = 20;
    for (let i = 0; i < toTranslate.length; i += BATCH_SIZE) {
        const batch = toTranslate.slice(i, i + BATCH_SIZE);
        const separator = ' ||| ';
        const combined = batch.map(t => t.replace(/\s+/g, ' ').trim()).join(separator);

        try {
            const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=${encodeURIComponent(combined)}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            let fullTranslated = '';
            if (data && data[0]) {
                data[0].forEach(segment => {
                    if (segment[0]) fullTranslated += segment[0];
                });
            }

            if (fullTranslated) {
                const parts = fullTranslated.split('|||').map(s => s.trim());
                batch.forEach((original, idx) => {
                    const norm = original.replace(/\s+/g, ' ').trim();
                    if (parts[idx]) {
                        const translated = toArabicNumerals(parts[idx]);
                        cache[norm] = translated;
                        results[norm] = translated;
                    }
                });
            }
        } catch (err) {
            console.warn('[Translation] Batch error:', err.message);
            // Fallback: translate individually
            for (const text of batch) {
                const result = await translateText(text);
                if (result) {
                    const norm = text.replace(/\s+/g, ' ').trim();
                    results[norm] = result;
                }
            }
        }

        // Small delay between batches to avoid rate limiting
        if (i + BATCH_SIZE < toTranslate.length) {
            await new Promise(r => setTimeout(r, 100));
        }
    }

    saveCache();
    return results;
}

// ── DOM Walking ──────────────────────────────────────────────

// Store original content for restoration
const originalData = new WeakMap();

function getAllTextNodes() {
    const nodes = [];
    function walk(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            const trimmed = node.textContent.trim();
            if (trimmed.length > 0) nodes.push(node);
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            if (SKIP_TAGS.has(node.tagName)) return;
            if (node.classList && (
                node.classList.contains('language-toggle') ||
                node.classList.contains('sidebar-language-toggle') ||
                node.classList.contains('theme-toggle')
            )) return;
            for (const child of node.childNodes) walk(child);
        }
    }
    walk(document.body);
    return nodes;
}

// ── Core Translation ─────────────────────────────────────────

async function translatePageToArabic() {
    showTranslationLoading(true);

    const textNodes = getAllTextNodes();
    const uniqueTexts = new Set();

    // Collect all unique texts
    textNodes.forEach(node => {
        const norm = node.textContent.replace(/\s+/g, ' ').trim();
        if (norm.length >= 2) {
            // Store original
            if (!originalData.has(node)) {
                originalData.set(node, node.textContent);
            }
            uniqueTexts.add(norm);
        }
    });

    // Also collect placeholder texts
    const placeholderEls = document.querySelectorAll('input[placeholder], textarea[placeholder]');
    placeholderEls.forEach(el => {
        const ph = el.getAttribute('placeholder');
        if (ph && ph.trim().length >= 2) uniqueTexts.add(ph.trim());
    });

    // Phase 1: Apply cached translations instantly
    textNodes.forEach(node => {
        const norm = node.textContent.replace(/\s+/g, ' ').trim();
        if (cache[norm]) {
            if (!originalData.has(node)) originalData.set(node, node.textContent);
            
            const leadingSpace = node.textContent.match(/^\s*/)[0];
            const trailingSpace = node.textContent.match(/\s*$/)[0];
            node.textContent = leadingSpace + cache[norm] + trailingSpace;
        }
    });

    placeholderEls.forEach(el => {
        const ph = el.getAttribute('placeholder');
        if (ph && cache[ph.trim()]) {
            el.setAttribute('data-original-placeholder', ph);
            el.setAttribute('placeholder', cache[ph.trim()]);
        }
    });

    // Phase 2: Translate remaining via API
    const uncached = [...uniqueTexts].filter(t => !cache[t]);

    if (uncached.length > 0) {
        const translations = await translateTexts(uncached);

        // Apply translations
        textNodes.forEach(node => {
            const norm = node.textContent.replace(/\s+/g, ' ').trim();
            // Check if it's already translated or needs new translation
            if (translations[norm]) {
                if (!originalData.has(node)) originalData.set(node, node.textContent);
                
                const leadingSpace = node.textContent.match(/^\s*/)[0];
                const trailingSpace = node.textContent.match(/\s*$/)[0];
                node.textContent = leadingSpace + translations[norm] + trailingSpace;
            } else if (cache[norm] && originalData.has(node)) {
                // Already handled in Phase 1
            }
        });

        placeholderEls.forEach(el => {
            const ph = el.getAttribute('placeholder');
            if (ph && translations[ph.trim()]) {
                el.setAttribute('data-original-placeholder', ph);
                el.setAttribute('placeholder', translations[ph.trim()]);
            }
        });
    }

    // Convert any remaining Western numerals
    textNodes.forEach(node => {
        if (/[0-9]/.test(node.textContent)) {
            node.textContent = toArabicNumerals(node.textContent);
        }
    });

    showTranslationLoading(false);
}

function restorePageToEnglish() {
    // Restore text nodes
    const textNodes = getAllTextNodes();
    textNodes.forEach(node => {
        const original = originalData.get(node);
        if (original !== undefined) {
            node.textContent = original;
        }
    });

    // Restore placeholders
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
                position: 'fixed', top: '20px', left: '50%',
                transform: 'translateX(-50%)',
                background: 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                color: '#fff', padding: '10px 24px', borderRadius: '30px',
                fontSize: '14px', fontWeight: '600', zIndex: '99999',
                boxShadow: '0 4px 20px rgba(99,102,241,0.4)',
                display: 'flex', alignItems: 'center', gap: '8px',
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
    document.querySelectorAll('.language-toggle, .sidebar-language-toggle').forEach(btn => {
        btn.innerHTML = lang === 'ar'
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>EN`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>عربي`;
    });
}

/**
 * Re-translate dynamically injected content.
 * Called by dashboard.js / doctor-dashboard.js after innerHTML injection.
 */
function retranslateCurrentPage() {
    const lang = document.documentElement.getAttribute('lang') || 'en';
    if (lang === 'ar') {
        translatePageToArabic();
    }
}

// ── Initialize ───────────────────────────────────────────────

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
