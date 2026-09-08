<?php
/**
 * Karnataka Trekkers - Booking Success Confirmation Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$booking_no = sanitize_input($_GET['booking_no'] ?? '');

if (empty($booking_no)) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$db = Database::connect();

try {
    // Fetch Booking record
    $stmt = $db->prepare("SELECT b.*, t.title as trek_title, d.start_date, d.end_date, p.location as pickup_loc, p.time as pickup_time FROM bookings b
                          INNER JOIN treks t ON b.trek_id = t.id
                          INNER JOIN trek_dates d ON b.trek_date_id = d.id
                          LEFT JOIN pickup_points p ON b.pickup_point_id = p.id
                          WHERE b.booking_no = ? LIMIT 1");
    $stmt->execute([$booking_no]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception("Booking ID mismatch.");
    }

    // Verify booking status is Confirmed (prevent showing success screen for unconfirmed bookings)
    if ($booking['booking_status'] !== 'Confirmed' && !in_array($booking['payment_status'], ['Paid', 'Pay on Trek'])) {
        set_flash_message('warning', 'Booking payment is still processing or has failed. Please verify with customer service.');
        header('Location: ' . SITE_URL . '/user/bookings.php');
        exit();
    }

} catch (Exception $e) {
    die("Verification failed: " . $e->getMessage());
}

$page_title = "Booking Success";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding bg-light" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg overflow-hidden">
                    <div class="bg-success text-white text-center py-5">
                        <i class="far fa-check-circle display-1 mb-3"></i>
                        <h2 class="fw-bold"><?php echo $booking['payment_status'] === 'Pay on Trek' ? 'Booking Confirmed!' : 'Payment Successful!'; ?></h2>
                        <p class="mb-0 text-white-50">Thank you for booking with Karnataka Trekkers</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-4 text-center">Trek Ticket Booking Details</h4>
                        
                        <div class="row g-3 border-bottom pb-4 mb-4 text-muted">
                            <div class="col-md-6">
                                <span class="small d-block text-muted">BOOKING REFERENCE:</span>
                                <strong class="text-dark"><?php echo $booking_no; ?></strong>
                            </div>
                            <?php if ($booking['payment_status'] === 'Pay on Trek'): ?>
                                <div class="col-md-6">
                                    <span class="small d-block text-muted">PAYMENT METHOD:</span>
                                    <strong class="text-success">Pay on Trek</strong>
                                </div>
                            <?php else: ?>
                                <div class="col-md-6">
                                    <span class="small d-block text-muted">TRANSACTION NO (RAZORPAY):</span>
                                    <strong class="text-dark"><?php echo htmlspecialchars($booking['razorpay_payment_id']); ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">TREK ITINERARY:</span>
                                <strong class="text-success"><?php echo htmlspecialchars($booking['trek_title']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">PACKAGE TYPE:</span>
                                <strong class="text-dark"><?php echo $booking['package_type'] === 'without_transport' ? 'Without Transport' : 'With Transport'; ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">START DATE:</span>
                                <strong class="text-dark"><?php echo format_date($booking['start_date']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">PICKUP POINT:</span>
                                <strong class="text-dark"><?php echo $booking['package_type'] === 'without_transport' ? 'Own Transport Selected' : htmlspecialchars($booking['pickup_loc'] . ' (' . date('h:i A', strtotime($booking['pickup_time'])) . ')'); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">TOTAL TREKKERS:</span>
                                <strong class="text-dark"><?php echo $booking['num_trekkers']; ?> Traveler(s)</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="small d-block text-muted">PAYABLE AMOUNT:</span>
                                <strong class="text-success fs-5"><?php echo format_price($booking['payable_amount']); ?></strong>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
                            <i class="fab fa-whatsapp text-success fs-2"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">WhatsApp Alert Dispatched</h6>
                                <p class="small text-muted mb-0">Booking details and tickets link are successfully sent to <?php echo htmlspecialchars($booking['phone']); ?>.</p>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="<?php echo SITE_URL; ?>/booking/invoice.php?booking_no=<?php echo $booking_no; ?>" class="btn btn-outline-custom px-4 py-2" target="_blank">
                                <i class="fas fa-file-invoice me-2"></i>View & Print Invoice
                            </a>
                            <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-primary-custom px-4 py-2">
                                <i class="fas fa-hiking me-2"></i>Go to User Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
