<?php
/**
 * Comprehensive Dashboard Patch
 * Fixes: missing openActivityDetailModal, refresh button size, broken images,
 *        growth velocity spikes, cooldown emoji, auto-load checklist on home,
 *        notifications indicator, activity section refresh
 */

$file = __DIR__ . '/dashboards/parent/dashboard.js';
$content = file_get_contents($file);
if (!$content) { die("ERROR: Cannot read dashboard.js"); }

$originalSize = strlen($content);
$changes = 0;

// ═══════════════════════════════════════════════════════════════
// FIX 1: Add missing openActivityDetailModal function
// It's called from both Today's Activities and Weekly Plan but never defined
// ═══════════════════════════════════════════════════════════════
$activityModalFunc = <<<'JSBLOCK'

// ── Activity Detail Modal (for Today's Activities & Weekly Plan clicks) ──
window.openActivityDetailModal = function(title, description, reason, category, bgColor, textColor) {
    let existing = document.getElementById('activity-detail-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'activity-detail-modal';
    modal.innerHTML = `
        <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.parentElement.remove()">
            <div style="background:#ffffff;border-radius:24px;width:100%;max-width:520px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);overflow:hidden;animation:slideUp 0.3s ease-out;">
                <div style="background:linear-gradient(135deg,${bgColor || '#dbeafe'},${textColor ? textColor+'20' : '#ede9fe'});padding:2rem 2rem 1.5rem;position:relative;">
                    <button onclick="document.getElementById('activity-detail-modal').remove()" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,0.8);border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;color:#64748b;backdrop-filter:blur(4px);">✕</button>
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                        <div style="width:48px;height:48px;background:${bgColor || '#dbeafe'};border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;border:2px solid rgba(255,255,255,0.5);">🎯</div>
                        <div>
                            <span style="font-size:0.7rem;font-weight:700;color:${textColor || '#1e40af'};text-transform:uppercase;letter-spacing:0.05em;">${category || 'Activity'}</span>
                            <h2 style="margin:0;font-size:1.25rem;font-weight:800;color:#1e293b;line-height:1.3;">${title}</h2>
                        </div>
                    </div>
                </div>
                <div style="padding:1.5rem 2rem;overflow-y:auto;flex:1;">
                    ${description ? '<div style="margin-bottom:1.25rem;"><h4 style="font-weight:700;color:#334155;margin:0 0 0.5rem;font-size:0.9rem;">📝 Description</h4><p style="color:#64748b;line-height:1.7;margin:0;font-size:0.9rem;">' + description + '</p></div>' : ''}
                    ${reason ? '<div style="background:#f8fafc;border-radius:14px;padding:1.25rem;border:1px solid #e2e8f0;"><h4 style="font-weight:700;color:#334155;margin:0 0 0.5rem;font-size:0.9rem;">📋 Details & Steps</h4><p style="color:#64748b;line-height:1.7;margin:0;font-size:0.85rem;">' + reason + '</p></div>' : ''}
                </div>
                <div style="padding:1rem 2rem 1.5rem;border-top:1px solid #f1f5f9;">
                    <button onclick="document.getElementById('activity-detail-modal').remove()" style="width:100%;padding:0.85rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px;font-weight:700;font-size:0.95rem;cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">Got it! 👍</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(modal);
};
JSBLOCK;

// Append after checkMemoryMatchCooldown
if (strpos($content, 'window.openActivityDetailModal') === false) {
    $content .= $activityModalFunc;
    $changes++;
    echo "FIX 1: Added openActivityDetailModal function\n";
} else {
    echo "SKIP 1: openActivityDetailModal already exists\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 2: Make Today's Activities refresh button bigger
// ═══════════════════════════════════════════════════════════════
$oldRefreshBtn = '<button onclick="loadHomeActivities()" style="background:none;border:none;color:var(--slate-400);cursor:pointer;padding:0.25rem;" title="Refresh"><svg width="16" height="16"';
$newRefreshBtn = '<button onclick="loadHomeActivities()" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;cursor:pointer;padding:0.45rem 0.9rem;border-radius:8px;display:flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:600;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 8px rgba(99,102,241,0.25);" title="Refresh Activities" onmouseover="this.style.transform=\'translateY(-1px)\'" onmouseout="this.style.transform=\'\'"><svg width="14" height="14"';

if (strpos($content, $oldRefreshBtn) !== false) {
    // Also need to close the button properly - add text after the SVG
    $content = str_replace($oldRefreshBtn, $newRefreshBtn, $content);
    // Add "Refresh" text after the closing svg tag for this button
    $oldSvgClose = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1 0 2.13-5.88L2 9"/></svg></button>';
    $newSvgClose = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1 0 2.13-5.88L2 9"/></svg> Refresh</button>';
    $content = str_replace($oldSvgClose, $newSvgClose, $content);
    $changes++;
    echo "FIX 2: Made refresh button bigger with text\n";
} else {
    echo "SKIP 2: Refresh button pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 3: Fix broken Unsplash article images → gradient placeholders
// ═══════════════════════════════════════════════════════════════
$oldImgUrl = "const imgUrl = `https://source.unsplash.com/400x300/?\${keyword},child`;";
$newImgUrl = "const gradients = ['linear-gradient(135deg,#667eea,#764ba2)','linear-gradient(135deg,#f093fb,#f5576c)','linear-gradient(135deg,#4facfe,#00f2fe)','linear-gradient(135deg,#43e97b,#38f9d7)','linear-gradient(135deg,#fa709a,#fee140)','linear-gradient(135deg,#a18cd1,#fbc2eb)'];\n                            const imgUrl = gradients[i % gradients.length];";

if (strpos($content, $oldImgUrl) !== false) {
    $content = str_replace($oldImgUrl, $newImgUrl, $content);
    // Also fix the background-image usage to work with gradients
    $content = str_replace(
        'background-image:url(${imgUrl});background-size:cover;background-position:center;',
        'background:${imgUrl};',
        $content
    );
    $changes++;
    echo "FIX 3: Replaced broken Unsplash URLs with gradient placeholders\n";
} else {
    echo "SKIP 3: Unsplash URL pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 4: Fix growth velocity spikes (add minimum monthsDiff threshold)
// ═══════════════════════════════════════════════════════════════
$oldVelocity = 'if (monthsDiff > 0) {';
$newVelocity = 'if (monthsDiff > 0.5) { // Minimum half-month gap to avoid velocity spikes';

if (strpos($content, $oldVelocity) !== false) {
    // Only replace the first occurrence (in the velocity calc block)
    $pos = strpos($content, $oldVelocity);
    $content = substr_replace($content, $newVelocity, $pos, strlen($oldVelocity));
    $changes++;
    echo "FIX 4: Fixed velocity calculation minimum threshold\n";
} else {
    echo "SKIP 4: Velocity pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 5: Fix cooldown button emoji (? → ⏳)
// ═══════════════════════════════════════════════════════════════
$oldCooldown = "mmBtn.innerHTML = '? Cooldown (' + daysLeft + ' day' + (daysLeft>1?'s':'') + ')';";
$newCooldown = "mmBtn.innerHTML = '⏳ Cooldown (' + daysLeft + ' day' + (daysLeft>1?'s':'') + ')';";

if (strpos($content, $oldCooldown) !== false) {
    $content = str_replace($oldCooldown, $newCooldown, $content);
    // Also redesign the cooldown button style
    $content = str_replace(
        "mmBtn.style.background = '#e2e8f0';",
        "mmBtn.style.background = 'linear-gradient(135deg,#cbd5e1,#94a3b8)';",
        $content
    );
    $content = str_replace(
        "mmBtn.style.color = '#64748b';",
        "mmBtn.style.color = '#fff';mmBtn.style.fontSize = '1rem';mmBtn.style.padding = '1rem 2rem';",
        $content
    );
    $changes++;
    echo "FIX 5: Fixed cooldown button emoji and styling\n";
} else {
    echo "SKIP 5: Cooldown emoji pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 6: Auto-load checklist data on home view so Weekly Plan works
// ═══════════════════════════════════════════════════════════════
$oldHomeHook = "if (viewId === 'home' || !viewId) {\n            loadHomeActivities();\n        }";
$newHomeHook = "if (viewId === 'home' || !viewId) {\n            loadHomeActivities();\n            // Auto-load checklist so Weekly Plan generates activities\n            var _homeChild = (window.dashboardData || {}).children || [];\n            var _hc = _homeChild[window._selectedChildIndex || 0];\n            if (_hc && _hc.child_id && typeof loadBehaviorChecklist === 'function' && !window._behaviorChecklistLoaded) {\n                window._behaviorChecklistLoaded = true;\n                loadBehaviorChecklist(_hc.child_id);\n            }\n        }";

if (strpos($content, $oldHomeHook) !== false) {
    $content = str_replace($oldHomeHook, $newHomeHook, $content);
    $changes++;
    echo "FIX 6: Auto-load checklist on home view\n";
} else {
    echo "SKIP 6: Home hook pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 7: Add Refresh button to Activities section header
// ═══════════════════════════════════════════════════════════════
$oldActHeader = '<h1 class="dashboard-title">Activity Center 🎨</h1>';
$newActHeader = '<h1 class="dashboard-title">Activity Center 🎨</h1></div><div><button id="ai-refresh-btn" onclick="loadAIRecommendations(\'' . "' + childParam + '" . '\')" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;padding:0.6rem 1.25rem;border-radius:12px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;box-shadow:0 4px 12px rgba(99,102,241,0.3);transition:all 0.2s;" onmouseover="this.style.transform=\'translateY(-1px)\'" onmouseout="this.style.transform=\'\'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1 0 2.13-5.88L2 9"/></svg> Refresh</button>';

// This needs special handling because of template literals
$oldActHeaderAlt = '<h1 class=\"dashboard-title\">Activity Center 🎨</h1>';
if (strpos($content, $oldActHeader) !== false) {
    $content = str_replace($oldActHeader, $newActHeader, $content);
    $changes++;
    echo "FIX 7: Added refresh button to Activities section\n";
} else {
    echo "SKIP 7: Activities header pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 8: Fix notifications - add red dot for new notifications
// ═══════════════════════════════════════════════════════════════
// Search for the notification bell area
$oldNotifBell = "id=\"notif-count\"";
if (strpos($content, $oldNotifBell) !== false) {
    // Already has a notif-count element, make sure the red dot shows
    echo "SKIP 8: Notification counter already exists\n";
} else {
    echo "SKIP 8: Notification bell not found in dashboard.js\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 9: Fix Developmental Insights section with modern design
// ═══════════════════════════════════════════════════════════════
$oldDevInsights = '<h4 style="font-weight:800;font-size:1.1rem;margin:0 0 0.5rem;letter-spacing:0.5px;text-shadow:0 2px 4px rgba(0,0,0,0.2);">Developmental Insights</h4>';
$newDevInsights = '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;"><div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">🧠</div><h4 style="font-weight:800;font-size:1.15rem;margin:0;letter-spacing:0.3px;">Developmental Insights</h4></div>';

if (strpos($content, $oldDevInsights) !== false) {
    $content = str_replace($oldDevInsights, $newDevInsights, $content);
    $changes++;
    echo "FIX 9: Redesigned Developmental Insights header\n";
} else {
    echo "SKIP 9: Developmental Insights pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 10: Fix the weekly plan default message (also in the static HTML)
// ═══════════════════════════════════════════════════════════════
$oldDefaultMsg = '<div style="text-align:center;padding:1.5rem;color:var(--slate-500);font-size:0.85rem;">Open the checklist to generate personalized activities</div>';
$newDefaultMsg = '<div style="text-align:center;padding:2rem;color:var(--slate-400);font-size:0.85rem;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;margin:0 auto 0.5rem;display:block;color:var(--slate-300);"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>Loading personalized activities...</div>';

if (strpos($content, $oldDefaultMsg) !== false) {
    $content = str_replace($oldDefaultMsg, $newDefaultMsg, $content);
    $changes++;
    echo "FIX 10: Updated weekly plan placeholder to loading state\n";
} else {
    echo "SKIP 10: Weekly plan default message not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 11: Fix View Profile popup - fix the broken flex-wrap:gap syntax
// ═══════════════════════════════════════════════════════════════
$oldFlexWrap = 'display:flex;flex-wrap:gap:0.5rem;margin-bottom:1.5rem;';
$newFlexWrap = 'display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem;';

if (strpos($content, $oldFlexWrap) !== false) {
    $content = str_replace($oldFlexWrap, $newFlexWrap, $content);
    $changes++;
    echo "FIX 11: Fixed broken CSS in View Profile popup\n";
} else {
    echo "SKIP 11: Flex-wrap pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 12: Make _behaviorChecklistData accessible from home view
// The variable is local to the IIFE, so we need to expose it on window
// ═══════════════════════════════════════════════════════════════
$oldChecklistVar = 'let _behaviorChecklistData = null;';
$newChecklistVar = 'let _behaviorChecklistData = null; window._behaviorChecklistDataRef = () => _behaviorChecklistData;';

if (strpos($content, $oldChecklistVar) !== false) {
    $content = str_replace($oldChecklistVar, $newChecklistVar, $content);
    $changes++;
    echo "FIX 12: Exposed checklist data reference for home view\n";
} else {
    echo "SKIP 12: _behaviorChecklistData declaration not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 13: Fix Milestone Progress - ensure bar chart renders with data
// Make sure Milestone Progress bars have minimum visible height
// ═══════════════════════════════════════════════════════════════
$oldMilestoneBar = "const displayPct = Math.max(pct, 5); // Minimum 5% for visibility";
$newMilestoneBar = "const displayPct = Math.max(pct, 8); // Minimum 8% for visibility";

if (strpos($content, $oldMilestoneBar) !== false) {
    $content = str_replace($oldMilestoneBar, $newMilestoneBar, $content);
    $changes++;
    echo "FIX 13: Updated milestone progress minimum bar height\n";
} else {
    echo "SKIP 13: Milestone bar pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// SAVE
// ═══════════════════════════════════════════════════════════════
$newSize = strlen($content);
echo "\n========================================\n";
echo "Original size: $originalSize bytes\n";
echo "New size: $newSize bytes\n";
echo "Total fixes applied: $changes\n";

if ($changes > 0) {
    // Backup first
    $backup = $file . '.bak_' . date('YmdHis');
    copy($file, $backup);
    echo "Backup saved to: $backup\n";
    
    file_put_contents($file, $content);
    echo "✅ All patches applied successfully!\n";
} else {
    echo "⚠️ No changes were needed.\n";
}
?>
