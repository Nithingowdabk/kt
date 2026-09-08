<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    
    // Fetch Trek 1 settings
    $trek = $db->query("SELECT title, recurring_friday, recurring_saturday, recurring_sunday, recurring_until FROM treks WHERE id = 1")->fetch();
    echo "=== Trek 1 Settings ===\n";
    print_r($trek);
    
    // Fetch Trek 1 dates
    $dates = $db->query("SELECT id, start_date, end_date, schedule_type, status FROM trek_dates WHERE trek_id = 1 ORDER BY start_date ASC")->fetchAll();
    echo "\n=== Trek 1 Dates ===\n";
    foreach ($dates as $d) {
        $day_name = date('D', strtotime($d['start_date']));
        echo "ID: {$d['id']} | {$d['start_date']} ({$day_name}) -> {$d['end_date']} | Type: {$d['schedule_type']} | Status: {$d['status']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
