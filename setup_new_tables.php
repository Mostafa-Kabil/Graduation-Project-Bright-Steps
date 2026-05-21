<?php
/**
 * setup_new_tables.php
 * Creates specialist_reviews table and updates appointment status enum.
 */
require_once __DIR__ . '/connection.php';

try {
    // Create specialist_reviews table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS specialist_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        specialist_id INT NOT NULL,
        parent_id INT NOT NULL,
        appointment_id INT NOT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (specialist_id) REFERENCES specialist(specialist_id),
        FOREIGN KEY (parent_id) REFERENCES parent(parent_id),
        FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $connect->exec($sql);

    // Update appointment status enum to include new values
    $enumQuery = $connect->query("SHOW COLUMNS FROM appointment LIKE 'status'");
    $row = $enumQuery->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $type = $row['Type']; // e.g., enum('Scheduled','Completed',...)
        if (strpos($type, 'enum') !== false) {
            $newValues = ['Pending Reschedule','Cancelled','Refunded'];
            foreach ($newValues as $val) {
                if (strpos($type, "'$val'") === false) {
                    $type = rtrim($type, ')') . ",'$val')";
                }
            }
            $alterSql = "ALTER TABLE appointment MODIFY status $type NOT NULL";
            $connect->exec($alterSql);
        }
    }
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
?>
