<?php
require 'connection.php';
try {
    $stmt = $connect->query('SELECT COUNT(*) FROM milestones');
    echo "Milestones: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Milestones Error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $connect->query('SELECT COUNT(*) FROM child_milestones');
    echo "Child Milestones: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Child Milestones Error: " . $e->getMessage() . "\n";
}
