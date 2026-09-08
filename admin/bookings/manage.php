<?php
/**
 * Admin - Manage Bookings Catalog Redesigned
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

// Auto-migration: Ensure 'completed' column and indexes exist on both local and live databases
try {
    $check_col = $db->query("SHOW COLUMNS FROM bookings LIKE 'completed'")->fetch();
    if (!$check_col) {
        $db->exec("ALTER TABLE bookings ADD COLUMN completed TINYINT(1) NOT NULL DEFAULT 0 AFTER booking_status");
        $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_completed (completed)");
        $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_created_at (created_at)");
        $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_payment_status (payment_status)");
        $db->exec("ALTER TABLE bookings ADD INDEX idx_bookings_booking_status (booking_status)");
        
        // Check trek_dates start_date index
        try {
            $db->exec("ALTER TABLE trek_dates ADD INDEX idx_trek_dates_start_date (start_date)");
        } catch (PDOException $ex) {
            // Index might already exist
        }
    }
} catch (PDOException $e) {
    // Silent fail if permissions prevent it (fallback logging can be done)
}

// Formatting utility for preserving query parameters on filter change
function get_filter_url($params_to_merge) {
    $current_params = $_GET;
    unset($current_params['page']); // Reset pagination on filter change
    $merged = array_merge($current_params, $params_to_merge);
    return '?' . http_build_query($merged);
}

// -------------------------------------------------------------------------
// POST HANDLERS FOR ACTIONS
// -------------------------------------------------------------------------

// Edit Booking Details POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $booking_id = (int)$_POST['booking_id'];
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $num_trekkers = (int)$_POST['num_trekkers'];
    $payable_amount = (float)$_POST['payable_amount'];
    $payment_status = sanitize_input($_POST['payment_status'] ?? 'Pending');
    $booking_status = sanitize_input($_POST['booking_status'] ?? 'Pending');
    $completed = (int)($_POST['completed'] ?? 0);
    
    try {
        $stmt = $db->prepare("UPDATE bookings SET name = ?, email = ?, phone = ?, num_trekkers = ?, payable_amount = ?, payment_status = ?, booking_status = ?, completed = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $num_trekkers, $payable_amount, $payment_status, $booking_status, $completed, $booking_id]);
        
        set_flash_message('success', 'Booking updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to update booking: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Mark Completed POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_mark_completed'])) {
    $booking_id = (int)$_POST['booking_id'];
    try {
        $stmt = $db->prepare("UPDATE bookings SET completed = 1 WHERE id = ?");
        $stmt->execute([$booking_id]);
        set_flash_message('success', 'Booking marked as Completed.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to mark as completed: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Cancel Booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cancel'])) {
    $booking_id = (int)$_POST['booking_id'];
    try {
        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        set_flash_message('success', 'Booking cancelled successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to cancel booking: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Send WhatsApp / Resend Confirmation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_send_whatsapp'])) {
    $booking_id = (int)$_POST['booking_id'];
    try {
        require_once __DIR__ . '/../../whatsapp/WhatsAppService.php';
        $whatsapp = new WhatsAppService();
        $res = $whatsapp->sendBookingConfirmation($booking_id);
        if ($res) {
            set_flash_message('success', 'WhatsApp confirmation sent successfully.');
        } else {
            set_flash_message('danger', 'Failed to send WhatsApp message. Check service configuration.');
        }
    } catch (Exception $e) {
        set_flash_message('danger', 'Error sending WhatsApp: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// -------------------------------------------------------------------------
// QUERY BUILDER & FILTERS
// -------------------------------------------------------------------------
$tab = sanitize_input($_GET['tab'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');
$filter_trek = (int)($_GET['filter_trek'] ?? 0);
$filter_start_date = sanitize_input($_GET['filter_start_date'] ?? '');
$filter_end_date = sanitize_input($_GET['filter_end_date'] ?? '');
$filter_package = sanitize_input($_GET['filter_package'] ?? '');
$filter_payment = sanitize_input($_GET['filter_payment'] ?? '');
$filter_booking = sanitize_input($_GET['filter_booking'] ?? '');

$where = [];
$params = [];

// 1. Top Bar Tab filter logic
if ($tab === 'this_month') {
    $where[] = "MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE())";
} elseif ($tab === 'upcoming') {
    $where[] = "d.start_date >= CURRENT_DATE()";
} elseif ($tab === 'completed') {
    $where[] = "d.start_date < CURRENT_DATE()";
} elseif ($tab === 'not_completed') {
    $where[] = "d.start_date >= CURRENT_DATE() AND b.completed = 0";
} elseif ($tab === 'cancelled') {
    $where[] = "b.booking_status = 'Cancelled'";
} elseif ($tab === 'payment_pending') {
    $where[] = "b.payment_status = 'Pending'";
} elseif ($tab === 'today_departures') {
    $where[] = "d.start_date = CURRENT_DATE()";
}

// 2. Global Search
if (!empty($search)) {
    $where[] = "(b.booking_no LIKE ? OR b.name LIKE ? OR b.phone LIKE ? OR b.email LIKE ? OR t.title LIKE ? OR pp.location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// 3. Dropdowns
if ($filter_trek > 0) {
    $where[] = "b.trek_id = ?";
    $params[] = $filter_trek;
}
if (!empty($filter_start_date)) {
    $where[] = "d.start_date >= ?";
    $params[] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $where[] = "d.start_date <= ?";
    $params[] = $filter_end_date;
}
if (!empty($filter_package)) {
    $where[] = "b.package_type = ?";
    $params[] = $filter_package;
}
if (!empty($filter_payment)) {
    $where[] = "b.payment_status = ?";
    $params[] = $filter_payment;
}
if (!empty($filter_booking)) {
    if ($filter_booking === 'Completed') {
        $where[] = "b.completed = 1";
    } else {
        $where[] = "b.booking_status = ? AND b.completed = 0";
        $params[] = $filter_booking;
    }
}

$where_clause = "";
if (!empty($where)) {
    $where_clause = " WHERE " . implode(" AND ", $where);
}

// Fetch dashboard statistics in a single optimized query
try {
    $stats_query = "
        SELECT 
            COUNT(b.id) as stat_total_bookings,
            COUNT(CASE WHEN DATE(b.created_at) = CURRENT_DATE() THEN 1 END) as stat_today_bookings,
            COALESCE(SUM(CASE WHEN b.payment_status = 'Paid' AND MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN b.payable_amount ELSE 0 END), 0) as stat_month_revenue,
            COUNT(CASE WHEN d.start_date >= CURRENT_DATE() AND b.booking_status != 'Cancelled' THEN 1 END) as stat_upcoming_treks,
            COUNT(CASE WHEN d.start_date < CURRENT_DATE() OR b.completed = 1 THEN 1 END) as stat_completed_treks,
            COUNT(CASE WHEN b.payment_status = 'Pending' AND b.booking_status != 'Cancelled' THEN 1 END) as stat_pending_payments
        FROM bookings b
        INNER JOIN treks t ON b.trek_id = t.id
        INNER JOIN trek_dates d ON b.trek_date_id = d.id";
    $stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [];
}

// Fetch filter tab counts dynamically
try {
    $counts_query = "
        SELECT 
            COUNT(b.id) as count_all,
            COUNT(CASE WHEN MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 END) as count_this_month,
            COUNT(CASE WHEN d.start_date >= CURRENT_DATE() THEN 1 END) as count_upcoming,
            COUNT(CASE WHEN d.start_date < CURRENT_DATE() THEN 1 END) as count_completed,
            COUNT(CASE WHEN d.start_date >= CURRENT_DATE() AND b.completed = 0 THEN 1 END) as count_not_completed,
            COUNT(CASE WHEN b.booking_status = 'Cancelled' THEN 1 END) as count_cancelled,
            COUNT(CASE WHEN b.payment_status = 'Pending' THEN 1 END) as count_payment_pending,
            COUNT(CASE WHEN d.start_date = CURRENT_DATE() THEN 1 END) as count_today_departures
        FROM bookings b
        INNER JOIN treks t ON b.trek_id = t.id
        INNER JOIN trek_dates d ON b.trek_date_id = d.id";
    $filter_counts = $db->query($counts_query)->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $filter_counts = [];
}

// Pagination Count
$limit = 15;
$count_sql = "SELECT COUNT(b.id) FROM bookings b 
              INNER JOIN treks t ON b.trek_id = t.id 
              INNER JOIN trek_dates d ON b.trek_date_id = d.id
              LEFT JOIN pickup_points pp ON b.pickup_point_id = pp.id" . $where_clause;
$total_stmt = $db->prepare($count_sql);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

$total_pages = ceil($total_records / $limit);
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Fetch filtered and paginated bookings list
$sql = "SELECT b.*, t.title as trek_title, d.start_date, d.end_date, pp.location as pickup_loc_name FROM bookings b 
        INNER JOIN treks t ON b.trek_id = t.id 
        INNER JOIN trek_dates d ON b.trek_date_id = d.id
        LEFT JOIN pickup_points pp ON b.pickup_point_id = pp.id" 
        . $where_clause 
        . " ORDER BY b.id DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get list of all treks for the dropdown
$all_treks_list = $db->query("SELECT id, title FROM treks ORDER BY title ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Catalog Operations | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    
    <style>
        .stat-card {
            transition: transform 0.2s ease-in-out;
            border-radius: 10px;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .btn-filter-chip {
            white-space: nowrap;
            border-radius: 50px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 6px 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-filter-chip:hover {
            background-color: #f1f3f5;
            color: #212529;
        }
        .btn-filter-chip.active {
            background-color: #198754;
            border-color: #198754;
            color: #ffffff;
        }
        .btn-filter-chip.active .badge {
            background-color: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }
        .filter-scroll-x::-webkit-scrollbar {
            height: 4px;
        }
        .filter-scroll-x::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.15);
            border-radius: 4px;
        }
        .pagination-custom .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }
        .pagination-custom .page-link {
            color: #198754;
        }
        .sticky-filter-bar {
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: #f8f9fa;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
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
                    <span class="text-muted small">Daily Operations Command Dashboard</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Booking Catalog Operations</h3>
            </div>

            <?php echo get_flash_message(); ?>

            <!-- Dashboard Statistics Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-book-open text-success opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">Total Bookings</span>
                        <h4 class="fw-bold text-success mb-0"><?php echo $stats['stat_total_bookings'] ?? 0; ?></h4>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-calendar-day text-primary opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">Today's Bookings</span>
                        <h4 class="fw-bold text-primary mb-0"><?php echo $stats['stat_today_bookings'] ?? 0; ?></h4>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-rupee-sign text-success opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">This Month Revenue</span>
                        <h4 class="fw-bold text-success mb-0"><?php echo format_price($stats['stat_month_revenue'] ?? 0); ?></h4>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-hiking text-info opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">Upcoming Treks</span>
                        <h4 class="fw-bold text-info mb-0"><?php echo $stats['stat_upcoming_treks'] ?? 0; ?></h4>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-check-circle text-secondary opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">Completed Treks</span>
                        <h4 class="fw-bold text-secondary mb-0"><?php echo $stats['stat_completed_treks'] ?? 0; ?></h4>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm p-3 text-center stat-card bg-white h-100">
                        <i class="fas fa-clock text-danger opacity-50 mb-2 fs-4"></i>
                        <span class="text-muted small d-block mb-1 fw-semibold">Pending Payments</span>
                        <h4 class="fw-bold text-danger mb-0"><?php echo $stats['stat_pending_payments'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>

            <!-- Sticky Operational Filter Tabs -->
            <div class="sticky-filter-bar mb-3">
                <div class="d-flex flex-nowrap overflow-auto pb-1 filter-scroll-x" style="-webkit-overflow-scrolling: touch; gap: 8px;">
                    <a href="<?php echo get_filter_url(['tab' => '']); ?>" class="btn btn-sm btn-filter-chip <?php echo empty($tab) ? 'active' : ''; ?>">
                        All Bookings <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_all'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'this_month']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'this_month' ? 'active' : ''; ?>">
                        This Month Bookings <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_this_month'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'upcoming']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'upcoming' ? 'active' : ''; ?>">
                        Upcoming Trips <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_upcoming'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'completed']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'completed' ? 'active' : ''; ?>">
                        Trip Completed <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_completed'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'not_completed']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'not_completed' ? 'active' : ''; ?>">
                        Trip Not Completed <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_not_completed'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'cancelled']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'cancelled' ? 'active' : ''; ?>">
                        Cancelled Bookings <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_cancelled'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'payment_pending']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'payment_pending' ? 'active' : ''; ?>">
                        Payment Pending <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_payment_pending'] ?? 0; ?></span>
                    </a>
                    <a href="<?php echo get_filter_url(['tab' => 'today_departures']); ?>" class="btn btn-sm btn-filter-chip <?php echo $tab === 'today_departures' ? 'active' : ''; ?>">
                        Today's Departures <span class="badge bg-secondary ms-2"><?php echo $filter_counts['count_today_departures'] ?? 0; ?></span>
                    </a>
                </div>
            </div>

            <!-- Custom Filters Form -->
            <div class="card border-0 shadow-sm p-4 mb-4 bg-white">
                <form method="GET" action="">
                    <?php if (!empty($tab)): ?>
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <!-- Global Search -->
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold small text-muted">Global Search Box</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="ID, Name, Phone, Email, Trek, Pickup..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>

                        <!-- Trek Dropdown -->
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label fw-bold small text-muted">Trek Name Filter</label>
                            <select name="filter_trek" class="form-select">
                                <option value="">-- All Treks --</option>
                                <?php foreach ($all_treks_list as $t_item): ?>
                                    <option value="<?php echo $t_item['id']; ?>" <?php echo $filter_trek === (int)$t_item['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t_item['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold small text-muted">Date Range Filter</label>
                            <div class="input-group">
                                <input type="date" name="filter_start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                                <span class="input-group-text bg-light text-muted">to</span>
                                <input type="date" name="filter_end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                            </div>
                        </div>

                        <!-- Package Type -->
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label fw-bold small text-muted">Package Type Filter</label>
                            <select name="filter_package" class="form-select">
                                <option value="">-- All --</option>
                                <option value="with_transport" <?php echo $filter_package === 'with_transport' ? 'selected' : ''; ?>>With Transport</option>
                                <option value="without_transport" <?php echo $filter_package === 'without_transport' ? 'selected' : ''; ?>>Own Transport</option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-lg-1 col-md-6">
                            <label class="form-label fw-bold small text-muted">Payment Status</label>
                            <select name="filter_payment" class="form-select">
                                <option value="">-- All --</option>
                                <option value="Paid" <?php echo $filter_payment === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Pending" <?php echo $filter_payment === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Failed" <?php echo $filter_payment === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="Pay on Trek" <?php echo $filter_payment === 'Pay on Trek' ? 'selected' : ''; ?>>Pay on Trek</option>
                            </select>
                        </div>

                        <!-- Booking Status -->
                        <div class="col-lg-1.5 col-md-6">
                            <label class="form-label fw-bold small text-muted">Booking Status</label>
                            <select name="filter_booking" class="form-select">
                                <option value="">-- All --</option>
                                <option value="Pending" <?php echo $filter_booking === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo $filter_booking === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo $filter_booking === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="Completed" <?php echo $filter_booking === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="submit" class="btn btn-success px-4 py-2"><i class="fas fa-filter me-2"></i>Apply Filters</button>
                            <a href="?" class="btn btn-outline-secondary px-4 py-2"><i class="fas fa-undo me-2"></i>Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Bookings List Table -->
            <div class="admin-card bg-white shadow-sm rounded border-0 p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Customer Details</th>
                                <th>Trek Destination</th>
                                <th>Package Type</th>
                                <th>Qty</th>
                                <th>Payable</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $b): 
                                    $p_badge = $b['payment_status'] === 'Paid' ? 'bg-success' : ($b['payment_status'] === 'Pending' ? 'bg-warning text-dark' : ($b['payment_status'] === 'Pay on Trek' ? 'bg-info text-dark' : 'bg-danger'));
                                    
                                    // Highlight cancelled/completed
                                    if ($b['completed'] == 1) {
                                        $b_badge_class = 'bg-secondary';
                                        $b_status_label = 'Completed';
                                    } else {
                                        $b_status_label = $b['booking_status'];
                                        $b_badge_class = $b['booking_status'] === 'Confirmed' ? 'bg-success' : ($b['booking_status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger');
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?php echo $b['booking_no']; ?></strong>
                                            <div class="text-muted small mt-1"><?php echo format_date($b['created_at']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['name']); ?></div>
                                            <small class="text-muted d-block"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($b['phone']); ?></small>
                                            <small class="text-muted d-block"><i class="far fa-envelope me-1"></i><?php echo htmlspecialchars($b['email']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-bold"><?php echo htmlspecialchars($b['trek_title']); ?></div>
                                            <small class="text-muted">Departure: <?php echo format_date($b['start_date']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo ($b['package_type'] ?? 'with_transport') === 'without_transport' ? '<span class="badge bg-success bg-opacity-10 text-success" style="font-size:0.75rem; color: #198754 !important; background-color: #e8f5e9 !important;">Own Transport</span>' : '<span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:0.75rem; color: #0d6efd !important; background-color: #e7f1ff !important;">With Transport</span>'; ?>
                                            <?php if ($b['pickup_loc_name'] && $b['package_type'] !== 'without_transport'): ?>
                                                <small class="text-muted d-block mt-1"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($b['pickup_loc_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $b['num_trekkers']; ?></span></td>
                                        <td><span class="fw-bold text-dark"><?php echo format_price($b['payable_amount']); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $p_badge; ?>"><?php echo $b['payment_status']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $b_badge_class; ?>"><?php echo $b_status_label; ?></span>
                                        </td>
                                        
                                        <!-- Table Actions -->
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- View details -->
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick='viewBooking(<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8"); ?>)' title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Edit details -->
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick='editBooking(<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8"); ?>)' title="Edit Booking">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- Dropdown controls -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border">
                                                        <!-- Mark Completed -->
                                                        <?php if ($b['completed'] == 0): ?>
                                                            <li>
                                                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to mark this trip as completed?');">
                                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                                    <input type="hidden" name="action_mark_completed" value="1">
                                                                    <button type="submit" class="dropdown-item"><i class="fas fa-check-circle text-success me-2"></i>Mark Completed</button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Cancel Booking -->
                                                        <?php if ($b['booking_status'] !== 'Cancelled'): ?>
                                                            <li>
                                                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                                    <input type="hidden" name="action_cancel" value="1">
                                                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-times-circle me-2"></i>Cancel Booking</button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Send WhatsApp Confirmation -->
                                                        <li>
                                                            <form action="" method="POST" onsubmit="return confirm('Resend WhatsApp Confirmation?');">
                                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                                <input type="hidden" name="action_send_whatsapp" value="1">
                                                                <button type="submit" class="dropdown-item"><i class="fab fa-whatsapp text-success me-2"></i>Send WhatsApp</button>
                                                            </form>
                                                        </li>
                                                        
                                                        <!-- Resend Confirmation -->
                                                        <li>
                                                            <form action="" method="POST" onsubmit="return confirm('Resend Booking Confirmation Details?');">
                                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                                <input type="hidden" name="action_send_whatsapp" value="1">
                                                                <button type="submit" class="dropdown-item"><i class="fas fa-sync text-primary me-2"></i>Resend Confirmation</button>
                                                            </form>
                                                        </li>
                                                        
                                                        <!-- Invoice Download -->
                                                        <li>
                                                            <a href="<?php echo SITE_URL; ?>/booking/invoice.php?booking_no=<?php echo $b['booking_no']; ?>" target="_blank" class="dropdown-item">
                                                                <i class="fas fa-file-invoice text-info me-2"></i>Download Invoice
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-box-open display-4 text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-0">No booking records match your filter parameters.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination pagination-custom justify-content-center">
                            <!-- Previous page link -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo get_filter_url(['page' => $page - 1]); ?>">&laquo; Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo get_filter_url(['page' => $i]); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next page link -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo get_filter_url(['page' => $page + 1]); ?>">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DETAIL MODALS -->
<!-- ========================================================================= -->

<!-- View Booking Modal -->
<div class="modal fade" id="viewBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Booking Details - <span id="view_booking_no"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Customer Name</label>
                        <div id="view_name" class="fw-bold text-dark fs-6"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Contact Email</label>
                        <div id="view_email" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Phone Number</label>
                        <div id="view_phone" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Trek Destination</label>
                        <div id="view_trek" class="fw-bold text-success"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Trek Start Date</label>
                        <div id="view_date" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Quantity (Trekkers)</label>
                        <div id="view_qty" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Total Amount</label>
                        <div id="view_total" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Discount Amount</label>
                        <div id="view_discount" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Payable Amount</label>
                        <div id="view_payable" class="fw-bold text-success"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Package Option</label>
                        <div id="view_package" class="fw-bold text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block">Pickup Spot</label>
                        <div id="view_pickup" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Payment Status</label>
                        <div id="view_payment" class="fw-bold text-primary"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Booking Status</label>
                        <div id="view_booking_status" class="fw-bold text-primary"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-bold d-block">Trip Status</label>
                        <div id="view_completed" class="fw-bold text-primary"></div>
                    </div>
                </div>
                
                <!-- Secondary travelers table -->
                <div class="mt-4 pt-3 border-top" id="view_travelers_section">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-users me-2"></i>Secondary Trekkers List</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-muted">
                            <thead>
                                <tr class="bg-light">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody id="view_travelers_list">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST">
            <input type="hidden" name="action_edit" value="1">
            <input type="hidden" name="booking_id" id="edit_booking_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Booking - <span id="edit_booking_no"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-dark">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Customer Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Number of Trekkers</label>
                        <input type="number" name="num_trekkers" id="edit_num_trekkers" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Payable Amount (₹)</label>
                        <input type="number" step="0.01" name="payable_amount" id="edit_payable_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Payment Status</label>
                        <select name="payment_status" id="edit_payment_status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                            <option value="Failed">Failed</option>
                            <option value="Pay on Trek">Pay on Trek</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Booking Status</label>
                        <select name="booking_status" id="edit_booking_status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Trip Completed Status</label>
                        <select name="completed" id="edit_completed" class="form-select" required>
                            <option value="0">Not Completed</option>
                            <option value="1">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

<script>
    // JS view mapping handler
    function viewBooking(b) {
        $('#view_booking_no').text(b.booking_no);
        $('#view_name').text(b.name);
        $('#view_email').text(b.email);
        $('#view_phone').text(b.phone);
        $('#view_trek').text(b.trek_title);
        $('#view_date').text(b.start_date);
        $('#view_qty').text(b.num_trekkers);
        $('#view_total').text('₹' + parseFloat(b.total_amount).toFixed(2));
        $('#view_discount').text('₹' + parseFloat(b.discount_amount).toFixed(2));
        $('#view_payable').text('₹' + parseFloat(b.payable_amount).toFixed(2));
        $('#view_payment').text(b.payment_status);
        $('#view_booking_status').text(b.booking_status);
        $('#view_completed').text(b.completed == 1 ? 'Completed' : 'Not Completed');
        $('#view_package').text(b.package_type === 'without_transport' ? 'Without Transport (Own Transport)' : 'With Transport');
        $('#view_pickup').text(b.pickup_loc_name ? b.pickup_loc_name : 'Own Transport Selected');
        
        // Render secondary travelers
        var travelersList = $('#view_travelers_list');
        travelersList.empty();
        try {
            var detailsObj = JSON.parse(b.details);
            if (detailsObj && detailsObj.length > 0) {
                detailsObj.forEach(function(t, idx) {
                    travelersList.append('<tr><td>' + (idx + 1) + '</td><td>' + escapeHtml(t.name) + '</td><td>' + escapeHtml(t.age) + '</td><td>' + escapeHtml(t.gender) + '</td></tr>');
                });
                $('#view_travelers_section').show();
            } else {
                $('#view_travelers_section').hide();
            }
        } catch(e) {
            $('#view_travelers_section').hide();
        }
        
        var modal = new bootstrap.Modal(document.getElementById('viewBookingModal'));
        modal.show();
    }

    // JS edit mapping handler
    function editBooking(b) {
        $('#edit_booking_id').val(b.id);
        $('#edit_booking_no').text(b.booking_no);
        $('#edit_name').val(b.name);
        $('#edit_email').val(b.email);
        $('#edit_phone').val(b.phone);
        $('#edit_num_trekkers').val(b.num_trekkers);
        $('#edit_payable_amount').val(b.payable_amount);
        $('#edit_payment_status').val(b.payment_status);
        $('#edit_booking_status').val(b.booking_status);
        $('#edit_completed').val(b.completed);
        
        var modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
        modal.show();
    }
    
    // HTML escaping helper
    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>

</body>
</html>
