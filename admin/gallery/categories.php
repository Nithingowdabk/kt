<?php
/**
 * Admin - Manage Gallery Categories
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();

$db = Database::connect();

// Auto-ensure table structure for gallery_categories
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `gallery_categories` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `category_name` VARCHAR(150) NOT NULL,
      `slug` VARCHAR(150) NOT NULL UNIQUE,
      `description` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Ignore schema errors
}

// Edit Mode
$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_cat = null;
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM gallery_categories WHERE id = ? LIMIT 1");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

// Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = (int)($_GET['id'] ?? 0);
    if ($del_id > 0) {
        try {
            // Unlink photos from this category before deleting
            $db->prepare("UPDATE trek_gallery SET category_id = NULL WHERE category_id = ?")->execute([$del_id]);
            $stmt = $db->prepare("DELETE FROM gallery_categories WHERE id = ?");
            $stmt->execute([$del_id]);
            set_flash_message('success', 'Gallery category deleted successfully.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to delete category: ' . $e->getMessage());
        }
    }
    header('Location: ' . SITE_URL . '/admin/gallery/categories.php');
    exit();
}

// Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = sanitize_input($_POST['category_name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $cat_id = (int)($_POST['cat_id'] ?? 0);

    if (empty($category_name)) {
        set_flash_message('danger', 'Category name is required.');
    } else {
        $slug = create_slug($category_name);

        if ($cat_id > 0) {
            // Update
            try {
                $stmt = $db->prepare("UPDATE gallery_categories SET category_name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$category_name, $slug, $description, $cat_id]);
                set_flash_message('success', 'Category updated successfully.');
                header('Location: ' . SITE_URL . '/admin/gallery/categories.php');
                exit();
            } catch (PDOException $e) {
                set_flash_message('danger', 'Error updating category: ' . $e->getMessage());
            }
        } else {
            // Insert
            try {
                $stmt = $db->prepare("INSERT INTO gallery_categories (category_name, slug, description) VALUES (?, ?, ?)");
                $stmt->execute([$category_name, $slug, $description]);
                set_flash_message('success', 'New gallery category added successfully.');
                header('Location: ' . SITE_URL . '/admin/gallery/categories.php');
                exit();
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    set_flash_message('danger', 'A category with this name already exists.');
                } else {
                    set_flash_message('danger', 'Error adding category: ' . $e->getMessage());
                }
            }
        }
    }
}

// Fetch all gallery categories with photo count
try {
    $categories = $db->query("
        SELECT gc.*, COUNT(tg.id) as photo_count 
        FROM gallery_categories gc 
        LEFT JOIN trek_gallery tg ON gc.id = tg.category_id 
        GROUP BY gc.id 
        ORDER BY gc.category_name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Categories | Admin Portal</title>
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
                    <a href="<?php echo SITE_URL; ?>/admin/gallery/manage.php" class="btn btn-outline-success btn-sm me-2">
                        <i class="fas fa-images me-1"></i> Manage Photos
                    </a>
                    <span class="text-muted small">Gallery Category Settings</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Gallery Categories</h3>
                    <p class="text-muted small mb-0">Manage photo categories for the public adventure gallery</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/gallery/manage.php" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Photos
                </a>
            </div>

            <?php echo get_flash_message(); ?>

            <div class="row g-4">
                <!-- Add / Edit Form Column -->
                <div class="col-lg-4">
                    <div class="admin-card">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-folder-plus text-success me-2"></i>
                            <?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?>
                        </h5>
                        <form action="" method="POST">
                            <input type="hidden" name="cat_id" value="<?php echo $edit_cat['id'] ?? 0; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" class="form-control" placeholder="e.g. Western Ghats, Sunrise Treks" value="<?php echo htmlspecialchars($edit_cat['category_name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Short description for this gallery category"><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success w-100 fw-bold">
                                    <i class="fas fa-save me-1"></i> <?php echo $edit_cat ? 'Update Category' : 'Create Category'; ?>
                                </button>
                                <?php if ($edit_cat): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin/gallery/categories.php" class="btn btn-outline-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List Categories Column -->
                <div class="col-lg-8">
                    <div class="admin-card">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle text-muted">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Slug</th>
                                        <th>Photos</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td>#<?php echo $cat['id']; ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                                                    <?php if ($cat['description']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($cat['description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                                                        <i class="fas fa-image me-1"></i><?php echo $cat['photo_count']; ?> Photos
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="?edit_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category? Photos will remain but won\'t be assigned to this category.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                                                No custom gallery categories found. Create one using the form on the left!
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
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
