<?php
/**
 * Karnataka Trekkers - Refund & Cancellation Policy
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Refund & Cancellation Policy";

// Load dynamic content from database
$cancellation_content = get_setting('page_cancellation_content', '');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Refund & Cancellation Policy</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Refund & Cancellation</li>
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
                    <span class="text-success fw-bold text-uppercase d-block mb-2" style="letter-spacing: 1px;">Refund Policies</span>
                    <h2 class="fw-bold mb-4">Refund & Cancellation Rules</h2>
                    
                    <div class="cancellation-dynamic-content">
                        <?php if (!empty($cancellation_content)): ?>
                            <?php echo $cancellation_content; ?>
                        <?php else: ?>
                            <p class="text-muted leading-relaxed mb-4">Please check our cancellation timelines and fee structure below before requesting a refund or rescheduling.</p>
                            
                            <div class="mb-5 bg-light p-4 rounded-3 border-start border-success border-4">
                                <h4 class="fw-bold text-success mb-3">General Information</h4>
                                <ul class="text-muted leading-relaxed flex-column d-flex gap-2 ps-3 mb-0">
                                    <li>Refunds are processed within 2–3 business days.</li>
                                    <li>All refunds processed via the website will have a 3% payment gateway fee deducted.</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
