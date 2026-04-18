<?php
/**
 * Bright Steps – Contact Form API
 * Receives contact form submissions, sends email to admin and confirmation to user.
 */
session_start();
include 'connection.php';
require_once __DIR__ . '/includes/mailer.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

// Validation
$errors = [];
if (empty($name))    $errors[] = 'Name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($subject)) $errors[] = 'Subject is required';
if (empty($message)) $errors[] = 'Message is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(', ', $errors)]);
    exit;
}

// Store in database
try {
    $stmt = $connect->prepare(
        "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $subject, $message]);
} catch (Exception $e) {
    // Table might not exist, continue with email anyway
}

// Send notification to admin
$smtp = getSmtpConfig();
$adminEmail = $smtp['email'] ?: 'admin@brightsteps.com';

$adminContent = '
    <div style="background:#f8fafc;border-radius:12px;padding:1.5rem;margin-bottom:1rem;">
        <p style="color:#475569;margin:0 0 0.5rem;"><strong>From:</strong> ' . htmlspecialchars($name) . '</p>
        <p style="color:#475569;margin:0 0 0.5rem;"><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
        <p style="color:#475569;margin:0 0 0.5rem;"><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;">
        <p style="color:#1e293b;line-height:1.6;margin:0;">' . nl2br(htmlspecialchars($message)) . '</p>
    </div>';

$adminHtml = buildEmailTemplate('New Contact Form Submission', $adminContent, 'This message was sent via the Bright Steps contact form.');
sendMail($adminEmail, "Contact: " . $subject, $adminHtml, 'Bright Steps Admin');

// Send confirmation to user
$userContent = '
    <p style="color:#475569;margin:0 0 1rem;">Hi ' . htmlspecialchars($name) . ',</p>
    <p style="color:#475569;margin:0 0 1rem;">Thank you for reaching out to Bright Steps! We have received your message and our team will get back to you within 24-48 hours.</p>
    <div style="background:#f1f0ff;border-radius:12px;padding:1.25rem;margin:1rem 0;">
        <p style="color:#6C63FF;font-weight:600;margin:0 0 0.5rem;">Your Message</p>
        <p style="color:#475569;font-size:0.9rem;margin:0;"><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
        <p style="color:#475569;font-size:0.9rem;margin:0.25rem 0 0;">' . htmlspecialchars(substr($message, 0, 200)) . (strlen($message) > 200 ? '...' : '') . '</p>
    </div>
    <p style="color:#475569;">In the meantime, feel free to visit our <a href="https://brightsteps.com/help" style="color:#6C63FF;">Help Center</a> for quick answers.</p>';

$userHtml = buildEmailTemplate('We Received Your Message!', $userContent);
sendMail($email, "Bright Steps – We Received Your Message", $userHtml, $name);

echo json_encode([
    'success' => true,
    'message' => 'Your message has been sent successfully! We\'ll get back to you soon.'
]);
