<?php
/**
 * Admin - Manage Customers Catalog
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

// Update customer status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_status'])) {
    $usr_id = (int)$_POST['user_id'];
    $usr_status = sanitize_input($_POST['status'] ?? 'Active');
    
    try {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$usr_status, $usr_id]);
        set_flash_message('success', 'Customer status updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to update user status.');
    }
    
    header('Location: ' . SITE_URL . '/admin/users/manage.php');
    exit();
}

try {
    // Fetch users
    $stmt = $db->query("SELECT u.*, COUNT(b.id) as total_bookings FROM users u 
                        LEFT JOIN bookings b ON u.id = b.user_id 
                        GROUP BY u.id ORDER BY u.id DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers | Admin Portal</title>
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
                    <span class="text-muted small">Registered customers profile center</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Manage Customers</h3>
            </div>

            <?php echo get_flash_message(); ?>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Bookings Qty</th>
                                <th>Registered On</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $usr): 
                                    $status_badge = $usr['status'] === 'Active' ? 'bg-success' : 'bg-secondary';
                                ?>
                                    <tr>
                                        <td><?php echo $usr['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($usr['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                        <td><?php echo htmlspecialchars($usr['phone']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo $usr['total_bookings']; ?> Bookings</span></td>
                                        <td><?php echo format_date($usr['created_at']); ?></td>
                                        
                                        <!-- Inline update form -->
                                        <form action="" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                            <td>
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="Active" <?php echo $usr['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo $usr['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                                <input type="hidden" name="update_user_status" value="1">
                                            </td>
                                        </form>
                                        
                                        <td class="text-end">
                                            <a href="<?php echo SITE_URL; ?>/admin/users/view.php?id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-outline-success py-1 px-2">
                                                <i class="far fa-eye"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No customers registered yet.</td>
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
