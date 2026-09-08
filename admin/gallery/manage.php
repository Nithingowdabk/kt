<?php
/**
 * Admin - Manage Media Gallery Photos
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();

$db = Database::connect();

// Auto-ensure DB structure
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `gallery_categories` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `category_name` VARCHAR(150) NOT NULL,
      `slug` VARCHAR(150) NOT NULL UNIQUE,
      `description` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ensure category_id exists in trek_gallery
    $cols = $db->query("SHOW COLUMNS FROM trek_gallery LIKE 'category_id'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE trek_gallery ADD COLUMN category_id INT DEFAULT NULL AFTER trek_id");
    }
} catch (PDOException $e) {
    // Ignore schema errors
}

// Handle Delete Photo
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT image_path, thumbnail_path FROM trek_gallery WHERE id = ?");
        $stmt->execute([$del_id]);
        $photo = $stmt->fetch();
        if ($photo) {
            // Unlink files if exists
            if (!empty($photo['image_path']) && file_exists(__DIR__ . '/../../' . $photo['image_path'])) {
                @unlink(__DIR__ . '/../../' . $photo['image_path']);
            }
            if (!empty($photo['thumbnail_path']) && file_exists(__DIR__ . '/../../' . $photo['thumbnail_path'])) {
                @unlink(__DIR__ . '/../../' . $photo['thumbnail_path']);
            }
            $db->prepare("DELETE FROM trek_gallery WHERE id = ?")->execute([$del_id]);
            set_flash_message('success', 'Photo deleted successfully.');
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting photo: ' . $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/admin/gallery/manage.php');
    exit();
}

// Handle Edit Photo Caption / Category / Trek
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_photo') {
    $photo_id = (int)($_POST['photo_id'] ?? 0);
    $caption = sanitize_input($_POST['caption'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $trek_id = !empty($_POST['trek_id']) ? (int)$_POST['trek_id'] : null;

    if ($photo_id > 0) {
        try {
            $stmt = $db->prepare("UPDATE trek_gallery SET image_title = ?, category_id = ?, trek_id = ? WHERE id = ?");
            $stmt->execute([$caption, $category_id, $trek_id, $photo_id]);
            set_flash_message('success', 'Photo details updated successfully.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Error updating photo: ' . $e->getMessage());
        }
    }
    header('Location: ' . SITE_URL . '/admin/gallery/manage.php');
    exit();
}

// Handle Single / Multiple Photo Upload Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photos') {
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $trek_id = !empty($_POST['trek_id']) ? (int)$_POST['trek_id'] : null;
    $custom_caption = sanitize_input($_POST['caption'] ?? '');

    // Check if new category was typed
    $new_cat_name = sanitize_input($_POST['new_category_name'] ?? '');
    if (!empty($new_cat_name)) {
        $new_slug = create_slug($new_cat_name);
        try {
            $stmt_cat = $db->prepare("INSERT INTO gallery_categories (category_name, slug) VALUES (?, ?)");
            $stmt_cat->execute([$new_cat_name, $new_slug]);
            $category_id = (int)$db->lastInsertId();
        } catch (PDOException $e) {
            // If already exists, fetch its ID
            $stmt_fetch = $db->prepare("SELECT id FROM gallery_categories WHERE slug = ? LIMIT 1");
            $stmt_fetch->execute([$new_slug]);
            $category_id = (int)$stmt_fetch->fetchColumn();
        }
    }

    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $total_files = count($_FILES['photos']['name']);
        $uploaded_count = 0;
        $errors = [];

        $upload_dir = __DIR__ . '/../../assets/uploads/gallery/';
        $thumb_dir = __DIR__ . '/../../assets/uploads/gallery/thumbs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0755, true);

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['photos']['name'][$i];
                $file_tmp = $_FILES['photos']['tmp_name'][$i];
                $file_size = $_FILES['photos']['size'][$i];

                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $errors[] = "File '$file_name' is not a valid image.";
                    continue;
                }

                if ($file_size > 5 * 1024 * 1024) {
                    $errors[] = "File '$file_name' exceeds 5MB size limit.";
                    continue;
                }

                $base_name = bin2hex(random_bytes(12));
                $has_gd = extension_loaded('gd');
                $dest_filename = $base_name . ($has_gd ? '.webp' : '.' . $ext);

                $full_path = $upload_dir . $dest_filename;
                $thumb_path = $thumb_dir . $dest_filename;

                $rel_full = 'assets/uploads/gallery/' . $dest_filename;
                $rel_thumb = 'assets/uploads/gallery/thumbs/' . $dest_filename;

                $processed = false;
                if ($has_gd) {
                    if (process_and_optimize_image($file_tmp, $full_path, 1600, 1200, 85)) {
                        process_and_optimize_image($full_path, $thumb_path, 400, 300, 75);
                        $processed = true;
                    }
                }

                if (!$processed) {
                    move_uploaded_file($file_tmp, $full_path);
                    copy($full_path, $thumb_path);
                }

                // Determine caption
                $image_title = !empty($custom_caption) ? $custom_caption : pathinfo($file_name, PATHINFO_FILENAME);

                // Get max sort_order
                $max_sort = (int)$db->query("SELECT MAX(sort_order) FROM trek_gallery")->fetchColumn();

                $stmt_ins = $db->prepare("INSERT INTO trek_gallery (trek_id, category_id, image_path, thumbnail_path, image_title, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_ins->execute([$trek_id, $category_id, $rel_full, $rel_thumb, $image_title, $max_sort + 1]);

                $uploaded_count++;
            }
        }

        if ($uploaded_count > 0) {
            set_flash_message('success', "Successfully uploaded $uploaded_count photo(s) to the gallery.");
        }
        if (!empty($errors)) {
            set_flash_message('warning', implode('<br>', $errors));
        }
    } else {
        set_flash_message('danger', 'Please select at least one photo to upload.');
    }

    header('Location: ' . SITE_URL . '/admin/gallery/manage.php');
    exit();
}

// Fetch Gallery Categories
try {
    $gallery_cats = $db->query("SELECT * FROM gallery_categories ORDER BY category_name ASC")->fetchAll();
} catch (PDOException $e) {
    $gallery_cats = [];
}

// Fetch Treks
try {
    $treks = $db->query("SELECT id, title FROM treks ORDER BY title ASC")->fetchAll();
} catch (PDOException $e) {
    $treks = [];
}

// Filter photos if category or trek selected
$filter_cat = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$filter_trek = isset($_GET['trek_id']) ? (int)$_GET['trek_id'] : 0;

$where_clauses = [];
$params = [];

if ($filter_cat > 0) {
    $where_clauses[] = "tg.category_id = ?";
    $params[] = $filter_cat;
}
if ($filter_trek > 0) {
    $where_clauses[] = "tg.trek_id = ?";
    $params[] = $filter_trek;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch Photos
try {
    $stmt_photos = $db->prepare("
        SELECT tg.*, gc.category_name, t.title as trek_title 
        FROM trek_gallery tg 
        LEFT JOIN gallery_categories gc ON tg.category_id = gc.id 
        LEFT JOIN treks t ON tg.trek_id = t.id 
        $where_sql 
        ORDER BY tg.created_at DESC
    ");
    $stmt_photos->execute($params);
    $photos = $stmt_photos->fetchAll();
} catch (PDOException $e) {
    $photos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Media Gallery | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    <style>
        .gallery-admin-card {
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--border-color, #e0e0e0);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .gallery-admin-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .gallery-admin-thumb {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f4f6f4;
        }
        .upload-dropzone {
            border: 2px dashed #2D5A27;
            border-radius: 12px;
            background: #f8fbf8;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .upload-dropzone:hover {
            background: #eef6ee;
        }
    </style>
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
                    <a href="<?php echo SITE_URL; ?>/admin/gallery/categories.php" class="btn btn-outline-success btn-sm me-2">
                        <i class="fas fa-tags me-1"></i> Gallery Categories
                    </a>
                    <a href="<?php echo SITE_URL; ?>/gallery.php" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-external-link-alt me-1"></i> View Live Gallery
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Media Gallery</h3>
                    <p class="text-muted small mb-0">Upload photos, manage categories, and organize adventure moments</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo SITE_URL; ?>/admin/gallery/categories.php" class="btn btn-outline-success btn-sm px-3">
                        <i class="fas fa-folder-plus me-1"></i> Manage Categories
                    </a>
                    <button class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Upload Photos
                    </button>
                </div>
            </div>

            <?php echo get_flash_message(); ?>

            <!-- Filter Controls Bar -->
            <div class="admin-card mb-4 p-3">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Filter by Category</label>
                        <select name="cat_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">-- All Categories --</option>
                            <?php foreach ($gallery_cats as $gc): ?>
                                <option value="<?php echo $gc['id']; ?>" <?php echo ($filter_cat == $gc['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($gc['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Filter by Trek</label>
                        <select name="trek_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">-- All Treks --</option>
                            <?php foreach ($treks as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($filter_trek == $t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-md-end mt-md-4">
                        <?php if ($filter_cat > 0 || $filter_trek > 0): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/gallery/manage.php" class="btn btn-outline-secondary btn-sm me-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-secondary px-3 py-2 fs-6">Total: <?php echo count($photos); ?> Photos</span>
                    </div>
                </form>
            </div>

            <!-- Photos Grid View -->
            <?php if (!empty($photos)): ?>
                <div class="row g-3">
                    <?php foreach ($photos as $p): 
                        $img_src = !empty($p['thumbnail_path']) ? (SITE_URL . '/' . $p['thumbnail_path']) : (SITE_URL . '/' . $p['image_path']);
                    ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="gallery-admin-card h-100 d-flex flex-column">
                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($p['image_title'] ?? 'Photo'); ?>" class="gallery-admin-thumb" loading="lazy">
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <h6 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($p['image_title'] ?? 'Untitled'); ?>">
                                        <?php echo htmlspecialchars($p['image_title'] ?? 'Untitled Photo'); ?>
                                    </h6>
                                    <div class="mb-2">
                                        <?php if (!empty($p['category_name'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1 small">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($p['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['trek_title'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle small">
                                                <i class="fas fa-mountain me-1"></i><?php echo htmlspecialchars($p['trek_title']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-auto pt-2 d-flex justify-content-between border-top">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this photo permanently?');">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Photo Modal -->
                        <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <input type="hidden" name="action" value="edit_photo">
                                        <input type="hidden" name="photo_id" value="<?php echo $p['id']; ?>">
                                        
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-edit text-success me-2"></i>Edit Photo Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-center mb-3">
                                                <img src="<?php echo $img_src; ?>" class="rounded img-fluid" style="max-height: 150px; object-fit: cover;">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Photo Title / Caption</label>
                                                <input type="text" name="caption" class="form-control" value="<?php echo htmlspecialchars($p['image_title'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Gallery Category</label>
                                                <select name="category_id" class="form-select">
                                                    <option value="">-- No Category --</option>
                                                    <?php foreach ($gallery_cats as $gc): ?>
                                                        <option value="<?php echo $gc['id']; ?>" <?php echo ($p['category_id'] == $gc['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($gc['category_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Link to Trek (Optional)</label>
                                                <select name="trek_id" class="form-select">
                                                    <option value="">-- No Specific Trek --</option>
                                                    <?php foreach ($treks as $t): ?>
                                                        <option value="<?php echo $t['id']; ?>" <?php echo ($p['trek_id'] == $t['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($t['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="admin-card text-center py-5">
                    <i class="fas fa-camera-retro fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold">No photos found</h5>
                    <p class="text-muted small">Upload photos to display in the adventure gallery on your website.</p>
                    <button class="btn btn-success btn-sm px-4" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Upload Photos Now
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Upload Photos Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_photos">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cloud-upload-alt text-success me-2"></i>Upload Photos to Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Photos <span class="text-danger">*</span></label>
                        <div class="upload-dropzone" onclick="document.getElementById('photoFilesInput').click();">
                            <i class="fas fa-images fa-2x text-success mb-2"></i>
                            <h6 class="fw-bold mb-1">Click or drag images here to upload</h6>
                            <small class="text-muted">JPG, PNG, WEBP allowed (Max 5MB each). Select multiple photos at once.</small>
                            <input type="file" name="photos[]" id="photoFilesInput" class="d-none" multiple accept="image/*" required onchange="showSelectedCount(this)">
                        </div>
                        <div id="fileSelectionStatus" class="mt-2 text-success small fw-bold"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Existing Category</label>
                            <select name="category_id" class="form-select" id="existingCatSelect">
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($gallery_cats as $gc): ?>
                                    <option value="<?php echo $gc['id']; ?>"><?php echo htmlspecialchars($gc['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">OR Create New Category</label>
                            <input type="text" name="new_category_name" class="form-control" placeholder="e.g. Sunset Views, Monsoons">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Link to Trek (Optional)</label>
                            <select name="trek_id" class="form-select">
                                <option value="">-- No Specific Trek --</option>
                                <?php foreach ($treks as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Caption / Title (Optional)</label>
                            <input type="text" name="caption" class="form-control" placeholder="Default title for uploaded photo(s)">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                        <i class="fas fa-upload me-1"></i> Upload Photos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
<script>
function showSelectedCount(input) {
    const statusDiv = document.getElementById('fileSelectionStatus');
    if (input.files && input.files.length > 0) {
        statusDiv.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + input.files.length + ' photo(s) selected.';
    } else {
        statusDiv.innerHTML = '';
    }
}
</script>
</body>
</html>
