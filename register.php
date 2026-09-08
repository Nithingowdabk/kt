<?php
/**
 * Karnataka Trekkers - User Registration
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
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = sanitize_input($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($phone) < 10 || !is_numeric($phone)) {
        $error = "Please enter a valid 10-digit mobile number.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $db = Database::connect();
            
            // Check if email already exists
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check_stmt->execute([$email]);
            if ($check_stmt->fetch()) {
                $error = "An account with this email address already exists.";
            } else {
                // Insert User
                $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, phone, address, status, email_verified) VALUES (?, ?, ?, ?, ?, 'Active', 1)");
                $stmt->execute([$name, $email, $pass_hash, $phone, $address]);
                
                $user_id = $db->lastInsertId();

                // Dispatch Lead Created event to Naitrons SaaS
                sendNaitronsEvent('LEAD_CREATED', [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'userId' => $user_id
                ], $user_id);

                // Set Session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_phone'] = $phone;

                // Send Welcome WhatsApp Message
                $welcome_msg = "Hello $name,\nWelcome to Karnataka Trekkers! 🌄\nYour account has been registered successfully.\nExplore our latest trails and book your next adventure with us.\n\nHappy Trekking,\nTeam Karnataka Trekkers";
                send_whatsapp_message($phone, $welcome_msg);

                set_flash_message('success', 'Account registered successfully! Welcome aboard, ' . $name . '.');
                $redirect_url = $_SESSION['redirect_url'] ?? $_GET['redirect'] ?? $_POST['redirect'] ?? '';
                unset($_SESSION['redirect_url']);
                $redirect = sanitize_redirect_url($redirect_url);
                header('Location: ' . $redirect);
                exit();
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again. " . $e->getMessage();
        }
    }
}

$page_title = "User Registration";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding bg-light d-flex align-items-center" style="min-height: 90vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card border-0 shadow-sm p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-success"><i class="fas fa-hiking me-2"></i>Create Account</h3>
                        <p class="text-muted">Start booking your outdoor journeys</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-user"></i></span>
                                    <input type="text" name="name" class="form-control" required placeholder="John Doe" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required placeholder="john@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Phone Number *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                    <input type="tel" name="phone" class="form-control" required placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required placeholder="Min 6 characters">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Residential Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Enter your full address (optional)"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            </div>

                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary-custom w-100">Sign Up</button>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <p class="mb-0 text-muted small">Already have an account? <a href="<?php echo SITE_URL; ?>/login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : (isset($_SESSION['redirect_url']) ? '?redirect=' . urlencode($_SESSION['redirect_url']) : ''); ?>" class="text-success text-decoration-none fw-bold">Sign In Here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
