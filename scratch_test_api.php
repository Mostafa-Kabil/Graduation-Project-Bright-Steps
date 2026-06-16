<?php
require_once 'connection.php';
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

$apiKey = getEnvValue('OPENAI_API_KEY');
if (!$apiKey || strpos($apiKey, 'your-key') !== false || strpos($apiKey, 'sk-') !== 0) {
    echo "Key invalid\n";
    exit;
}

$prompt = "You are a child development expert. Reply with {\"status\":\"ok\"}";
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
        'max_tokens' => 4000
    ]),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Curl Error: $curlError\n";
echo "Response: $response\n";
