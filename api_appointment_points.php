<?php
/**
 * Bright Steps - Appointment Points Integration API
 * Handles token application, discount calculation, and points-based booking
 */
session_start();
require_once "connection.php";
header('Content-Type: application/json');

// Require authenticated parent
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Parent access required']);
    exit();
}

$parentId = $_SESSION['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'calculate';

        // Calculate discount for appointment with token
        if ($action === 'calculate') {
            $appointmentId = $_GET['appointment_id'] ?? null;
            $tokenId = $_GET['token_id'] ?? null;
            $basePrice = (float) ($_GET['price'] ?? 50.00);

            if (!$tokenId) {
                echo json_encode([
                    'success' => true,
                    'base_price' => $basePrice,
                    'discount' => 0,
                    'final_price' => $basePrice,
                    'message' => 'No token applied'
                ]);
                exit();
            }

            // Get token details
            $stmt = $connect->prepare("
                SELECT at.*, rc.item_name, rc.discount_percentage
                FROM appointment_tokens at
                LEFT JOIN parent_redemptions pr ON at.redemption_id = pr.redemption_id
                LEFT JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE at.token_id = ? AND at.parent_id = ? AND at.status = 'available'
            ");
            $stmt->execute([$tokenId, $parentId]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$token) {
                http_response_code(404);
                echo json_encode(['error' => 'Token not found or not available']);
                exit();
            }

            $discount = (float) $token['discount_amount'];
            $finalPrice = max(0, $basePrice - $discount);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'base_price' => $basePrice,
                'discount' => $discount,
                'discount_percentage' => $token['discount_percentage'] ?? 0,
                'final_price' => $finalPrice,
                'expires_at' => $token['expires_at']
            ]);

        // Get available tokens for appointment
        } elseif ($action === 'available_tokens') {
            $stmt = $connect->prepare("
                SELECT at.*, rc.item_name, rc.icon, rc.badge_color
                FROM appointment_tokens at
                LEFT JOIN parent_redemptions pr ON at.redemption_id = pr.redemption_id
                LEFT JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE at.parent_id = ? AND at.status = 'available' AND at.expires_at >= CURDATE()
                ORDER BY at.discount_amount DESC, at.expires_at ASC
            ");
            $stmt->execute([$parentId]);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'tokens' => $tokens,
                'count' => count($tokens)
            ]);

        // Get appointment with token info
        } elseif ($action === 'appointment_tokens') {
            $appointmentId = $_GET['appointment_id'] ?? null;

            if (!$appointmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'appointment_id required']);
                exit();
            }

            $stmt = $connect->prepare("
                SELECT a.*, at.token_id, at.token_type, at.discount_amount,
                       rc.item_name as token_name, rc.icon
                FROM appointment a
                LEFT JOIN appointment_tokens at ON at.applied_to_appointment = a.appointment_id AND at.status IN ('applied', 'used')
                LEFT JOIN parent_redemptions pr ON at.redemption_id = pr.redemption_id
                LEFT JOIN redemption_catalog rc ON pr.item_id = rc.item_id
                WHERE a.appointment_id = ? AND a.parent_id = ?
            ");
            $stmt->execute([$appointmentId, $parentId]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
                exit();
            }

            echo json_encode([
                'success' => true,
                'appointment' => $appointment
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // Apply token to appointment
        if ($action === 'apply_token') {
            $appointmentId = (int) ($input['appointment_id'] ?? 0);
            $tokenId = (int) ($input['token_id'] ?? 0);

            if (!$appointmentId || !$tokenId) {
                http_response_code(400);
                echo json_encode(['error' => 'appointment_id and token_id required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                // Verify appointment belongs to parent and is not cancelled/completed
                $stmt = $connect->prepare("
                    SELECT status, payment_id FROM appointment
                    WHERE appointment_id = ? AND parent_id = ?
                ");
                $stmt->execute([$appointmentId, $parentId]);
                $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$appointment) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Appointment not found']);
                    exit();
                }

                if (in_array($appointment['status'], ['cancelled', 'completed'])) {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Cannot apply token to ' . $appointment['status'] . ' appointment']);
                    exit();
                }

                // Verify token
                $stmt = $connect->prepare("
                    SELECT * FROM appointment_tokens
                    WHERE token_id = ? AND parent_id = ? AND status = 'available'
                ");
                $stmt->execute([$tokenId, $parentId]);
                $token = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$token) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Token not found or not available']);
                    exit();
                }

                // Check token not already applied to another appointment
                if ($token['applied_to_appointment'] && $token['applied_to_appointment'] != $appointmentId) {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Token already applied to another appointment']);
                    exit();
                }

                // Apply token to appointment
                $stmt = $connect->prepare("
                    UPDATE appointment_tokens
                    SET status = 'applied', applied_to_appointment = ?
                    WHERE token_id = ?
                ");
                $stmt->execute([$appointmentId, $tokenId]);

                // Update payment record with discount
                if ($appointment['payment_id'] && $token['discount_amount'] > 0) {
                    $stmt = $connect->prepare("
                        UPDATE payment
                        SET tokens_used = ?, token_id = ?,
                            amount_post_discount = GREATEST(0, amount_post_discount - ?)
                        WHERE payment_id = ?
                    ");
                    $stmt->execute([$token['discount_amount'], $tokenId, $token['discount_amount'], $appointment['payment_id']]);
                }

                // Create notification
                $stmt = $connect->prepare("
                    INSERT INTO notifications (user_id, type, title, message)
                    VALUES (?, 'points', ?, ?)
                ");
                $title = "Token Applied! 💰";
                $message = "You saved $" . number_format($token['discount_amount'], 2) . " on your appointment";
                $stmt->execute([$parentId, $title, $message]);

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Token applied successfully',
                    'discount_amount' => $token['discount_amount'],
                    'token_type' => $token['token_type']
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to apply token: ' . $e->getMessage()]);
            }

        // Remove token from appointment
        } elseif ($action === 'remove_token') {
            $appointmentId = (int) ($input['appointment_id'] ?? 0);
            $tokenId = (int) ($input['token_id'] ?? 0);

            if (!$appointmentId || !$tokenId) {
                http_response_code(400);
                echo json_encode(['error' => 'appointment_id and token_id required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                // Verify token is applied to this appointment
                $stmt = $connect->prepare("
                    SELECT * FROM appointment_tokens
                    WHERE token_id = ? AND parent_id = ? AND applied_to_appointment = ? AND status = 'applied'
                ");
                $stmt->execute([$tokenId, $parentId, $appointmentId]);
                $token = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$token) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Token not applied to this appointment']);
                    exit();
                }

                // Revert token status
                $stmt = $connect->prepare("
                    UPDATE appointment_tokens
                    SET status = 'available', applied_to_appointment = NULL
                    WHERE token_id = ?
                ");
                $stmt->execute([$tokenId]);

                // Revert payment discount
                $stmt = $connect->prepare("
                    SELECT payment_id FROM appointment WHERE appointment_id = ?
                ");
                $stmt->execute([$appointmentId]);
                $appt = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($appt['payment_id']) {
                    $stmt = $connect->prepare("
                        UPDATE payment
                        SET tokens_used = 0, token_id = NULL,
                            amount_post_discount = amount_pre_discount
                        WHERE payment_id = ?
                    ");
                    $stmt->execute([$appt['payment_id']]);
                }

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Token removed from appointment'
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to remove token: ' . $e->getMessage()]);
            }

        // Book appointment with token
        } elseif ($action === 'book_with_token') {
            $specialistId = (int) ($input['specialist_id'] ?? 0);
            $scheduledAt = $input['scheduled_at'] ?? '';
            $type = $input['type'] ?? 'onsite';
            $comment = trim($input['comment'] ?? '');
            $tokenId = (int) ($input['token_id'] ?? 0);
            $paymentMethod = $input['payment_method'] ?? 'Cash';

            if (!$specialistId || !$scheduledAt) {
                http_response_code(400);
                echo json_encode(['error' => 'Specialist and scheduled_at required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                $basePrice = 50.00;
                $discount = 0;
                $tokenType = null;

                // If token provided, verify and calculate discount
                if ($tokenId) {
                    $stmt = $connect->prepare("
                        SELECT * FROM appointment_tokens
                        WHERE token_id = ? AND parent_id = ? AND status = 'available'
                    ");
                    $stmt->execute([$tokenId, $parentId]);
                    $token = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$token) {
                        $connect->rollBack();
                        http_response_code(404);
                        echo json_encode(['error' => 'Token not found or not available']);
                        exit();
                    }

                    $discount = (float) $token['discount_amount'];
                    $tokenType = $token['token_type'];
                }

                $finalPrice = max(0, $basePrice - $discount);

                // Create payment record
                $stmt = $connect->prepare("
                    INSERT INTO payment (amount_pre_discount, amount_post_discount, method, status, tokens_used, token_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $paymentStatus = ($paymentMethod === 'Credit Card') ? 'Paid' : 'Pending';
                $stmt->execute([$basePrice, $finalPrice, $paymentMethod, $paymentStatus, $discount, $tokenId ?: null]);
                $paymentId = $connect->lastInsertId();

                // Create appointment
                $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledAt));
                $stmt = $connect->prepare("
                    INSERT INTO appointment (parent_id, payment_id, specialist_id, status, type, comment, scheduled_at)
                    VALUES (?, ?, ?, 'Scheduled', ?, ?, ?)
                ");
                $stmt->execute([$parentId, $paymentId, $specialistId, $type, $comment, $scheduledDateTime]);
                $appointmentId = $connect->lastInsertId();

                // If token used, update its status
                if ($tokenId && $discount > 0) {
                    $stmt = $connect->prepare("
                        UPDATE appointment_tokens
                        SET status = 'applied', applied_to_appointment = ?
                        WHERE token_id = ?
                    ");
                    $stmt->execute([$appointmentId, $tokenId]);
                }

                // Create notification
                $appointmentType = ($type === 'onsite') ? 'clinic visit' : 'online session';
                $savingsText = $discount > 0 ? " (Saved $" . number_format($discount, 2) . " with token!)" : "";
                $stmt = $connect->prepare("
                    INSERT INTO notifications (user_id, type, title, message)
                    VALUES (?, 'appointment', ?, ?)
                ");
                $title = "Appointment Scheduled" . ($discount > 0 ? " 🎉" : "");
                $message = "Your {$appointmentType} has been scheduled for " . date('M j, Y g:i A', strtotime($scheduledDateTime)) . $savingsText;
                $stmt->execute([$parentId, $title, $message]);

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'appointment_id' => $appointmentId,
                    'payment_id' => $paymentId,
                    'base_price' => $basePrice,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'message' => 'Appointment booked successfully' . ($discount > 0 ? " - You saved $" . number_format($discount, 2) . "!" : "")
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to book appointment: ' . $e->getMessage()]);
            }

        // Use token (mark as used after appointment)
        } elseif ($action === 'use_token') {
            $tokenId = (int) ($input['token_id'] ?? 0);
            $appointmentId = (int) ($input['appointment_id'] ?? 0);

            if (!$tokenId || !$appointmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'token_id and appointment_id required']);
                exit();
            }

            $connect->beginTransaction();

            try {
                // Verify token is applied to this appointment
                $stmt = $connect->prepare("
                    SELECT * FROM appointment_tokens
                    WHERE token_id = ? AND parent_id = ? AND applied_to_appointment = ? AND status = 'applied'
                ");
                $stmt->execute([$tokenId, $parentId, $appointmentId]);
                $token = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$token) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Token not applied to this appointment']);
                    exit();
                }

                // Verify appointment is completed
                $stmt = $connect->prepare("SELECT status FROM appointment WHERE appointment_id = ? AND parent_id = ?");
                $stmt->execute([$appointmentId, $parentId]);
                $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$appointment) {
                    $connect->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Appointment not found']);
                    exit();
                }

                if ($appointment['status'] !== 'completed') {
                    $connect->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Token can only be used after appointment is completed']);
                    exit();
                }

                // Mark token as used
                $stmt = $connect->prepare("
                    UPDATE appointment_tokens
                    SET status = 'used', used_at = NOW()
                    WHERE token_id = ?
                ");
                $stmt->execute([$tokenId]);

                $connect->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Token marked as used'
                ]);

            } catch (Exception $e) {
                $connect->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to use token: ' . $e->getMessage()]);
            }

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
