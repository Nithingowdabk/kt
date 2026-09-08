<?php
/**
 * Run Database Migration for Packages Upgrade
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    $sql = file_get_contents(__DIR__ . '/upgrade_packages.sql');
    
    // Split SQL by semicolon and execute queries one by one
    // (Note: simple splitting works since there are no semicolons inside triggers or stored procedures)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    echo "SUCCESS: Database migrated successfully for Dual Package Booking System!\n";
} catch (Exception $e) {
    echo "ERROR: Migration failed: " . $e->getMessage() . "\n";
}
