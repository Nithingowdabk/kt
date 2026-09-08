<?php
/**
 * User Dashboard Portal
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$user_id = get_logged_in_user_id();
$db = Database::connect();

try {
    // 1. Fetch user metrics
    $stats = $db->query("SELECT 
        COUNT(id) as total,
        SUM(CASE WHEN booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN booking_status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM bookings WHERE user_id = $user_id")->fetch();

    $wishlist_count = $db->query("SELECT COUNT(id) FROM wishlist WHERE user_id = $user_id")->fetchColumn();

    // 2. Fetch recent bookings
    $booking_stmt = $db->prepare("SELECT b.*, t.title as trek_title, t.slug as trek_slug, d.start_date, d.end_date FROM bookings b 
                                 INNER JOIN treks t ON b.trek_id = t.id 
                                 INNER JOIN trek_dates d ON b.trek_date_id = d.id 
                                 WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 5");
    $booking_stmt->execute([$user_id]);
    $recent_bookings = $booking_stmt->fetchAll();

} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}

$page_title = "User Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="row g-4">
        
        <!-- Dashboard Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center user-sidebar-header mb-4 pb-3 border-bottom">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="far fa-user"></i>
                    </div>
                    <div class="user-sidebar-info overflow-hidden" style="min-width: 0;">
                        <h5 class="fw-bold mb-0 text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                        <small class="text-muted d-block text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_email']); ?>"><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="list-group-item list-group-item-action active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/user/bookings.php" class="list-group-item list-group-item-action"><i class="fas fa-hiking me-2"></i>My Bookings</a>
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action"><i class="fas fa-id-card me-2"></i>Profile Details</a>
                    <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="list-group-item list-group-item-action"><i class="fas fa-heart me-2"></i>Wishlist</a>
                    <a href="<?php echo SITE_URL; ?>/user/settings.php" class="list-group-item list-group-item-action"><i class="fas fa-cogs me-2"></i>Settings</a>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Main Panel -->
        <div class="col-lg-9">
            <?php echo get_flash_message(); ?>

            <!-- Metrics row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center">
                        <span class="text-muted small d-block mb-1">Total Bookings</span>
                        <h3 class="fw-bold text-success mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center">
                        <span class="text-muted small d-block mb-1">Confirmed</span>
                        <h3 class="fw-bold text-success mb-0"><?php echo $stats['confirmed'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center">
                        <span class="text-muted small d-block mb-1">Pending Payment</span>
                        <h3 class="fw-bold text-warning mb-0"><?php echo $stats['pending'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center">
                        <span class="text-muted small d-block mb-1">Saved Wishlist</span>
                        <h3 class="fw-bold text-danger mb-0"><?php echo $wishlist_count; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings panel -->
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0 text-success"><i class="fas fa-hiking me-2"></i>Recent Bookings</h5>
                    <a href="<?php echo SITE_URL; ?>/user/bookings.php" class="text-success text-decoration-none small fw-bold">View All &rarr;</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm text-muted">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Destination</th>
                                <th>Trek Date</th>
                                <th>Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_bookings)): ?>
                                <?php foreach ($recent_bookings as $booking): 
                                    $status_badge = 'bg-secondary';
                                    if ($booking['booking_status'] === 'Confirmed') $status_badge = 'bg-success';
                                    if ($booking['booking_status'] === 'Pending') $status_badge = 'bg-warning text-dark';
                                    if ($booking['booking_status'] === 'Cancelled') $status_badge = 'bg-danger';
                                ?>
                                    <tr>
                                        <td><strong><?php echo $booking['booking_no']; ?></strong></td>
                                         <td>
                                             <a href="<?php echo SITE_URL; ?>/treks/<?php echo htmlspecialchars($booking['trek_slug']); ?>" class="text-decoration-none text-dark fw-bold text-success-hover" style="transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color=''" title="Click to view trek details and reviews">
                                                 <?php echo htmlspecialchars($booking['trek_title'] ?? 'Custom Trek'); ?>
                                             </a>
                                         </td>
                                         <td><?php echo format_date($booking['start_date']); ?></td>
                                         <td><?php echo format_price($booking['payable_amount']); ?></td>
                                         <td class="text-center"><span class="badge <?php echo $status_badge; ?>"><?php echo $booking['booking_status']; ?></span></td>
                                         <td class="text-end">
                                             <div class="d-flex justify-content-end gap-1">
                                                 <a href="<?php echo SITE_URL; ?>/booking/invoice.php?booking_no=<?php echo $booking['booking_no']; ?>" class="btn btn-outline-success btn-sm py-1 px-2" target="_blank">
                                                     <i class="fas fa-file-invoice"></i> Invoice
                                                 </a>
                                                 <?php if ($booking['booking_status'] === 'Confirmed'): ?>
                                                     <a href="<?php echo SITE_URL; ?>/treks/<?php echo htmlspecialchars($booking['trek_slug']); ?>#section-reviews" class="btn btn-success btn-sm py-1 px-2">
                                                         <i class="fas fa-star"></i> Review
                                                     </a>
                                                 <?php endif; ?>
                                             </div>
                                         </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No bookings found. Start exploring trails now!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
