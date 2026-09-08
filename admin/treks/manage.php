<?php
/**
 * Admin - Manage Treks Catalog
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

try {
    if ($search !== '') {
        $stmt = $db->prepare("SELECT t.*, c.category_name as category_name FROM treks t 
                            LEFT JOIN trek_categories c ON t.category_id = c.id 
                            WHERE t.title LIKE ? OR t.slug LIKE ? OR c.category_name LIKE ?
                            ORDER BY t.id DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $db->query("SELECT t.*, c.category_name as category_name FROM treks t 
                            LEFT JOIN trek_categories c ON t.category_id = c.id 
                            ORDER BY t.id DESC");
    }
    $treks = $stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Treks | Admin Portal</title>
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
        <!-- Top navbar -->
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto">
                    <span class="text-muted small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Manage Treks</h3>
                <a href="<?php echo SITE_URL; ?>/admin/treks/add.php" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-plus me-1"></i> Add New Trek
                </a>
            </div>

            <?php echo get_flash_message(); ?>

            <!-- Search Form -->
            <div class="row mb-3">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by title, slug, category..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                            <?php if ($search !== ''): ?>
                                <a href="manage.php" class="btn btn-outline-secondary d-flex align-items-center">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle text-muted">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Difficulty</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($treks)): ?>
                                <?php foreach ($treks as $trek): 
                                    $status_badge = $trek['status'] === 'Active' ? 'bg-success' : 'bg-secondary';
                                    $difficulty_badge = $trek['difficulty'] === 'Easy' ? 'bg-info text-dark' : ($trek['difficulty'] === 'Moderate' ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td><?php echo $trek['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($trek['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($trek['slug']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($trek['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($trek['duration']); ?></td>
                                        <td>
                                            <?php if ($trek['offer_price'] > 0): ?>
                                                <del class="text-danger me-1 small"><?php echo format_price($trek['price']); ?></del>
                                                <span class="text-success"><?php echo format_price($trek['offer_price']); ?></span>
                                            <?php else: ?>
                                                <span><?php echo format_price($trek['price']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?php echo $difficulty_badge; ?>"><?php echo $trek['difficulty']; ?></span></td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo $trek['status']; ?></span></td>
                                        <td class="text-end">
                                            <a href="<?php echo SITE_URL; ?>/admin/treks/schedules.php?trek_id=<?php echo $trek['id']; ?>" class="btn btn-sm btn-outline-success py-1 px-2 me-1">
                                                <i class="far fa-calendar-alt"></i> Trip Schedules
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/treks/pickups.php?trek_id=<?php echo $trek['id']; ?>" class="btn btn-sm btn-outline-warning py-1 px-2 me-1 text-dark">
                                                <i class="fas fa-map-marker-alt"></i> Pickups
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/treks/edit.php?id=<?php echo $trek['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2 me-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/treks/delete.php?id=<?php echo $trek['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Are you sure you want to delete this trek? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt"></i> Del
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <?php if ($search !== ''): ?>
                                            No treks found matching "<strong><?php echo htmlspecialchars($search); ?></strong>". <a href="manage.php">Clear search</a> to see all.
                                        <?php else: ?>
                                            No treks added yet. Click "Add New Trek" to create one.
                                        <?php endif; ?>
                                    </td>
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
