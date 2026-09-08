<?php
/**
 * Trek Leader Portal - Trek Completion Report
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

$error_msg = '';
$success_msg = '';

try {
    // 1. Verify trip schedule ownership and check if report already exists
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

    $report_stmt = $db->prepare("SELECT * FROM trek_completion_reports WHERE trek_date_id = ? LIMIT 1");
    $report_stmt->execute([$date_id]);
    $report = $report_stmt->fetch();

    if ($report) {
        set_flash_message('info', 'Completion report has already been submitted for this trip schedule.');
        header('Location: dashboard.php');
        exit();
    }

    // 2. Process Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $weather = sanitize_input($_POST['weather_conditions'] ?? '');
        $challenges = sanitize_input($_POST['challenges_faced'] ?? '');
        $summary = sanitize_input($_POST['summary'] ?? '');

        if (empty($summary)) {
            $error_msg = "Please write a summary of the trek trip.";
        } else {
            $insert_stmt = $db->prepare("INSERT INTO trek_completion_reports (trek_date_id, trek_leader_id, summary, weather_conditions, challenges_faced) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->execute([
                $date_id,
                $leader_id,
                $summary,
                $weather,
                $challenges
            ]);
            
            log_activity('leader_submit_report', ['trek_date_id' => $date_id]);
            set_flash_message('success', 'Completion report submitted successfully!');
            header('Location: ' . SITE_URL . '/admin/trek-leaders/dashboard.php');
            exit();
        }
    }

} catch (PDOException $e) {
    $error_msg = "Database operation failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Completion Report | Trek Leader Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="container p-4" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h2 class="fw-bold mb-0">Trek Completion Report</h2>
            <p class="text-muted mb-0">Trip Schedule: <strong><?php echo htmlspecialchars($batch['trek_title']); ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Report Submission Form -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
        <form action="" method="POST">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-muted">Weather Conditions</label>
                    <input type="text" name="weather_conditions" class="form-control" placeholder="e.g. Sunny and clear / Heavy rains during ascent / Foggy peak" required>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-muted">Challenges Faced (optional)</label>
                    <textarea name="challenges_faced" class="form-control" rows="3" placeholder="Describe any trail blockages, injuries, delays or administrative difficulties faced during the trek..."></textarea>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold small text-muted">Overall Trek Summary *</label>
                    <textarea name="summary" class="form-control" rows="6" placeholder="Provide a detailed feedback summary of the homestay, guide coordination, trail conditions, and overall trip satisfaction..." required></textarea>
                </div>
                
                <div class="col-md-12 mt-4 text-end">
                    <button type="submit" class="btn btn-success px-5 py-2.5 fw-bold rounded-3">
                        <i class="fas fa-paper-plane me-1"></i> Submit Completion Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
