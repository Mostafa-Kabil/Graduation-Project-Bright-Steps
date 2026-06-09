import os
import re

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# Fix 1: Card Counting (Use s.total instead of s.expected)
js_content = re.sub(
    r"if \(totalEl2\)\s*totalEl2\.textContent\s*=\s*s\.expected;",
    r"if (totalEl2)   totalEl2.textContent  = s.total;",
    js_content
)

# Fix 2: Move "Needs Attention" right above the bar
pillars = [
    ('attention', 'rgba(245,158,11,0.15)', 'linear-gradient(90deg,#f59e0b,#ef4444)'),
    ('communication', 'rgba(59,130,246,0.15)', 'linear-gradient(90deg,#3b82f6,#8b5cf6)'),
    ('social', 'rgba(16,185,129,0.15)', 'linear-gradient(90deg,#10b981,#06b6d4)'),
    ('motor', 'rgba(102,126,234,0.15)', 'linear-gradient(90deg,#667eea,#764ba2)'),
    ('fine_motor', 'rgba(236,72,153,0.15)', 'linear-gradient(90deg,#ec4899,#f43f5e)')
]

for p, bg, grad in pillars:
    bg_escaped = bg.replace('(', r'\(').replace(')', r'\)')
    header_regex = rf'(<h3[^>]*>.*?</h3>\s*<p[^>]*>.*?</p>\s*</div>)\s*<span id="alert-{p}"[^>]*>.*?</span>\s*(</div>\s*<div style="background:{bg_escaped})'
    
    match = re.search(header_regex, js_content, re.DOTALL)
    if match:
        new_header = match.group(1) + f'\n                        </div>\n                        <div style="display:flex; justify-content:flex-end; margin-bottom:0.25rem;">\n                            <span id="alert-{p}"></span>\n                        </div>\n                        <div style="background:{bg}'
        js_content = js_content.replace(match.group(0), new_header)

old_alert_style = """                alertEl.style.marginLeft = 'auto';
                
                alertEl.innerHTML = `<span style="font-size:0.5rem; line-height:1;">●</span><span style="font-size:0.7rem; font-weight:700; white-space:nowrap; line-height:1;">${status.label}</span>`;"""

new_alert_style = """                
                alertEl.innerHTML = `<span style="font-size:0.5rem; line-height:1;">●</span><span style="font-size:0.65rem; font-weight:700; white-space:nowrap; line-height:1;">${status.label}</span>`;"""
js_content = js_content.replace(old_alert_style, new_alert_style)


# Fix 3: Milestone Progress Bar Graph height fix
old_bar_cols = """<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.5rem;">"""
new_bar_cols = """<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.5rem;height:100%;justify-content:flex-end;">"""
js_content = js_content.replace(old_bar_cols, new_bar_cols)


# Fix 4: Milestone Journey
mock_journey_regex = r'<div id="milestone-journey-container".*?</div>\s*</div>\s*<div id="badges-container"'
js_content = re.sub(mock_journey_regex, '<div id="badges-container"', js_content, flags=re.DOTALL)

old_journey_func = """    // Update Milestone Journey badges (5 pillars now)
    function updateMilestoneJourney(stats) {
        const totalAchieved = stats.attention.achieved + stats.communication.achieved + stats.social.achieved + stats.motor.achieved + stats.fine_motor.achieved;
        const badgesContainer = document.getElementById('badges-container');

        if (!badgesContainer) return;

        // Calculate pillar-specific achievements (5 pillars)
        const pillarAchievements = {
            attention: stats.attention.achieved,
            communication: stats.communication.achieved,
            social: stats.social.achieved,
            motor: stats.motor.achieved,
            fine_motor: stats.fine_motor.achieved
        };

        let earnedBadges = [];
        badgeDefinitions.forEach(badge => {
            let earned = false;
            if (badge.pillar === 'all') {
                earned = totalAchieved >= badge.threshold;
            } else {
                earned = pillarAchievements[badge.pillar] >= badge.threshold;
            }
            if (earned) {
                earnedBadges.push(badge);
            }
        });

        // Render badges with unlock animation
        badgesContainer.innerHTML = earnedBadges.map(badge => `
            <div style="text-align:center;padding:0.75rem;background:rgba(245,158,11,0.15);border-radius:10px;animation:pulse 2s infinite;cursor:pointer;" title="${badge.name}: Achieved ${badge.threshold}+ milestones">
                <div style="font-size:1.75rem;margin-bottom:0.25rem;">${badge.icon}</div>
                <div style="font-size:0.7rem;font-weight:700;color:var(--text-color,#1e293b);">${badge.name}</div>
            </div>
        `).join('');
    }"""

new_journey_func = """    // Update Milestone Journey badges (5 pillars now)
    function updateMilestoneJourney(stats) {
        const totalAchieved = stats.attention.achieved + stats.communication.achieved + stats.social.achieved + stats.motor.achieved + stats.fine_motor.achieved;
        const badgesContainer = document.getElementById('badges-container');

        if (!badgesContainer) return;

        // Calculate pillar-specific achievements (5 pillars)
        const pillarAchievements = {
            attention: stats.attention.achieved,
            communication: stats.communication.achieved,
            social: stats.social.achieved,
            motor: stats.motor.achieved,
            fine_motor: stats.fine_motor.achieved
        };

        let allBadgesHTML = '';
        badgeDefinitions.forEach(badge => {
            let earned = false;
            if (badge.pillar === 'all') {
                earned = totalAchieved >= badge.threshold;
            } else {
                earned = pillarAchievements[badge.pillar] >= badge.threshold;
            }
            
            if (earned) {
                allBadgesHTML += `
                <div style="text-align:center;padding:0.75rem;background:rgba(245,158,11,0.15);border-radius:12px;animation:pulse 2s infinite;cursor:pointer;box-shadow:0 4px 6px rgba(0,0,0,0.05);" title="${badge.name}: Achieved ${badge.threshold}+ milestones">
                    <div style="font-size:1.75rem;margin-bottom:0.25rem;">${badge.icon}</div>
                    <div style="font-size:0.7rem;font-weight:700;color:var(--text-color,#1e293b);">${badge.name}</div>
                </div>`;
            } else {
                allBadgesHTML += `
                <div style="text-align:center;padding:0.75rem;background:var(--surface-light,#fff);border-radius:12px;opacity:0.6;border:1px dashed var(--slate-300,#cbd5e1);" title="Requires ${badge.threshold} milestones in ${badge.pillar}">
                    <div style="font-size:1.75rem;margin-bottom:0.25rem;filter:grayscale(100%);opacity:0.7;">${badge.icon}</div>
                    <div style="font-size:0.7rem;color:var(--slate-500,#64748b);font-weight:500;">${badge.name}</div>
                    <div style="font-size:0.55rem;color:var(--slate-400,#94a3b8);margin-top:2px;">🔒 ${badge.threshold} needed</div>
                </div>`;
            }
        });

        // Set grid layout to fit more nicely
        badgesContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(110px, 1fr))';
        badgesContainer.innerHTML = allBadgesHTML;
    }"""

js_content = js_content.replace(old_journey_func, new_journey_func)

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)
print("Applied all fixes to dashboard.js successfully.")
