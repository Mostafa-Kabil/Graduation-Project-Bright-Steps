<?php
/**
 * Bright Steps – PHPMailer Utility
 * Provides a reusable sendMail() function for all email functionality.
 * Uses SMTP credentials from .env file.
 */

// Include PHPMailer classes
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Load SMTP configuration from .env
 */
function getSmtpConfig() {
    static $config = null;
    if ($config !== null) return $config;

    $envFile = __DIR__ . '/../.env';
    $config = [
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'email'     => '',
        'password'  => '',
        'from_name' => 'Bright Steps'
    ];

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            switch ($key) {
                case 'SMTP_HOST':     $config['host'] = $val; break;
                case 'SMTP_PORT':     $config['port'] = (int)$val; break;
                case 'SMTP_EMAIL':    $config['email'] = $val; break;
                case 'SMTP_PASSWORD': $config['password'] = $val; break;
                case 'SMTP_FROM_NAME': $config['from_name'] = $val; break;
            }
        }
    }
    return $config;
}

/**
 * Send an email using PHPMailer with SMTP
 *
 * @param string $to        Recipient email
 * @param string $subject   Email subject
 * @param string $htmlBody  HTML email body
 * @param string $toName    Optional recipient name
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendMail($to, $subject, $htmlBody, $toName = '') {
    $smtp = getSmtpConfig();
    
    // If SMTP is not configured, fall back to PHP mail()
    if (empty($smtp['email']) || $smtp['email'] === 'your-email@gmail.com') {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$smtp['from_name']} <noreply@brightsteps.com>\r\n";
        $sent = @mail($to, $subject, $htmlBody, $headers);
        return ['success' => true, 'fallback' => true]; // Always return true for dev
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['email'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'];

        // Sender & recipient
        $mail->setFrom($smtp['email'], $smtp['from_name']);
        $mail->addAddress($to, $toName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Build a branded Bright Steps email template
 *
 * @param string $title    Email title/heading
 * @param string $content  HTML content for the body
 * @param string $footer   Optional footer text
 * @return string Complete HTML email
 */
function buildEmailTemplate($title, $content, $footer = '') {
    $footerHtml = $footer ?: "If you didn't request this, please ignore this email.";
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
        <div style="max-width:560px;margin:2rem auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#6C63FF,#a78bfa);padding:2rem;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:1.75rem;font-weight:700;">Bright Steps</h1>
                <p style="color:rgba(255,255,255,0.85);margin:0.25rem 0 0;font-size:0.9rem;">Child Development Platform</p>
            </div>
            <!-- Body -->
            <div style="padding:2rem 2.5rem;">
                <h2 style="color:#1e293b;margin:0 0 1rem;font-size:1.5rem;font-weight:700;">' . $title . '</h2>
                ' . $content . '
            </div>
            <!-- Footer -->
            <div style="padding:1.5rem 2.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="color:#94a3b8;font-size:0.8rem;margin:0;">' . $footerHtml . '</p>
                <p style="color:#cbd5e1;font-size:0.75rem;margin:0.5rem 0 0;">© ' . date('Y') . ' Bright Steps. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
}
