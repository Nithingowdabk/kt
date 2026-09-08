<?php
/**
 * Admin - Manage Pickup Points
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
    $trek_stmt = $db->prepare("SELECT title FROM treks WHERE id = ? LIMIT 1");
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

// 1. Process Pickup Point Addition & Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_mode'])) {
    $action_mode = sanitize_input($_POST['action_mode']);
    $pickup_id = (int)($_POST['pickup_id'] ?? 0);
    $location = sanitize_input($_POST['location'] ?? '');
    $time = sanitize_input($_POST['time'] ?? '');
    $landmark = sanitize_input($_POST['landmark'] ?? '');
    $trek_date_id = !empty($_POST['trek_date_id']) ? (int)$_POST['trek_date_id'] : null;

    if (empty($location) || empty($time)) {
        $error = "Location and Time are required fields.";
    } else {
        try {
            if ($action_mode === 'update') {
                if ($pickup_id <= 0) {
                    $error = "Invalid pickup point ID for update.";
                } else {
                    $stmt = $db->prepare("UPDATE pickup_points SET trek_date_id = ?, time = ?, location = ?, landmark = ? WHERE id = ? AND trek_id = ?");
                    $stmt->execute([
                        $trek_date_id,
                        $time,
                        $location,
                        $landmark,
                        $pickup_id,
                        $trek_id
                    ]);
                    log_activity('admin_update_pickup_point', ['trek_id' => $trek_id, 'pickup_id' => $pickup_id, 'location' => $location]);
                    $success = "Pickup Point Updated Successfully";
                }
            } else {
                $stmt = $db->prepare("INSERT INTO pickup_points (trek_id, trek_date_id, time, location, landmark) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $trek_id,
                    $trek_date_id,
                    $time,
                    $location,
                    $landmark
                ]);
                log_activity('admin_add_pickup_point', ['trek_id' => $trek_id, 'location' => $location]);
                $success = "Pickup point added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Failed to save pickup point: " . $e->getMessage();
        }
    }
}

// 2. Process Pickup Point Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['pickup_id'])) {
    $pickup_id = (int)$_GET['pickup_id'];
    try {
        $del_stmt = $db->prepare("DELETE FROM pickup_points WHERE id = ? AND trek_id = ?");
        $del_stmt->execute([$pickup_id, $trek_id]);
        log_activity('admin_delete_pickup_point', ['trek_id' => $trek_id, 'pickup_id' => $pickup_id]);
        set_flash_message('success', 'Pickup point deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Cannot delete this pickup point as bookings are linked to it.');
    }
    header('Location: ' . SITE_URL . '/admin/treks/pickups.php?trek_id=' . $trek_id);
    exit();
}

// 3. Fetch scheduled dates & pickup points
try {
    $dates_stmt = $db->prepare("SELECT id, start_date, end_date FROM trek_dates WHERE trek_id = ? ORDER BY start_date ASC");
    $dates_stmt->execute([$trek_id]);
    $trek_dates = $dates_stmt->fetchAll();
    
    $pickups_stmt = $db->prepare("SELECT pp.*, td.start_date, td.end_date FROM pickup_points pp
                                  LEFT JOIN trek_dates td ON pp.trek_date_id = td.id
                                  WHERE pp.trek_id = ? 
                                  ORDER BY pp.id ASC");
    $pickups_stmt->execute([$trek_id]);
    $pickups = $pickups_stmt->fetchAll();
} catch (PDOException $e) {
    die("System fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pickup Points | Admin Portal</title>
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
                    <span class="text-muted small">Configure Boarding Locations</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Manage Pickup Points: <?php echo htmlspecialchars($trek['title']); ?></h3>
                    <p class="text-muted mb-0">Setup boarding nodes, timings, landmarks, and link them to trek date batches</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/treks/manage.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Catalog</a>
            </div>

            <?php echo get_flash_message(); ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="row g-4">
                <!-- Left: Pickup Points List -->
                <div class="col-lg-8">
                    <div class="admin-card">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2"></i>Boarding Roster Locations</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-muted">
                                <thead>
                                    <tr>
                                        <th>Location Spot</th>
                                        <th>Landmark Node</th>
                                        <th>Boarding Time</th>
                                        <th>Assigned Date Batch</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pickups)): ?>
                                        <?php foreach ($pickups as $p): ?>
                                            <tr>
                                                <td><strong class="text-dark fs-6"><?php echo htmlspecialchars($p['location']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($p['landmark'] ?: 'None'); ?></td>
                                                <td><strong class="text-success"><i class="far fa-clock me-1"></i><?php echo date('h:i A', strtotime($p['time'])); ?></strong></td>
                                                <td>
                                                    <?php echo $p['start_date'] ? format_date($p['start_date']) . ' to ' . format_date($p['end_date']) : '<span class="badge bg-secondary">All Batches</span>'; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-sm btn-outline-primary py-1 me-1 btn-edit-pickup" 
                                                       data-id="<?php echo $p['id']; ?>"
                                                       data-location="<?php echo htmlspecialchars($p['location']); ?>"
                                                       data-landmark="<?php echo htmlspecialchars($p['landmark'] ?? ''); ?>"
                                                       data-time="<?php echo htmlspecialchars($p['time']); ?>"
                                                       data-date-id="<?php echo htmlspecialchars($p['trek_date_id'] ?? ''); ?>"
                                                    >
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/admin/treks/pickups.php?action=delete&trek_id=<?php echo $trek_id; ?>&pickup_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Are you sure you want to delete this pickup point?')">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No pickup points added yet. Use the form on the right to configure one.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right: Configure Pickup Form -->
                <div class="col-lg-4">
                    <div class="admin-card">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2" id="form_title"><i class="fas fa-plus-circle me-2"></i>Configure Pickup Spot</h5>
                        
                        <form action="" method="POST" id="pickup_form">
                            <input type="hidden" name="action_mode" id="pickup_action_mode" value="add">
                            <input type="hidden" name="pickup_id" id="pickup_id" value="0">
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Location Spot Name *</label>
                                <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Majestic Metro Station" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Landmark Info</label>
                                <input type="text" name="landmark" id="landmark" class="form-control" placeholder="e.g. Next to Platform 2 Exit">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Boarding Time *</label>
                                <input type="time" name="time" id="time" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold">Assign to Date Batch</label>
                                <select name="trek_date_id" id="trek_date_id" class="form-select">
                                    <option value="">-- Apply to All Batches --</option>
                                    <?php foreach ($trek_dates as $date): ?>
                                        <option value="<?php echo $date['id']; ?>">
                                            <?php echo format_date($date['start_date']) . ' to ' . format_date($date['end_date']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted mt-1 d-block">If unassigned, this pickup point will be selectable on all trek schedules.</small>
                            </div>

                            <button type="submit" id="submit_btn" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                                <i class="fas fa-plus-circle me-1"></i> Add Pickup Point
                            </button>
                            <button type="button" id="cancel_btn" class="btn btn-outline-secondary w-100 mt-2 py-2 fw-bold shadow-sm" style="display:none;">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
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
    // Edit action button
    $('.btn-edit-pickup').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var location = $(this).data('location');
        var landmark = $(this).data('landmark');
        var time = $(this).data('time');
        var dateId = $(this).data('date-id');
        
        $('#pickup_id').val(id);
        $('#location').val(location);
        $('#landmark').val(landmark);
        $('#time').val(time);
        $('#trek_date_id').val(dateId ? dateId : '');
        
        $('#pickup_action_mode').val('update');
        $('#submit_btn').html('<i class="fas fa-save me-1"></i> Update Pickup Point');
        $('#cancel_btn').show();
        $('#form_title').html('<i class="fas fa-edit me-2"></i>Edit Pickup Spot');
        
        // Scroll to the form
        $('html, body').animate({
            scrollTop: $("#form_title").offset().top - 100
        }, 300);
    });

    // Cancel action button
    $('#cancel_btn').on('click', function(e) {
        e.preventDefault();
        
        $('#pickup_id').val('0');
        $('#location').val('');
        $('#landmark').val('');
        $('#time').val('');
        $('#trek_date_id').val('');
        
        $('#pickup_action_mode').val('add');
        $('#submit_btn').html('<i class="fas fa-plus-circle me-1"></i> Add Pickup Point');
        $('#cancel_btn').hide();
        $('#form_title').html('<i class="fas fa-plus-circle me-2"></i>Configure Pickup Spot');
    });
});
</script>

</body>
</html>
