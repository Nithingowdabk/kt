<?php
/**
 * Karnataka Trekkers - Booking Checkout & Coupon Verification
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$db = Database::connect();

// 1. AJAX CALL - Apply Coupon
if (isset($_GET['action']) && $_GET['action'] === 'apply_coupon') {
    header('Content-Type: application/json');
    $coupon_code = sanitize_input($_POST['coupon_code'] ?? '');
    $subtotal = (float)($_POST['subtotal'] ?? 0);

    if (empty($coupon_code)) {
        echo json_encode(['success' => false, 'message' => 'Empty coupon code.']);
        exit();
    }

    try {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'Active' AND expiry_date >= CURDATE() LIMIT 1");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
            exit();
        }

        if ($subtotal < $coupon['min_booking_amount']) {
            echo json_encode(['success' => false, 'message' => 'Minimum booking amount to use this coupon is ' . format_price($coupon['min_booking_amount'])]);
            exit();
        }

        if ($coupon['max_uses'] > 0 && $coupon['current_uses'] >= $coupon['max_uses']) {
            echo json_encode(['success' => false, 'message' => 'This coupon code usage limit has been reached.']);
            exit();
        }

        // Calculate discount
        $discount = 0.00;
        if ($coupon['discount_type'] === 'Percentage') {
            $discount = ($subtotal * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }

        // Coupon discount cannot be more than subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Coupon code applied successfully! ' . ($coupon['discount_type'] === 'Percentage' ? $coupon['discount_value'] . '%' : format_price($coupon['discount_value'])) . ' discount.',
            'discount_amount' => round($discount, 2)
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        exit();
    }
}

// 2. Normal Post handling - Collect traveler lists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    if (!isset($_SESSION['temp_booking'])) {
        header('Location: ' . SITE_URL . '/treks/index.php');
        exit();
    }

    $travelers = [];
    $names = $_POST['traveler_name'] ?? [];
    $ages = $_POST['traveler_age'] ?? [];
    $genders = $_POST['traveler_gender'] ?? [];

    for ($i = 0; $i < count($names); $i++) {
        if (!empty($names[$i])) {
            $travelers[] = [
                'name' => sanitize_input($names[$i]),
                'age' => (int)$ages[$i],
                'gender' => sanitize_input($genders[$i])
            ];
        }
    }

    $_SESSION['temp_booking']['lead_name'] = sanitize_input($_POST['name'] ?? '');
    $_SESSION['temp_booking']['lead_email'] = sanitize_input($_POST['email'] ?? '');
    $_SESSION['temp_booking']['lead_phone'] = sanitize_input($_POST['phone'] ?? '');
    $_SESSION['temp_booking']['other_travelers'] = $travelers;
}

// Redirect if no session exists
if (!isset($_SESSION['temp_booking']) || !isset($_SESSION['temp_booking']['lead_name'])) {
    header('Location: ' . SITE_URL . '/treks/index.php');
    exit();
}

$temp = $_SESSION['temp_booking'];

try {
    // Retrieve details for summary display
    $trek = $db->query("SELECT * FROM treks WHERE id = " . (int)$temp['trek_id'])->fetch();
    $date = $db->query("SELECT * FROM trek_dates WHERE id = " . (int)$temp['trek_date_id'])->fetch();
    
    $pickup = null;
    if ($temp['package_type'] === 'with_transport' && !empty($temp['pickup_point_id'])) {
        $pickup = $db->query("SELECT * FROM pickup_points WHERE id = " . (int)$temp['pickup_point_id'])->fetch();
    }
    
    if ($temp['package_type'] === 'without_transport') {
        $unit_price = $trek['without_transport_offer_price'] > 0 ? $trek['without_transport_offer_price'] : $trek['without_transport_price'];
    } else {
        $unit_price = (!empty($date['price']) && $date['price'] > 0) ? $date['price'] : ($trek['with_transport_offer_price'] > 0 ? $trek['with_transport_offer_price'] : $trek['with_transport_price']);
    }
    $subtotal = $unit_price * $temp['num_trekkers'];
} catch (PDOException $e) {
    die("Database fetch error: " . $e->getMessage());
}

$extra_js = ['assets/js/booking.js'];
$page_title = "Checkout Confirmation";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Banner -->
<section class="bg-success text-white py-4 mb-4">
    <div class="container text-center">
        <h2 class="fw-bold mb-0">Booking Checkout</h2>
        <p class="mb-0 text-white-50">Review details and complete payment</p>
    </div>
</section>

<!-- Main checkout grid -->
<section class="mb-5">
    <div class="container">
        <div class="row g-4">
            
            <!-- Summary Review -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="fw-bold text-success mb-3 border-bottom pb-2">Review Traveler Information</h4>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Lead Traveler</span>
                            <strong><?php echo htmlspecialchars($temp['lead_name']); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Email Address</span>
                            <strong><?php echo htmlspecialchars($temp['lead_email']); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">WhatsApp Mobile</span>
                            <strong><?php echo htmlspecialchars($temp['lead_phone']); ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($temp['other_travelers'])): ?>
                        <h5 class="fw-bold text-dark mt-4 mb-2">Co-Trekkers</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm text-muted">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($temp['other_travelers'] as $idx => $t): ?>
                                        <tr>
                                            <td><?php echo $idx + 2; ?></td>
                                            <td><?php echo htmlspecialchars($t['name']); ?></td>
                                            <td><?php echo htmlspecialchars($t['age']); ?></td>
                                            <td><?php echo htmlspecialchars($t['gender']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Coupon section -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-ticket-alt text-success me-2"></i>Apply Coupon Code</h5>
                    <div class="row g-2">
                        <div class="col-md-9 col-8">
                            <input type="text" id="coupon_code_input" class="form-control text-uppercase" placeholder="Enter code (e.g. TREK10, WELCOME500)">
                        </div>
                        <div class="col-md-3 col-4">
                            <button id="btn_apply_coupon" class="btn btn-primary-custom w-100">Apply</button>
                        </div>
                    </div>
                    <div id="coupon_success_msg" class="text-success small mt-2 d-none"></div>
                    <div id="coupon_error_msg" class="text-danger small mt-2 d-none"></div>
                </div>

                <!-- Payment form -->
                <div class="card border-0 shadow-sm p-4 bg-light text-center">
                    <?php if (defined('TEST_MODE') && TEST_MODE): ?>
                        <h5 class="fw-bold mb-3 text-warning"><i class="fas fa-tools me-2"></i>Test Mode: Pay on Trek</h5>
                        <p class="text-muted small">Redirection to Razorpay will be skipped. Clicking the button below will immediately create and confirm your booking.</p>
                    <?php else: ?>
                        <h5 class="fw-bold mb-3">Complete Secure Payment</h5>
                        <p class="text-muted small">We support Credit Card, Debit Card, Net Banking, and UPI payments via Razorpay.</p>
                    <?php endif; ?>
                    
                    <form action="<?php echo SITE_URL; ?>/booking/payment.php" method="POST">
                        <!-- Calculations variables -->
                        <input type="hidden" name="discount_amount" id="discount_amount_val" value="0.00">
                        <input type="hidden" name="payable_amount" id="payable_amount_val" value="<?php echo number_format($subtotal, 2, '.', ''); ?>">
                        <input type="hidden" name="applied_coupon" id="applied_coupon_code" value="">

                        <?php if (defined('TEST_MODE') && TEST_MODE): ?>
                            <button type="submit" class="btn btn-success px-5 py-3 fs-5 shadow">
                                <i class="fas fa-check-circle me-2"></i>Proceed Booking (Pay on Trek)
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-accent px-5 py-3 fs-5 shadow">
                                <i class="fas fa-lock me-2"></i>Pay Now with Razorpay
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 payment-summary-sticky">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Order Calculations</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex justify-content-between text-muted">
                            <span>Trek:</span>
                            <span><?php echo htmlspecialchars($trek['title']); ?></span>
                        </li>
                        <li class="mb-2 d-flex justify-content-between text-muted">
                            <span>Trekkers Count:</span>
                            <span><?php echo $temp['num_trekkers']; ?></span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between text-muted">
                            <span>Pickup:</span>
                            <span><?php echo $pickup ? htmlspecialchars($pickup['location']) : 'Own Transport Selected'; ?></span>
                        </li>
                        
                        <li class="border-top pt-3 mb-2 d-flex justify-content-between text-muted">
                            <span>Subtotal:</span>
                            <span id="summary_subtotal"><?php echo format_price($subtotal); ?></span>
                        </li>
                        
                        <!-- Discount row (hidden by default) -->
                        <li class="mb-2 d-flex justify-content-between text-danger d-none" id="summary_discount_row">
                            <span>Discount:</span>
                            <span id="summary_discount">-₹0.00</span>
                        </li>

                        <li class="border-top pt-3 d-flex justify-content-between fw-bold text-success fs-4">
                            <span>Total Payable:</span>
                            <span id="summary_payable"><?php echo format_price($subtotal); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
