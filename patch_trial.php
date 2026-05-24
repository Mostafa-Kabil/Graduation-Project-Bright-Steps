<?php
$file = 'c:\\xampp\\htdocs\\Bright Steps Website\\dashboards\\parent\\dashboard.js';
$content = file_get_contents($file);

// 1. Remove the strict switchView blocks for motor and speech
$searchSwitch = "        if (viewId === 'clinic') access = await checkSubscriptionAccess('clinic');
        else if (viewId === 'speech') access = await checkSubscriptionAccess('speech', 3);
        else if (viewId === 'motor') access = await checkSubscriptionAccess('motor', 1);

        if (!access) return;";

$replaceSwitch = "        if (viewId === 'clinic') access = await checkSubscriptionAccess('clinic');
        if (!access) return;";

$content = str_replace($searchSwitch, $replaceSwitch, $content);

// 2. Add applyPaywallBlur call inside loadView
$searchHooks = "        // Post-render hooks
        if (viewId === 'home' || !viewId) {
            loadHomeActivities();
        }";

$replaceHooks = "        // Post-render hooks
        if (viewId === 'home' || !viewId) {
            loadHomeActivities();
        }
        if (viewId === 'speech' || viewId === 'motor') {
            applyPaywallBlur(viewId);
        }";

$content = str_replace($searchHooks, $replaceHooks, $content);

// 3. Inject applyPaywallBlur and unlockTrialView
$injectFunctions = <<<EOD
    window.applyPaywallBlur = function(viewId) {
        const isPremium = window.dashboardData && window.dashboardData.subscription && window.dashboardData.subscription.plan_name === 'Premium';
        if (isPremium) return;

        let maxTrials = viewId === 'speech' ? 3 : 1;
        let usedTrials = 0;
        if (viewId === 'speech') {
            const child = (window.dashboardData && window.dashboardData.children && window.dashboardData.children[window._selectedChildIndex || 0]) || null;
            usedTrials = child && child._speech_history ? child._speech_history.length : 0;
        } else {
            const trialKey = 'bs_trial_' + viewId;
            usedTrials = parseInt(localStorage.getItem(trialKey) || '0');
        }

        const content = document.querySelector('.dashboard-content');
        if (!content) return;

        if (window['_unlocked_' + viewId]) return;

        const wrapper = document.createElement('div');
        wrapper.style.filter = 'blur(10px)';
        wrapper.style.pointerEvents = 'none';
        wrapper.style.userSelect = 'none';
        wrapper.style.transition = 'filter 0.5s ease';
        while (content.firstChild) {
            wrapper.appendChild(content.firstChild);
        }
        content.appendChild(wrapper);

        const overlay = document.createElement('div');
        overlay.style.position = 'absolute';
        overlay.style.inset = '0';
        overlay.style.display = 'flex';
        overlay.style.flexDirection = 'column';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.background = 'rgba(255,255,255,0.4)';
        overlay.style.zIndex = '10';

        let overlayHtml = '';
        if (usedTrials < maxTrials) {
            const left = maxTrials - usedTrials;
            overlayHtml = `
                <div style="background:#fff;padding:2rem;border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.1);text-align:center;max-width:400px;">
                    <div style="font-size:3rem;margin-bottom:1rem;">🎁</div>
                    <h3 style="font-size:1.5rem;font-weight:800;margin:0 0 0.5rem;color:var(--slate-900);">Unlock \${viewId === 'speech' ? 'Speech' : 'Motor'} Analysis</h3>
                    <p style="color:var(--slate-600);margin-bottom:1.5rem;">You have <strong>\${left} free trial\${left>1?'s':''}</strong> remaining. Experience our AI-powered analysis today!</p>
                    <button onclick="window.unlockTrialView('\${viewId}', this)" class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1.1rem;margin-bottom:0.75rem;">Start Free Trial</button>
                    <button onclick="window.showPremiumModal()" class="btn btn-outline" style="width:100%;padding:1rem;font-size:1.1rem;">Subscribe Now</button>
                </div>
            `;
        } else {
            overlayHtml = `
                <div style="background:#fff;padding:2rem;border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.1);text-align:center;max-width:400px;">
                    <div style="font-size:3rem;margin-bottom:1rem;">🔒</div>
                    <h3 style="font-size:1.5rem;font-weight:800;margin:0 0 0.5rem;color:var(--slate-900);">Premium Feature</h3>
                    <p style="color:var(--slate-600);margin-bottom:1.5rem;">You've used all your free trials for this feature. Subscribe to unlock unlimited access!</p>
                    <button onclick="window.showPremiumModal()" class="btn btn-gradient" style="width:100%;padding:1rem;font-size:1.1rem;">Subscribe Now</button>
                </div>
            `;
        }
        overlay.innerHTML = overlayHtml;
        content.style.position = 'relative';
        content.appendChild(overlay);
    };

    window.unlockTrialView = function(viewId, btn) {
        window['_unlocked_' + viewId] = true;
        if (viewId === 'motor') {
            window.incrementTrial('motor');
        }
        const overlay = btn.closest('div').parentElement;
        const wrapper = overlay.previousElementSibling;
        overlay.remove();
        wrapper.style.filter = 'none';
        wrapper.style.pointerEvents = 'auto';
        wrapper.style.userSelect = 'auto';
    };

    // Load view content
EOD;

// We will inject the new functions right before "// Load view content" comment
$content = str_replace("    // Load view content\n    function loadView(viewId) {", $injectFunctions . "    function loadView(viewId) {", $content);

file_put_contents($file, $content);
echo "Patch applied successfully.";
?>
