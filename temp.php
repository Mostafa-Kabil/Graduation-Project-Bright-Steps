<?php
require 'connection.php';

// Check Mallak
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'mallak@gmail.com'");
$stmt->execute();
$mallak_user = $stmt->fetchColumn();

if ($mallak_user) {
    echo "Mallak user_id is $mallak_user\n";
    $stmt = $connect->prepare("SELECT * FROM clinic WHERE email = 'mallak@gmail.com'");
    $stmt->execute();
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($clinic) {
        echo "Mallak clinic exists. Updating status to verified.\n";
        $connect->query("UPDATE clinic SET status = 'verified' WHERE clinic_id = " . $clinic['clinic_id']);
    }
} else {
    echo "Mallak user not found.\n";
}

// Moaz children cleanup
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'moaz@gmail.com'");
$stmt->execute();
$moaz_user = $stmt->fetchColumn();

if ($moaz_user) {
    $stmt = $connect->prepare("SELECT parent_id FROM parent WHERE parent_id = ?");
    $stmt->execute([$moaz_user]);
    $moaz_parent = $stmt->fetchColumn();
    
    // Cascading deletes
    $children = $connect->query("SELECT child_id FROM child WHERE parent_id = $moaz_parent")->fetchAll(PDO::FETCH_COLUMN);
    if(count($children) > 0) {
        $c_ids = implode(',', $children);
        
        $connect->query("DELETE FROM speech_analysis WHERE sample_id IN (SELECT sample_id FROM voice_sample WHERE child_id IN ($c_ids))");
        $connect->query("DELETE FROM voice_sample WHERE child_id IN ($c_ids)");
        
        $connect->query("DELETE FROM child_badge WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM child_activities WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM growth_record WHERE child_id IN ($c_ids)");
        
        $connect->query("DELETE FROM prescriptions WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM shared_reports WHERE child_id IN ($c_ids)");
        
        // Disable foreign keys just in case
        $connect->query("SET FOREIGN_KEY_CHECKS = 0");
        $connect->query("DELETE FROM appointment WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM doctor_report WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM child_last_login WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM points_wallet WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM streaks WHERE child_id IN ($c_ids)");
        $connect->query("DELETE FROM child WHERE parent_id = $moaz_parent");
        $connect->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    echo "Deleted all children for Moaz.\n";

    // Recreate exactly 3 children
    $stmt = $connect->prepare("INSERT INTO child (ssn, parent_id, first_name, last_name, gender, birth_day, birth_month, birth_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Omar (4 yrs)
    $stmt->execute([uniqid('ssn_'), $moaz_parent, 'Omar', 'Moaz', 'male', 10, 3, 2022]);
    $omar_id = $connect->lastInsertId();
    
    // Laila (2 yrs)
    $stmt->execute([uniqid('ssn_'), $moaz_parent, 'Laila', 'Moaz', 'female', 15, 5, 2024]);
    $laila_id = $connect->lastInsertId();
    
    // Yassin (8 mo)
    $stmt->execute([uniqid('ssn_'), $moaz_parent, 'Yassin', 'Moaz', 'male', 1, 10, 2025]);
    $yassin_id = $connect->lastInsertId();
    
    echo "Recreated Omar ($omar_id), Laila ($laila_id), Yassin ($yassin_id)\n";

    // Insert historical growth records in ASCENDING order of time to fix the graph
    $gstmt = $connect->prepare("INSERT INTO growth_record (child_id, recorded_at, weight, height, head_circumference) VALUES (?, DATE_SUB(NOW(), INTERVAL ? MONTH), ?, ?, ?)");
    
    for($i = 12; $i >= 1; $i--) {
        $gstmt->execute([$omar_id, $i, 15 - ($i*0.2), 100 - ($i*1.5), 50 - ($i*0.1)]);
        $gstmt->execute([$laila_id, $i, 12 - ($i*0.3), 85 - ($i*2), 48 - ($i*0.1)]);
        $gstmt->execute([$yassin_id, $i, 8 - ($i*0.5), 70 - ($i*3), 45 - ($i*0.2)]);
    }
    
    // Insert traffic light historical speech records
    $s_stmt = $connect->prepare("INSERT INTO voice_sample (child_id, feedback, audio_url, sent_at) VALUES (?, ?, 'audio.wav', DATE_SUB(NOW(), INTERVAL ? DAY))");
    for($i = 6; $i >= 1; $i--) {
        $s_stmt->execute([$omar_id, "Pronunciation score is " . (70 + $i*5) . "%", $i*15]);
        $s_stmt->execute([$laila_id, "Progress is slow. Score: " . (40 + $i*3) . "%", $i*15]); 
    }

    echo "Inserted historical data.\n";
}
