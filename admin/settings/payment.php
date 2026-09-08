<?php
/**
 * Admin - Payment Settings
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razorpay_key = sanitize_input($_POST['razorpay_key'] ?? '');
    $razorpay_secret = sanitize_input($_POST['razorpay_secret'] ?? '');
    $razorpay_mode = sanitize_input($_POST['razorpay_mode'] ?? 'TEST');

    try {
        $stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");
        
        $stmt->execute(['razorpay_key', $razorpay_key, $razorpay_key]);
        $stmt->execute(['razorpay_secret', $razorpay_secret, $razorpay_secret]);
        $stmt->execute(['razorpay_mode', $razorpay_mode, $razorpay_mode]);
        
        log_activity('settings_update_payment', [
            'razorpay_key' => $razorpay_key,
            'razorpay_mode' => $razorpay_mode
        ]);
        
        $success = "Payment configurations updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to save payment settings: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings | Admin Portal</title>
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
                    <span class="text-muted small">Configure payment node parameters</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4" style="max-width: 800px;">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h3 class="fw-bold mb-0">System Configurations</h3>
                
                <div class="btn-group btn-group-sm flex-wrap">
                    <a href="<?php echo SITE_URL; ?>/admin/settings/general.php" class="btn btn-outline-success">General</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/payment.php" class="btn btn-success active">Payments</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/seo.php" class="btn btn-outline-success">SEO tags</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/announcement.php" class="btn btn-outline-success">Announcement Bar</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/pages.php" class="btn btn-outline-success"><i class="fas fa-file-alt me-1"></i>Footer Pages & FAQs</a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-credit-card me-2"></i>Razorpay Gateway Integrations</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Razorpay Key ID</label>
                            <input type="text" name="razorpay_key" class="form-control" value="<?php echo htmlspecialchars(get_setting('razorpay_key', RAZORPAY_KEY_ID)); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Razorpay Key Secret</label>
                            <input type="password" name="razorpay_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('razorpay_secret', RAZORPAY_KEY_SECRET)); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Mode</label>
                            <select name="razorpay_mode" class="form-select">
                                <?php $mode = get_setting('razorpay_mode', RAZORPAY_MODE); ?>
                                <option value="TEST" <?php echo $mode === 'TEST' ? 'selected' : ''; ?>>Sandbox (TEST)</option>
                                <option value="LIVE" <?php echo $mode === 'LIVE' ? 'selected' : ''; ?>>Production (LIVE)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">Save Payment Configs</button>
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
