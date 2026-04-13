<?php
// api_guided_words.php
// Returns age-appropriate words for guided speech practice
header('Content-Type: application/json');
require_once 'includes/auth_check.php';

$childId = isset($_GET['child_id']) ? (int) $_GET['child_id'] : 0;
$ageMonths = isset($_GET['age_months']) ? (int) $_GET['age_months'] : 24;

if (!$childId) {
    echo json_encode(['success' => false, 'error' => 'child_id required']);
    exit();
}

// Verify child belongs to parent
$stmt = $connect->prepare("SELECT child_id, first_name, birth_day, birth_month, birth_year FROM child WHERE child_id = :cid AND parent_id = :pid");
$stmt->execute(['cid' => $childId, 'pid' => $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    echo json_encode(['success' => false, 'error' => 'Child not found']);
    exit();
}

// Calculate age in months if not provided
if (!$ageMonths || $ageMonths <= 0) {
    $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
    $ageMonths = (int) floor((time() - $bd) / (30.44 * 86400));
}
$ageMonths = max(12, min(72, $ageMonths));

// Check if Python server is running, if not start it
function isPythonServerRunning($port = 8000) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($fp) { fclose($fp); return true; }
    return false;
}

if (!isPythonServerRunning(8000)) {
    $scriptDir = realpath(__DIR__ . '/APIs/Speech Analysis');
    if ($scriptDir) {
        pclose(popen('cd "' . $scriptDir . '" && start /B python -m uvicorn app:app --port 8000 > NUL 2> NUL', 'r'));
        $maxWait = 20;
        while (!isPythonServerRunning(8000) && $maxWait > 0) {
            usleep(500000);
            $maxWait--;
        }
    }
}

// Call Python API to generate age-appropriate words
$ch = curl_init('http://127.0.0.1:8000/generate-words');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['age' => $ageMonths],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'words' => $result['words'] ?? [],
        'age_label' => $result['age_label'] ?? '',
        'age_months' => $ageMonths,
        'child_name' => $child['first_name']
    ]);
} else {
    // Fallback to built-in word lists if Python API fails
    $wordLists = [
        12 => ['label' => '12-17 months', 'words' => ['mama', 'dada', 'ball', 'cup', 'no', 'bye', 'up', 'hi', 'dog', 'cat']],
        18 => ['label' => '18-23 months', 'words' => ['water', 'shoe', 'bird', 'book', 'car', 'baby', 'hot', 'go', 'sit', 'more']],
        24 => ['label' => '24-35 months', 'words' => ['apple', 'tree', 'run', 'jump', 'play', 'happy', 'blue', 'red', 'big', 'eat']],
        36 => ['label' => '36-47 months', 'words' => ['orange', 'school', 'friend', 'animal', 'family', 'outside', 'music', 'color', 'dance', 'grow']],
        48 => ['label' => '48-59 months', 'words' => ['elephant', 'butterfly', 'teacher', 'together', 'beautiful', 'favorite', 'remember', 'always', 'village', 'garden']],
        60 => ['label' => '60-72 months', 'words' => ['strawberry', 'hospital', 'experiment', 'neighborhood', 'imagination', 'celebrate', 'accomplish', 'wonderful', 'adventure', 'discovery']],
    ];

    $nearestAge = 24;
    foreach (array_keys($wordLists) as $age) {
        if ($age <= $ageMonths) {
            $nearestAge = $age;
        } else {
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'words' => $wordLists[$nearestAge]['words'],
        'age_label' => $wordLists[$nearestAge]['label'],
        'age_months' => $ageMonths,
        'child_name' => $child['first_name'],
        'source' => 'fallback'
    ]);
}
