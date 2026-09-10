<?php
/**
 * Admin - Add / Edit Blog Article
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$id = (int)($_GET['id'] ?? 0); // 0 means Add mode, >0 means Edit mode

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? ''; // rich content, raw html allowed in admin
    $author = sanitize_input($_POST['author'] ?? 'Admin');
    $status = sanitize_input($_POST['status'] ?? 'Active');
    
    $meta_title = sanitize_input($_POST['meta_title'] ?? '');
    $meta_description = sanitize_input($_POST['meta_description'] ?? '');
    $focus_keyphrase = sanitize_input($_POST['focus_keyphrase'] ?? '');
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $image_alt = sanitize_input($_POST['image_alt'] ?? '');
    $tags = sanitize_input($_POST['tags'] ?? '');

    if (empty($title) || empty($content)) {
        $error = "Please fill in both title and content.";
    } else {
        $slug = create_slug($slug ?: $title);

        // Handle image upload
        $image = null;
        if ($id > 0) {
            // Get current image for fallback
            $curr = $db->prepare("SELECT image FROM blogs WHERE id = ? LIMIT 1");
            $curr->execute([$id]);
            $image = $curr->fetchColumn();
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $uploaded = secure_image_upload($_FILES['image_file'], 'blogs');
            if ($uploaded !== false) {
                $image = $uploaded;
            } else {
                $error = "Invalid blog image file. Ensure it is a valid JPG/PNG/WEBP/GIF under 2MB.";
            }
        }

        if (empty($error)) {
            try {
                // Ensure required SEO columns exist in the database (auto-migrates missing columns)
                $blog_col_specs = [
                    'meta_title' => "VARCHAR(150) DEFAULT NULL",
                    'meta_description' => "VARCHAR(255) DEFAULT NULL",
                    'focus_keyphrase' => "VARCHAR(150) DEFAULT NULL",
                    'excerpt' => "TEXT DEFAULT NULL",
                    'image_alt' => "VARCHAR(255) DEFAULT NULL",
                    'tags' => "TEXT DEFAULT NULL"
                ];
                $existing_cols = ensure_table_columns($db, 'blogs', $blog_col_specs);

                // Build data array dynamically based on columns present in the database
                $data = [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'author' => $author,
                    'status' => $status,
                    'image' => $image
                ];

                if (isset($existing_cols['meta_title'])) $data['meta_title'] = $meta_title;
                if (isset($existing_cols['meta_description'])) $data['meta_description'] = $meta_description;
                if (isset($existing_cols['focus_keyphrase'])) $data['focus_keyphrase'] = $focus_keyphrase;
                if (isset($existing_cols['excerpt'])) $data['excerpt'] = $excerpt;
                if (isset($existing_cols['image_alt'])) $data['image_alt'] = $image_alt;
                if (isset($existing_cols['tags'])) $data['tags'] = $tags;

                if ($id > 0) {
                    // UPDATE MODE
                    // Check slug uniqueness
                    $slug_check = $db->prepare("SELECT id FROM blogs WHERE slug = ? AND id != ? LIMIT 1");
                    $slug_check->execute([$slug, $id]);
                    if ($slug_check->fetch()) {
                        $slug .= '-' . rand(10, 99);
                        $data['slug'] = $slug;
                    }

                    $set_clauses = [];
                    $params = [];
                    foreach ($data as $col => $val) {
                        $set_clauses[] = "`{$col}` = ?";
                        $params[] = $val;
                    }
                    $params[] = $id;

                    $stmt = $db->prepare("UPDATE blogs SET " . implode(', ', $set_clauses) . " WHERE id = ?");
                    $stmt->execute($params);
                    
                    set_flash_message('success', 'Blog article updated successfully.');
                } else {
                    // INSERT MODE
                    // Check slug uniqueness
                    $slug_check = $db->prepare("SELECT id FROM blogs WHERE slug = ? LIMIT 1");
                    $slug_check->execute([$slug]);
                    if ($slug_check->fetch()) {
                        $slug .= '-' . rand(10, 99);
                        $data['slug'] = $slug;
                    }

                    $col_names = array_keys($data);
                    $placeholders = array_fill(0, count($col_names), '?');

                    $stmt = $db->prepare("INSERT INTO blogs (`" . implode('`, `', $col_names) . "`) VALUES (" . implode(', ', $placeholders) . ")");
                    $stmt->execute(array_values($data));
                    
                    set_flash_message('success', 'Blog article created successfully.');
                }
                
                header('Location: ' . SITE_URL . '/admin/blogs/manage.php');
                exit();
                
            } catch (PDOException $e) {
                $error = "Database operation failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch article if in Edit mode
$blog = null;
if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM blogs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $blog = $stmt->fetch();
        
        if (!$blog) {
            set_flash_message('danger', 'Blog article not found.');
            header('Location: ' . SITE_URL . '/admin/blogs/manage.php');
            exit();
        }
    } catch (PDOException $e) {
        die("System error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit Article' : 'Add Article'; ?> | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Summernote WYSIWYG Editor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    <style>
        .note-editor.note-frame {
            border-radius: 10px;
            border-color: #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .note-toolbar {
            background-color: #f8f9fa !important;
            border-top-left-radius: 9px !important;
            border-top-right-radius: 9px !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        .note-editable {
            background-color: #fff;
            min-height: 320px;
            font-size: 15px;
            line-height: 1.6;
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
                    <span class="text-muted small"><?php echo $id > 0 ? 'Edit mode' : 'Create mode'; ?></span>
                </div>
            </div>
        </nav>

        <!-- Main Form -->
        <div class="container-fluid p-4" style="max-width: 900px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0"><?php echo $id > 0 ? 'Edit Blog Article' : 'Write New Article'; ?></h3>
                <a href="<?php echo SITE_URL; ?>/admin/blogs/manage.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Articles
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">Article Content Details</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Article Title *</span>
                            </label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. 10 Essential Hiking Safety Tips for Trekkers" value="<?php echo htmlspecialchars($blog['title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>URL Slug <small class="text-muted">(leave blank to auto-generate)</small></span>
                                <span id="blog_slug_counter" class="badge bg-light text-muted border">30–50 chars</span>
                            </label>
                            <input type="text" name="slug" id="blog_slug" class="form-control" placeholder="e.g. hiking-safety-guide-western-ghats" value="<?php echo htmlspecialchars($blog['slug'] ?? ''); ?>" data-seo-counter="blog_slug_counter" data-seo-min="30" data-seo-max="50">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Author Name</label>
                            <input type="text" name="author" class="form-control" placeholder="Admin" value="<?php echo htmlspecialchars($blog['author'] ?? 'Admin'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo isset($blog) && $blog['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo isset($blog) && $blog['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <!-- Blog Excerpt / Summary -->
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Article Excerpt / Summary <small class="text-muted">(Short teaser shown on blog listings and search results)</small></span>
                                <span id="blog_excerpt_counter" class="badge bg-light text-muted border">120–200 chars</span>
                            </label>
                            <textarea name="excerpt" id="blog_excerpt" class="form-control" rows="2" placeholder="Discover the essential hiking safety tips and precautions for navigating the rugged terrains and monsoons of Western Ghats with ease and confidence." data-seo-counter="blog_excerpt_counter" data-seo-min="120" data-seo-max="200"><?php echo htmlspecialchars($blog['excerpt'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Article Cover Image</label>
                            <input type="file" name="image_file" class="form-control">
                            <?php if (!empty($blog['image'])): ?>
                                <small class="text-success d-block mt-1">Current Image: <strong><?php echo htmlspecialchars($blog['image']); ?></strong></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Cover Image Alt Text</span>
                                <span id="blog_image_alt_counter" class="badge bg-light text-muted border">50–125 chars</span>
                            </label>
                            <input type="text" name="image_alt" id="blog_image_alt" class="form-control" placeholder="e.g. Group of happy backpackers trekking along scenic green mountain trail" value="<?php echo htmlspecialchars($blog['image_alt'] ?? ''); ?>" data-seo-counter="blog_image_alt_counter" data-seo-min="50" data-seo-max="125">
                            <small class="text-muted">Descriptive alt text for image accessibility and Google Image SEO.</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Article Content *</label>
                            <textarea name="content" class="form-control summernote-blog-editor" required placeholder="Write article content here..."><?php echo htmlspecialchars($blog['content'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO Optimization Card -->
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-search me-2"></i>SEO Optimization & Search Engine Indexing</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-key me-1 text-success"></i> Focus Keyphrase <small class="text-muted">(Primary search keyword/phrase target)</small></span>
                                <span id="blog_keyphrase_counter" class="badge bg-light text-muted border">10–40 chars</span>
                            </label>
                            <input type="text" name="focus_keyphrase" id="blog_focus_keyphrase" class="form-control" placeholder="e.g. Hiking Safety Guide Karnataka" value="<?php echo htmlspecialchars($blog['focus_keyphrase'] ?? ''); ?>" data-seo-counter="blog_keyphrase_counter" data-seo-min="10" data-seo-max="40">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>SEO Title (Meta Title)</span>
                                <span id="blog_meta_title_counter" class="badge bg-light text-muted border">50–60 chars</span>
                            </label>
                            <input type="text" name="meta_title" id="blog_meta_title" class="form-control" placeholder="e.g. Top 10 Hiking Safety Tips for Western Ghats Trekkers" value="<?php echo htmlspecialchars($blog['meta_title'] ?? ''); ?>" data-seo-counter="blog_meta_title_counter" data-seo-min="50" data-seo-max="60">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Meta Description</span>
                                <span id="blog_meta_desc_counter" class="badge bg-light text-muted border">150–155 chars</span>
                            </label>
                            <textarea name="meta_description" id="blog_meta_description" class="form-control" rows="2" placeholder="Learn the must-know trekking safety tips for hiking in Karnataka and Western Ghats. Stay prepared with guidance from expert outdoor tour leaders." data-seo-counter="blog_meta_desc_counter" data-seo-min="150" data-seo-max="155"><?php echo htmlspecialchars($blog['meta_description'] ?? ''); ?></textarea>
                        </div>

                        <!-- 10-12 Tags Manager -->
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tags me-1 text-success"></i> Blog Tags & Keywords <small class="text-muted">(Recommended: 10–12 tags)</small></span>
                                <span class="tag-count-badge badge bg-light text-muted border">0 tags</span>
                            </label>
                            <div class="tag-manager-wrapper border rounded p-3 bg-light">
                                <input type="hidden" name="tags" class="tag-hidden-input" value="<?php echo htmlspecialchars($blog['tags'] ?? ''); ?>">
                                <div class="tag-chips-container mb-2 min-h-30"></div>
                                <div class="input-group">
                                    <input type="text" class="form-control tag-add-input" placeholder="Type a tag and press Enter or comma (e.g. Trekking Tips, Safety Guide, Hiking Gear, Karnataka)...">
                                    <button class="btn btn-outline-success btn-add-tag" type="button"><i class="fas fa-plus me-1"></i> Add Tag</button>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i> Press Enter or comma to add each tag. Aim for 10 to 12 descriptive tags to boost search visibility.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-success px-5 py-3 shadow fw-bold">
                        <i class="fas fa-save me-1"></i> Save Blog Article
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Summernote WYSIWYG JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script>
$(document).ready(function() {
    $('.summernote-blog-editor').summernote({
        placeholder: 'Write your story and format article content visually here...',
        tabsize: 2,
        height: 380,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});
</script>

</body>
</html>
