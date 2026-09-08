<?php
/**
 * Admin - Add / Edit Review & Testimonial
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$id = (int)($_GET['id'] ?? 0); // 0 means Add Testimonial, >0 means Edit Review

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trek_id = (int)($_POST['trek_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = sanitize_input($_POST['comment'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Approved');
    
    // Parse created_at datetime picker
    $created_at_raw = $_POST['created_at'] ?? '';
    if (!empty($created_at_raw)) {
        $created_at = str_replace('T', ' ', $created_at_raw);
        if (strlen($created_at) === 16) {
            $created_at .= ':00';
        }
    } else {
        $created_at = date('Y-m-d H:i:s');
    }

    if ($trek_id <= 0 || empty($name) || empty($comment)) {
        $error = "Please fill in all required fields (Trek Destination, Author Name, Comment Content).";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating between 1 and 5 stars.";
    } else {
        try {
            if ($id > 0) {
                // Edit existing review
                $stmt = $db->prepare("UPDATE reviews SET trek_id = ?, name = ?, rating = ?, comment = ?, status = ?, created_at = ? WHERE id = ?");
                $stmt->execute([$trek_id, $name, $rating, $comment, $status, $created_at, $id]);
                
                log_activity('review_updated', ['review_id' => $id]);
                set_flash_message('success', 'Review details updated successfully.');
                header('Location: ' . SITE_URL . '/admin/reviews/manage.php');
                exit();
            } else {
                // Add new manual testimonial
                $stmt = $db->prepare("INSERT INTO reviews (user_id, trek_id, name, rating, comment, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$trek_id, $name, $rating, $comment, $status, $created_at]);
                $new_id = $db->lastInsertId();
                
                log_activity('testimonial_created', ['review_id' => $new_id]);
                set_flash_message('success', 'New testimonial created successfully.');
                header('Location: ' . SITE_URL . '/admin/reviews/manage.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Database operation failed: " . $e->getMessage();
        }
    }
}

// Fetch review details if editing
$review = null;
if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM reviews WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $review = $stmt->fetch();
        
        if (!$review) {
            set_flash_message('danger', 'Review not found.');
            header('Location: ' . SITE_URL . '/admin/reviews/manage.php');
            exit();
        }
    } catch (PDOException $e) {
        die("System error: " . $e->getMessage());
    }
}

// Fetch all treks for the dropdown
try {
    $treks_stmt = $db->query("SELECT id, title FROM treks ORDER BY title ASC");
    $all_treks = $treks_stmt->fetchAll();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit Review' : 'Create Testimonial'; ?> | Admin Portal</title>
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
                    <span class="text-muted small">Configure customer testimonials and reviews</span>
                </div>
            </div>
        </nav>

        <!-- Main Form -->
        <div class="container-fluid p-4" style="max-width: 600px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0"><?php echo $id > 0 ? 'Edit Review Details' : 'Create Testimonial'; ?></h3>
                <a href="<?php echo SITE_URL; ?>/admin/reviews/manage.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Reviews
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">Review Configurations</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Trek Destination *</label>
                            <select name="trek_id" class="form-select" required>
                                <option value="">-- Select Trek --</option>
                                <?php foreach ($all_treks as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" <?php echo (isset($review) && $review['trek_id'] == $t['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Author Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Jane Smith" value="<?php echo htmlspecialchars($review['name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Rating *</label>
                            <select name="rating" class="form-select" required>
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <option value="<?php echo $r; ?>" <?php echo (!isset($review) && $r === 5) || (isset($review) && (int)$review['rating'] === $r) ? 'selected' : ''; ?>>
                                        <?php echo $r; ?> Star<?php echo $r > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="Approved" <?php echo (!isset($review)) || (isset($review) && $review['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Pending" <?php echo isset($review) && $review['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Rejected" <?php echo isset($review) && $review['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Comment Content *</label>
                            <textarea name="comment" class="form-control" rows="5" required placeholder="Write the testimonial content here..."><?php echo htmlspecialchars($review['comment'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Date Created (Optional)</label>
                            <?php 
                            $created_at_val = '';
                            if (isset($review['created_at'])) {
                                $created_at_val = date('Y-m-d\TH:i', strtotime($review['created_at']));
                            } else {
                                $created_at_val = date('Y-m-d\TH:i');
                            }
                            ?>
                            <input type="datetime-local" name="created_at" class="form-control" value="<?php echo $created_at_val; ?>">
                            <small class="text-muted d-block mt-1">Leaves empty to use the current time, or pick a date to backdate testimonials.</small>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Save Testimonial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

</body>
</html>
