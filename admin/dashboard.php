<?php
/**
 * Admin Panel Dashboard Home with Rich Analytics & Reporting
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

try {
    // 1. Fetch aggregates metrics
    $revenue = $db->query("SELECT SUM(payable_amount) FROM bookings WHERE payment_status = 'Paid'")->fetchColumn() ?? 0.00;
    $bookings_cnt = $db->query("SELECT COUNT(id) FROM bookings WHERE booking_status = 'Confirmed'")->fetchColumn() ?? 0;
    $treks_cnt = $db->query("SELECT COUNT(id) FROM treks WHERE status = 'Active'")->fetchColumn() ?? 0;
    $users_cnt = $db->query("SELECT COUNT(id) FROM users WHERE status = 'Active'")->fetchColumn() ?? 0;

    // 2. Fetch recent bookings list
    $stmt = $db->query("SELECT b.*, t.title as trek_title FROM bookings b 
                        INNER JOIN treks t ON b.trek_id = t.id 
                        ORDER BY b.id DESC LIMIT 5");
    $recent_bookings = $stmt->fetchAll();

    // 3. Popular Treks Analytics
    $popular_stmt = $db->query("SELECT t.title, COUNT(b.id) as booking_count, 
                                       SUM(b.num_trekkers) as total_seats_sold, 
                                       SUM(b.payable_amount) as total_rev 
                                FROM bookings b 
                                INNER JOIN treks t ON b.trek_id = t.id 
                                WHERE b.booking_status = 'Confirmed' 
                                GROUP BY b.trek_id 
                                ORDER BY total_seats_sold DESC 
                                LIMIT 5");
    $popular_treks = $popular_stmt->fetchAll();

    // 4. Monthly Reports Analytics for Current Year
    $monthly_stmt = $db->query("SELECT DATE_FORMAT(b.created_at, '%M') as month_name, 
                                       COUNT(b.id) as booking_count, 
                                       SUM(b.payable_amount) as total_rev 
                                FROM bookings b 
                                WHERE b.booking_status = 'Confirmed' AND YEAR(b.created_at) = YEAR(CURDATE()) 
                                GROUP BY MONTH(b.created_at) 
                                ORDER BY MONTH(b.created_at) ASC");
    $monthly_reports = $monthly_stmt->fetchAll();

    // 5. Customer Spending Roster Reports
    $customer_stmt = $db->query("SELECT name, email, phone, COUNT(id) as booking_count, 
                                        SUM(payable_amount) as total_spend 
                                 FROM bookings 
                                 WHERE booking_status = 'Confirmed' 
                                 GROUP BY email 
                                 ORDER BY total_spend DESC 
                                 LIMIT 5");
    $customer_reports = $customer_stmt->fetchAll();

} catch (PDOException $e) {
    die("System error loading dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Karnataka Trekkers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <!-- Top navbar -->
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="text-muted small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></span>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-power-off me-1"></i>Logout</a>
                </div>
            </div>
        </nav>

        <!-- Main Dashboard Container -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div>
                    <h2 class="fw-bold mb-0 text-success">Control Dashboard</h2>
                    <p class="text-muted mb-0">System metrics, aggregates, and financial reports</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/bookings/export.php" class="btn btn-success btn-sm shadow-sm px-3 py-2 fw-bold">
                    <i class="fas fa-file-excel me-1"></i> Export Bookings list (Excel/CSV)
                </a>
            </div>

            <!-- Stats metric cards row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card border-start border-4 border-success">
                        <div>
                            <span class="text-muted small d-block mb-1">Total Revenue</span>
                            <h3 class="fw-bold mb-0 text-success"><?php echo format_price($revenue); ?></h3>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card border-start border-4 border-primary">
                        <div>
                            <span class="text-muted small d-block mb-1">Confirmed Bookings</span>
                            <h3 class="fw-bold mb-0 text-primary"><?php echo $bookings_cnt; ?></h3>
                        </div>
                        <div class="stat-icon primary">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card border-start border-4 border-warning">
                        <div>
                            <span class="text-muted small d-block mb-1">Active Treks</span>
                            <h3 class="fw-bold mb-0 text-warning"><?php echo $treks_cnt; ?></h3>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-route"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card border-start border-4 border-danger">
                        <div>
                            <span class="text-muted small d-block mb-1">Registered Customers</span>
                            <h3 class="fw-bold mb-0 text-danger"><?php echo $users_cnt; ?></h3>
                        </div>
                        <div class="stat-icon danger">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tables Grid -->
            <div class="row g-4 mb-4">
                <!-- Popular Treks list -->
                <div class="col-lg-6">
                    <div class="admin-card h-100">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-fire me-2"></i>Popular Treks</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle text-muted">
                                <thead>
                                    <tr class="table-light">
                                        <th>Trek Title</th>
                                        <th class="text-center">Bookings</th>
                                        <th class="text-center">Seats Sold</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($popular_treks)): ?>
                                        <?php foreach ($popular_treks as $pt): ?>
                                            <tr>
                                                <td><strong class="text-dark"><?php echo htmlspecialchars($pt['title']); ?></strong></td>
                                                <td class="text-center"><?php echo $pt['booking_count']; ?></td>
                                                <td class="text-center"><span class="badge bg-success"><?php echo $pt['total_seats_sold']; ?> seats</span></td>
                                                <td class="text-end text-success fw-bold"><?php echo format_price($pt['total_rev']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3">No stats compiled yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Spender Customer reports -->
                <div class="col-lg-6">
                    <div class="admin-card h-100">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-award me-2"></i>Top Customers (Spend Roster)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle text-muted">
                                <thead>
                                    <tr class="table-light">
                                        <th>Customer</th>
                                        <th>Contact Details</th>
                                        <th class="text-center">Bookings</th>
                                        <th class="text-end">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customer_reports)): ?>
                                        <?php foreach ($customer_reports as $cr): ?>
                                            <tr>
                                                <td><strong class="text-dark"><?php echo htmlspecialchars($cr['name']); ?></strong></td>
                                                <td>
                                                    <small class="d-block"><?php echo htmlspecialchars($cr['email']); ?></small>
                                                    <small class="text-muted"><?php echo htmlspecialchars($cr['phone']); ?></small>
                                                </td>
                                                <td class="text-center"><?php echo $cr['booking_count']; ?></td>
                                                <td class="text-end text-success fw-bold"><?php echo format_price($cr['total_spend']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3">No customer logs verified.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Report & Recent Bookings -->
            <div class="row g-4">
                <!-- Monthly Revenue Chart / List -->
                <div class="col-lg-4">
                    <div class="admin-card h-100">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-chart-bar me-2"></i>Monthly Financial Summary (<?php echo date('Y'); ?>)</h5>
                        <ul class="list-group list-group-flush text-muted">
                            <?php if (!empty($monthly_reports)): ?>
                                <?php foreach ($monthly_reports as $mr): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <strong class="text-dark"><?php echo $mr['month_name']; ?></strong>
                                            <div class="text-muted small"><?php echo $mr['booking_count']; ?> confirmed bookings</div>
                                        </div>
                                        <span class="badge bg-success rounded-pill"><?php echo format_price($mr['total_rev']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center py-3 px-0">No monthly data captured for this year.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Recent Bookings list -->
                <div class="col-lg-8">
                    <div class="admin-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h5 class="fw-bold text-success mb-0"><i class="fas fa-list-ul me-2"></i>Recent Bookings Activity</h5>
                            <a href="<?php echo SITE_URL; ?>/admin/bookings/manage.php" class="btn btn-outline-success btn-xs py-0.5 px-2 text-decoration-none">View All</a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle text-muted">
                                <thead>
                                    <tr>
                                        <th>Ref ID</th>
                                        <th>Customer</th>
                                        <th>Trek</th>
                                        <th>Total Paid</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_bookings)): ?>
                                        <?php foreach ($recent_bookings as $b): 
                                            $b_badge = $b['booking_status'] === 'Confirmed' ? 'bg-success' : ($b['booking_status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                            <tr>
                                                <td><strong><?php echo $b['booking_no']; ?></strong></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($b['phone']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($b['trek_title']); ?></td>
                                                <td class="text-success fw-bold"><?php echo format_price($b['payable_amount']); ?></td>
                                                <td class="text-center"><span class="badge <?php echo $b_badge; ?>"><?php echo $b['booking_status']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No recent bookings recorded.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JS imports -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

</body>
</html>
