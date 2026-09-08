<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    
    // Check if column already exists
    $columns = $db->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'completed') {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $db->exec("ALTER TABLE bookings ADD COLUMN completed TINYINT(1) NOT NULL DEFAULT 0 AFTER booking_status");
        echo "Successfully added 'completed' column to bookings table.\n";
    } else {
        echo "'completed' column already exists in bookings table.\n";
    }
    
    // Add indexes to optimize queries
    $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_completed (completed)");
    $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_created_at (created_at)");
    $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_payment_status (payment_status)");
    $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_booking_status (booking_status)");
    $db->exec("ALTER TABLE trek_dates ADD INDEX idx_trek_dates_start_date (start_date)");
    echo "Successfully created indexes for query performance optimization.\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
