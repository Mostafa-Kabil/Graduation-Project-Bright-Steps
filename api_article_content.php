<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
$title = trim($input['title'] ?? '');
$summary = trim($input['summary'] ?? '');

if (!$title) {
    echo json_encode(['error' => 'Title is required.']);
    exit();
}

function getEnvValueArticle($key) {
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

$apiKey = getEnvValueArticle('OPENAI_API_KEY');
if (!$apiKey || empty(trim($apiKey))) {
    echo json_encode(['generated_html' => '<div style="padding:1rem;background:#fee2e2;color:#991b1b;border-radius:8px;">OpenAI API key not configured. Please set your API key in the .env file.</div>']);
    exit();
}

$systemPrompt = "You are Bright Steps, an expert child development and parenting author. Your task is to expand the provided article Title and Summary into a comprehensive, engaging, and highly informative article for parents. \n\nOutput ONLY valid HTML content. Use tags like <p>, <h3>, <ul>, <li>, <strong>. Do NOT wrap the response in markdown blocks like ```html. Keep the tone warm, evidence-based, and actionable. The article should be approx 300-450 words.";

$userPrompt = "Title: $title\nSummary: $summary\n\nPlease write the full detailed article in HTML.";

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
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800
    ]),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 45
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $curlError) {
    if (!$curlError) {
        $debug = "HTTP $httpCode - Response: " . htmlspecialchars(substr($response, 0, 200));
    } else {
        $debug = $curlError;
    }
    echo json_encode(['generated_html' => "<div style='padding:1rem;background:#fee2e2;color:#991b1b;border-radius:8px;'>Error loading article.<br>Debug info: " . $debug . "</div>"]);
    exit();
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? null;

if (!$reply) {
    echo json_encode(['generated_html' => '<p>Could not generate article at this time.</p>']);
    exit();
}

echo json_encode(['generated_html' => $reply]);
?>
