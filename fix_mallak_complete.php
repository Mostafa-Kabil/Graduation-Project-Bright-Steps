<?php
include 'connection.php';

echo "=== Fixing Mallak Care Clinic Data ===\n\n";

$clinic_id = 16;

// ────────────────────────────────────────
// 1. Fix is_first_login to skip onboarding
// ────────────────────────────────────────
$connect->query("UPDATE clinic SET is_first_login = 0, bio = 'Mallak Care is a leading pediatric development clinic in Cairo, specializing in early childhood intervention, speech therapy, occupational therapy, and behavioral support. Our multidisciplinary team of certified specialists is dedicated to helping every child reach their full potential through evidence-based care.', medical_specialties = 'Speech Therapy,Occupational Therapy,Behavioral Therapy,ABA Therapy,Developmental Screening', rating = 4.7, phone = '01263939324', website = 'https://mallakcare.com' WHERE clinic_id = $clinic_id");
echo "1. Fixed is_first_login = 0 and enriched clinic profile\n";

// ────────────────────────────────────────
// 2. Enrich Specialist Data 
// ────────────────────────────────────────
// Add specialization details, experience, certifications for existing specialists
$connect->query("UPDATE specialist SET 
    specialization = 'Speech-Language Pathology',
    experience_years = 7,
    certification_text = 'Board Certified Speech-Language Pathologist (CCC-SLP), Licensed Clinical Specialist in Pediatric Language Disorders, Certified PROMPT Therapist'
    WHERE specialist_id = 15 AND clinic_id = $clinic_id");

$connect->query("UPDATE specialist SET 
    specialization = 'Occupational Therapy',
    experience_years = 9,
    certification_text = 'Certified Occupational Therapy Assistant (COTA), Sensory Integration Specialist (SIS), Certified Pediatric OT Practitioner'
    WHERE specialist_id = 245 AND clinic_id = $clinic_id");

$connect->query("UPDATE specialist SET 
    specialization = 'Applied Behavior Analysis (ABA)',
    experience_years = 6,
    certification_text = 'Board Certified Behavior Analyst (BCBA), Registered Behavior Technician (RBT) Supervisor, Certified Autism Specialist (CAS)'
    WHERE specialist_id = 246 AND clinic_id = $clinic_id");

// Add 2 more specialists
$extra_specs = [
    ['email' => 'tarek@mallak.com', 'fname' => 'Tarek', 'lname' => 'Nabil', 'spec' => 'Developmental Pediatrics', 'exp' => 12, 'cert' => 'Board Certified Developmental Pediatrician, Fellow of the Egyptian Pediatric Society, Certified in Neurodevelopmental Assessment'],
    ['email' => 'nour@mallak.com', 'fname' => 'Nour', 'lname' => 'El-Sayed', 'spec' => 'Child Psychology', 'exp' => 8, 'cert' => 'Licensed Clinical Psychologist (LCP), Certified Child & Adolescent Therapist, Certified Play Therapist (CPT)']
];

$all_spec_ids = [15, 245, 246];

foreach ($extra_specs as $s) {
    $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$s['email']]);
    $uid = $stmt->fetchColumn();

    if (!$uid) {
        $pwd = password_hash('pass123', PASSWORD_DEFAULT);
        $connect->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, 'specialist', 'active')")->execute([$s['fname'], $s['lname'], $s['email'], $pwd]);
        $uid = $connect->lastInsertId();
    }
    
    $connect->prepare("INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years, certification_text) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE clinic_id = ?, specialization = ?, experience_years = ?, certification_text = ?")
        ->execute([$uid, $clinic_id, $s['fname'], $s['lname'], $s['spec'], $s['exp'], $s['cert'], $clinic_id, $s['spec'], $s['exp'], $s['cert']]);
    $all_spec_ids[] = $uid;
}
echo "2. Enriched 5 specialists with certifications and experience\n";

// ────────────────────────────────────────
// 3. Fix Revenue: Update existing payment records with actual amounts
// ────────────────────────────────────────
// Get all appointments for this clinic's specialists that have NULL payment amounts
$fix_stmt = $connect->query("
    SELECT a.appointment_id, a.payment_id 
    FROM appointment a 
    JOIN specialist s ON a.specialist_id = s.specialist_id 
    WHERE s.clinic_id = $clinic_id AND a.payment_id IS NOT NULL
");
$appointments_to_fix = $fix_stmt->fetchAll(PDO::FETCH_ASSOC);

$methods = ['cash', 'credit_card', 'credit_card', 'cash', 'credit_card'];
$amounts = [350, 450, 500, 600, 750, 400, 550, 650, 800, 300];
$fixed = 0;

foreach ($appointments_to_fix as $apt) {
    $amount = $amounts[array_rand($amounts)];
    $method = $methods[array_rand($methods)];
    $connect->prepare("UPDATE payment SET amount_pre_discount = ?, amount_post_discount = ?, method = ?, status = 'paid' WHERE payment_id = ? AND (amount_post_discount IS NULL OR amount_post_discount = 0)")
        ->execute([$amount, $amount, $method, $apt['payment_id']]);
    $fixed++;
}
echo "3. Fixed $fixed payment records with amounts (EGP 300-800)\n";

// ────────────────────────────────────────
// 4. Add NEW appointments with proper payment data for recent dates
// ────────────────────────────────────────
// Get all children that have appointments with this clinic
$children_stmt = $connect->query("SELECT DISTINCT c.child_id, c.parent_id FROM child c JOIN appointment a ON a.child_id = c.child_id JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = $clinic_id LIMIT 10");
$children = $children_stmt->fetchAll(PDO::FETCH_ASSOC);

$statuses = ['completed', 'completed', 'completed', 'scheduled', 'completed'];
$types = ['onsite', 'online', 'onsite', 'onsite'];
$new_apts = 0;

foreach ($children as $child) {
    // Add 2 new recent appointments per child with PROPER payment data
    for ($i = 0; $i < 2; $i++) {
        $days_ago = rand(1, 25);
        $amount = $amounts[array_rand($amounts)];
        $method = $methods[array_rand($methods)];
        $status = $statuses[array_rand($statuses)];
        $type = $types[array_rand($types)];
        $spec_id = $all_spec_ids[array_rand($all_spec_ids)];
        
        // Create payment first
        $connect->prepare("INSERT INTO payment (parent_id, amount_pre_discount, amount_post_discount, method, status, paid_at) VALUES (?, ?, ?, ?, 'paid', DATE_SUB(NOW(), INTERVAL ? DAY))")
            ->execute([$child['parent_id'], $amount, $amount, $method, $days_ago]);
        $payment_id = $connect->lastInsertId();
        
        // Create appointment
        $hour = rand(9, 16);
        $minute = [0, 15, 30, 45][rand(0, 3)];
        $connect->prepare("INSERT INTO appointment (parent_id, specialist_id, child_id, payment_id, scheduled_at, status, type) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY) + INTERVAL ? HOUR + INTERVAL ? MINUTE, ?, ?)")
            ->execute([$child['parent_id'], $spec_id, $child['child_id'], $payment_id, $days_ago, $hour, $minute, $status, $type]);
        $new_apts++;
    }
}
echo "4. Added $new_apts new appointments with proper payment data\n";

// ────────────────────────────────────────
// 5. Add specialist reviews for rating data
// ────────────────────────────────────────
$review_comments = [
    'Dr. Salsabel has been incredible with our child. Highly recommended!',
    'Very professional and caring specialist. Great results in just a few sessions.',
    'Amazing improvement in our daughter speech after therapy. Thank you!',
    'The clinic atmosphere is wonderful and the specialists are top-notch.',
    'We are very satisfied with the progress our son has made.',
    'Exceptional care and attention to detail. Our child loves the sessions.',
    'Professional, patient, and incredibly knowledgeable. Best clinic in Cairo.',
    'The developmental screening was thorough and the recommendations were spot-on.',
    'Our child has shown remarkable progress since starting at Mallak Care.',
    'Outstanding therapy sessions. The specialists really understand children.'
];

// Get some parent IDs
$parents = $connect->query("SELECT DISTINCT parent_id FROM appointment a JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = $clinic_id LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);

$review_count = 0;
foreach ($all_spec_ids as $spec_id) {
    foreach (array_slice($parents, 0, 3) as $parent_id) {
        $rating = [4, 4, 5, 5, 5][rand(0, 4)];
        $comment = $review_comments[array_rand($review_comments)];
        $days = rand(1, 60);
        
        try {
            $connect->prepare("INSERT INTO specialist_reviews (specialist_id, parent_id, rating, comment, created_at) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))")
                ->execute([$spec_id, $parent_id, $rating, $comment, $days]);
            $review_count++;
        } catch (Exception $e) {
            // Duplicate or constraint - skip
        }
    }
}
echo "5. Added $review_count specialist reviews\n";

// ────────────────────────────────────────
// 6. Verify final state
// ────────────────────────────────────────
$spec_count = $connect->query("SELECT COUNT(*) FROM specialist WHERE clinic_id = $clinic_id")->fetchColumn();
$apt_count = $connect->query("SELECT COUNT(*) FROM appointment a JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = $clinic_id")->fetchColumn();
$rev_total = $connect->query("SELECT SUM(p.amount_post_discount) FROM appointment a JOIN specialist s ON a.specialist_id = s.specialist_id JOIN payment p ON a.payment_id = p.payment_id WHERE s.clinic_id = $clinic_id AND p.amount_post_discount > 0")->fetchColumn();
$patient_count = $connect->query("SELECT COUNT(DISTINCT a.child_id) FROM appointment a JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = $clinic_id")->fetchColumn();

echo "\n=== Final State ===\n";
echo "Clinic ID: $clinic_id (Mallak Care)\n";
echo "Specialists: $spec_count\n";
echo "Total Appointments: $apt_count\n";
echo "Unique Patients: $patient_count\n";
echo "Total Revenue: EGP " . number_format($rev_total ?? 0) . "\n";
echo "\n✅ All data seeded successfully!\n";
