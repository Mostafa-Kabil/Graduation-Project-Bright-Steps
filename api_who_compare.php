<?php
/**
 * Bright Steps – WHO Growth Comparison Endpoint
 * PHP endpoint that compares child growth data against WHO standards.
 * Works within XAMPP without the Python API.
 */
error_reporting(0);
ini_set('display_errors', 0);

// CORS headers for fetch requests
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load connection if not in same directory
if (file_exists('connection.php')) {
    include 'connection.php';
} elseif (file_exists('../connection.php')) {
    include '../connection.php';
} elseif (file_exists('../../connection.php')) {
    include '../../connection.php';
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// WHO reference data (median and SD values)
// Source: WHO Child Growth Standards – simplified lookup tables
$whoData = [
    'weight_for_age' => [
        'male' => [
            0 => ['median' => 3.3, 'sd' => 0.5],
            3 => ['median' => 6.4, 'sd' => 0.8],
            6 => ['median' => 7.9, 'sd' => 1.0],
            9 => ['median' => 9.0, 'sd' => 1.1],
            12 => ['median' => 9.6, 'sd' => 1.2],
            15 => ['median' => 10.3, 'sd' => 1.3],
            18 => ['median' => 10.9, 'sd' => 1.3],
            24 => ['median' => 12.2, 'sd' => 1.5],
            30 => ['median' => 13.3, 'sd' => 1.6],
            36 => ['median' => 14.3, 'sd' => 1.7],
            48 => ['median' => 16.3, 'sd' => 2.0],
            60 => ['median' => 18.3, 'sd' => 2.4]
        ],
        'female' => [
            0 => ['median' => 3.2, 'sd' => 0.4],
            3 => ['median' => 5.8, 'sd' => 0.7],
            6 => ['median' => 7.3, 'sd' => 0.9],
            9 => ['median' => 8.2, 'sd' => 1.0],
            12 => ['median' => 8.9, 'sd' => 1.1],
            15 => ['median' => 9.6, 'sd' => 1.2],
            18 => ['median' => 10.2, 'sd' => 1.2],
            24 => ['median' => 11.5, 'sd' => 1.4],
            30 => ['median' => 12.7, 'sd' => 1.5],
            36 => ['median' => 13.9, 'sd' => 1.7],
            48 => ['median' => 16.1, 'sd' => 2.1],
            60 => ['median' => 18.2, 'sd' => 2.4]
        ]
    ],
    'height_for_age' => [
        'male' => [
            0 => ['median' => 49.9, 'sd' => 2.0],
            3 => ['median' => 61.4, 'sd' => 2.3],
            6 => ['median' => 67.6, 'sd' => 2.5],
            9 => ['median' => 72.0, 'sd' => 2.6],
            12 => ['median' => 75.7, 'sd' => 2.7],
            15 => ['median' => 79.1, 'sd' => 2.8],
            18 => ['median' => 82.3, 'sd' => 2.9],
            24 => ['median' => 87.8, 'sd' => 3.2],
            30 => ['median' => 92.4, 'sd' => 3.4],
            36 => ['median' => 96.1, 'sd' => 3.6],
            48 => ['median' => 103.3, 'sd' => 4.0],
            60 => ['median' => 110.0, 'sd' => 4.5]
        ],
        'female' => [
            0 => ['median' => 49.1, 'sd' => 1.9],
            3 => ['median' => 59.8, 'sd' => 2.2],
            6 => ['median' => 65.7, 'sd' => 2.4],
            9 => ['median' => 70.1, 'sd' => 2.5],
            12 => ['median' => 74.0, 'sd' => 2.7],
            15 => ['median' => 77.5, 'sd' => 2.8],
            18 => ['median' => 80.7, 'sd' => 2.9],
            24 => ['median' => 86.4, 'sd' => 3.2],
            30 => ['median' => 91.2, 'sd' => 3.4],
            36 => ['median' => 95.1, 'sd' => 3.6],
            48 => ['median' => 102.7, 'sd' => 4.0],
            60 => ['median' => 109.4, 'sd' => 4.5]
        ]
    ],
    'head_for_age' => [
        'male' => [
            0 => ['median' => 34.5, 'sd' => 1.2],
            3 => ['median' => 40.5, 'sd' => 1.2],
            6 => ['median' => 43.3, 'sd' => 1.2],
            9 => ['median' => 45.0, 'sd' => 1.2],
            12 => ['median' => 46.1, 'sd' => 1.2],
            15 => ['median' => 46.8, 'sd' => 1.2],
            18 => ['median' => 47.4, 'sd' => 1.3],
            24 => ['median' => 48.3, 'sd' => 1.3],
            36 => ['median' => 49.6, 'sd' => 1.3],
            48 => ['median' => 50.5, 'sd' => 1.3],
            60 => ['median' => 50.7, 'sd' => 1.4]
        ],
        'female' => [
            0 => ['median' => 33.9, 'sd' => 1.1],
            3 => ['median' => 39.5, 'sd' => 1.2],
            6 => ['median' => 42.0, 'sd' => 1.2],
            9 => ['median' => 43.8, 'sd' => 1.2],
            12 => ['median' => 44.9, 'sd' => 1.2],
            15 => ['median' => 45.6, 'sd' => 1.2],
            18 => ['median' => 46.2, 'sd' => 1.2],
            24 => ['median' => 47.2, 'sd' => 1.3],
            36 => ['median' => 48.5, 'sd' => 1.3],
            60 => ['median' => 49.6, 'sd' => 1.3]
        ]
    ],
    'bmi_for_age' => [
        'male' => [ 0 => ['median' => 13.4, 'sd' => 1.2], 12 => ['median' => 16.5, 'sd' => 1.3], 24 => ['median' => 16.0, 'sd' => 1.2], 60 => ['median' => 15.3, 'sd' => 1.2] ],
        'female' => [ 0 => ['median' => 13.3, 'sd' => 1.1], 12 => ['median' => 16.0, 'sd' => 1.3], 24 => ['median' => 15.7, 'sd' => 1.2], 60 => ['median' => 15.3, 'sd' => 1.2] ]
    ],
    'arm_for_age' => [
        'male' => [ 0 => ['median' => 11.0, 'sd' => 1.0], 12 => ['median' => 15.0, 'sd' => 1.0], 24 => ['median' => 16.0, 'sd' => 1.2], 60 => ['median' => 17.0, 'sd' => 1.2] ],
        'female' => [ 0 => ['median' => 10.5, 'sd' => 1.0], 12 => ['median' => 14.5, 'sd' => 1.0], 24 => ['median' => 15.5, 'sd' => 1.2], 60 => ['median' => 16.5, 'sd' => 1.2] ]
    ],
    'subscapular_for_age' => [
        'male' => [ 0 => ['median' => 5.5, 'sd' => 1.0], 12 => ['median' => 6.5, 'sd' => 1.0], 24 => ['median' => 6.0, 'sd' => 0.8], 60 => ['median' => 5.5, 'sd' => 0.8] ],
        'female' => [ 0 => ['median' => 5.8, 'sd' => 1.0], 12 => ['median' => 7.0, 'sd' => 1.0], 24 => ['median' => 6.5, 'sd' => 0.8], 60 => ['median' => 6.0, 'sd' => 0.8] ]
    ],
    'triceps_for_age' => [
        'male' => [ 0 => ['median' => 7.5, 'sd' => 1.0], 12 => ['median' => 9.0, 'sd' => 1.0], 24 => ['median' => 9.5, 'sd' => 1.0], 60 => ['median' => 9.0, 'sd' => 1.0] ],
        'female' => [ 0 => ['median' => 8.0, 'sd' => 1.0], 12 => ['median' => 10.0, 'sd' => 1.2], 24 => ['median' => 10.5, 'sd' => 1.2], 60 => ['median' => 10.0, 'sd' => 1.2] ]
    ],
    'weight_for_length' => [
        'male' => [ 40 => ['median' => 2.5, 'sd' => 0.4], 50 => ['median' => 3.3, 'sd' => 0.5], 70 => ['median' => 8.5, 'sd' => 0.8], 90 => ['median' => 13.0, 'sd' => 1.1], 110 => ['median' => 18.5, 'sd' => 1.5] ],
        'female' => [ 40 => ['median' => 2.4, 'sd' => 0.4], 50 => ['median' => 3.2, 'sd' => 0.5], 70 => ['median' => 8.0, 'sd' => 0.8], 90 => ['median' => 12.5, 'sd' => 1.0], 110 => ['median' => 18.0, 'sd' => 1.5] ]
    ]
];

function getClosestAge($ageMonths, $data)
{
    $closest = 0;
    $minDiff = PHP_INT_MAX;
    foreach (array_keys($data) as $age) {
        $diff = abs($age - $ageMonths);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closest = $age;
        }
    }
    return $closest;
}

function calcZScore($value, $median, $sd)
{
    if ($sd == 0)
        return 0;
    return round(($value - $median) / $sd, 2);
}

function zToPercentile($z)
{
    // Approximate normal CDF using Abramowitz & Stegun
    $t = 1.0 / (1.0 + 0.2316419 * abs($z));
    $d = 0.3989422802 * exp(-$z * $z / 2);
    $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
    if ($z > 0)
        $p = 1 - $p;
    return round($p * 100, 1);
}

function getStatus($z)
{
    if ($z >= -1 && $z <= 1)
        return ['status' => 'green', 'label' => 'On Track'];
    if ($z >= -2 && $z <= 2)
        return ['status' => 'yellow', 'label' => 'Needs Attention'];
    return ['status' => 'red', 'label' => 'Consult Doctor'];
}

$childId = $_GET['child_id'] ?? null;

if (!$childId) {
    http_response_code(400);
    echo json_encode(['error' => 'child_id is required']);
    exit();
}

// Check database connection
if (!$connect) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$parentId = $_SESSION['id'];

// Fetch child info
$stmt = $connect->prepare("SELECT * FROM child WHERE child_id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    http_response_code(404);
    echo json_encode(['error' => 'Child not found']);
    exit();
}

// Calculate age in months
$bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
$ageMonths = floor((time() - $bd) / (30.44 * 86400));
$gender = strtolower($child['gender']) === 'female' ? 'female' : 'male';

// Get latest growth record
$stmt = $connect->prepare("SELECT * FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 1");
$stmt->execute([$childId]);
$growth = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$growth) {
    echo json_encode([
        'child' => $child['first_name'] . ' ' . $child['last_name'],
        'age_months' => $ageMonths,
        'message' => 'No growth records found. Add measurements to compare with WHO standards.'
    ]);
    exit();
}

$results = [];

// Weight
if ($growth['weight']) {
    $wData = $whoData['weight_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $wData);
    $ref = $wData[$closestAge];
    $z = calcZScore(floatval($growth['weight']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['weight'] = [
        'value' => $growth['weight'] . ' kg',
        'z_score' => $z,
        'percentile' => $pct,
        'status' => $st['status'],
        'label' => $st['label'],
        'who_median' => $ref['median'] . ' kg'
    ];
}

// Height
if ($growth['height']) {
    $hData = $whoData['height_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $hData);
    $ref = $hData[$closestAge];
    $z = calcZScore(floatval($growth['height']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['height'] = [
        'value' => $growth['height'] . ' cm',
        'z_score' => $z,
        'percentile' => $pct,
        'status' => $st['status'],
        'label' => $st['label'],
        'who_median' => $ref['median'] . ' cm'
    ];
}

// Head circumference
if ($growth['head_circumference']) {
    $hcData = $whoData['head_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $hcData);
    $ref = $hcData[$closestAge];
    $z = calcZScore(floatval($growth['head_circumference']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['head_circumference'] = [
        'value' => $growth['head_circumference'] . ' cm',
        'z_score' => $z,
        'percentile' => $pct,
        'status' => $st['status'],
        'label' => $st['label'],
        'who_median' => $ref['median'] . ' cm'
    ];
}

// BMI (Calculated automatically if height and weight exist)
if ($growth['weight'] && $growth['height']) {
    $heightM = floatval($growth['height']) / 100;
    if ($heightM > 0) {
        $bmi = round(floatval($growth['weight']) / ($heightM * $heightM), 2);
        $bData = $whoData['bmi_for_age'][$gender];
        $closestAge = getClosestAge($ageMonths, $bData);
        $ref = $bData[$closestAge];
        $z = calcZScore($bmi, $ref['median'], $ref['sd']);
        $pct = zToPercentile($z);
        $st = getStatus($z);
        $results['bmi'] = [
            'value' => $bmi . ' kg/m²',
            'z_score' => $z,
            'percentile' => $pct,
            'status' => $st['status'],
            'label' => $st['label'],
            'who_median' => $ref['median'] . ' kg/m²'
        ];
        
        $wlData = $whoData['weight_for_length'][$gender];
        $closestLen = getClosestAge(floatval($growth['height']), $wlData);
        $refWL = $wlData[$closestLen];
        $zw = calcZScore(floatval($growth['weight']), $refWL['median'], $refWL['sd']);
        $pctw = zToPercentile($zw);
        $stw = getStatus($zw);
        $results['weight_for_length'] = [
            'value' => $growth['weight'] . ' kg',
            'z_score' => $zw,
            'percentile' => $pctw,
            'status' => $stw['status'],
            'label' => $stw['label'],
            'who_median' => $refWL['median'] . ' kg'
        ];
    }
}

// Arm Circumference
if ($growth['arm_circumference']) {
    $aData = $whoData['arm_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $aData);
    $ref = $aData[$closestAge];
    $z = calcZScore(floatval($growth['arm_circumference']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['arm_circumference'] = [
        'value' => $growth['arm_circumference'] . ' cm',
        'z_score' => $z, 'percentile' => $pct, 'status' => $st['status'], 'label' => $st['label'],
        'who_median' => $ref['median'] . ' cm'
    ];
}

// Subscapular Skinfold
if ($growth['subscapular_skinfold']) {
    $aData = $whoData['subscapular_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $aData);
    $ref = $aData[$closestAge];
    $z = calcZScore(floatval($growth['subscapular_skinfold']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['subscapular_skinfold'] = [
        'value' => $growth['subscapular_skinfold'] . ' mm',
        'z_score' => $z, 'percentile' => $pct, 'status' => $st['status'], 'label' => $st['label'],
        'who_median' => $ref['median'] . ' mm'
    ];
}

// Triceps Skinfold
if ($growth['triceps_skinfold']) {
    $aData = $whoData['triceps_for_age'][$gender];
    $closestAge = getClosestAge($ageMonths, $aData);
    $ref = $aData[$closestAge];
    $z = calcZScore(floatval($growth['triceps_skinfold']), $ref['median'], $ref['sd']);
    $pct = zToPercentile($z);
    $st = getStatus($z);
    $results['triceps_skinfold'] = [
        'value' => $growth['triceps_skinfold'] . ' mm',
        'z_score' => $z, 'percentile' => $pct, 'status' => $st['status'], 'label' => $st['label'],
        'who_median' => $ref['median'] . ' mm'
    ];
}



// Fetch historical records
$stmtHistory = $connect->prepare("SELECT * FROM growth_record WHERE child_id = ? ORDER BY recorded_at ASC");
$stmtHistory->execute([$childId]);
$historical = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'child' => $child['first_name'] . ' ' . $child['last_name'],
    'age_months' => $ageMonths,
    'gender' => $gender,
    'measurements' => $results,
    'recorded_at' => $growth['recorded_at'],
    'historical_records' => $historical,
    'who_curve_points' => $whoData
]);
