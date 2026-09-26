<?php
/**
 * Karnataka Trekkers - Database Cleanup Script for Broken Gallery Records
 * 
 * Usage:
 *   php cleanup_broken_gallery_records.php        (Reports missing gallery records)
 *   php cleanup_broken_gallery_records.php --fix  (Safely deletes orphaned records from DB)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$is_fix_mode = in_array('--fix', $argv ?? []);
$db = Database::connect();

echo "========================================================\n";
echo " Karnataka Trekkers - Gallery Integrity Check & Cleanup\n";
echo " Mode: " . ($is_fix_mode ? "AUTOMATIC FIX (DELETE ORPHANS)" : "DRY RUN (REPORT ONLY)") . "\n";
echo "========================================================\n\n";

$stmt = $db->query("SELECT tg.id, tg.trek_id, tg.image_path, tg.thumbnail_path, t.title, t.slug 
                    FROM trek_gallery tg 
                    LEFT JOIN treks t ON tg.trek_id = t.id 
                    ORDER BY tg.id ASC");
$all_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($all_images);
$valid_count = 0;
$broken_count = 0;
$broken_ids = [];

foreach ($all_images as $row) {
    $img_path = ltrim($row['image_path'] ?? '', '/\\');
    $trek_name = $row['title'] ?? ('Trek #' . $row['trek_id']);
    
    $is_valid = false;
    if (str_starts_with($img_path, 'http://') || str_starts_with($img_path, 'https://')) {
        $is_valid = true;
    } else {
        $full_path = dirname(__DIR__) . '/' . $img_path;
        if (!empty($img_path) && is_file($full_path) && filesize($full_path) > 0) {
            $is_valid = true;
        }
    }
    
    if ($is_valid) {
        $valid_count++;
    } else {
        $broken_count++;
        $broken_ids[] = $row['id'];
        echo "[BROKEN] ID: {$row['id']} | Trek: {$trek_name} ({$row['slug']})\n";
        echo "         Path: {$row['image_path']}\n\n";
    }
}

echo "Summary:\n";
echo " - Total gallery records in DB : {$total}\n";
echo " - Valid images with disk files: {$valid_count}\n";
echo " - Broken / missing image files: {$broken_count}\n\n";

if ($broken_count > 0 && $is_fix_mode) {
    echo "Purging {$broken_count} orphaned records from `trek_gallery`...\n";
    $in_placeholders = implode(',', array_fill(0, count($broken_ids), '?'));
    $del_stmt = $db->prepare("DELETE FROM trek_gallery WHERE id IN ($in_placeholders)");
    $del_stmt->execute($broken_ids);
    echo "Done! Deleted {$broken_count} orphaned records.\n";
} elseif ($broken_count > 0) {
    echo "To remove these orphaned records from the database, run:\n";
    echo "php database/cleanup_broken_gallery_records.php --fix\n";
} else {
    echo "No broken gallery records found in the database!\n";
}
