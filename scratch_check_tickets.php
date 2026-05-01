<?php
include 'connection.php';
try {
    $r = $connect->query("DESCRIBE ticket_messages");
    if($r) {
        print_r($r->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "Table does not exist.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
