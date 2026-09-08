<?php
/**
 * Karnataka Trekkers - User Login
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_user_logged_in()) {
    $redirect_url = $_GET['redirect'] ?? $_SESSION['redirect_url'] ?? '';
    unset($_SESSION['redirect_url']);
    $redirect = sanitize_redirect_url($redirect_url);
    header('Location: ' . $redirect);
    exit();
}

// Capture redirect parameter from query string if available
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all details.";
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'Active') {
                    $error = "Your account is currently inactive. Please contact support.";
                } else {
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    // Set Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_phone'] = $user['phone'];

                    // Redirect URL check
                    $redirect_url = $_SESSION['redirect_url'] ?? $_GET['redirect'] ?? $_POST['redirect'] ?? '';
                    unset($_SESSION['redirect_url']);
                    $redirect = sanitize_redirect_url($redirect_url);
                    
                    log_activity('user_login_success', ['email' => $user['email']]);
                    set_flash_message('success', 'Logged in successfully! Welcome back, ' . $user['name'] . '.');
                    header('Location: ' . $redirect);
                    exit();
                }
            } else {
                $error = "Invalid email or password combination.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again. " . $e->getMessage();
        }
    }
}

$page_title = "User Login";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding bg-light d-flex align-items-center" style="min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card border-0 shadow-sm p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-success"><i class="fas fa-hiking me-2"></i>Sign In</h3>
                        <p class="text-muted">Access your bookings and dashboard</p>
                    </div>

                    <?php echo get_flash_message(); ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <label class="form-label mb-0">Password</label>
                                <a href="#" class="text-success text-decoration-none small">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required placeholder="Enter password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100 mb-3">Sign In</button>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0 text-muted small">Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : (isset($_SESSION['redirect_url']) ? '?redirect=' . urlencode($_SESSION['redirect_url']) : ''); ?>" class="text-success text-decoration-none fw-bold">Sign Up Now</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
