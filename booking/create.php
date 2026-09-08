<?php
/**
 * Karnataka Trekkers - Booking Details Collection
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/debug.log');
require_once __DIR__ . '/../includes/config.php';

error_log('--- CREATE BOOKING ACCESS ---');
error_log('SESSION: ' . print_r($_SESSION ?? [], true));
error_log('POST: ' . print_r($_POST, true));
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle initial post from trek details page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_type = sanitize_input($_POST['package_type'] ?? 'with_transport');
    $_SESSION['temp_booking'] = [
        'trek_id' => (int)$_POST['trek_id'],
        'trek_date_id' => (int)$_POST['trek_date_id'],
        'num_trekkers' => (int)$_POST['num_trekkers'],
        'pickup_point_id' => isset($_POST['pickup_point_id']) && $_POST['pickup_point_id'] !== '' ? (int)$_POST['pickup_point_id'] : null,
        'package_type' => $package_type
    ];
}

// Enforce login (Must be done AFTER processing POST to save booking context in session before redirection)
require_user_login();

$db = Database::connect();


// Redirect if no session exists
if (!isset($_SESSION['temp_booking'])) {
    header('Location: ' . SITE_URL . '/treks/index.php');
    exit();
}

$temp = $_SESSION['temp_booking'];

try {
    // Fetch Trek Details
    $trek_stmt = $db->prepare("SELECT * FROM treks WHERE id = ?");
    $trek_stmt->execute([$temp['trek_id']]);
    $trek = $trek_stmt->fetch();

    // Fetch Date Details
    $date_stmt = $db->prepare("SELECT * FROM trek_dates WHERE id = ?");
    $date_stmt->execute([$temp['trek_date_id']]);
    $date = $date_stmt->fetch();

    // Fetch Pickup details if With Transportation package is active
    $pickup = null;
    if ($temp['package_type'] === 'with_transport') {
        $pick_stmt = $db->prepare("SELECT * FROM pickup_points WHERE id = ?");
        $pick_stmt->execute([$temp['pickup_point_id']]);
        $pickup = $pick_stmt->fetch();
        
        if (!$pickup) {
            throw new Exception("Invalid pickup point selected.");
        }
    }

    if (!$trek || !$date || ($temp['package_type'] === 'with_transport' && !$pickup)) {
        throw new Exception("Invalid booking parameters.");
    }
} catch (Exception $e) {
    set_flash_message('danger', 'Unable to load booking details: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/treks/index.php');
    exit();
}

// Fetch logged in user details to auto-fill
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([get_logged_in_user_id()]);
$user = $user_stmt->fetch();

$extra_js = ['assets/js/booking.js'];
$page_title = "Enter Trekker Details";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Banner -->
<section class="bg-success text-white py-4 mb-4">
    <div class="container text-center">
        <h2 class="fw-bold mb-0">Booking Information<?php if (defined('TEST_MODE') && TEST_MODE): ?> <span class="badge bg-warning text-dark fs-6 ms-2">Test Mode: Pay on Trek</span><?php endif; ?></h2>
        <p class="mb-0 text-white-50">Trekker Details for <?php echo htmlspecialchars($trek['title']); ?></p>
    </div>
</section>

<!-- Details Form -->
<section class="mb-5">
    <div class="container">
        <div class="row g-4">
            
            <!-- Details fields -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4">
                    <h4 class="fw-bold mb-4 text-success border-bottom pb-2"><i class="far fa-address-card me-2"></i>Contact Details (Lead Trekker)</h4>
                    
                    <form action="<?php echo SITE_URL; ?>/booking/checkout.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Enter name" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="john@example.com" autocomplete="email" inputmode="email">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Mobile Number (WhatsApp compatible) *</label>
                                <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="10-digit number" autocomplete="tel" inputmode="tel" pattern="[0-9]{10}">
                            </div>
                        </div>

                        <!-- Co-trekkers details -->
                        <div id="other_travelers_container">
                            <?php if ($temp['num_trekkers'] > 1): ?>
                                <h5 class="mt-4 mb-3 text-success border-bottom pb-2"><i class="fas fa-users me-2"></i>Co-Trekkers Details</h5>
                                <?php for ($i = 2; $i <= $temp['num_trekkers']; $i++): ?>
                                    <div class="card p-3 mb-3 border-light shadow-sm bg-light">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <strong>Trekker #<?php echo $i; ?></strong>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="traveler_name[]" class="form-control form-control-sm" required placeholder="Trekker Name">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Age *</label>
                                                <input type="number" name="traveler_age[]" class="form-control form-control-sm" required min="5" max="100" placeholder="Age">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Gender *</label>
                                                <select name="traveler_gender[]" class="form-select form-select-sm" required>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/treks/<?php echo $trek['slug']; ?>" class="btn btn-outline-secondary"><i class="fas fa-chevron-left me-2"></i>Back</a>
                            <button type="submit" class="btn btn-primary-custom px-4 py-2">Continue Checkout <i class="fas fa-chevron-right ms-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Trek Summary</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <span class="text-muted d-block small">Trek Name</span>
                            <strong><?php echo htmlspecialchars($trek['title']); ?></strong>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Trip Schedule</span>
                            <strong><?php echo format_date($date['start_date']) . ' to ' . format_date($date['end_date']); ?></strong>
                        </li>
                        <?php if ($temp['package_type'] === 'with_transport' && $pickup): ?>
                            <li class="mb-3">
                                <span class="text-muted d-block small">Pick-up Point</span>
                                <strong><?php echo htmlspecialchars($pickup['location']); ?></strong>
                                <small class="text-muted d-block">(Time: <?php echo date('h:i A', strtotime($pickup['time'])); ?>)</small>
                            </li>
                        <?php else: ?>
                            <li class="mb-3">
                                <span class="text-muted d-block small">Package Option</span>
                                <strong class="text-success"><i class="fas fa-hiking me-1"></i>Own Transport Selected</strong>
                                <small class="text-muted d-block">(No Pickup Point Required)</small>
                            </li>
                        <?php endif; ?>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Quantity</span>
                            <strong><?php echo $temp['num_trekkers']; ?> Trekker(s)</strong>
                        </li>
                        <li class="border-top pt-3">
                            <div class="d-flex justify-content-between text-muted mb-2">
                                <span>Per Trekker:</span>
                                <span><?php 
                                if ($temp['package_type'] === 'without_transport') {
                                    $active_p = $trek['without_transport_offer_price'] > 0 ? $trek['without_transport_offer_price'] : $trek['without_transport_price'];
                                } else {
                                    $active_p = (!empty($date['price']) && $date['price'] > 0) ? $date['price'] : ($trek['with_transport_offer_price'] > 0 ? $trek['with_transport_offer_price'] : $trek['with_transport_price']);
                                }
                                echo format_price($active_p); 
                                ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold text-success fs-5">
                                <span>Subtotal:</span>
                                <span><?php echo format_price($active_p * $temp['num_trekkers']); ?></span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
