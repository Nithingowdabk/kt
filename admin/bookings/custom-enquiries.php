<?php
/**
 * Admin - Manage Customized Trip Enquiries
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$filter_status = sanitize_input($_GET['status'] ?? '');

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_enquiry_status'])) {
    $enquiry_id = (int)$_POST['enquiry_id'];
    $status = sanitize_input($_POST['status'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE custom_trip_enquiries SET status = ? WHERE id = ?");
        $stmt->execute([$status, $enquiry_id]);
        
        set_flash_message('success', 'Enquiry status updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Status update failed: ' . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Build SQL query
$sql = "SELECT cte.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, t.title as trek_title FROM custom_trip_enquiries cte 
        INNER JOIN users u ON cte.user_id = u.id 
        INNER JOIN treks t ON cte.trek_id = t.id";
$params = [];

if (!empty($filter_status)) {
    $sql .= " WHERE cte.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY cte.id DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $enquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customized Trip Enquiries | Admin Portal</title>
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
                    <span class="text-muted small">Manage Customer Custom Trips</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Customized Trip Requests</h3>
                
                <div class="btn-group btn-group-sm">
                    <a href="<?php echo SITE_URL; ?>/admin/bookings/custom-enquiries.php" class="btn btn-outline-success <?php echo empty($filter_status) ? 'active' : ''; ?>">All</a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings/custom-enquiries.php?status=Pending" class="btn btn-outline-success <?php echo $filter_status === 'Pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings/custom-enquiries.php?status=Reviewed" class="btn btn-outline-success <?php echo $filter_status === 'Reviewed' ? 'active' : ''; ?>">Reviewed</a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings/custom-enquiries.php?status=Cancelled" class="btn btn-outline-success <?php echo $filter_status === 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
                </div>
            </div>

            <?php echo get_flash_message(); ?>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Info</th>
                                <th>Trek Title</th>
                                <th>Preferred Date</th>
                                <th>Participants</th>
                                <th>Pickup Location</th>
                                <th>Requirements</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($enquiries)): ?>
                                <?php foreach ($enquiries as $enq): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $enq['id']; ?></strong>
                                            <div class="text-muted small mt-1"><?php echo format_date($enq['created_at']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($enq['customer_name']); ?></div>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($enq['customer_email']); ?></small>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($enq['customer_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-bold"><?php echo htmlspecialchars($enq['trek_title']); ?></div>
                                        </td>
                                        <td><?php echo format_date($enq['preferred_date']); ?></td>
                                        <td class="text-center font-monospace fw-bold text-dark"><?php echo $enq['num_participants']; ?></td>
                                        <td><?php echo htmlspecialchars($enq['pickup_location']); ?></td>
                                        <td>
                                            <?php if (!empty($enq['special_requirements'])): ?>
                                                <small class="text-dark d-block text-wrap" style="max-width: 250px;">
                                                    <?php echo nl2br(htmlspecialchars($enq['special_requirements'])); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <form action="" method="POST">
                                            <input type="hidden" name="enquiry_id" value="<?php echo $enq['id']; ?>">
                                            <input type="hidden" name="update_enquiry_status" value="1">
                                            <td>
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $enq['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Reviewed" <?php echo $enq['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="Cancelled" <?php echo $enq['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No customized trip requests found.</td>
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
