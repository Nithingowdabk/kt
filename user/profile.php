<?php
/**
 * User Profile Management
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
    $name = sanitize_input($_POST['name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($phone) < 10 || !is_numeric($phone)) {
        $error = "Please enter a valid 10-digit mobile number.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $user_id]);
            
            // Refresh Session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            
            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error = "DB Update failed: " . $e->getMessage();
        }
    }
}

// Fetch user profile details
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}

$page_title = "Manage Profile";
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
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action active"><i class="fas fa-id-card me-2"></i>Profile Details</a>
                    <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="list-group-item list-group-item-action"><i class="fas fa-heart me-2"></i>Wishlist</a>
                    <a href="<?php echo SITE_URL; ?>/user/settings.php" class="list-group-item list-group-item-action"><i class="fas fa-cogs me-2"></i>Settings</a>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-4 text-success border-bottom pb-2"><i class="far fa-id-card me-2"></i>Profile Details</h4>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address (Read-only)</label>
                            <input type="email" class="form-control" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Residential Address</label>
                            <textarea name="address" class="form-control" rows="4" placeholder="Enter your full residential address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary-custom px-4 py-2">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
