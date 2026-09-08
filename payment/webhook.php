<?php
/**
 * Razorpay Webhook Handler (Asynchronous Server to Server Alerts)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Razorpay\Api\Api;

// Fetch raw JSON payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo "Invalid Payload";
    exit();
}

$webhook_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
$webhook_secret = get_setting('razorpay_webhook_secret', 'mockWebhookSecret12345');

$key_id = get_setting('razorpay_key', RAZORPAY_KEY_ID);
$key_secret = get_setting('razorpay_secret', RAZORPAY_KEY_SECRET);

$is_verified = false;
try {
    if (defined('TEST_MODE') && TEST_MODE) {
        $is_verified = true;
    } else {
        // Verify Webhook Signature via Razorpay SDK
        $api = new Api($key_id, $key_secret);
        $api->utility->verifyWebhookSignature($payload, $webhook_signature, $webhook_secret);
        $is_verified = true;
    }
} catch (Exception $e) {
    $is_verified = false;
    file_put_contents(__DIR__ . '/webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] VERIFICATION FAILED: " . $e->getMessage() . "\n", FILE_APPEND);
}

if (!$is_verified) {
    http_response_code(400);
    echo "Signature verification failed";
    exit();
}

$event = $data['event'] ?? '';

if ($event === 'order.paid' || $event === 'payment.captured') {
    $entity = $data['payload']['payment']['entity'] ?? [];
    $order_id = $entity['order_id'] ?? '';
    $payment_id = $entity['id'] ?? '';
    $amount = ($entity['amount'] ?? 0) / 100;
    
    $db = Database::connect();
    
    try {
        $db->beginTransaction();
        
        // Find booking matching the order ID
        $stmt = $db->prepare("SELECT * FROM bookings WHERE razorpay_order_id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$order_id]);
        $booking = $stmt->fetch();
        
        if ($booking && $booking['booking_status'] === 'Pending') {
            
            // Lock trek dates row
            $date_stmt = $db->prepare("SELECT id, status FROM trek_dates WHERE id = ? FOR UPDATE");
            $date_stmt->execute([$booking['trek_date_id']]);
            $trek_date = $date_stmt->fetch();
            
            if (!$trek_date || $trek_date['status'] !== 'Active') {
                throw new Exception("Trek date is not active.");
            }
            
            // Update booking details
            $up = $db->prepare("UPDATE bookings SET payment_status = 'Paid', booking_status = 'Confirmed', razorpay_payment_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $up->execute([$payment_id, $booking['id']]);
            
            // Add payment record
            $pay = $db->prepare("INSERT INTO payments (booking_id, transaction_no, amount, payment_method, status) VALUES (?, ?, ?, 'Razorpay Webhook', 'Success')");
            $pay->execute([$booking['id'], $payment_id, $amount]);
            
            log_activity('webhook_confirmed_payment', ['booking_no' => $booking['booking_no'], 'payment_id' => $payment_id, 'amount' => $amount]);

            $db->commit();

            // Dispatch Payment Received event to Naitrons SaaS
            sendNaitronsEvent('PAYMENT_RECEIVED', [
                'bookingId' => $booking['id'],
                'bookingNo' => $booking['booking_no'],
                'transactionNo' => $payment_id,
                'amount' => $amount,
                'phone' => $booking['phone'],
                'name' => $booking['name'],
                'email' => $booking['email'],
            ], $payment_id);
            
            // Dispatch WhatsApp Booking Notification
            require_once __DIR__ . '/../whatsapp/WhatsAppService.php';
            $whatsapp = new WhatsAppService();
            $whatsapp->sendBookingConfirmation($booking['id']);
            
            // Log success
            file_put_contents(__DIR__ . '/webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] SUCCESS: Webhook booking confirmation for Order: $order_id, Booking: " . $booking['id'] . "\n", FILE_APPEND);
        } else {
            $db->rollBack();
            file_put_contents(__DIR__ . '/webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] IGNORED: Webhook for Order: $order_id. Booking already processed or not found.\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        file_put_contents(__DIR__ . '/webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] ERROR: Webhook DB transaction failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

http_response_code(200);
echo "Webhook Handled";
exit();
