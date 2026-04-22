<?php
require 'connection.php';
try {
    $cCount = $connect->query("SELECT COUNT(*) FROM child")->fetchColumn();
    $sCount = $connect->query("SELECT COUNT(*) FROM specialist")->fetchColumn();
    $clinicsCount = $connect->query("SELECT COUNT(*) FROM clinic")->fetchColumn();
    $usersCount = $connect->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    echo "Summary:\n";
    echo "Children: $cCount\n";
    echo "Specialists: $sCount\n";
    echo "Clinics: $clinicsCount\n";
    echo "Users: $usersCount\n\n";
    
    if ($sCount > 0) {
        echo "Specialists Sample:\n";
        print_r($connect->query("SELECT * FROM specialist LIMIT 3")->fetchAll(PDO::FETCH_ASSOC));
    }
    
    if ($cCount > 0) {
        echo "\nChildren Sample:\n";
        print_r($connect->query("SELECT * FROM child LIMIT 3")->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "\nWARNING: child table is EMPTY!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
