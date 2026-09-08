<?php
require_once __DIR__ . '/../includes/database.php';
try {
    $db = Database::connect();
    
    // Clear Naitrons credentials
    $db->exec("UPDATE settings SET value_data = '' WHERE key_name = 'naitrons_api_key'");

    // Clean up test bookings, payments, and users matching the test phone
    $db->exec("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE phone = '919999911111')");
    $db->exec("DELETE FROM bookings WHERE phone = '919999911111'");
    $db->exec("DELETE FROM users WHERE phone = '919999911111'");

    echo "MYSQL_RESTORE_SUCCESS\n";
} catch (Exception $e) {
    echo "MYSQL_RESTORE_ERROR: " . $e->getMessage() . "\n";
}
