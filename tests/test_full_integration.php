<?php
/**
 * Automated Full Integration Test for KarnatakaTrekkers Upgrades
 */

require_once __DIR__ . '/../includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Running automated integration test...\n";

    // 1. Clean up potential previous leftover test trek
    $db->prepare("DELETE FROM treks WHERE slug = 'test-integration-trek'")->execute();

    // 2. Insert test trek
    $itinerary_json = json_encode([
        [
            'day' => 'Day 0',
            'title' => 'Travel Night',
            'desc' => 'Overnight journey from Bangalore via luxury coach.'
        ],
        [
            'day' => 'Day 1',
            'title' => 'Base Camp & Trek',
            'desc' => 'Arrive at the base camp, have breakfast, and start trek.'
        ]
    ]);

    $stmt = $db->prepare("INSERT INTO treks (
        category_id, title, slug, duration, difficulty, trek_distance, altitude, 
        price, offer_price, with_transport_price, with_transport_offer_price,
        without_transport_price, without_transport_offer_price,
        transport_enabled, own_transport_enabled, own_transport_note, with_transport_note,
        description, itinerary, inclusions, exclusions, 
        with_transport_inclusions, with_transport_exclusions,
        own_transport_inclusions, own_transport_exclusions,
        things_to_carry, pickup_points_txt, status, featured, meta_title, meta_description, image
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        1, // category_id
        'Test Integration Trek',
        'test-integration-trek',
        '2 Days',
        'Moderate',
        '15 km',
        '1500 m',
        2500, // price
        2000, // offer_price
        2500, // with_transport_price
        2000, // with_transport_offer_price
        1500, // without_transport_price
        1200, // without_transport_offer_price
        1, // transport_enabled
        1, // own_transport_enabled
        "Reach base point before 3:30 AM.\nHomestay is located near the temple.", // own_transport_note
        "Transport package departure starts previous night.\nBring original ID proof.", // with_transport_note
        "This is a description for the integration test trek.",
        $itinerary_json,
        'With transport inclusions legacy fallback',
        'With transport exclusions legacy fallback',
        "Transport from Bangalore\nStandard shared accommodation\nCoorg style Breakfast & Dinner",
        "Personal expenses\nLunch on Day 2\nAnything not in inclusions",
        "Standard shared accommodation at base camp\nCoorg style Breakfast & Dinner",
        "Transport of any kind\nPersonal expenses\nLunch on Day 2",
        "Warm clothes, Raincoat, Shoes",
        "Test pickup point details text",
        'active',
        0,
        'Test Meta Title',
        'Test Meta Description',
        'test.jpg'
    ]);

    $trek_id = $db->lastInsertId();
    echo "Test Trek inserted with ID: $trek_id\n";

    // 3. Insert Additional Important Notes
    $db->prepare("INSERT INTO trek_additional_notes (trek_id, note, sort_order) VALUES (?, ?, ?)")
       ->execute([$trek_id, 'Make sure to select previous day date while booking.', 1]);
    $db->prepare("INSERT INTO trek_additional_notes (trek_id, note, sort_order) VALUES (?, ?, ?)")
       ->execute([$trek_id, 'Check temperature conditions before packing.', 2]);

    // 4. Insert Pickup Point with Landmark
    $db->prepare("INSERT INTO pickup_points (trek_id, location, time, landmark) VALUES (?, ?, ?, ?)")
       ->execute([$trek_id, 'Test Station Gate 3', '22:30:00', 'Near the big blue sign board']);

    // 5. Mock request and execute details.php
    $_GET['slug'] = 'test-integration-trek';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = '127.0.0.1';
    $_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs';

    ob_start();
    require __DIR__ . '/../treks/details.php';
    $html = ob_get_clean();

    echo "Generated Details Page size: " . strlen($html) . " bytes\n";

    // 6. Assertions
    $errors = [];

    // Check custom itinerary Day 0
    if (strpos($html, '<span class="itinerary-day-label">Day 0</span>') !== false && strpos($html, 'Travel Night') !== false) {
        echo "[OK] Itinerary Day 0 custom header found.\n";
    } else {
        $errors[] = "Itinerary Day 0 custom header 'Day 0: Travel Night' not found in page HTML.";
    }

    if (strpos($html, '<span class="itinerary-day-label">Day 1</span>') !== false && strpos($html, 'Base Camp') !== false) {
        echo "[OK] Itinerary Day 1 custom header found.\n";
    } else {
        $errors[] = "Itinerary Day 1 custom header 'Day 1: Base Camp & Trek' not found in page HTML.";
    }

    // Check Segmented Inclusions/Exclusions
    if (strpos($html, 'WITH TRANSPORT') !== false && strpos($html, 'WITHOUT TRANSPORT') !== false) {
        echo "[OK] Segmented headings WITH TRANSPORT and WITHOUT TRANSPORT found.\n";
    } else {
        $errors[] = "Segmented headings for packages not found in page HTML.";
    }

    if (strpos($html, 'Transport from Bangalore') !== false) {
        echo "[OK] With Transport Inclusions successfully rendered.\n";
    } else {
        $errors[] = "With Transport Inclusions 'Transport from Bangalore' not found.";
    }

    if (strpos($html, 'Transport of any kind') !== false) {
        echo "[OK] Without Transport Exclusions successfully rendered.\n";
    } else {
        $errors[] = "Without Transport Exclusions 'Transport of any kind' not found.";
    }

    // Check Landmark
    if (strpos($html, 'Landmark:</strong> Near the big blue sign board') !== false) {
        echo "[OK] Pickup Point Landmark successfully rendered.\n";
    } else {
        $errors[] = "Pickup point landmark 'Near the big blue sign board' not found.";
    }

    // Check Additional Important Notes Amber Card
    if (strpos($html, 'Additional Important Notes') !== false && strpos($html, 'Make sure to select previous day date while booking.') !== false) {
        echo "[OK] Additional Important Notes Amber Card successfully rendered.\n";
    } else {
        $errors[] = "Additional Important Notes section or items not found in page HTML.";
    }

    // Check Package Booking Notes wrapper rendering
    if (strpos($html, 'id="with_transport_info_wrapper"') !== false && strpos($html, 'id="own_transport_info_wrapper"') !== false) {
        echo "[OK] Package specific booking note wrappers found in DOM.\n";
    } else {
        $errors[] = "Package specific booking note wrappers not found in DOM.";
    }

    if (strpos($html, 'Transport package departure starts previous night.') !== false) {
        echo "[OK] With Transport Note exists in wrapper.\n";
    } else {
        $errors[] = "With Transport Note text not found in page HTML.";
    }

    if (strpos($html, 'Reach base point before 3:30 AM.') !== false) {
        echo "[OK] Own Transport Note exists in wrapper.\n";
    } else {
        $errors[] = "Own Transport Note text not found in page HTML.";
    }

    // 7. Clean up DB records
    $db->prepare("DELETE FROM treks WHERE slug = 'test-integration-trek'")->execute();
    echo "Test Trek and associated cascading data deleted successfully.\n";

    // Summary of results
    if (empty($errors)) {
        echo "\n>>> INTEGRATION TEST RESULT: SUCCESS (ALL PASSED) <<<\n";
    } else {
        echo "\n>>> INTEGRATION TEST RESULT: FAILED <<<\n";
        foreach ($errors as $err) {
            echo " - $err\n";
        }
    }

} catch (Exception $e) {
    echo "\n>>> INTEGRATION TEST ERROR: " . $e->getMessage() . "\n";
}
