<?php
/**
 * Bright Steps - Redemption Catalog API
 * CRUD operations for redemption items and parent redemption management
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        // List all active catalog items (public or authenticated)
        if ($action === 'list') {
            $category = $_GET['category'] ?? 'all';

            $sql = "SELECT * FROM redemption_catalog WHERE is_active = 1";

            if ($category !== 'all') {
                $sql .= " AND item_type = ?";
            }

            $sql .= " ORDER BY points_cost ASC";

            $stmt = $connect->prepare($sql);

            if ($category !== 'all') {
                $stmt->execute([$category]);
            } else {
                $stmt->execute();
            }

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'items' => $items,
                'count' => count($items)
            ]);

        // Get single item details
        } elseif ($action === 'item') {
            $itemId = $_GET['item_id'] ?? null;

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id required']);
                exit();
            }

            $stmt = $connect->prepare("SELECT * FROM redemption_catalog WHERE item_id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => 'Item not found']);
                exit();
            }

            echo json_encode([
                'success' => true,
                'item' => $item
            ]);

        // Get parent's redemption history (requires auth)
        } elseif ($action === 'history') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            $parentId = $_SESSION['id'];
            $status = $_GET['status'] ?? 'all';

            $sql = "
                SELECT pr.*, rc.item_name, rc.item_type, rc.icon
                FROM parent_redemptions pr
                INNER JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE pr.parent_id = ?
            ";

            $params = [$parentId];

            if ($status !== 'all') {
                $sql .= " AND pr.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY pr.created_at DESC";

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'redemptions' => $redemptions,
                'count' => count($redemptions)
            ]);

        // Get parent's available tokens (requires auth)
        } elseif ($action === 'tokens') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            $parentId = $_SESSION['id'];

            $stmt = $connect->prepare("
                SELECT at.*, rc.item_name
                FROM appointment_tokens at
                LEFT JOIN parent_redemptions pr ON at.redemption_id = pr.redemption_id
                LEFT JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE at.parent_id = ? AND at.status = 'available'
                ORDER BY at.expires_at ASC
            ");
            $stmt->execute([$parentId]);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalDiscountValue = 0;
            foreach ($tokens as $token) {
                $totalDiscountValue += (float) $token['discount_amount'];
            }

            echo json_encode([
                'success' => true,
                'tokens' => $tokens,
                'count' => count($tokens),
                'total_discount_value' => $totalDiscountValue
            ]);

        // Admin: Get all redemptions
        } elseif ($action === 'all_redemptions') {
            if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
                http_response_code(401);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            $status = $_GET['status'] ?? 'all';
            $limit = $_GET['limit'] ?? 100;

            $sql = "
                SELECT pr.*, rc.item_name, rc.item_type,
                       p.email as parent_email,
                       u.first_name, u.last_name
                FROM parent_redemptions pr
                INNER JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                INNER JOIN parent p ON pr.parent_id = p.parent_id
                INNER JOIN users u ON p.parent_id = u.user_id
            ";

            if ($status !== 'all') {
                $sql .= " WHERE pr.status = ?";
                $params = [$status];
            }

            $sql .= " ORDER BY pr.created_at DESC LIMIT ?";
            $params = isset($params) ? array_merge($params, [$limit]) : [$limit];

            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'redemptions' => $redemptions,
                'count' => count($redemptions)
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } elseif ($method === 'POST') {
        // Require admin for write operations
        if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? '';

        // Create catalog item
        if ($action === 'create_item') {
            $itemType = $input['item_type'] ?? '';
            $itemName = $input['item_name'] ?? '';
            $description = $input['description'] ?? '';
            $pointsCost = (int) ($input['points_cost'] ?? 0);
            $originalPrice = (float) ($input['original_price'] ?? 0);
            $discountPercentage = (float) ($input['discount_percentage'] ?? 0);
            $icon = $input['icon'] ?? '🎁';
            $badgeColor = $input['badge_color'] ?? 'blue';
            $maxRedemptions = $input['max_redemptions_per_user'] ?? null;
            $validUntil = $input['valid_until'] ?? null;

            if (!$itemType || !$itemName || !$pointsCost) {
                http_response_code(400);
                echo json_encode(['error' => 'item_type, item_name, and points_cost are required']);
                exit();
            }

            $stmt = $connect->prepare("
                INSERT INTO redemption_catalog
                (item_type, item_name, description, points_cost, original_price, discount_percentage, icon, badge_color, max_redemptions_per_user, valid_until)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $itemType, $itemName, $description, $pointsCost, $originalPrice, $discountPercentage,
                $icon, $badgeColor, $maxRedemptions, $validUntil
            ]);

            echo json_encode([
                'success' => true,
                'item_id' => $connect->lastInsertId(),
                'message' => 'Catalog item created'
            ]);

        // Update catalog item
        } elseif ($action === 'update_item') {
            $itemId = (int) ($input['item_id'] ?? 0);

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id required']);
                exit();
            }

            $fields = [];
            $params = ['id' => $itemId];

            $allowedFields = ['item_type', 'item_name', 'description', 'points_cost', 'original_price',
                             'discount_percentage', 'is_active', 'icon', 'badge_color',
                             'max_redemptions_per_user', 'valid_until'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }

            $sql = "UPDATE redemption_catalog SET " . implode(', ', $fields) . " WHERE item_id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Catalog item updated'
            ]);

        // Delete catalog item
        } elseif ($action === 'delete_item') {
            $itemId = (int) ($input['item_id'] ?? 0);

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id required']);
                exit();
            }

            // Soft delete by setting is_active = 0
            $stmt = $connect->prepare("UPDATE redemption_catalog SET is_active = 0 WHERE item_id = ?");
            $stmt->execute([$itemId]);

            echo json_encode([
                'success' => true,
                'message' => 'Catalog item deactivated'
            ]);

        // Admin: Update redemption status
        } elseif ($action === 'update_redemption') {
            $redemptionId = (int) ($input['redemption_id'] ?? 0);
            $status = $input['status'] ?? '';

            if (!$redemptionId || !in_array($status, ['pending', 'active', 'used', 'expired', 'refunded'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Valid redemption_id and status required']);
                exit();
            }

            $notes = $input['notes'] ?? null;

            $stmt = $connect->prepare("
                UPDATE parent_redemptions
                SET status = ?, notes = ?, updated_at = NOW()
                WHERE redemption_id = ?
            ");
            $stmt->execute([$status, $notes, $redemptionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Redemption status updated'
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } elseif ($method === 'DELETE') {
        // Require admin
        if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $redemptionId = $input['redemption_id'] ?? null;

        if ($redemptionId) {
            $stmt = $connect->prepare("DELETE FROM parent_redemptions WHERE redemption_id = ?");
            $stmt->execute([$redemptionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Redemption record deleted'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'redemption_id required']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
