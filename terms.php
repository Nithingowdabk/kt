<?php
/**
 * Karnataka Trekkers - Terms & Conditions
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Terms & Conditions";

// Load dynamic content from database
$terms_content = get_setting('page_terms_content', '');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Terms & Conditions</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Terms & Conditions</li>
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
                    <span class="text-success fw-bold text-uppercase d-block mb-2" style="letter-spacing: 1px;">Rules & Guidelines</span>
                    <h2 class="fw-bold mb-4">Our General Terms & Conditions</h2>
                    
                    <div class="terms-dynamic-content">
                        <?php if (!empty($terms_content)): ?>
                            <?php echo $terms_content; ?>
                        <?php else: ?>
                            <p class="text-muted leading-relaxed mb-4">Please read the following terms and conditions carefully before booking any trek or trip with Karnataka Trekkers. By making a booking, you acknowledge that you have read, understood, and agreed to be bound by these policies.</p>
                            
                            <div class="terms-list mt-4">
                                <ol class="list-group list-group-numbered list-group-flush gap-3">
                                    <li class="list-group-item border-0 ps-0 text-muted leading-relaxed">
                                        <strong class="text-dark d-block mb-1">Minimum Participants</strong>
                                        A minimum of 10–12 participants is required to organize any trek or trip.
                                    </li>
                                    <li class="list-group-item border-0 ps-0 text-muted leading-relaxed">
                                        <strong class="text-dark d-block mb-1">Alternative & Reschedule</strong>
                                        If minimum bookings are not met or permits are unavailable, an alternative trek or reschedule will be offered.
                                    </li>
                                    <li class="list-group-item border-0 ps-0 text-muted leading-relaxed">
                                        <strong class="text-dark d-block mb-1">Rescheduling Requests</strong>
                                        Rescheduling requests must be made at least 5 days before and within 30 days of the original date.
                                    </li>
                                    <li class="list-group-item border-0 ps-0 text-muted leading-relaxed">
                                        <strong class="text-dark d-block mb-1">Itinerary Changes</strong>
                                        Itinerary, activities, and pickup/drop timings are subject to change due to weather, traffic, permits, or government restrictions.
                                    </li>
                                    <li class="list-group-item border-0 ps-0 text-muted leading-relaxed">
                                        <strong class="text-dark d-block mb-1">Basic Accommodation</strong>
                                        Facilities and accommodation are basic (tents, sleeping bags, dorms, or open sky depending on the specific location).
                                    </li>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
