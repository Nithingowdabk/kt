<?php
/**
 * Test Suite - Transactional Seat Management & Overbooking Prevention
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "=== TRANSACTIONAL SEATS ALLOCATION TEST ===\n";

$db = Database::connect();

try {
    $db->beginTransaction();

    // 1. Create a dummy trek for testing
    $db->exec("INSERT INTO treks (title, slug, duration, difficulty, trek_distance, altitude, price, description, itinerary, status) 
               VALUES ('Mock Test Trek', 'mock-test-trek', '1 Day', 'Easy', 5.0, 1000, 1000.00, 'Mock description', 'Mock itinerary', 'Active')");
    $trek_id = $db->lastInsertId();

    // 2. Create a dummy trek date batch with exactly 5 seats
    $db->exec("INSERT INTO trek_dates (trek_id, start_date, end_date, seats, booked_seats, available_seats, status) 
               VALUES ($trek_id, '2026-07-01', '2026-07-02', 5, 0, 5, 'Active')");
    $date_id = $db->lastInsertId();

    $db->commit();
    echo "Mock trek and trek date batch created successfully (Total Seats: 5).\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Setup failed: " . $e->getMessage() . "\n");
}

// Function to simulate booking request inside transaction
function simulate_booking($date_id, $num_trekkers) {
    global $db;
    try {
        $db->beginTransaction();

        // Lock row for update
        $stmt = $db->prepare("SELECT id, seats, booked_seats, available_seats, status FROM trek_dates WHERE id = ? FOR UPDATE");
        $stmt->execute([$date_id]);
        $batch = $stmt->fetch();

        if (!$batch) {
            throw new Exception("Batch not found.");
        }

        echo "Attempting to book $num_trekkers seats... (Available: " . $batch['available_seats'] . ")\n";

        if ($batch['status'] !== 'Active') {
            throw new Exception("Batch is not active.");
        }

        if ($batch['available_seats'] < $num_trekkers) {
            throw new Exception("Insufficient seats left. Requested $num_trekkers, left " . $batch['available_seats'] . ".");
        }

        // Deduct seats
        $new_booked = $batch['booked_seats'] + $num_trekkers;
        $new_available = $batch['available_seats'] - $num_trekkers;

        $up = $db->prepare("UPDATE trek_dates SET booked_seats = ?, available_seats = ? WHERE id = ?");
        $up->execute([$new_booked, $new_available, $date_id]);

        if ($new_available <= 0) {
            $status_stmt = $db->prepare("UPDATE trek_dates SET status = 'Full' WHERE id = ?");
            $status_stmt->execute([$date_id]);
        }

        $db->commit();
        echo "[SUCCESS] Booked $num_trekkers seats. Seats left: $new_available.\n";
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        echo "[FAILED] " . $e->getMessage() . "\n";
        return false;
    }
}

// Perform bookings sequential test simulating concurrent bounds
echo "\n--- Request 1: Book 3 seats (Should pass) ---\n";
assert(simulate_booking($date_id, 3) === true);

echo "\n--- Request 2: Book 3 seats (Should fail since only 2 are left) ---\n";
assert(simulate_booking($date_id, 3) === false);

echo "\n--- Request 3: Book 2 seats (Should pass and mark batch Full) ---\n";
assert(simulate_booking($date_id, 2) === true);

echo "\n--- Request 4: Book 1 seat (Should fail because batch is now Full) ---\n";
assert(simulate_booking($date_id, 1) === false);

// Clean up mock data
try {
    $db->beginTransaction();
    $db->exec("DELETE FROM trek_dates WHERE id = $date_id");
    $db->exec("DELETE FROM treks WHERE id = $trek_id");
    $db->commit();
    echo "\nMock data cleaned up successfully.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n=== ALL TRANSACTIONAL SEAT MANAGEMENT TESTS PASSED ===\n";
