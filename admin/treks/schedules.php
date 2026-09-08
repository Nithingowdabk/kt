<?php
/**
 * Admin - Manage Scheduled Trip Schedules & Leaders (Trek Scheduling Redesign)
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$trek_id = (int)($_GET['trek_id'] ?? 0);

if ($trek_id <= 0) {
    set_flash_message('danger', 'Invalid Trek selected.');
    header('Location: ' . SITE_URL . '/admin/treks/manage.php');
    exit();
}

// Fetch Trek details
try {
    $trek_stmt = $db->prepare("SELECT title, duration, price, offer_price, recurring_friday, recurring_saturday, recurring_sunday, recurring_until FROM treks WHERE id = ? LIMIT 1");
    $trek_stmt->execute([$trek_id]);
    $trek = $trek_stmt->fetch();
    
    if (!$trek) {
        set_flash_message('danger', 'Trek not found.');
        header('Location: ' . SITE_URL . '/admin/treks/manage.php');
        exit();
    }
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}

$error = '';
$success = '';

// Prefill values for Add Form (used for cloning as well)
$prefill_price = '';
$prefill_label = '';
$prefill_notes = '';
$prefill_leader_id = '';
$prefill_status = 'Active';

// Handle Cloning Prefills
if (isset($_GET['action']) && $_GET['action'] === 'clone' && isset($_GET['date_id'])) {
    $clone_id = (int)$_GET['date_id'];
    $clone_stmt = $db->prepare("SELECT * FROM trek_dates WHERE id = ? AND trek_id = ?");
    $clone_stmt->execute([$clone_id, $trek_id]);
    $clone_row = $clone_stmt->fetch();
    if ($clone_row) {
        $prefill_price = $clone_row['price'] ? (float)$clone_row['price'] : '';
        $prefill_label = $clone_row['label'];
        $prefill_notes = $clone_row['notes'] ?? '';
        $prefill_leader_id = $clone_row['trek_leader_id'];
        $prefill_status = $clone_row['status'];
        $success = "Config copied from schedule #" . $clone_id . ". Fill in dates below to create.";
    }
}

// Handle Disable Action
if (isset($_GET['action']) && $_GET['action'] === 'disable' && isset($_GET['date_id'])) {
    $date_id = (int)$_GET['date_id'];
    try {
        $disable_stmt = $db->prepare("UPDATE trek_dates SET status = 'Disabled' WHERE id = ? AND trek_id = ?");
        $disable_stmt->execute([$date_id, $trek_id]);
        log_activity('admin_disable_trip_schedule', ['trek_id' => $trek_id, 'date_id' => $date_id]);
        set_flash_message('success', 'Trip schedule marked as Disabled.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to disable schedule: ' . $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/admin/treks/schedules.php?trek_id=' . $trek_id);
    exit();
}

// Handle Enable Action
if (isset($_GET['action']) && $_GET['action'] === 'enable' && isset($_GET['date_id'])) {
    $date_id = (int)$_GET['date_id'];
    try {
        $enable_stmt = $db->prepare("UPDATE trek_dates SET status = 'Active' WHERE id = ? AND trek_id = ?");
        $enable_stmt->execute([$date_id, $trek_id]);
        log_activity('admin_enable_trip_schedule', ['trek_id' => $trek_id, 'date_id' => $date_id]);
        set_flash_message('success', 'Trip schedule marked as Active.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to enable schedule: ' . $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/admin/treks/schedules.php?trek_id=' . $trek_id);
    exit();
}

// Handle Date Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['date_id'])) {
    $date_id = (int)$_GET['date_id'];
    try {
        $del_stmt = $db->prepare("DELETE FROM trek_dates WHERE id = ? AND trek_id = ?");
        $del_stmt->execute([$date_id, $trek_id]);
        log_activity('admin_delete_trip_schedule', ['trek_id' => $trek_id, 'date_id' => $date_id]);
        set_flash_message('success', 'Trip schedule deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Cannot delete this schedule. Bookings may be linked to it.');
    }
    header('Location: ' . SITE_URL . '/admin/treks/schedules.php?trek_id=' . $trek_id);
    exit();
}

// Guess duration in days from text representation (e.g. "2 Days / 1 Night" -> 2)
$duration_days_guess = 2;
if (preg_match('/(\d+)\s*Day/i', $trek['duration'], $matches)) {
    $duration_days_guess = (int)$matches[1];
}

// 1. Process Automatic Weekend Schedule Configuration & Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recurring_schedule'])) {
    $recurring_friday = !empty($_POST['recurring_friday']) ? 1 : 0;
    $recurring_saturday = !empty($_POST['recurring_saturday']) ? 1 : 0;
    $recurring_sunday = !empty($_POST['recurring_sunday']) ? 1 : 0;
    $recurring_until = sanitize_input($_POST['recurring_until'] ?? '');

    try {
        $db->beginTransaction();

        // Save settings to treks table
        $up_trek = $db->prepare("UPDATE treks SET recurring_friday = ?, recurring_saturday = ?, recurring_sunday = ?, recurring_until = ? WHERE id = ?");
        $up_trek->execute([$recurring_friday, $recurring_saturday, $recurring_sunday, !empty($recurring_until) ? $recurring_until : null, $trek_id]);

        $generated_count = 0;

        // Auto-generate weekend dates if enabled
        if (($recurring_friday || $recurring_saturday || $recurring_sunday) && !empty($recurring_until)) {
            $current_time = time(); // Starts from today
            $until_time = strtotime($recurring_until);

            while ($current_time <= $until_time) {
                $day_name = date('D', $current_time);
                if (($recurring_friday && $day_name === 'Fri') || 
                    ($recurring_saturday && $day_name === 'Sat') || 
                    ($recurring_sunday && $day_name === 'Sun')) {
                    $batch_start = date('Y-m-d', $current_time);
                    
                    // calculate end date
                    $batch_end_time = strtotime("+" . ($duration_days_guess - 1) . " days", $current_time);
                    $batch_end = date('Y-m-d', $batch_end_time);

                    // Prevent duplicate start date creation
                    $dup_stmt = $db->prepare("SELECT COUNT(*) FROM trek_dates WHERE trek_id = ? AND start_date = ?");
                    $dup_stmt->execute([$trek_id, $batch_start]);
                    
                    if ($dup_stmt->fetchColumn() == 0) {
                        $ins_stmt = $db->prepare("INSERT INTO trek_dates (trek_id, start_date, end_date, seats, booked_seats, available_seats, price, label, schedule_type, status) VALUES (?, ?, ?, 20, 0, 20, NULL, NULL, 'auto', 'Active')");
                        $ins_stmt->execute([$trek_id, $batch_start, $batch_end]);
                        $generated_count++;
                    }
                }
                $current_time = strtotime("+1 day", $current_time);
            }
        }

        $db->commit();
        set_flash_message('success', 'Automatic Weekend Schedule saved successfully! Generated ' . $generated_count . ' date(s).');
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        set_flash_message('danger', 'Failed to save schedule settings: ' . $e->getMessage());
    }

    header('Location: ' . SITE_URL . '/admin/treks/schedules.php?trek_id=' . $trek_id);
    exit();
}

// 2. Process Special / Custom Trip Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_date'])) {
    $start_date = sanitize_input($_POST['start_date'] ?? '');
    $end_date = sanitize_input($_POST['end_date'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
    
    // Determine label
    $label = sanitize_input($_POST['label'] ?? '');
    $special_tag_select = sanitize_input($_POST['special_tag_select'] ?? '');
    if (!empty($special_tag_select) && $special_tag_select !== 'Custom') {
        $label = $special_tag_select;
    }

    $notes = sanitize_input($_POST['notes'] ?? '');
    $leader_id = !empty($_POST['trek_leader_id']) ? (int)$_POST['trek_leader_id'] : null;
    $status = sanitize_input($_POST['status'] ?? 'Active');
    $schedule_type = 'custom';

    if (empty($start_date)) {
        $error = "Please select a date.";
    } else {
        if (empty($end_date)) {
            $end_date_time = strtotime("+" . ($duration_days_guess - 1) . " days", strtotime($start_date));
            $end_date = date('Y-m-d', $end_date_time);
        }

        try {
            // Check duplicate start date
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM trek_dates WHERE trek_id = ? AND start_date = ?");
            $check_stmt->execute([$trek_id, $start_date]);
            if ($check_stmt->fetchColumn() > 0) {
                $error = "A schedule already exists for this trek on " . format_date($start_date) . ".";
            } else {
                $stmt = $db->prepare("INSERT INTO trek_dates (trek_id, start_date, end_date, seats, booked_seats, available_seats, price, label, notes, schedule_type, status, trek_leader_id) VALUES (?, ?, ?, 20, 0, 20, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $trek_id,
                    $start_date,
                    $end_date,
                    $price,
                    $label ?: null,
                    $notes ?: null,
                    $schedule_type,
                    $status,
                    $leader_id
                ]);
                log_activity('admin_add_trip_schedule_custom', ['trek_id' => $trek_id, 'start_date' => $start_date]);
                $success = "Custom Special Date scheduled successfully!";
            }
        } catch (PDOException $e) {
            $error = "Failed to add special date: " . $e->getMessage();
        }
    }
}

// 3. Process Edit Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_date'])) {
    $date_id = (int)($_POST['date_id'] ?? 0);
    $start_date = sanitize_input($_POST['start_date'] ?? '');
    $end_date = sanitize_input($_POST['end_date'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
    
    // Determine label
    $label = sanitize_input($_POST['label'] ?? '');
    $special_tag_select = sanitize_input($_POST['special_tag_select'] ?? '');
    if (!empty($special_tag_select) && $special_tag_select !== 'Custom') {
        $label = $special_tag_select;
    }

    $notes = sanitize_input($_POST['notes'] ?? '');
    $schedule_type = sanitize_input($_POST['schedule_type'] ?? 'auto');
    $leader_id = !empty($_POST['trek_leader_id']) ? (int)$_POST['trek_leader_id'] : null;
    $status = sanitize_input($_POST['status'] ?? 'Active');

    if ($date_id <= 0 || empty($start_date)) {
        $error = "Please fill in all required fields correctly.";
    } else {
        if (empty($end_date)) {
            $end_date_time = strtotime("+" . ($duration_days_guess - 1) . " days", strtotime($start_date));
            $end_date = date('Y-m-d', $end_date_time);
        }

        try {
            $stmt = $db->prepare("UPDATE trek_dates SET start_date = ?, end_date = ?, price = ?, label = ?, notes = ?, schedule_type = ?, status = ?, trek_leader_id = ? WHERE id = ? AND trek_id = ?");
            $stmt->execute([
                $start_date,
                $end_date,
                $price,
                $label ?: null,
                $notes ?: null,
                $schedule_type,
                $status,
                $leader_id,
                $date_id,
                $trek_id
            ]);
            log_activity('admin_edit_trip_schedule', ['trek_id' => $trek_id, 'date_id' => $date_id]);
            $success = "Trip schedule updated successfully!";
        } catch (PDOException $e) {
            $error = "Failed to update trip schedule: " . $e->getMessage();
        }
    }
}

// 4. Fetch scheduled dates & leaders
try {
    $dates_stmt = $db->prepare("SELECT td.*, tl.name as leader_name FROM trek_dates td
                                 LEFT JOIN trek_leaders tl ON td.trek_leader_id = tl.id
                                 WHERE td.trek_id = ? 
                                 ORDER BY td.start_date ASC");
    $dates_stmt->execute([$trek_id]);
    $trek_dates = $dates_stmt->fetchAll();
    
    // Fetch active trek leaders
    $leaders = $db->query("SELECT id, name FROM trek_leaders WHERE status = 'Active'")->fetchAll();
} catch (PDOException $e) {
    die("System fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trip Schedules | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    <style>
        .type-badge {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .type-auto {
            background-color: #e6f7ed;
            color: #198754;
        }
        .type-custom {
            background-color: #f3e8ff;
            color: #7c3aed;
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
                    <span class="text-muted small">Manage Trip Schedules</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Manage Schedules: <?php echo htmlspecialchars($trek['title']); ?></h3>
                    <p class="text-muted mb-0">Trek Hybrid Scheduling (Auto & Special Dates)</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/treks/manage.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Catalog</a>
            </div>

            <?php echo get_flash_message(); ?>
            <?php if (!empty($success)): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>

            <!-- Trek Master Summary Bar -->
            <div class="card border-0 shadow-sm p-3 mb-4 bg-light rounded-3">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Base Price</span>
                        <strong class="text-dark fs-5"><?php echo format_price($trek['offer_price'] > 0 ? $trek['offer_price'] : $trek['price']); ?></strong>
                        <?php if ($trek['offer_price'] > 0): ?>
                            <del class="text-muted small ms-1"><?php echo format_price($trek['price']); ?></del>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Duration</span>
                        <strong class="text-dark fs-5"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars($trek['duration']); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block">Schedules Count</span>
                        <strong class="text-success fs-5"><i class="far fa-calendar-alt me-1"></i><?php echo count($trek_dates); ?> scheduled</strong>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left: Scheduled Dates List -->
                <div class="col-lg-8">
                    <div class="admin-card">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2">
                         <i class="far fa-calendar-alt me-2"></i>Trip Schedules List
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-muted">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date Range</th>
                                        <th class="text-center">Price Override</th>
                                        <th>Label / Notes</th>
                                        <th>Leader</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($trek_dates)): ?>
                                        <?php foreach ($trek_dates as $date): 
                                            $status_badge = $date['status'] === 'Active' ? 'bg-success' : 'bg-danger';
                                            $custom_price = $date['price'] ? format_price($date['price']) : '<span class="text-muted small">Base fallback</span>';
                                            $is_auto = ($date['schedule_type'] ?? 'auto') === 'auto';
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="type-badge <?php echo $is_auto ? 'type-auto' : 'type-custom'; ?>">
                                                        <?php echo $is_auto ? 'Auto' : 'Custom'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-dark fw-bold"><?php echo format_date($date['start_date']); ?></div>
                                                    <small class="text-muted">to <?php echo format_date($date['end_date']); ?></small>
                                                </td>
                                                <td class="text-center fw-bold text-dark">
                                                    <?php echo $custom_price; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($date['label'])): ?>
                                                        <span class="badge bg-primary d-block mb-1 text-wrap" style="max-width: 150px;"><?php echo htmlspecialchars($date['label']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($date['notes'])): ?>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($date['notes']); ?>">
                                                            <i class="far fa-sticky-note me-1 text-warning"></i><?php echo htmlspecialchars($date['notes']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if (empty($date['label']) && empty($date['notes'])): ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $date['leader_name'] ? htmlspecialchars($date['leader_name']) : '<span class="text-danger small">Unassigned</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $status_badge; ?>"><?php echo $date['status']; ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                            <li><a class="dropdown-item btn-edit-batch" href="#" 
                                                                    data-id="<?php echo $date['id']; ?>"
                                                                    data-start="<?php echo $date['start_date']; ?>"
                                                                    data-end="<?php echo $date['end_date']; ?>"
                                                                    data-price="<?php echo $date['price']; ?>"
                                                                    data-tag="<?php echo htmlspecialchars($date['label'] ?? ''); ?>"
                                                                    data-notes="<?php echo htmlspecialchars($date['notes'] ?? ''); ?>"
                                                                    data-type="<?php echo htmlspecialchars($date['schedule_type'] ?? 'auto'); ?>"
                                                                    data-leader="<?php echo $date['trek_leader_id']; ?>"
                                                                    data-status="<?php echo $date['status']; ?>"
                                                                ><i class="fas fa-edit me-2 text-primary"></i> Edit Custom Date</a></li>
                                                            <li><a class="dropdown-item" href="?trek_id=<?php echo $trek_id; ?>&action=clone&date_id=<?php echo $date['id']; ?>#add-form-card"><i class="fas fa-clone me-2 text-warning"></i> Clone Config</a></li>
                                                            <?php if ($date['status'] === 'Active'): ?>
                                                                <li><a class="dropdown-item" href="?trek_id=<?php echo $trek_id; ?>&action=disable&date_id=<?php echo $date['id']; ?>" onclick="return confirm('Are you sure you want to disable this date?')"><i class="fas fa-ban me-2 text-danger"></i> Disable Date</a></li>
                                                            <?php else: ?>
                                                                <li><a class="dropdown-item" href="?trek_id=<?php echo $trek_id; ?>&action=enable&date_id=<?php echo $date['id']; ?>" onclick="return confirm('Are you sure you want to enable this date?')"><i class="fas fa-check-circle me-2 text-success"></i> Enable Date</a></li>
                                                            <?php endif; ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="?trek_id=<?php echo $trek_id; ?>&action=delete&date_id=<?php echo $date['id']; ?>" onclick="return confirm('Are you sure you want to delete this schedule?')"><i class="fas fa-trash-alt me-2"></i> Delete Date</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">No trip schedules created for this trek yet. Use the schedulers on the right.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Forms Tabs -->
                <div class="col-lg-4">
                    <div class="admin-card" id="add-form-card">
                        <ul class="nav nav-tabs mb-4 justify-content-center" id="formTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold text-success" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk-generator-pane" type="button" role="tab"><i class="fas fa-redo me-1"></i> Weekend Schedule</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold text-success" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-form-pane" type="button" role="tab"><i class="fas fa-star me-1"></i> Special Dates</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="formTabsContent">
                            <!-- Pane 1: Automatic Weekend Schedule Form -->
                            <div class="tab-pane fade show active" id="bulk-generator-pane" role="tabpanel">
                                <h5 class="fw-bold mb-2 text-dark">Automatic Weekend Schedule</h5>
                                <p class="text-muted mb-4 small">Configure recurring weekend dates. Future dates are generated automatically on save.</p>
                                
                                <form action="" method="POST">
                                    <input type="hidden" name="save_recurring_schedule" value="1">

                                    <div class="mb-3">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="recurring_friday" id="recurring_friday" value="1" <?php echo ($trek['recurring_friday'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark" for="recurring_friday">Every Friday</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="recurring_saturday" id="recurring_saturday" value="1" <?php echo ($trek['recurring_saturday'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark" for="recurring_saturday">Every Saturday</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="recurring_sunday" id="recurring_sunday" value="1" <?php echo ($trek['recurring_sunday'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark" for="recurring_sunday">Every Sunday</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label small text-muted fw-bold">Generate Until Date *</label>
                                        <input type="date" name="recurring_until" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($trek['recurring_until'] ?? date('Y-12-31')); ?>">
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Are you sure you want to save schedule configurations? Future dates will be auto-generated.')">
                                        <i class="fas fa-save me-1"></i> Save Schedule
                                    </button>
                                </form>
                            </div>

                            <!-- Pane 2: Single Date Special Trip Form -->
                            <div class="tab-pane fade" id="single-form-pane" role="tabpanel">
                                <h5 class="fw-bold mb-3 text-dark">Add Custom Date</h5>
                                <form action="" method="POST">
                                    <input type="hidden" name="add_date" value="1">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Date *</label>
                                        <input type="date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">End Date (Optional)</label>
                                        <input type="date" name="end_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                                        <small class="text-muted small">Will default based on trek duration if left blank.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Price Override (Optional)</label>
                                        <input type="number" name="price" class="form-control" step="0.01" placeholder="Leave empty for fallback pricing" value="<?php echo $prefill_price; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Label / Special Event *</label>
                                        <select name="special_tag_select" id="add_special_tag_select" class="form-select mb-2" onchange="toggleCustomTagInput(this, 'add_special_tag_custom_div')" required>
                                            <option value="">-- Select Label --</option>
                                            <option value="Independence Day Special" <?php echo $prefill_label === 'Independence Day Special' ? 'selected' : ''; ?>>Independence Day Special</option>
                                            <option value="Gandhi Jayanti Special" <?php echo $prefill_label === 'Gandhi Jayanti Special' ? 'selected' : ''; ?>>Gandhi Jayanti Special</option>
                                            <option value="Christmas Special" <?php echo $prefill_label === 'Christmas Special' ? 'selected' : ''; ?>>Christmas Special</option>
                                            <option value="Corporate Trek" <?php echo $prefill_label === 'Corporate Trek' ? 'selected' : ''; ?>>Corporate Trek</option>
                                            <option value="College Trek" <?php echo $prefill_label === 'College Trek' ? 'selected' : ''; ?>>College Trek</option>
                                            <option value="Custom" <?php echo (!empty($prefill_label) && !in_array($prefill_label, ['Independence Day Special', 'Gandhi Jayanti Special', 'Christmas Special', 'Corporate Trek', 'College Trek'])) ? 'selected' : ''; ?>>Other / Custom...</option>
                                        </select>
                                        <div id="add_special_tag_custom_div" style="<?php echo (!empty($prefill_label) && !in_array($prefill_label, ['Independence Day Special', 'Gandhi Jayanti Special', 'Christmas Special', 'Corporate Trek', 'College Trek'])) ? 'display:block;' : 'display:none;'; ?>">
                                            <input type="text" name="label" id="add_special_tag_custom" class="form-control" placeholder="Enter custom label" value="<?php echo htmlspecialchars($prefill_label); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Notes / Comments</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Special requirements, itineraries, etc."><?php echo htmlspecialchars($prefill_notes); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Assign Guide</label>
                                        <select name="trek_leader_id" class="form-select">
                                            <option value="">-- No Guide Assigned --</option>
                                            <?php foreach ($leaders as $leader): ?>
                                                <option value="<?php echo $leader['id']; ?>" <?php echo $prefill_leader_id == $leader['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($leader['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small text-muted fw-bold">Initial Status</label>
                                        <select name="status" class="form-select">
                                            <option value="Active" <?php echo $prefill_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Disabled" <?php echo $prefill_status === 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-calendar-plus me-1"></i> Add Custom Date
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editBatchModal" tabindex="-1" aria-labelledby="editBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="editBatchModalLabel"><i class="fas fa-edit me-2"></i>Edit Trip Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_date" value="1">
                    <input type="hidden" name="date_id" id="edit_date_id">
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Scheduling Type</label>
                        <select name="schedule_type" id="edit_schedule_type" class="form-select">
                            <option value="auto">Automatic Recurring Trip</option>
                            <option value="custom">Special / Custom Date Trip</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Start Date *</label>
                        <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">End Date (Optional)</label>
                        <input type="date" name="end_date" id="edit_end_date" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Custom Price Override</label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" placeholder="Leave empty for fallback pricing">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Special Label</label>
                        <select name="special_tag_select" id="edit_special_tag_select" class="form-select mb-2" onchange="toggleCustomTagInput(this, 'edit_special_tag_custom_div')">
                            <option value="">-- No Special Label / Clean --</option>
                            <option value="Independence Day Special">Independence Day Special</option>
                            <option value="Gandhi Jayanti Special">Gandhi Jayanti Special</option>
                            <option value="Christmas Special">Christmas Special</option>
                            <option value="Corporate Trek">Corporate Trek</option>
                            <option value="College Trek">College Trek</option>
                            <option value="Custom">Other / Custom...</option>
                        </select>
                        <div id="edit_special_tag_custom_div" style="display:none;">
                            <input type="text" name="label" id="edit_special_tag_custom" class="form-control" placeholder="Enter custom label">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Notes / Comments</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3" placeholder="Special requirements, info..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Assign Trek Leader / Guide</label>
                        <select name="trek_leader_id" id="edit_leader_id" class="form-select">
                            <option value="">-- No Guide Assigned --</option>
                            <?php foreach ($leaders as $leader): ?>
                                <option value="<?php echo $leader['id']; ?>"><?php echo htmlspecialchars($leader['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
<script>
function toggleCustomTagInput(selectEl, customDivId) {
    var customDiv = document.getElementById(customDivId);
    if (selectEl.value === 'Custom') {
        customDiv.style.display = 'block';
        customDiv.querySelector('input').setAttribute('required', 'required');
    } else {
        customDiv.style.display = 'none';
        customDiv.querySelector('input').removeAttribute('required');
    }
}

$(document).ready(function() {
    // Edit schedule action button
    $('.btn-edit-batch').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var start = $(this).data('start');
        var end = $(this).data('end');
        var price = $(this).data('price');
        var tag = $(this).data('tag');
        var notes = $(this).data('notes');
        var type = $(this).data('type');
        var leader = $(this).data('leader');
        var status = $(this).data('status');
        
        $('#edit_date_id').val(id);
        $('#edit_start_date').val(start);
        $('#edit_end_date').val(end);
        $('#edit_price').val(price ? price : '');
        $('#edit_notes').val(notes);
        $('#edit_schedule_type').val(type);
        $('#edit_leader_id').val(leader ? leader : '');
        $('#edit_status').val(status);
        
        // Setup label selectors
        var standardTags = [
            'Independence Day Special',
            'Gandhi Jayanti Special',
            'Christmas Special',
            'Corporate Trek',
            'College Trek'
        ];
        
        if (tag === '') {
            $('#edit_special_tag_select').val('');
            $('#edit_special_tag_custom_div').hide();
            $('#edit_special_tag_custom').val('').removeAttribute('required');
        } else if (standardTags.indexOf(tag) !== -1) {
            $('#edit_special_tag_select').val(tag);
            $('#edit_special_tag_custom_div').hide();
            $('#edit_special_tag_custom').val('').removeAttribute('required');
        } else {
            $('#edit_special_tag_select').val('Custom');
            $('#edit_special_tag_custom_div').show();
            $('#edit_special_tag_custom').val(tag).setAttribute('required', 'required');
        }
        
        var editModal = new bootstrap.Modal(document.getElementById('editBatchModal'));
        editModal.show();
    });
});
</script>
</body>
</html>
