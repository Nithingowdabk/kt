<?php
/**
 * Admin - Manage Reviews & Approvals
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

// Handle Approval / Rejection / Delete Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $rev_id = (int)$_GET['id'];
    $act = sanitize_input($_GET['action']);

    if ($act === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$rev_id]);
            log_activity('review_deleted', ['review_id' => $rev_id]);
            set_flash_message('success', "Review / Testimonial deleted successfully.");
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to delete review.');
        }
        header('Location: ' . SITE_URL . '/admin/reviews/manage.php');
        exit();
    } else {
        $status_val = $act === 'approve' ? 'Approved' : 'Rejected';
        try {
            $stmt = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
            $stmt->execute([$status_val, $rev_id]);
            log_activity('review_moderated', ['review_id' => $rev_id, 'status' => $status_val]);
            set_flash_message('success', "Review status updated to $status_val.");
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to update review status.');
        }
        header('Location: ' . SITE_URL . '/admin/reviews/manage.php');
        exit();
    }
}

try {
    // Fetch all reviews
    $stmt = $db->query("SELECT r.*, t.title as trek_title FROM reviews r 
                        INNER JOIN treks t ON r.trek_id = t.id 
                        ORDER BY r.id DESC");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews | Admin Portal</title>
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
                    <span class="text-muted small">Moderate customer feedback submissions</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Manage Reviews & Ratings</h3>
                <a href="<?php echo SITE_URL; ?>/admin/reviews/add.php" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-plus me-1"></i> Create Testimonial
                </a>
            </div>

            <?php echo get_flash_message(); ?>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>Author Name</th>
                                <th>Trek Destination</th>
                                <th>Rating</th>
                                <th>Comment Content</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $rev): 
                                    $status_badge = $rev['status'] === 'Approved' ? 'bg-success' : ($rev['status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($rev['name']); ?></div>
                                            <small class="text-muted"><?php echo format_date($rev['created_at']); ?></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($rev['trek_title']); ?></strong></td>
                                        <td>
                                            <?php echo render_rating_stars($rev['rating']); ?>
                                        </td>
                                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($rev['comment']); ?>
                                        </td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo $rev['status']; ?></span></td>
                                        <td class="text-end" style="white-space: nowrap;">
                                            <?php if ($rev['status'] !== 'Approved'): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/reviews/manage.php?action=approve&id=<?php echo $rev['id']; ?>" class="btn btn-sm btn-outline-success py-1 px-2 me-1" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($rev['status'] !== 'Rejected'): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/reviews/manage.php?action=reject&id=<?php echo $rev['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2 me-1" title="Reject">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/reviews/add.php?id=<?php echo $rev['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2 me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/reviews/manage.php?action=delete&id=<?php echo $rev['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete" onclick="return confirm('Are you sure you want to delete this review/testimonial?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No reviews recorded yet.</td>
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
