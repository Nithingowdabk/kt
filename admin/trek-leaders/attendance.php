<?php
/**
 * Trek Leader Portal - Attendance Tracker
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

$success_msg = '';
$error_msg = '';

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

    // 2. Fetch confirmed bookings
    $bookings_stmt = $db->prepare("SELECT b.* FROM bookings b WHERE b.trek_date_id = ? AND b.booking_status = 'Confirmed'");
    $bookings_stmt->execute([$date_id]);
    $bookings = $bookings_stmt->fetchAll();

    // 3. Unpack all participants into flat array
    $participant_list = [];
    foreach ($bookings as $b) {
        // Add lead trekker
        $participant_list[] = [
            'booking_id' => $b['id'],
            'name' => $b['name'],
            'role' => 'Lead'
        ];

        // Add co-trekkers
        $co_trekkers = json_decode($b['details'], true);
        if (is_array($co_trekkers)) {
            foreach ($co_trekkers as $co) {
                $participant_list[] = [
                    'booking_id' => $b['id'],
                    'name' => $co['name'] ?? '',
                    'role' => 'Co-Trekker'
                ];
            }
        }
    }

    // 4. Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $attendance_status = $_POST['status'] ?? []; // format: [booking_id_name => 'Present'|'Absent']
        
        $db->beginTransaction();

        // Clear existing attendance log for this date
        $clear_stmt = $db->prepare("DELETE FROM attendance WHERE trek_date_id = ?");
        $clear_stmt->execute([$date_id]);

        $insert_stmt = $db->prepare("INSERT INTO attendance (trek_date_id, booking_id, traveler_name, status) VALUES (?, ?, ?, ?)");

        foreach ($participant_list as $p) {
            $safe_key = $p['booking_id'] . '_' . str_replace(' ', '_', $p['name']);
            $status = $attendance_status[$safe_key] ?? 'Absent';
            
            $insert_stmt->execute([
                $date_id,
                $p['booking_id'],
                $p['name'],
                $status
            ]);
        }

        $db->commit();
        log_activity('leader_mark_attendance', ['trek_date_id' => $date_id]);
        $success_msg = "Attendance roster saved successfully!";
    }

    // 5. Fetch existing attendance states to prefill checkboxes
    $att_stmt = $db->prepare("SELECT traveler_name, booking_id, status FROM attendance WHERE trek_date_id = ?");
    $att_stmt->execute([$date_id]);
    $existing_attendance = $att_stmt->fetchAll();
    
    $attendance_cache = [];
    foreach ($existing_attendance as $att) {
        $cache_key = $att['booking_id'] . '_' . $att['traveler_name'];
        $attendance_cache[$cache_key] = $att['status'];
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $error_msg = "Database operation failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance | Trek Leader Portal</title>
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
            <h2 class="fw-bold mb-0">Attendance Check: <?php echo htmlspecialchars($batch['trek_title']); ?></h2>
            <p class="text-muted mb-0">Mark boarding/attendance for participants</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Attendance Form -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
        <form action="" method="POST">
            <div class="table-responsive">
                <table class="table align-middle text-muted">
                    <thead>
                        <tr class="table-light">
                            <th>Trekker Name</th>
                            <th>Role</th>
                            <th class="text-center" style="width: 150px;">Present</th>
                            <th class="text-center" style="width: 150px;">Absent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($participant_list)): ?>
                            <?php foreach ($participant_list as $p): 
                                $cache_key = $p['booking_id'] . '_' . $p['name'];
                                $current_status = $attendance_cache[$cache_key] ?? 'Present'; // default to present if unmarked
                                $input_name = "status[" . $p['booking_id'] . "_" . str_replace(' ', '_', $p['name']) . "]";
                            ?>
                                <tr>
                                    <td><strong class="text-dark fs-6"><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $p['role'] === 'Lead' ? 'bg-success' : 'bg-info text-dark'; ?>">
                                            <?php echo $p['role']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="<?php echo $input_name; ?>" value="Present" <?php echo $current_status === 'Present' ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="<?php echo $input_name; ?>" value="Absent" <?php echo $current_status === 'Absent' ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No confirmed participants for this trip schedule.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($participant_list)): ?>
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-success px-4 py-2.5 fw-bold rounded-3">
                        <i class="fas fa-save me-1"></i> Save Roster Attendance
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
