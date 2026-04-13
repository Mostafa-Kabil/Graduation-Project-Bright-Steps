<?php
/**
 * Bright Steps Clinic API — Parent Authentication
 * 
 * Endpoints:
 *   POST ?action=register   — Register a new parent account
 *   POST ?action=login      — Login and receive JWT token
 *   GET  ?action=profile    — Get authenticated parent's profile
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── Register ──────────────────────────────────────────
if ($action === 'register' && $method === 'POST') {
    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['first_name', 'last_name', 'email', 'password']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    if (!validate_email($input['email'])) {
        json_error('Invalid email address');
    }

    if (!validate_password($input['password'])) {
        json_error('Password must be at least 8 characters');
    }

    $db = get_db();

    // Check if email already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute([':email' => $input['email']]);
    if ($stmt->fetch()) {
        json_error('Email already registered', 409);
    }

    try {
        $db->beginTransaction();

        // Create user
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, status)
            VALUES (:first_name, :last_name, :email, :password, 'parent', 'active')
        ");
        $stmt->execute([
            ':first_name' => $input['first_name'],
            ':last_name'  => $input['last_name'],
            ':email'      => $input['email'],
            ':password'   => $hashedPassword
        ]);
        $userId = $db->lastInsertId();

        // Create parent record
        $stmt = $db->prepare("INSERT INTO parent (parent_id) VALUES (:parent_id)");
        $stmt->execute([':parent_id' => $userId]);

        $db->commit();

        // Generate token
        $token = generate_token([
            'user_id' => (int)$userId,
            'email'   => $input['email'],
            'role'    => 'parent'
        ]);

        json_success([
            'user_id' => (int)$userId,
            'token'   => $token
        ], 'Registration successful', 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_error('Registration failed. Please try again.', 500);
    }
}

// ── Login ─────────────────────────────────────────────
elseif ($action === 'login' && $method === 'POST') {
    $input = get_json_input();
    $input = sanitize_array($input);

    $missing = validate_required($input, ['email', 'password']);
    if ($missing !== true) {
        json_error("Field '$missing' is required");
    }

    $db = get_db();

    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.password, u.role, u.status
        FROM users u
        INNER JOIN parent p ON u.user_id = p.parent_id
        WHERE u.email = :email AND u.role = 'parent'
    ");
    $stmt->execute([':email' => $input['email']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input['password'], $user['password'])) {
        json_error('Invalid email or password', 401);
    }

    if ($user['status'] !== 'active') {
        json_error('Account is not active. Please contact support.', 403);
    }

    $token = generate_token([
        'user_id' => (int)$user['user_id'],
        'email'   => $user['email'],
        'role'    => $user['role']
    ]);

    json_success([
        'user_id'    => (int)$user['user_id'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'token'      => $token
    ], 'Login successful');
}

// ── Profile ───────────────────────────────────────────
elseif ($action === 'profile' && $method === 'GET') {
    $authUser = require_auth();
    require_role($authUser, 'parent');

    $db = get_db();

    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.status, u.created_at,
               p.number_of_children
        FROM users u
        INNER JOIN parent p ON u.user_id = p.parent_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $authUser['user_id']]);
    $profile = $stmt->fetch();

    if (!$profile) {
        json_error('Profile not found', 404);
    }

    json_success(['profile' => $profile]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'POST ?action=register' => 'Register a new parent account',
            'POST ?action=login'    => 'Login and receive JWT token',
            'GET  ?action=profile'  => 'Get authenticated parent profile'
        ]
    ], 'Bright Steps Clinic — Parent Auth API');
}
