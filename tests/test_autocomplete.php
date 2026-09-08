<?php
/**
 * Automated Test for AJAX Autocomplete Search Endpoint
 */

require_once __DIR__ . '/../includes/config.php';

try {
    // Simulate search query for "n"
    $_GET['q'] = 'n';
    $_SERVER['REQUEST_METHOD'] = 'GET';

    ob_start();
    require __DIR__ . '/../ajax/search_treks.php';
    $output = ob_get_clean();

    echo "Running Autocomplete Endpoint Unit Tests...\n\n";
    echo "Search Output: " . $output . "\n\n";

    // 1. JSON structure assertion
    $data = json_decode($output, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Output is not valid JSON. JSON Error: " . json_last_error_msg());
    }
    echo "[PASSED] Valid JSON returned.\n";

    // 2. Count limit assertion
    if (count($data) > 10) {
        throw new Exception("Returned results exceed limit of 10 items. Count: " . count($data));
    }
    echo "[PASSED] Limit assertion (max 10 items) passed.\n";

    // 3. Output contents validation
    if (count($data) > 0) {
        foreach ($data as $item) {
            if (!isset($item['id']) || !isset($item['title']) || !isset($item['slug'])) {
                throw new Exception("Required fields (id, title, slug) missing from response element: " . json_encode($item));
            }
        }
        echo "[PASSED] Required fields (id, title, slug) exist in all items.\n";
    } else {
        echo "[WARNING] No treks found for query 'n'. Check if seed data exists.\n";
    }

    // 4. Test empty query query
    $_GET['q'] = '';
    ob_start();
    require __DIR__ . '/../ajax/search_treks.php';
    $empty_output = ob_get_clean();
    $empty_data = json_decode($empty_output, true);
    if ($empty_data !== []) {
        throw new Exception("Empty query did not return empty array.");
    }
    echo "[PASSED] Empty query returned empty array.\n";

    echo "\n>>> ALL UNIT TESTS PASSED SUCCESSFULLY <<<\n";

} catch (Exception $e) {
    echo "\n>>> UNIT TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
