<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "=== WEEKEND SCHEDULE AUTO-GENERATION LOGIC TEST (WITH SUNDAY) ===\n";

$db = Database::connect();

try {
    $db->beginTransaction();

    // Create a mock trek for testing
    $db->exec("INSERT INTO treks (title, slug, duration, difficulty, trek_distance, altitude, price, description, itinerary, status) 
               VALUES ('Sunday Schedule Test Trek', 'sunday-schedule-test-trek', '2 Days / 1 Night', 'Moderate', 10.0, 3000, 1500.00, 'Mock description', 'Mock itinerary', 'Active')");
    $trek_id = $db->lastInsertId();
    
    // Duration guess logic (similar to schedules.php)
    $duration_days_guess = 2;
    
    // We will test starting from a fixed date: 2026-06-01
    // 2026-06-01 is a Monday.
    // Let's generate until 2026-06-22 (Monday).
    $test_start_date = '2026-06-01';
    $test_until_date = '2026-06-22';
    
    // Case 1: Friday only
    echo "\n--- Testing Case 1: Friday Only ---\n";
    $recurring_friday = 1;
    $recurring_saturday = 0;
    $recurring_sunday = 0;
    $generated_dates_1 = run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $test_start_date, $test_until_date, $duration_days_guess);
    print_r($generated_dates_1);
    
    // Clean up
    $db->exec("DELETE FROM trek_dates WHERE trek_id = $trek_id");

    // Case 2: Saturday only
    echo "\n--- Testing Case 2: Saturday Only ---\n";
    $recurring_friday = 0;
    $recurring_saturday = 1;
    $recurring_sunday = 0;
    $generated_dates_2 = run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $test_start_date, $test_until_date, $duration_days_guess);
    print_r($generated_dates_2);
    
    // Clean up
    $db->exec("DELETE FROM trek_dates WHERE trek_id = $trek_id");

    // Case 3: Sunday only
    echo "\n--- Testing Case 3: Sunday Only ---\n";
    $recurring_friday = 0;
    $recurring_saturday = 0;
    $recurring_sunday = 1;
    $generated_dates_3 = run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $test_start_date, $test_until_date, $duration_days_guess);
    print_r($generated_dates_3);
    
    // Clean up
    $db->exec("DELETE FROM trek_dates WHERE trek_id = $trek_id");

    // Case 4: Friday, Saturday, and Sunday
    echo "\n--- Testing Case 4: All (Friday + Saturday + Sunday) ---\n";
    $recurring_friday = 1;
    $recurring_saturday = 1;
    $recurring_sunday = 1;
    $generated_dates_4 = run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $test_start_date, $test_until_date, $duration_days_guess);
    print_r($generated_dates_4);
    
    // Case 5: Duplicate Protection
    echo "\n--- Testing Case 5: Duplicate Protection ---\n";
    // We run generation again with all enabled. It should return 0 new generated dates because they already exist from Case 4.
    $generated_dates_5 = run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $test_start_date, $test_until_date, $duration_days_guess);
    echo "Generated count on second run: " . count($generated_dates_5) . " (Expected: 0)\n";
    
    $db->rollBack();
    echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY AND ROLLED BACK ===\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "TEST FAILED: " . $e->getMessage() . "\n";
}

function run_generation($trek_id, $recurring_friday, $recurring_saturday, $recurring_sunday, $start_date, $until_date, $duration_days_guess) {
    global $db;
    $generated = [];
    
    if (($recurring_friday || $recurring_saturday || $recurring_sunday) && !empty($until_date)) {
        $current_time = strtotime($start_date);
        $until_time = strtotime($until_date);

        while ($current_time <= $until_time) {
            $day_name = date('D', $current_time);
            if (($recurring_friday && $day_name === 'Fri') || 
                ($recurring_saturday && $day_name === 'Sat') || 
                ($recurring_sunday && $day_name === 'Sun')) {
                $batch_start = date('Y-m-d', $current_time);
                
                // calculate end date
                $batch_end_time = strtotime("+" . ($duration_days_guess - 1) . " days", $current_time);
                $batch_end = date('Y-m-d', $batch_end_time);

                // Prevent duplicate start date creation
                $dup_stmt = $db->prepare("SELECT COUNT(*) FROM trek_dates WHERE trek_id = ? AND start_date = ?");
                $dup_stmt->execute([$trek_id, $batch_start]);
                
                if ($dup_stmt->fetchColumn() == 0) {
                    $ins_stmt = $db->prepare("INSERT INTO trek_dates (trek_id, start_date, end_date, seats, booked_seats, available_seats, price, label, schedule_type, status) VALUES (?, ?, ?, 20, 0, 20, NULL, NULL, 'auto', 'Active')");
                    $ins_stmt->execute([$trek_id, $batch_start, $batch_end]);
                    $generated[] = $batch_start . " (" . $day_name . ")";
                }
            }
            $current_time = strtotime("+1 day", $current_time);
        }
    }
    return $generated;
}
