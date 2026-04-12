<?php
/**
 * Bright Steps Clinic API — Parent Notifications
 * 
 * Endpoints:
 *   GET  ?action=list   — Get parent's notifications
 *   PUT  ?action=read   — Mark notification(s) as read
 *   GET  ?action=count  — Get unread notification count
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth_middleware.php';

$action = get_action();
$method = get_method();

// ── List Notifications ────────────────────────────────
if ($action === 'list' && $method === 'GET') {
    $authUser = require_auth();

    $db = get_db();
    $unreadOnly = get_string('unread_only') === '1';
    $limit = get_int('limit', 50);

    $sql = "
        SELECT notification_id, title, message, type, reference_id, is_read, created_at
        FROM notifications
        WHERE user_id = :user_id
    ";
    $params = [':user_id' => $authUser['user_id']];

    if ($unreadOnly) {
        $sql .= " AND is_read = 0";
    }

    $sql .= " ORDER BY created_at DESC LIMIT " . min($limit, 100);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();

    // Get unread count
    $stmt2 = $db->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $stmt2->execute([':user_id' => $authUser['user_id']]);
    $unreadCount = $stmt2->fetch()['unread'];

    json_success([
        'count'         => count($notifications),
        'unread_count'  => (int)$unreadCount,
        'notifications' => $notifications
    ]);
}

// ── Mark as Read ──────────────────────────────────────
elseif ($action === 'read' && $method === 'PUT') {
    $authUser = require_auth();

    $input = get_json_input();
    $db = get_db();

    // Mark single notification or all
    if (isset($input['notification_id'])) {
        $stmt = $db->prepare("
            UPDATE notifications SET is_read = 1
            WHERE notification_id = :nid AND user_id = :user_id
        ");
        $stmt->execute([
            ':nid'     => intval($input['notification_id']),
            ':user_id' => $authUser['user_id']
        ]);
        $updated = $stmt->rowCount();
    } else {
        // Mark all as read
        $stmt = $db->prepare("
            UPDATE notifications SET is_read = 1
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute([':user_id' => $authUser['user_id']]);
        $updated = $stmt->rowCount();
    }

    json_success(['updated' => $updated], 'Notifications marked as read');
}

// ── Unread Count ──────────────────────────────────────
elseif ($action === 'count' && $method === 'GET') {
    $authUser = require_auth();

    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $stmt->execute([':user_id' => $authUser['user_id']]);
    $count = $stmt->fetch()['unread'];

    json_success(['unread_count' => (int)$count]);
}

// ── Default ───────────────────────────────────────────
else {
    json_success([
        'endpoints' => [
            'GET  ?action=list'   => 'Get notifications (?unread_only=1, ?limit=N)',
            'PUT  ?action=read'   => 'Mark as read (notification_id or all)',
            'GET  ?action=count'  => 'Get unread notification count'
        ]
    ], 'Bright Steps Clinic — Notifications API');
}
