<?php
/**
 * Bright Steps – AI Activities API
 * Uses OpenAI GPT to recommend personalized articles, activities, and games
 * based on child's developmental data.
 */
session_start();
include 'connection.php';
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
                ['title' => 'Sleep Training Basics', 'summary' => 'Gentle methods to help your baby develop healthy sleep patterns.', 'category' => 'health', 'read_time' => '6 min']
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
                ['title' => 'Toddler Hygiene Routines', 'summary' => 'Making handwashing and teeth brushing fun with songs and routines.', 'category' => 'health', 'read_time' => '3 min']
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
                ['title' => 'Screen Time Guidelines for Kids', 'summary' => 'How much is too much? Setting healthy limits on digital device usage for preschoolers.', 'category' => 'health', 'read_time' => '4 min']
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
                ['title' => 'Balanced Lunchbox Ideas', 'summary' => 'Nutritious and appealing lunch ideas that kids will actually eat at school.', 'category' => 'nutrition', 'read_time' => '4 min']
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

        // Gather child data
        $stmt = $connect->prepare(
            "SELECT c.first_name, c.last_name, c.birth_day, c.birth_month, c.birth_year, c.gender
             FROM child c WHERE c.child_id = ? AND c.parent_id = (
                SELECT parent_id FROM parent WHERE parent_id = ?
             )"
        );
        $stmt->execute([$childId, $userId]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$child) {
            echo json_encode(['error' => 'Child not found']);
            exit();
        }

        // Calculate age in months
        $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
        $ageMonths = floor((time() - $bd) / (30.44 * 86400));

        // Get latest growth data
        $stmt2 = $connect->prepare(
            "SELECT height, weight FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 1"
        );
        $stmt2->execute([$childId]);
        $growth = $stmt2->fetch(PDO::FETCH_ASSOC);

        // Get latest speech data
        $stmt3 = $connect->prepare(
            "SELECT sa.vocabulary_score, sa.clarify_score, sa.transcript
             FROM speech_analysis sa
             INNER JOIN voice_sample vs ON sa.sample_id = vs.sample_id
             WHERE vs.child_id = ?
             ORDER BY sa.analyzed_at DESC LIMIT 1"
        );
        $stmt3->execute([$childId]);
        $speech = $stmt3->fetch(PDO::FETCH_ASSOC);

        // Get achieved milestones
        $stmt4 = $connect->prepare(
            "SELECT m.category, m.title FROM child_milestones cm
             INNER JOIN milestones m ON cm.milestone_id = m.milestone_id
             WHERE cm.child_id = ? ORDER BY cm.achieved_at DESC LIMIT 5"
        );
        $stmt4->execute([$childId]);
        $milestones = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        // Get recently completed activities
        $stmt5 = $connect->prepare(
            "SELECT title, category FROM child_activities 
             WHERE child_id = ? AND is_completed = 1 
             ORDER BY completed_at DESC LIMIT 5"
        );
        $stmt5->execute([$childId]);
        $recentActivities = $stmt5->fetchAll(PDO::FETCH_ASSOC);

        // Build context for OpenAI
        $ageDisplay = $ageMonths >= 24 ? floor($ageMonths / 12) . ' years old' : $ageMonths . ' months old';
        $context = "Child: {$child['first_name']}, {$ageDisplay}, Gender: {$child['gender']}.\n";

        if ($growth) {
            $context .= "Growth: Weight {$growth['weight']}kg, Height {$growth['height']}cm.\n";
        }
        if ($speech) {
            $context .= "Speech: Vocabulary score {$speech['vocabulary_score']}, Clarity {$speech['clarify_score']}.\n";
        }
        if (!empty($milestones)) {
            $context .= "Recent milestones achieved: " . implode(', ', array_column($milestones, 'title')) . ".\n";
        }
        if (!empty($recentActivities)) {
            $context .= "Recent activities completed: " . implode(', ', array_column($recentActivities, 'title')) . ".\n";
        }

        $apiKey = getEnvValue('OPENAI_API_KEY');
        if (!$apiKey || strpos($apiKey, 'your-key') !== false) {
            echo json_encode(['error' => 'OpenAI API key not configured']);
            exit();
        }

        $prompt = "You are a child development expert for the Bright Steps platform. Based on the following child data, provide personalized recommendations in JSON format.

$context

Return EXACTLY this JSON structure (no markdown, no backticks, just raw JSON):
{
  \"articles\": [
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"parenting|development|health|nutrition\", \"read_time\": \"5 min\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"},
    {\"title\": \"...\", \"summary\": \"...\", \"category\": \"...\", \"read_time\": \"...\"}
  ],
  \"real_life_activities\": [
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"15 min\", \"category\": \"motor|speech|cognitive|social\", \"difficulty\": \"easy|medium|hard\", \"materials\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"...\", \"category\": \"...\", \"difficulty\": \"...\", \"materials\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"duration\": \"...\", \"category\": \"...\", \"difficulty\": \"...\", \"materials\": \"...\"}
  ],
  \"website_games\": [
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"interactive|quiz|creative\", \"skill_focus\": \"...\", \"duration\": \"10 min\"},
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"...\", \"skill_focus\": \"...\", \"duration\": \"...\"},
    {\"title\": \"...\", \"description\": \"...\", \"type\": \"...\", \"skill_focus\": \"...\", \"duration\": \"...\"}
  ]
}

Make all recommendations age-appropriate, specific, and actionable. Vary the categories to cover different developmental areas.";

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
        curl_close($ch);

        if ($httpCode !== 200) {
            // Fallback to curated activities when API is unavailable
            $fallback = getCuratedActivities($ageMonths, $child['first_name']);
            echo json_encode(['success' => true, 'recommendations' => $fallback, 'source' => 'curated']);
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
            echo json_encode(['error' => 'Failed to parse AI response', 'raw' => $content]);
            exit();
        }

        // Store recommended activities in DB
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

        echo json_encode(['success' => true, 'recommendations' => $recommendations]);
        break;

    // ── Mark an activity as completed ──────────────────────────
    case 'complete':
        $input = json_decode(file_get_contents('php://input'), true);
        $activityId = $input['activity_id'] ?? null;
        $childId = $input['child_id'] ?? null;

        if (!$activityId || !$childId) {
            echo json_encode(['error' => 'activity_id and child_id required']);
            exit();
        }

        // Mark as completed
        $stmt = $connect->prepare(
            "UPDATE child_activities SET is_completed = 1, completed_at = NOW(), points_earned = 15
             WHERE activity_id = ? AND child_id = ? AND is_completed = 0"
        );
        $stmt->execute([$activityId, $childId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['error' => 'Activity not found or already completed']);
            exit();
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
        }

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
            'points_earned' => 15
        ]);
        break;

    // ── Get activity history for a child ──────────────────────
    case 'history':
        $childId = $_GET['child_id'] ?? null;
        if (!$childId) {
            echo json_encode(['error' => 'child_id required']);
            exit();
        }

        $stmt = $connect->prepare(
            "SELECT * FROM child_activities WHERE child_id = ? ORDER BY created_at DESC LIMIT 50"
        );
        $stmt->execute([$childId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'activities' => $activities]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: recommend, complete, history']);
        break;
}
