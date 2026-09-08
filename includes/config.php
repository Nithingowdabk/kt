<?php
/**
 * Karnataka Trekkers Configuration File
 */

// Load Composer autoloader if present
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Force sessions to use cookies only and make them HTTPOnly (prevent JS access)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    $isSecure = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on');
    if ($isSecure) {
        ini_set('session.cookie_secure', 1);
    }
    
    // Configure cookie options (SameSite=Lax to allow session context during payment callbacks)
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Database Configurations (Local vs Production)
$isLocalHost = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $currentHost = $_SERVER['HTTP_HOST'];
    if (strpos($currentHost, 'localhost') !== false || strpos($currentHost, '127.0.0.1') !== false) {
        $isLocalHost = true;
    }
} else {
    // CLI fallback
    $isLocalHost = true;
}

if ($isLocalHost) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'karnataka_trekkers');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u994480942_KT');
    define('DB_PASS', 'Karnatakatrekkers@2025.');
    define('DB_NAME', 'u994480942_KT');
}

// Application Configurations
define('SITE_NAME', 'Karnataka Trekkers');
// Dynamically resolve the base URL to support localhost, ngrok, custom domains, etc.
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'];
    
    // Resolve project subdirectory relative to Document Root dynamically
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $proj_root = str_replace('\\', '/', dirname(__DIR__));
    
    $sub_dir = '';
    if (strpos($proj_root, $doc_root) === 0) {
        $sub_dir = substr($proj_root, strlen($doc_root));
    }
    $sub_dir = trim($sub_dir, '/');
    
    $dynamic_base = $protocol . '://' . $host . ($sub_dir ? '/' . $sub_dir : '');
} else {
    // Fallback for CLI/Cron
    $dynamic_base = 'http://localhost/karnatakatrekkers';
}

define('BASE_URL', $dynamic_base);
define('SITE_URL', BASE_URL); // Maintain compatibility with existing code using SITE_URL
define('CONTACT_EMAIL', 'info.karnatakatrekkers@gmail.com');
define('CONTACT_PHONE', '+91 89512 21179');
define('SITE_PHONE', '+918951221179');
define('CONTACT_ADDRESS', '#42, 3rd Cross, HSR Layout, Sector 2, Bengaluru, Karnataka - 560102');

// Razorpay Payment Gateway API Settings
define('RAZORPAY_KEY_ID', 'rzp_test_SzS3tYXTKOsOwY');
define('RAZORPAY_KEY_SECRET', 'fUt2zyiL0ASgWj7Gx0aNsfIF');
define('RAZORPAY_MODE', 'TEST'); // TEST or LIVE

// WhatsApp Notification Settings
define('WHATSAPP_API_URL', 'https://api.chatprovider.com/send'); // Change to your active API URL
define('WHATSAPP_TOKEN', 'your_whatsapp_token_here');
define('WHATSAPP_ENABLE_NOTIFICATION', true); // Toggle to true to send/log notifications
define('WHATSAPP_LOG_FILE', __DIR__ . '/../whatsapp/log.txt');

// SEO Settings
define('DEFAULT_META_TITLE', 'Karnataka Trekkers - Book Adventure & Nature Treks near Bangalore');
define('DEFAULT_META_DESC', 'Karnataka Trekkers offers guided weekend treks, night treks, and Western Ghats adventure trips with experienced leads, pickup, food, and homestays.');
define('DEFAULT_META_KEYWORDS', 'trekking bangalore, kudremukh trek, kumara parvatha trek, night treks bangalore, western ghats trekking');

// Error Reporting (Development Mode - toggle to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Temporary Test Mode for Development & Testing (Pay on Trek bypass)
define('TEST_MODE', false);