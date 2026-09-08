<?php
/**
 * Karnataka Trekkers - FAQ Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Frequently Asked Questions";

// Load dynamic FAQs from database
$faqs_json = get_setting('page_faqs_json', '');
$faqs = [];

if (!empty($faqs_json)) {
    $faqs = json_decode($faqs_json, true) ?: [];
}

// Fallback if no FAQs are defined in admin yet
if (empty($faqs)) {
    $faqs = [
        [
            'question' => 'Are forest permits included in the trek price?',
            'answer' => 'Yes, for Kudremukh, Netravati, and similar treks, we handle the reservation slot booking and forest office entry permissions. The cost of these entry fees is fully included in your booking package.'
        ],
        [
            'question' => 'Can absolute beginners join Western Ghats treks?',
            'answer' => 'Yes! Beginners can easily complete treks categorized as "Easy" or "Moderate" (like Skandagiri or Kudremukh) provided they have basic cardiovascular fitness. For difficult treks (like Kumara Parvatha), we recommend having completed at least 2 moderate treks earlier.'
        ],
        [
            'question' => 'What is the accommodation type in the Western Ghats?',
            'answer' => 'We arrange shared accommodations in clean, rustic homestays or standard dome tents, depending on the itinerary. Hot water is available, and traditional home-style veg/non-veg meals are served.'
        ],
        [
            'question' => 'What is the booking cancellation and refund policy?',
            'answer' => 'Cancellations 15–30 days prior to the trek receive a 50% refund (minus 3% gateway fee). Cancellations within 15 days of departure are non-refundable. Rescheduling requests must be placed at least 5 days in advance.'
        ],
        [
            'question' => 'What happens during heavy rain conditions?',
            'answer' => 'Safety is our priority. If the forest department restricts entry due to landslides, flood risk, or heavy storm alerts, we reschedule the trek to the next available weekend or provide an alternative destination.'
        ]
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Frequently Asked Questions</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">FAQs</li>
            </ol>
        </nav>
    </div>
</section>

<!-- FAQs Accordions Section -->
<section class="section-padding">
    <div class="container" style="max-width: 900px;">
        <div class="section-title text-center mb-5">
            <span class="text-success fw-bold text-uppercase d-block mb-1" style="letter-spacing: 1px;">Got Questions?</span>
            <h2 class="fw-bold">Common Inquiries & Answers</h2>
        </div>
        
        <div class="accordion shadow-sm rounded-3 overflow-hidden" id="faqAccordion">
            <?php foreach ($faqs as $index => $faq): 
                $collapse_id = "collapseFaq_" . $index;
                $heading_id = "headingFaq_" . $index;
                $is_first = ($index === 0);
            ?>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="<?php echo $heading_id; ?>">
                        <button class="accordion-button fw-bold text-success <?php echo $is_first ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapse_id; ?>">
                            <span class="me-2"><?php echo ($index + 1) . '.'; ?></span> <?php echo htmlspecialchars($faq['question']); ?>
                        </button>
                    </h2>
                    <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse <?php echo $is_first ? 'show' : ''; ?>" aria-labelledby="<?php echo $heading_id; ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card border-0 bg-light p-4 rounded-4 mt-5 text-center">
            <h5 class="fw-bold mb-2">Still have questions?</h5>
            <p class="text-muted mb-3">Our adventure specialists are here to assist you with trail details, bookings, and customized group trips.</p>
            <div>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-success px-4 py-2 fw-semibold rounded-3 me-2">
                    <i class="fas fa-headset me-1"></i> Contact Support
                </a>
                <a href="https://wa.me/918951221179" target="_blank" class="btn btn-outline-success px-4 py-2 fw-semibold rounded-3">
                    <i class="fab fa-whatsapp me-1"></i> WhatsApp Us
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
