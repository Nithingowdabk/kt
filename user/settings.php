<?php
/**
 * User Account Settings
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$user_id = get_logged_in_user_id();
$db = Database::connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "Please fill in all details.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } else {
        try {
            // Retrieve current password hash
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_pass, $user['password_hash'])) {
                // Update Password hash
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $up = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $up->execute([$new_hash, $user_id]);
                
                $success = "Password updated successfully!";
            } else {
                $error = "Your current password is incorrect.";
            }
        } catch (PDOException $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}

$page_title = "Account Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="row g-4">
        
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center user-sidebar-header mb-4 pb-3 border-bottom">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="far fa-user"></i>
                    </div>
                    <div class="user-sidebar-info overflow-hidden" style="min-width: 0;">
                        <h5 class="fw-bold mb-0 text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                        <small class="text-muted d-block text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_email']); ?>"><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/user/bookings.php" class="list-group-item list-group-item-action"><i class="fas fa-hiking me-2"></i>My Bookings</a>
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action"><i class="fas fa-id-card me-2"></i>Profile Details</a>
                    <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="list-group-item list-group-item-action"><i class="fas fa-heart me-2"></i>Wishlist</a>
                    <a href="<?php echo SITE_URL; ?>/user/settings.php" class="list-group-item list-group-item-action active"><i class="fas fa-cogs me-2"></i>Settings</a>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <!-- Password edit form -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-4 text-success border-bottom pb-2"><i class="fas fa-key me-2"></i>Change Password</h4>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" style="max-width: 500px;">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Update Password</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
