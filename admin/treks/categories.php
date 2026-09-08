<?php
/**
 * Admin - Manage Trek Categories
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();

$db = Database::connect();
$error = '';

// Edit Mode
$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_cat = null;
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM trek_categories WHERE id = ? LIMIT 1");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

// Delete Mode
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = (int)($_GET['id'] ?? 0);
    if ($del_id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM trek_categories WHERE id = ?");
            $stmt->execute([$del_id]);
            set_flash_message('success', 'Category deleted successfully.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to delete category: ' . $e->getMessage());
        }
    }
    header('Location: ' . SITE_URL . '/admin/treks/categories.php');
    exit();
}

// Toggle Status Mode
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $toggle_id = (int)($_GET['id'] ?? 0);
    if ($toggle_id > 0) {
        try {
            $stmt = $db->prepare("SELECT status FROM trek_categories WHERE id = ?");
            $stmt->execute([$toggle_id]);
            $current_status = $stmt->fetchColumn();
            $new_status = $current_status === 'Active' ? 'Inactive' : 'Active';
            
            $db->prepare("UPDATE trek_categories SET status = ? WHERE id = ?")->execute([$new_status, $toggle_id]);
            set_flash_message('success', 'Category status updated successfully.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to update status: ' . $e->getMessage());
        }
    }
    header('Location: ' . SITE_URL . '/admin/treks/categories.php');
    exit();
}

// Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = sanitize_input($_POST['category_name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '');
    $icon = sanitize_input($_POST['icon'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Active');
    
    if (empty($category_name)) {
        $error = "Category name is required.";
    } else {
        if (empty($slug)) {
            $slug = create_slug($category_name);
        } else {
            $slug = create_slug($slug);
        }
        
        try {
            // Check slug uniqueness
            if ($edit_id > 0) {
                $check = $db->prepare("SELECT COUNT(*) FROM trek_categories WHERE slug = ? AND id != ?");
                $check->execute([$slug, $edit_id]);
            } else {
                $check = $db->prepare("SELECT COUNT(*) FROM trek_categories WHERE slug = ?");
                $check->execute([$slug]);
            }
            
            if ($check->fetchColumn() > 0) {
                $slug .= '-' . rand(10, 99);
            }
            
            if ($edit_id > 0) {
                $stmt = $db->prepare("UPDATE trek_categories SET category_name = ?, slug = ?, icon = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$category_name, $slug, $icon, $description, $status, $edit_id]);
                set_flash_message('success', 'Category updated successfully.');
            } else {
                $stmt = $db->prepare("INSERT INTO trek_categories (category_name, slug, icon, description, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$category_name, $slug, $icon, $description, $status]);
                set_flash_message('success', 'Category added successfully.');
            }
            header('Location: ' . SITE_URL . '/admin/treks/categories.php');
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all categories from DB (default sorted)
try {
    $all_cats = $db->query("SELECT * FROM trek_categories")->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}

// Retrieve the current order from the settings table
$order_setting = '';
try {
    $order_stmt = $db->prepare("SELECT value_data FROM settings WHERE key_name = ? LIMIT 1");
    $order_stmt->execute(['category_order']);
    $order_setting = $order_stmt->fetchColumn();
} catch (PDOException $e) {
    // If settings table query fails, skip custom order sorting
}

$ordered_ids = [];
if (!empty($order_setting)) {
    $ordered_ids = json_decode($order_setting, true);
    if (!is_array($ordered_ids)) {
        $ordered_ids = [];
    }
}

// Ensure all categories currently in DB are represented in $ordered_ids
$db_cat_ids = array_column($all_cats, 'id');
$existing_ordered_ids = array_intersect($ordered_ids, $db_cat_ids);
$new_ids = array_diff($db_cat_ids, $existing_ordered_ids);
$final_order_ids = array_values(array_merge($existing_ordered_ids, $new_ids));

// Save final order back if it changed to keep settings table synchronized
if (count($final_order_ids) !== count($ordered_ids)) {
    try {
        $json_order = json_encode($final_order_ids);
        $save_stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES ('category_order', ?) 
                                   ON DUPLICATE KEY UPDATE value_data = ?");
        $save_stmt->execute([$json_order, $json_order]);
    } catch (PDOException $e) {
        // Ignore/log
    }
}

// Move Up / Move Down Handler
if (isset($_GET['action']) && ($_GET['action'] === 'move_up' || $_GET['action'] === 'move_down')) {
    $move_id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'];
    
    if ($move_id > 0) {
        $idx = array_search($move_id, $final_order_ids);
        if ($idx !== false) {
            if ($action === 'move_up' && $idx > 0) {
                // Swap with the previous element
                $temp = $final_order_ids[$idx];
                $final_order_ids[$idx] = $final_order_ids[$idx - 1];
                $final_order_ids[$idx - 1] = $temp;
            } elseif ($action === 'move_down' && $idx < count($final_order_ids) - 1) {
                // Swap with the next element
                $temp = $final_order_ids[$idx];
                $final_order_ids[$idx] = $final_order_ids[$idx + 1];
                $final_order_ids[$idx + 1] = $temp;
            }
            
            // Save updated order to settings
            try {
                $json_order = json_encode($final_order_ids);
                $save_stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES ('category_order', ?) 
                                           ON DUPLICATE KEY UPDATE value_data = ?");
                $save_stmt->execute([$json_order, $json_order]);
                set_flash_message('success', 'Category order updated successfully.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Failed to update category order: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . SITE_URL . '/admin/treks/categories.php');
    exit();
}

// Sort $all_cats based on $final_order_ids
$category_map = [];
foreach ($all_cats as $cat) {
    $category_map[$cat['id']] = $cat;
}

$categories = [];
foreach ($final_order_ids as $id) {
    if (isset($category_map[$id])) {
        $categories[] = $category_map[$id];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trek Categories | Admin Portal</title>
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
                <h3 class="fw-bold mb-0">Trek Categories</h3>
            </div>

            <?php echo get_flash_message(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Categories Table -->
                <div class="col-lg-8">
                    <div class="admin-card">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2">Category List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-muted">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Icon</th>
                                        <th>Category Name</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categories)): ?>
                                        <?php 
                                        $serial = 1;
                                        foreach ($categories as $cat): 
                                            $status_badge = $cat['status'] === 'Active' ? 'bg-success' : 'bg-secondary';
                                        ?>
                                            <tr>
                                                <td><?php echo $serial++; ?></td>
                                                <td>
                                                    <span class="fs-4 text-success">
                                                        <i class="<?php echo htmlspecialchars($cat['icon'] ?: 'fas fa-tags'); ?>"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></small>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                                <td>
                                                    <a href="?action=toggle&id=<?php echo $cat['id']; ?>" class="badge <?php echo $status_badge; ?> text-decoration-none">
                                                        <?php echo $cat['status']; ?>
                                                    </a>
                                                </td>
                                                <td class="text-end">
                                                    <a href="?action=move_up&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" title="Move Up">
                                                        ↑ Move Up
                                                    </a>
                                                    <a href="?action=move_down&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" title="Move Down">
                                                        ↓ Move Down
                                                    </a>
                                                    <a href="?edit_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2 me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Are you sure you want to delete this category? Any associated treks will have their category cleared.')">
                                                        <i class="fas fa-trash-alt"></i> Del
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No categories created yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div class="col-lg-4">
                    <div class="admin-card">
                        <h5 class="fw-bold text-success mb-3 border-bottom pb-2">
                            <?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?>
                        </h5>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="category_name" class="form-control" required value="<?php echo htmlspecialchars($edit_cat['category_name'] ?? ''); ?>" placeholder="e.g. Weekend Trek">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug (optional)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_cat['slug'] ?? ''); ?>" placeholder="e.g. weekend-trek">
                                <small class="text-muted d-block mt-1">Leave empty to auto-generate slug.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">FontAwesome Icon Class</label>
                                <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($edit_cat['icon'] ?? ''); ?>" placeholder="e.g. fas fa-tree">
                                <small class="text-muted d-block mt-1">Use FontAwesome v6 classes like <code>fas fa-mountain</code> or <code>fas fa-campground</code>.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Brief description of treks in this category..."><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?php echo ($edit_cat['status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($edit_cat['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success w-100 fw-bold">
                                    <?php echo $edit_cat ? 'Update Category' : 'Save Category'; ?>
                                </button>
                                <?php if ($edit_cat): ?>
                                    <a href="categories.php" class="btn btn-outline-secondary w-100 fw-bold">Cancel</a>
                                <?php endif; ?>
                            </div>
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

</body>
</html>
