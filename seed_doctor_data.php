<?php
/**
 * Seeds the doctor dashboard with sample data
 * Adapted to actual DB schema (no status on users/clinic)
 */
require 'connection.php';

echo "=== Seeding Doctor Dashboard Data ===\n\n";

try {
    $connect->beginTransaction();

    // 1. Create admin if not exists
    $stmt = $connect->query("SELECT user_id FROM users WHERE role='admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        $connect->exec("INSERT INTO users (first_name, last_name, email, password, role) 
            VALUES ('Super','Admin','admin@brightsteps.com','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin')");
        $adminId = $connect->lastInsertId();
        $connect->exec("INSERT IGNORE INTO admin (admin_id, role_level) VALUES ($adminId, 1)");
        echo "Created admin (ID: $adminId)\n";
    } else {
        $adminId = $admin['user_id'];
        echo "Admin exists (ID: $adminId)\n";
    }

    // 2. Create clinic (no status/rating columns)
    $stmt = $connect->query("SELECT clinic_id FROM clinic LIMIT 1");
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$clinic) {
        $connect->exec("INSERT INTO clinic (admin_id, clinic_name, email, password, location) 
            VALUES ($adminId, 'City Kids Care', 'info@citykids.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Downtown Blvd')");
        $clinicId = $connect->lastInsertId();
        echo "Created clinic (ID: $clinicId)\n";
    } else {
        $clinicId = $clinic['clinic_id'];
        echo "Clinic exists (ID: $clinicId)\n";
    }

    // 3. Create specialist
    $stmt = $connect->query("SELECT specialist_id FROM specialist LIMIT 1");
    $spec = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$spec) {
        $connect->exec("INSERT INTO users (first_name, last_name, email, password, role) 
            VALUES ('Sarah','Mitchell','sarah.m@citykids.com','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','specialist')");
        $specUserId = $connect->lastInsertId();
        $connect->exec("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years) 
            VALUES ($specUserId, $clinicId, 'Sarah', 'Mitchell', 'Pediatrician', 8)");
        echo "Created specialist Dr. Sarah Mitchell (ID: $specUserId)\n";
    } else {
        $specUserId = $spec['specialist_id'];
        echo "Specialist exists (ID: $specUserId)\n";
    }

    // 4. Create parents
    $parentIds = [];
    $parentData = [
        ['Sarah', 'Johnson', 'sarah.j@email.com'],
        ['Michael', 'Thompson', 'michael.t@email.com'],
        ['Jennifer', 'Williams', 'jennifer.w@email.com'],
    ];
    foreach ($parentData as $pd) {
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$pd[2]]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $pid = $existing['user_id'];
        } else {
            $connect->exec("INSERT INTO users (first_name, last_name, email, password, role) 
                VALUES ('{$pd[0]}','{$pd[1]}','{$pd[2]}','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','parent')");
            $pid = $connect->lastInsertId();
        }
        $connect->exec("INSERT IGNORE INTO parent (parent_id, number_of_children) VALUES ($pid, 0)");
        $parentIds[] = $pid;
        echo "Parent {$pd[0]} {$pd[1]} (ID: $pid)\n";
    }

    // 5. Create children
    $childIds = [];
    $childData = [
        [$parentIds[0], 'Emma', 'Johnson', 'female', 2024, 1, 15],
        [$parentIds[0], 'Noah', 'Johnson', 'male', 2023, 6, 10],
        [$parentIds[1], 'Liam', 'Thompson', 'male', 2024, 6, 1],
        [$parentIds[2], 'Olivia', 'Williams', 'female', 2025, 4, 20],
    ];
    foreach ($childData as $idx => $cd) {
        $stmt = $connect->prepare("SELECT child_id FROM child WHERE first_name=? AND last_name=? AND parent_id=?");
        $stmt->execute([$cd[1], $cd[2], $cd[0]]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $childIds[] = $existing['child_id'];
            echo "Child {$cd[1]} {$cd[2]} exists (ID: {$existing['child_id']})\n";
        } else {
            $childIdVal = $idx + 1;
            // Find next available child_id
            $maxStmt = $connect->query("SELECT COALESCE(MAX(child_id), 0) + 1 as next_id FROM child");
            $nextId = $maxStmt->fetch()['next_id'];
            if ($nextId <= $childIdVal) $childIdVal = $nextId;
            else $childIdVal = $nextId;
            $ssn = 'SSN' . rand(10000, 99999);
            $connect->exec("INSERT INTO child (ssn, child_id, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) 
                VALUES ('$ssn', $childIdVal, {$cd[0]}, '{$cd[1]}', '{$cd[2]}', {$cd[6]}, {$cd[5]}, {$cd[4]}, '{$cd[3]}')");
            $childIds[] = $childIdVal;
            echo "Created child {$cd[1]} {$cd[2]} (ID: $childIdVal)\n";
        }
    }

    // 6. Create payments
    $paymentIds = [];
    $stmt = $connect->query("SELECT payment_id FROM payment LIMIT 6");
    $existingPayments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($existingPayments) < 6) {
        $stmt = $connect->query("SELECT subscription_id FROM subscription LIMIT 1");
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub) {
            $connect->exec("INSERT INTO subscription (plan_name, plan_period, price) VALUES ('Standard','monthly',9.99)");
            $subId = $connect->lastInsertId();
        } else {
            $subId = $sub['subscription_id'];
        }
        for ($i = count($existingPayments); $i < 6; $i++) {
            $connect->exec("INSERT INTO payment (subscription_id, amount_pre_discount, discount_rate, method, status) 
                VALUES ($subId, 50.00, 0.00, 'credit_card', 'completed')");
            $existingPayments[] = $connect->lastInsertId();
        }
        echo "Created payments\n";
    }
    $paymentIds = $existingPayments;

    // 7. Create appointments
    $stmt = $connect->prepare("SELECT COUNT(*) as c FROM appointment WHERE specialist_id = ?");
    $stmt->execute([$specUserId]);
    if ($stmt->fetch()['c'] == 0) {
        $apptData = [
            [$parentIds[0], $paymentIds[0], 'completed', 'onsite', '2026-03-01 10:00:00'],
            [$parentIds[0], $paymentIds[1], 'scheduled', 'online', '2026-04-10 14:00:00'],
            [$parentIds[1], $paymentIds[2], 'completed', 'online', '2026-02-20 09:30:00'],
            [$parentIds[1], $paymentIds[3], 'scheduled', 'onsite', '2026-04-15 11:00:00'],
            [$parentIds[2], $paymentIds[4], 'completed', 'onsite', '2026-03-05 15:00:00'],
            [$parentIds[2], $paymentIds[5], 'cancelled', 'online', '2026-03-28 10:00:00'],
        ];
        foreach ($apptData as $a) {
            $connect->exec("INSERT INTO appointment (parent_id, payment_id, specialist_id, status, type, scheduled_at) 
                VALUES ({$a[0]}, {$a[1]}, $specUserId, '{$a[2]}', '{$a[3]}', '{$a[4]}')");
        }
        echo "Created 6 appointments\n";
    } else {
        echo "Appointments exist\n";
    }

    // 8. Child system reports
    foreach ($childIds as $cid) {
        $connect->exec("INSERT IGNORE INTO child_generated_system_report (child_id, report) 
            VALUES ($cid, 'Developmental milestone assessment completed.')");
    }
    echo "Created child system reports\n";

    // 9. Doctor reports
    $stmt = $connect->prepare("SELECT COUNT(*) as c FROM doctor_report WHERE specialist_id = ?");
    $stmt->execute([$specUserId]);
    if ($stmt->fetch()['c'] == 0) {
        $connect->exec("INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date) VALUES
            ($specUserId, {$childIds[0]}, 'Motor skills assessment', 'All developmental milestones within expected range. Fine motor control excellent.', 'Continue daily exercises. Follow-up in 3 months.', '2026-03-01'),
            ($specUserId, {$childIds[2]}, 'Language delay assessment', 'Language development concerns noted. Limited verbal output.', 'Schedule audiological evaluation. Begin speech therapy.', '2026-02-20')");
        echo "Created 2 doctor reports\n";
    }

    // 10. Messages
    $stmt = $connect->prepare("SELECT COUNT(*) as c FROM message WHERE sender_id = ? OR receiver_id = ?");
    $stmt->execute([$specUserId, $specUserId]);
    if ($stmt->fetch()['c'] == 0) {
        $msgs = [
            [$parentIds[0], $specUserId, "Hi Dr. Mitchell, I wanted to share Emma's report with you."],
            [$specUserId, $parentIds[0], "Thank you Sarah! I'm pleased with Emma's progress."],
            [$parentIds[0], $specUserId, "Should we continue with the same exercises?"],
            [$specUserId, $parentIds[0], "Yes, continue the current routine."],
            [$parentIds[0], $specUserId, "Thank you doctor for the report!"],
            [$parentIds[1], $specUserId, "Hello Dr. Mitchell, I have a concern about Liam."],
            [$specUserId, $parentIds[1], "Hi Michael, I recommend a detailed evaluation."],
            [$parentIds[1], $specUserId, "When is Liam's next appointment?"],
            [$parentIds[2], $specUserId, "Olivia is doing great with the exercises."],
            [$specUserId, $parentIds[2], "Wonderful. Keep up the great work!"],
        ];
        $stmtMsg = $connect->prepare("INSERT INTO message (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        foreach ($msgs as $m) {
            $stmtMsg->execute([$m[0], $m[1], $m[2]]);
        }
        echo "Created 10 messages\n";
    }

    // 11. Feedback
    $stmt = $connect->prepare("SELECT COUNT(*) as c FROM feedback WHERE specialist_id = ?");
    $stmt->execute([$specUserId]);
    if ($stmt->fetch()['c'] == 0) {
        $connect->exec("INSERT INTO feedback (parent_id, specialist_id, content, rating) VALUES
            ({$parentIds[0]}, $specUserId, 'Excellent pediatrician!', 5),
            ({$parentIds[1]}, $specUserId, 'Very helpful with Liam.', 4),
            ({$parentIds[2]}, $specUserId, 'Great experience!', 5)");
        echo "Created 3 feedback entries\n";
    }

    $connect->commit();
    echo "\n=== DONE! ===\n";
    echo ">>> Specialist ID = $specUserId <<<\n";
    echo ">>> Make sure SPECIALIST_ID in doctor-dashboard.js matches this value <<<\n";

} catch (Exception $e) {
    $connect->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
