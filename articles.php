<?php
session_start();
include 'connection.php';

// Check if user has child data for personalized content
$hasChild = false;
$childAge = 'preschool';
if (isset($_SESSION['id'])) {
    $stmt = $connect->prepare("SELECT birth_day, birth_month, birth_year FROM child WHERE parent_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($child) {
        $hasChild = true;
        $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
        $ageMonths = floor((time() - $bd) / (30.44 * 86400));
        if ($ageMonths < 12) $childAge = 'infant';
        elseif ($ageMonths < 24) $childAge = 'toddler';
        elseif ($ageMonths < 48) $childAge = 'preschool';
        else $childAge = 'school';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles & Tips - Bright Steps</title>
    <meta name="description" content="Expert parenting tips, child health guides, nutrition advice, and development activities from Bright Steps.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main, #f0f2ff); color: var(--text-primary, #1e293b); min-height: 100vh; }

        /* Header */
        .articles-header {
            background: linear-gradient(135deg, #6C63FF 0%, #a78bfa 50%, #c084fc 100%);
            padding: 3rem 1.5rem 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .articles-header::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
        }
        .header-nav {
            max-width: 1200px; margin: 0 auto 2rem; display: flex;
            justify-content: space-between; align-items: center; position: relative;
        }
        .header-nav a { color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 500; transition: color 0.2s; display: flex; align-items: center; gap: 0.5rem; }
        .header-nav a:hover { color: #fff; }
        .header-nav .logo { display: flex; align-items: center; gap: 0.5rem; }
        .header-nav .logo img { height: 2rem; }
        .header-content { position: relative; max-width: 700px; margin: 0 auto; }
        .header-content h1 { font-size: 2.5rem; font-weight: 800; color: #fff; margin-bottom: 0.75rem; }
        .header-content p { font-size: 1.1rem; color: rgba(255,255,255,0.85); line-height: 1.6; }

        /* Search */
        .search-bar {
            max-width: 600px; margin: 1.5rem auto 0; position: relative;
        }
        .search-bar input {
            width: 100%; padding: 0.9rem 1.2rem 0.9rem 3rem;
            background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.3);
            border-radius: 16px; font-size: 1rem; color: #fff; outline: none;
            backdrop-filter: blur(10px); transition: all 0.3s;
        }
        .search-bar input::placeholder { color: rgba(255,255,255,0.6); }
        .search-bar input:focus { background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.5); }
        .search-bar svg { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.6); }

        /* Filter Tabs */
        .filter-section {
            max-width: 1200px; margin: -1.5rem auto 0; padding: 0 1.5rem; position: relative; z-index: 2;
        }
        .filter-tabs {
            display: flex; gap: 0.3rem; overflow-x: auto; padding-bottom: 0;
            background: var(--bg-card, #fff); border-radius: 16px; padding: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid var(--border-color, #e2e8f0);
            justify-content: center; white-space: nowrap;
            -ms-overflow-style: none; scrollbar-width: none;
        }
        .filter-tabs::-webkit-scrollbar { display: none; }
        .filter-tab {
            padding: 0.5rem 0.9rem; border-radius: 12px; border: none; cursor: pointer;
            font-size: 0.82rem; font-weight: 600; white-space: nowrap;
            background: transparent; color: var(--text-secondary, #64748b); transition: all 0.2s;
            flex-shrink: 0;
        }
        .filter-tab:hover { background: var(--bg-main, #f8fafc); color: var(--text-primary); }
        .filter-tab.active { background: linear-gradient(135deg, #6C63FF, #a78bfa); color: #fff; box-shadow: 0 2px 8px rgba(108,99,255,0.3); }

        /* Content */
        .articles-content { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .section-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .section-title .emoji { font-size: 1.4rem; }

        /* Article Cards */
        .articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.25rem; margin-bottom: 3rem; }
        .article-card {
            background: var(--bg-card, #fff); border-radius: 20px;
            border: 1px solid var(--border-color, #e2e8f0);
            overflow: hidden; transition: all 0.3s ease;
            cursor: pointer; position: relative;
        }
        .article-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(108,99,255,0.12); border-color: rgba(108,99,255,0.3); }
        .article-card-header {
            padding: 1.5rem 1.5rem 0; display: flex; justify-content: space-between; align-items: flex-start;
        }
        .article-category {
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            padding: 0.3rem 0.7rem; border-radius: 20px;
        }
        .cat-health { background: #dcfce7; color: #16a34a; }
        .cat-nutrition { background: #fef3c7; color: #d97706; }
        .cat-development { background: #dbeafe; color: #2563eb; }
        .cat-parenting { background: #f3e8ff; color: #7c3aed; }
        .cat-hygiene { background: #e0e7ff; color: #4338ca; }
        .cat-safety { background: #fce7f3; color: #db2777; }
        .cat-education { background: #ccfbf1; color: #0d9488; }
        .cat-activities { background: #fff7ed; color: #ea580c; }
        .article-read-time { font-size: 0.75rem; color: var(--text-secondary, #94a3b8); display: flex; align-items: center; gap: 0.25rem; }
        .article-card-body { padding: 1rem 1.5rem 1.5rem; }
        .article-card-body h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.4; }
        .article-card-body p { font-size: 0.88rem; color: var(--text-secondary, #64748b); line-height: 1.6; }
        .article-card-footer {
            padding: 0.75rem 1.5rem; border-top: 1px solid var(--border-color, #f1f5f9);
            display: flex; justify-content: space-between; align-items: center;
        }
        .article-age-tag { font-size: 0.72rem; color: var(--text-secondary); background: var(--bg-main, #f8fafc); padding: 0.25rem 0.6rem; border-radius: 8px; }
        .article-read-more { font-size: 0.82rem; font-weight: 600; color: #6C63FF; text-decoration: none; transition: color 0.2s; }
        .article-read-more:hover { color: #5046e5; }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content h1 { font-size: 1.75rem; }
            .articles-grid { grid-template-columns: 1fr; }
            .filter-tabs { flex-wrap: nowrap; }
        }

        /* Dark Mode */
        [data-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        [data-theme="dark"] .articles-header { background: linear-gradient(135deg, #312e81 0%, #4c1d95 100%); }
        [data-theme="dark"] .article-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .filter-tabs { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .filter-tab { color: #94a3b8; }
        [data-theme="dark"] .filter-tab:hover { background: #334155; }
        /* Modal Styles */
        .article-modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 1rem;
        }
        .article-modal.active { display: flex; }
        .article-modal-content {
            background: var(--bg-card, #fff); width: 100%; max-width: 650px; max-height: 85vh;
            border-radius: 16px; overflow-y: auto; position: relative; padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: modalFadeIn 0.3s ease;
        }
        .article-modal-content h2 { margin-bottom: 1rem; color: var(--text-primary); }
        .article-modal-content p { color: var(--text-secondary); line-height: 1.7; margin-bottom: 1.5rem; }
        .article-modal-category { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.82rem; font-weight: 600; margin-bottom: 1rem; }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-close {
            position: absolute; top: 1.25rem; right: 1.5rem; background: none; border: none;
            font-size: 1.5rem; color: var(--text-secondary); cursor: pointer; transition: color 0.2s;
        }
        .modal-close:hover { color: #5046e5; }
        [data-theme="dark"] .article-modal-content { background: #1e293b; color: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>
    
    <!-- Header -->
    <header class="articles-header" style="padding-top: 8rem;">
        <div class="header-content">
            <h1>Articles & Tips <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -6px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></h1>
            <p>Expert guidance for every stage of your child's journey — from health and nutrition to activities and development.</p>
        </div>
        <div class="search-bar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="article-search" placeholder="Search articles..." oninput="filterArticles()">
        </div>
    </header>

    <!-- Filter Tabs -->
    <div class="filter-section">
        <div class="filter-tabs" id="filter-tabs">
            <button class="filter-tab active" data-filter="all" onclick="setFilter('all', this)">All Topics</button>
            <button class="filter-tab" data-filter="health" onclick="setFilter('health', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Health
            </button>
            <button class="filter-tab" data-filter="nutrition" onclick="setFilter('nutrition', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M22 12a10 10 0 0 1-10 10 10 10 0 0 1-10-10A10 10 0 0 1 12 2a10 10 0 0 1 10 10z"/><path d="M12 2v20"/><path d="M12 12h10"/></svg> Nutrition
            </button>
            <button class="filter-tab" data-filter="development" onclick="setFilter('development', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg> Development
            </button>
            <button class="filter-tab" data-filter="parenting" onclick="setFilter('parenting', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Parenting
            </button>
            <button class="filter-tab" data-filter="hygiene" onclick="setFilter('hygiene', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M12 22v-7l-2-2a4 4 0 1 1 5.66-5.66L18 9l-2-2a4 4 0 1 1 5.66-5.66l1.41 1.41"/><path d="M6.34 9.34A4 4 0 0 0 2 15h12c0-2.21-1.79-4-4-4H6.34z"/></svg> Hygiene
            </button>
            <button class="filter-tab" data-filter="safety" onclick="setFilter('safety', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Safety
            </button>
            <button class="filter-tab" data-filter="activities" onclick="setFilter('activities', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Activities
            </button>
            <button class="filter-tab" data-filter="education" onclick="setFilter('education', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-3px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg> Education
            </button>
        </div>
    </div>

    <!-- Articles Content -->
    <main class="articles-content" id="articles-content">
        <!-- Dynamic content loaded by JS -->
    </main>

    <script src="scripts/articles_data.js"></script>
    <script>
    let currentFilter = 'all';
    const childAge = '<?php echo $childAge; ?>';

    function getCategoryClass(cat) {
        return 'cat-' + cat;
    }

    function renderArticles(filteredArticles) {
        const container = document.getElementById('articles-content');
        if (filteredArticles.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:4rem 2rem;color:var(--text-secondary);"><div style="font-size:3rem;margin-bottom:1rem;">🔍</div><h3>No articles found</h3><p>Try adjusting your search or filters.</p></div>';
            return;
        }

        // Sort: show child-age-relevant articles first
        filteredArticles.sort((a, b) => {
            const aMatch = (a.ageGroup === childAge || a.ageGroup === 'all') ? 0 : 1;
            const bMatch = (b.ageGroup === childAge || b.ageGroup === 'all') ? 0 : 1;
            return aMatch - bMatch;
        });

        const ageLabels = { infant: '0-12 months', toddler: '1-2 years', preschool: '2-4 years', school: '4+ years', all: 'All ages' };

        container.innerHTML = `
            <h2 class="section-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px; vertical-align:-3px; color:var(--purple-500);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> ${currentFilter === 'all' ? 'All Articles' : filteredArticles[0]?.category.charAt(0).toUpperCase() + filteredArticles[0]?.category.slice(1)} (${filteredArticles.length})</h2>
            <div class="articles-grid">
                ${filteredArticles.map(a => `
                    <div class="article-card" data-category="${a.category}" onclick="openArticle(this)">
                        <div class="article-card-header">
                            <span class="article-category ${getCategoryClass(a.category)}">${a.category}</span>
                        </div>
                        <div class="article-card-body">
                            <h3>${a.title}</h3>
                            <p>${a.summary}</p>
                        </div>
                        <div class="article-card-footer">
                            <span class="article-age-tag"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:-2px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>${ageLabels[a.ageGroup] || 'All ages'}</span>
                            <span class="article-read-more">Read more →</span>
                        </div>
                    </div>
                `).join('')}
            </div>`;
    }

    function setFilter(filter, btn) {
        currentFilter = filter;
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        filterArticles();
    }

    function filterArticles() {
        const search = (document.getElementById('article-search')?.value || '').toLowerCase();
        let filtered = articles;
        if (currentFilter !== 'all') {
            filtered = filtered.filter(a => a.category === currentFilter);
        }
        if (search) {
            filtered = filtered.filter(a =>
                a.title.toLowerCase().includes(search) ||
                a.summary.toLowerCase().includes(search) ||
                a.category.toLowerCase().includes(search)
            );
        }
        renderArticles(filtered);
    }

    function openArticle(el) {
        // Add a subtle bounce animation to the button/card
        el.style.transform = 'scale(0.98)';
        setTimeout(() => el.style.transform = '', 150);
        
        const title = el.querySelector('h3').innerText;
        const articleData = articles.find(a => a.title === title);
        
        if (articleData && articleData.content) {
            document.getElementById('modalArticleBody').innerHTML = articleData.content;
        } else {
            document.getElementById('modalArticleBody').innerHTML = `
                <div style="padding:1rem;background:#fee2e2;color:#991b1b;border-radius:8px; margin-top: 1.5rem;">Article content not found.</div>
            `;
        }
        
        document.getElementById('articleModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeArticleModal(e) {
        if(e) e.stopPropagation();
        document.getElementById('articleModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Apply theme
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);

    // Initial render
    filterArticles();
    </script>
    
    <!-- Article Modal -->
    <div class="article-modal" id="articleModal" onclick="if(event.target === this) closeArticleModal()">
        <div class="article-modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeArticleModal()">✕</button>
            <div id="modalArticleBody"></div>
        </div>
    </div>

    <!-- Floating Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <?php include 'includes/public_footer.php'; ?>
    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>
</html>
