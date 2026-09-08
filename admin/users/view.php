<?php
/**
 * Admin - View Customer Profile Details
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . SITE_URL . '/admin/users/manage.php');
    exit();
}

try {
    // 1. Fetch User details
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $user_stmt->execute([$id]);
    $user = $user_stmt->fetch();

    if (!$user) {
        set_flash_message('danger', 'Customer not found.');
        header('Location: ' . SITE_URL . '/admin/users/manage.php');
        exit();
    }

    // 2. Fetch User Bookings
    $bookings_stmt = $db->prepare("SELECT b.*, t.title as trek_title, d.start_date, d.end_date FROM bookings b 
                                   INNER JOIN treks t ON b.trek_id = t.id 
                                   INNER JOIN trek_dates d ON b.trek_date_id = d.id 
                                   WHERE b.user_id = ? ORDER BY b.id DESC");
    $bookings_stmt->execute([$id]);
    $bookings = $bookings_stmt->fetchAll();

} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto">
                    <span class="text-muted small">Viewing profile of: <strong><?php echo htmlspecialchars($user['name']); ?></strong></span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4" style="max-width: 1000px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Customer Profile & History</h3>
                <a href="<?php echo SITE_URL; ?>/admin/users/manage.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Customers
                </a>
            </div>

            <!-- Profile Summary Card -->
            <div class="admin-card">
                <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="far fa-user me-2"></i>Contact Profile Details</h5>
                <div class="row g-3 text-muted">
                    <div class="col-md-4">
                        <span class="small d-block text-muted">FULL NAME:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($user['name']); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="small d-block text-muted">EMAIL ADDRESS:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="small d-block text-muted">PHONE NUMBER:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($user['phone']); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span class="small d-block text-muted">RESIDENTIAL ADDRESS:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($user['address'] ?: 'Not Provided'); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <span class="small d-block text-muted">ACCOUNT STATUS:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($user['status']); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <span class="small d-block text-muted">REGISTERED ON:</span>
                        <strong class="text-dark"><?php echo format_date($user['created_at']); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Booking History Card -->
            <div class="admin-card">
                <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-history me-2"></i>Trek Bookings History</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Trek Destination</th>
                                <th>Departure Date</th>
                                <th>Trekkers</th>
                                <th>Paid</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $b): 
                                    $b_badge = $b['booking_status'] === 'Confirmed' ? 'bg-success' : ($b['booking_status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td><strong><?php echo $b['booking_no']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($b['trek_title']); ?></td>
                                        <td><?php echo format_date($b['start_date']); ?></td>
                                        <td><?php echo $b['num_trekkers']; ?></td>
                                        <td><?php echo format_price($b['payable_amount']); ?></td>
                                        <td class="text-center"><span class="badge <?php echo $b_badge; ?>"><?php echo $b['booking_status']; ?></span></td>
                                        <td class="text-end">
                                            <a href="<?php echo SITE_URL; ?>/booking/invoice.php?booking_no=<?php echo $b['booking_no']; ?>" class="btn btn-sm btn-outline-success py-1" target="_blank">
                                                <i class="fas fa-file-invoice"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No bookings made by this customer yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

</body>
</html>
