<?php
/**
 * Karnataka Trekkers - Privacy Policy
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Privacy Policy";

// Load dynamic content from database
$privacy_content = get_setting('page_privacy_content', '');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Privacy Policy</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Privacy Policy</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Main Details -->
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
                    <span class="text-success fw-bold text-uppercase d-block mb-2" style="letter-spacing: 1px;">Privacy & Security</span>
                    <h2 class="fw-bold mb-4">Our Privacy Policy</h2>
                    
                    <div class="privacy-dynamic-content">
                        <?php if (!empty($privacy_content)): ?>
                            <?php echo $privacy_content; ?>
                        <?php else: ?>
                            <p class="text-muted leading-relaxed mb-4">We respect your privacy and are committed to protecting it. The purpose of this Privacy Policy is to inform you what personally identifiable information we may collect and how it may be used. This statement only applies to this Website.</p>
                            
                            <div class="mb-4">
                                <h4 class="fw-bold text-success mb-3"><i class="fas fa-info-circle me-2"></i>Information We Collect</h4>
                                <p class="text-muted leading-relaxed">We collect personal information such as your name, email, and phone number when you place a booking or send an inquiry.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
