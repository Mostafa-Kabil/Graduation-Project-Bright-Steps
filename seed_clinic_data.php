<?php
include 'connection.php';

echo "Seeding clinic data...\n";

try {
    // Pick the most recent active clinic
    $cStmt = $connect->query("SELECT clinic_id FROM clinic ORDER BY clinic_id DESC LIMIT 1");
    $clinic_id = $cStmt->fetchColumn();

    if (!$clinic_id) {
        die("No clinic found to seed.");
    }

    echo "Using Clinic ID: $clinic_id\n";

    // Find specialists for this clinic
    $sStmt = $connect->prepare("SELECT specialist_id FROM specialist WHERE clinic_id = ?");
    $sStmt->execute([$clinic_id]);
    $specialists = $sStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($specialists)) {
        // Create a dummy specialist if none exist
        $connect->prepare("INSERT INTO specialist (clinic_id, first_name, last_name, specialization) VALUES (?, 'Dummy', 'Doctor', 'Pediatrics')")->execute([$clinic_id]);
        $specialists[] = $connect->lastInsertId();
    }
    
    $spec_id = $specialists[0];

    // Find some active children and parents
    $childStmt = $connect->query("SELECT child_id, parent_id FROM child LIMIT 5");
    $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($children)) {
        // Create dummy parent and child
        $connect->query("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Test', 'Parent', 'parent_test@test.com', '123', 'parent')");
        $p_id = $connect->lastInsertId();
        $connect->prepare("INSERT INTO child (parent_id, first_name, last_name, date_of_birth) VALUES (?, 'Test', 'Child', '2020-01-01')")->execute([$p_id]);
        $children[] = ['child_id' => $connect->lastInsertId(), 'parent_id' => $p_id];
    }

    // Insert Appointments
    $now = new DateTime();
    $statuses = ['scheduled', 'completed', 'scheduled', 'completed', 'cancelled'];
    $types = ['Consultation', 'Follow-up', 'Therapy Session'];

    $connect->exec("DELETE FROM appointment WHERE specialist_id = $spec_id");

    foreach ($children as $index => $child) {
        // Add appointments around today
        $days_offset = rand(-5, 5);
        $apt_date = (clone $now)->modify("$days_offset days")->format('Y-m-d H:i:s');
        $status = $statuses[$index % count($statuses)];
        $type = $types[$index % count($types)];

        $stmt = $connect->prepare("INSERT INTO appointment (child_id, parent_id, specialist_id, scheduled_at, status, type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$child['child_id'], $child['parent_id'], $spec_id, $apt_date, $status, $type]);
    }
    echo "Inserted appointments.\n";

    // Insert Feedback
    $connect->exec("DELETE FROM feedback WHERE specialist_id = $spec_id");
    
    $reviews = [
        ["Excellent care and very patient with my child.", 5],
        ["The doctor explained everything clearly. Highly recommend.", 5],
        ["Good experience but the waiting time was a bit long.", 4]
    ];
    
    foreach ($reviews as $idx => $rev) {
        if (!isset($children[$idx])) break;
        $child = $children[$idx];
        $stmt = $connect->prepare("INSERT INTO feedback (parent_id, specialist_id, content, rating) VALUES (?, ?, ?, ?)");
        $stmt->execute([$child['parent_id'], $spec_id, $rev[0], $rev[1]]);
    }
    echo "Inserted feedback reviews.\n";

    // Update clinic stats mapping
    $connect->prepare("UPDATE clinic SET rating = 4.8 WHERE clinic_id = ?")->execute([$clinic_id]);

    echo "Successfully seeded data!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
