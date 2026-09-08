<?php
/**
 * Karnataka Trekkers - Booking Invoice Generator
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$booking_no = sanitize_input($_GET['booking_no'] ?? '');

if (empty($booking_no)) {
    die("Invoice error: Booking ID is missing.");
}

$db = Database::connect();

try {
    // Fetch Booking details
    $stmt = $db->prepare("SELECT b.*, t.title as trek_title, t.price as base_price, d.start_date, d.end_date, p.location as pickup_loc, p.time as pickup_time FROM bookings b
                          INNER JOIN treks t ON b.trek_id = t.id
                          INNER JOIN trek_dates d ON b.trek_date_id = d.id
                          LEFT JOIN pickup_points p ON b.pickup_point_id = p.id
                          WHERE b.booking_no = ? LIMIT 1");
    $stmt->execute([$booking_no]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Invoice error: Booking record not found.");
    }

    // Verify ownership: Admin can view any invoice, regular user can only view their own
    if (!is_admin_logged_in() && $booking['user_id'] != get_logged_in_user_id()) {
        die("Access Denied: You do not have permission to view this invoice.");
    }

    $travelers = json_decode($booking['details'], true);
    if (!is_array($travelers)) {
        $travelers = [];
    }

} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice_<?php echo $booking_no; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F8FAFC;
            color: #1E293B;
            padding: 30px 0;
        }
        .invoice-card {
            background-color: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #E2E8F0;
            padding: 50px;
        }
        .invoice-logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: #2D5A27;
        }
        .invoice-logo span {
            color: #E67E22;
        }
        .divider {
            height: 1px;
            background-color: #E2E8F0;
            margin: 30px 0;
        }
        @media print {
            body {
                background-color: #FFFFFF;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px;">
    <!-- Control Buttons -->
    <div class="d-flex justify-content-between mb-4 btn-print">
        <a href="<?php echo is_admin_logged_in() ? SITE_URL . '/admin/bookings/manage.php' : SITE_URL . '/user/bookings.php'; ?>" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-success btn-sm px-4">
            Print / Save PDF
        </button>
    </div>

    <!-- Main Invoice Card -->
    <div class="invoice-card">
        <!-- Logo & Title -->
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="invoice-logo">
                    Karnataka <span>Trekkers</span>
                </div>
                <p class="text-muted small mb-0 mt-2">
                    <?php echo CONTACT_ADDRESS; ?><br>
                    Phone: <?php echo CONTACT_PHONE; ?> | Email: <?php echo CONTACT_EMAIL; ?>
                </p>
            </div>
            <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                <h2 class="fw-bold text-uppercase text-success mb-1">Invoice</h2>
                <span class="badge <?php echo $booking['payment_status'] === 'Paid' ? 'bg-success' : 'bg-danger'; ?> px-3 py-2 fs-7">
                    Payment Status: <?php echo htmlspecialchars($booking['payment_status']); ?>
                </span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Bill To / Invoice Details -->
        <div class="row">
            <div class="col-sm-6">
                <h6 class="text-muted text-uppercase fw-bold small">Billed To:</h6>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['name']); ?></h5>
                <p class="text-muted small mb-0">
                    Email: <?php echo htmlspecialchars($booking['email']); ?><br>
                    Phone: <?php echo htmlspecialchars($booking['phone']); ?>
                </p>
            </div>
            <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                <h6 class="text-muted text-uppercase fw-bold small">Invoice Details:</h6>
                <ul class="list-unstyled mb-0 text-muted small">
                    <li>Booking Reference: <strong class="text-dark"><?php echo $booking_no; ?></strong></li>
                    <li>Package Option: <strong class="text-dark"><?php echo $booking['package_type'] === 'without_transport' ? 'Without Transport' : 'With Transport'; ?></strong></li>
                    <li>Date Issued: <strong class="text-dark"><?php echo format_date($booking['created_at']); ?></strong></li>
                    <li>Transaction ID: <strong class="text-dark"><?php echo htmlspecialchars($booking['razorpay_payment_id'] ?? 'N/A'); ?></strong></li>
                </ul>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Table items -->
        <h6 class="text-muted text-uppercase fw-bold mb-3 small">Trek Itinerary & Booking Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Trek details</th>
                        <th class="text-center">Batch Dates</th>
                        <th class="text-center">Rate</th>
                        <th class="text-center">Trekkers</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong class="text-success"><?php echo htmlspecialchars($booking['trek_title']); ?></strong>
                            <?php if ($booking['package_type'] === 'without_transport'): ?>
                                <div class="text-muted small mt-1"><span class="badge bg-success">Without Transport</span> (Own Transport Selected)</div>
                            <?php else: ?>
                                <div class="text-muted small mt-1">Pickup: <?php echo htmlspecialchars($booking['pickup_loc'] ?? 'TBD'); ?> <?php if (!empty($booking['pickup_time'])) echo '(' . date('h:i A', strtotime($booking['pickup_time'])) . ')'; ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-muted small align-middle">
                            <?php echo format_date($booking['start_date']) . '<br>to<br>' . format_date($booking['end_date']); ?>
                        </td>
                        <td class="text-center align-middle">
                            <?php 
                            $unit_p = $booking['total_amount'] / $booking['num_trekkers'];
                            echo format_price($unit_p); 
                            ?>
                        </td>
                        <td class="text-center align-middle">
                            <?php echo $booking['num_trekkers']; ?>
                        </td>
                        <td class="text-end align-middle">
                            <?php echo format_price($booking['total_amount']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Co-Trekkers List -->
        <?php if (!empty($travelers)): ?>
            <h6 class="text-muted text-uppercase fw-bold mt-4 mb-2 small">Co-Trekkers Passenger Details</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-muted mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th class="text-center">Age</th>
                            <th class="text-center">Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($travelers as $idx => $t): ?>
                            <tr>
                                <td><?php echo $idx + 2; ?></td>
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($t['age']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($t['gender']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Totals calculations -->
        <div class="row justify-content-end mt-4">
            <div class="col-sm-5 text-end">
                <ul class="list-unstyled">
                    <li class="mb-2 d-flex justify-content-between text-muted">
                        <span>Subtotal:</span>
                        <strong><?php echo format_price($booking['total_amount']); ?></strong>
                    </li>
                    <?php if ($booking['discount_amount'] > 0): ?>
                        <li class="mb-2 d-flex justify-content-between text-danger">
                            <span>Discount Applied:</span>
                            <strong>-<?php echo format_price($booking['discount_amount']); ?></strong>
                        </li>
                    <?php endif; ?>
                    <li class="border-top pt-2 d-flex justify-content-between fs-5 fw-bold text-success">
                        <span>Grand Total Paid:</span>
                        <span><?php echo format_price($booking['payable_amount']); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="divider"></div>
        
        <!-- Terms / Footer -->
        <div class="text-center text-muted small">
            <p class="mb-1">This is a system generated booking invoice. No physical signature is required.</p>
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Thank you for your business!</p>
        </div>

    </div>
</div>

</body>
</html>
