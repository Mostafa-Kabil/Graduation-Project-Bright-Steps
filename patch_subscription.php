<?php
/**
 * Patch: Subscription gating, report restrictions, child limits, etc.
 */
$file = __DIR__ . '/dashboards/parent/dashboard.js';
$content = file_get_contents($file);
if (!$content) die("Cannot read file\n");
$changes = 0;

// ═══════════════════════════════════════════════════════════════
// FIX 1: Speech & Motor should show blurred content with overlay
//        instead of blocking entirely. Clinic should be OPEN for free.
// ═══════════════════════════════════════════════════════════════
$oldAccess = <<<'JS'
        // Check subscription limits
        let access = true;
        if (viewId === 'clinic') access = await checkSubscriptionAccess('clinic');
        else if (viewId === 'speech') access = await checkSubscriptionAccess('speech', 3);
        else if (viewId === 'motor') access = await checkSubscriptionAccess('motor', 1);

        if (!access) return;
JS;

$newAccess = <<<'JS'
        // Check subscription limits - speech/motor show blurred, clinic is open for all
        let access = true;
        let showBlurred = false;
        if (viewId === 'speech' || viewId === 'motor') {
            const isPremium = window.dashboardData && window.dashboardData.subscription && window.dashboardData.subscription.plan_name === 'Premium';
            if (!isPremium) {
                showBlurred = true; // Will show content but blurred with overlay
            }
        }
        // clinic is open for free members - no check needed
JS;

if (strpos($content, $oldAccess) !== false) {
    $content = str_replace($oldAccess, $newAccess, $content);
    $changes++;
    echo "FIX 1: Speech/Motor now show blurred, clinic open for free\n";
} else {
    echo "SKIP 1: Access pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 2: After loadView, apply blur overlay for speech/motor
// ═══════════════════════════════════════════════════════════════
$oldLoadView = "loadView(viewId);\r\n    }";
$newLoadView = <<<'JS'
loadView(viewId);

        // Apply blur + paywall overlay for free users on speech/motor
        if (showBlurred) {
            setTimeout(() => {
                const cc = document.getElementById('dashboard-content');
                if (!cc) return;
                cc.style.position = 'relative';
                cc.style.filter = 'blur(4px)';
                cc.style.pointerEvents = 'none';
                cc.style.userSelect = 'none';

                // Check free trial count
                const trialKey = 'bs_trial_' + viewId;
                const used = parseInt(localStorage.getItem(trialKey) || '0');
                const maxTrials = 3;
                const remaining = Math.max(0, maxTrials - used);

                let overlay = document.getElementById('paywall-blur-overlay');
                if (overlay) overlay.remove();
                overlay = document.createElement('div');
                overlay.id = 'paywall-blur-overlay';
                overlay.style.cssText = 'position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,0.15);';
                
                const featureName = viewId === 'speech' ? 'AI Speech Analysis' : 'Motor Skills Tracking';
                const trialBtnHtml = remaining > 0
                    ? '<button onclick="window._startFreeTrial(\'' + viewId + '\')" style="width:100%;padding:0.9rem;background:linear-gradient(135deg,#10b981,#06b6d4);color:#fff;border:none;border-radius:14px;font-weight:700;font-size:1rem;cursor:pointer;margin-bottom:0.75rem;box-shadow:0 8px 20px rgba(16,185,129,0.3);transition:transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'\'">🎁 Start Free Trial (' + remaining + ' remaining)</button>'
                    : '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:0.75rem;text-align:center;margin-bottom:0.75rem;color:#dc2626;font-weight:600;font-size:0.85rem;">❌ Free trials exhausted</div>';

                overlay.innerHTML = '<div style="background:#fff;border-radius:28px;padding:2.5rem;max-width:440px;width:90%;text-align:center;box-shadow:0 30px 60px rgba(0,0,0,0.25);animation:slideUp 0.3s ease-out;">' +
                    '<div style="width:72px;height:72px;background:linear-gradient(135deg,#fde047,#f59e0b);border-radius:18px;margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center;font-size:2.25rem;box-shadow:0 8px 20px rgba(245,158,11,0.3);">🔒</div>' +
                    '<h2 style="font-size:1.5rem;font-weight:800;color:#1e293b;margin-bottom:0.5rem;">Premium Feature</h2>' +
                    '<p style="color:#64748b;margin-bottom:1.5rem;line-height:1.6;font-size:0.9rem;"><strong>' + featureName + '</strong> is a premium feature. Subscribe or use a free trial to access.</p>' +
                    trialBtnHtml +
                    '<button onclick="triggerPaymentUI(\'premium_subscription\')" style="width:100%;padding:0.9rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px;font-weight:700;font-size:1rem;cursor:pointer;box-shadow:0 8px 20px rgba(99,102,241,0.3);transition:transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'\'">💎 Subscribe Now</button>' +
                    '<button onclick="document.getElementById(\'paywall-blur-overlay\').remove();document.getElementById(\'dashboard-content\').style.filter=\'\';document.getElementById(\'dashboard-content\').style.pointerEvents=\'\';document.getElementById(\'dashboard-content\').style.userSelect=\'\';window._dashboardSwitchView(\'home\')" style="display:block;width:100%;margin-top:0.75rem;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:0.85rem;font-weight:500;">← Go Back</button>' +
                    '</div>';
                document.body.appendChild(overlay);
            }, 100);
        }
    }
JS;

if (strpos($content, $oldLoadView) !== false) {
    $content = str_replace($oldLoadView, $newLoadView, $content);
    $changes++;
    echo "FIX 2: Added blur overlay for speech/motor\n";
} else {
    echo "SKIP 2: loadView pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 3: Add _startFreeTrial function
// ═══════════════════════════════════════════════════════════════
$trialFunc = <<<'JS'

// Free trial handler
window._startFreeTrial = function(feature) {
    const trialKey = 'bs_trial_' + feature;
    let used = parseInt(localStorage.getItem(trialKey) || '0');
    if (used >= 3) {
        alert('You have used all 3 free trials for this feature.');
        return;
    }
    localStorage.setItem(trialKey, used + 1);
    // Remove overlay and unblur
    var ov = document.getElementById('paywall-blur-overlay');
    if (ov) ov.remove();
    var cc = document.getElementById('dashboard-content');
    if (cc) { cc.style.filter = ''; cc.style.pointerEvents = ''; cc.style.userSelect = ''; }
    showBadgeToast('🎁 Free trial activated! ' + (2 - used) + ' remaining');
};
JS;

if (strpos($content, 'window._startFreeTrial') === false) {
    $content .= $trialFunc;
    $changes++;
    echo "FIX 3: Added _startFreeTrial function\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 4: Remove clinic premium check from bookSpecialist
// ═══════════════════════════════════════════════════════════════
$oldBookCheck = "if (!window.checkSubscriptionAccess('appointment', 0)) return;";
$newBookCheck = "// Clinic is open for all users";

if (strpos($content, $oldBookCheck) !== false) {
    $content = str_replace($oldBookCheck, $newBookCheck, $content);
    $changes++;
    echo "FIX 4: Removed premium check from bookSpecialist\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 5: Remove clinic from async checkSubscriptionAccess
// ═══════════════════════════════════════════════════════════════
$oldClinicCheck = <<<'JS'
        if (feature === 'clinic') {
            // Clinic is Premium Only
            showPremiumPaywallModal('Clinic Consultations', 'Unlimited access to our network of specialists, online and onsite.');
            return false;
        }
JS;
$newClinicCheck = <<<'JS'
        if (feature === 'clinic') {
            // Clinic is open for all users
            return true;
        }
JS;

if (strpos($content, $oldClinicCheck) !== false) {
    $content = str_replace($oldClinicCheck, $newClinicCheck, $content);
    $changes++;
    echo "FIX 5: Clinic now open for free members\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 6: Remove Child Profile card from reports section
// ═══════════════════════════════════════════════════════════════
$childProfileStart = '<!-- Child Profile Card -->';
$childProfileEnd = '</div>

                    <!-- Speech Report Card -->';

if (strpos($content, $childProfileStart) !== false) {
    $startPos = strpos($content, $childProfileStart);
    $endPos = strpos($content, '<!-- Speech Report Card -->', $startPos);
    if ($endPos !== false) {
        $removeSection = substr($content, $startPos, $endPos - $startPos);
        $content = str_replace($removeSection, '<!-- Child Profile Card removed -->' . "\r\n                    ", $content);
        $changes++;
        echo "FIX 6: Removed Child Profile card from reports\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// FIX 7: PDF download/upload requires Premium
// ═══════════════════════════════════════════════════════════════
$oldDownload = "onclick=\"window.open('../../api_export_pdf.php?type=full-report";
$newDownload = "onclick=\"if(!(window.dashboardData&&window.dashboardData.subscription&&window.dashboardData.subscription.plan_name==='Premium')){window.showPremiumModal();return;}window.open('../../api_export_pdf.php?type=full-report";

if (strpos($content, $oldDownload) !== false) {
    $content = str_replace(
        "onclick=\"window.open('../../api_export_pdf.php?type=",
        "onclick=\"if(!(window.dashboardData&&window.dashboardData.subscription&&window.dashboardData.subscription.plan_name==='Premium')){window.showPremiumModal();return;}window.open('../../api_export_pdf.php?type=",
        $content
    );
    $changes++;
    echo "FIX 7: PDF download now requires Premium\n";
}

// ═══════════════════════════════════════════════════════════════
// FIX 8: Free members can only add 1 child
// ═══════════════════════════════════════════════════════════════
$oldAddChild = "window.openAddChildModal = function";
$newAddChild = <<<'JS'
window.openAddChildModal = function
JS;

// Find the openAddChildModal function and add a check at the top
$addChildPos = strpos($content, "window.openAddChildModal = function");
if ($addChildPos !== false) {
    // Find the opening brace
    $bracePos = strpos($content, '{', $addChildPos);
    if ($bracePos !== false) {
        $insertAfter = $bracePos + 1;
        $childLimitCheck = "\n        // Free members can only add 1 child\n        const _isPrem = window.dashboardData && window.dashboardData.subscription && window.dashboardData.subscription.plan_name === 'Premium';\n        const _childCount = (window.dashboardData && window.dashboardData.children) ? window.dashboardData.children.length : 0;\n        if (!_isPrem && _childCount >= 1 && !arguments[0]) {\n            window.showPremiumModal();\n            return;\n        }\n";
        
        // Check if already added
        if (strpos($content, 'Free members can only add 1 child') === false) {
            $content = substr_replace($content, $childLimitCheck, $insertAfter, 0);
            $changes++;
            echo "FIX 8: Free members limited to 1 child\n";
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// FIX 9: Make Weekly Articles refresh button bigger & premium-only
// ═══════════════════════════════════════════════════════════════
$oldArticleRefresh = '<button onclick="loadHomeActivities()" style="background:none;border:none;color:var(--slate-400);cursor:pointer;padding:0;display:flex;align-items:center;" title="Refresh"><svg width="14" height="14"';
$newArticleRefresh = '<button onclick="if(!(window.dashboardData&&window.dashboardData.subscription&&window.dashboardData.subscription.plan_name===\'Premium\')){window.showPremiumModal();return;}loadHomeActivities()" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;cursor:pointer;padding:0.3rem 0.7rem;border-radius:8px;display:flex;align-items:center;gap:0.3rem;font-size:0.7rem;font-weight:600;transition:transform 0.2s;" title="Refresh Articles (Premium)" onmouseover="this.style.transform=\'translateY(-1px)\'" onmouseout="this.style.transform=\'\'"><svg width="12" height="12"';

if (strpos($content, $oldArticleRefresh) !== false) {
    $content = str_replace($oldArticleRefresh, $newArticleRefresh, $content);
    $changes++;
    echo "FIX 9: Article refresh button redesigned & premium-gated\n";
} else {
    echo "SKIP 9: Article refresh pattern not found\n";
}

// ═══════════════════════════════════════════════════════════════
// SAVE
// ═══════════════════════════════════════════════════════════════
echo "\n========================================\n";
echo "Total fixes applied: $changes\n";

if ($changes > 0) {
    $backup = $file . '.bak_sub_' . date('YmdHis');
    copy($file, $backup);
    echo "Backup: $backup\n";
    file_put_contents($file, $content);
    echo "✅ All patches applied!\n";
} else {
    echo "No changes.\n";
}
?>
