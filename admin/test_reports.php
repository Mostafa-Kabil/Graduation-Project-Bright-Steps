<?php
$_SESSION = [];
$_SESSION['id'] = 1;
$_SESSION['role'] = 'admin';
$_GET['action'] = 'behavior_categories';
include 'connection.php';
// just execute the block for behavior_categories
$stmt = $connect->query("
    SELECT bc.category_id, bc.category_name, bc.category_type,
        (SELECT COUNT(*) FROM behavior b WHERE b.category_id = bc.category_id) as behavior_count,
        (SELECT COUNT(DISTINCT ceb.child_id) FROM child_exhibited_behavior ceb 
         JOIN behavior b2 ON ceb.behavior_id = b2.behavior_id 
         WHERE b2.category_id = bc.category_id) as children_affected
    FROM behavior_category bc
    ORDER BY bc.category_name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'categories' => $categories]);
?>
