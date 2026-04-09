<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// --- 1. Basic pseudo-cron lock using platform_settings ---
$today = date('Y-m-d');
$stmt = $connect->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'last_daily_digest'");
$stmt->execute();
$lastRun = $stmt->fetchColumn();

if ($lastRun === $today) {
    // Already ran today
    echo json_encode(['success' => true, 'message' => 'Daily digest already ran today.']);
    exit();
}

// Update to lock it instantly
if ($lastRun === false) {
    $stmt = $connect->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES ('last_daily_digest', ?)");
    $stmt->execute([$today]);
} else {
    $stmt = $connect->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = 'last_daily_digest'");
    $stmt->execute([$today]);
}

// Helper to parse .env file
function getEnvValue($key, $default = '') {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) return $default;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($k, $v) = explode('=', $line, 2);
        if (trim($k) === $key) return trim(trim($v), '"\'');
    }
    return $default;
}

$apiKey = getEnvValue('OPENAI_API_KEY');
if (!$apiKey || strpos($apiKey, 'your-key') !== false) {
    echo json_encode(['success' => false, 'error' => 'OpenAI API key not configured']);
    exit();
}

// --- 2. Process active parents & children ---
$stmt = $connect->prepare("
    SELECT p.parent_id, p.user_id, p.first_name as p_name, u.email, 
           c.child_id, c.first_name as c_name, c.date_of_birth, c.gender, c.health_condition
    FROM parent p
    JOIN users u ON p.user_id = u.id
    JOIN child c ON p.parent_id = c.parent_id
");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group children by parent
$parents = [];
foreach ($records as $r) {
    $pid = $r['parent_id'];
    if (!isset($parents[$pid])) {
        $parents[$pid] = [
            'user_id' => $r['user_id'],
            'email' => $r['email'],
            'name' => $r['p_name'],
            'children' => []
        ];
    }
    
    // Calculate age in months
    $dob = new DateTime($r['date_of_birth']);
    $now = new DateTime();
    $interval = $now->diff($dob);
    $ageMonths = ($interval->y * 12) + $interval->m;
    
    $parents[$pid]['children'][] = [
        'name' => $r['c_name'],
        'age_months' => $ageMonths,
        'gender' => $r['gender'],
        'condition' => ltrim($r['health_condition'] ?? 'None', ', ')
    ];
}

$notifiedCount = 0;

// Iterate each parent and formulate the localized prompt
foreach ($parents as $pid => $parentData) {
    $context = "";
    foreach ($parentData['children'] as $idx => $c) {
        $ageDisplay = $c['age_months'] >= 24 ? floor($c['age_months'] / 12) . ' years old' : $c['age_months'] . ' months old';
        $cond = empty($c['condition']) ? 'No specific condition' : $c['condition'];
        $context .= "- Child ".($idx+1).": {$c['name']}, {$ageDisplay}, Gender: {$c['gender']}, Condition: {$cond}.\n";
    }

    $prompt = "You are a child development expert for Bright Steps sending a daily system alert. Based on the following children profiles for this parent, provide a short personalized Daily Tip and exactly one recommended engaging Article title.

Children Profiles:
$context

Return EXACTLY this JSON structure:
{
  \"daily_tip\": \"A single, encouraging, actionable daily developmental tip customized to the children's exact conditions and age.\",
  \"article_title\": \"The exact title of a helpful parenting article relating to them.\",
  \"notification_message\": \"A very short 1-sentence notification message suitable for a phone alert summarizing the tip.\"
}";

    // Call OpenAI
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'You output strictly raw JSON.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            // Strip markdown block if present
            $content = preg_replace('/^```json\s*|\s*```$/i', '', $content);
            $aiData = json_decode($content, true);

            if ($aiData && isset($aiData['daily_tip'])) {
                // 1. Insert into notifications
                $nstmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)");
                $nstmt->execute([
                    $parentData['user_id'], 
                    '💡 Your Daily Insight is here!', 
                    $aiData['notification_message']
                ]);

                // 2. Send email utilizing PHP's native mail() hook
                $to = $parentData['email'];
                $subject = "Your Daily Bright Steps Digest - Customized for " . $parentData['children'][0]['name'];
                $message = "Hello {$parentData['name']},\n\n";
                $message .= "Here is your Bright Steps personalized daily tip for today:\n";
                $message .= $aiData['daily_tip'] . "\n\n";
                $message .= "Recommended Article for today: " . $aiData['article_title'] . "\n\n";
                $message .= "Log into your dashboard to read more and track your progress!\n";
                
                $headers = "From: noreply@brightsteps.com\r\n";
                @mail($to, $subject, $message, $headers);
                
                $notifiedCount++;
            }
        }
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Daily digests processed successfully for $notifiedCount parents."
]);

// ──────────────────── WEEKLY DIGEST (Mondays) ────────────────────
$dayOfWeek = date('N'); // 1=Monday
$stmtWk = $connect->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'last_weekly_digest'");
$stmtWk->execute();
$lastWeekly = $stmtWk->fetchColumn();
$thisWeekId = date('Y-W');

if ($dayOfWeek == 1 && $lastWeekly !== $thisWeekId) {
    // Lock
    if ($lastWeekly === false) {
        $connect->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES ('last_weekly_digest', ?)")->execute([$thisWeekId]);
    } else {
        $connect->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = 'last_weekly_digest'")->execute([$thisWeekId]);
    }

    foreach ($parents as $pid => $parentData) {
        $weekSummaryParts = [];
        foreach ($parentData['children'] as $c) {
            // Get child_id
            $stmtCI = $connect->prepare("SELECT child_id FROM child WHERE first_name = ? AND parent_id = ?");
            $stmtCI->execute([$c['name'], $pid]);
            $cid = $stmtCI->fetchColumn();
            if (!$cid) continue;

            // Count activities this week
            $stmtAW = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $stmtAW->execute([$cid]);
            $actCount = $stmtAW->fetchColumn();

            // Count milestones this week
            $stmtMW = $connect->prepare("SELECT COUNT(*) FROM child_milestones WHERE child_id = ? AND is_achieved = 1 AND achieved_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $stmtMW->execute([$cid]);
            $mileCount = $stmtMW->fetchColumn();

            $weekSummaryParts[] = "{$c['name']}: {$actCount} activities completed, {$mileCount} milestones achieved this week.";
        }
        $weekSummary = implode(' ', $weekSummaryParts);

        // Generate weekly article via OpenAI
        $weekPrompt = "You are a child development expert. Based on this family's weekly progress, write a brief encouraging weekly summary notification and suggest one specific activity for next week.

Weekly data: $weekSummary

Children profiles:
" . implode("\n", array_map(function($c) { return "- {$c['name']}: {$c['age_months']} months, Condition: " . ($c['condition'] ?: 'None'); }, $parentData['children'])) . "

Return JSON: {\"weekly_summary\": \"...\", \"next_week_tip\": \"...\", \"recommended_article\": \"article title\"}";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'system', 'content' => 'Output strictly raw JSON.'], ['role' => 'user', 'content' => $weekPrompt]],
                'temperature' => 0.7
            ])
        ]);
        $wRes = curl_exec($ch); $wCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);

        if ($wCode === 200) {
            $wData = json_decode($wRes, true);
            $wContent = trim($wData['choices'][0]['message']['content'] ?? '');
            $wContent = preg_replace('/^```json\s*|\s*```$/i', '', $wContent);
            $wAI = json_decode($wContent, true);

            if ($wAI && isset($wAI['weekly_summary'])) {
                $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)")
                    ->execute([$parentData['user_id'], '📊 Your Weekly Progress Report', $wAI['weekly_summary'] . "\n\n💡 Next week tip: " . ($wAI['next_week_tip'] ?? '')]);

                $wSubject = "Your Bright Steps Weekly Report - Week of " . date('M d');
                $wBody = "Hello {$parentData['name']},\n\n{$wAI['weekly_summary']}\n\nTip for next week: " . ($wAI['next_week_tip'] ?? '') . "\n\nRecommended reading: " . ($wAI['recommended_article'] ?? '') . "\n\nKeep up the great work!\n";
                @mail($parentData['email'], $wSubject, $wBody, "From: noreply@brightsteps.com\r\n");
            }
        }
    }
}

// ──────────────────── MONTHLY DIGEST (1st of month) ────────────────────
$dayOfMonth = date('j');
$stmtMn = $connect->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'last_monthly_digest'");
$stmtMn->execute();
$lastMonthly = $stmtMn->fetchColumn();
$thisMonthId = date('Y-m');

if ($dayOfMonth == 1 && $lastMonthly !== $thisMonthId) {
    if ($lastMonthly === false) {
        $connect->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES ('last_monthly_digest', ?)")->execute([$thisMonthId]);
    } else {
        $connect->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = 'last_monthly_digest'")->execute([$thisMonthId]);
    }

    foreach ($parents as $pid => $parentData) {
        $monthParts = [];
        foreach ($parentData['children'] as $c) {
            $stmtCI2 = $connect->prepare("SELECT child_id FROM child WHERE first_name = ? AND parent_id = ?");
            $stmtCI2->execute([$c['name'], $pid]);
            $cid2 = $stmtCI2->fetchColumn();
            if (!$cid2) continue;

            $stmtAM = $connect->prepare("SELECT COUNT(*) FROM child_activities WHERE child_id = ? AND is_completed = 1 AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmtAM->execute([$cid2]);
            $mActCount = $stmtAM->fetchColumn();

            $stmtMM = $connect->prepare("SELECT COUNT(*) FROM child_milestones WHERE child_id = ? AND is_achieved = 1 AND achieved_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmtMM->execute([$cid2]);
            $mMileCount = $stmtMM->fetchColumn();

            // Motor progress
            $stmtMP = $connect->prepare("SELECT COUNT(*) FROM child_milestones cm JOIN milestones m ON cm.milestone_id = m.milestone_id WHERE cm.child_id = ? AND m.category IN ('gross_motor','fine_motor') AND cm.is_achieved = 1");
            $stmtMP->execute([$cid2]);
            $motorAchieved = $stmtMP->fetchColumn();

            $monthParts[] = "{$c['name']} ({$c['age_months']}mo): {$mActCount} activities, {$mMileCount} new milestones, {$motorAchieved} motor milestones total.";
        }

        $monthPrompt = "You are a child development expert writing a comprehensive monthly progress report for a parent. Be warm, encouraging, and specific.

Monthly data: " . implode(' ', $monthParts) . "

Children profiles:
" . implode("\n", array_map(function($c) { return "- {$c['name']}: {$c['age_months']} months, Gender: {$c['gender']}, Condition: " . ($c['condition'] ?: 'None'); }, $parentData['children'])) . "

Return JSON: {\"monthly_report\": \"A 2-3 sentence comprehensive monthly progress summary\", \"improvement_areas\": \"Key areas to focus on next month\", \"motivation\": \"A short motivational message\"}";

        $ch2 = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'system', 'content' => 'Output strictly raw JSON.'], ['role' => 'user', 'content' => $monthPrompt]],
                'temperature' => 0.7
            ])
        ]);
        $mRes = curl_exec($ch2); $mCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE); curl_close($ch2);

        if ($mCode === 200) {
            $mData = json_decode($mRes, true);
            $mContent = trim($mData['choices'][0]['message']['content'] ?? '');
            $mContent = preg_replace('/^```json\s*|\s*```$/i', '', $mContent);
            $mAI = json_decode($mContent, true);

            if ($mAI && isset($mAI['monthly_report'])) {
                $fullNotif = $mAI['monthly_report'] . "\n\n📋 Focus areas: " . ($mAI['improvement_areas'] ?? '') . "\n\n" . ($mAI['motivation'] ?? '');
                $connect->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', ?, ?)")
                    ->execute([$parentData['user_id'], '📅 Monthly Progress Report - ' . date('F Y'), $fullNotif]);

                $mSubject = "Your Bright Steps Monthly Report - " . date('F Y');
                $mBody = "Hello {$parentData['name']},\n\n{$mAI['monthly_report']}\n\nFocus for next month: " . ($mAI['improvement_areas'] ?? '') . "\n\n{$mAI['motivation']}\n\nView your full dashboard for detailed charts and insights!\n";
                @mail($parentData['email'], $mSubject, $mBody, "From: noreply@brightsteps.com\r\n");
            }
        }
    }
}
?>
