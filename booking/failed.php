<?php
/**
 * Karnataka Trekkers - Booking Failure Alert Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$booking_no = sanitize_input($_GET['booking_no'] ?? '');

$db = Database::connect();
try {
    $stmt = $db->prepare("SELECT b.*, t.title as trek_title FROM bookings b 
                          INNER JOIN treks t ON b.trek_id = t.id 
                          WHERE b.booking_no = ? LIMIT 1");
    $stmt->execute([$booking_no]);
    $booking = $stmt->fetch();
    
    if ($booking && $booking['payment_status'] === 'Pending') {
        // Update payment status as Failed
        $up = $db->prepare("UPDATE bookings SET payment_status = 'Failed', booking_status = 'Cancelled' WHERE id = ?");
        $up->execute([$booking['id']]);
    }
} catch (PDOException $e) {
    // Graceful error handle
}

$page_title = "Payment Failed";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding bg-light" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg text-center p-5 bg-white">
                    <div class="text-danger mb-4">
                        <i class="far fa-times-circle display-1"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Payment Transaction Failed</h2>
                    <p class="text-muted mb-4">We were unable to complete processing your online payment request. Any amount debited from your account will be auto-refunded by your bank within 3-5 business days.</p>
                    
                    <?php if ($booking): ?>
                        <div class="border p-3 rounded mb-4 text-start bg-light text-muted small">
                            <div class="mb-1"><strong>Trek Destination:</strong> <?php echo htmlspecialchars($booking['trek_title']); ?></div>
                            <div class="mb-1"><strong>Booking Order No:</strong> <?php echo $booking_no; ?></div>
                            <div><strong>Amount:</strong> <?php echo format_price($booking['payable_amount']); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning border-0 small mb-4 text-start">
                        <strong>Common Reasons for failure:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Incorrect card details, expiry date or CVV.</li>
                            <li>OTP verification time limit expired.</li>
                            <li>Insufficient balance in bank account.</li>
                            <li>Temporary network downtime at payment gateway or bank nodes.</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo SITE_URL; ?>/treks/index.php" class="btn btn-primary-custom px-4 py-2">
                            <i class="fas fa-redo me-2"></i>Retry New Booking
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-secondary px-4 py-2">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
