<?php
/**
 * Bulletproof Seed Script — Doctor Dashboard
 * Ensures specialist record exists, then seeds all data with explicit errors.
 */
session_start();
require 'connection.php';
header('Content-Type: text/plain; charset=utf-8');

$results = [];
$pw = password_hash('12345678', PASSWORD_DEFAULT);

// ─── Detect doctor from session ───
$DOCTOR_ID = intval($_SESSION['specialist_id'] ?? $_SESSION['id'] ?? 0);
if (!$DOCTOR_ID) {
    try {
        $r = $connect->query("SELECT user_id FROM users WHERE role IN ('doctor','specialist') LIMIT 1")->fetch();
        if ($r) $DOCTOR_ID = intval($r['user_id']);
    } catch (Exception $e) {}
}
if (!$DOCTOR_ID) die("❌ No doctor found. Log in first.\n");

// Get doctor info
$doc = $connect->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$doc->execute([$DOCTOR_ID]);
$doc = $doc->fetch(PDO::FETCH_ASSOC);
echo "═══════════════════════════════════════════\n";
echo "  Doctor: {$doc['first_name']} {$doc['last_name']} (ID: $DOCTOR_ID)\n";
echo "═══════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════
// STEP 0: ENSURE SPECIALIST RECORD EXISTS (root cause fix)
// ═══════════════════════════════════════════════════
try {
    $chk = $connect->prepare("SELECT specialist_id, clinic_id FROM specialist WHERE specialist_id = ?");
    $chk->execute([$DOCTOR_ID]);
    $specRow = $chk->fetch(PDO::FETCH_ASSOC);
    
    if (!$specRow) {
        // Need a clinic first
        $clinicRow = $connect->query("SELECT clinic_id FROM clinic LIMIT 1")->fetch();
        $CLINIC_ID = $clinicRow ? intval($clinicRow['clinic_id']) : 1;
        
        // Create clinic if none exists
        if (!$clinicRow) {
            $connect->exec("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role) VALUES (5000, 'Admin', 'System', 'admin-seed@brightsteps.com', '$pw', 'admin')");
            $connect->exec("INSERT IGNORE INTO admin (admin_id, role_level) VALUES (5000, 1)");
            $connect->exec("INSERT IGNORE INTO clinic (clinic_id, admin_id, clinic_name, location, email, status) VALUES (1, 5000, 'Bright Steps Center', 'Cairo', 'clinic@brightsteps.com', 'active')");
            $CLINIC_ID = 1;
        }
        
        $connect->exec("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years) VALUES ($DOCTOR_ID, $CLINIC_ID, '{$doc['first_name']}', '{$doc['last_name']}', 'pediatrician', 10)");
        $results[] = "✓ CREATED specialist record (ID: $DOCTOR_ID, clinic: $CLINIC_ID) — THIS WAS THE MISSING PIECE!";
    } else {
        $CLINIC_ID = intval($specRow['clinic_id']);
        $results[] = "✓ Specialist exists (ID: $DOCTOR_ID, clinic: $CLINIC_ID)";
    }
} catch (Exception $e) {
    die("❌ Cannot create specialist: " . $e->getMessage() . "\n");
}

// ═══════════════════════════════════════════════════
// STEP 1: ONBOARDING
// ═══════════════════════════════════════════════════
try {
    $connect->exec("CREATE TABLE IF NOT EXISTS doctor_onboarding (id INT AUTO_INCREMENT PRIMARY KEY, doctor_id INT NOT NULL, specialization VARCHAR(100), experience_years INT DEFAULT 0, certifications VARCHAR(255), focus_areas TEXT, working_days TEXT, start_time TIME DEFAULT '09:00:00', end_time TIME DEFAULT '17:00:00', consultation_types TEXT, goals TEXT, completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $chk = $connect->prepare("SELECT id FROM doctor_onboarding WHERE doctor_id = ?"); $chk->execute([$DOCTOR_ID]);
    if (!$chk->fetch()) $connect->exec("INSERT INTO doctor_onboarding (doctor_id, specialization, experience_years) VALUES ($DOCTOR_ID, 'pediatrician', 10)");
    $results[] = "✓ Onboarding ensured";
} catch (Exception $e) { $results[] = "⚠ Onboarding: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 2: PARENTS
// ═══════════════════════════════════════════════════
try {
    $parents = [[5100,'Sara','Mohamed','sara@test.com'],[5101,'Fatma','Ali','fatma@test.com'],[5102,'Nour','Ibrahim','nour@test.com'],[5103,'Hana','Youssef','hana@test.com'],[5104,'Layla','Khaled','layla@test.com'],[5105,'Mariam','Samir','mariam@test.com'],[5106,'Dina','Farouk','dina@test.com']];
    foreach ($parents as $p) {
        $connect->exec("INSERT IGNORE INTO users (user_id, first_name, last_name, email, password, role) VALUES ({$p[0]}, '{$p[1]}', '{$p[2]}', '{$p[3]}', '$pw', 'parent')");
        $connect->exec("INSERT IGNORE INTO parent (parent_id, number_of_children) VALUES ({$p[0]}, 1)");
    }
    $results[] = "✓ 7 parents";
} catch (Exception $e) { $results[] = "⚠ Parents: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 3: CHILDREN
// ═══════════════════════════════════════════════════
try {
    $children = [[5200,5100,'Omar','Mohamed',15,3,2021,'M'],[5201,5100,'Lina','Mohamed',22,8,2023,'F'],[5202,5101,'Yara','Ali',10,7,2020,'F'],[5203,5102,'Adam','Ibrahim',5,1,2023,'M'],[5204,5103,'Mona','Youssef',18,11,2019,'F'],[5205,5104,'Zain','Khaled',25,9,2022,'M'],[5206,5105,'Salma','Samir',3,5,2021,'F'],[5207,5106,'Kareem','Farouk',12,2,2022,'M'],[5208,5106,'Nadia','Farouk',8,6,2024,'F']];
    foreach ($children as $c) {
        $ssn = 'SSN' . str_pad($c[0], 6, '0', STR_PAD_LEFT);
        $connect->exec("INSERT IGNORE INTO child (ssn, child_id, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) VALUES ('$ssn', {$c[0]}, {$c[1]}, '{$c[2]}', '{$c[3]}', {$c[4]}, {$c[5]}, {$c[6]}, '{$c[7]}')");
    }
    $results[] = "✓ 9 children";
} catch (Exception $e) { $results[] = "⚠ Children: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 4: PAYMENTS
// ═══════════════════════════════════════════════════
try {
    for ($i = 0; $i < 30; $i++) {
        $pid = 5000 + $i;
        $connect->exec("INSERT IGNORE INTO payment (payment_id, amount_pre_discount, discount_rate, amount_post_discount, method, status) VALUES ($pid, 350.00, 0.00, 350.00, 'card', 'completed')");
    }
    $results[] = "✓ 30 payments";
} catch (Exception $e) { $results[] = "⚠ Payments: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 5: APPOINTMENTS (delete old seed + re-insert)
// ═══════════════════════════════════════════════════
try {
    // Clean old seed appointments
    $connect->exec("DELETE FROM appointment WHERE appointment_id BETWEEN 5000 AND 5029");
    
    $pp = [5100,5101,5102,5103,5104,5105,5106];
    $inserted = 0;
    for ($i = 0; $i < 30; $i++) {
        $aid = 5000 + $i; $payId = 5000 + $i;
        $parentId = $pp[$i % 7];
        if ($i < 5)       { $off = -rand(1,7);    $st = 'completed'; }
        elseif ($i < 10)  { $off = -rand(8,30);   $st = 'completed'; }
        elseif ($i < 15)  { $off = -rand(31,60);  $st = 'completed'; }
        elseif ($i < 20)  { $off = -rand(61,180); $st = ($i%3==0)?'cancelled':'completed'; }
        elseif ($i < 25)  { $off = rand(1,14);    $st = 'scheduled'; }
        else              { $off = rand(1,7);     $st = 'confirmed'; }
        $dt = date('Y-m-d H:i:s', strtotime("$off days " . rand(9,16) . ":00:00"));
        $type = ($i%2==0)?'online':'onsite';
        $connect->exec("INSERT INTO appointment (appointment_id, parent_id, payment_id, specialist_id, status, type, scheduled_at) VALUES ($aid, $parentId, $payId, $DOCTOR_ID, '$st', '$type', '$dt')");
        $inserted++;
    }
    $results[] = "✓ $inserted appointments inserted (doctor $DOCTOR_ID)";
} catch (Exception $e) { $results[] = "❌ Appointments: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 6: APPOINTMENT SLOTS
// ═══════════════════════════════════════════════════
try {
    $connect->exec("CREATE TABLE IF NOT EXISTS appointment_slots (slot_id INT AUTO_INCREMENT PRIMARY KEY, doctor_id INT NOT NULL, clinic_id INT NOT NULL, day_of_week TINYINT(1) NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, slot_duration INT DEFAULT 30, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connect->exec("DELETE FROM appointment_slots WHERE doctor_id = $DOCTOR_ID");
    foreach ([0,1,2,3,4] as $d) $connect->exec("INSERT INTO appointment_slots (doctor_id, clinic_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES ($DOCTOR_ID, $CLINIC_ID, $d, '09:00:00', '17:00:00', 30, 1)");
    $results[] = "✓ Availability slots (Sun-Thu)";
} catch (Exception $e) { $results[] = "⚠ Slots: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 7: DOCTOR REPORTS (delete old + re-insert)
// ═══════════════════════════════════════════════════
try {
    $connect->exec("DELETE FROM doctor_report WHERE specialist_id = $DOCTOR_ID AND child_id BETWEEN 5200 AND 5210");
    $dr = [[5200,'Motor development normal.','Daily reading. Follow up 3 months.','-15 days'],[5202,'Excellent articulation progress.','Continue speech exercises.','-30 days'],[5203,'Mild language delay.','Speech therapy 2x/week.','-45 days'],[5204,'Exceeds milestones.','No intervention needed.','-60 days'],[5205,'Sensory eval done.','OT referral provided.','-20 days'],[5206,'Strong fine motor.','Encourage outdoor play.','-10 days'],[5200,'Follow-up: Speech progressing.','Next follow-up 6 months.','-5 days']];
    foreach ($dr as $d) {
        $notes = addslashes($d[1]); $recs = addslashes($d[2]);
        $repDate = date('Y-m-d', strtotime($d[3])); $ca = date('Y-m-d H:i:s', strtotime($d[3]));
        $connect->exec("INSERT INTO doctor_report (specialist_id, child_id, doctor_notes, recommendations, report_date, created_at) VALUES ($DOCTOR_ID, {$d[0]}, '$notes', '$recs', '$repDate', '$ca')");
    }
    $results[] = "✓ 7 doctor reports";
} catch (Exception $e) { $results[] = "❌ Reports: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 8: FEEDBACK (delete old + re-insert)
// ═══════════════════════════════════════════════════
try {
    $connect->exec("DELETE FROM feedback WHERE specialist_id = $DOCTOR_ID AND parent_id BETWEEN 5100 AND 5106");
    $rv = [[5100,5,'Amazing with children!'],[5101,4,'Great doctor.'],[5102,5,'Highly recommend!'],[5103,4,'Good experience.'],[5104,5,'Best pediatrician!'],[5105,5,'Excellent skills.'],[5106,4,'Professional and kind.']];
    foreach ($rv as $r) {
        $c = addslashes($r[2]);
        $connect->exec("INSERT INTO feedback (parent_id, specialist_id, content, rating) VALUES ({$r[0]}, $DOCTOR_ID, '$c', {$r[1]})");
    }
    $results[] = "✓ 7 reviews (avg 4.6★)";
} catch (Exception $e) { $results[] = "❌ Feedback: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 9: NOTIFICATIONS
// ═══════════════════════════════════════════════════
try {
    $ns = [['system','Appointment Confirmed','Sara confirmed Sunday 10 AM.','-2 hours'],['system','New Patient Report','Report generated for Omar.','-4 hours'],['system','Upcoming','3 appointments tomorrow.','-1 day'],['system','Monthly Summary','12 appointments this month.','-3 days']];
    foreach ($ns as $n) {
        $t = addslashes($n[1]); $m = addslashes($n[2]); $ca = date('Y-m-d H:i:s', strtotime($n[3]));
        $connect->exec("INSERT IGNORE INTO notifications (user_id, type, title, message, is_read, created_at) VALUES ($DOCTOR_ID, '{$n[0]}', '$t', '$m', 0, '$ca')");
    }
    $results[] = "✓ Notifications";
} catch (Exception $e) { $results[] = "⚠ Notifications: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 10: MESSAGES (conversations between doctor & parents)
// ═══════════════════════════════════════════════════
try {
    $connect->exec("DELETE FROM message WHERE (sender_id = $DOCTOR_ID OR receiver_id = $DOCTOR_ID) AND (sender_id BETWEEN 5100 AND 5106 OR receiver_id BETWEEN 5100 AND 5106)");
    $msgs = [
        // Sara (5100) — conversation about Omar
        [5100,$DOCTOR_ID,"Hi Dr, I wanted to ask about Omar's speech development.",'-5 days -3 hours'],
        [$DOCTOR_ID,5100,"Hello Sara! Omar is making good progress. His articulation has improved significantly since our last session.",'-5 days -2 hours'],
        [5100,$DOCTOR_ID,"That's great to hear! Should we continue the current exercises?",'-5 days -1 hour'],
        [$DOCTOR_ID,5100,"Yes, keep doing the daily reading exercises. I'd also recommend adding some new vocabulary games. I'll share some resources at our next appointment.",'-5 days'],
        [5100,$DOCTOR_ID,"Thank you so much, Doctor!",'-4 days -5 hours'],
        [$DOCTOR_ID,5100,"You're welcome! See you next Sunday at 10 AM.",'-4 days -4 hours'],
        // Fatma (5101) — conversation about Yara
        [5101,$DOCTOR_ID,"Doctor, Yara has been having trouble sleeping lately. Is this related to her therapy?",'-3 days -6 hours'],
        [$DOCTOR_ID,5101,"Hi Fatma. Sleep disruptions can happen during developmental transitions. How many hours is she sleeping?",'-3 days -5 hours'],
        [5101,$DOCTOR_ID,"About 6-7 hours. She used to sleep 9-10.",'-3 days -4 hours'],
        [$DOCTOR_ID,5101,"I'd recommend establishing a consistent bedtime routine. Let's discuss this in detail at our next appointment. If it persists beyond a week, we should investigate further.",'-3 days -3 hours'],
        // Nour (5102) — conversation about Adam
        [5102,$DOCTOR_ID,"Good morning Doctor. Adam said his first full sentence today!",'-2 days -8 hours'],
        [$DOCTOR_ID,5102,"That's wonderful news, Nour! What did he say?",'-2 days -7 hours'],
        [5102,$DOCTOR_ID,"He said 'I want milk please' — all four words!",'-2 days -6 hours'],
        [$DOCTOR_ID,5102,"Excellent progress! This is a major milestone. Keep encouraging him. I'll note this in his file for our next review.",'-2 days -5 hours'],
        // Hana (5103) — about Mona
        [$DOCTOR_ID,5103,"Hi Hana, just a reminder about Mona's follow-up appointment this Thursday.",'-1 day -4 hours'],
        [5103,$DOCTOR_ID,"Thank you for the reminder! We'll be there at 2 PM.",'-1 day -3 hours'],
        // Layla (5104) — about Zain
        [5104,$DOCTOR_ID,"Doctor, I noticed Zain is making more eye contact now during play time.",'-12 hours'],
        [$DOCTOR_ID,5104,"That's a great sign, Layla! Increased eye contact is one of the key social indicators we track. Keep up the joint play activities.",'-11 hours'],
        [5104,$DOCTOR_ID,"Will do! Thank you for all your help.",'-10 hours'],
        // Mariam (5105) — about Salma
        [5105,$DOCTOR_ID,"Hi Doctor, can we reschedule Salma's appointment from Monday to Wednesday?",'-8 hours'],
        [$DOCTOR_ID,5105,"Of course, Mariam. Wednesday at 11 AM works. I'll update the schedule.",'-7 hours'],
        [5105,$DOCTOR_ID,"Perfect, thank you!",'-6 hours'],
        // Dina (5106) — about Kareem & Nadia
        [5106,$DOCTOR_ID,"Doctor, I have questions about both Kareem and Nadia's progress reports.",'-4 hours'],
        [$DOCTOR_ID,5106,"Hi Dina! Sure, let's discuss both. Kareem's motor skills are improving well, and Nadia's sensory evaluation came back positive.",'-3 hours'],
        [5106,$DOCTOR_ID,"That's reassuring. When should we schedule the next evaluation?",'-2 hours'],
        [$DOCTOR_ID,5106,"I'd suggest in 3 weeks. I'll send you available slots tomorrow.",'-1 hour'],
    ];
    foreach ($msgs as $m) {
        $content = addslashes($m[2]);
        $sentAt = date('Y-m-d H:i:s', strtotime($m[3]));
        $connect->exec("INSERT INTO message (sender_id, receiver_id, content, is_read, sent_at) VALUES ({$m[0]}, {$m[1]}, '$content', 1, '$sentAt')");
    }
    $results[] = "✓ " . count($msgs) . " messages (7 conversations)";
} catch (Exception $e) { $results[] = "❌ Messages: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 11: SHARED CHILD REPORTS (for Reports → Shared tab)
// ═══════════════════════════════════════════════════
try {
    $connect->exec("DELETE FROM child_generated_system_report WHERE child_id BETWEEN 5200 AND 5208");
    $reports = [
        [5200,"Speech assessment: Omar shows age-appropriate vocabulary but mild articulation delay in consonant clusters."],
        [5201,"Cognitive screening: Lina demonstrates advanced problem-solving skills for her age group."],
        [5202,"Behavioral assessment: Yara exhibits excellent social skills but shows signs of separation anxiety."],
        [5203,"Language evaluation: Adam has moderate expressive language delay. Receptive language within normal range."],
        [5204,"Developmental screening: Mona exceeds all developmental milestones for her age group."],
        [5205,"Sensory profile: Zain shows hypersensitivity to auditory stimuli and tactile defensiveness."],
        [5206,"Motor assessment: Salma demonstrates strong fine motor skills. Gross motor coordination needs support."],
        [5207,"Cognitive assessment: Kareem shows strong visual-spatial reasoning. Attention span is below average for age."],
        [5208,"Early development: Nadia is meeting most milestones. Slight delay in expressive language noted."],
    ];
    foreach ($reports as $r) {
        $rep = addslashes($r[1]);
        $connect->exec("INSERT IGNORE INTO child_generated_system_report (child_id, report) VALUES ({$r[0]}, '$rep')");
    }
    $results[] = "✓ " . count($reports) . " shared child reports";
} catch (Exception $e) { $results[] = "❌ Shared reports: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 12: GROWTH RECORDS
// ═══════════════════════════════════════════════════
try {
    $connect->exec("DELETE FROM growth_record WHERE child_id BETWEEN 5200 AND 5208");
    $growth = [
        [5200,85.5,12.3,47.2,'-30 days'],[5200,87.0,12.8,47.5,'-5 days'],
        [5201,62.0,7.5,42.0,'-20 days'],
        [5202,105.0,17.5,50.1,'-15 days'],
        [5203,72.0,9.8,44.5,'-25 days'],[5203,74.5,10.2,45.0,'-3 days'],
        [5204,112.0,20.0,51.0,'-40 days'],
        [5205,78.0,10.5,46.0,'-10 days'],
        [5206,82.0,11.0,46.8,'-18 days'],
        [5207,80.0,11.5,46.5,'-12 days'],
        [5208,60.0,6.8,41.5,'-8 days'],
    ];
    foreach ($growth as $g) {
        $recAt = date('Y-m-d H:i:s', strtotime($g[4]));
        $connect->exec("INSERT INTO growth_record (child_id, height, weight, head_circumference, recorded_at) VALUES ({$g[0]}, {$g[1]}, {$g[2]}, {$g[3]}, '$recAt')");
    }
    $results[] = "✓ " . count($growth) . " growth records";
} catch (Exception $e) { $results[] = "❌ Growth records: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// STEP 13: MILESTONES
// ═══════════════════════════════════════════════════
try {
    // Ensure milestones table has data
    $mCount = $connect->query("SELECT COUNT(*) n FROM milestones")->fetch()['n'] ?? 0;
    if ($mCount == 0) {
        $milestones = [
            ['First Smile','social','Baby smiles in response to stimulation'],
            ['Rolls Over','motor','Baby rolls from tummy to back'],
            ['Sits Without Support','motor','Baby sits independently'],
            ['First Words','language','Baby says first meaningful words'],
            ['Walks Independently','motor','Child walks without assistance'],
            ['Points to Objects','cognitive','Child points to show interest'],
            ['Two-Word Phrases','language','Child combines two words together'],
            ['Follows Instructions','cognitive','Child follows simple one-step directions'],
        ];
        foreach ($milestones as $ml) {
            $t = addslashes($ml[0]); $c = addslashes($ml[1]); $d = addslashes($ml[2]);
            $connect->exec("INSERT INTO milestones (title, category, description) VALUES ('$t', '$c', '$d')");
        }
    }
    // Get milestone IDs
    $allM = $connect->query("SELECT milestone_id FROM milestones ORDER BY milestone_id LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
    if (count($allM) > 0) {
        $connect->exec("DELETE FROM child_milestones WHERE child_id BETWEEN 5200 AND 5208");
        $childMilestones = [
            [5200, array_slice($allM, 0, 5), '-60 days'],
            [5202, array_slice($allM, 0, 7), '-45 days'],
            [5204, $allM, '-30 days'],
            [5203, array_slice($allM, 0, 3), '-20 days'],
            [5205, array_slice($allM, 0, 4), '-15 days'],
        ];
        $cmCount = 0;
        foreach ($childMilestones as $cm) {
            foreach ($cm[1] as $mid) {
                $achAt = date('Y-m-d H:i:s', strtotime($cm[2] . ' +' . rand(0,10) . ' days'));
                $connect->exec("INSERT IGNORE INTO child_milestones (child_id, milestone_id, achieved_at) VALUES ({$cm[0]}, $mid, '$achAt')");
                $cmCount++;
            }
        }
        $results[] = "✓ $cmCount child milestones";
    }
} catch (Exception $e) { $results[] = "⚠ Milestones: " . $e->getMessage(); }

// ═══════════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════════
echo "\n── RESULTS ──\n";
foreach ($results as $r) echo "  $r\n";

// Quick verification
echo "\n── VERIFICATION ──\n";
$v = [
    ["Patients", "SELECT COUNT(DISTINCT c.child_id) n FROM appointment a JOIN child c ON c.parent_id=a.parent_id WHERE a.specialist_id=$DOCTOR_ID"],
    ["Appointments", "SELECT COUNT(*) n FROM appointment WHERE specialist_id=$DOCTOR_ID"],
    ["Reports (Doctor)", "SELECT COUNT(*) n FROM doctor_report WHERE specialist_id=$DOCTOR_ID"],
    ["Reports (Shared)", "SELECT COUNT(*) n FROM child_generated_system_report WHERE child_id BETWEEN 5200 AND 5208"],
    ["Messages", "SELECT COUNT(*) n FROM message WHERE sender_id=$DOCTOR_ID OR receiver_id=$DOCTOR_ID"],
    ["Growth Records", "SELECT COUNT(*) n FROM growth_record WHERE child_id BETWEEN 5200 AND 5208"],
    ["Feedback", "SELECT COUNT(*) n FROM feedback WHERE specialist_id=$DOCTOR_ID"],
];
foreach ($v as $c) {
    try { $r = $connect->query($c[1])->fetch(); echo "  {$c[0]}: {$r['n']}\n"; }
    catch (Exception $e) { echo "  {$c[0]}: ⚠ " . $e->getMessage() . "\n"; }
}
echo "\n  ✅ Go to doctor-dashboard.php now!\n";

