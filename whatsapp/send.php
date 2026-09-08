<?php
/**
 * Standalone WhatsApp Notification Dispatcher / Trigger Endpoint
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow execution via command line or REST request
if (php_sapi_name() === 'cli') {
    // CLI execution
    if ($argc < 3) {
        echo "Usage: php send.php <phone> <message>\n";
        exit(1);
    }
    $phone = $argv[1];
    $message = $argv[2];
    $status = send_whatsapp_message($phone, $message);
    echo $status ? "Notification sent successfully (logged).\n" : "Sending failed.\n";
    exit(0);
}

// REST GET/POST API access
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($phone) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Missing phone or message body.']);
        exit();
    }

    $status = send_whatsapp_message($phone, $message);
    echo json_encode(['success' => $status, 'message' => $status ? 'Message logged/sent.' : 'Sending failed.']);
    exit();
}
?>
