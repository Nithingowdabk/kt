<?php
/**
 * Karnataka Trekkers - Submit Callback Request Handler (AJAX)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Log incoming request data for debugging
error_log(print_r($_POST, true));
error_log("Incoming Callback Form Data: " . print_r($_POST, true));

$db = Database::connect();

$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$trek_id = !empty($_POST['trek_id']) ? (int)$_POST['trek_id'] : null;
$preferred_date = !empty($_POST['preferred_date']) ? sanitize_input($_POST['preferred_date']) : null;
$participants = !empty($_POST['participants']) ? (int)$_POST['participants'] : null;
$message = !empty($_POST['message']) ? sanitize_input($_POST['message']) : null;

// Validation
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required.']);
    exit();
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Mobile number is required.']);
    exit();
}

// Phone validation (10 digits after normalization)
$clean_phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($clean_phone) === 11 && strpos($clean_phone, '0') === 0) {
    $clean_phone = substr($clean_phone, 1);
} elseif (strlen($clean_phone) === 12 && strpos($clean_phone, '91') === 0) {
    $clean_phone = substr($clean_phone, 2);
}

if (strlen($clean_phone) !== 10) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
    exit();
}

// Fetch Trek Title for notifications
$trek_title = 'General Enquiry';
if ($trek_id) {
    try {
        $trek_stmt = $db->prepare("SELECT title FROM treks WHERE id = ? LIMIT 1");
        $trek_stmt->execute([$trek_id]);
        $fetched_title = $trek_stmt->fetchColumn();
        if ($fetched_title) {
            $trek_title = $fetched_title;
        }
    } catch (PDOException $e) {
        // Ignored
    }
}

try {
    // Calculate priority based on participant count
    $priority = 'Low';
    if ($participants !== null) {
        if ($participants >= 4) {
            $priority = 'High';
        } elseif ($participants >= 2) {
            $priority = 'Medium';
        }
    }

    // Insert into database
    $stmt = $db->prepare("INSERT INTO callback_requests (name, phone, trek_id, preferred_date, participants, message, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'New')");
    $stmt->execute([
        $name,
        $clean_phone,
        $trek_id,
        $preferred_date,
        $participants,
        $message,
        $priority
    ]);

    log_activity('submit_callback_request', [
        'name' => $name,
        'phone' => $clean_phone,
        'trek_id' => $trek_id,
        'priority' => $priority
    ]);

    // Send notifications to Admin
    $admin_email = get_setting('contact_email', 'info.karnatakatrekkers@gmail.com');

    // 1. Email notification
    $email_subject = "📞 New Callback Request: " . $name . " [" . $priority . " Priority]";
    $email_body = "Hello Admin,\n\nA new callback request has been submitted on Karnataka Trekkers website.\n\n" .
                  "Details:\n" .
                  "----------------------------\n" .
                  "Name: $name\n" .
                  "Mobile Number: $clean_phone\n" .
                  "Preferred Trek: $trek_title\n" .
                  "Preferred Date: " . ($preferred_date ? format_date($preferred_date) : 'Not Specified') . "\n" .
                  "Participants: " . ($participants ?: 'Not Specified') . "\n" .
                  "Priority: $priority\n" .
                  "Message: " . ($message ?: 'N/A') . "\n" .
                  "----------------------------\n\n" .
                  "Please log into the Admin Portal to review and update request status.\n\n" .
                  "Warm regards,\nSystem Core";

    @mail($admin_email, $email_subject, $email_body);

    echo json_encode(['success' => true, 'message' => 'Thank you. Our team will contact you shortly.']);
} catch (Exception $e) {
    // Log the error
    error_log("Callback submission insert failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to submit request. Please try again.']);
}
exit();
