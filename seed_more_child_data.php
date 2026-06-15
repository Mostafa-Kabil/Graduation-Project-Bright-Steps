<?php
include 'connection.php';

echo "Adding more realistic data for Moaz children...\n";

$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'moaz@gmail.com'");
$stmt->execute();
$moaz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$moaz) { echo "Moaz not found\n"; exit; }
$moaz_id = $moaz['user_id'];

$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = 'salsabel@gmail.com'");
$stmt->execute();
$salsabel = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$salsabel) { echo "Salsabel not found\n"; exit; }
$salsabel_id = $salsabel['user_id'];

// Get children
$stmt = $connect->prepare("SELECT child_id FROM child WHERE parent_id = ?");
$stmt->execute([$moaz_id]);
$children = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($children) == 0) { echo "No children found for Moaz\n"; exit; }

$omar_id = $children[0];
$laila_id = $children[1] ?? $omar_id;
$yassin_id = $children[2] ?? $omar_id;

echo "Found children: Omar ($omar_id), Laila ($laila_id), Yassin ($yassin_id)\n";

// 1. Motor Milestones
$categories = ['gross_motor', 'fine_motor', 'sensory'];
$milestones = [
    'gross_motor' => ['Walking independently', 'Running', 'Jumping with both feet', 'Climbing stairs'],
    'fine_motor' => ['Holding a pencil', 'Drawing a circle', 'Using scissors', 'Building a block tower'],
    'sensory' => ['Reacting to sounds', 'Tracking moving objects', 'Responding to name', 'Enjoying swinging']
];

foreach ([$omar_id, $laila_id, $yassin_id] as $cid) {
    foreach ($categories as $cat) {
        foreach ($milestones[$cat] as $idx => $m_name) {
            $is_achieved = rand(0, 100) > 30 ? 1 : 0;
            $connect->query("INSERT IGNORE INTO motor_milestones (child_id, category, milestone_name, is_achieved) 
            VALUES ($cid, '$cat', '$m_name', $is_achieved)");
        }
    }
}
echo "Added motor milestones\n";

// 2. Behavior Checklist
$categories = [
    ['name' => 'Attention span', 'type' => 'attention'],
    ['name' => 'Social interaction', 'type' => 'social'],
    ['name' => 'Communication skills', 'type' => 'communication']
];
foreach ($categories as $cat) {
    $connect->query("INSERT IGNORE INTO behavior_category (category_name, category_type) VALUES ('{$cat['name']}', '{$cat['type']}')");
}

$cat_ids = $connect->query("SELECT category_id FROM behavior_category LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
if (count($cat_ids) >= 3) {
    $behaviors = [
        [$cat_ids[0], 'Maintains eye contact during conversation'],
        [$cat_ids[0], 'Can focus on a task for 10 minutes'],
        [$cat_ids[1], 'Plays well with other children'],
        [$cat_ids[1], 'Shares toys voluntarily'],
        [$cat_ids[2], 'Can express basic needs clearly'],
        [$cat_ids[2], 'Follows two-step instructions']
    ];
    
    foreach ($behaviors as $b) {
        $connect->query("INSERT IGNORE INTO behavior (category_id, behavior_details) VALUES ({$b[0]}, '{$b[1]}')");
    }
    
    $behavior_ids = $connect->query("SELECT behavior_id FROM behavior")->fetchAll(PDO::FETCH_COLUMN);
    foreach ([$omar_id, $laila_id, $yassin_id] as $cid) {
        foreach ($behavior_ids as $bid) {
            $freq = ['Never', 'Rarely', 'Sometimes', 'Often'][rand(0, 3)];
            $sev = ['Low', 'Medium', 'High'][rand(0, 2)];
            $connect->query("INSERT IGNORE INTO child_exhibited_behavior (child_id, behavior_id, frequency, severity) 
            VALUES ($cid, $bid, '$freq', '$sev')");
        }
    }
    echo "Added behavior checklist\n";
}

// 3. Speech Analysis
$samples = $connect->query("SELECT sample_id, child_id FROM voice_sample WHERE child_id IN ($omar_id, $laila_id, $yassin_id)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples as $s) {
    $vocab = rand(10, 50);
    $clarity = rand(50, 95) / 100;
    $transcripts = [
        "I want an apple", "Look at the big dog", "Can we go to the park", 
        "Where is my red car", "I like playing with blocks", "Mommy look at this"
    ];
    $trans = $transcripts[array_rand($transcripts)];
    $connect->query("INSERT IGNORE INTO speech_analysis (sample_id, transcript, vocabulary_score, clarify_score) 
    VALUES ({$s['sample_id']}, '$trans', $vocab, $clarity)");
}
echo "Added speech analysis\n";

// 4. Shared Reports
$types = ['full-report', 'growth-report', 'speech-report'];
foreach ([$omar_id, $laila_id] as $cid) {
    foreach ($types as $type) {
        $connect->query("INSERT INTO shared_reports (child_id, doctor_id, is_shared, created_at, report_type) 
        VALUES ($cid, $salsabel_id, 1, DATE_SUB(NOW(), INTERVAL ".rand(1, 10)." DAY), '$type')");
    }
}
echo "Added shared reports\n";

// 5. Messages / Chat
$salsabel_spec_id = $salsabel_id;

// Salsabel chatting with Moaz
$connect->query("INSERT INTO message (sender_id, receiver_id, content, is_read, sent_at) VALUES 
($salsabel_id, $moaz_id, 'Hello Moaz, Omar is doing great! Make sure he practices his speech exercises.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
($moaz_id, $salsabel_id, 'Thank you Dr. Salsabel. We are doing the exercises every night.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
($salsabel_id, $moaz_id, 'Excellent. See you at the next appointment.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
($salsabel_id, $moaz_id, 'Also, I sent you a detailed report for Laila, please review it.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY))");
echo "Added chat messages\n";

echo "Done.\n";
