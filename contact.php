<?php
/**
 * Karnataka Trekkers - Contact Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Contact Us";

$contact_address = get_setting('contact_address', CONTACT_ADDRESS);
$contact_phone = get_setting('contact_phone', CONTACT_PHONE);
$contact_email = get_setting('contact_email', CONTACT_EMAIL);
$contact_hours = get_setting('contact_hours', 'Mon – Sun: 8:00 AM – 9:00 PM');
$contact_whatsapp = get_setting('contact_whatsapp', '+918951221179');

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            $success_msg = "Thank you! Your message has been received. Our team will contact you shortly.";
            
            // Send email or log notification
            $notif_text = "New contact message from $name ($phone):\nSubject: $subject\nMessage: $message";
            if (function_exists('send_whatsapp_message')) {
                send_whatsapp_message($contact_phone, $notif_text);
            }
            
        } catch (PDOException $e) {
            $error_msg = "Something went wrong. Please try again later. " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Get in Touch</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Contact Form and Coordinates Section -->
<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Contact info card -->
            <div class="col-lg-5">
                <span class="text-success fw-bold text-uppercase d-block mb-1" style="letter-spacing: 1px;">Support Center</span>
                <h2 class="fw-bold mb-4">Contact Details</h2>
                <p class="text-muted leading-relaxed">Have questions about trek dates, permissions, fitness criteria, or customized team outings? Reach out to us through any of the channels below.</p>
                
                <div class="mt-4 d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="bg-success text-white p-3 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="fas fa-map-marked-alt fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Office Headquarters</h6>
                            <p class="text-muted mb-0 small"><?php echo nl2br(htmlspecialchars($contact_address)); ?></p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="bg-success text-white p-3 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="fas fa-phone-alt fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Phone Enquiries</h6>
                            <p class="text-muted mb-0 small">
                                <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>" class="text-decoration-none text-dark fw-semibold"><?php echo htmlspecialchars($contact_phone); ?></a>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="bg-success text-white p-3 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="fas fa-envelope-open-text fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Email Support</h6>
                            <p class="text-muted mb-0 small">
                                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="text-decoration-none text-dark fw-semibold"><?php echo htmlspecialchars($contact_email); ?></a>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($contact_hours)): ?>
                        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                            <div class="bg-success text-white p-3 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="far fa-clock fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Working Hours</h6>
                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($contact_hours); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
                    <h3 class="fw-bold mb-4"><i class="far fa-paper-plane text-success me-2"></i>Send a Message</h3>
                    
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name *</label>
                                <input type="text" name="name" class="form-control py-2" required placeholder="Your full name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address *</label>
                                <input type="email" name="email" class="form-control py-2" required placeholder="you@example.com">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" name="phone" class="form-control py-2" placeholder="10-digit mobile number">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Subject *</label>
                                <input type="text" name="subject" class="form-control py-2" required placeholder="e.g. Enquiry for Kudremukh trek">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Message *</label>
                                <textarea name="message" class="form-control" rows="5" required placeholder="Write your query in detail..."></textarea>
                            </div>
                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-semibold">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
