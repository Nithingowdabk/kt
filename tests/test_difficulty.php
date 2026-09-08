<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    
    // 1. Describe table to check column details
    $stmt = $db->query("DESCRIBE treks");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fields as $field) {
        if ($field['Field'] === 'difficulty') {
            echo "Difficulty Column definition:\n";
            print_r($field);
        }
    }
    
    // 2. Test updating an existing trek (ID 1) setting difficulty to NULL
    echo "\nTesting UPDATE treks SET difficulty = NULL WHERE id = 1...\n";
    $test_stmt = $db->prepare("UPDATE treks SET difficulty = NULL WHERE id = 1");
    $test_stmt->execute();
    
    // Fetch it back
    $fetch_stmt = $db->prepare("SELECT id, title, difficulty FROM treks WHERE id = 1");
    $fetch_stmt->execute();
    $trek = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Trek after update:\n";
    print_r($trek);
    
    // Restore back to original (Moderate or Easy etc)
    $restore_stmt = $db->prepare("UPDATE treks SET difficulty = 'Moderate' WHERE id = 1");
    $restore_stmt->execute();
    echo "\nRestored trek difficulty back to Moderate.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
