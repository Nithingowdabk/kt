<?php
/**
 * Trek Leader Portal - Sign In
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Redirect if already logged in as leader
if (is_leader_logged_in()) {
    header('Location: ' . SITE_URL . '/admin/trek-leaders/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM trek_leaders WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $leader = $stmt->fetch();

            if ($leader && password_verify($password, $leader['password_hash'])) {
                if ($leader['status'] !== 'Active') {
                    $error = "Your account is currently suspended. Please contact admin.";
                } else {
                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Set Leader Sessions
                    $_SESSION['leader_id'] = $leader['id'];
                    $_SESSION['leader_name'] = $leader['name'];
                    $_SESSION['leader_email'] = $leader['email'];
                    
                    log_activity('leader_login_success', ['email' => $leader['email']]);
                    header('Location: ' . SITE_URL . '/admin/trek-leaders/dashboard.php');
                    exit();
                }
            } else {
                $error = "Invalid credentials. Please verify your email/password.";
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
    <title>Trek Leader Portal Login | Karnataka Trekkers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-lg p-4 bg-white rounded-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-success"><i class="fas fa-mountain me-2"></i>Trek Leader Portal</h3>
                    <p class="text-muted small">Access your assigned treks, rosters, and mark attendance</p>
                </div>

                <?php echo get_flash_message(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Leader Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="leader@karnatakatrekkers.com" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2.5 fw-bold shadow-sm rounded-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Sign In as Leader
                    </button>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="text-muted text-decoration-none small">&larr; Return to main site</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
