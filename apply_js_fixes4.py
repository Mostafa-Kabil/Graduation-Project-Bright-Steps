import os
import re

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()


# 1. Fix the percentage calculation to use s.total instead of s.expected
js_content = js_content.replace(
    "const percent = s.expected > 0 ? Math.min(Math.round((s.achieved / s.expected) * 100), 100) : 0;",
    "const percent = s.total > 0 ? Math.round((s.achieved / s.total) * 100) : 0;"
)

# Also fix the bar chart percentage calculation
js_content = js_content.replace(
    "const pct = s.expected > 0 ? Math.min(Math.round((s.achieved / s.expected) * 100), 100) : 0;",
    "const pct = s.total > 0 ? Math.round((s.achieved / s.total) * 100) : 0;"
)


# 2. Fix the missing text in earned achievements
# In the previous step, updateMilestoneJourney had this for earned badges:
# <div style="font-size:1.5rem;">${badge.icon}</div>
# <div style="font-size:0.65rem;color:var(--slate-600,#475569);font-weight:600;">${badge.name}</div>
# Let's replace the whole updateMilestoneJourney function correctly this time!

old_journey_func_regex = r"// Update Milestone Journey badges \(5 pillars now\)\s*function updateMilestoneJourney\(stats\) \{.*?\n    \}"

new_journey_func = """// Update Milestone Journey badges (5 pillars now)
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
                    <div style="font-size:0.55rem;color:#f59e0b;margin-top:2px;font-weight:bold;">✅ Achieved!</div>
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

js_content = re.sub(old_journey_func_regex, new_journey_func, js_content, flags=re.DOTALL)


# 3. Move the Needs Attention text slightly to the right
# We added this wrapper earlier: <div style="display:flex; justify-content:flex-end; margin-bottom:0.25rem;">
# Let's just modify the wrapper to add a negative right margin to push it slightly right to align perfectly visually.
js_content = js_content.replace(
    '<div style="display:flex; justify-content:flex-end; margin-bottom:0.25rem;">',
    '<div style="display:flex; justify-content:flex-end; margin-bottom:0.25rem; margin-right:-6px;">'
)

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)
print("Applied fixes successfully.")
