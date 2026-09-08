<?php
/**
 * Razorpay Payment Callback Handler
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/debug.log');
require_once __DIR__ . '/../includes/config.php';

error_log('--- PAYMENT CALLBACK ACCESS ---');
error_log('SESSION: ' . print_r($_SESSION ?? [], true));
error_log('POST: ' . print_r($_POST, true));

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Razorpay\Api\Api;

$razorpay_order_id = sanitize_input($_POST['razorpay_order_id'] ?? '');
$razorpay_payment_id = sanitize_input($_POST['razorpay_payment_id'] ?? '');
$razorpay_signature = sanitize_input($_POST['razorpay_signature'] ?? '');
$booking_no = sanitize_input($_POST['booking_no'] ?? '');

if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature) || empty($booking_no)) {
    header('Location: ' . SITE_URL . '/booking/failed.php?booking_no=' . $booking_no . '&reason=missing_parameters');
    exit();
}

$key_id = get_setting('razorpay_key', RAZORPAY_KEY_ID);
$key_secret = get_setting('razorpay_secret', RAZORPAY_KEY_SECRET);

$is_valid = false;
try {
    if (defined('TEST_MODE') && TEST_MODE) {
        $is_valid = true;
    } else {
        // 1. Verify Razorpay Payment Signature
        $api = new Api($key_id, $key_secret);
        $attributes = [
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_payment_id' => $razorpay_payment_id,
            'razorpay_signature' => $razorpay_signature
        ];
        $api->utility->verifyPaymentSignature($attributes);
        $is_valid = true;
    }
} catch (Exception $e) {
    $is_valid = false;
    $error_msg = $e->getMessage();
}

if (!$is_valid) {
    header('Location: ' . SITE_URL . '/booking/failed.php?booking_no=' . $booking_no . '&reason=signature_mismatch');
    exit();
}

// 2. Process Secure Booking Confirmation inside Database Transaction
$db = Database::connect();

try {
    $db->beginTransaction();

    // Fetch booking details
    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_no = ? LIMIT 1");
    $stmt->execute([$booking_no]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception("Booking not found in record.");
    }

    // Only process if booking is still pending (avoid duplicate processing on reload)
    if ($booking['booking_status'] === 'Pending') {
        
        // Lock the trek dates row using SELECT FOR UPDATE
        $date_stmt = $db->prepare("SELECT id, status FROM trek_dates WHERE id = ? FOR UPDATE");
        $date_stmt->execute([$booking['trek_date_id']]);
        $trek_date = $date_stmt->fetch();

        if (!$trek_date || $trek_date['status'] !== 'Active') {
            throw new Exception("Selected trek date is no longer active.");
        }

        // Update booking confirmation status
        $update_booking = $db->prepare("UPDATE bookings SET 
            payment_status = 'Paid', 
            booking_status = 'Confirmed', 
            razorpay_payment_id = ?, 
            updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $update_booking->execute([$razorpay_payment_id, $booking['id']]);

        // Log payment in payments table
        $insert_payment = $db->prepare("INSERT INTO payments (booking_id, transaction_no, amount, payment_method, status) VALUES (?, ?, ?, 'Razorpay Gateway', 'Success')");
        $insert_payment->execute([$booking['id'], $razorpay_payment_id, $booking['payable_amount']]);

        log_activity('booking_confirmed_payment', ['booking_no' => $booking_no, 'payment_id' => $razorpay_payment_id, 'amount' => $booking['payable_amount']]);

        $db->commit();

        // Dispatch Payment Received event to Naitrons SaaS
        sendNaitronsEvent('PAYMENT_RECEIVED', [
            'bookingId' => $booking['id'],
            'bookingNo' => $booking_no,
            'transactionNo' => $razorpay_payment_id,
            'amount' => $booking['payable_amount'],
            'phone' => $booking['phone'],
            'name' => $booking['name'],
            'email' => $booking['email'],
        ], $razorpay_payment_id);

        // 3. Dispatch WhatsApp Notification (uses our service wrapper)
        require_once __DIR__ . '/../whatsapp/WhatsAppService.php';
        $whatsapp = new WhatsAppService();
        $whatsapp->sendBookingConfirmation($booking['id']);

    } else {
        // If already paid/confirmed, roll back silently
        $db->rollBack();
    }

    header('Location: ' . SITE_URL . '/booking/success.php?booking_no=' . $booking_no);
    exit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Update booking status to Failed if transaction errored out
    try {
        $fail_stmt = $db->prepare("UPDATE bookings SET payment_status = 'Failed', booking_status = 'Cancelled' WHERE booking_no = ? AND payment_status = 'Pending'");
        $fail_stmt->execute([$booking_no]);
    } catch (Exception $ex) {
        // Ignore secondary error
    }
    header('Location: ' . SITE_URL . '/booking/failed.php?booking_no=' . $booking_no . '&reason=' . urlencode($e->getMessage()));
    exit();
}
