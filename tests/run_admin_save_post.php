<?php
/**
 * Test script to programmatically simulate admin schedule POST submission
 */
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['trek_id'] = 1;
$_POST['save_recurring_schedule'] = 1;
$_POST['recurring_friday'] = 1;
$_POST['recurring_saturday'] = 1;
$_POST['recurring_sunday'] = 1;
$_POST['recurring_until'] = '2026-07-15';

// Start and mock session for admin login check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';
$_SESSION['admin_role'] = 'Super Admin';

// Clean up existing auto-generated trek dates for Trek 1
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
$db = Database::connect();
$db->exec("DELETE FROM trek_dates WHERE trek_id = 1 AND schedule_type = 'auto'");
echo "Cleaned up old auto-generated dates for Trek 1.\n";

echo "Simulating POST request to save_recurring_schedule for Trek 1 (Saturday only)...\n";
echo "Params: Friday=0, Saturday=1, Until=2026-07-15\n";

// Require the schedules.php page to execute the POST handler
require_once __DIR__ . '/../admin/treks/schedules.php';
echo "POST execution done.\n";
