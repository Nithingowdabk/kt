<?php
/**
 * Test Trek Categories Section DB Logic
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::connect();
    
    // 1. Fetch active categories
    $cat_stmt = $db->query("SELECT * FROM trek_categories WHERE status = 'Active'");
    $all_active_cats = $cat_stmt->fetchAll();
    echo "Found " . count($all_active_cats) . " active categories in DB.\n";

    // 2. Fetch order from settings
    $order_stmt = $db->prepare("SELECT value_data FROM settings WHERE key_name = ? LIMIT 1");
    $order_stmt->execute(['category_order']);
    $order_setting = $order_stmt->fetchColumn();
    $ordered_ids = !empty($order_setting) ? json_decode($order_setting, true) : [];
    if (!is_array($ordered_ids)) {
        $ordered_ids = [];
    }
    echo "Order settings IDs: " . implode(', ', $ordered_ids) . "\n";

    // 3. Sort active categories
    $category_map = [];
    foreach ($all_active_cats as $cat) {
        $category_map[$cat['id']] = $cat;
    }

    $categories = [];
    foreach ($ordered_ids as $id) {
        if (isset($category_map[$id])) {
            $categories[] = $category_map[$id];
            unset($category_map[$id]);
        }
    }
    foreach ($category_map as $cat) {
        $categories[] = $cat;
    }

    // 4. Fetch all active treks and map counts
    $all_treks_stmt = $db->query("SELECT t.* FROM treks t WHERE t.status = 'Active'");
    $all_treks = $all_treks_stmt->fetchAll();
    
    $category_treks = [];
    foreach ($all_treks as $t) {
        if (!empty($t['category_id'])) {
            $category_treks[$t['category_id']][] = $t;
        }
    }

    echo "\nSorted Categories:\n";
    $shown_categories = 0;
    foreach ($categories as $cat) {
        $cat_id = $cat['id'];
        $trek_count = isset($category_treks[$cat_id]) ? count($category_treks[$cat_id]) : 0;
        
        echo "- " . $cat['category_name'] . " (ID: {$cat_id}, Slug: " . $cat['slug'] . "): " . $trek_count . " active treks";
        if ($trek_count === 0) {
            echo " [HIDDEN]";
        } else {
            $shown_categories++;
        }
        echo "\n";
    }

    echo "\nTotal categories shown: {$shown_categories}\n";
    
    if ($shown_categories > 0) {
        echo "TEST PASSED: Category loading logic behaves as expected.\n";
    } else {
        echo "TEST WARNING: No categories with active treks found. Make sure some treks are set to 'Active' and assigned to active categories.\n";
    }
    
} catch (Exception $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
