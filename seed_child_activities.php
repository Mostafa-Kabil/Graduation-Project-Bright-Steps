<?php
include 'connection.php';

$childIds = [];
$c = $connect->query("SELECT child_id FROM child");
while ($r = $c->fetch(PDO::FETCH_ASSOC)) {
    $childIds[] = $r['child_id'];
}

if (empty($childIds)) die("No children found.");

$titles = ["Motor Skills Training", "Speech Practice", "Color Matching", "Block Stacking", "Shape Sorting", "Vocabulary Builder"];
$descs = ["Practice fine motor skills", "Repeat daily words", "Match primary colors", "Stack blocks 5 high", "Sort blocks by shape", "Learn 3 new words today"];

$added = 0;
for ($i = 0; $i < 30; $i++) {
    $cid = $childIds[array_rand($childIds)];
    $idx = array_rand($titles);
    $title = $titles[$idx];
    $desc = $descs[$idx];
    $comp = (rand(1, 100) > 60) ? 1 : 0; // 40% completed

    $connect->prepare("INSERT INTO child_activities (child_id, title, description, created_at, is_completed) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), ?)")->execute([
        $cid, $title, $desc, rand(0, 30), $comp
    ]);
    $added++;
}

echo "Added $added child_activities.";
