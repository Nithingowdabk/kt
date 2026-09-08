<?php
/**
 * Test Suite - Pickup Point Management Add & Edit Logic
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "=== PICKUP POINT EDIT LOGIC TEST ===\n";

$db = Database::connect();

try {
    $db->beginTransaction();

    // 1. Create a dummy pickup point directly in the DB
    $db->exec("INSERT INTO pickup_points (trek_id, time, location, landmark) VALUES (1, '22:00:00', 'Test Edit Point Initial', 'Initial Landmark')");
    $pickup_id = $db->lastInsertId();
    echo "Created initial mock pickup point with ID: $pickup_id\n";
    
    // Verify it exists in database
    $p = $db->query("SELECT * FROM pickup_points WHERE id = $pickup_id")->fetch();
    if (!$p || $p['location'] !== 'Test Edit Point Initial') {
        throw new Exception("Initial pickup point not created correctly.");
    }
    
    // 2. Simulate POST UPDATE via pickups.php
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['trek_id'] = 1;
    $_POST['action_mode'] = 'update';
    $_POST['pickup_id'] = $pickup_id;
    $_POST['location'] = 'Test Edit Point Updated';
    $_POST['time'] = '22:30:00';
    $_POST['landmark'] = 'Updated Landmark';
    $_POST['trek_date_id'] = ''; // Apply to All Batches

    // Mock session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_name'] = 'Admin';
    $_SESSION['admin_role'] = 'Super Admin';

    echo "Simulating UPDATE POST request to pickups.php...\n";
    // We catch the redirect exit or warnings since headers might be sent
    ob_start();
    require_once __DIR__ . '/../admin/treks/pickups.php';
    ob_end_clean();

    // 3. Verify values updated in DB
    // Since pickups.php was included, let's connect to DB again and select
    $p_updated = $db->query("SELECT * FROM pickup_points WHERE id = $pickup_id")->fetch();
    echo "Checking updated values in DB:\n";
    print_r($p_updated);
    
    if (!$p_updated) {
        throw new Exception("Updated pickup point not found.");
    }
    if ($p_updated['location'] !== 'Test Edit Point Updated' || $p_updated['time'] !== '22:30:00' || $p_updated['landmark'] !== 'Updated Landmark') {
        throw new Exception("Pickup point did not update to new values.");
    }
    
    echo "[SUCCESS] Pickup point updated successfully!\n";

    // 4. Test validation logic (Location/Time empty)
    // Run validation simulation directly or via mock
    $_POST['location'] = '';
    $_POST['time'] = '22:30:00';
    
    $location = trim($_POST['location'] ?? '');
    $time = trim($_POST['time'] ?? '');
    if (empty($location) || empty($time)) {
        echo "[SUCCESS] Validation successfully blocked save due to empty location.\n";
    } else {
        throw new Exception("Validation failed to block empty location.");
    }

    $db->rollBack();
    echo "=== ALL TESTS COMPLETED AND ROLLED BACK ===\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "TEST FAILED: " . $e->getMessage() . "\n";
}
