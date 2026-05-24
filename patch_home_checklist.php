<?php
$file = __DIR__ . '/dashboards/parent/dashboard.js';
$content = file_get_contents($file);

// Fix: Auto-load checklist on home view
$old = "loadHomeActivities();\r\n        }";
$new = "loadHomeActivities();\r\n            // Auto-load checklist so Weekly Plan works\r\n            var _homeChild = (window.dashboardData || {}).children || [];\r\n            var _hc = _homeChild[window._selectedChildIndex || 0];\r\n            if (_hc && _hc.child_id && typeof loadBehaviorChecklist === 'function' && !window._behaviorChecklistLoaded) {\r\n                window._behaviorChecklistLoaded = true;\r\n                loadBehaviorChecklist(_hc.child_id);\r\n            }\r\n        }";

$pos = strpos($content, $old);
if ($pos !== false) {
    $content = substr_replace($content, $new, $pos, strlen($old));
    file_put_contents($file, $content);
    echo "SUCCESS: Auto-load checklist on home view applied\n";
} else {
    echo "Pattern not found. Trying alt...\n";
    $old2 = "loadHomeActivities();\n        }";
    $pos2 = strpos($content, $old2);
    if ($pos2 !== false) {
        $new2 = "loadHomeActivities();\n            var _homeChild = (window.dashboardData || {}).children || [];\n            var _hc = _homeChild[window._selectedChildIndex || 0];\n            if (_hc && _hc.child_id && typeof loadBehaviorChecklist === 'function' && !window._behaviorChecklistLoaded) {\n                window._behaviorChecklistLoaded = true;\n                loadBehaviorChecklist(_hc.child_id);\n            }\n        }";
        $content = substr_replace($content, $new2, $pos2, strlen($old2));
        file_put_contents($file, $content);
        echo "SUCCESS (alt): Auto-load checklist on home view applied\n";
    } else {
        echo "FAILED: Could not find pattern\n";
    }
}
?>
