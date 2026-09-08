<?php
/**
 * Test script for Admin-Managed Testimonial / Review System database operations
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    echo "Successfully connected to the database.\n";

    // Select a trek ID to associate the test review with
    $trek = $db->query("SELECT id, title FROM treks LIMIT 1")->fetch();
    if (!$trek) {
        throw new Exception("No treks found in the database. Please seed treks first.");
    }
    $trek_id = $trek['id'];
    echo "Using Trek: '{$trek['title']}' (ID: {$trek_id}) for testing.\n";

    // 1. Create a manual testimonial (user_id = NULL)
    $test_name = "Test Author Name";
    $test_rating = 5;
    $test_comment = "Breathtaking views and amazing guide coordination!";
    $test_status = "Approved";
    $test_created_at = "2026-05-15 10:30:00";

    $insert_stmt = $db->prepare("INSERT INTO reviews (user_id, trek_id, name, rating, comment, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->execute([$trek_id, $test_name, $test_rating, $test_comment, $test_status, $test_created_at]);
    $new_id = $db->lastInsertId();
    echo "1. Created Test Testimonial (ID: {$new_id}).\n";

    // Verify insertion
    $check_stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
    $check_stmt->execute([$new_id]);
    $review = $check_stmt->fetch();

    if (!$review) {
        throw new Exception("Failed to retrieve the inserted review.");
    }
    if ($review['user_id'] !== null) {
        throw new Exception("Expected user_id to be NULL for manual testimonial.");
    }
    if ($review['name'] !== $test_name || (int)$review['rating'] !== $test_rating || $review['comment'] !== $test_comment || $review['status'] !== $test_status || $review['created_at'] !== $test_created_at) {
        throw new Exception("Inserted values do not match input parameters.");
    }
    echo "Assertion passed: Inserted values match parameters exactly.\n";

    // 2. Update the testimonial details
    $updated_name = "Updated Author Name";
    $updated_rating = 4;
    $updated_comment = "Updated Comment text. Very good experience.";
    $updated_status = "Rejected";
    $updated_created_at = "2026-06-01 12:00:00";

    $update_stmt = $db->prepare("UPDATE reviews SET name = ?, rating = ?, comment = ?, status = ?, created_at = ? WHERE id = ?");
    $update_stmt->execute([$updated_name, $updated_rating, $updated_comment, $updated_status, $updated_created_at, $new_id]);
    echo "2. Updated Test Testimonial details.\n";

    // Verify updates
    $check_stmt->execute([$new_id]);
    $review = $check_stmt->fetch();
    if ($review['name'] !== $updated_name || (int)$review['rating'] !== $updated_rating || $review['comment'] !== $updated_comment || $review['status'] !== $updated_status || $review['created_at'] !== $updated_created_at) {
         throw new Exception("Updated values do not match updated parameters in the DB.");
    }
    echo "Assertion passed: Updated values match parameters exactly.\n";

    // 3. Delete the testimonial
    $delete_stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    $delete_stmt->execute([$new_id]);
    echo "3. Deleted Test Testimonial.\n";

    // Verify deletion
    $check_stmt->execute([$new_id]);
    if ($check_stmt->fetch()) {
        throw new Exception("Review was not successfully deleted.");
    }
    echo "Assertion passed: Review was successfully deleted from database.\n";

    echo "\nALL DATABASE TESTS PASSED SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "\nTEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
