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
    <link rel="stylesheet" href="styles/globals.css">
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
            display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem;
            background: var(--bg-card, #fff); border-radius: 16px; padding: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid var(--border-color, #e2e8f0);
        }
        .filter-tab {
            padding: 0.6rem 1.2rem; border-radius: 12px; border: none; cursor: pointer;
            font-size: 0.85rem; font-weight: 600; white-space: nowrap;
            background: transparent; color: var(--text-secondary, #64748b); transition: all 0.2s;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="articles-header">
        <nav class="header-nav">
            <a href="index.php" class="logo"><img src="assets/logo.png" alt="Bright Steps"> Bright Steps</a>
            <?php if (isset($_SESSION['id'])): ?>
                <a href="dashboards/parent/dashboard.php">← Back to Dashboard</a>
            <?php else: ?>
                <a href="login.php">Sign In</a>
            <?php endif; ?>
        </nav>
        <div class="header-content">
            <h1>Articles & Tips 📚</h1>
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
            <button class="filter-tab" data-filter="health" onclick="setFilter('health', this)">🏥 Health</button>
            <button class="filter-tab" data-filter="nutrition" onclick="setFilter('nutrition', this)">🥗 Nutrition</button>
            <button class="filter-tab" data-filter="development" onclick="setFilter('development', this)">🧒 Development</button>
            <button class="filter-tab" data-filter="parenting" onclick="setFilter('parenting', this)">👪 Parenting</button>
            <button class="filter-tab" data-filter="hygiene" onclick="setFilter('hygiene', this)">🧼 Hygiene</button>
            <button class="filter-tab" data-filter="safety" onclick="setFilter('safety', this)">🛡️ Safety</button>
            <button class="filter-tab" data-filter="activities" onclick="setFilter('activities', this)">🎨 Activities</button>
            <button class="filter-tab" data-filter="education" onclick="setFilter('education', this)">📖 Education</button>
        </div>
    </div>

    <!-- Articles Content -->
    <main class="articles-content" id="articles-content">
        <!-- Dynamic content loaded by JS -->
    </main>

    <script>
    const articles = [
        // Health
        { title: "Vaccinations: The Complete Schedule", summary: "Everything parents need to know about childhood vaccination schedules, side effects, and why they're important.", category: "health", readTime: "6 min", ageGroup: "all" },
        { title: "When to Visit the Pediatrician", summary: "Know the warning signs — fever thresholds, breathing difficulties, and other symptoms that need immediate attention.", category: "health", readTime: "4 min", ageGroup: "all" },
        { title: "Building Strong Immune Systems", summary: "Simple daily habits that help strengthen your child's natural defenses against illness.", category: "health", readTime: "5 min", ageGroup: "all" },
        { title: "Common Childhood Allergies", summary: "How to identify, manage, and prevent allergic reactions in babies and children.", category: "health", readTime: "5 min", ageGroup: "all" },
        { title: "Healthy Sleep Habits by Age", summary: "How much sleep does your child need? Create the perfect bedtime routine for restful nights.", category: "health", readTime: "6 min", ageGroup: "all" },

        // Nutrition
        { title: "Introduction to Solid Foods", summary: "A step-by-step guide to transitioning from milk to solids, including first foods and allergen introduction.", category: "nutrition", readTime: "7 min", ageGroup: "infant" },
        { title: "Brain-Boosting Foods for Kids", summary: "The best foods for cognitive development — omega-3s, iron-rich meals, and antioxidant-packed snacks.", category: "nutrition", readTime: "5 min", ageGroup: "all" },
        { title: "Dealing with Picky Eaters", summary: "Proven strategies to expand your child's palate without forcing or bribing.", category: "nutrition", readTime: "4 min", ageGroup: "toddler" },
        { title: "Healthy Lunchbox Ideas", summary: "Quick, nutritious, and appealing lunch ideas that kids will actually eat at school.", category: "nutrition", readTime: "4 min", ageGroup: "school" },
        { title: "Meal Planning for Busy Parents", summary: "Time-saving tips for preparing nutritious family meals throughout the week.", category: "nutrition", readTime: "5 min", ageGroup: "all" },

        // Development
        { title: "Milestones: 0-12 Months", summary: "Track your baby's key development milestones — rolling over, crawling, first words, and more.", category: "development", readTime: "6 min", ageGroup: "infant" },
        { title: "Toddler Language Development", summary: "How to encourage speech and when to seek professional help for delayed language skills.", category: "development", readTime: "5 min", ageGroup: "toddler" },
        { title: "Building Fine Motor Skills", summary: "Fun activities that develop hand-eye coordination, grip strength, and dexterity.", category: "development", readTime: "4 min", ageGroup: "all" },
        { title: "Social Skills Through Play", summary: "How unstructured play, sharing games, and group activities build emotional intelligence.", category: "development", readTime: "5 min", ageGroup: "preschool" },
        { title: "School Readiness Checklist", summary: "Essential skills your child needs before starting school — cognitive, social, and physical.", category: "development", readTime: "6 min", ageGroup: "preschool" },

        // Parenting
        { title: "Positive Discipline Techniques", summary: "Effective ways to set boundaries while building trust and strengthening your bond.", category: "parenting", readTime: "6 min", ageGroup: "all" },
        { title: "Managing Screen Time", summary: "Age-appropriate screen time limits and how to choose quality digital content.", category: "parenting", readTime: "4 min", ageGroup: "all" },
        { title: "Building Confidence in Children", summary: "Everyday actions that foster self-esteem, resilience, and a growth mindset.", category: "parenting", readTime: "5 min", ageGroup: "all" },
        { title: "Sibling Rivalry Solutions", summary: "Practical strategies for reducing conflict and fostering strong sibling bonds.", category: "parenting", readTime: "5 min", ageGroup: "all" },
        { title: "Self-Care for Parents", summary: "You can't pour from an empty cup. Essential wellness tips for exhausted parents.", category: "parenting", readTime: "4 min", ageGroup: "all" },

        // Hygiene
        { title: "Teaching Handwashing", summary: "Make handwashing fun with songs, games, and visual timers that kids actually enjoy.", category: "hygiene", readTime: "3 min", ageGroup: "toddler" },
        { title: "First Dental Care Guide", summary: "When to start brushing, choosing the right toothbrush, and making dental hygiene a habit.", category: "hygiene", readTime: "4 min", ageGroup: "all" },
        { title: "Bath Time Routines", summary: "Safe bathing practices for every age, plus tips for making bath time relaxing and fun.", category: "hygiene", readTime: "3 min", ageGroup: "all" },
        { title: "Potty Training Made Easy", summary: "Signs of readiness, step-by-step approach, and how to handle setbacks gracefully.", category: "hygiene", readTime: "6 min", ageGroup: "toddler" },

        // Safety
        { title: "Childproofing Your Home", summary: "Room-by-room guide to making your home safe for curious crawlers and toddlers.", category: "safety", readTime: "5 min", ageGroup: "infant" },
        { title: "Sun Safety for Kids", summary: "Protecting your child's skin — sunscreen, protective clothing, and shade strategies.", category: "safety", readTime: "3 min", ageGroup: "all" },
        { title: "Online Safety for Children", summary: "How to protect your child online with parental controls, education, and open conversations.", category: "safety", readTime: "5 min", ageGroup: "school" },
        { title: "First Aid Basics for Parents", summary: "Essential first aid skills every parent should know — from minor cuts to choking emergencies.", category: "safety", readTime: "6 min", ageGroup: "all" },

        // Activities
        { title: "Sensory Play Ideas", summary: "Engaging sensory activities for different ages using household items — water, sand, playdough, and more.", category: "activities", readTime: "4 min", ageGroup: "all" },
        { title: "Indoor Activities for Rainy Days", summary: "20+ creative indoor games and projects that fight boredom and promote learning.", category: "activities", readTime: "5 min", ageGroup: "all" },
        { title: "Nature Walks & Scavenger Hunts", summary: "Turn outdoor walks into learning adventures with observation games and nature journaling.", category: "activities", readTime: "4 min", ageGroup: "preschool" },
        { title: "Music & Movement Games", summary: "Fun ways to use music, dance, and rhythm to boost coordination and cognitive skills.", category: "activities", readTime: "3 min", ageGroup: "all" },
        { title: "STEM Activities for Kids", summary: "Simple science experiments and engineering challenges that spark curiosity and critical thinking.", category: "activities", readTime: "5 min", ageGroup: "school" },

        // Education
        { title: "Reading Together: Tips by Age", summary: "How to make storytime engaging at every age — from board books to chapter books.", category: "education", readTime: "5 min", ageGroup: "all" },
        { title: "Learning Through Play", summary: "How play-based learning develops problem-solving, creativity, and social skills.", category: "education", readTime: "4 min", ageGroup: "all" },
        { title: "Teaching Numbers & Counting", summary: "Fun, everyday ways to introduce math concepts — counting stairs, sorting toys, and more.", category: "education", readTime: "4 min", ageGroup: "preschool" },
        { title: "Bilingual Kids: Benefits & Tips", summary: "How to raise a bilingual child and why it boosts cognitive development.", category: "education", readTime: "5 min", ageGroup: "all" }
    ];

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
            <h2 class="section-title"><span class="emoji">📖</span> ${currentFilter === 'all' ? 'All Articles' : filteredArticles[0]?.category.charAt(0).toUpperCase() + filteredArticles[0]?.category.slice(1)} (${filteredArticles.length})</h2>
            <div class="articles-grid">
                ${filteredArticles.map(a => `
                    <div class="article-card" data-category="${a.category}" onclick="openArticle(this)">
                        <div class="article-card-header">
                            <span class="article-category ${getCategoryClass(a.category)}">${a.category}</span>
                            <span class="article-read-time">⏱ ${a.readTime}</span>
                        </div>
                        <div class="article-card-body">
                            <h3>${a.title}</h3>
                            <p>${a.summary}</p>
                        </div>
                        <div class="article-card-footer">
                            <span class="article-age-tag">👶 ${ageLabels[a.ageGroup] || 'All ages'}</span>
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
        // Add a subtle bounce animation
        el.style.transform = 'scale(0.98)';
        setTimeout(() => el.style.transform = '', 150);
    }

    // Apply theme
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);

    // Initial render
    filterArticles();
    </script>
</body>
</html>
