<?php
/**
 * Karnataka Trekkers - Razorpay Gateway Integration Loader
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/debug.log');
require_once __DIR__ . '/../includes/config.php';

error_log('--- PAYMENT INITIALIZATION ACCESS ---');
error_log('SESSION: ' . print_r($_SESSION ?? [], true));
error_log('POST: ' . print_r($_POST, true));
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['temp_booking'])) {
    error_log('PAYMENT REDIRECT: Request method is ' . $_SERVER['REQUEST_METHOD'] . ', temp_booking isset: ' . (isset($_SESSION['temp_booking']) ? 'yes' : 'no') . ' (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')');
    header('Location: ' . SITE_URL . '/treks/index.php');
    exit();
}

$temp = $_SESSION['temp_booking'];
$discount = (float)($_POST['discount_amount'] ?? 0);
$payable = (float)($_POST['payable_amount'] ?? 0);
$coupon_code = sanitize_input($_POST['applied_coupon'] ?? '');

$db = Database::connect();

try {
    $db->beginTransaction();

    // 1. Generate unique booking reference
    $booking_no = generate_booking_no();

    // 2. Fetch Trek Details securely via prepared statements
    $trek_stmt = $db->prepare("SELECT title, price, offer_price, with_transport_price, with_transport_offer_price, without_transport_price, without_transport_offer_price FROM treks WHERE id = ?");
    $trek_stmt->execute([$temp['trek_id']]);
    $trek = $trek_stmt->fetch();
    
    if (!$trek) {
        throw new Exception("Selected trek does not exist.");
    }
    
    // Double check date active before initiating payment
    $date_stmt = $db->prepare("SELECT price, status FROM trek_dates WHERE id = ? FOR UPDATE");
    $date_stmt->execute([$temp['trek_date_id']]);
    $date_row = $date_stmt->fetch();
    
    if (!$date_row || $date_row['status'] !== 'Active') {
        throw new Exception("The selected trek date is no longer active.");
    }
    
    // Determine price based on selected package
    if (($temp['package_type'] ?? 'with_transport') === 'without_transport') {
        $active_p = $trek['without_transport_offer_price'] > 0 ? $trek['without_transport_offer_price'] : $trek['without_transport_price'];
    } else {
        $active_p = (!empty($date_row['price']) && $date_row['price'] > 0) ? $date_row['price'] : ($trek['with_transport_offer_price'] > 0 ? $trek['with_transport_offer_price'] : $trek['with_transport_price']);
    }
    $subtotal = $active_p * $temp['num_trekkers'];

    if (defined('TEST_MODE') && TEST_MODE) {
        // --- TEST MODE: DIRECT CONFIRMATION (Pay on Trek) ---
        $mock_order_id = 'POT_ORD_' . $booking_no;
        $mock_payment_id = 'POT_PAY_' . $booking_no;

        // 4. Save Booking entry to DB
        $stmt = $db->prepare("INSERT INTO bookings (
            booking_no, user_id, trek_id, trek_date_id, num_trekkers, 
            total_amount, discount_amount, payable_amount, pickup_point_id, 
            payment_status, booking_status, name, email, phone, details, razorpay_order_id, razorpay_payment_id, package_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pay on Trek', 'Confirmed', ?, ?, ?, ?, ?, ?, ?)");
 
        $travelers_json = json_encode($temp['other_travelers'] ?? []);
 
        $stmt->execute([
            $booking_no,
            get_logged_in_user_id(),
            $temp['trek_id'],
            $temp['trek_date_id'],
            $temp['num_trekkers'],
            $subtotal,
            $discount,
            $payable,
            $temp['pickup_point_id'],
            $temp['lead_name'],
            $temp['lead_email'],
            $temp['lead_phone'],
            $travelers_json,
            $mock_order_id,
            $mock_payment_id,
            $temp['package_type'] ?? 'with_transport'
        ]);

        $booking_id = $db->lastInsertId();

        // Dispatch Booking Created & Payment Received events to Naitrons SaaS
        sendNaitronsEvent('BOOKING_CREATED', [
            'bookingId' => $booking_id,
            'bookingNo' => $booking_no,
            'trekTitle' => $trek['title'],
            'amount' => $payable,
            'phone' => $temp['lead_phone'],
            'name' => $temp['lead_name'],
            'email' => $temp['lead_email'],
        ], $booking_no);

        sendNaitronsEvent('PAYMENT_RECEIVED', [
            'bookingId' => $booking_id,
            'bookingNo' => $booking_no,
            'transactionNo' => $mock_payment_id,
            'amount' => $payable,
            'phone' => $temp['lead_phone'],
            'name' => $temp['lead_name'],
            'email' => $temp['lead_email'],
        ], $mock_payment_id);

        // Log payment in payments table
        $insert_payment = $db->prepare("INSERT INTO payments (booking_id, transaction_no, amount, payment_method, status) VALUES (?, ?, ?, 'Pay on Trek', 'Success')");
        $insert_payment->execute([$booking_id, $mock_payment_id, $payable]);

        // Update coupon usages if applied
        if (!empty($coupon_code)) {
            $coupon_stmt = $db->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE code = ?");
            $coupon_stmt->execute([$coupon_code]);
        }

        log_activity('booking_confirmed_test_mode', ['booking_no' => $booking_no, 'payment_id' => $mock_payment_id, 'amount' => $payable]);

        $db->commit();

        // Clear temp session
        unset($_SESSION['temp_booking']);

        // Dispatch WhatsApp Notification (uses our service wrapper)
        require_once __DIR__ . '/../whatsapp/WhatsAppService.php';
        $whatsapp = new WhatsAppService();
        $whatsapp->sendBookingConfirmation($booking_id);

        header('Location: ' . SITE_URL . '/booking/success.php?booking_no=' . $booking_no);
        exit();
    } else {
        // --- NORMAL MODE: RAZORPAY INTEGRATION ---
        // 3. Setup Razorpay API Order
        $key_id = get_setting('razorpay_key', RAZORPAY_KEY_ID);
        $key_secret = get_setting('razorpay_secret', RAZORPAY_KEY_SECRET);
        
        error_log('Razorpay Init: key_id = ' . $key_id . ', amount = ' . round($payable * 100) . ' paise (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')');
        $api = new Razorpay\Api\Api($key_id, $key_secret);
        $order_data = [
            'receipt'         => $booking_no,
            'amount'          => round($payable * 100), // paise
            'currency'        => 'INR'
        ];
        
        error_log('Razorpay Order Request: ' . print_r($order_data, true) . ' (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')');
        $razorpay_order = $api->order->create($order_data);
        error_log('Razorpay Order Response: ' . print_r($razorpay_order, true) . ' (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')');
        $razorpay_order_id = $razorpay_order['id'];

        // 4. Save Booking entry to DB
        $stmt = $db->prepare("INSERT INTO bookings (
            booking_no, user_id, trek_id, trek_date_id, num_trekkers, 
            total_amount, discount_amount, payable_amount, pickup_point_id, 
            payment_status, booking_status, name, email, phone, details, razorpay_order_id, package_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', ?, ?, ?, ?, ?, ?)");
 
        $travelers_json = json_encode($temp['other_travelers'] ?? []);
 
        $stmt->execute([
            $booking_no,
            get_logged_in_user_id(),
            $temp['trek_id'],
            $temp['trek_date_id'],
            $temp['num_trekkers'],
            $subtotal,
            $discount,
            $payable,
            $temp['pickup_point_id'],
            $temp['lead_name'],
            $temp['lead_email'],
            $temp['lead_phone'],
            $travelers_json,
            $razorpay_order_id,
            $temp['package_type'] ?? 'with_transport'
        ]);

        $booking_id = $db->lastInsertId();

        // Dispatch Booking Created event to Naitrons SaaS
        sendNaitronsEvent('BOOKING_CREATED', [
            'bookingId' => $booking_id,
            'bookingNo' => $booking_no,
            'trekTitle' => $trek['title'],
            'amount' => $payable,
            'phone' => $temp['lead_phone'],
            'name' => $temp['lead_name'],
            'email' => $temp['lead_email'],
        ], $booking_no);

        // Update coupon usages if applied
        if (!empty($coupon_code)) {
            $coupon_stmt = $db->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE code = ?");
            $coupon_stmt->execute([$coupon_code]);
        }

        $db->commit();

        // Clear temp session
        unset($_SESSION['temp_booking']);
    }

} catch (Exception $e) {
    error_log('PAYMENT REDIRECT CATCH: Exception: ' . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString() . ' (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')');
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash_message('danger', 'Booking failed: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/treks/index.php');
    exit();
}

$page_title = "Proceed Secure Payment";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding text-center bg-light" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg p-5 bg-white text-center rounded-4">
                    <div class="text-success mb-4">
                        <i class="fas fa-shield-alt fa-3x animate-pulse"></i>
                    </div>
                    
                    <h3 class="fw-bold mb-2">Secure Checkout Session</h3>
                    <p class="text-muted mb-4">You are connecting to the secure Razorpay Payment Gateway. Click the button below to launch the overlay.</p>
                    
                    <div class="border p-3 rounded-3 mb-4 text-start bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Booking Reference:</span>
                            <strong class="text-dark"><?php echo htmlspecialchars($booking_no); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Trek Destination:</span>
                            <strong class="text-dark"><?php echo htmlspecialchars($trek['title']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Package:</span>
                            <strong class="text-dark"><?php echo ($temp['package_type'] ?? 'with_transport') === 'without_transport' ? 'Without Transport' : 'With Transport'; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">No. of Trekkers:</span>
                            <strong class="text-dark"><?php echo $temp['num_trekkers']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="text-muted fw-bold">Total Payable:</span>
                            <strong class="text-success fs-5"><?php echo format_price($payable); ?></strong>
                        </div>
                    </div>

                    <!-- Payment Button -->
                    <button id="btn_rzp_pay" class="btn btn-accent w-100 py-3 shadow fs-6 fw-bold rounded-3">
                        <i class="fas fa-lock me-2"></i>Pay <?php echo format_price($payable); ?>
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="text-muted small text-decoration-none">&larr; Return to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Razorpay Checkout JS Integration -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var options = {
        "key": "<?php echo htmlspecialchars($key_id); ?>",
        "amount": "<?php echo $razorpay_order['amount']; ?>",
        "currency": "INR",
        "name": "<?php echo htmlspecialchars(get_setting('site_name', SITE_NAME)); ?>",
        "description": "Trek Ticket Booking - <?php echo htmlspecialchars($trek['title']); ?>",
        "order_id": "<?php echo $razorpay_order_id; ?>",
        "handler": function (response){
            // Redirect to callback page with payment details for verification
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo SITE_URL; ?>/payment/callback.php';
            
            var fields = {
                'razorpay_order_id': response.razorpay_order_id,
                'razorpay_payment_id': response.razorpay_payment_id,
                'razorpay_signature': response.razorpay_signature,
                'booking_no': '<?php echo $booking_no; ?>'
            };
            
            for (var key in fields) {
                if (fields.hasOwnProperty(key)) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
            }
            
            document.body.appendChild(form);
            form.submit();
        },
        "prefill": {
            "name": "<?php echo htmlspecialchars($temp['lead_name']); ?>",
            "email": "<?php echo htmlspecialchars($temp['lead_email']); ?>",
            "contact": "<?php echo htmlspecialchars($temp['lead_phone']); ?>"
        },
        "theme": {
            "color": "#198754"
        },
        "modal": {
            "ondismiss": function(){
                // On dismiss, redirect to failed page or alert the user
                window.location.href = "<?php echo SITE_URL; ?>/booking/failed.php?booking_no=<?php echo $booking_no; ?>&reason=dismissed";
            }
        }
    };

    var rzp = new Razorpay(options);
    
    // Auto-launch checkout modal
    setTimeout(function() {
        rzp.open();
    }, 1000);

    document.getElementById("btn_rzp_pay").addEventListener("click", function(e) {
        rzp.open();
        e.preventDefault();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
