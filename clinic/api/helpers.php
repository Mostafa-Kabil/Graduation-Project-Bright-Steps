<?php
/**
 * Bright Steps Clinic API — Helper Functions
 * Shared utilities for input handling and response formatting
 */

/**
 * Send a JSON response and exit
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Send a success JSON response
 */
function json_success($data = [], $message = 'Success', $status_code = 200) {
    json_response(array_merge([
        'success' => true,
        'message' => $message
    ], $data), $status_code);
}

/**
 * Send an error JSON response
 */
function json_error($message, $status_code = 400) {
    json_response([
        'success' => false,
        'error' => $message
    ], $status_code);
}

/**
 * Get JSON input from request body
 */
function get_json_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        json_error('Invalid JSON input', 400);
    }
    return $data ?? [];
}

/**
 * Validate that required fields are present in an array
 * @param array $data The input data
 * @param array $fields List of required field names
 * @return true|string Returns true if valid, or the missing field name
 */
function validate_required($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            return $field;
        }
    }
    return true;
}

/**
 * Sanitize a string input
 */
function sanitize_input($input) {
    if (!is_string($input)) return $input;
    $input = trim($input);
    $input = stripslashes($input);
    $input = strip_tags($input);
    return $input;
}

/**
 * Sanitize all string values in an associative array
 */
function sanitize_array($data) {
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = is_string($value) ? sanitize_input($value) : $value;
    }
    return $sanitized;
}

/**
 * Get the request method
 */
function get_method() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get the action parameter from GET or POST
 */
function get_action() {
    return $_GET['action'] ?? '';
}

/**
 * Validate an email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength (min 8 chars)
 */
function validate_password($password) {
    return strlen($password) >= 8;
}

/**
 * Get integer parameter from GET
 */
function get_int($key, $default = 0) {
    return intval($_GET[$key] ?? $default);
}

/**
 * Get string parameter from GET
 */
function get_string($key, $default = '') {
    return sanitize_input($_GET[$key] ?? $default);
}
