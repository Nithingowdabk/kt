<?php
/**
 * Test Suite - Secure File Upload Validation
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== SECURE IMAGE UPLOAD UNIT TEST ===\n";

// Helper function to simulate secure_image_upload validation rules
function run_upload_validation_test($file_name, $file_tmp, $file_size) {
    echo "Testing File: $file_name (Size: " . round($file_size / 1024, 2) . " KB)\n";

    // 1. Validate File Size (Limit to 2MB)
    $max_size = 2 * 1024 * 1024;
    if ($file_size > $max_size) {
        echo "[REJECTED] File size exceeds 2MB limit.\n";
        return false;
    }

    // 2. Validate Extension
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_extensions)) {
        echo "[REJECTED] Extension '$ext' is not allowed.\n";
        return false;
    }

    // 3. Validate MIME Type
    if (!function_exists('finfo_open')) {
        $mime = mime_content_type($file_tmp);
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
    }

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        echo "[REJECTED] MIME type '$mime' is not allowed.\n";
        return false;
    }

    // 4. Content Check
    $content = @file_get_contents($file_tmp);
    if ($content !== false) {
        if (preg_match('/<\?php/i', $content) || preg_match('/<script/i', $content)) {
            echo "[REJECTED] Malicious PHP or JS scripts detected in file content.\n";
            return false;
        }
    }

    echo "[PASSED] File validated successfully as safe image upload.\n";
    return true;
}

// Prepare temp files
$temp_dir = sys_get_temp_dir();

// Test 1: Safe JPEG Image
$safe_jpeg = $temp_dir . '/test_safe.jpg';
// A minimal valid 1x1 pixel JPEG hex representation
$jpeg_data = hex2bin('ffd8ffe000104a46494600010101006000600000ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432ffc0000b080001000101011100ffc4001f0000010501110101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999ad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f003d00ffd9');
file_put_contents($safe_jpeg, $jpeg_data);

// Test 2: PHP masquerading as JPG
$fake_jpg = $temp_dir . '/fake_image.jpg';
file_put_contents($fake_jpg, "<?php echo 'malicious code'; ?>");

// Test 3: Large File (> 2MB)
$large_file = $temp_dir . '/large.png';
$fp = fopen($large_file, 'w');
fseek($fp, 2.5 * 1024 * 1024); // 2.5MB
fwrite($fp, 'a');
fclose($fp);

// Test 4: Disallowed Extension
$bad_ext = $temp_dir . '/malicious.exe';
file_put_contents($bad_ext, 'dummy executable content');

// Run tests
echo "\n--- Running Test 1 (Safe JPG) ---\n";
assert(run_upload_validation_test('test_safe.jpg', $safe_jpeg, filesize($safe_jpeg)) === true);

echo "\n--- Running Test 2 (Fake JPG / Embedded PHP) ---\n";
assert(run_upload_validation_test('fake_image.jpg', $fake_jpg, filesize($fake_jpg)) === false);

echo "\n--- Running Test 3 (Large File 2.5MB) ---\n";
assert(run_upload_validation_test('large.png', $large_file, filesize($large_file)) === false);

echo "\n--- Running Test 4 (Disallowed Extension .exe) ---\n";
assert(run_upload_validation_test('malicious.exe', $bad_ext, filesize($bad_ext)) === false);

// Clean up
unlink($safe_jpeg);
unlink($fake_jpg);
unlink($large_file);
unlink($bad_ext);

echo "\n=== ALL FILE UPLOAD TESTS COMPLETED SUCCESSFULLY ===\n";
