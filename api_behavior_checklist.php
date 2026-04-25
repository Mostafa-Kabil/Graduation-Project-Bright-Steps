<?php
/**
 * Bright Steps – AI-Powered Behavior Checklist API
 * Calls Python AI API to generate behaviors based on child metrics
 * Handles database operations for behavior, behavior_category, and child_exhibited_behavior tables
 */
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load database connection
if (file_exists('connection.php')) {
    include 'connection.php';
} elseif (file_exists('../connection.php')) {
    include '../connection.php';
} elseif (file_exists('../../connection.php')) {
    include '../../connection.php';
}

// Check authentication
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Parent access required']);
    exit();
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$childId = $_GET['child_id'] ?? $_POST['child_id'] ?? null;

// AI API URL
$AI_API_URL = 'http://localhost:8003';

// Verify child belongs to parent
function verifyChild($connect, $childId, $parentId) {
    if (!$childId || !$parentId) return false;
    $stmt = $connect->prepare("SELECT child_id, first_name, last_name, birth_year, birth_month, birth_day FROM child WHERE child_id = ? AND parent_id = ?");
    $stmt->execute([$childId, $parentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate age in months
function calculateAgeMonths($birthYear, $birthMonth, $birthDay) {
    $bd = mktime(0, 0, 0, $birthMonth, $birthDay, $birthYear);
    return floor((time() - $bd) / (30.44 * 86400));
}

// Get latest growth metrics for child
function getGrowthMetrics($connect, $childId) {
    $stmt = $connect->prepare("SELECT weight, height, head_circumference FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->execute([$childId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

// Get latest speech analysis for child
function getSpeechMetrics($connect, $childId) {
    $stmt = $connect->prepare("
        SELECT sa.vocabulary_score, sa.clarify_score
        FROM speech_analysis sa
        INNER JOIN voice_sample vs ON sa.sample_id = vs.sample_id
        WHERE vs.child_id = ?
        ORDER BY vs.sent_at DESC LIMIT 1
    ");
    $stmt->execute([$childId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

// Ensure behavior category exists in database
function ensureCategoryExists($connect, $categoryName, $categoryType, $categoryDescription) {
    $stmt = $connect->prepare("SELECT category_id FROM behavior_category WHERE category_name = ?");
    $stmt->execute([$categoryName]);
    $categoryId = $stmt->fetchColumn();

    if (!$categoryId) {
        $stmt = $connect->prepare("INSERT INTO behavior_category (category_name, category_type, category_description) VALUES (?, ?, ?)");
        $stmt->execute([$categoryName, $categoryType, $categoryDescription]);
        $categoryId = $connect->lastInsertId();
    }
    return $categoryId;
}

// Ensure behavior exists in database
function ensureBehaviorExists($connect, $categoryId, $behaviorDetails, $behaviorType = 'milestone', $indicator = 'AI-generated') {
    $stmt = $connect->prepare("SELECT behavior_id FROM behavior WHERE behavior_details = ? AND category_id = ?");
    $stmt->execute([$behaviorDetails, $categoryId]);
    $behaviorId = $stmt->fetchColumn();

    if (!$behaviorId) {
        $stmt = $connect->prepare("INSERT INTO behavior (category_id, behavior_type, behavior_details, indicator) VALUES (?, ?, ?, ?)");
        $stmt->execute([$categoryId, $behaviorType, $behaviorDetails, $indicator]);
        $behaviorId = $connect->lastInsertId();
    }
    return $behaviorId;
}

// Call Python AI API to generate behaviors
function callAIAPI($childId, $ageMonths, $weight, $height, $headCirc, $speechVocab, $condition = null) {
    global $AI_API_URL;

    $payload = [
        'child_id' => $childId,
        'age_months' => $ageMonths,
        'weight' => $weight,
        'height' => $height,
        'head_circumference' => $headCirc,
        'speech_vocabulary' => $speechVocab,
        'condition' => $condition
    ];

    $ch = curl_init($AI_API_URL . '/generate-behavior-checklist');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Fail fast if service is down

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => 'AI API connection error: ' . $error,
            'ai_unavailable' => true
        ];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'AI API error (HTTP ' . $httpCode . ')',
            'ai_unavailable' => true
        ];
    }

    $decoded = json_decode($response, true);
    if (!$decoded) {
        return ['success' => false, 'error' => 'Invalid AI API response format'];
    }

    return $decoded;
}

// Check if child already has exhibited behaviors saved
function hasExistingChecklist($connect, $childId) {
    $stmt = $connect->prepare("SELECT COUNT(*) as count FROM child_exhibited_behavior WHERE child_id = ?");
    $stmt->execute([$childId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result['count'] ?? 0) > 0;
}

// Get existing checklist for a child (to avoid regenerating)
function getExistingChecklist($connect, $childId) {
    $stmt = $connect->prepare("
        SELECT b.*, bc.category_name, bc.category_type, bc.category_description
        FROM behavior b
        INNER JOIN behavior_category bc ON b.category_id = bc.category_id
        INNER JOIN child_exhibited_behavior ceb ON b.behavior_id = ceb.behavior_id
        WHERE ceb.child_id = ?
        ORDER BY bc.category_type, bc.category_name, b.behavior_details
    ");
    $stmt->execute([$childId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

switch ($action) {
    case 'list':
        if (!$childId) {
            http_response_code(400);
            echo json_encode(['error' => 'child_id is required']);
            exit();
        }

        $child = verifyChild($connect, $childId, $parentId);
        if (!$child) {
            http_response_code(404);
            echo json_encode(['error' => 'Child not found or access denied']);
            exit();
        }

        try {
            $ageMonths = calculateAgeMonths($child['birth_year'], $child['birth_month'], $child['birth_day']);

            // Get child's growth metrics
            $growthMetrics = getGrowthMetrics($connect, $childId);

            // Get child's speech metrics
            $speechMetrics = getSpeechMetrics($connect, $childId);

            // Check if child already has behaviors saved (existing checklist)
            $hasExisting = hasExistingChecklist($connect, $childId);

            // Call AI API to generate personalized behaviors
            $aiResponse = callAIAPI(
                $childId,
                $ageMonths,
                $growthMetrics['weight'] ?? null,
                $growthMetrics['height'] ?? null,
                $growthMetrics['head_circumference'] ?? null,
                $speechMetrics['vocabulary_score'] ?? null,
                null // condition - could be determined based on metrics
            );

            // Check if AI API call was successful
            $useAI = $aiResponse['success'] ?? false;
            $aiUnavailable = $aiResponse['ai_unavailable'] ?? false;

            // Get categories and behaviors from database (AI-generated or existing)
            $stmt = $connect->prepare("SELECT * FROM behavior_category ORDER BY category_type, category_name");
            $stmt->execute();
            $dbCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $connect->prepare("
                SELECT b.*, bc.category_name, bc.category_type, bc.category_description
                FROM behavior b
                INNER JOIN behavior_category bc ON b.category_id = bc.category_id
                ORDER BY bc.category_type, bc.category_name, b.behavior_details
            ");
            $stmt->execute();
            $dbBehaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If AI generated new behaviors, insert them into database only if this is a new checklist
            if ($useAI && !empty($aiResponse['categories']) && !$hasExisting) {
                foreach ($aiResponse['categories'] as $aiCat) {
                    $categoryId = ensureCategoryExists(
                        $connect,
                        $aiCat['category_name'],
                        $aiCat['category_type'],
                        $aiCat['category_description']
                    );

                    foreach ($aiCat['behaviors'] as $aiBeh) {
                        $behaviorId = ensureBehaviorExists(
                            $connect,
                            $categoryId,
                            $aiBeh['behavior_details'],
                            'milestone',
                            $aiBeh['typical_age'] ?? 'AI-generated'
                        );
                    }
                }

                // Re-fetch behaviors after AI insertion
                $stmt = $connect->prepare("
                    SELECT b.*, bc.category_name, bc.category_type, bc.category_description
                    FROM behavior b
                    INNER JOIN behavior_category bc ON b.category_id = bc.category_id
                    ORDER BY bc.category_type, bc.category_name, b.behavior_details
                ");
                $stmt->execute();
                $dbBehaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Get child's exhibited behaviors
            $stmt = $connect->prepare("
                SELECT ceb.behavior_id, ceb.frequency, ceb.severity, ceb.recorded_at
                FROM child_exhibited_behavior ceb
                WHERE ceb.child_id = ?
            ");
            $stmt->execute([$childId]);
            $childBehaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $childBehaviorMap = [];
            foreach ($childBehaviors as $cb) {
                $childBehaviorMap[$cb['behavior_id']] = [
                    'frequency' => $cb['frequency'],
                    'severity' => $cb['severity'],
                    'recorded_at' => $cb['recorded_at']
                ];
            }

            // Map frequency int to text
            $freqMap = [1 => 'rarely', 2 => 'sometimes', 3 => 'often', 4 => 'always'];

            // Organize behaviors by category
            $categories = [];
            $categoryMap = [];

            foreach ($dbCategories as $cat) {
                $categoryMap[$cat['category_id']] = [
                    'category_id' => $cat['category_id'],
                    'category_name' => $cat['category_name'],
                    'category_type' => $cat['category_type'],
                    'category_description' => $cat['category_description'],
                    'behaviors' => []
                ];
            }

            foreach ($dbBehaviors as $b) {
                if (isset($categoryMap[$b['category_id']])) {
                    $childData = $childBehaviorMap[$b['behavior_id']] ?? null;
                    $categoryMap[$b['category_id']]['behaviors'][] = [
                        'behavior_id' => $b['behavior_id'],
                        'behavior_details' => $b['behavior_details'],
                        'behavior_type' => $b['behavior_type'],
                        'indicator' => $b['indicator'],
                        'is_exhibited' => $childData !== null,
                        'frequency' => $childData ? $freqMap[$childData['frequency']] ?? 'sometimes' : null,
                        'severity' => $childData ? $childData['severity'] : null,
                        'recorded_at' => $childData ? $childData['recorded_at'] : null
                    ];
                }
            }

            $categories = array_values($categoryMap);

            // Generate AI feedback
            $feedback = generateAIFeedback($ageMonths, $growthMetrics, $speechMetrics, $categories, $childBehaviorMap);

            $response = [
                'success' => true,
                'child_id' => $childId,
                'child_name' => $child['first_name'] . ' ' . $child['last_name'],
                'age_months' => $ageMonths,
                'categories' => $categories,
                'frequency_map' => $freqMap,
                'feedback' => $feedback,
                'has_existing_checklist' => $hasExisting
            ];

            // Include AI generation info if available
            if ($useAI) {
                $response['ai_generated'] = $aiResponse;
            }

            // Warn if AI service was unavailable but we still have behaviors to show
            if ($aiUnavailable) {
                $response['ai_unavailable'] = true;
                $response['warning'] = 'AI service unavailable. Showing existing behavior checklist.';
            }

            echo json_encode($response);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'save':
        if (!$childId) {
            http_response_code(400);
            echo json_encode(['error' => 'child_id is required']);
            exit();
        }

        $child = verifyChild($connect, $childId, $parentId);
        if (!$child) {
            http_response_code(404);
            echo json_encode(['error' => 'Child not found or access denied']);
            exit();
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $behaviors = $input['behaviors'] ?? [];

        if (empty($behaviors)) {
            http_response_code(400);
            echo json_encode(['error' => 'No behaviors provided']);
            exit();
        }

        try {
            $connect->beginTransaction();

            $freqMap = ['rarely' => 1, 'sometimes' => 2, 'often' => 3, 'always' => 4];
            $savedCount = 0;

            foreach ($behaviors as $b) {
                $behaviorId = $b['behavior_id'] ?? null;
                $frequency = $b['frequency'] ?? 'sometimes';
                $severity = $b['severity'] ?? 'mild';
                $isExhibited = $b['is_exhibited'] ?? false;

                if (!$behaviorId) continue;

                if ($isExhibited) {
                    $freqInt = $freqMap[$frequency] ?? 2;

                    // Insert or update in child_exhibited_behavior
                    $stmt = $connect->prepare("
                        INSERT INTO child_exhibited_behavior (child_id, behavior_id, frequency, severity)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            frequency = VALUES(frequency),
                            severity = VALUES(severity),
                            recorded_at = CURRENT_TIMESTAMP
                    ");
                    $stmt->execute([$childId, $behaviorId, $freqInt, $severity]);
                    $savedCount++;
                } else {
                    // Remove if previously saved
                    $stmt = $connect->prepare("DELETE FROM child_exhibited_behavior WHERE child_id = ? AND behavior_id = ?");
                    $stmt->execute([$childId, $behaviorId]);
                }
            }

            $connect->commit();
            echo json_encode([
                'success' => true,
                'saved_count' => $savedCount,
                'message' => 'Behavior checklist saved successfully!'
            ]);

        } catch (Exception $e) {
            $connect->rollBack();
            http_response_code(500);
            error_log('Error saving behavior checklist: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Failed to save checklist. Please try again.',
                'details' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action. Use: list, save']);
}

// Generate AI-powered feedback based on child's profile
function generateAIFeedback($ageMonths, $growthMetrics, $speechMetrics, $categories, $childBehaviorMap) {
    $feedback = [];

    // Growth assessment
    if ($growthMetrics && $growthMetrics['weight'] && $growthMetrics['height']) {
        $weight = floatval($growthMetrics['weight']);
        $height = floatval($growthMetrics['height']) / 100; // convert to meters
        $bmi = $weight / ($height * $height);

        if ($bmi >= 14 && $bmi <= 18) {
            $feedback[] = "Physical growth is on track with a healthy weight-to-height ratio (BMI: " . round($bmi, 1) . ").";
        } elseif ($bmi < 14) {
            $feedback[] = "Consider discussing healthy weight gain strategies with your pediatrician.";
        } else {
            $feedback[] = "Monitor activity levels and nutrition to maintain healthy growth patterns.";
        }
    }

    // Motor development assessment
    $exhibitedCount = 0;
    $totalCount = 0;
    foreach ($categories as $cat) {
        foreach ($cat['behaviors'] as $b) {
            $totalCount++;
            if ($b['is_exhibited']) $exhibitedCount++;
        }
    }

    $completionRate = $totalCount > 0 ? ($exhibitedCount / $totalCount * 100) : 0;

    if ($completionRate >= 75) {
        $feedback[] = "Excellent motor development! Your child is meeting most age-expected milestones.";
    } elseif ($completionRate >= 50) {
        $feedback[] = "Good progress on motor skills. Continue encouraging daily physical activities and play.";
    } else {
        $feedback[] = "Consider adding more motor skill activities. Consult your pediatrician if you have concerns.";
    }

    // Speech-motor correlation
    if ($speechMetrics && $speechMetrics['vocabulary_score']) {
        $expectedVocab = min(50, $ageMonths * 3);
        if ($speechMetrics['vocabulary_score'] >= $expectedVocab) {
            $feedback[] = "Speech development is advanced for age - support with rich language exposure and reading.";
        } elseif ($speechMetrics['vocabulary_score'] < $expectedVocab * 0.5) {
            $feedback[] = "Speech and motor skills may benefit from early intervention assessment.";
        }
    }

    // Head circumference note
    if ($growthMetrics && $growthMetrics['head_circumference']) {
        $feedback[] = "Head circumference is being tracked - consistent growth along a percentile curve is what matters most.";
    }

    return implode(" ", $feedback) ?: "Continue monitoring development with regular pediatric check-ups. Every child develops at their own pace.";
}
?>
