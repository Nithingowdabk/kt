<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::connect();
    echo "CONNECTED TO DB\n";
    
    echo "--- DESCRIBE trek_dates ---\n";
    $schema = $db->query("DESCRIBE trek_dates")->fetchAll(PDO::FETCH_ASSOC);
    print_r($schema);
    
    echo "--- SELECT * FROM trek_dates LIMIT 3 ---\n";
    $rows = $db->query("SELECT * FROM trek_dates LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
