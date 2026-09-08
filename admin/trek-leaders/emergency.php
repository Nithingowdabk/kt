<?php
/**
 * Trek Leader Portal - Emergency Contact Directory
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce trek leader login
require_leader_login();

$db = Database::connect();
$leader_id = get_logged_in_leader_id();
$date_id = (int)($_GET['date_id'] ?? 0);

if ($date_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

try {
    // 1. Verify trip schedule ownership and fetch details
    $batch_stmt = $db->prepare("SELECT td.*, t.title as trek_title FROM trek_dates td
                                INNER JOIN treks t ON td.trek_id = t.id
                                WHERE td.id = ? AND td.trek_leader_id = ? LIMIT 1");
    $batch_stmt->execute([$date_id, $leader_id]);
    $batch = $batch_stmt->fetch();

    if (!$batch) {
        set_flash_message('danger', 'Unauthorized access or trip schedule not found.');
        header('Location: dashboard.php');
        exit();
    }

    // 2. Fetch confirmed participant contacts
    $bookings_stmt = $db->prepare("SELECT name, email, phone, booking_no, details FROM bookings 
                                   WHERE trek_date_id = ? AND booking_status = 'Confirmed'
                                   ORDER BY id ASC");
    $bookings_stmt->execute([$date_id]);
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
    <title>Emergency Directory | Trek Leader Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="container p-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h2 class="fw-bold mb-0">Emergency Contacts: <?php echo htmlspecialchars($batch['trek_title']); ?></h2>
            <p class="text-muted mb-0">Quick directory for emergency services</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <!-- Emergency Directory -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
        <h5 class="fw-bold text-danger mb-4"><i class="fas fa-phone-alt me-2 animate-pulse"></i>Emergency Contacts</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-muted">
                <thead>
                    <tr class="table-light">
                        <th>Trekker Name</th>
                        <th>Mobile Number</th>
                        <th>Email Address</th>
                        <th>Roster Group / Lead</th>
                        <th>Booking Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $b): ?>
                            <!-- Lead Trekker Contact Card Row -->
                            <tr class="table-danger-subtle">
                                <td><strong class="text-dark fs-6"><?php echo htmlspecialchars($b['name']); ?></strong> <span class="badge bg-danger ms-1">Lead</span></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($b['phone']); ?>" class="text-decoration-none fw-bold text-dark">
                                        <i class="fas fa-phone-alt me-1 text-success"></i> <?php echo htmlspecialchars($b['phone']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($b['email']); ?></td>
                                <td>Primary Contact</td>
                                <td><strong><?php echo htmlspecialchars($b['booking_no']); ?></strong></td>
                            </tr>

                            <!-- Parse Co-Trekkers and show they belong to this lead -->
                            <?php 
                                $co_trekkers = json_decode($b['details'], true);
                                if (is_array($co_trekkers)):
                                    foreach ($co_trekkers as $co):
                            ?>
                                <tr>
                                    <td><span class="ps-3 text-muted">&bull; <?php echo htmlspecialchars($co['name'] ?? ''); ?></span></td>
                                    <td>
                                        <span class="text-muted">Use Lead's Phone</span>
                                    </td>
                                    <td>N/A</td>
                                    <td>Lead: <strong><?php echo htmlspecialchars($b['name']); ?></strong> (<?php echo htmlspecialchars($b['phone']); ?>)</td>
                                    <td><strong><?php echo htmlspecialchars($b['booking_no']); ?></strong></td>
                                </tr>
                            <?php 
                                    endforeach;
                                endif;
                            ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No confirmed participants for this trip schedule.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
