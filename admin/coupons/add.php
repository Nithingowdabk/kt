<?php
/**
 * Admin - Add / Edit Coupon
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
    $code = sanitize_input(strtoupper($_POST['code'] ?? ''));
    $discount_type = sanitize_input($_POST['discount_type'] ?? 'Percentage');
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_booking_amount = (float)($_POST['min_booking_amount'] ?? 0);
    $expiry_date = sanitize_input($_POST['expiry_date'] ?? '');
    $max_uses = (int)($_POST['max_uses'] ?? 0);
    $status = sanitize_input($_POST['status'] ?? 'Active');

    if (empty($code) || $discount_value <= 0 || empty($expiry_date)) {
        $error = "Please fill in all core required fields (Code, Value, Expiry Date).";
    } else {
        try {
            if ($id > 0) {
                // UPDATE MODE
                // Check code uniqueness
                $code_check = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ? LIMIT 1");
                $code_check->execute([$code, $id]);
                if ($code_check->fetch()) {
                    $error = "A coupon with this code already exists.";
                } else {
                    $stmt = $db->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_booking_amount = ?, expiry_date = ?, max_uses = ?, status = ? WHERE id = ?");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_booking_amount, $expiry_date, $max_uses, $status, $id]);
                    
                    set_flash_message('success', 'Coupon details updated successfully.');
                    header('Location: ' . SITE_URL . '/admin/coupons/manage.php');
                    exit();
                }
            } else {
                // INSERT MODE
                // Check code uniqueness
                $code_check = $db->prepare("SELECT id FROM coupons WHERE code = ? LIMIT 1");
                $code_check->execute([$code]);
                if ($code_check->fetch()) {
                    $error = "A coupon with this code already exists.";
                } else {
                    $stmt = $db->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_booking_amount, expiry_date, max_uses, current_uses, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_booking_amount, $expiry_date, $max_uses, $status]);
                    
                    set_flash_message('success', 'Coupon created successfully!');
                    header('Location: ' . SITE_URL . '/admin/coupons/manage.php');
                    exit();
                }
            }
            
        } catch (PDOException $e) {
            $error = "Database operation failed: " . $e->getMessage();
        }
    }
}

// Fetch coupon if in Edit mode
$coupon = null;
if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) {
            set_flash_message('danger', 'Coupon not found.');
            header('Location: ' . SITE_URL . '/admin/coupons/manage.php');
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
    <title><?php echo $id > 0 ? 'Edit Coupon' : 'Create Coupon'; ?> | Admin Portal</title>
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
                    <span class="text-muted small">Promotional code configuration</span>
                </div>
            </div>
        </nav>

        <!-- Main Form -->
        <div class="container-fluid p-4" style="max-width: 600px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0"><?php echo $id > 0 ? 'Edit Coupon Details' : 'Create Coupon'; ?></h3>
                <a href="<?php echo SITE_URL; ?>/admin/coupons/manage.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Coupons
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">Discount Configurations</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Coupon Code *</label>
                            <input type="text" name="code" class="form-control text-uppercase" required placeholder="e.g. MONSOON300" value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Discount Type *</label>
                            <select name="discount_type" class="form-select" required>
                                <option value="Percentage" <?php echo isset($coupon) && $coupon['discount_type'] === 'Percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="Fixed" <?php echo isset($coupon) && $coupon['discount_type'] === 'Fixed' ? 'selected' : ''; ?>>Fixed Amount (INR)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount Value *</label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" required placeholder="e.g. 10 or 500" value="<?php echo htmlspecialchars($coupon['discount_value'] ?? ''); ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Minimum Purchase Amount (INR)</label>
                            <input type="number" step="0.01" name="min_booking_amount" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($coupon['min_booking_amount'] ?? '0.00'); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expiry Date *</label>
                            <input type="date" name="expiry_date" class="form-control" required value="<?php echo htmlspecialchars($coupon['expiry_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Usage Limits (0 for unlimited)</label>
                            <input type="number" name="max_uses" class="form-control" value="<?php echo htmlspecialchars($coupon['max_uses'] ?? '0'); ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo isset($coupon) && $coupon['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo isset($coupon) && $coupon['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Save Coupon
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
