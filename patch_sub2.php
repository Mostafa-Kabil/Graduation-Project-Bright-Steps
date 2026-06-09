<?php
$file = __DIR__ . '/dashboards/parent/dashboard.js';
$content = file_get_contents($file);
$changes = 0;

// FIX 1: Change subscription checks for speech/motor to show blurred
$old1 = "if (viewId === 'clinic') access = await checkSubscriptionAccess('clinic');\r\n        else if (viewId === 'speech') access = await checkSubscriptionAccess('speech', 3);\r\n        else if (viewId === 'motor') access = await checkSubscriptionAccess('motor', 1);\r\n\r\n        if (!access) return;";

$new1 = "// speech/motor: show blurred content. clinic: open for all.\r\n        let showBlurred = false;\r\n        if (viewId === 'speech' || viewId === 'motor') {\r\n            const isPremium = window.dashboardData && window.dashboardData.subscription && window.dashboardData.subscription.plan_name === 'Premium';\r\n            if (!isPremium) showBlurred = true;\r\n        }";

if (strpos($content, $old1) !== false) {
    $content = str_replace($old1, $new1, $content);
    $changes++;
    echo "FIX 1: Subscription checks updated\n";
} else {
    echo "SKIP 1\n";
}

// FIX 3: Add _startFreeTrial
if (strpos($content, 'window._startFreeTrial') === false) {
    $content .= "\n" . 'window._startFreeTrial = function(feature) {
    var trialKey = "bs_trial_" + feature;
    var used = parseInt(localStorage.getItem(trialKey) || "0");
    if (used >= 3) { alert("You have used all 3 free trials."); return; }
    localStorage.setItem(trialKey, used + 1);
    var ov = document.getElementById("paywall-blur-overlay");
    if (ov) ov.remove();
    var cc = document.getElementById("dashboard-content");
    if (cc) { cc.style.filter = ""; cc.style.pointerEvents = ""; cc.style.userSelect = ""; }
    if (typeof showBadgeToast === "function") showBadgeToast("🎁 Free trial activated! " + (2 - used) + " remaining");
};' . "\n";
    $changes++;
    echo "FIX 3: Added _startFreeTrial\n";
}

// FIX 5: Fix clinic check in async function
$old5 = "if (feature === 'clinic') {\r\n            // Clinic is Premium Only\r\n            showPremiumPaywallModal('Clinic Consultations', 'Unlimited access to our network of specialists, online and onsite.');\r\n            return false;\r\n        }";
$new5 = "if (feature === 'clinic') {\r\n            // Clinic is open for all users\r\n            return true;\r\n        }";

if (strpos($content, $old5) !== false) {
    $content = str_replace($old5, $new5, $content);
    $changes++;
    echo "FIX 5: Clinic open for free in async check\n";
} else {
    echo "SKIP 5\n";
}

echo "\nTotal: $changes\n";
if ($changes > 0) {
    file_put_contents($file, $content);
    echo "✅ Applied!\n";
}
?>
