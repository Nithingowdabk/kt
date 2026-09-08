<?php
/**
 * Trek Leader Portal - Participant List
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

    // 2. Fetch confirmed participants
    $bookings_stmt = $db->prepare("SELECT b.*, p.location as pickup_spot, p.time as pickup_time FROM bookings b
                                   LEFT JOIN pickup_points p ON b.pickup_point_id = p.id
                                   WHERE b.trek_date_id = ? AND b.booking_status = 'Confirmed'
                                   ORDER BY b.id ASC");
    $bookings_stmt->execute([$date_id]);
    $bookings = $bookings_stmt->fetchAll();

    // 3. Unpack all participants (lead trekkers + co-trekkers)
    $participant_list = [];
    foreach ($bookings as $b) {
        // Add lead trekker
        $participant_list[] = [
            'booking_no' => $b['booking_no'],
            'name' => $b['name'],
            'role' => 'Lead Trekker',
            'details' => 'Phone: ' . $b['phone'] . ' | Email: ' . $b['email'],
            'gender' => 'N/A', // Lead info stored on user profile or filled in checkout
            'age' => 'N/A',
            'pickup' => ($b['pickup_spot'] ?? 'N/A') . ' (' . ($b['pickup_time'] ? date('h:i A', strtotime($b['pickup_time'])) : 'N/A') . ')'
        ];

        // Parse co-trekkers JSON details
        $co_trekkers = json_decode($b['details'], true);
        if (is_array($co_trekkers)) {
            foreach ($co_trekkers as $co) {
                $participant_list[] = [
                    'booking_no' => $b['booking_no'],
                    'name' => $co['name'] ?? '',
                    'role' => 'Co-Trekker',
                    'details' => 'Lead: ' . $b['name'] . ' (' . $b['phone'] . ')',
                    'gender' => $co['gender'] ?? 'N/A',
                    'age' => $co['age'] ?? 'N/A',
                    'pickup' => ($b['pickup_spot'] ?? 'N/A')
                ];
            }
        }
    }

} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant List | Trek Leader Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="container p-4" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h2 class="fw-bold mb-0">Roster: <?php echo htmlspecialchars($batch['trek_title']); ?></h2>
            <p class="text-muted mb-0">Trip Schedule Date: <strong><?php echo format_date($batch['start_date']) . ' to ' . format_date($batch['end_date']); ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <!-- Participants Table -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-success mb-0"><i class="fas fa-users me-2"></i>Trekkers Roster (<?php echo count($participant_list); ?> Confirmed)</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-muted">
                <thead>
                    <tr class="table-light">
                        <th>Trekker Name</th>
                        <th>Type / Role</th>
                        <th>Age / Gender</th>
                        <th>Contact / Lead Details</th>
                        <th>Pickup Point</th>
                        <th>Booking Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($participant_list)): ?>
                        <?php foreach ($participant_list as $p): ?>
                            <tr>
                                <td><strong class="text-dark fs-6"><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $p['role'] === 'Lead Trekker' ? 'bg-success' : 'bg-info text-dark'; ?>">
                                        <?php echo $p['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($p['age']); ?> yrs / <?php echo htmlspecialchars($p['gender']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['details']); ?></td>
                                <td><small class="text-dark fw-bold"><?php echo htmlspecialchars($p['pickup']); ?></small></td>
                                <td><strong><?php echo htmlspecialchars($p['booking_no']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No participants have booked/confirmed for this trip schedule yet.</td>
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
