import os
import re

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# Replace the AI Analysis per pillar loop
old_ai_loop = """        // AI Analysis per pillar - using stats.expected (age-appropriate benchmark)
        ['attention', 'communication', 'social', 'motor'].forEach(pillar => {
            const s = stats[pillar];
            if (!s || s.expected === 0) return;

            // Calculate percentage based on age expectation
            const pct = Math.round((s.achieved / s.expected) * 100);
            const status = getTrafficLightStatus(pct);
            const expectation = ageExpectations[pillar][ageRange];

            // Generate AI analysis based on actual data patterns
            if (status.icon === '🟢') {
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #10b981;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#10b981;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(16,185,129,0.3);">✓</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillar.charAt(0).toUpperCase() + pillar.slice(1)}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} demonstrates ${s.achieved}/${s.expected} age-expected skills. ${expectation ? expectation.description + '.' : 'Healthy progress.'}</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'strength', detail: `Mastered ${s.achieved}/${s.expected} age-expected skills` });
            } else if (status.icon === '🟡') {
                const gap = s.expected - s.achieved;
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #f59e0b;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#f59e0b;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(245,158,11,0.3);">⚠</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillar.charAt(0).toUpperCase() + pillar.slice(1)}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} shows ${s.achieved}/${s.expected} age-expected skills. ${gap > 0 ? gap + ' skills emerging.' : ''} Recommend targeted play activities.</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'developing', detail: `${gap} skills below age expectation; focus area identified` });
            } else {
                const gap = s.expected - s.achieved;
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #ef4444;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#ef4444;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(239,68,68,0.3);">✕</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillar.charAt(0).toUpperCase() + pillar.slice(1)}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} has ${s.achieved}/${s.expected} age-expected skills. ${gap} skills below expectation. Suggest daily structured activities.</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'concern', detail: `Significant gap (${gap} skills); early intervention may benefit` });
            }
        });

        // Overall AI synthesis - based on age expectations
        let totalExpected = 0;
        let totalAchieved = 0;
        Object.values(stats).forEach(s => {
            if (s && s.expected) {
                totalExpected += s.expected;
                totalAchieved += s.achieved;
            }
        });
        const overallPercent = totalExpected > 0 ? Math.round((totalAchieved / totalExpected) * 100) : 0;"""

new_ai_loop = """        // AI Analysis per pillar
        ['attention', 'communication', 'social', 'motor', 'fine_motor'].forEach(pillar => {
            const s = stats[pillar];
            if (!s || s.total === 0) return;

            // Calculate percentage based on checklist total
            const pct = Math.round((s.achieved / s.total) * 100);
            const status = getTrafficLightStatus(pct);
            const expectation = ageExpectations[pillar] && ageExpectations[pillar][ageRange] ? ageExpectations[pillar][ageRange] : {description: 'steady progress expected'};

            let pillarName = pillar.charAt(0).toUpperCase() + pillar.slice(1).replace('_', ' ');

            // Generate AI analysis based on actual data patterns
            if (status.icon === '🟢') {
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #10b981;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#10b981;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(16,185,129,0.3);">✓</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillarName}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} demonstrates ${s.achieved}/${s.total} targeted skills. ${expectation ? expectation.description + '.' : 'Healthy progress.'}</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'strength', detail: `Mastered ${s.achieved}/${s.total} targeted skills` });
            } else if (status.icon === '🟡') {
                const gap = s.total - s.achieved;
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #f59e0b;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#f59e0b;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(245,158,11,0.3);">⚠</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillarName}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} shows ${s.achieved}/${s.total} targeted skills. ${gap > 0 ? gap + ' skills still emerging.' : ''} Recommend targeted play activities.</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'developing', detail: `${gap} targeted skills emerging; focus area identified` });
            } else {
                const gap = s.total - s.achieved;
                messages.push(`<div style="background:rgba(255,255,255,0.1);border-radius:12px;padding:1.25rem;border-left:4px solid #ef4444;backdrop-filter:blur(4px);box-shadow:0 4px 12px rgba(0,0,0,0.05);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        <span style="background:#ef4444;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 4px rgba(239,68,68,0.3);">✕</span>
                        <strong style="font-size:1.05rem;color:#fff;letter-spacing:0.3px;">${pillarName}</strong>
                        <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:99px;font-size:0.75rem;font-weight:700;margin-left:auto;border:1px solid rgba(255,255,255,0.3);">${pct}%</span>
                    </div>
                    <div style="font-size:0.9rem;opacity:0.9;line-height:1.5;">${name} has ${s.achieved}/${s.total} targeted skills. ${gap} skills below expectation. Suggest daily structured activities.</div>
                </div>`);
                analysisPoints.push({ pillar, status: 'concern', detail: `Significant gap (${gap} skills); early intervention may benefit` });
            }
        });

        // Overall AI synthesis
        let totalTarget = 0;
        let totalAchieved = 0;
        Object.values(stats).forEach(s => {
            if (s && s.total) {
                totalTarget += s.total;
                totalAchieved += s.achieved;
            }
        });
        const overallPercent = totalTarget > 0 ? Math.round((totalAchieved / totalTarget) * 100) : 0;"""

js_content = js_content.replace(old_ai_loop, new_ai_loop)

# Fix aiSummary formatting slightly to mention 5 areas instead of length dynamically, actually the length logic is fine.
# Let's write back
with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print("Fixed updateEmpathyFeedback logic successfully.")
