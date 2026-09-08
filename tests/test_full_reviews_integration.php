<?php
/**
 * Test integration of review insertion, edit, details page retrieval, and initials generation.
 * Uses HTTP client requests to test the running Apache server.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    echo "Successfully connected to the database.\n";

    // Select first trek ID
    $trek = $db->query("SELECT id, slug, title FROM treks LIMIT 1")->fetch();
    if (!$trek) {
        throw new Exception("No treks found in the database. Please seed treks first.");
    }
    $trek_id = $trek['id'];
    $trek_slug = $trek['slug'];

    echo "Using Trek: '{$trek['title']}' (Slug: {$trek_slug}) for simulation.\n";

    // 1. Directly insert a manual testimonial in the DB (Simulating admin/reviews/add.php form submission)
    $test_name = "Jane Smith";
    $test_rating = 5;
    $test_comment = "Breathtaking views and amazing guide coordination!";
    $test_status = "Approved";
    $test_created_at = "2026-05-15 10:30:00";

    echo "Inserting test testimonial in database...\n";
    $insert_stmt = $db->prepare("INSERT INTO reviews (user_id, trek_id, name, rating, comment, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->execute([$trek_id, $test_name, $test_rating, $test_comment, $test_status, $test_created_at]);
    $review_id = $db->lastInsertId();
    echo "Testimonial inserted (ID: {$review_id}).\n";

    // 2. Fetch the frontend trek details page via HTTP request
    $url = "http://localhost/KarnatakaTrekkers-Copy/treks/details.php?slug=" . $trek_slug;
    echo "Fetching page: {$url} ...\n";
    $details_html = file_get_contents($url);

    if ($details_html === false) {
        throw new Exception("Failed to fetch the trek details page via HTTP.");
    }

    // Search for review contents in output html
    if (strpos($details_html, 'Jane Smith') === false) {
        throw new Exception("Jane Smith author name not found on trek details page.");
    }
    echo "Assertion passed: Author name 'Jane Smith' found on details page.\n";

    if (strpos($details_html, 'Breathtaking views and amazing guide coordination!') === false) {
        throw new Exception("Testimonial comment not found on trek details page.");
    }
    echo "Assertion passed: Testimonial comment found on details page.\n";

    // Check initials generation output "JS"
    if (strpos($details_html, '<div class="review-avatar">JS</div>') === false) {
        throw new Exception("Expected avatar initials 'JS' not found on trek details page.");
    }
    echo "Assertion passed: Avatar initials 'JS' rendered correctly.\n";

    // 3. Edit the testimonial directly in the DB (Simulating admin/reviews/add.php edit action)
    $updated_name = "Jane Smith-Doe";
    $updated_rating = 4;
    $updated_comment = "Breathtaking views and amazing guide coordination! Homestay was wonderful.";
    $updated_status = "Approved";

    echo "Updating testimonial in database...\n";
    $update_stmt = $db->prepare("UPDATE reviews SET name = ?, rating = ?, comment = ?, status = ? WHERE id = ?");
    $update_stmt->execute([$updated_name, $updated_rating, $updated_comment, $updated_status, $review_id]);

    // Fetch details page again
    echo "Re-fetching page to verify updates...\n";
    $details_html = file_get_contents($url);

    if (strpos($details_html, 'Jane Smith-Doe') === false) {
        throw new Exception("Updated author name 'Jane Smith-Doe' not found on trek details page.");
    }
    echo "Assertion passed: Updated author name 'Jane Smith-Doe' found.\n";

    if (strpos($details_html, 'Homestay was wonderful.') === false) {
        throw new Exception("Updated comment suffix not found on trek details page.");
    }
    echo "Assertion passed: Updated comment content found.\n";

    // Check updated initials "JS"
    if (strpos($details_html, '<div class="review-avatar">JS</div>') === false) {
        throw new Exception("Expected avatar initials 'JS' for 'Jane Smith-Doe' not found on trek details page.");
    }
    echo "Assertion passed: Avatar initials 'JS' rendered correctly for 'Jane Smith-Doe'.\n";

    // 4. Delete the review (Simulating admin/reviews/manage.php delete action)
    echo "Deleting testimonial in database...\n";
    $delete_stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    $delete_stmt->execute([$review_id]);

    // Fetch details page again to verify removal
    echo "Re-fetching page to verify removal...\n";
    $details_html = file_get_contents($url);

    if (strpos($details_html, 'Jane Smith-Doe') !== false) {
        throw new Exception("Deleted author name 'Jane Smith-Doe' was still found on trek details page.");
    }
    echo "Assertion passed: Review successfully removed from details page.\n";

    echo "\nALL TESTIMONIAL INTEGRATION TESTS PASSED SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "\nINTEGRATION TEST FAILED: " . $e->getMessage() . "\n";
    
    // Clean up if review was left behind
    if (isset($db) && isset($review_id)) {
        $db->exec("DELETE FROM reviews WHERE id = {$review_id}");
    }
    exit(1);
}
