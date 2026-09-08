<?php
/**
 * Admin Panel Sign In
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    header('Location: ' . SITE_URL . '/admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Set Admin Sessions
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                log_activity('admin_login_success', ['username' => $admin['username']]);
                header('Location: ' . SITE_URL . '/admin/dashboard.php');
                exit();
            } else {
                $error = "Invalid admin credentials. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database validation failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Sign In | Karnataka Trekkers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-login-container">
    <div class="admin-login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-success"><i class="fas fa-user-shield me-2"></i>Admin Area</h3>
            <p class="text-muted small">Access management panel and reports</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="far fa-user"></i></span>
                    <input type="text" name="username" class="form-control" required placeholder="admin" autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                <i class="fas fa-sign-in-alt me-1"></i> Sign In to Portal
            </button>
            
            <div class="text-center mt-4">
                <a href="<?php echo SITE_URL; ?>/index.php" class="text-muted text-decoration-none small">&larr; Back to Main Website</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
