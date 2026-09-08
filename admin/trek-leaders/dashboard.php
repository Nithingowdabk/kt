<?php
/**
 * Trek Leader Portal - Dashboard
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce trek leader login
require_leader_login();

$db = Database::connect();
$leader_id = get_logged_in_leader_id();

try {
    // Fetch assigned treks
    $stmt = $db->prepare("SELECT td.*, t.title as trek_title, t.slug as trek_slug, t.duration, t.difficulty,
                            (SELECT COUNT(*) FROM bookings WHERE trek_date_id = td.id AND booking_status = 'Confirmed') as total_bookings,
                            cr.id as report_id
                          FROM trek_dates td
                          INNER JOIN treks t ON td.trek_id = t.id
                          LEFT JOIN trek_completion_reports cr ON td.id = cr.trek_date_id
                          WHERE td.trek_leader_id = ?
                          ORDER BY td.start_date DESC");
    $stmt->execute([$leader_id]);
    $assigned_treks = $stmt->fetchAll();
    
    // Fetch leader stats
    $total_assigned = count($assigned_treks);
    
    $completed_stmt = $db->prepare("SELECT COUNT(*) FROM trek_dates WHERE trek_leader_id = ? AND start_date < CURDATE()");
    $completed_stmt->execute([$leader_id]);
    $completed_treks = $completed_stmt->fetchColumn();

} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trek Leader Dashboard | Karnataka Trekkers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar / Top bar for Trek Leader -->
    <div id="page-content-wrapper" class="w-100">
        <!-- Top navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom px-3">
            <div class="container-fluid">
                <span class="navbar-brand fw-bold text-success"><i class="fas fa-mountain me-2"></i>Trek Leader Portal</span>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="text-white-50 small">Welcome, <strong class="text-white"><?php echo htmlspecialchars($_SESSION['leader_name']); ?></strong></span>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-power-off me-1"></i>Logout</a>
                </div>
            </div>
        </nav>

        <!-- Main Container -->
        <div class="container p-4" style="max-width: 1200px;">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h2 class="fw-bold mb-0">Assigned Treks</h2>
                <span class="text-muted small">Server Time: <?php echo date('d M Y, h:i A'); ?></span>
            </div>

            <?php echo get_flash_message(); ?>

            <!-- Stats Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 bg-white rounded-3 d-flex flex-row align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block mb-1">Total Assigned Schedules</span>
                            <h3 class="fw-bold mb-0 text-success"><?php echo $total_assigned; ?></h3>
                        </div>
                        <div class="fs-1 text-success opacity-50"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 bg-white rounded-3 d-flex flex-row align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block mb-1">Completed Schedules</span>
                            <h3 class="fw-bold mb-0 text-primary"><?php echo $completed_treks; ?></h3>
                        </div>
                        <div class="fs-1 text-primary opacity-50"><i class="fas fa-check-double"></i></div>
                    </div>
                </div>
            </div>

            <!-- Roster Table Card -->
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <h5 class="fw-bold text-success mb-3"><i class="fas fa-tasks me-2"></i>My Trip Schedules</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-muted">
                        <thead>
                            <tr class="table-light">
                                <th>Trek Name</th>
                                <th>Schedule Date</th>
                                <th class="text-center">Bookings Count</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Roster Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assigned_treks)): ?>
                                <?php foreach ($assigned_treks as $t): 
                                    $is_completed = (strtotime($t['start_date']) < strtotime(date('Y-m-d')));
                                    $status_badge = $t['status'] === 'Active' ? 'bg-success' : ($t['status'] === 'Full' ? 'bg-warning text-dark' : 'bg-danger');
                                    
                                    // If trek is completed in past, override status badge visually
                                    if ($is_completed) {
                                        $status_badge = 'bg-secondary';
                                        $status_text = 'Completed';
                                    } else {
                                        $status_text = $t['status'];
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($t['trek_title']); ?></div>
                                            <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars($t['duration']); ?> | <?php echo htmlspecialchars($t['difficulty']); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-bold"><?php echo format_date($t['start_date']) . ' to ' . format_date($t['end_date']); ?></div>
                                            <small class="text-muted">Start in: <?php echo round((strtotime($t['start_date']) - time()) / 86400); ?> days</small>
                                        </td>
                                        <td class="text-center text-dark fw-bold"><?php echo $t['total_bookings']; ?> booking(s)</td>
                                        <td class="text-center"><span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-success dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Manage Schedule
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                    <li><a class="dropdown-item" href="participants.php?date_id=<?php echo $t['id']; ?>"><i class="fas fa-users text-muted me-2"></i> Trekkers List</a></li>
                                                    <li><a class="dropdown-item" href="attendance.php?date_id=<?php echo $t['id']; ?>"><i class="fas fa-clipboard-check text-muted me-2"></i> Mark Attendance</a></li>
                                                    <li><a class="dropdown-item" href="emergency.php?date_id=<?php echo $t['id']; ?>"><i class="fas fa-exclamation-triangle text-muted me-2"></i> Emergency Contacts</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php if ($t['report_id']): ?>
                                                        <li><a class="dropdown-item disabled text-success" href="#"><i class="fas fa-check-circle me-2"></i> Report Submitted</a></li>
                                                    <?php else: ?>
                                                        <li><a class="dropdown-item text-primary fw-bold" href="reports.php?date_id=<?php echo $t['id']; ?>"><i class="fas fa-file-invoice me-2"></i> Submit Report</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">No assigned trip schedules found. Contact Administrator to allocate schedules to you.</td>
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
