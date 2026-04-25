<?php
require 'connection.php';

try {
    // 0. Fix child_id to be AUTO_INCREMENT
    $connect->exec("ALTER TABLE child MODIFY child_id INT AUTO_INCREMENT");
    echo "Modified child table to AUTO_INCREMENT.\n";
    // 1. Ensure we have a parent record linked to a user
    $user = $connect->query("SELECT user_id FROM users WHERE role='parent' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // Create a dummy parent user
        $connect->exec("INSERT INTO users (first_name, last_name, email, role, status) VALUES ('Test', 'Parent', 'parent@test.com', 'parent', 'active')");
        $user_id = $connect->lastInsertId();
    } else {
        $user_id = $user['user_id'];
    }

    $parent = $connect->prepare("SELECT parent_id FROM parent WHERE parent_id = ?");
    $parent->execute([$user_id]);
    if (!$parent->fetch()) {
        $connect->prepare("INSERT INTO parent (parent_id) VALUES (?)")->execute([$user_id]);
    }

    // 2. Add children if none exist
    $child_count = $connect->query("SELECT COUNT(*) FROM child")->fetchColumn();
    if ($child_count == 0) {
        $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES 
            ('SSN001', ?, 'Adam', 'Test', 1, 1, 2020, 'Male'),
            ('SSN002', ?, 'Zoe', 'Test', 15, 6, 2021, 'Female')
        ")->execute([$user_id, $user_id]);
        echo "Inserted 2 dummy children.\n";
    } else {
        echo "Children already exist.\n";
    }

    // 3. Ensure the current clinic has specialists 
    // We'll look for a clinic owned by admin_id=1 (common in this project)
    $clinic = $connect->query("SELECT clinic_id FROM clinic WHERE admin_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($clinic) {
        $cid = $clinic['clinic_id'];
        $s_count = $connect->prepare("SELECT COUNT(*) FROM specialist WHERE clinic_id = ?");
        $s_count->execute([$cid]);
        if ($s_count->fetchColumn() == 0) {
            $connect->prepare("INSERT INTO specialist (clinic_id, first_name, last_name, specialization, experience_years) VALUES 
                (?, 'Medical', 'Officer', 'General Pediatrician', 10)
            ")->execute([$cid]);
            echo "Added a specialist to clinic $cid.\n";
        } else {
            echo "Clinic $cid already has specialists.\n";
        }
    }

    echo "Seed completed successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
