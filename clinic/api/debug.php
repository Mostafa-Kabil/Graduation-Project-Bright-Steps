<?php
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

try {
    $db = get_db();
    
    $tables = [
        'users', 'clinic', 'specialist', 'child', 'parent', 
        'appointment', 'medical_records', 'prescriptions'
    ];
    
    $results = [];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM `$table` LIMIT 1");
            $row = $stmt->fetch();
            $results[$table] = [
                'exists' => true,
                'count'  => (int)$row['count']
            ];
        } catch (Exception $e) {
            $results[$table] = [
                'exists' => false,
                'error'  => $e->getMessage()
            ];
        }
    }
    
    json_success([
        'connection' => 'success',
        'database'   => 'grad',
        'tables'     => $results
    ], 'Bright Steps Clinic — System Debug Utility');

} catch (Exception $e) {
    json_error("Connection Failed: " . $e->getMessage(), 500);
}
