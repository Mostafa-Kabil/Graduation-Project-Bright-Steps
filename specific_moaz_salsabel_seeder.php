<?php
require 'connection.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting Moaz & Salsabel Specific Seeder...\n";

// Disable foreign key checks to avoid constraint errors during bulk seeding
$connect->query("SET FOREIGN_KEY_CHECKS=0;");

// --- 1. MOAZ'S CHILDREN (Spreading data) ---
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'moaz@gmail.com'");
$stmt->execute();
$moaz_user = $stmt->fetchColumn();

if ($moaz_user) {
    $stmt = $connect->prepare("SELECT parent_id FROM parent WHERE parent_id = ?");
    $stmt->execute([$moaz_user]);
    $moaz_parent = $stmt->fetchColumn();

    $children = $connect->query("SELECT child_id, first_name, birth_year, birth_month, birth_day FROM child WHERE parent_id = $moaz_parent")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($children as $c) {
        $cid = $c['child_id'];
        // Clear existing growth data
        $connect->query("DELETE FROM growth_record WHERE child_id = $cid");

        // Calculate age in months
        $birthDate = new DateTime($c['birth_year'] . '-' . str_pad($c['birth_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($c['birth_day'], 2, '0', STR_PAD_LEFT));
        $now = new DateTime();
        $diff = $now->diff($birthDate);
        $total_months_old = ($diff->y * 12) + $diff->m;

        // Generate data points spread across the child's life
        // e.g. every 2-3 months from birth to now
        $data_points = [];
        for ($m = 2; $m <= $total_months_old; $m += rand(2, 4)) {
            $data_points[] = $m;
        }
        // Ensure we have a recent point
        if (empty($data_points) || end($data_points) != $total_months_old) {
            $data_points[] = $total_months_old > 0 ? $total_months_old : 1;
        }

        $gstmt = $connect->prepare("INSERT INTO growth_record (child_id, recorded_at, weight, height, head_circumference) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data_points as $age_in_months) {
            // Rough WHO medians approximations
            // Weight: birth ~3.5kg, 12m ~9.5kg, 24m ~12.5kg, 36m ~14.5kg, 48m ~16kg, 60m ~18kg
            $w = 3.5 + ($age_in_months * 0.3);
            if ($age_in_months > 12) $w = 9.5 + (($age_in_months - 12) * 0.2);
            if ($age_in_months > 24) $w = 12.5 + (($age_in_months - 24) * 0.15);
            
            // Height: birth ~50cm, 12m ~75cm, 24m ~87cm, 36m ~96cm, 48m ~103cm
            $h = 50 + ($age_in_months * 2);
            if ($age_in_months > 12) $h = 75 + (($age_in_months - 12) * 1);
            if ($age_in_months > 24) $h = 87 + (($age_in_months - 24) * 0.7);

            // Head: birth ~35cm, 12m ~46cm, 24m ~48cm, 36m ~50cm
            $hc = 35 + ($age_in_months * 0.9);
            if ($age_in_months > 12) $hc = 46 + (($age_in_months - 12) * 0.15);

            // Add slight randomness for realism
            $w += (rand(-5, 5) / 10);
            $h += (rand(-10, 10) / 10);
            $hc += (rand(-3, 3) / 10);

            // Calculate date of record
            $recordDate = clone $birthDate;
            $recordDate->modify("+$age_in_months months");
            if ($recordDate > $now) $recordDate = $now;

            $gstmt->execute([$cid, $recordDate->format('Y-m-d H:i:s'), round($w, 1), round($h, 1), round($hc, 1)]);
        }
        echo "Spread growth data for {$c['first_name']} ($total_months_old months old).\n";
    }
}

// --- 2. SALSABEL (Specialist Data & Analytics) ---
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$stmt->execute();
$sal_user = $stmt->fetchColumn();

if ($sal_user) {
    // Ensure she's active
    $connect->query("UPDATE users SET status = 'active' WHERE user_id = $sal_user");

    // Fetch a pool of random children/parents for appointments
    $pool = $connect->query("SELECT child_id, parent_id FROM child LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pool) > 0) {
        $astmt = $connect->prepare("INSERT INTO appointment (parent_id, child_id, specialist_id, status, type, scheduled_at, report, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $pstmt = $connect->prepare("INSERT INTO payment (parent_id, subscription_id, amount_pre_discount, amount_post_discount, method, status, paid_at, token_id) VALUES (?, 1, 300, 300, 'credit_card', 'completed', ?, NULL)");
        $rstmt = $connect->prepare("INSERT INTO specialist_reviews (parent_id, specialist_id, appointment_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $dstmt = $connect->prepare("INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date) VALUES (?, ?, ?, ?, ?, ?)");

        // Generate 60 historical appointments over the past 12 months for analytics
        for ($i = 1; $i <= 60; $i++) {
            $random_child = $pool[array_rand($pool)];
            $cid = $random_child['child_id'];
            $pid = $random_child['parent_id'];
            
            // Random date in past year
            $days_ago = rand(1, 360);
            $appt_date = date("Y-m-d H:i:s", time() - ($days_ago * 86400));
            
            // Status distribution: mostly completed, some cancelled
            $status = rand(1, 10) > 8 ? 'Cancelled' : 'Completed';
            $type = rand(0, 1) ? 'online' : 'onsite';

            // Insert payment if completed
            $payment_id = null;
            if ($status === 'Completed') {
                try {
                    $pstmt->execute([$pid, $appt_date]);
                    $payment_id = $connect->lastInsertId();
                } catch (Exception $e) {
                    // Ignore payment creation error
                }
            }

            // Insert Appointment
            $astmt->execute([$pid, $cid, $sal_user, $status, $type, $appt_date, 'Routine session', '']);
            $appt_id = $connect->lastInsertId();

            if ($payment_id) {
                $connect->query("UPDATE appointment SET payment_id = $payment_id WHERE appointment_id = $appt_id");
            }

            // If completed, add reports and reviews
            if ($status === 'Completed') {
                // Doctor Report
                $dstmt->execute([
                    $sal_user, $cid, 
                    "Session $i Report", 
                    "Child responded well to therapy. Needs focus on articulation.", 
                    "Practice sounds at home 15 mins daily.", 
                    date("Y-m-d", strtotime($appt_date))
                ]);

                // Review (mostly positive)
                if (rand(1, 10) > 4) {
                    $rating = rand(4, 5);
                    $comments = ['Dr. Salsabel is amazing!', 'Very patient with my child.', 'Highly recommended.', 'Saw great improvement.'];
                    $rstmt->execute([$pid, $sal_user, $appt_id, $rating, $comments[array_rand($comments)], $appt_date]);
                }
            }
        }
        echo "Generated 60 historical appointments and reports for Salsabel.\n";

        // Generate 5 upcoming appointments
        for ($i = 1; $i <= 5; $i++) {
            $random_child = $pool[array_rand($pool)];
            $cid = $random_child['child_id'];
            $pid = $random_child['parent_id'];
            $future_date = date("Y-m-d H:i:s", time() + (rand(1, 14) * 86400));
            $astmt->execute([$pid, $cid, $sal_user, 'Scheduled', 'online', $future_date, '', '']);
        }
        echo "Generated 5 upcoming appointments for Salsabel.\n";
    }

    // --- 3. Chat Messages between Moaz and Salsabel ---
    if ($moaz_user) {
        $mstmt = $connect->prepare("INSERT INTO message (sender_id, receiver_id, content, is_read, sent_at) VALUES (?, ?, ?, 1, ?)");
        
        $chat = [
            ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "Hello Dr. Salsabel, I'd like to ask about Omar's progress.", 'days' => 5],
            ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "Hi Moaz! Omar is doing fantastic. His articulation has improved significantly.", 'days' => 5],
            ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "That's great to hear. Should we continue the same exercises?", 'days' => 4],
            ['sender' => $sal_user, 'receiver' => $moaz_user, 'msg' => "Yes, but let's increase the duration to 20 minutes a day. I will upload a new worksheet for him.", 'days' => 4],
            ['sender' => $moaz_user, 'receiver' => $sal_user, 'msg' => "Perfect, thank you so much!", 'days' => 3],
        ];

        foreach ($chat as $msg) {
            $sent_date = date("Y-m-d H:i:s", time() - ($msg['days'] * 86400) + rand(1000, 5000));
            $mstmt->execute([$msg['sender'], $msg['receiver'], $msg['msg'], $sent_date]);
        }
        echo "Generated chat messages between Moaz and Salsabel.\n";
    }
}

echo "Seeding Complete!\n";
?>
