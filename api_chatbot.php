<?php
/**
 * Bright Steps – Chatbot API Endpoint
 * Accepts a user message + child_id, fetches child context, calls OpenAI.
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

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
if (!$apiKey || strpos($apiKey, 'your-key') !== false) {
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
$systemPrompt = "You are Bright Steps Assistant, a warm, expert child development advisor embedded in the Bright Steps parenting platform. 
You have access to the following real data about the parent's child:

{$childContext}

Guidelines:
- Be highly conversational, warm, and human-like. Imagine you are directly speaking with the parent.
- Always reference the child by name and use their actual recorded age, speech, growth, and motor data in your answers.
- CRITICAL: Do NOT provide generic bulleted lists of recommendations for 'all age groups' or 'children of this age'. Instead, weave your advice directly into a conversational paragraph tailored exclusively to this specific child's context.
- If they ask about growth, speech, or motor skills, seamlessly reference their exact scores or percentiles provided in the context above.
- Keep responses concise (under 250 words), friendly, and deeply customized.
- Never diagnose or replace professional medical advice — politely recommend consulting a specialist if asked for medical diagnoses.";

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
        'max_tokens' => 500
    ]),
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'AI service temporarily unavailable. Please try again.', 'fallback' => true]);
    exit();
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response right now.';

echo json_encode(['success' => true, 'reply' => $reply]);
?>
