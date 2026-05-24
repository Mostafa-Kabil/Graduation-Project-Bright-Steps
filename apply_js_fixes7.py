import os

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# Fix the string assignments for aiSummary and focusAreas

old_ai_summary_block = """        let aiSummary = '';
        if (strengths.length >= 3) {
            aiSummary = `${name} is meeting or exceeding age expectations across ${strengths.length}/${strengths.length + developing.length + concerns.length} domains. Continue current enrichment activities.`;
        } else if (concerns.length === 0) {
            aiSummary = `${name} is progressing well overall (${overallPercent}% of age-expected skills). Focus on ${developing.map(d => d.pillar).join(' & ')} for continued growth.`;
        } else if (concerns.length <= 1) {
            aiSummary = `${name} shows ${overallPercent}% of age-expected development. ${concerns[0]?.pillar || 'one area'} identified as focus area. Early enrichment recommended.`;
        } else {
            aiSummary = `Multiple domains (${concerns.map(c => c.pillar).join(', ')}) show delays relative to age expectations. Recommend: developmental pediatrician consultation + structured daily activities.`;
        }"""

new_ai_summary_block = """        const formatPillarName = (p) => p === 'fine_motor' ? 'Fine Motor' : p.charAt(0).toUpperCase() + p.slice(1);
        
        let aiSummary = '';
        if (strengths.length >= 3) {
            aiSummary = `${name} is meeting or exceeding expectations across ${strengths.length}/${strengths.length + developing.length + concerns.length} domains. Continue current enrichment activities.`;
        } else if (concerns.length === 0) {
            aiSummary = `${name} is progressing well overall (${overallPercent}% of targeted skills). Focus on ${developing.map(d => formatPillarName(d.pillar)).join(' & ')} for continued growth.`;
        } else if (concerns.length <= 1) {
            aiSummary = `${name} shows ${overallPercent}% of targeted development. ${concerns[0] ? formatPillarName(concerns[0].pillar) : 'one area'} identified as focus area. Early enrichment recommended.`;
        } else {
            aiSummary = `Multiple domains (${concerns.map(c => formatPillarName(c.pillar)).join(', ')}) show delays relative to expectations. Recommend: developmental pediatrician consultation + structured daily activities.`;
        }"""

js_content = js_content.replace(old_ai_summary_block, new_ai_summary_block)

old_focus_areas = """            const focusAreas = [...concerns, ...developing].map(a => `<span style="background:rgba(255,255,255,0.25);padding:0.35rem 0.85rem;border-radius:99px;font-size:0.8rem;font-weight:700;display:inline-flex;align-items:center;gap:0.35rem;border:1px solid rgba(255,255,255,0.3);box-shadow:0 2px 8px rgba(0,0,0,0.1);">🎯 ${a.pillar.charAt(0).toUpperCase() + a.pillar.slice(1)}</span>`);"""

new_focus_areas = """            const focusAreas = [...concerns, ...developing].map(a => `<span style="background:rgba(255,255,255,0.25);padding:0.35rem 0.85rem;border-radius:99px;font-size:0.8rem;font-weight:700;display:inline-flex;align-items:center;gap:0.35rem;border:1px solid rgba(255,255,255,0.3);box-shadow:0 2px 8px rgba(0,0,0,0.1);">🎯 ${formatPillarName(a.pillar)}</span>`);"""

js_content = js_content.replace(old_focus_areas, new_focus_areas)

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print("Applied casing fixes successfully.")
