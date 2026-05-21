<?php
require_once "connection.php";
try {
    $result = $connect->query("SHOW TABLES LIKE 'specialist_reviews'");
    if ($result->rowCount() > 0) {
        echo "Table exists\n";
        $cols = $connect->query("DESCRIBE specialist_reviews");
        print_r($cols->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "Table does not exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>