<?php
/**
 * Bright Steps – Email Verification API
 * Generates a 6-digit code, stores it in the session, and sends it via PHP mail().
 * Works within XAMPP without Python.
 */
session_start();
include 'connection.php';
require_once __DIR__ . '/includes/mailer.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Send verification code ─────────────────────────────────────
    case 'send':
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address']);
            exit();
        }

        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['verify_code'] = $code;
        $_SESSION['verify_email'] = $email;
        $_SESSION['verify_expiry'] = time() + 600; // 10 minutes

        // Build branded HTML email
        $subject = "Bright Steps – Verify Your Email";
        $content = '
            <p style="color:#475569;margin:0 0 1.5rem;">Enter this code to complete your registration:</p>
            <div style="text-align:center;margin:1.5rem 0;">
                <div style="font-size:2.5rem;font-weight:800;letter-spacing:0.5rem;color:#6C63FF;background:#f1f0ff;border-radius:12px;padding:1rem;display:inline-block;">
                    ' . $code . '
                </div>
            </div>
            <p style="color:#94a3b8;font-size:0.875rem;">This code expires in 10 minutes.</p>';
        
        $htmlBody = buildEmailTemplate('Verify Your Email', $content);
        $result = sendMail($email, $subject, $htmlBody);

        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to ' . $email,
            'dev_code' => $code
        ]);
        break;

    // ── Verify code ────────────────────────────────────────────────
    case 'verify':
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $email = $input['email'] ?? '';

        if (!isset($_SESSION['verify_code'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No verification pending. Request a new code.']);
            exit();
        }

        if (time() > $_SESSION['verify_expiry']) {
            unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_expiry']);
            http_response_code(400);
            echo json_encode(['error' => 'Code expired. Request a new one.']);
            exit();
        }

        if ($_SESSION['verify_code'] !== $code || $_SESSION['verify_email'] !== $email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid verification code.']);
            exit();
        }

        // Mark email as verified in session
        $_SESSION['email_verified'] = true;
        unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_expiry']);

        echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
        break;

    // ── Forgot password – send reset code ──────────────────────────
    case 'forgot':
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email']);
            exit();
        }

        // Check if user exists
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Don't reveal if email exists
            echo json_encode(['success' => true, 'message' => 'If that email exists, a reset code has been sent.']);
            exit();
        }

        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', time() + 600);

        // Store in password_reset_tokens table
        $stmt = $connect->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$user['user_id'], password_hash($code, PASSWORD_DEFAULT), $expiry]);

        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_expiry'] = time() + 600;

        $content = '
            <p style="color:#475569;">Enter this code on the reset page:</p>
            <div style="text-align:center;margin:1.5rem 0;">
                <div style="font-size:2.5rem;font-weight:800;letter-spacing:0.5rem;color:#ef4444;background:#fef2f2;border-radius:12px;padding:1rem;display:inline-block;">
                    ' . $code . '
                </div>
            </div>
            <p style="color:#94a3b8;font-size:0.875rem;">This code expires in 10 minutes.</p>';
        
        $htmlBody = buildEmailTemplate('Reset Your Password', $content);
        sendMail($email, $subject, $htmlBody);

        echo json_encode([
            'success' => true,
            'message' => 'If that email exists, a reset code has been sent.',
            'dev_code' => $code
        ]);
        break;

    // ── Verify reset code & change password ────────────────────────
    case 'reset':
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $code = $input['code'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$code || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email, code, and new password are required.']);
            exit();
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters.']);
            exit();
        }

        // Verify via session
        if (!isset($_SESSION['reset_code']) || $_SESSION['reset_email'] !== $email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid reset session. Request a new code.']);
            exit();
        }

        if (time() > $_SESSION['reset_expiry']) {
            http_response_code(400);
            echo json_encode(['error' => 'Code expired.']);
            exit();
        }

        if ($_SESSION['reset_code'] !== $code) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid code.']);
            exit();
        }

        // Update password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $email]);

        unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_expiry']);

        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
        break;

    // ── Send change password verification code ─────────────────────
    case 'send-change-pw-code':
        if (!isset($_SESSION['id']) || !isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit();
        }
        $email = $_SESSION['email'];
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['verify_code'] = $code;
        $_SESSION['verify_email'] = $email;
        $_SESSION['verify_expiry'] = time() + 600;

        $subject = "Bright Steps – Password Change Verification";
        $content = '
            <p style="color:#475569;">Enter this code to change your password:</p>
            <div style="text-align:center;margin:1.5rem 0;">
                <div style="font-size:2.5rem;font-weight:800;letter-spacing:0.5rem;color:#6C63FF;background:#f1f0ff;border-radius:12px;padding:1rem;display:inline-block;">
                    ' . $code . '
                </div>
            </div>
            <p style="color:#94a3b8;font-size:0.875rem;">This code expires in 10 minutes.</p>';
        
        $htmlBody = buildEmailTemplate('Change Password', $content);
        sendMail($email, $subject, $htmlBody);
        
        echo json_encode(['success' => true, 'message' => 'Verification code sent.', 'dev_code' => $code]);
        break;

    // ── Verify code & Change password (authenticated) ───────────────
    case 'change-password-verify':
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $currentPwd = $input['current_password'] ?? '';
        $newPwd = $input['new_password'] ?? '';

        if (!$code || !$currentPwd || !$newPwd) {
            http_response_code(400);
            echo json_encode(['error' => 'Code, current password, and new password are required.']);
            exit();
        }

        if (!isset($_SESSION['verify_code']) || $_SESSION['verify_email'] !== $_SESSION['email']) {
            http_response_code(400);
            echo json_encode(['error' => 'No verification pending.']);
            exit();
        }

        if (time() > $_SESSION['verify_expiry']) {
            http_response_code(400);
            echo json_encode(['error' => 'Code expired. Request a new one.']);
            exit();
        }

        if ($_SESSION['verify_code'] !== $code) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid verification code.']);
            exit();
        }

        if (strlen($newPwd) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 8 characters.']);
            exit();
        }

        $stmt = $connect->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPwd, $user['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is incorrect.']);
            exit();
        }

        $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $_SESSION['id']]);

        unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_expiry']);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        break;

    // ── Change password (authenticated) ────────────────────────────
    case 'change-password':
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $currentPwd = $input['current_password'] ?? '';
        $newPwd = $input['new_password'] ?? '';

        if (!$currentPwd || !$newPwd) {
            http_response_code(400);
            echo json_encode(['error' => 'Both current and new passwords are required.']);
            exit();
        }

        if (strlen($newPwd) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 8 characters.']);
            exit();
        }

        $stmt = $connect->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPwd, $user['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is incorrect.']);
            exit();
        }

        $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $_SESSION['id']]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: send, verify, forgot, reset, change-password, send-change-pw-code, change-password-verify']);
        break;
}
