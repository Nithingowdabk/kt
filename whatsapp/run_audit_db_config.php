<?php
require_once __DIR__ . '/../includes/database.php';
try {
    $db = Database::connect();
    
    // Ensure the settings keys exist
    $db->exec("INSERT IGNORE INTO settings (key_name, value_data) VALUES ('naitrons_api_url', '')");
    $db->exec("INSERT IGNORE INTO settings (key_name, value_data) VALUES ('naitrons_api_key', '')");

    $db->exec("UPDATE settings SET value_data = 'http://localhost:4000/api/v1/events' WHERE key_name = 'naitrons_api_url'");
    
    $apiKey = $argv[1] ?? '';
    $stmt = $db->prepare("UPDATE settings SET value_data = ? WHERE key_name = 'naitrons_api_key'");
    $stmt->execute([$apiKey]);

    // Clean up any existing test bookings, payments, and users matching the test phone
    $db->exec("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE phone = '919999911111')");
    $db->exec("DELETE FROM bookings WHERE phone = '919999911111'");
    $db->exec("DELETE FROM users WHERE phone = '919999911111'");

    echo "MYSQL_CONFIG_SUCCESS\n";
} catch (Exception $e) {
    echo "MYSQL_CONFIG_ERROR: " . $e->getMessage() . "\n";
}
