<?php
/**
 * Test script for Header Announcement Bar database lookup and parsing logic
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = Database::connect();
    echo "Successfully connected to the database.\n";

    // 1. Back up existing settings
    $keys = [
        'announcement_enabled',
        'announcement_messages',
        'announcement_display_type',
        'announcement_link',
        'announcement_bg_color',
        'announcement_text_color'
    ];
    $backup = [];
    foreach ($keys as $key) {
        $stmt = $db->prepare("SELECT value_data FROM settings WHERE key_name = ? LIMIT 1");
        $stmt->execute([$key]);
        $backup[$key] = $stmt->fetchColumn();
    }
    echo "Backed up existing settings.\n";

    // 2. Insert test announcement settings
    $test_settings = [
        'announcement_enabled' => '1',
        'announcement_messages' => "🚍 Free Transport Available\n🌄 Sunrise Treks Every Friday & Saturday\n🔥 Flat ₹500 Off Group Bookings",
        'announcement_display_type' => 'Slider',
        'announcement_link' => 'http://localhost/KarnatakaTrekkers-Copy/treks/index.php',
        'announcement_bg_color' => '#ff0000',
        'announcement_text_color' => '#ffff00'
    ];

    $stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");
    foreach ($test_settings as $key => $val) {
        $stmt->execute([$key, $val, $val]);
        echo "Set {$key} => " . str_replace("\n", "\\n", $val) . "\n";
    }

    // 3. Verify settings retrieval using get_setting()
    // Since this is a clean PHP execution, get_setting static cache is empty and will load the newly inserted values
    echo "\nRetrieving and validating settings via get_setting():\n";
    foreach ($test_settings as $key => $expected) {
        $actual = get_setting($key);
        if ($actual !== $expected) {
            throw new Exception("Mismatch for key '{$key}': Expected '" . str_replace("\n", "\\n", $expected) . "', got '" . str_replace("\n", "\\n", $actual) . "'");
        }
        echo "Assertion passed: {$key} matches expected value.\n";
    }

    // 4. Verify message splitting logic (replicating includes/navbar.php)
    $ann_messages_raw = get_setting('announcement_messages', '');
    $ann_messages = [];
    if (!empty($ann_messages_raw)) {
        $lines = preg_split('/\r\n|\r|\n/', $ann_messages_raw);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $ann_messages[] = $trimmed;
            }
        }
    }

    echo "\nValidating message splitting logic:\n";
    $expected_messages = [
        "🚍 Free Transport Available",
        "🌄 Sunrise Treks Every Friday & Saturday",
        "🔥 Flat ₹500 Off Group Bookings"
    ];

    if (count($ann_messages) !== 3) {
        throw new Exception("Expected 3 parsed messages, got " . count($ann_messages));
    }

    foreach ($expected_messages as $idx => $msg) {
        if ($ann_messages[$idx] !== $msg) {
            throw new Exception("Message index {$idx} mismatch: Expected '{$msg}', got '{$ann_messages[$idx]}'");
        }
        echo "Parsed message {$idx}: '{$ann_messages[$idx]}' [OK]\n";
    }

    // 5. Restore backup values
    echo "\nRestoring backed up settings...\n";
    $restore_stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");
    $delete_stmt = $db->prepare("DELETE FROM settings WHERE key_name = ?");
    foreach ($backup as $key => $val) {
        if ($val === false || $val === null) {
            $delete_stmt->execute([$key]);
        } else {
            $restore_stmt->execute([$key, $val, $val]);
        }
    }
    echo "Restore complete.\n";
    echo "\nALL TESTS PASSED SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "\nTEST FAILED: " . $e->getMessage() . "\n";
    
    // Attempt restore on failure
    if (isset($db) && isset($backup)) {
        echo "Attempting emergency restore of backed up settings...\n";
        try {
            $restore_stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");
            $delete_stmt = $db->prepare("DELETE FROM settings WHERE key_name = ?");
            foreach ($backup as $key => $val) {
                if ($val === false || $val === null) {
                    $delete_stmt->execute([$key]);
                } else {
                    $restore_stmt->execute([$key, $val, $val]);
                }
            }
            echo "Emergency restore complete.\n";
        } catch (Exception $innerEx) {
            echo "Emergency restore failed: " . $innerEx->getMessage() . "\n";
        }
    }
    exit(1);
}
