<?php
require 'connection.php';
$sql = "CREATE TABLE IF NOT EXISTS specialist_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    specialist_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES parent(parent_id),
    FOREIGN KEY (specialist_id) REFERENCES specialist(specialist_id)
)";
try {
    $connect->query($sql);
    echo "Table created successfully\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
