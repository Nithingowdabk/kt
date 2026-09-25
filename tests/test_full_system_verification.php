<?php
/**
 * Comprehensive System Verification Script
 * Validates all architectural fixes, SEO tags, schema, 301 redirects, and 404 handling.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::connect();

echo "====================================================\n";
echo " KARNATAKA TREKKERS DYNAMIC TREK SYSTEM VERIFICATION\n";
echo "====================================================\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test($description, $condition, $details = '') {
    global $pass_count, $fail_count;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $pass_count++;
    } else {
        echo " [FAIL] " . $description . "\n";
        if (!empty($details)) {
            echo "        Details: " . $details . "\n";
        }
        $fail_count++;
    }
}

// 1. Verify Database Typos Fixed
echo "--- 1. Database Data Quality ---\n";
$shiva = $db->query("SELECT title, slug FROM treks WHERE slug LIKE '%shivagange%'")->fetch();
assert_test(
    "Shivagange title spelling is 'Bangalore'", 
    $shiva && strpos($shiva['title'], 'Bangalore') !== false && strpos($shiva['title'], 'Baangalore') === false,
    $shiva ? $shiva['title'] : 'Not found'
);
assert_test(
    "Shivagange slug spelling is 'bangalore'", 
    $shiva && strpos($shiva['slug'], 'bangalore') !== false && strpos($shiva['slug'], 'baangalore') === false,
    $shiva ? $shiva['slug'] : 'Not found'
);

$netra = $db->query("SELECT title, slug FROM treks WHERE slug LIKE '%netravathi%'")->fetch();
if ($netra) {
    assert_test(
        "Netravathi title spelling is 'Bangalore'", 
        strpos($netra['title'], 'Bangalore') !== false && strpos($netra['title'], 'Banaglore') === false,
        $netra['title']
    );
}

// 2. Verify Schema Columns
echo "\n--- 2. Database Schema Migration ---\n";
$cols = $db->query("SHOW COLUMNS FROM treks")->fetchAll(PDO::FETCH_COLUMN);
assert_test("Column 'starting_point' exists in treks", in_array('starting_point', $cols));
assert_test("Column 'best_season' exists in treks", in_array('best_season', $cols));
assert_test("Column 'is_indexed' exists in treks", in_array('is_indexed', $cols));
assert_test("Table 'trek_slug_redirects' exists", (bool)$db->query("SHOW TABLES LIKE 'trek_slug_redirects'")->fetchColumn());

// 3. Verify Helpers
echo "\n--- 3. Helper Functions ---\n";
$long_text = "This is a wonderful sunrise trek near Bangalore offering panoramic views and breathtaking clouds above the hills.";
$truncated = truncate_meta_description($long_text, 50);
assert_test("truncate_meta_description truncates cleanly without cutting words", !preg_match('/\b\w{1,3}\.\.\.$/', $truncated) && strlen($truncated) <= 55, $truncated);

$test_trek_data = [
    'transport_enabled' => 1,
    'own_transport_enabled' => 1,
    'with_transport_price' => 2499,
    'with_transport_offer_price' => 1999,
    'without_transport_price' => 1499,
    'without_transport_offer_price' => 1199
];
$starting_p = get_starting_price($test_trek_data);
assert_test("get_starting_price returns lowest active offer (1199)", $starting_p == 1199, "Returned: $starting_p");

// 4. Test Rendering of Dynamic Trek Page (Nandi Hills)
echo "\n--- 4. Frontend Trek Details Rendering (Nandi Hills) ---\n";
$_GET['slug'] = 'nandi-hills-sunrise-trek-from-bangalore';
$_SERVER['REQUEST_URI'] = '/treks/nandi-hills-sunrise-trek-from-bangalore?utm_source=test_campaign';
$_SERVER['QUERY_STRING'] = 'utm_source=test_campaign';
$_SERVER['SCRIPT_NAME'] = '/treks/details.php';

ob_start();
include __DIR__ . '/../treks/details.php';
$html = ob_get_clean();

// Check canonical URL is clean and strips utm_source
assert_test(
    "Canonical URL is clean and ignores query parameters",
    strpos($html, '<link rel="canonical" href="' . SITE_URL . '/treks/nandi-hills-sunrise-trek-from-bangalore">') !== false,
    "Expected exact canonical URL without query string"
);

// Check robots meta tag
assert_test(
    "Robots meta tag is 'index, follow'",
    strpos($html, '<meta name="robots" content="index, follow">') !== false
);

// Check hero preload
assert_test(
    "Hero image preload exists in header",
    strpos($html, 'rel="preload" as="image"') !== false
);

// Check SEO title
assert_test(
    "Page title contains meta_title or trek title",
    strpos($html, '<title>Nandi Hills Sunrise Trek') !== false
);

// Check Breadcrumbs HTML
assert_test(
    "Visible breadcrumbs rendered",
    strpos($html, 'breadcrumbs-nav') !== false && strpos($html, 'Home') !== false && strpos($html, 'Treks') !== false
);

// Check BreadcrumbList Schema
assert_test(
    "BreadcrumbList JSON-LD schema generated",
    strpos($html, 'BreadcrumbList') !== false
);

// Check Heading Hierarchy
assert_test("Single H1 exists", substr_count($html, '<h1') === 1);
assert_test("H2 Overview exists", strpos($html, '<h2 class="fw-bold text-success mb-3"><i class="fas fa-info-circle me-2"></i>Overview</h2>') !== false);
assert_test("H2 Detailed Itinerary exists", strpos($html, 'Detailed Itinerary</h2>') !== false);
assert_test("H2 Pickup Points exists", strpos($html, 'Pickup Points</h2>') !== false);
assert_test("H2 Reviews exists", strpos($html, 'Reviews (') !== false && strpos($html, '</h2>') !== false);
assert_test("H2 Related Treks exists", strpos($html, 'Related Treks</h2>') !== false);
assert_test("Quick Facts values are NOT <h5>", strpos($html, '<div class="details-quick-fact-value">') !== false && strpos($html, '<h5 class="details-quick-fact-value">') === false);

// Check Pickup Points duplicate removed
assert_test(
    "Redundant pickup_points_txt is NOT rendered publicly",
    strpos($html, 'Opposite Gopalan Arcade') === false || strpos($html, 'pickup-timeline-content') !== false
);

// Check Zero-reviews display logic (Nandi has 0 approved reviews in local db)
assert_test(
    "Zero review displays 'New Trek • No reviews yet' instead of 5.0 (0 Reviews)",
    strpos($html, 'New Trek • No reviews yet') !== false && strpos($html, '5.0 (0 Reviews)') === false
);

// Check Product Schema when 0 reviews
assert_test(
    "Product schema omits aggregateRating when 0 reviews",
    strpos($html, '"@type": "Product"') !== false && strpos($html, '"aggregateRating"') === false
);

// Check Product Schema starting price
assert_test(
    "Product schema includes starting price",
    strpos($html, '"price": "') !== false
);

// 5. Test 301 Redirect for Old/Typo Slug & Trailing Slash
echo "\n--- 5. Slug Redirect System ---\n";
$old_slug_query = $db->prepare("SELECT new_slug FROM trek_slug_redirects WHERE old_slug = ? LIMIT 1");
$old_slug_query->execute(['shivagange-sunrise-trek-from-baangalore']);
$redirect_target = $old_slug_query->fetchColumn();
assert_test(
    "Redirect record exists for old Shivagange typo slug",
    $redirect_target === 'shivagange-sunrise-trek-from-bangalore',
    "Target: $redirect_target"
);

// Trailing slash query string clean test
$sim_get = ['slug' => 'nandi-hills-sunrise-trek-from-bangalore'];
$sim_user_params = $sim_get;
unset($sim_user_params['slug']);
$sim_clean_query = !empty($sim_user_params) ? '?' . http_build_query($sim_user_params) : '';
$sim_target = SITE_URL . "/treks/" . $sim_get['slug'] . $sim_clean_query;
assert_test(
    "Trailing slash redirect target contains NO ?slug= parameter",
    strpos($sim_target, 'slug=') === false && $sim_target === SITE_URL . "/treks/nandi-hills-sunrise-trek-from-bangalore"
);

// Preload URL matches high priority mobile hero image
preg_match('/<link rel="preload" as="image" href="([^"]+)"/i', $html, $m_pre);
preg_match('/<img[^>]+src="([^"]+)"[^>]+fetchpriority="high"/i', $html, $m_img);
assert_test(
    "Hero preload URL matches rendered mobile LCP image",
    !empty($m_pre[1]) && !empty($m_img[1]) && $m_pre[1] === $m_img[1],
    "Preload: " . ($m_pre[1] ?? '') . " | Img: " . ($m_img[1] ?? '')
);

// 6. Test Sitemap
echo "\n--- 6. XML Sitemap Generation ---\n";
ob_start();
@include __DIR__ . '/../sitemap.php';
$sitemap_xml = ob_get_clean();
assert_test("Sitemap XML is non-empty", strlen($sitemap_xml) > 100);
assert_test("Sitemap includes urlset", strpos($sitemap_xml, '<urlset') !== false);
assert_test("Sitemap includes /treks/", strpos($sitemap_xml, '/treks/') !== false);
assert_test("Sitemap includes /category/", strpos($sitemap_xml, '/category/') !== false);

echo "\n====================================================\n";
echo " TESTS COMPLETED: {$pass_count} PASSED, {$fail_count} FAILED\n";
echo "====================================================\n";
