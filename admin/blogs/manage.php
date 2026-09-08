<?php
/**
 * Admin - Manage Blogs
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->execute([$del_id]);
        set_flash_message('success', 'Blog article deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting blog article.');
    }
    header('Location: ' . SITE_URL . '/admin/blogs/manage.php');
    exit();
}

try {
    // Fetch all blogs
    $stmt = $db->query("SELECT * FROM blogs ORDER BY id DESC");
    $blogs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blogs | Admin Portal</title>
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
                    <span class="text-muted small">Publish articles and guides</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Manage Blog Articles</h3>
                <a href="<?php echo SITE_URL; ?>/admin/blogs/add.php" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-plus me-1"></i> Add New Article
                </a>
            </div>

            <?php echo get_flash_message(); ?>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Created On</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($blogs)): ?>
                                <?php foreach ($blogs as $blog): 
                                    $status_badge = $blog['status'] === 'Active' ? 'bg-success' : 'bg-secondary';
                                ?>
                                    <tr>
                                        <td><?php echo $blog['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($blog['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($blog['slug']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($blog['author']); ?></td>
                                        <td><?php echo format_date($blog['created_at']); ?></td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo $blog['status']; ?></span></td>
                                        <td class="text-end">
                                            <a href="<?php echo SITE_URL; ?>/admin/blogs/add.php?id=<?php echo $blog['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2 me-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/blogs/manage.php?action=delete&id=<?php echo $blog['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Are you sure you want to delete this article?')">
                                                <i class="fas fa-trash-alt"></i> Del
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No blog articles published yet. Click "Add New Article" to start.</td>
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
