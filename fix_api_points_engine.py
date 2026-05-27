import os

file_path = "c:/xampp/htdocs/Bright Steps Website/api_points_engine.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Replace all 'parent_points_tracking' with 'parent_points_history'
content = content.replace("parent_points_tracking", "parent_points_history")

# 2. Fix awardPoints logging to create the table if missing
old_log = """        // 3. Log to parent_points_history
        $connect->prepare("INSERT INTO parent_points_history (parent_id, child_id, action, points, reason) VALUES (?, ?, ?, ?, ?)")->execute([$parentId, $childId, $actionName, $points, $reason]);"""

new_log = """        // 3. Log to parent_points_history
        try {
            $connect->prepare("INSERT INTO parent_points_history (parent_id, child_id, action, points, reason) VALUES (?, ?, ?, ?, ?)")->execute([$parentId, $childId, $actionName, $points, $reason]);
        } catch (Exception $e) {
            $connect->exec("CREATE TABLE IF NOT EXISTS `parent_points_history` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `parent_id` INT NOT NULL, `child_id` INT DEFAULT NULL,
                `action` VARCHAR(100) NOT NULL, `points` INT NOT NULL, `reason` TEXT DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $connect->prepare("INSERT INTO parent_points_history (parent_id, child_id, action, points, reason) VALUES (?, ?, ?, ?, ?)")->execute([$parentId, $childId, $actionName, $points, $reason]);
        }"""

content = content.replace(old_log, new_log)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("api_points_engine.php fixed.")
