<?php
/**
 * Bright Steps – Chatbot API Endpoint
 * Accepts a user message + child_id, fetches child context, calls OpenAI.
 */

// Enable CORS for same-origin requests with credentials
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "connection.php";

// Check authentication
if (!isset($_SESSION['id']) || !isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Not authenticated. Please log in.',
        'debug' => [
            'session_id' => session_id(),
            'has_id' => isset($_SESSION['id']) ? true : false,
            'has_email' => isset($_SESSION['email']) ? true : false,
            'session_data' => $_SESSION
        ]
    ]);
    exit();
}

$userId = $_SESSION['id'];

$userId = $_SESSION['id'];
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$childId = $input['child_id'] ?? null;

if (!$message) {
    echo json_encode(['error' => 'message required']);
    exit();
}

// Load OpenAI key
function getEnvValueChat($key) {
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

$apiKey = getEnvValueChat('OPENAI_API_KEY');
if (!$apiKey || empty(trim($apiKey))) {
    echo json_encode(['error' => 'OpenAI API key not configured. Please set your API key in the .env file.']);
    exit();
}

// Build child context
$childContext = "No child selected.";
if ($childId) {
    $stmt = $connect->prepare(
        "SELECT c.first_name, c.last_name, c.birth_day, c.birth_month, c.birth_year, c.gender, c.health_condition
         FROM child c WHERE c.child_id = ?"
    );
    $stmt->execute([$childId]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($child) {
        $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
        $ageMonths = floor((time() - $bd) / (30.44 * 86400));
        $ageDisplay = $ageMonths >= 24 ? floor($ageMonths / 12) . ' years and ' . ($ageMonths % 12) . ' months old' : $ageMonths . ' months old';

        $childContext = "Selected child: {$child['first_name']} {$child['last_name']}, {$ageDisplay}, Gender: {$child['gender']}.";
        if (!empty($child['health_condition'])) {
            $childContext .= " Health conditions: {$child['health_condition']}.";
        }

        // Growth
        $stmt2 = $connect->prepare("SELECT height, weight, head_circumference FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 1");
        $stmt2->execute([$childId]);
        $growth = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($growth) {
            $childContext .= " Latest growth: Weight {$growth['weight']}kg, Height {$growth['height']}cm" . ($growth['head_circumference'] ? ", Head {$growth['head_circumference']}cm" : "") . ".";
        } else {
            $childContext .= " No growth data recorded.";
        }

        // Speech
        $stmt3 = $connect->prepare(
            "SELECT sa.vocabulary_score, sa.clarify_score
             FROM speech_analysis sa
             INNER JOIN voice_sample vs ON sa.sample_id = vs.sample_id
             WHERE vs.child_id = ?
             ORDER BY sa.analyzed_at DESC LIMIT 1"
        );
        $stmt3->execute([$childId]);
        $speech = $stmt3->fetch(PDO::FETCH_ASSOC);
        if ($speech) {
            $childContext .= " Speech analysis: Vocabulary {$speech['vocabulary_score']}%, Clarity {$speech['clarify_score']}%.";
        } else {
            $childContext .= " No speech analysis data yet.";
        }

        // Motor milestones
        $stmtMT = $connect->prepare("SELECT COUNT(*) FROM milestones WHERE category IN ('gross_motor','fine_motor')");
        $stmtMT->execute();
        $motorTotal = (int)$stmtMT->fetchColumn();

        $stmtMD = $connect->prepare(
            "SELECT COUNT(*) FROM child_milestones cm JOIN milestones m ON cm.milestone_id = m.milestone_id
             WHERE cm.child_id = ? AND m.category IN ('gross_motor','fine_motor') AND cm.is_achieved = 1"
        );
        $stmtMD->execute([$childId]);
        $motorDone = (int)$stmtMD->fetchColumn();
        $motorPct = $motorTotal > 0 ? round(($motorDone / $motorTotal) * 100) : 0;
        $childContext .= " Motor milestones: {$motorDone}/{$motorTotal} achieved ({$motorPct}%).";

        // Recent milestones
        $stmtRM = $connect->prepare(
            "SELECT m.title FROM child_milestones cm JOIN milestones m ON cm.milestone_id = m.milestone_id
             WHERE cm.child_id = ? AND cm.is_achieved = 1 ORDER BY cm.achieved_at DESC LIMIT 3"
        );
        $stmtRM->execute([$childId]);
        $rm = $stmtRM->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($rm)) {
            $childContext .= " Recent milestones: " . implode(', ', $rm) . ".";
        }
    }
}

// Call OpenAI
$systemPrompt = "You are the Bright Steps Child Development Assistant — a warm, expert, and highly knowledgeable AI embedded in a comprehensive parenting platform. You provide reliable, evidence-based guidance across ALL aspects of child development.

YOU HAVE ACCESS TO THE FOLLOWING REAL DATA ABOUT THE CHILD:
{$childContext}

YOUR EXPERTISE COVERS 360° OF CHILD DEVELOPMENT:
📊 Growth & Physical Development — height, weight, head circumference, WHO percentiles, growth patterns
🗣️ Speech & Language — vocabulary, clarity, milestones, bilingual development, speech delays
🏃 Motor Skills — gross motor (walking, running, jumping) and fine motor (grasping, drawing, writing)
🧠 Cognitive Development — learning, problem-solving, memory, attention, age-appropriate expectations
😴 Sleep — patterns, routines, regression, age-appropriate sleep needs
🍼 Nutrition & Feeding — breastfeeding, solids, picky eating, balanced diets, meal planning
🧼 Hygiene & Self-Care — bathing, teeth brushing, potty training, handwashing
🛡️ Health & Safety — vaccination schedules, common illnesses, injury prevention, when to call a doctor
🎭 Social-Emotional — attachment, tantrums, sharing, emotional regulation, play skills
📚 Learning & Play — age-appropriate activities, educational games, screen time guidelines
🏥 Medical Appointments — checkup schedules, developmental screenings, specialist referrals

CRITICAL GUIDELINES FOR EVERY RESPONSE:
1. PERSONALIZE DEEPLY — Reference the child BY NAME, their exact age, and specific data points (e.g., \"Emma's vocabulary score of 85% is excellent for 18 months\"). Never give generic age-group advice.
2. BE CONVERSATIONAL — Write as if speaking directly to a caring parent. Use warm, encouraging language. Avoid clinical jargon unless explaining it.
3. PROVIDE ACTIONABLE STEPS — Give 2-3 specific, practical things the parent can do TODAY. Be concrete (e.g., \"Try the 'naming game' during bath time: name each body part as you wash it\").
4. CITE DEVELOPMENTAL SCIENCE — When relevant, briefly mention why your advice works (e.g., \"Research shows that reading aloud daily builds neural connections for language\").
5. ACKNOWLEDGE PROGRESS — If data shows improvement (e.g., motor skills at 85%), celebrate it specifically before suggesting next steps.
6. FLAG CONCERNS APPROPRIATELY — If data suggests a potential delay (e.g., vocabulary below expected range), gently suggest professional evaluation WITHOUT alarming the parent.
7. NEVER DIAGNOSE — Always clarify you're an AI assistant, not a doctor. Recommend pediatricians, speech therapists, or specialists when concerns exceed your scope.
8. KEEP IT CONCISE — Aim for 150-300 words. Use short paragraphs. If the topic is complex, offer to elaborate if the parent asks follow-ups.
9. USE FORMATTING SPARINGLY — Bold key phrases, use bullet points only when listing steps, avoid excessive emojis (max 2 per response).
10. END WITH ENCOURAGEMENT — Close with an affirming statement about the parent's attentiveness or the child's progress.

RESPONSE STRUCTURE (flexible, not rigid):
- Open with warmth + child's name + relevant data point
- Provide 2-3 personalized, actionable recommendations
- Explain the \"why\" briefly (developmental science)
- Gently flag any concerns if data warrants it
- Close with encouragement

TONE: Warm like a trusted pediatric nurse, knowledgeable like a child development professor, encouraging like a supportive friend.

RELIABILITY STANDARDS:
- Base recommendations on established developmental milestones (CDC, WHO, AAP guidelines)
- If unsure or lacking data, say so honestly: \"I'd need to know more about [specific aspect] to give personalized advice\"
- Never invent data or make up statistics
- When citing milestones, use ranges (e.g., \"most children walk between 9-15 months\") not absolutes

MEDICAL DISCLAIMER (include when health topics arise):
\"I'm an AI assistant, not a doctor. For medical concerns, please consult your pediatrician.\"\n";

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
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 600,
        'presence_penalty' => 0.3,
        'frequency_penalty' => 0.3
    ]),
    CURLOPT_TIMEOUT => 45
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log errors for debugging
if ($httpCode !== 200) {
    error_log("Chatbot OpenAI error (HTTP $httpCode): " . substr($response, 0, 500));
    if ($curlError) error_log("Curl error: " . $curlError);
}

if ($httpCode !== 200 || $curlError) {
    echo json_encode([
        'error' => 'AI service temporarily unavailable. Please try again in a moment.',
        'fallback' => true,
        'debug' => $curlError ?: "HTTP $httpCode"
    ]);
    exit();
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? null;

// Check for API errors
if (isset($result['error'])) {
    $errorMsg = $result['error']['message'] ?? 'Unknown API error';
    error_log("OpenAI API error: " . $errorMsg);
    echo json_encode([
        'error' => 'AI service encountered an error. Please try again.',
        'debug' => $errorMsg
    ]);
    exit();
}

if (!$reply) {
    echo json_encode([
        'error' => 'Could not generate response. Please try again.',
        'debug' => 'Empty response from API'
    ]);
    exit();
}

echo json_encode(['success' => true, 'reply' => $reply]);
?>
