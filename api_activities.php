<?php
/**
 * Bright Steps – AI Activities API
 * Uses OpenAI GPT to recommend personalized articles, activities, and games
 * based on child's developmental data.
 */
session_start();
require_once 'connection.php';

// Check if database connection is available
if (!isset($connect) || !$connect) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Load OpenAI key from .env
function getEnvValue($key) {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) return null;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        if (trim($k) === $key) return trim($v);
    }
    return null;
}

// Curated fallback activities when OpenAI is unavailable
function getCuratedActivities($ageMonths, $childName) {
    $ageGroup = $ageMonths < 12 ? 'infant' : ($ageMonths < 24 ? 'toddler' : ($ageMonths < 48 ? 'preschool' : 'school'));
    
    $activities = [
        'infant' => [
            'articles' => [
                ['title' => 'Baby\'s First Year: Development Guide', 'summary' => 'Essential milestones to look for in your baby\'s first 12 months, from first smiles to first steps.', 'category' => 'development', 'read_time' => '5 min'],
                ['title' => 'Healthy Nutrition for Infants', 'summary' => 'When and how to introduce solid foods, plus tips for balanced baby nutrition.', 'category' => 'nutrition', 'read_time' => '4 min'],
                ['title' => 'Sleep Training Basics', 'summary' => 'Gentle methods to help your baby develop healthy sleep patterns.', 'category' => 'health', 'read_time' => '6 min'],
                ['title' => 'Understanding Baby Cries', 'summary' => 'A guide to decoding what your infant is trying to tell you.', 'category' => 'parenting', 'read_time' => '4 min'],
                ['title' => 'Sensory Play for Brain Growth', 'summary' => 'How simple touch and sound games wire your baby\'s developing brain.', 'category' => 'development', 'read_time' => '5 min']
            ],
            'real_life_activities' => [
                ['title' => 'Tummy Time Play', 'description' => 'Place baby on their tummy with colorful toys in front. This strengthens neck and shoulder muscles.', 'duration' => '10 min', 'category' => 'motor', 'difficulty' => 'easy', 'materials' => 'Play mat, soft toys'],
                ['title' => 'Sensory Bottles', 'description' => 'Fill clear bottles with water, glitter, and small items. Let baby watch and shake them.', 'duration' => '15 min', 'category' => 'cognitive', 'difficulty' => 'easy', 'materials' => 'Plastic bottles, water, glitter, small beads'],
                ['title' => 'Sing-Along Time', 'description' => 'Sing nursery rhymes with hand gestures. Helps develop language recognition and bonding.', 'duration' => '10 min', 'category' => 'speech', 'difficulty' => 'easy', 'materials' => 'None']
            ],
            'website_games' => [
                ['title' => 'Color Discovery', 'description' => 'Interactive game showing bright colors with sounds to stimulate visual development.', 'type' => 'interactive', 'skill_focus' => 'Visual tracking', 'duration' => '5 min'],
                ['title' => 'Animal Sounds Match', 'description' => 'Listen to animal sounds and see matching pictures. Great for auditory development.', 'type' => 'interactive', 'skill_focus' => 'Auditory recognition', 'duration' => '5 min'],
                ['title' => 'Peek-a-Boo Digital', 'description' => 'Fun peek-a-boo animations that teach object permanence.', 'type' => 'interactive', 'skill_focus' => 'Object permanence', 'duration' => '5 min']
            ]
        ],
        'toddler' => [
            'articles' => [
                ['title' => 'Toddler Speech: When to Worry', 'summary' => 'Understanding speech milestones and when to seek professional help for your toddler.', 'category' => 'development', 'read_time' => '5 min'],
                ['title' => 'Healthy Meals for Picky Eaters', 'summary' => 'Creative strategies to ensure your toddler gets proper nutrition despite being selective.', 'category' => 'nutrition', 'read_time' => '4 min'],
                ['title' => 'Toddler Hygiene Routines', 'summary' => 'Making handwashing and teeth brushing fun with songs and routines.', 'category' => 'health', 'read_time' => '3 min'],
                ['title' => 'Navigating the Terrible Twos', 'summary' => 'Expert advice on managing toddler emotions and setting boundaries.', 'category' => 'parenting', 'read_time' => '5 min'],
                ['title' => 'Potty Training 101', 'summary' => 'Signs of readiness and a gentle approach to toilet training.', 'category' => 'health', 'read_time' => '6 min']
            ],
            'real_life_activities' => [
                ['title' => 'Building Block Tower', 'description' => 'Stack blocks as high as possible. Count each block together as you build.', 'duration' => '15 min', 'category' => 'motor', 'difficulty' => 'easy', 'materials' => 'Building blocks'],
                ['title' => 'Naming Game Walk', 'description' => 'Walk around the house or yard naming everything you see. Repeat words clearly.', 'duration' => '15 min', 'category' => 'speech', 'difficulty' => 'easy', 'materials' => 'None'],
                ['title' => 'Water Play Station', 'description' => 'Set up cups and containers with water. Practice pouring and scooping to build coordination.', 'duration' => '20 min', 'category' => 'motor', 'difficulty' => 'easy', 'materials' => 'Cups, bowls, water, towel']
            ],
            'website_games' => [
                ['title' => 'Shape Sorter', 'description' => 'Drag shapes to matching holes. Teaches shape recognition and problem-solving.', 'type' => 'interactive', 'skill_focus' => 'Shape recognition', 'duration' => '10 min'],
                ['title' => 'Word Builder', 'description' => 'Tap pictures to hear and learn new words. Builds vocabulary.', 'type' => 'interactive', 'skill_focus' => 'Vocabulary', 'duration' => '10 min'],
                ['title' => 'Color Mixing', 'description' => 'Mix primary colors to make new ones. Teaches cause and effect.', 'type' => 'creative', 'skill_focus' => 'Color recognition', 'duration' => '10 min']
            ]
        ],
        'preschool' => [
            'articles' => [
                ['title' => 'School Readiness Checklist', 'summary' => 'Key skills your preschooler should develop before starting school, from social skills to basic counting.', 'category' => 'development', 'read_time' => '6 min'],
                ['title' => 'Managing Tantrums Effectively', 'summary' => 'Evidence-based strategies for handling emotional outbursts with empathy and firmness.', 'category' => 'parenting', 'read_time' => '5 min'],
                ['title' => 'Screen Time Guidelines for Kids', 'summary' => 'How much is too much? Setting healthy limits on digital device usage for preschoolers.', 'category' => 'health', 'read_time' => '4 min'],
                ['title' => 'Fostering Independence', 'summary' => 'Teaching preschoolers to dress themselves and clean up their toys.', 'category' => 'development', 'read_time' => '5 min'],
                ['title' => 'Healthy Snacks for Energy', 'summary' => 'Quick, nutritious snacks that keep active preschoolers fueled.', 'category' => 'nutrition', 'read_time' => '3 min']
            ],
            'real_life_activities' => [
                ['title' => 'Obstacle Course', 'description' => 'Create a fun indoor or outdoor obstacle course with pillows, chairs, and hoops.', 'duration' => '20 min', 'category' => 'motor', 'difficulty' => 'medium', 'materials' => 'Pillows, chairs, hoops'],
                ['title' => 'Story Retelling', 'description' => 'Read a short story then ask your child to retell it in their own words.', 'duration' => '15 min', 'category' => 'speech', 'difficulty' => 'medium', 'materials' => 'Picture book'],
                ['title' => 'Sorting & Counting Game', 'description' => 'Sort objects by color, shape, or size. Count each group together.', 'duration' => '15 min', 'category' => 'cognitive', 'difficulty' => 'easy', 'materials' => 'Buttons, beads, or small toys']
            ],
            'website_games' => [
                ['title' => 'Number Adventures', 'description' => 'Interactive counting game with fun characters. Learn numbers 1-20.', 'type' => 'interactive', 'skill_focus' => 'Counting', 'duration' => '10 min'],
                ['title' => 'Letter Tracing', 'description' => 'Trace letters on screen to practice writing. Includes uppercase and lowercase.', 'type' => 'creative', 'skill_focus' => 'Pre-writing', 'duration' => '10 min'],
                ['title' => 'Memory Match', 'description' => 'Flip cards to find matching pairs. Builds concentration and memory.', 'type' => 'quiz', 'skill_focus' => 'Memory', 'duration' => '10 min']
            ]
        ],
        'school' => [
            'articles' => [
                ['title' => 'Building Confidence in Children', 'summary' => 'How to nurture self-esteem and resilience in school-age children through daily interactions.', 'category' => 'parenting', 'read_time' => '5 min'],
                ['title' => 'Active Kids, Healthy Kids', 'summary' => 'The importance of physical activity and how to make exercise fun for school-age children.', 'category' => 'health', 'read_time' => '4 min'],
                ['title' => 'Balanced Lunchbox Ideas', 'summary' => 'Nutritious and appealing lunch ideas that kids will actually eat at school.', 'category' => 'nutrition', 'read_time' => '4 min'],
                ['title' => 'Helping with Homework', 'summary' => 'How to guide your child without doing the work for them.', 'category' => 'development', 'read_time' => '5 min'],
                ['title' => 'Bullying Prevention', 'summary' => 'Signs to look out for and how to talk to your child about bullying.', 'category' => 'parenting', 'read_time' => '6 min']
            ],
            'real_life_activities' => [
                ['title' => 'Science Experiment', 'description' => 'Make a baking soda volcano or grow crystals. Learn through hands-on discovery.', 'duration' => '30 min', 'category' => 'cognitive', 'difficulty' => 'medium', 'materials' => 'Baking soda, vinegar, food coloring'],
                ['title' => 'Journal Writing', 'description' => 'Write about the day\'s events. Draw pictures to accompany the writing.', 'duration' => '15 min', 'category' => 'speech', 'difficulty' => 'medium', 'materials' => 'Notebook, pencils, crayons'],
                ['title' => 'Team Sports Practice', 'description' => 'Practice throwing, catching, or kicking a ball. Great for coordination and social skills.', 'duration' => '25 min', 'category' => 'motor', 'difficulty' => 'medium', 'materials' => 'Ball']
            ],
            'website_games' => [
                ['title' => 'Math Challenge', 'description' => 'Solve addition and subtraction problems in a fun racing game format.', 'type' => 'quiz', 'skill_focus' => 'Math', 'duration' => '15 min'],
                ['title' => 'Reading Comprehension', 'description' => 'Read short stories and answer questions to build comprehension skills.', 'type' => 'quiz', 'skill_focus' => 'Reading', 'duration' => '15 min'],
                ['title' => 'Creative Drawing', 'description' => 'Follow prompts to create digital artwork. Encourages creativity and fine motor skills.', 'type' => 'creative', 'skill_focus' => 'Creativity', 'duration' => '15 min']
            ]
        ]
    ];
    
    return $activities[$ageGroup] ?? $activities['preschool'];
}

switch ($action) {

    // ── Get AI-powered activity recommendations ──────────────
    case 'recommend':
        $childId = $_GET['child_id'] ?? null;
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        // Check database connection first
        if (!$connect) {
            // DB connection failed - return curated activities as fallback
            $fallback = getCuratedActivities(0, 'your child');
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated (db_error)']);
            exit();
        }

        // Gather child data with error handling
        try {
            $stmt = $connect->prepare(
                "SELECT c.first_name, c.last_name, c.birth_day, c.birth_month, c.birth_year, c.gender
                 FROM child c WHERE c.child_id = ? AND c.parent_id = (
                    SELECT parent_id FROM parent WHERE parent_id = ?
                 )"
            );
            $stmt->execute([$childId, $userId]);
            $child = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Child data query failed: " . $e->getMessage());
            $fallback = getCuratedActivities(0, 'your child');
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated (db_query_error)']);
            exit();
        }

        if (!$child) {
            echo json_encode(['error' => 'Child not found']);
            exit();
        }

        // Calculate age in months
        $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
        $ageMonths = floor((time() - $bd) / (30.44 * 86400));

        // Get latest growth data (2 records to detect direction)
        try {
            $stmt2 = $connect->prepare(
                "SELECT height, weight, head_circumference, recorded_at FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 2"
            );
            $stmt2->execute([$childId]);
            $growthRecords = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            $growth = $growthRecords[0] ?? null;
            $prevGrowth = $growthRecords[1] ?? null;
        } catch (PDOException $e) {
            $growth = null;
            $prevGrowth = null;
        }

        // Get latest speech data
        try {
            $stmt3 = $connect->prepare(
                "SELECT sa.vocabulary_score, sa.clarify_score, sa.transcript
                 FROM speech_analysis sa
                 INNER JOIN voice_sample vs ON sa.sample_id = vs.sample_id
                 WHERE vs.child_id = ?
                 ORDER BY sa.analyzed_at DESC LIMIT 1"
            );
            $stmt3->execute([$childId]);
            $speech = $stmt3->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $speech = null;
        }

        // Get motor milestone completion percentage
        try {
            $stmtMotorTotal = $connect->prepare("SELECT COUNT(*) FROM milestones WHERE category IN ('gross_motor','fine_motor')");
            $stmtMotorTotal->execute();
            $motorTotal = (int)$stmtMotorTotal->fetchColumn();

            $stmtMotorDone = $connect->prepare(
                "SELECT COUNT(*) FROM child_milestones cm
                 JOIN milestones m ON cm.milestone_id = m.milestone_id
                 WHERE cm.child_id = ? AND m.category IN ('gross_motor','fine_motor') AND cm.is_achieved = 1"
            );
            $stmtMotorDone->execute([$childId]);
            $motorDone = (int)$stmtMotorDone->fetchColumn();
            $motorPct = $motorTotal > 0 ? round(($motorDone / $motorTotal) * 100) : 0;
        } catch (PDOException $e) {
            $motorTotal = 0;
            $motorDone = 0;
            $motorPct = 0;
        }

        // Get achieved milestones
        try {
            $stmt4 = $connect->prepare(
                "SELECT m.category, m.title FROM child_milestones cm
                 INNER JOIN milestones m ON cm.milestone_id = m.milestone_id
                 WHERE cm.child_id = ? AND cm.is_achieved = 1 ORDER BY cm.achieved_at DESC LIMIT 5"
            );
            $stmt4->execute([$childId]);
            $milestones = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $milestones = [];
        }

        // Get recently completed activities
        try {
            $stmt5 = $connect->prepare(
                "SELECT title, category FROM child_activities
                 WHERE child_id = ? AND is_completed = 1
                 ORDER BY completed_at DESC LIMIT 5"
            );
            $stmt5->execute([$childId]);
            $recentActivities = $stmt5->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $recentActivities = [];
        }

        // Build rich context for OpenAI
        $ageDisplay = $ageMonths >= 24 ? floor($ageMonths / 12) . ' years old' : $ageMonths . ' months old';
        $context = "Child: {$child['first_name']}, {$ageDisplay}, Gender: {$child['gender']}.\n";

        if ($growth) {
            $growthDir = 'stable';
            if ($prevGrowth) {
                $wDiff = floatval($growth['weight']) - floatval($prevGrowth['weight']);
                $hDiff = floatval($growth['height']) - floatval($prevGrowth['height']);
                if ($wDiff > 0.3 && $hDiff > 0.5) $growthDir = 'growing well';
                elseif ($wDiff < 0) $growthDir = 'weight declining — needs attention';
                elseif ($hDiff <= 0) $growthDir = 'height stagnant — monitor closely';
            }
            $context .= "Growth: Weight {$growth['weight']}kg, Height {$growth['height']}cm. Trend: {$growthDir}.\n";
        } else {
            $context .= "Growth: No measurements recorded yet.\n";
        }

        if ($speech) {
            $vocLevel = $speech['vocabulary_score'] >= 80 ? 'excellent' : ($speech['vocabulary_score'] >= 50 ? 'developing' : 'needs improvement');
            $claLevel = $speech['clarify_score'] >= 80 ? 'excellent' : ($speech['clarify_score'] >= 50 ? 'developing' : 'needs improvement');
            $context .= "Speech: Vocabulary {$speech['vocabulary_score']}% ({$vocLevel}), Clarity {$speech['clarify_score']}% ({$claLevel}).\n";
        } else {
            $context .= "Speech: No speech analysis data available yet.\n";
        }

        $context .= "Motor Skills: {$motorDone}/{$motorTotal} milestones achieved ({$motorPct}%).\n";

        // Identify weakest area for targeting
        $weakAreas = [];
        if ($motorPct < 50) $weakAreas[] = 'motor skills';
        if ($speech && $speech['vocabulary_score'] < 50) $weakAreas[] = 'vocabulary';
        if ($speech && $speech['clarify_score'] < 50) $weakAreas[] = 'speech clarity';
        if (!$growth) $weakAreas[] = 'growth tracking (no data)';
        $weakStr = !empty($weakAreas) ? "Areas needing focus: " . implode(', ', $weakAreas) . "." : "All areas progressing well.";
        $context .= $weakStr . "\n";

        if (!empty($milestones)) {
            $context .= "Recent milestones achieved: " . implode(', ', array_column($milestones, 'title')) . ".\n";
        }
        if (!empty($recentActivities)) {
            $context .= "Recent activities completed: " . implode(', ', array_column($recentActivities, 'title')) . ".\n";
        }

        $apiKey = getEnvValue('OPENAI_API_KEY');
        if (!$apiKey || strpos($apiKey, 'your-key') !== false || strpos($apiKey, 'sk-') !== 0) {
            error_log("OpenAI API key invalid or not configured. Key starts with: " . substr($apiKey, 0, 10) . "...");
            $fallback = getCuratedActivities($ageMonths, $child['first_name']);
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated (no api key)']);
            exit();
        }

        $prompt = "You are a child development expert for the Bright Steps platform. Based on the following child data, provide personalized recommendations in JSON format.

$context

Return EXACTLY this JSON structure (no markdown, no backticks, just raw JSON):
{
  \"articles\": [
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"parenting|development|health|nutrition\", \"read_time\": \"5 min\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"}
  ],
  \"real_life_activities\": [
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"15 min\", \"category\": \"motor|speech|cognitive|social\", \"difficulty\": \"easy|medium|hard\", \"materials\": \"...\", \"reason_picked\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"...\", \"category\": \"...\", \"difficulty\": \"...\", \"materials\": \"...\", \"reason_picked\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"...\", \"category\": \"...\", \"difficulty\": \"...\", \"materials\": \"...\", \"reason_picked\": \"...\"}
  ],
  \"website_games\": [
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"interactive|quiz|creative\", \"skill_focus\": \"...\", \"duration\": \"10 min\"},
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"...\", \"skill_focus\": \"...\", \"duration\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"...\", \"skill_focus\": \"...\", \"duration\": \"...\"}
  ]
}

Make all recommendations age-appropriate, specific, and actionable. Vary the categories to cover different developmental areas.
CRITICAL INSTRUCTION: For each 'real_life_activities' item, you MUST provide a 'reason_picked' field that explicitly explains why you picked it. This explanation MUST accurately mention the child's exact age (e.g., '$ageDisplay') and directly reference their specific conditions, recent milestones, or speech/growth status if available.";

        // OpenAI API call
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a child development expert. Always respond with valid JSON only, no markdown.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.8,
                'max_tokens' => 1500
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            // Log error for debugging
            error_log("OpenAI API error (HTTP $httpCode): " . substr($response, 0, 500));
            if ($curlError) error_log("Curl error: " . $curlError);

            // Fallback to curated activities when API is unavailable or rate limited
            $fallback = getCuratedActivities($ageMonths, $child['first_name']);
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated (api_error)']);
            exit();
        }

        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';

        // Parse JSON from response
        $recommendations = json_decode($content, true);
        if (!$recommendations) {
            // Try to extract JSON from markdown-wrapped response
            preg_match('/\{[\s\S]*\}/', $content, $matches);
            if (!empty($matches)) {
                $recommendations = json_decode($matches[0], true);
            }
        }

        if (!$recommendations) {
            $fallback = getCuratedActivities($ageMonths, $child['first_name']);
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated (unparseable api)']);
            exit();
        }

        // Store recommended activities in DB (skip if table doesn't exist)
        try {
            $insertStmt = $connect->prepare(
                "INSERT INTO child_activities (child_id, title, description, category, duration_minutes, difficulty, source)
                 VALUES (?, ?, ?, ?, ?, ?, 'ai')"
            );

            if (isset($recommendations['real_life_activities'])) {
                foreach ($recommendations['real_life_activities'] as $act) {
                    $dur = (int) filter_var($act['duration'] ?? '15', FILTER_SANITIZE_NUMBER_INT);
                    $cat = $act['category'] ?? 'real_life';
                    if (!in_array($cat, ['speech', 'motor', 'cognitive', 'social'])) $cat = 'real_life';
                    $diff = $act['difficulty'] ?? 'medium';
                    if (!in_array($diff, ['easy', 'medium', 'hard'])) $diff = 'medium';
                    try {
                        $insertStmt->execute([$childId, $act['title'] ?? '', $act['description'] ?? '', $cat, $dur, $diff]);
                    } catch (Exception $e) { /* skip duplicates */ }
                }
            }

            if (isset($recommendations['articles'])) {
                foreach ($recommendations['articles'] as $art) {
                    try {
                        $insertStmt->execute([$childId, $art['title'] ?? '', $art['summary'] ?? '', 'article', 5, 'easy']);
                    } catch (Exception $e) { /* skip */ }
                }
            }

            if (isset($recommendations['website_games'])) {
                foreach ($recommendations['website_games'] as $game) {
                    $dur = (int) filter_var($game['duration'] ?? '10', FILTER_SANITIZE_NUMBER_INT);
                    try {
                        $insertStmt->execute([$childId, $game['title'] ?? '', $game['description'] ?? '', 'website_game', $dur, 'medium']);
                    } catch (Exception $e) { /* skip */ }
                }
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other DB error - still return the recommendations
            error_log("Failed to store activities: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'recommendations' => $recommendations]);
        break;

    // ── Mark an activity as completed ──────────────────────────
    case 'complete':
        if (!$connect) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection unavailable']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $activityId = $input['activity_id'] ?? null;
        $childId = $input['child_id'] ?? null;

        $category = $input['category'] ?? null;
        $title = $input['title'] ?? 'Interactive Activity';

        if (!$activityId && !$category) {
            echo json_encode(['error' => 'activity_id or category required']);
            exit();
        }

        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        try {
            // Mark as completed
            if ($activityId) {
                $stmt = $connect->prepare(
                    "UPDATE child_activities SET is_completed = 1, completed_at = NOW(), points_earned = 15
                     WHERE activity_id = ? AND child_id = ? AND is_completed = 0"
                );
                $stmt->execute([$activityId, $childId]);

                if ($stmt->rowCount() === 0) {
                    echo json_encode(['error' => 'Activity not found or already completed']);
                    exit();
                }
            } else {
                // Generic completion (e.g. ad-hoc game)
                $stmt = $connect->prepare(
                    "INSERT INTO child_activities (child_id, title, category, is_completed, completed_at, points_earned)
                     VALUES (?, ?, ?, 1, NOW(), 15)"
                );
                $stmt->execute([$childId, $title, $category]);
            }

            // Award points to wallet
            $stmtW = $connect->prepare("SELECT wallet_id FROM points_wallet WHERE child_id = ? LIMIT 1");
            $stmtW->execute([$childId]);
            $wallet = $stmtW->fetch(PDO::FETCH_ASSOC);

            if ($wallet) {
                $stmtU = $connect->prepare(
                    "UPDATE points_wallet SET total_points = total_points + 15 WHERE wallet_id = ?"
                );
                $stmtU->execute([$wallet['wallet_id']]);

                $stmtT = $connect->prepare("SELECT total_points FROM points_wallet WHERE wallet_id = ?");
                $stmtT->execute([$wallet['wallet_id']]);
                $newPoints = $stmtT->fetchColumn();

                if ($newPoints % 300 >= 225 && ($newPoints - 15) % 300 < 225) {
                    $stmtReward = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                    $stmtReward->execute([$userId, 'Close to a Reward!', "You have $newPoints points! You are 75% of the way to redeeming a 300-point Certificate!"]);
                }
            }

            // Check for activity-based badges instantly
            $awardBadge = function($badgeName) use ($connect, $childId, $userId) {
                $stmt = $connect->prepare("SELECT badge_id FROM badge WHERE name = ? LIMIT 1");
                $stmt->execute([$badgeName]);
                $badge = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($badge) {
                    $stmt2 = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = ? AND badge_id = ?");
                    $stmt2->execute([$childId, $badge['badge_id']]);
                    if ($stmt2->fetchColumn() == 0) {
                        $connect->prepare("INSERT INTO child_badge (child_id, badge_id) VALUES (?, ?)")->execute([$childId, $badge['badge_id']]);
                        $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)")->execute([$userId, "Badge Earned: $badgeName", "Congratulations! You earned the '$badgeName' badge!"]);
                        return $badgeName;
                    }
                }
                return false;
            };

            $stmtTot = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1");
            $stmtTot->execute([$childId]);
            $totalActivities = (int)$stmtTot->fetchColumn();

            $stmtWk = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $stmtWk->execute([$childId]);
            $weeklyCount = (int)$stmtWk->fetchColumn();

            $stmtMo = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmtMo->execute([$childId]);
            $monthlyCount = (int)$stmtMo->fetchColumn();

            $stmtArt = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'article' AND is_completed = 1");
            $stmtArt->execute([$childId]);
            $articleCount = (int)$stmtArt->fetchColumn();

            $stmtGame = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'website_game' AND is_completed = 1");
            $stmtGame->execute([$childId]);
            $gameCount = (int)$stmtGame->fetchColumn();

            $stmtMotor = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'motor' AND is_completed = 1");
            $stmtMotor->execute([$childId]);
            $motorCount = (int)$stmtMotor->fetchColumn();

            $stmtSpeech = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND category = 'speech' AND is_completed = 1");
            $stmtSpeech->execute([$childId]);
            $speechCount = (int)$stmtSpeech->fetchColumn();

            $earnedBadges = [];
            if ($totalActivities >= 1) { $b = $awardBadge('First Steps'); if ($b) $earnedBadges[] = $b; }
            if ($weeklyCount >= 5) { $b = $awardBadge('Weekly Champion'); if ($b) $earnedBadges[] = $b; }
            if ($monthlyCount >= 20) { $b = $awardBadge('Monthly Master'); if ($b) $earnedBadges[] = $b; }
            if ($articleCount >= 1) { $b = $awardBadge('Article Reader'); if ($b) $earnedBadges[] = $b; }
            if ($articleCount >= 10) { $b = $awardBadge('Bookworm'); if ($b) $earnedBadges[] = $b; }
            if ($gameCount >= 5) { $b = $awardBadge('Game Master'); if ($b) $earnedBadges[] = $b; }
            if ($motorCount >= 5) { $b = $awardBadge('Motor Master'); if ($b) $earnedBadges[] = $b; }
            if ($speechCount >= 5) { $b = $awardBadge('Speech Explorer'); if ($b) $earnedBadges[] = $b; }

            // Create notification
            $stmtN = $connect->prepare(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'milestone', ?, ?)"
            );
            $stmtN->execute([
                $userId,
                '✅ Activity Completed!',
                'You completed an activity and earned 15 points! Keep up the great work!'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Activity completed! +15 points',
                'points_earned' => 15,
                'new_badges' => $earnedBadges
            ]);
        } catch (PDOException $e) {
            error_log("Complete activity error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to complete activity']);
        }
        break;

    // ── Get activity history for a child ──────────────────────
    case 'history':
        if (!$connect) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection unavailable']);
            exit();
        }

        $childId = $_GET['child_id'] ?? null;
        $period = $_GET['period'] ?? 'all';
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        try {
            $periodSql = "";
            if ($period === 'daily') $periodSql = " AND (completed_at >= CURDATE() OR (is_completed=0 AND created_at >= CURDATE())) ";
            else if ($period === 'weekly') $periodSql = " AND (completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR (is_completed=0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))) ";
            else if ($period === 'monthly') $periodSql = " AND (completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR (is_completed=0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))) ";

            $stmt = $connect->prepare(
                "SELECT * FROM child_activities WHERE child_id = ? $periodSql ORDER BY created_at DESC LIMIT 50"
            );
            $stmt->execute([$childId]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'activities' => $activities]);
        } catch (PDOException $e) {
            error_log("History query error: " . $e->getMessage());
            echo json_encode(['success' => true, 'activities' => []]);
        }
        break;

    // ── Get activity completion summary ──────────────────────
    case 'summary':
        if (!$connect) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection unavailable']);
            exit();
        }

        $childId = $_GET['child_id'] ?? null;
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        try {
            $stmtD = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= CURDATE()");
            $stmtD->execute([$childId]);
            $daily = $stmtD->fetchColumn();

            $stmtW = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $stmtW->execute([$childId]);
            $weekly = $stmtW->fetchColumn();

            $stmtM = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmtM->execute([$childId]);
            $monthly = $stmtM->fetchColumn();

            echo json_encode(['success' => true, 'daily_completed' => (int)$daily, 'weekly_completed' => (int)$weekly, 'monthly_completed' => (int)$monthly]);
        } catch (PDOException $e) {
            error_log("Summary query error: " . $e->getMessage());
            echo json_encode(['success' => true, 'daily_completed' => 0, 'weekly_completed' => 0, 'monthly_completed' => 0]);
        }
        break;

    // ── Mark article as read ──────────────────────────────────
    case 'mark-read':
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? null;
        if (!$title) { echo json_encode(['error' => 'title required']); exit(); }

        $alreadyRead = false;
        try {
            // Check if already read
            $chk = $connect->prepare("SELECT COUNT(*) FROM article_reads WHERE user_id = ? AND article_title = ?");
            $chk->execute([$userId, $title]);
            if ((int)$chk->fetchColumn() > 0) {
                $alreadyRead = true;
            } else {
                $stmt = $connect->prepare("INSERT INTO article_reads (user_id, article_title) VALUES (?, ?)");
                $stmt->execute([$userId, $title]);
            }
        } catch (Exception $e) {
            // Table might not exist – create it
            try {
                $connect->exec("CREATE TABLE IF NOT EXISTS `article_reads` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `article_title` varchar(500) NOT NULL,
                    `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_read` (`user_id`, `article_title`(200))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                $stmt = $connect->prepare("INSERT INTO article_reads (user_id, article_title) VALUES (?, ?)");
                $stmt->execute([$userId, $title]);
            } catch (Exception $e2) { /* skip */ }
        }
        
        echo json_encode(['success' => true, 'already_read' => $alreadyRead, 'message' => $alreadyRead ? 'Article was already read' : 'Article marked as read']);
        break;

    // ── 7-day activity chart data ──────────────────────────────
    case 'summary_chart':
        if (!$connect) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection unavailable']);
            exit();
        }

        $childId = $_GET['child_id'] ?? null;
        if (!$childId) { echo json_encode(['error' => 'child_id required']); exit(); }

        try {
            $days = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $label = date('D', strtotime("-{$i} days")); // Mon, Tue, etc.
                $stmtC = $connect->prepare(
                    "SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND DATE(completed_at) = ?"
                );
                $stmtC->execute([$childId, $date]);
                $days[] = ['label' => $label, 'date' => $date, 'count' => (int)$stmtC->fetchColumn()];
            }
            echo json_encode(['success' => true, 'chart_data' => $days]);
        } catch (PDOException $e) {
            error_log("Chart data error: " . $e->getMessage());
            echo json_encode(['success' => true, 'chart_data' => []]);
        }
        break;

    // ── Get all articles the user has already read ────────────
    case 'get-read-articles':
        $readTitles = [];
        try {
            $stmt = $connect->prepare("SELECT article_title FROM article_reads WHERE user_id = ?");
            $stmt->execute([$userId]);
            $readTitles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) { /* table may not exist yet */ }
        echo json_encode(['success' => true, 'read_titles' => $readTitles]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: recommend, complete, history, summary, summary_chart, mark-read, get-read-articles']);
        break;
}
