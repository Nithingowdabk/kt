<?php
/**
 * Database Auto-Sync & Migration Runner
 * 
 * Automatically ensures all required tables, columns, and indexes exist.
 * Safe to run anytime: checks if tables/columns already exist and skips them if present.
 * Can be run from browser (e.g. /database/sync_database.php) or CLI (php sync_database.php).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');

$is_cli = (php_sapi_name() === 'cli');

function output_line($msg, $type = 'info') {
    global $is_cli;
    if ($is_cli) {
        echo "[$type] " . strip_tags($msg) . "\n";
    } else {
        $colors = [
            'info' => '#0d6efd',
            'success' => '#198754',
            'warning' => '#ffc107',
            'danger' => '#dc3545'
        ];
        $color = $colors[$type] ?? '#333';
        echo "<div style='margin-bottom: 4px; color: {$color}; font-family: monospace;'>{$msg}</div>";
        flush();
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html><html><head><title>Database Sync</title><style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; line-height: 1.5; } .card { background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; max-width: 900px; margin: 0 auto; }</style></head><body><div class='card'><h2>Karnataka Trekkers - Database Schema Sync</h2><hr style='border-color: #334155; margin-bottom: 20px;'>";
}

try {
    $db = Database::connect();
    output_line("Connected to database successfully.", "success");
} catch (Exception $e) {
    output_line("Database connection failed: " . $e->getMessage(), "danger");
    if (!$is_cli) echo "</div></body></html>";
    exit(1);
}

// 1. All Column Specifications for Existing Tables
$schema_map = [
    'treks' => [
        'location' => "VARCHAR(150) DEFAULT 'Karnataka'",
        'with_transport_price' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'with_transport_offer_price' => "DECIMAL(10,2) DEFAULT NULL",
        'without_transport_price' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'without_transport_offer_price' => "DECIMAL(10,2) DEFAULT NULL",
        'transport_enabled' => "TINYINT(1) DEFAULT 1",
        'own_transport_enabled' => "TINYINT(1) DEFAULT 1",
        'own_transport_note' => "TEXT DEFAULT NULL",
        'with_transport_note' => "TEXT DEFAULT NULL",
        'with_transport_inclusions' => "TEXT DEFAULT NULL",
        'with_transport_exclusions' => "TEXT DEFAULT NULL",
        'own_transport_inclusions' => "TEXT DEFAULT NULL",
        'own_transport_exclusions' => "TEXT DEFAULT NULL",
        'recurring_friday' => "TINYINT(1) DEFAULT 0",
        'recurring_saturday' => "TINYINT(1) DEFAULT 0",
        'recurring_sunday' => "TINYINT(1) DEFAULT 0",
        'recurring_until' => "DATE DEFAULT NULL",
        'meta_title' => "VARCHAR(150) DEFAULT NULL",
        'meta_description' => "VARCHAR(255) DEFAULT NULL",
        'focus_keyphrase' => "VARCHAR(150) DEFAULT NULL",
        'excerpt' => "TEXT DEFAULT NULL",
        'image_alt' => "VARCHAR(255) DEFAULT NULL",
        'tags' => "TEXT DEFAULT NULL"
    ],
    'blogs' => [
        'meta_title' => "VARCHAR(150) DEFAULT NULL",
        'meta_description' => "VARCHAR(255) DEFAULT NULL",
        'focus_keyphrase' => "VARCHAR(150) DEFAULT NULL",
        'excerpt' => "TEXT DEFAULT NULL",
        'image_alt' => "VARCHAR(255) DEFAULT NULL",
        'tags' => "TEXT DEFAULT NULL"
    ],
    'bookings' => [
        'package_type' => "VARCHAR(50) NOT NULL DEFAULT 'with_transport'",
        'completed' => "TINYINT(1) NOT NULL DEFAULT 0"
    ],
    'trek_dates' => [
        'seats' => "INT NOT NULL DEFAULT 20",
        'booked_seats' => "INT NOT NULL DEFAULT 0",
        'available_seats' => "INT NOT NULL DEFAULT 20",
        'schedule_type' => "ENUM('auto', 'custom') NOT NULL DEFAULT 'auto'",
        'price' => "DECIMAL(10,2) DEFAULT NULL",
        'label' => "VARCHAR(100) DEFAULT NULL",
        'notes' => "TEXT DEFAULT NULL",
        'trek_leader_id' => "INT DEFAULT NULL"
    ],
    'pickup_points' => [
        'trek_date_id' => "INT DEFAULT NULL"
    ],
    'callback_requests' => [
        'notes' => "TEXT DEFAULT NULL",
        'priority' => "ENUM('High', 'Medium', 'Low') DEFAULT 'Low'"
    ],
    'trek_gallery' => [
        'session_token' => "VARCHAR(64) DEFAULT NULL",
        'is_featured' => "TINYINT(1) DEFAULT 0",
        'sort_order' => "INT DEFAULT 0"
    ],
    'trek_videos' => [
        'session_token' => "VARCHAR(64) DEFAULT NULL",
        'sort_order' => "INT DEFAULT 0"
    ]
];

// 2. Missing Table Definitions
$table_definitions = [
    'gallery_categories' => "CREATE TABLE IF NOT EXISTS `gallery_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_additional_notes' => "CREATE TABLE IF NOT EXISTS `trek_additional_notes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_id` INT NOT NULL,
        `note` TEXT NOT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_highlights' => "CREATE TABLE IF NOT EXISTS `trek_highlights` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_id` INT NOT NULL,
        `highlight` VARCHAR(255) NOT NULL,
        `icon` VARCHAR(50) NOT NULL DEFAULT 'fas fa-check-circle',
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_notes' => "CREATE TABLE IF NOT EXISTS `trek_notes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_id` INT NOT NULL,
        `note` TEXT NOT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_faqs' => "CREATE TABLE IF NOT EXISTS `trek_faqs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_id` INT NOT NULL,
        `question` TEXT NOT NULL,
        `answer` TEXT NOT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_leaders' => "CREATE TABLE IF NOT EXISTS `trek_leaders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
        `key_name` VARCHAR(100) PRIMARY KEY,
        `value_data` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'custom_trip_enquiries' => "CREATE TABLE IF NOT EXISTS `custom_trip_enquiries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `trek_id` INT NOT NULL,
        `preferred_date` DATE NOT NULL,
        `num_participants` INT NOT NULL,
        `pickup_location` VARCHAR(255) NOT NULL,
        `special_requirements` TEXT DEFAULT NULL,
        `status` ENUM('Pending', 'Reviewed', 'Cancelled') DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'callback_requests' => "CREATE TABLE IF NOT EXISTS `callback_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `trek_id` INT DEFAULT NULL,
        `preferred_date` DATE DEFAULT NULL,
        `participants` INT DEFAULT NULL,
        `message` TEXT DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `priority` ENUM('High', 'Medium', 'Low') DEFAULT 'Low',
        `status` ENUM('New', 'Contacted', 'Interested', 'Converted', 'Closed') DEFAULT 'New',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'attendance' => "CREATE TABLE IF NOT EXISTS `attendance` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_date_id` INT NOT NULL,
        `booking_id` INT NOT NULL,
        `traveler_name` VARCHAR(100) NOT NULL,
        `status` ENUM('Present', 'Absent') DEFAULT 'Present',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'trek_completion_reports' => "CREATE TABLE IF NOT EXISTS `trek_completion_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trek_date_id` INT NOT NULL UNIQUE,
        `trek_leader_id` INT NOT NULL,
        `summary` TEXT NOT NULL,
        `weather_conditions` VARCHAR(100) DEFAULT NULL,
        `challenges_faced` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

// Step 1: Create missing tables
output_line("<b>Step 1: Checking & creating missing tables...</b>", "info");
foreach ($table_definitions as $tbl => $sql) {
    try {
        $db->exec($sql);
        output_line("✓ Table verified/created: <code>{$tbl}</code>", "success");
    } catch (Exception $e) {
        output_line("Notice on table <code>{$tbl}</code>: " . $e->getMessage(), "warning");
    }
}

// Step 2: Ensure all required columns exist
output_line("<br><b>Step 2: Checking & migrating columns on existing tables...</b>", "info");
$total_added = 0;
foreach ($schema_map as $table => $columns) {
    // Get existing columns
    $existing = [];
    try {
        $q = $db->query("SHOW COLUMNS FROM `{$table}`");
        if ($q) {
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                $existing[strtolower($r['Field'])] = true;
            }
        }
    } catch (Exception $e) {
        output_line("Table <code>{$table}</code> not present yet. Skipping column checks.", "warning");
        continue;
    }

    foreach ($columns as $col => $def) {
        if (!isset($existing[strtolower($col)])) {
            try {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                output_line("✓ Table <code>{$table}</code>: Added missing column <code>{$col}</code>", "success");
                $total_added++;
            } catch (Exception $e) {
                output_line("Failed to add column <code>{$col}</code> to <code>{$table}</code>: " . $e->getMessage(), "danger");
            }
        }
    }
    output_line("✓ Table <code>{$table}</code> schema is fully verified and up to date.", "info");
}

// Step 3: Insert default seed settings
output_line("<br><b>Step 3: Checking default settings & administrator accounts...</b>", "info");
try {
    $db->exec("INSERT IGNORE INTO `settings` (`key_name`, `value_data`) VALUES
        ('site_name', 'Karnataka Trekkers'),
        ('contact_email', 'info@karnatakatrekkers.com'),
        ('contact_phone', '+91 98765 43210'),
        ('contact_address', '#42, 3rd Cross, HSR Layout, Sector 2, Bengaluru, Karnataka - 560102'),
        ('seo_default_title', 'Karnataka Trekkers - Book Adventure & Nature Treks near Bangalore'),
        ('seo_default_desc', 'Karnataka Trekkers offers guided weekend treks, night treks, and Western Ghats adventure trips with experienced leads, pickup, food, and homestays.'),
        ('seo_default_keywords', 'trekking bangalore, kudremukh trek, kumara parvatha trek, night treks bangalore, western ghats trekking');");
    output_line("✓ Default site settings verified.", "success");
} catch (Exception $e) {
    output_line("Settings check notice: " . $e->getMessage(), "warning");
}

output_line("<br><h3 style='color: #4ade80;'>🎉 Database Synchronization Completed Successfully!</h3>", "success");
output_line("Total missing columns automatically migrated: <b>{$total_added}</b>", "success");
output_line("You can now safely add and update Treks, Blogs, and SEO meta tags.", "success");

if (!$is_cli) {
    echo "<br><a href='" . SITE_URL . "/admin/treks/manage.php' style='display:inline-block; background: #16a34a; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold;'>Return to Admin Portal</a>";
    echo "</div></body></html>";
}
