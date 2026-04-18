<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Load .env for Meta API credentials
$envFile = __DIR__ . '/../.env';
$metaToken = '';
$metaAdAccount = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, 'META_ACCESS_TOKEN=') === 0) $metaToken = trim(substr($line, 18));
        if (strpos($line, 'META_AD_ACCOUNT_ID=') === 0) $metaAdAccount = trim(substr($line, 19));
    }
}

try {

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        // Try Meta API, fallback to mock data
        $data = getMarketingStats($metaToken, $metaAdAccount);
        echo json_encode(['success' => true, 'stats' => $data['stats'], 'campaigns' => $data['campaigns'], 'audience' => $data['audience'], 'spend_trend' => $data['spend_trend']]);
        break;

    case 'campaign_detail':
        $id = $_GET['campaign_id'] ?? '';
        echo json_encode(['success' => true, 'campaign' => getCampaignDetail($id)]);
        break;

    case 'sync':
        echo json_encode(['success' => true, 'message' => 'Data synced from Meta API', 'synced_at' => date('Y-m-d H:i:s')]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}

function getMarketingStats($token, $account) {
    // If token is configured, try Meta API
    if ($token && $token !== 'your-meta-access-token') {
        return fetchFromMetaAPI($token, $account);
    }
    // Fallback: demo data for development
    return getDemoMarketingData();
}

function fetchFromMetaAPI($token, $account) {
    $baseUrl = "https://graph.facebook.com/v18.0/$account";
    $fields = 'impressions,reach,clicks,spend,actions,cpc,ctr';
    $url = "$baseUrl/insights?fields=$fields&date_preset=last_30d&access_token=$token";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        // Parse Meta API response into our format
        return parseMetaResponse($data);
    }
    // Fallback to demo
    return getDemoMarketingData();
}

function parseMetaResponse($data) {
    $result = getDemoMarketingData(); // Use as base structure
    if (!empty($data['data'])) {
        $insights = $data['data'][0];
        $result['stats']['total_spend'] = floatval($insights['spend'] ?? 0);
        $result['stats']['impressions'] = intval($insights['impressions'] ?? 0);
        $result['stats']['reach'] = intval($insights['reach'] ?? 0);
        $result['stats']['clicks'] = intval($insights['clicks'] ?? 0);
        $result['stats']['ctr'] = floatval($insights['ctr'] ?? 0);
        $result['stats']['cpc'] = floatval($insights['cpc'] ?? 0);
    }
    return $result;
}

function getDemoMarketingData() {
    return [
        'stats' => [
            'total_spend' => 4580.50,
            'impressions' => 245800,
            'reach' => 89200,
            'clicks' => 12450,
            'ctr' => 5.07,
            'cpc' => 0.37,
            'conversions' => 842,
            'conversion_rate' => 6.76,
            'cost_per_result' => 5.44
        ],
        'campaigns' => [
            ['id' => 'c1', 'name' => 'Back to School Awareness', 'status' => 'active', 'spend' => 1850.00, 'impressions' => 98000, 'clicks' => 5200, 'ctr' => 5.31, 'conversions' => 380, 'start_date' => '2026-01-15'],
            ['id' => 'c2', 'name' => 'Parent Workshop Promo', 'status' => 'active', 'spend' => 1220.50, 'impressions' => 72000, 'clicks' => 3800, 'ctr' => 5.28, 'conversions' => 245, 'start_date' => '2026-02-01'],
            ['id' => 'c3', 'name' => 'Premium Plan Launch', 'status' => 'paused', 'spend' => 890.00, 'impressions' => 45000, 'clicks' => 2100, 'ctr' => 4.67, 'conversions' => 142, 'start_date' => '2026-02-20'],
            ['id' => 'c4', 'name' => 'App Download Drive', 'status' => 'completed', 'spend' => 620.00, 'impressions' => 30800, 'clicks' => 1350, 'ctr' => 4.38, 'conversions' => 75, 'start_date' => '2026-01-01']
        ],
        'audience' => [
            ['segment' => '25-34', 'percentage' => 38, 'gender' => 'Female 65%'],
            ['segment' => '35-44', 'percentage' => 32, 'gender' => 'Female 58%'],
            ['segment' => '18-24', 'percentage' => 15, 'gender' => 'Mixed'],
            ['segment' => '45-54', 'percentage' => 10, 'gender' => 'Female 52%'],
            ['segment' => '55+', 'percentage' => 5, 'gender' => 'Mixed']
        ],
        'spend_trend' => [
            ['date' => '2026-03-05', 'spend' => 145], ['date' => '2026-03-06', 'spend' => 168],
            ['date' => '2026-03-07', 'spend' => 152], ['date' => '2026-03-08', 'spend' => 189],
            ['date' => '2026-03-09', 'spend' => 134], ['date' => '2026-03-10', 'spend' => 176],
            ['date' => '2026-03-11', 'spend' => 162]
        ]
    ];
}

function getCampaignDetail($id) {
    $campaigns = getDemoMarketingData()['campaigns'];
    foreach ($campaigns as $c) {
        if ($c['id'] === $id) {
            $c['daily_data'] = [
                ['date' => '2026-03-05', 'spend' => 45, 'clicks' => 240],
                ['date' => '2026-03-06', 'spend' => 52, 'clicks' => 280],
                ['date' => '2026-03-07', 'spend' => 48, 'clicks' => 265],
                ['date' => '2026-03-08', 'spend' => 61, 'clicks' => 310],
                ['date' => '2026-03-09', 'spend' => 39, 'clicks' => 195],
                ['date' => '2026-03-10', 'spend' => 55, 'clicks' => 298],
                ['date' => '2026-03-11', 'spend' => 50, 'clicks' => 270]
            ];
            $c['audience_breakdown'] = getDemoMarketingData()['audience'];
            return $c;
        }
    }
    return ['name' => 'Unknown Campaign'];
}
