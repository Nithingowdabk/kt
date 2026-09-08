<?php
/**
 * Razorpay Payment SDK Placeholder / Wrapper
 */
require_once __DIR__ . '/../includes/config.php';

class RazorpayIntegration {
    private $key_id;
    private $key_secret;

    public function __construct() {
        $this->key_id = RAZORPAY_KEY_ID;
        $this->key_secret = RAZORPAY_KEY_SECRET;
    }

    /**
     * Create Order placeholder
     */
    public function createOrder($booking_no, $amount_in_rupees) {
        $amount_in_paise = $amount_in_rupees * 100;
        
        // Return mock order parameters for Razorpay checkout integration
        return [
            'id' => 'order_' . bin2hex(random_bytes(6)),
            'entity' => 'order',
            'amount' => $amount_in_paise,
            'currency' => 'INR',
            'receipt' => $booking_no,
            'status' => 'created',
            'created_at' => time()
        ];
    }

    /**
     * Verify payment signature (security check)
     */
    public function verifySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature) {
        // If testing with mock credentials, auto-approve
        if (strpos($this->key_id, 'rzp_test_mock') !== false) {
            return true;
        }

        // Real verification algorithm:
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $this->key_secret);
        return hash_equals($generated_signature, $razorpay_signature);
    }
}
