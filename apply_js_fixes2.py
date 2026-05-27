import os

# 1. Update dashboard.js
dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

old_alert = """                alertEl.style.display = 'flex';
                alertEl.style.flexDirection = 'column';
                alertEl.style.alignItems = 'center';
                alertEl.style.justifyContent = 'center';
                alertEl.style.gap = '2px';
                alertEl.style.flexShrink = '0';
                
                let labelHtml = status.label;
                if(status.label === 'Needs Attention') {
                    labelHtml = 'Needs<br>Attention';
                }
                
                alertEl.innerHTML = `<span style="font-size:1.1rem; line-height:1;">${status.icon}</span><span style="font-size:0.65rem; line-height:1.1; color:${status.color}; font-weight:700; text-align:center;">${labelHtml}</span>`;"""

new_alert = """                alertEl.style.display = 'inline-flex';
                alertEl.style.flexDirection = 'row';
                alertEl.style.alignItems = 'center';
                alertEl.style.justifyContent = 'center';
                alertEl.style.gap = '0.35rem';
                alertEl.style.padding = '0.3rem 0.65rem';
                alertEl.style.borderRadius = '20px';
                alertEl.style.background = `${status.color}15`;
                alertEl.style.color = status.color;
                alertEl.style.flexShrink = '0';
                alertEl.style.marginLeft = 'auto';
                
                alertEl.innerHTML = `<span style="font-size:0.5rem; line-height:1;">●</span><span style="font-size:0.7rem; font-weight:700; white-space:nowrap; line-height:1;">${status.label}</span>`;"""

if old_alert in js_content:
    js_content = js_content.replace(old_alert, new_alert)
else:
    print("Could not find alert block in dashboard.js to replace")

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)
print("Updated dashboard.js")

# 2. Update api_points_engine.php
api_points_path = r"c:\xampp\htdocs\Bright Steps Website\api_points_engine.php"
with open(api_points_path, "r", encoding="utf-8") as f:
    api_content = f.read()

old_history = """    case 'get_history':
        if (!$childId || !verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }
        $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
        $stmt = $connect->prepare("
            SELECT action, points, reason, created_at
            FROM parent_points_history
            WHERE parent_id = ? AND child_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $parentId, PDO::PARAM_INT);
        $stmt->bindValue(2, $childId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);"""

new_history = """    case 'get_history':
        if (!$childId || !verifyChildOwnership($connect, $childId, $parentId)) {
            echo json_encode(['error' => 'Invalid child']);
            exit();
        }
        $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
        $history = [];
        try {
            $stmt = $connect->prepare("
                SELECT action, points, reason, created_at
                FROM parent_points_history
                WHERE parent_id = ? AND child_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $parentId, PDO::PARAM_INT);
            $stmt->bindValue(2, $childId, PDO::PARAM_INT);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Table might not exist yet if no points have been awarded
            $history = [];
        }"""

if old_history in api_content:
    api_content = api_content.replace(old_history, new_history)
else:
    print("Could not find get_history block in api_points_engine.php to replace")

with open(api_points_path, "w", encoding="utf-8") as f:
    f.write(api_content)
print("Updated api_points_engine.php")
