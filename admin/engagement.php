<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'all_data';
        
        if ($action === 'all_data') {
            $stmt = $connect->query("SELECT * FROM points_refrence");
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $connect->query("SELECT * FROM badge");
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $connect->query("SELECT * FROM announcement_banners ORDER BY created_at DESC");
            $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'rules' => $rules,
                'badges' => $badges,
                'banners' => $banners
            ]);
        }
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $action = $data['action'] ?? '';

        if ($action === 'save_rule') {
            $id = (int)($data['refrence_id'] ?? 0);
            $name = $data['action_name'] ?? '';
            $pts = (int)($data['points_value'] ?? 0);
            $sign = $data['adjust_sign'] ?? '+';
            
            if (!$name || $pts <= 0) { echo json_encode(['error' => 'Invalid data']); exit; }

            if ($id > 0) {
                $stmt = $connect->prepare("UPDATE points_refrence SET action_name = :name, points_value = :pts, adjust_sign = :sign WHERE refrence_id = :id");
                $stmt->execute(['name'=>$name, 'pts'=>$pts, 'sign'=>$sign, 'id'=>$id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign) VALUES (:aid, :name, :pts, :sign)");
                $stmt->execute(['aid'=>$_SESSION['id'], 'name'=>$name, 'pts'=>$pts, 'sign'=>$sign]);
            }
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete_rule') {
            $id = (int)$data['refrence_id'];
            $stmt = $connect->prepare("DELETE FROM points_refrence WHERE refrence_id = :id");
            $stmt->execute(['id'=>$id]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'save_badge') {
            $id = (int)($data['badge_id'] ?? 0);
            $name = $data['name'] ?? '';
            $desc = $data['description'] ?? '';
            $icon = $data['icon'] ?? '🏆';

            if (!$name) { echo json_encode(['error' => 'Name is required']); exit; }

            if ($id > 0) {
                $stmt = $connect->prepare("UPDATE badge SET name = :name, description = :desc, icon = :icon WHERE badge_id = :id");
                $stmt->execute(['name'=>$name, 'desc'=>$desc, 'icon'=>$icon, 'id'=>$id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO badge (name, description, icon) VALUES (:name, :desc, :icon)");
                $stmt->execute(['name'=>$name, 'desc'=>$desc, 'icon'=>$icon]);
            }
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete_badge') {
            $id = (int)$data['badge_id'];
            $stmt = $connect->prepare("DELETE FROM badge WHERE badge_id = :id");
            $stmt->execute(['id'=>$id]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'save_banner') {
            $id = (int)($data['id'] ?? 0);
            $msg = $data['message'] ?? '';
            $style = $data['style'] ?? 'info';
            $link = $data['link'] ?? null;
            $aud = $data['target_audience'] ?? 'all';
            $is_active = (int)($data['is_active'] ?? 1);

            if (!$msg) { echo json_encode(['error' => 'Message is required']); exit; }

            if ($id > 0) {
                $stmt = $connect->prepare("UPDATE announcement_banners SET message=:m, style=:s, link=:l, target_audience=:ta, is_active=:ia WHERE id=:id");
                $stmt->execute(['m'=>$msg, 's'=>$style, 'l'=>$link, 'ta'=>$aud, 'ia'=>$is_active, 'id'=>$id]);
            } else {
                $stmt = $connect->prepare("INSERT INTO announcement_banners (message, style, link, target_audience, is_active, created_by) VALUES (:m, :s, :l, :ta, :ia, :cb)");
                $stmt->execute(['m'=>$msg, 's'=>$style, 'l'=>$link, 'ta'=>$aud, 'ia'=>$is_active, 'cb'=>$_SESSION['id']]);
            }
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete_banner') {
            $id = (int)$data['id'];
            $stmt = $connect->prepare("DELETE FROM announcement_banners WHERE id = :id");
            $stmt->execute(['id'=>$id]);
            echo json_encode(['success' => true]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}
