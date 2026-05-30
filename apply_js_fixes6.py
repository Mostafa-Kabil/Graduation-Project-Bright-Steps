import os
import re

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# 1. Fix the duplicate behaviors by deduplicating before push, and also remove the .slice(0,4) limitation
old_behavior_loop = """            cat.behaviors.forEach(b => {
                stats[pillar].total++;
                if (b.is_exhibited) {
                    stats[pillar].achieved++;
                    stats[pillar].behaviors.push({
                        name: b.behavior_details,
                        frequency: b.frequency,
                        severity: b.severity
                    });
                }
            });"""

new_behavior_loop = """            cat.behaviors.forEach(b => {
                stats[pillar].total++;
                if (b.is_exhibited) {
                    stats[pillar].achieved++;
                    // Avoid duplicates in the list
                    if (!stats[pillar].behaviors.some(x => x.name === b.behavior_details)) {
                        stats[pillar].behaviors.push({
                            name: b.behavior_details,
                            frequency: b.frequency,
                            severity: b.severity
                        });
                    }
                }
            });"""
js_content = js_content.replace(old_behavior_loop, new_behavior_loop)

# 2. Fix the html generation:
# - add <style> for @media print
# - add 'scrollable-content' class
# - remove .slice(0, 4)
# - add fine_motor to icons and colors

old_html_start = """        // Build comprehensive report HTML
        const reportContent = `
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;">"""

new_html_start = """        // Build comprehensive report HTML
        const reportContent = `
            <style>
                @media print {
                    body * { visibility: hidden; }
                    #doctor-report-modal, #doctor-report-modal * { visibility: visible; }
                    #doctor-report-modal { position: absolute; left: 0; top: 0; right: 0; bottom: 0; padding: 0 !important; background: transparent !important; z-index: 99999; }
                    #doctor-report-modal > div { max-height: none !important; width: 100% !important; max-width: none !important; box-shadow: none !important; border-radius: 0 !important; }
                    .report-scrollable { max-height: none !important; overflow: visible !important; }
                    .report-footer { display: none !important; }
                }
            </style>
            <div id="doctor-report-modal" style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.remove()">
                <div style="background:#ffffff;border-radius:24px;width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;">"""
js_content = js_content.replace(old_html_start, new_html_start)

# Replace the scrollable div to add class
js_content = js_content.replace(
    '<div style="padding:1.5rem;overflow-y:auto;flex:1;max-height:50vh;">',
    '<div class="report-scrollable" style="padding:1.5rem;overflow-y:auto;flex:1;max-height:50vh;">'
)

# Replace the footer to add class
js_content = js_content.replace(
    '<div style="padding:1rem 1.5rem;border-top:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">',
    '<div class="report-footer" style="padding:1rem 1.5rem;border-top:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">'
)

# Add fine_motor and remove slice
old_grid_code = """                                const pillarIcons = { attention: '🧠', communication: '💬', social: '🤝', motor: '🦵' };
                                const pillarColors = { attention: '#f59e0b', communication: '#3b82f6', social: '#10b981', motor: '#667eea' };
                                return `
                                    <div style="padding:1.25rem;background:#f8fafc;border-radius:16px;border:1px solid #e2e8f0;">
                                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                                            <span style="font-size:1.25rem;">${pillarIcons[pillar]}</span>
                                            <span style="font-weight:700;text-transform:capitalize;color:#1e293b;">${pillar}</span>
                                            <span style="margin-left:auto;font-size:1.25rem;">${status.icon}</span>
                                        </div>
                                        <div style="background:#e2e8f0;border-radius:8px;height:8px;overflow:hidden;margin-bottom:0.5rem;">
                                            <div style="background:linear-gradient(90deg,${pillarColors[pillar]},${pillarColors[pillar]}88);height:100%;width:${percent}%;border-radius:8px;"></div>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;">
                                            <span>${data.achieved}/${data.total} skills observed</span>
                                            <span>${percent}%</span>
                                        </div>
                                        <div style="font-size:0.75rem;color:${data.achieved >= expected ? '#16a34a' : '#ea580c'};font-weight:600;">${vsExpected}</div>
                                        ${data.behaviors.length > 0 ? `
                                            <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px dashed #e2e8f0;">
                                                <div style="font-size:0.7rem;color:#94a3b8;margin-bottom:0.25rem;">Observed behaviors:</div>
                                                <ul style="margin:0;padding-left:1rem;font-size:0.75rem;color:#475569;line-height:1.5;">
                                                    ${data.behaviors.slice(0, 4).map(b => `<li>${b.name}${b.frequency ? ' (' + b.frequency + ')' : ''}</li>`).join('')}
                                                    ${data.behaviors.length > 4 ? `<li>+${data.behaviors.length - 4} more...</li>` : ''}
                                                </ul>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;"""

new_grid_code = """                                const pillarIcons = { attention: '🧠', communication: '💬', social: '🤝', motor: '🦵', fine_motor: '✍️' };
                                const pillarColors = { attention: '#f59e0b', communication: '#3b82f6', social: '#10b981', motor: '#667eea', fine_motor: '#d946ef' };
                                let pillarName = pillar.charAt(0).toUpperCase() + pillar.slice(1).replace('_', ' ');
                                return `
                                    <div style="padding:1.25rem;background:#f8fafc;border-radius:16px;border:1px solid #e2e8f0;">
                                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                                            <span style="font-size:1.25rem;">${pillarIcons[pillar] || '📋'}</span>
                                            <span style="font-weight:700;text-transform:capitalize;color:#1e293b;">${pillarName}</span>
                                            <span style="margin-left:auto;font-size:1.25rem;">${status.icon}</span>
                                        </div>
                                        <div style="background:#e2e8f0;border-radius:8px;height:8px;overflow:hidden;margin-bottom:0.5rem;">
                                            <div style="background:linear-gradient(90deg,${pillarColors[pillar]||'#cbd5e1'},${pillarColors[pillar]||'#cbd5e1'}88);height:100%;width:${percent}%;border-radius:8px;"></div>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;">
                                            <span>${data.achieved}/${data.total} skills observed</span>
                                            <span>${percent}%</span>
                                        </div>
                                        <div style="font-size:0.75rem;color:${data.achieved >= expected ? '#16a34a' : '#ea580c'};font-weight:600;">${vsExpected}</div>
                                        ${data.behaviors.length > 0 ? `
                                            <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px dashed #e2e8f0;">
                                                <div style="font-size:0.7rem;color:#94a3b8;margin-bottom:0.25rem;">Observed behaviors:</div>
                                                <ul style="margin:0;padding-left:1rem;font-size:0.75rem;color:#475569;line-height:1.5;">
                                                    ${data.behaviors.map(b => `<li>${b.name}${b.frequency ? ' (' + b.frequency + ')' : ''}</li>`).join('')}
                                                </ul>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;"""

js_content = js_content.replace(old_grid_code, new_grid_code)

# Remove the old modal removal logic since we are removing by ID directly in the new HTML structure 
# wait, actually let's keep it safe. But I noticed in new_html_start I did `id="doctor-report-modal"` on the wrapper which was missing before.
# Let's fix the removal at the end of function:
js_content = js_content.replace("modal.id = 'doctor-report-modal';", "// modal.id = 'doctor-report-modal';")

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print("Applied Therapist PDF report fixes successfully.")
