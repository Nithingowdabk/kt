<?php
/**
 * Admin - Manage Callback Requests (Leads / CRM)
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

// AJAX handler for loading Trek dates and pickup points
if (isset($_GET['ajax_trek_details'])) {
    header('Content-Type: application/json');
    $trek_id = (int)$_GET['ajax_trek_details'];
    
    // Fetch active/future dates
    $dates_stmt = $db->prepare("SELECT id, start_date, end_date, price FROM trek_dates WHERE trek_id = ? AND status = 'Active' AND start_date >= CURDATE() ORDER BY start_date ASC");
    $dates_stmt->execute([$trek_id]);
    $dates = $dates_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch pickup points
    $pickups_stmt = $db->prepare("SELECT id, location, time FROM pickup_points WHERE trek_id = ? ORDER BY location ASC");
    $pickups_stmt->execute([$trek_id]);
    $pickups = $pickups_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'dates' => $dates,
        'pickups' => $pickups
    ]);
    exit();
}

// Handle status updates from inline select
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_callback_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = sanitize_input($_POST['status'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE callback_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);
        set_flash_message('success', 'Callback request status updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Status update failed: ' . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit();
}

// Handle details, notes, priority updates from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lead_details'])) {
    $request_id = (int)$_POST['request_id'];
    $status = sanitize_input($_POST['status'] ?? '');
    $priority = sanitize_input($_POST['priority'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE callback_requests SET status = ?, priority = ?, notes = ? WHERE id = ?");
        $stmt->execute([$status, $priority, $notes, $request_id]);
        set_flash_message('success', 'Lead details updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Update failed: ' . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit();
}

// Handle Lead Conversion to Confirmed Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_lead_to_booking'])) {
    $request_id = (int)$_POST['request_id'];
    $trek_date_id = (int)$_POST['trek_date_id'];
    $pickup_point_id = !empty($_POST['pickup_point_id']) ? (int)$_POST['pickup_point_id'] : null;
    $email = sanitize_input($_POST['email'] ?? '');
    $payable_amount = (float)($_POST['payable_amount'] ?? 0);
    $num_trekkers = (int)$_POST['num_trekkers'];

    if (empty($email)) {
        $email = 'guest@karnatakatrekkers.com';
    }

    try {
        $db->beginTransaction();

        // 1. Fetch Lead Details
        $lead_stmt = $db->prepare("SELECT * FROM callback_requests WHERE id = ? FOR UPDATE");
        $lead_stmt->execute([$request_id]);
        $lead = $lead_stmt->fetch();

        if (!$lead) {
            throw new Exception("Lead not found.");
        }

        if ($lead['status'] === 'Converted') {
            throw new Exception("This lead has already been converted.");
        }

        if (empty($lead['trek_id'])) {
            throw new Exception("This lead does not have a preferred trek. Select a trek before converting.");
        }

        // 2. Fetch Trek Date details to verify availability
        $date_stmt = $db->prepare("SELECT * FROM trek_dates WHERE id = ? FOR UPDATE");
        $date_stmt->execute([$trek_date_id]);
        $date_row = $date_stmt->fetch();

        if (!$date_row || $date_row['status'] !== 'Active') {
            throw new Exception("The selected trek date is not active.");
        }

        // 3. Generate booking ref
        $booking_no = generate_booking_no();

        // 4. Insert into bookings
        $booking_stmt = $db->prepare("INSERT INTO bookings (
            booking_no, user_id, trek_id, trek_date_id, num_trekkers, 
            total_amount, discount_amount, payable_amount, pickup_point_id, 
            payment_status, booking_status, name, email, phone, details
        ) VALUES (?, NULL, ?, ?, ?, ?, 0.00, ?, ?, 'Paid', 'Confirmed', ?, ?, ?, '[]')");

        $booking_stmt->execute([
            $booking_no,
            $lead['trek_id'],
            $trek_date_id,
            $num_trekkers,
            $payable_amount,
            $payable_amount,
            $pickup_point_id,
            $lead['name'],
            $email,
            $lead['phone']
        ]);

        // 6. Update lead status in callback_requests
        $sys_notes = ($lead['notes'] ? $lead['notes'] . "\n" : "") . "[System] Converted to booking #" . $booking_no . " on " . date('Y-m-d H:i:s');
        $update_lead_stmt = $db->prepare("UPDATE callback_requests SET status = 'Converted', notes = ? WHERE id = ?");
        $update_lead_stmt->execute([$sys_notes, $request_id]);

        // Log administrative action
        log_activity('convert_lead_to_booking', [
            'booking_no' => $booking_no,
            'lead_id' => $request_id,
            'name' => $lead['name'],
            'trek_id' => $lead['trek_id']
        ]);

        $db->commit();
        set_flash_message('success', 'Lead successfully converted to booking #' . $booking_no);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        set_flash_message('danger', 'Conversion failed: ' . $e->getMessage());
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit();
}

// Fetch dashboard statistics
$stats_query = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) as new,
    SUM(CASE WHEN status = 'Contacted' THEN 1 ELSE 0 END) as contacted,
    SUM(CASE WHEN status = 'Interested' THEN 1 ELSE 0 END) as interested,
    SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted
FROM callback_requests")->fetch(PDO::FETCH_ASSOC);

$total_leads = $stats_query['total'] ?? 0;
$new_leads = $stats_query['new'] ?? 0;
$contacted_leads = $stats_query['contacted'] ?? 0;
$interested_leads = $stats_query['interested'] ?? 0;
$converted_leads = $stats_query['converted'] ?? 0;
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 1) : 0;

// Filters and Search query setup
$search = sanitize_input($_GET['search'] ?? '');
$filter_status = sanitize_input($_GET['status'] ?? '');
$filter_priority = sanitize_input($_GET['priority'] ?? '');

$sql = "SELECT cr.*, t.title as trek_title, t.price as trek_price, t.offer_price as trek_offer_price FROM callback_requests cr 
        LEFT JOIN treks t ON cr.trek_id = t.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (cr.name LIKE ? OR cr.phone LIKE ? OR t.title LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($filter_status)) {
    $sql .= " AND cr.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_priority)) {
    $sql .= " AND cr.priority = ?";
    $params[] = $filter_priority;
}

$sql .= " ORDER BY cr.created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads / Callback CRM | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    
    <style>
        /* Modern Gradient Statistics Cards */
        .stat-card-gradient {
            border: none;
            border-radius: 12px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card-gradient:hover {
            transform: translateY(-3px);
        }
        .bg-gradient-total { background: linear-gradient(135deg, #4f46e5, #3b82f6); }
        .bg-gradient-new { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .bg-gradient-contacted { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .bg-gradient-interested { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .bg-gradient-converted { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-gradient-rate { background: linear-gradient(135deg, #ec4899, #db2777); }
        
        .stat-card-icon {
            position: absolute;
            right: 15px;
            bottom: 10px;
            font-size: 3.5rem;
            opacity: 0.15;
        }
        
        /* Badges for Priority */
        .badge-high { background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
        .badge-medium { background-color: #fef3c7; color: #d97706; border: 1px solid #fcd34d; }
        .badge-low { background-color: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
        
        /* Mobile responsive card layouts */
        @media (max-width: 767.98px) {
            .table-responsive-mobile table thead {
                display: none;
            }
            .table-responsive-mobile table tbody, 
            .table-responsive-mobile table tr, 
            .table-responsive-mobile table td {
                display: block;
                width: 100%;
            }
            .table-responsive-mobile table tr {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .table-responsive-mobile table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 8px 0;
                border-bottom: 1px solid #f3f4f6;
            }
            .table-responsive-mobile table td:last-child {
                border-bottom: none;
                padding-top: 12px;
                justify-content: flex-end;
                gap: 8px;
            }
            .table-responsive-mobile table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #374151;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
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
                    <span class="text-muted small">Trekking Leads CRM</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            
            <!-- CRM Header -->
            <div class="mb-4">
                <h3 class="fw-bold mb-1">Leads & Callback requests</h3>
                <p class="text-muted mb-0">Track customer enquiries, internal notes, priority levels, and convert leads to confirmed bookings.</p>
            </div>

            <?php echo get_flash_message(); ?>

            <!-- Dashboard Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-total p-3">
                        <div class="small opacity-75">Total Leads</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $total_leads; ?></div>
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-new p-3">
                        <div class="small opacity-75">New Leads</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $new_leads; ?></div>
                        <div class="stat-card-icon"><i class="fas fa-star"></i></div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-contacted p-3">
                        <div class="small opacity-75">Contacted</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $contacted_leads; ?></div>
                        <div class="stat-card-icon"><i class="fas fa-phone-alt"></i></div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-interested p-3">
                        <div class="small opacity-75">Interested</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $interested_leads; ?></div>
                        <div class="stat-card-icon"><i class="fas fa-heart"></i></div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-converted p-3">
                        <div class="small opacity-75">Converted</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $converted_leads; ?></div>
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card-gradient bg-gradient-rate p-3">
                        <div class="small opacity-75">Conversion Rate</div>
                        <div class="fs-3 fw-bold mt-1"><?php echo $conversion_rate; ?>%</div>
                        <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <!-- Search, Filter & Controls Panel -->
            <div class="admin-card mb-4 p-3 bg-white border rounded shadow-sm">
                <form method="GET" action="" class="row g-2">
                    <!-- Text Search -->
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, phone, trek..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <!-- Status Filter -->
                    <div class="col-6 col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Statuses --</option>
                            <option value="New" <?php echo $filter_status === 'New' ? 'selected' : ''; ?>>New</option>
                            <option value="Contacted" <?php echo $filter_status === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                            <option value="Interested" <?php echo $filter_status === 'Interested' ? 'selected' : ''; ?>>Interested</option>
                            <option value="Converted" <?php echo $filter_status === 'Converted' ? 'selected' : ''; ?>>Converted</option>
                            <option value="Closed" <?php echo $filter_status === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <!-- Priority Filter -->
                    <div class="col-6 col-md-3">
                        <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Priorities --</option>
                            <option value="High" <?php echo $filter_priority === 'High' ? 'selected' : ''; ?>>🔥 High</option>
                            <option value="Medium" <?php echo $filter_priority === 'Medium' ? 'selected' : ''; ?>>🟡 Medium</option>
                            <option value="Low" <?php echo $filter_priority === 'Low' ? 'selected' : ''; ?>>⚪ Low</option>
                        </select>
                    </div>
                    <!-- Reset/Search buttons -->
                    <div class="col-12 col-md-2 d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-success btn-sm w-100">Search</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Leads Table / Responsive Cards -->
            <div class="admin-card">
                <div class="table-responsive table-responsive-mobile">
                    <table class="table table-hover table-custom align-middle text-muted mb-0">
                        <thead>
                            <tr>
                                <th>ID & Created</th>
                                <th>Customer Info</th>
                                <th>Trek Name</th>
                                <th class="text-center">Pax</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $req): 
                                    $p_badge = 'badge-low';
                                    $p_icon = '⚪ Low';
                                    if ($req['priority'] === 'High') {
                                        $p_badge = 'badge-high';
                                        $p_icon = '🔥 High';
                                    } elseif ($req['priority'] === 'Medium') {
                                        $p_badge = 'badge-medium';
                                        $p_icon = '🟡 Medium';
                                    }
                                ?>
                                    <tr>
                                        <!-- ID -->
                                        <td data-label="ID / Created">
                                            <strong>#<?php echo $req['id']; ?></strong>
                                            <div class="text-muted small mt-1"><?php echo format_date($req['created_at']); ?></div>
                                        </td>
                                        <!-- Customer Info -->
                                        <td data-label="Customer Info">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($req['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($req['phone']); ?></small>
                                        </td>
                                        <!-- Trek -->
                                        <td data-label="Trek">
                                            <div class="text-dark fw-bold"><?php echo htmlspecialchars($req['trek_title'] ?: 'General Enquiry'); ?></div>
                                            <?php if ($req['preferred_date']): ?>
                                                <small class="text-muted">Date: <?php echo format_date($req['preferred_date']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Participants -->
                                        <td data-label="Participants" class="text-center font-monospace fw-bold text-dark">
                                            <?php echo $req['participants'] ?: '1'; ?>
                                        </td>
                                        <!-- Priority -->
                                        <td data-label="Priority">
                                            <span class="badge px-2.5 py-1.5 rounded-pill <?php echo $p_badge; ?>">
                                                <?php echo $p_icon; ?>
                                            </span>
                                        </td>
                                        <!-- Status Inline form -->
                                        <td data-label="Status">
                                            <form action="" method="POST" style="margin:0;">
                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                <input type="hidden" name="update_callback_status" value="1">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="New" <?php echo $req['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                    <option value="Contacted" <?php echo $req['status'] === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                    <option value="Interested" <?php echo $req['status'] === 'Interested' ? 'selected' : ''; ?>>Interested</option>
                                                    <option value="Converted" <?php echo $req['status'] === 'Converted' ? 'selected' : ''; ?>>Converted</option>
                                                    <option value="Closed" <?php echo $req['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </form>
                                        </td>
                                        <!-- Actions -->
                                        <td data-label="Actions" class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="tel:<?php echo $req['phone']; ?>" class="btn btn-sm btn-outline-primary" title="Call Lead">
                                                    <i class="fas fa-phone-alt"></i> <span class="d-none d-md-inline">Call</span>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success btn-view-details" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#leadDetailsModal"
                                                        data-id="<?php echo $req['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($req['name']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($req['phone']); ?>"
                                                        data-trek-id="<?php echo $req['trek_id'] ?? ''; ?>"
                                                        data-trek-title="<?php echo htmlspecialchars($req['trek_title'] ?: 'General Enquiry'); ?>"
                                                        data-trek-price="<?php echo $req['trek_offer_price'] > 0 ? $req['trek_offer_price'] : ($req['trek_price'] ?? 0); ?>"
                                                        data-date="<?php echo $req['preferred_date'] ?: 'Not Specified'; ?>"
                                                        data-participants="<?php echo $req['participants'] ?: '1'; ?>"
                                                        data-message="<?php echo htmlspecialchars($req['message'] ?? ''); ?>"
                                                        data-notes="<?php echo htmlspecialchars($req['notes'] ?? ''); ?>"
                                                        data-priority="<?php echo $req['priority']; ?>"
                                                        data-status="<?php echo $req['status']; ?>"
                                                        data-created="<?php echo format_date($req['created_at']); ?>">
                                                    <i class="fas fa-eye"></i> <span class="d-none d-md-inline">View</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No callback requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Lead Details & CRM Actions Modal -->
<div class="modal fade" id="leadDetailsModal" tabindex="-1" aria-labelledby="leadDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="leadDetailsModalLabel"><i class="fas fa-user-tie me-2"></i>Lead CRM Hub</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-start">
                <div class="row g-4">
                    
                    <!-- Left Column: Lead Information -->
                    <div class="col-md-6 border-end">
                        <h5 class="fw-bold text-success mb-3"><i class="fas fa-info-circle me-1"></i>Customer Details</h5>
                        <table class="table table-sm table-borderless align-middle text-muted">
                            <tr>
                                <th width="40%">Name</th>
                                <td class="text-dark fw-bold" id="modal_name"></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>
                                    <a href="" id="modal_phone_link" class="text-decoration-none fw-bold text-primary"></a>
                                    <a href="" id="modal_call_btn" class="btn btn-xs btn-outline-primary ms-2 py-0 px-1"><i class="fas fa-phone-alt text-xs"></i> Call</a>
                                </td>
                            </tr>
                            <tr>
                                <th>Preferred Trek</th>
                                <td class="text-dark fw-bold" id="modal_trek_title"></td>
                            </tr>
                            <tr>
                                <th>Preferred Date</th>
                                <td id="modal_pref_date"></td>
                            </tr>
                            <tr>
                                <th>Participants</th>
                                <td class="fw-bold text-dark" id="modal_participants"></td>
                            </tr>
                            <tr>
                                <th>Enquiry Message</th>
                                <td class="text-wrap text-dark" id="modal_message" style="font-style: italic;"></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td id="modal_created_date"></td>
                            </tr>
                        </table>
                        
                        <!-- Convert to Booking panel -->
                        <div id="convert_booking_section" class="mt-4 p-3 rounded bg-light border">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-ticket-alt me-1 text-success"></i>Convert To Booking</h6>
                            
                            <form action="" method="POST" id="convertBookingForm">
                                <input type="hidden" name="convert_lead_to_booking" value="1">
                                <input type="hidden" name="request_id" id="convert_request_id">
                                <input type="hidden" name="num_trekkers" id="convert_num_trekkers">

                                <div class="mb-2">
                                    <label class="form-label small text-muted fw-bold">Select Trek Date Schedule *</label>
                                    <select name="trek_date_id" id="convert_trek_date_id" class="form-select form-select-sm" required>
                                        <option value="">-- Loading schedules... --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted fw-bold">Select Pickup Point (Optional)</label>
                                    <select name="pickup_point_id" id="convert_pickup_point_id" class="form-select form-select-sm">
                                        <option value="">-- None --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted fw-bold">Customer Email Address</label>
                                    <input type="email" name="email" id="convert_email" class="form-control form-control-sm" placeholder="customer@example.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Payable Amount (INR) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="payable_amount" id="convert_payable_amount" class="form-control" required min="0" step="0.01">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold"><i class="fas fa-exchange-alt me-1"></i>Create Confirmed Booking</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right Column: Notes & Priority Override Form -->
                    <div class="col-md-6">
                        <h5 class="fw-bold text-success mb-3"><i class="fas fa-cog me-1"></i>Lead Controls</h5>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="update_lead_details" value="1">
                            <input type="hidden" name="request_id" id="modal_request_id">
                            
                            <!-- Priority Select -->
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Lead Priority</label>
                                <select name="priority" id="modal_priority" class="form-select form-select-sm">
                                    <option value="High">🔥 High</option>
                                    <option value="Medium">🟡 Medium</option>
                                    <option value="Low">⚪ Low</option>
                                </select>
                            </div>
                            
                            <!-- Status Select -->
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Lead Status</label>
                                <select name="status" id="modal_status" class="form-select form-select-sm">
                                    <option value="New">New</option>
                                    <option value="Contacted">Contacted</option>
                                    <option value="Interested">Interested</option>
                                    <option value="Converted">Converted</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            
                            <!-- Internal Staff Notes -->
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Internal Staff Notes</label>
                                <textarea name="notes" id="modal_notes" class="form-control" rows="8" placeholder="Type internal logs here... (e.g. Call after 6 PM, interested in August batch)"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-sm btn-success fw-bold">Save CRM Details</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

<script>
$(document).ready(function() {
    $('.btn-view-details').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var phone = $(this).data('phone');
        var trekId = $(this).data('trek-id');
        var trekTitle = $(this).data('trek-title');
        var trekPrice = $(this).data('trek-price');
        var date = $(this).data('date');
        var participants = $(this).data('participants');
        var message = $(this).data('message');
        var notes = $(this).data('notes');
        var priority = $(this).data('priority');
        var status = $(this).data('status');
        var created = $(this).data('created');

        // Populate left column lead details
        $('#modal_name').text(name);
        $('#modal_phone_link').text(phone).attr('href', 'tel:' + phone);
        $('#modal_call_btn').attr('href', 'tel:' + phone);
        $('#modal_trek_title').text(trekTitle);
        $('#modal_pref_date').text(date);
        $('#modal_participants').text(participants);
        $('#modal_message').text(message ? '"' + message + '"' : '-');
        $('#modal_created_date').text(created);

        // Populate right column forms
        $('#modal_request_id').val(id);
        $('#modal_priority').val(priority);
        $('#modal_status').val(status);
        $('#modal_notes').val(notes);
        
        // Populate conversion panel
        $('#convert_request_id').val(id);
        $('#convert_num_trekkers').val(participants);
        $('#convert_payable_amount').val(parseFloat(trekPrice) * parseInt(participants));
        
        // Hide conversion panel if status is already Converted
        if (status === 'Converted') {
            $('#convert_booking_section').hide();
        } else if (trekId) {
            $('#convert_booking_section').show();
            // Fetch schedules & pickup points for this trek
            $('#convert_trek_date_id').html('<option value="">-- Loading schedules... --</option>');
            $('#convert_pickup_point_id').html('<option value="">-- Loading pickups... --</option>');
            
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'GET',
                data: { ajax_trek_details: trekId },
                dataType: 'json',
                success: function(res) {
                    // Load Dates
                    var dateOpts = '';
                    if (res.dates && res.dates.length > 0) {
                        $.each(res.dates, function(i, d) {
                            var dateLabel = d.start_date;
                            dateOpts += '<option value="' + d.id + '" data-price="' + d.price + '">' + dateLabel + '</option>';
                        });
                    } else {
                        dateOpts = '<option value="">No active scheduled dates found</option>';
                    }
                    $('#convert_trek_date_id').html(dateOpts);

                    // Load Pickup points
                    var pickupOpts = '<option value="">-- None --</option>';
                    if (res.pickups && res.pickups.length > 0) {
                        $.each(res.pickups, function(i, p) {
                            pickupOpts += '<option value="' + p.id + '">' + p.location + ' (' + p.time + ')</option>';
                        });
                    }
                    $('#convert_pickup_point_id').html(pickupOpts);
                    
                    // Trigger price update when date selected
                    $('#convert_trek_date_id').on('change', function() {
                        var opt = $(this).find('option:selected');
                        var batchPrice = opt.data('price');
                        if (batchPrice && parseFloat(batchPrice) > 0) {
                            $('#convert_payable_amount').val(parseFloat(batchPrice) * parseInt(participants));
                        } else {
                            $('#convert_payable_amount').val(parseFloat(trekPrice) * parseInt(participants));
                        }
                    }).trigger('change');
                },
                error: function() {
                    $('#convert_trek_date_id').html('<option value="">Error loading schedules</option>');
                    $('#convert_pickup_point_id').html('<option value="">Error loading pickups</option>');
                }
            });
        } else {
            $('#convert_booking_section').hide(); // Hide if no trek_id is selected
        }
    });
});
</script>

</body>
</html>
