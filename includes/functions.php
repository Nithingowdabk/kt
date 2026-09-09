<?php
/**
 * Global Helper Functions
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize user input to prevent XSS
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Generate a URL-friendly slug from string
 */
function create_slug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

/**
 * Format price in Indian Rupees (INR)
 */
function format_price($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Get starting price of a trek (without transport if own transport is enabled, otherwise with transport)
 */
function get_starting_price($trek) {
    if (!$trek) return 0.00;
    
    $has_own_transport = isset($trek['own_transport_enabled']) && (int)$trek['own_transport_enabled'] === 1;
    if ($has_own_transport) {
        $without_price = (float)($trek['without_transport_price'] ?? 0);
        $without_offer = (float)($trek['without_transport_offer_price'] ?? 0);
        
        // Fallback to legacy price * 0.6 if without_transport_price is 0
        if ($without_price <= 0) {
            $legacy_price = (float)($trek['price'] ?? 0);
            $legacy_offer = (float)($trek['offer_price'] ?? 0);
            $without_price = $legacy_price * 0.6;
            $without_offer = $legacy_offer > 0 ? ($legacy_offer * 0.6) : 0;
        }
        
        return ($without_offer > 0) ? $without_offer : $without_price;
    } else {
        $legacy_price = (float)($trek['price'] ?? 0);
        $legacy_offer = (float)($trek['offer_price'] ?? 0);
        return ($legacy_offer > 0) ? $legacy_offer : $legacy_price;
    }
}


/**
 * Format date for user-friendly display
 */
function format_date($date_str, $format = 'd M Y') {
    if (!$date_str) return '';
    $timestamp = strtotime($date_str);
    return date($format, $timestamp);
}

/**
 * Generate a unique Booking Number
 */
function generate_booking_no() {
    return 'KT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Render star ratings using FontAwesome icons
 */
function render_rating_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $stars .= '<i class="far fa-star text-muted"></i>';
        }
    }
    return $stars;
}

/**
 * Set flash session message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'text' => $message
    ];
}

/**
 * Get and display flash session message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $alert_type = $msg['type'];
        if ($alert_type === 'error') $alert_type = 'danger';
        
        return '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">' . 
               $msg['text'] . 
               '<button type="button" class="btn-close" data-bs-dismiss-slide="alert" data-bs-dismiss="alert" aria-label="Close"></button>' . 
               '</div>';
    }
    return '';
}

/**
 * Send WhatsApp Message Mock / Real REST API
 */
function send_whatsapp_message($phone, $message) {
    if (!WHATSAPP_ENABLE_NOTIFICATION) {
        return false;
    }

    // Clean phone number (needs country code, default to 91 if 10 digits)
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean_phone) === 10) {
        $clean_phone = '91' . $clean_phone;
    }

    $log_message = "[" . date('Y-m-d H:i:s') . "] TO: " . $clean_phone . "\nMESSAGE: " . $message . "\n--------------------------------------------------\n";
    
    // Always log message locally first
    $dir = dirname(WHATSAPP_LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(WHATSAPP_LOG_FILE, $log_message, FILE_APPEND);

    // If API endpoint is still using placeholder, return true (mock success)
    if (strpos(WHATSAPP_API_URL, 'chatprovider.com') !== false || empty(WHATSAPP_TOKEN) || WHATSAPP_TOKEN === 'your_whatsapp_token_here') {
        return true; 
    }

    // Real API Call setup
    $payload = json_encode([
        'token' => WHATSAPP_TOKEN,
        'to' => '+' . $clean_phone,
        'body' => $message
    ]);

    $ch = curl_init(WHATSAPP_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200);
}

/**
 * Securely uploads an image file
 * 
 * @param array $file_array The $_FILES['image'] array
 * @param string $target_subfolder Subfolder name in assets/uploads/
 * @return string|false Path to uploaded file relative to root, or false on failure
 */
function secure_image_upload($file_array, $target_subfolder = '') {
    if (!isset($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $file_name = $file_array['name'];
    $file_tmp = $file_array['tmp_name'];
    $file_size = $file_array['size'];

    // 1. Validate File Size (Limit to 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB
    if ($file_size > $max_size) {
        return false;
    }

    // 2. Validate Extension (Strict Whitelist)
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_extensions)) {
        return false;
    }

    // 3. Validate MIME Type (Use finfo to prevent executable uploads masquerading as images)
    if (!function_exists('finfo_open')) {
        $mime = mime_content_type($file_tmp);
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
    }

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return false;
    }

    // 4. Double check content to ensure no php code / script tags exist
    $content = @file_get_contents($file_tmp);
    if ($content !== false) {
        if (preg_match('/<\?php/i', $content) || preg_match('/<script/i', $content)) {
            return false;
        }
    }

    // 5. Generate a unique, randomized filename (prevent overwrite and path traversal)
    $new_name = bin2hex(random_bytes(16)) . '.' . $ext;

    // 6. Set Target Directory
    $upload_base = __DIR__ . '/../assets/uploads/';
    $target_dir = $upload_base;
    if (!empty($target_subfolder)) {
        $target_dir .= trim($target_subfolder, '/') . '/';
    }

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // 7. Perform Upload
    $destination = $target_dir . $new_name;
    if (move_uploaded_file($file_tmp, $destination)) {
        // Automatically generate optimized responsive WebP variants if GD is available
        if (extension_loaded('gd')) {
            $base_name = pathinfo($new_name, PATHINFO_FILENAME);
            $full_webp = $target_dir . $base_name . '.webp';
            $webp_480 = $target_dir . $base_name . '_480.webp';
            $webp_320 = $target_dir . $base_name . '_320.webp';

            // Generate full webp if original is not webp
            if ($ext !== 'webp' && !file_exists($full_webp)) {
                process_and_optimize_image($destination, $full_webp, 1200, 800, 85);
            }
            // Generate 480w and 320w responsive variants
            process_and_optimize_image($destination, $webp_480, 480, 240, 80);
            process_and_optimize_image($destination, $webp_320, 320, 160, 80);
        }

        $relative_path = 'assets/uploads/';
        if (!empty($target_subfolder)) {
            $relative_path .= trim($target_subfolder, '/') . '/';
        }
        return $relative_path . $new_name;
    }

    return false;
}

/**
 * Retrieve system settings from the database (cached per-request)
 * 
 * @param string $key The key name of the setting
 * @param string $default Fallback value if setting is not found
 * @return string The setting value
 */
function get_setting($key, $default = '') {
    static $settings_cache = null;
    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $db = Database::connect();
            $stmt = $db->query("SELECT key_name, value_data FROM settings");
            if ($stmt) {
                while ($row = $stmt->fetch()) {
                    $settings_cache[$row['key_name']] = $row['value_data'];
                }
            }
        } catch (Exception $e) {
            // Silent fallback if database connection is not established yet
        }
    }
    return $settings_cache[$key] ?? $default;
}

/**
 * Log administrative or system activity
 * 
 * @param string $action The action performed
 * @param string|array $details Any extra context/metadata
 * @return bool True on success
 */
function log_activity($action, $details = null) {
    try {
        $db = Database::connect();
        
        $user_id = null;
        $user_type = 'System';
        $username = null;
        
        if (isset($_SESSION['admin_id'])) {
            $user_id = $_SESSION['admin_id'];
            $user_type = 'Admin';
            $username = $_SESSION['admin_name'] ?? 'Admin ID ' . $user_id;
        } elseif (isset($_SESSION['leader_id'])) {
            $user_id = $_SESSION['leader_id'];
            $user_type = 'Trek Leader';
            $username = $_SESSION['leader_name'] ?? 'Leader ID ' . $user_id;
        } elseif (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $user_type = 'User';
            $username = $_SESSION['user_name'] ?? 'User ID ' . $user_id;
        }
        
        if (is_array($details) || is_object($details)) {
            $details = json_encode($details);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_type, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $user_type, $username, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Advanced image processing: Convert to WebP and generate thumbnail
 * 
 * @param string $source_path Full path to source file
 * @param string $dest_path Full path to destination file
 * @param int $max_width Target maximum width
 * @param int $max_height Target maximum height
 * @param int $quality WebP quality (0-100)
 * @return bool True on success
 */
function process_and_optimize_image($source_path, $dest_path, $max_width = 1200, $max_height = 800, $quality = 80) {
    if (!extension_loaded('gd')) return false;

    $info = @getimagesize($source_path);
    if (!$info) return false;

    list($width, $height, $type) = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $image = @imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // Calculate new dimensions maintaining aspect ratio
    $ratio = $width / $height;
    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_height = $max_width / $ratio;
        $new_width = $max_width;
    }

    // Prevent upscaling
    if ($width < $new_width && $height < $new_height) {
        $new_width = $width;
        $new_height = $height;
    }

    $new_image = imagecreatetruecolor((int)round($new_width), (int)round($new_height));

    // Handle transparency
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF || $type == IMAGETYPE_WEBP) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, (int)round($new_width), (int)round($new_height), $transparent);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, (int)round($new_width), (int)round($new_height), $width, $height);

    // Save as WebP
    $success = imagewebp($new_image, $dest_path, $quality);

    imagedestroy($image);
    imagedestroy($new_image);

    return $success;
}

/**
 * Get premium FontAwesome icon class mapping for highlight titles
 */
function get_highlight_icon($title) {
    $title_lower = strtolower($title);
    
    if (strpos($title_lower, 'guide') !== false) {
        return 'fas fa-hiking';
    }
    if (strpos($title_lower, 'leader') !== false || strpos($title_lower, 'expert') !== false) {
        return 'fas fa-user-shield';
    }
    if (strpos($title_lower, 'camp') !== false || strpos($title_lower, 'tent') !== false) {
        return 'fas fa-campground';
    }
    if (strpos($title_lower, 'fire') !== false || strpos($title_lower, 'campfire') !== false) {
        return 'fas fa-fire';
    }
    if (strpos($title_lower, 'waterfall') !== false || strpos($title_lower, 'water') !== false) {
        return 'fas fa-water';
    }
    if (strpos($title_lower, 'sunrise') !== false) {
        return 'fas fa-sun';
    }
    if (strpos($title_lower, 'sunset') !== false) {
        return 'fas fa-mountain';
    }
    if (strpos($title_lower, 'tea') !== false || strpos($title_lower, 'snack') !== false || strpos($title_lower, 'coffee') !== false) {
        return 'fas fa-coffee';
    }
    if (strpos($title_lower, 'zipline') !== false) {
        return 'fas fa-wind';
    }
    if (strpos($title_lower, 'climb') !== false || strpos($title_lower, 'rock') !== false) {
        return 'fas fa-mountain';
    }
    if (strpos($title_lower, 'meal') !== false || strpos($title_lower, 'food') !== false || strpos($title_lower, 'breakfast') !== false || strpos($title_lower, 'dinner') !== false) {
        return 'fas fa-utensils';
    }
    if (strpos($title_lower, 'transport') !== false || strpos($title_lower, 'travel') !== false || strpos($title_lower, 'vehicle') !== false || strpos($title_lower, 'bus') !== false || strpos($title_lower, 'pickup') !== false) {
        return 'fas fa-bus';
    }
    if (strpos($title_lower, 'permit') !== false || strpos($title_lower, 'forest') !== false || strpos($title_lower, 'fees') !== false) {
        return 'fas fa-file-contract';
    }
    if (strpos($title_lower, 'photo') !== false || strpos($title_lower, 'camera') !== false) {
        return 'fas fa-camera';
    }
    if (strpos($title_lower, 'homestay') !== false || strpos($title_lower, 'stay') !== false || strpos($title_lower, 'room') !== false || strpos($title_lower, 'hotel') !== false) {
        return 'fas fa-home';
    }
    if (strpos($title_lower, 'first aid') !== false || strpos($title_lower, 'medical') !== false || strpos($title_lower, 'safety') !== false) {
        return 'fas fa-first-aid';
    }
    
    return 'fas fa-check-circle';
}

/**
 * Formats rich text for notes. Converts newlines to line breaks, 
 * markdown **bold** to <strong>, and lists (lines starting with •, *, -) to HTML <ul><li> structure.
 */
function format_rich_text($text) {
    if (empty($text)) return '';
    
    // Escape standard HTML first to prevent XSS, but allow formatting
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Replace escaped markdown bold **text** with <strong>text</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    
    // Parse lines
    $lines = explode("\n", $text);
    $in_list = false;
    $formatted = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            if ($in_list) {
                $formatted .= "</ul>";
                $in_list = false;
            }
            $formatted .= "<br>";
            continue;
        }
        
        // Check if starts with list marker (e.g. • or * or -)
        if (preg_match('/^(&bull;|[•*\-])\s*(.*)/u', $line, $matches)) {
            if (!$in_list) {
                $formatted .= "<ul class='mb-0' style='padding-left: 1.2rem;'>";
                $in_list = true;
            }
            $formatted .= "<li>" . $matches[2] . "</li>";
        } else {
            if ($in_list) {
                $formatted .= "</ul>";
                $in_list = false;
            }
            $formatted .= "<div>" . $line . "</div>";
        }
    }
    
    if ($in_list) {
        $formatted .= "</ul>";
    }
    
    return $formatted;
}


/**
 * Dispatch an event to Naitrons Automation SaaS Platform with cURL, timeout, retry, and logging.
 *
 * @param string $eventName Event type (e.g., 'LEAD_CREATED', 'TREK_BOOKED', 'PAYMENT_RECEIVED')
 * @param array $payload Event body data
 * @param string|null $externalEventId Optional external event/idempotency key
 * @return bool True if successfully dispatched, false otherwise
 */
function sendNaitronsEvent($eventName, array $payload, $externalEventId = null) {
    // 1. Resolve configuration values
    $apiUrl = get_setting('naitrons_api_url', defined('NAITRONS_API_URL') ? NAITRONS_API_URL : 'http://localhost:4000/api/v1/events');
    $apiKey = get_setting('naitrons_api_key', defined('NAITRONS_API_KEY') ? NAITRONS_API_KEY : '');

    if (empty($apiUrl)) {
        error_log("[Naitrons] Error: Endpoint URL is not configured.");
        return false;
    }

    // Prepare body payload
    $body = [
        'eventType' => $eventName,
        'payload' => $payload
    ];

    if ($externalEventId !== null) {
        $body['externalEventId'] = (string)$externalEventId;
    }

    // Extract phone if present for easier indexing in Naitrons
    if (!empty($payload['phone'])) {
        $body['phone'] = (string)$payload['phone'];
    }

    $jsonData = json_encode($body);
    $headers = [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ];

    // Retry settings
    $maxRetries = 3;
    $retryDelayMs = 500; // milliseconds
    $attempt = 0;
    $success = false;
    $lastError = '';

    while ($attempt < $maxRetries && !$success) {
        $attempt++;
        
        $ch = curl_init($apiUrl);
        if ($ch === false) {
            $lastError = "Failed to initialize cURL handle";
            break;
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Timeout handling
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3 seconds connect timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);        // 6 seconds execution timeout
        
        // SSL verification (Disable ONLY in local development environment if needed)
        if (strpos($apiUrl, 'localhost') !== false || strpos($apiUrl, '127.0.0.1') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $lastError = "cURL error: " . $curlError;
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $success = true;
            break;
        } else {
            $lastError = "HTTP response status code: " . $httpCode . ", Response: " . $response;
            // Don't retry client errors (4xx) except possibly throttled requests (429)
            if ($httpCode >= 400 && $httpCode < 500 && $httpCode !== 429) {
                break;
            }
        }

        if (!$success && $attempt < $maxRetries) {
            usleep($retryDelayMs * 1000); // Backoff before next attempt
        }
    }

    if (!$success) {
        error_log("[Naitrons] Failed to send event '$eventName' after $attempt attempts. Error: $lastError. Payload: $jsonData");
    }

    return $success;
}





