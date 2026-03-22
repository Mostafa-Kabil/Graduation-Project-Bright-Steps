<?php
/**
 * Bright Steps – Newsletter API
 * AI-powered newsletter with personalized articles using OpenAI.
 * Actions: subscribe, unsubscribe, generate, send, list
 */
session_start();
include 'connection.php';
require_once __DIR__ . '/includes/mailer.php';
header('Content-Type: application/json');

// Load OpenAI key
$envFile = __DIR__ . '/.env';
$openaiKey = '';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === 'OPENAI_API_KEY') {
            $openaiKey = trim($parts[1]);
        }
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── Subscribe to newsletter ─────────────────────────────────────
    case 'subscribe':
        $email = trim($input['email'] ?? '');
        $userId = $_SESSION['id'] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid email is required']);
            exit;
        }

        try {
            // Check existing
            $stmt = $connect->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Re-subscribe
                $stmt = $connect->prepare("UPDATE newsletter_subscribers SET subscribed = 1, user_id = COALESCE(?, user_id) WHERE email = ?");
                $stmt->execute([$userId, $email]);
            } else {
                $stmt = $connect->prepare("INSERT INTO newsletter_subscribers (user_id, email, subscribed) VALUES (?, ?, 1)");
                $stmt->execute([$userId, $email]);
            }

            // Send welcome email
            $content = '
                <p style="color:#475569;margin:0 0 1rem;">Welcome to the Bright Steps Newsletter! 🎉</p>
                <p style="color:#475569;margin:0 0 1rem;">You\'ll receive personalized articles, parenting tips, and activity recommendations tailored to your child\'s development stage.</p>
                <div style="background:#f1f0ff;border-radius:12px;padding:1.25rem;margin:1rem 0;text-align:center;">
                    <p style="color:#6C63FF;font-weight:600;font-size:1.1rem;margin:0;">What to Expect</p>
                    <p style="color:#475569;font-size:0.9rem;margin:0.5rem 0 0;">📚 Curated articles based on your child\'s age<br>🎯 Activity recommendations from AI<br>🏆 Tips to boost your child\'s milestones</p>
                </div>';
            $html = buildEmailTemplate('Welcome to Bright Steps Newsletter!', $content);
            sendMail($email, 'Welcome to Bright Steps Newsletter!', $html);

            echo json_encode(['success' => true, 'message' => 'Successfully subscribed to the newsletter!']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to subscribe: ' . $e->getMessage()]);
        }
        break;

    // ── Unsubscribe ─────────────────────────────────────────────────
    case 'unsubscribe':
        $email = trim($input['email'] ?? $_GET['email'] ?? '');
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is required']);
            exit;
        }

        try {
            $stmt = $connect->prepare("UPDATE newsletter_subscribers SET subscribed = 0 WHERE email = ?");
            $stmt->execute([$email]);
            echo json_encode(['success' => true, 'message' => 'Successfully unsubscribed.']);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'message' => 'Unsubscribed.']);
        }
        break;

    // ── Generate AI newsletter content for a user ────────────────────
    case 'generate':
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $parentId = $_SESSION['id'];

        // Get child data
        $stmt = $connect->prepare(
            "SELECT c.first_name, c.birth_day, c.birth_month, c.birth_year, c.gender
             FROM child c WHERE c.parent_id = ? ORDER BY c.child_id ASC LIMIT 3"
        );
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $childContext = '';
        foreach ($children as $ch) {
            $bd = mktime(0, 0, 0, $ch['birth_month'], $ch['birth_day'], $ch['birth_year']);
            $ageMonths = floor((time() - $bd) / (30.44 * 86400));
            $ageStr = $ageMonths >= 24 ? floor($ageMonths / 12) . ' years' : $ageMonths . ' months';
            $childContext .= "{$ch['first_name']} ({$ch['gender']}, {$ageStr} old). ";
        }

        if (empty($childContext)) {
            $childContext = 'No child profile added yet. Generate general parenting tips for children ages 0-5.';
        }

        if (empty($openaiKey)) {
            echo json_encode(['error' => 'OpenAI API key not configured']);
            exit;
        }

        // Call OpenAI
        $prompt = "You are a child development expert creating a newsletter for a parent. Their children: {$childContext}

Generate a personalized newsletter with:
1. A warm greeting and headline
2. 3 recommended articles (title, 2-sentence summary, link placeholder)
3. 2 real-life activities appropriate for the child's age
4. 1 developmental tip or milestone to watch for
5. An encouraging closing message

Format as JSON with keys: greeting, articles (array of {title, summary}), activities (array of {title, description}), tip, closing";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $openaiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'response_format' => ['type' => 'json_object']
            ])
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['error' => 'AI service unavailable', 'details' => $response]);
            exit;
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $newsletter = json_decode($content, true);

        // Store in history
        try {
            $stmt = $connect->prepare(
                "INSERT INTO newsletter_history (user_id, subject, content) VALUES (?, ?, ?)"
            );
            $stmt->execute([$parentId, $newsletter['greeting'] ?? 'Bright Steps Newsletter', $content]);
        } catch (Exception $e) { /* table might not exist */ }

        echo json_encode(['success' => true, 'newsletter' => $newsletter]);
        break;

    // ── Send newsletter to a user ────────────────────────────────────
    case 'send':
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $newsletterData = $input['newsletter'] ?? null;
        $email = $_SESSION['email'] ?? '';

        if (!$newsletterData || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Newsletter data and email required']);
            exit;
        }

        // Build HTML newsletter
        $articlesHtml = '';
        foreach (($newsletterData['articles'] ?? []) as $article) {
            $articlesHtml .= '
                <div style="background:#f8fafc;border-radius:12px;padding:1.25rem;margin-bottom:0.75rem;border-left:4px solid #6C63FF;">
                    <h3 style="color:#1e293b;margin:0 0 0.25rem;font-size:1rem;">' . htmlspecialchars($article['title'] ?? '') . '</h3>
                    <p style="color:#64748b;margin:0;font-size:0.875rem;">' . htmlspecialchars($article['summary'] ?? '') . '</p>
                </div>';
        }

        $activitiesHtml = '';
        foreach (($newsletterData['activities'] ?? []) as $activity) {
            $activitiesHtml .= '
                <div style="background:#f0fdf4;border-radius:12px;padding:1.25rem;margin-bottom:0.75rem;border-left:4px solid #22c55e;">
                    <h3 style="color:#166534;margin:0 0 0.25rem;font-size:1rem;">🎯 ' . htmlspecialchars($activity['title'] ?? '') . '</h3>
                    <p style="color:#475569;margin:0;font-size:0.875rem;">' . htmlspecialchars($activity['description'] ?? '') . '</p>
                </div>';
        }

        $emailContent = '
            <p style="color:#475569;margin:0 0 1.5rem;">' . htmlspecialchars($newsletterData['greeting'] ?? 'Here\'s your personalized newsletter!') . '</p>
            <h3 style="color:#1e293b;margin:0 0 1rem;">📚 Recommended Articles</h3>
            ' . $articlesHtml . '
            <h3 style="color:#1e293b;margin:1.5rem 0 1rem;">🎯 Activities For Your Child</h3>
            ' . $activitiesHtml . '
            <div style="background:#fef3c7;border-radius:12px;padding:1.25rem;margin:1.5rem 0;">
                <h3 style="color:#92400e;margin:0 0 0.5rem;font-size:1rem;">💡 Development Tip</h3>
                <p style="color:#78350f;margin:0;font-size:0.875rem;">' . htmlspecialchars($newsletterData['tip'] ?? '') . '</p>
            </div>
            <p style="color:#475569;font-style:italic;">' . htmlspecialchars($newsletterData['closing'] ?? '') . '</p>';

        $html = buildEmailTemplate('Your Bright Steps Newsletter', $emailContent, 
            '<a href="mailto:?subject=Unsubscribe" style="color:#6C63FF;">Unsubscribe</a> from this newsletter.');
        $result = sendMail($email, 'Bright Steps – Your Personalized Newsletter', $html, $_SESSION['fname'] ?? '');

        echo json_encode(['success' => true, 'message' => 'Newsletter sent to your email!']);
        break;

    // ── List newsletter history ──────────────────────────────────────
    case 'list':
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        try {
            $stmt = $connect->prepare(
                "SELECT id, subject, content, sent_at FROM newsletter_history WHERE user_id = ? ORDER BY sent_at DESC LIMIT 10"
            );
            $stmt->execute([$_SESSION['id']]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse JSON content
            foreach ($history as &$h) {
                $h['content'] = json_decode($h['content'], true);
            }

            echo json_encode(['success' => true, 'newsletters' => $history]);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'newsletters' => []]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: subscribe, unsubscribe, generate, send, list']);
        break;
}
