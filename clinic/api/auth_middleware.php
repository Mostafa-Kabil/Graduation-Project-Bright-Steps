<?php
/**
 * Bright Steps Clinic API — Authentication Middleware
 * JWT token generation, verification, and role-based access control
 */

/**
 * Base64 URL-safe encoding
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL-safe decoding
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generate a JWT token for a user
 * @param array $user User data (user_id, email, role)
 * @return string JWT token
 */
function generate_token($user) {
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => JWT_ALGORITHM
    ]);

    $payload = json_encode([
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'type' => 'access',
        'iat' => time(),
        'exp' => time() + JWT_EXPIRY
    ]);

    $base64Header = base64url_encode($header);
    $base64Payload = base64url_encode($payload);

    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);
    $base64Signature = base64url_encode($signature);

    return "$base64Header.$base64Payload.$base64Signature";
}

/**
 * Verify and decode a JWT token
 * @param string $token The JWT token
 * @return array|false Decoded payload or false on failure
 */
function verify_token($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($base64Header, $base64Payload, $base64Signature) = $parts;

    // Verify signature
    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);
    $expectedSignature = base64url_encode($signature);

    if (!hash_equals($expectedSignature, $base64Signature)) {
        return false;
    }

    // Decode payload
    $payload = json_decode(base64url_decode($base64Payload), true);
    if (!$payload) {
        return false;
    }

    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    // Check token type
    if (($payload['type'] ?? '') !== 'access') {
        return false;
    }

    return $payload;
}

/**
 * Require authentication — returns the authenticated user payload
 * Sends 401 error and exits if not authenticated
 * @return array User payload from JWT
 */
function require_auth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (empty($authHeader)) {
        // Also check for Apache-style header
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
    }

    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        json_error('Missing or invalid Authorization header. Use: Bearer <token>', 401);
    }

    $token = substr($authHeader, 7);
    $payload = verify_token($token);

    if (!$payload) {
        json_error('Invalid or expired token. Please login again.', 401);
    }

    return $payload;
}

/**
 * Require a specific role — must be called after require_auth()
 * @param array $user The authenticated user payload
 * @param string|array $roles Allowed role(s)
 */
function require_role($user, $roles) {
    if (is_string($roles)) {
        $roles = [$roles];
    }

    if (!in_array($user['role'], $roles)) {
        json_error('Access denied. Required role: ' . implode(' or ', $roles), 403);
    }
}

/**
 * Optional authentication — returns user payload or null
 * Does not exit on failure, just returns null
 * @return array|null
 */
function optional_auth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (empty($authHeader)) {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
    }

    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }

    $token = substr($authHeader, 7);
    return verify_token($token) ?: null;
}
