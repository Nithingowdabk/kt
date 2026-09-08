<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    
    // Check if column already exists in treks
    $columns = $db->query("SHOW COLUMNS FROM treks")->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'recurring_saturday') {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $db->exec("ALTER TABLE treks ADD COLUMN recurring_saturday TINYINT(1) DEFAULT 0 AFTER recurring_friday");
        echo "Successfully added 'recurring_saturday' column to treks table.\n";
    } else {
        echo "'recurring_saturday' column already exists in treks table.\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
