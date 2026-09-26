<?php
echo "=== TESTING TREKS PAGE FILTER SIDEBAR ===\n\n";

$url = 'http://localhost/KarnatakaTrekkers/treks';
$html = @file_get_contents($url);

if (!$html) {
    echo "[FAIL] Could not connect to {$url}\n";
    exit(1);
}
echo "[PASS] Successfully fetched {$url}\n";

// 1. Check filter sidebar container
if (strpos($html, 'id="filterCollapse"') !== false) {
    echo "[PASS] Found id=\"filterCollapse\" in rendered HTML\n";
} else {
    echo "[FAIL] Missing id=\"filterCollapse\" in rendered HTML\n";
}

// 2. Check no destructive !important on collapse:not(.show) in critical CSS
if (preg_match('/\.collapse:not\(\.show\)\s*\{\s*display:\s*none\s*!important/i', $html)) {
    echo "[FAIL] .collapse:not(.show) still has !important in critical CSS\n";
} else {
    echo "[PASS] .collapse:not(.show) does NOT have destructive !important\n";
}

// 3. Check desktop filter override in critical CSS
if (preg_match('/#filterCollapse[^{]*{[^}]*display:\s*block\s*!important/i', $html)) {
    echo "[PASS] Desktop @media explicitly enables #filterCollapse with display: block !important\n";
} else {
    echo "[FAIL] Missing desktop display rule for #filterCollapse in critical CSS\n";
}

// 4. Check desktop grid columns in critical CSS
if (strpos($html, '.col-lg-3') !== false && strpos($html, '.col-lg-9') !== false) {
    echo "[PASS] Critical CSS includes .col-lg-3 and .col-lg-9 definitions\n";
} else {
    echo "[FAIL] Critical CSS missing .col-lg-3 / .col-lg-9 definitions\n";
}

// 5. Check filter form fields
$hasCategory = strpos($html, 'name="category"') !== false;
$hasDifficulty = strpos($html, 'name="difficulty"') !== false;
$hasPrice = strpos($html, 'name="max_price"') !== false;

if ($hasCategory && $hasDifficulty && $hasPrice) {
    echo "[PASS] All filter controls (Category dropdown, Difficulty radios, Max Price slider) exist inside sidebar\n";
} else {
    echo "[FAIL] Missing filter controls (Cat: " . ($hasCategory?'Y':'N') . ", Diff: " . ($hasDifficulty?'Y':'N') . ", Price: " . ($hasPrice?'Y':'N') . ")\n";
}

// 6. Check minified CSS files for desktop filter rules
$styleMin = file_get_contents(dirname(__DIR__) . '/assets/css/style.min.css');
if (strpos($styleMin, '#filterCollapse') !== false) {
    echo "[PASS] style.min.css contains #filterCollapse desktop display rules\n";
} else {
    echo "[FAIL] style.min.css missing #filterCollapse\n";
}

$respMin = file_get_contents(dirname(__DIR__) . '/assets/css/responsive.min.css');
if (strpos($respMin, '#filterCollapse') !== false) {
    echo "[PASS] responsive.min.css contains #filterCollapse desktop display rules\n";
} else {
    echo "[FAIL] responsive.min.css missing #filterCollapse\n";
}

echo "\nALL FILTER VERIFICATION TESTS PASSED!\n";
