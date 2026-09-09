<?php
/**
 * Karnataka Trekkers - Trek Details Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = Database::connect();
$slug = sanitize_input($_GET['slug'] ?? '');

// 301 Canonical Redirect for legacy query URLs: /treks/details.php?slug=xxx -> /treks/xxx
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'details.php') !== false) {
    if (!empty($slug)) {
        header("Location: " . SITE_URL . "/treks/" . $slug, true, 301);
    } else {
        header("Location: " . SITE_URL . "/treks", true, 301);
    }
    exit();
}

if (empty($slug)) {
    header('Location: ' . SITE_URL . '/treks');
    exit();
}

try {
    // 1. Fetch Trek Details
    $stmt = $db->prepare("SELECT t.*, c.category_name as category_name FROM treks t 
                          LEFT JOIN trek_categories c ON t.category_id = c.id 
                          WHERE t.slug = ? AND t.status = 'Active' LIMIT 1");
    $stmt->execute([$slug]);
    $trek = $stmt->fetch();

    if (!$trek) {
        set_flash_message('danger', 'Trek not found.');
        header('Location: ' . SITE_URL . '/treks/index.php');
        exit();
    }

    $trek_id = $trek['id'];

    // Package details parsing
    $transport_enabled = (int)($trek['transport_enabled'] ?? 1);
    $own_transport_enabled = (int)($trek['own_transport_enabled'] ?? 1);
    $with_transport_price = (float)($trek['with_transport_price'] ?? $trek['price']);
    $with_transport_offer_price = (float)($trek['with_transport_offer_price'] ?? $trek['offer_price']);
    $without_transport_price = (float)($trek['without_transport_price'] ?? ($trek['price'] * 0.6));
    $without_transport_offer_price = (float)($trek['without_transport_offer_price'] ?? ($trek['offer_price'] * 0.6));
    $own_transport_note = $trek['own_transport_note'] ?? '';
    $with_transport_note = $trek['with_transport_note'] ?? '';

    if ($own_transport_enabled) {
        $default_package = 'without_transport';
        $active_price = $without_transport_offer_price > 0 ? $without_transport_offer_price : $without_transport_price;
    } else {
        $default_package = 'with_transport';
        $active_price = $with_transport_offer_price > 0 ? $with_transport_offer_price : $with_transport_price;
    }

    // Handle Review Submission
    if (isset($_GET['action']) && $_GET['action'] === 'add_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_user_logged_in()) {
            set_flash_message('danger', 'Please login to submit a review.');
            header('Location: ' . SITE_URL . '/treks/' . $slug);
            exit();
        }
        
        // Check verification
        $check_booking = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND trek_id = ? AND booking_status = 'Confirmed' AND payment_status = 'Paid'");
        $check_booking->execute([get_logged_in_user_id(), $trek_id]);
        if ($check_booking->fetchColumn() <= 0) {
            set_flash_message('danger', 'Only verified trekkers who have completed this trek can submit a review.');
            header('Location: ' . SITE_URL . '/treks/' . $slug);
            exit();
        }

        $rating = (int)$_POST['rating'];
        $comment = sanitize_input($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5 || empty($comment)) {
            set_flash_message('danger', 'Please provide a valid rating and review comment.');
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO reviews (user_id, trek_id, name, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([
                    get_logged_in_user_id(),
                    $trek_id,
                    get_logged_in_user_name(),
                    $rating,
                    $comment
                ]);
                set_flash_message('success', 'Thank you! Your review has been submitted for moderation and will appear once approved.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Failed to submit review: ' . $e->getMessage());
            }
        }
        header('Location: ' . SITE_URL . '/treks/' . $slug);
        exit();
    }

    // Check if current user is a verified trekker for this trek
    $is_verified_trekker = false;
    if (is_user_logged_in()) {
        $check_booking = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND trek_id = ? AND booking_status = 'Confirmed' AND payment_status = 'Paid'");
        $check_booking->execute([get_logged_in_user_id(), $trek_id]);
        if ($check_booking->fetchColumn() > 0) {
            $is_verified_trekker = true;
        }
    }

    // 2. Fetch Trek Dates
    $dates_stmt = $db->prepare("SELECT * FROM trek_dates WHERE trek_id = ? AND start_date >= CURDATE() AND status = 'Active' ORDER BY start_date ASC");
    $dates_stmt->execute([$trek_id]);
    $trek_dates = $dates_stmt->fetchAll();

    // 3. Fetch Pickup Points
    $pickup_stmt = $db->prepare("SELECT * FROM pickup_points WHERE trek_id = ?");
    $pickup_stmt->execute([$trek_id]);
    $pickup_points = $pickup_stmt->fetchAll();

    // 4. Fetch Reviews
    $reviews_stmt = $db->prepare("SELECT * FROM reviews WHERE trek_id = ? AND status = 'Approved' ORDER BY created_at DESC");
    $reviews_stmt->execute([$trek_id]);
    $reviews = $reviews_stmt->fetchAll();

    // Fetch dynamic Highlights
    $hl_stmt = $db->prepare("SELECT * FROM trek_highlights WHERE trek_id = ? ORDER BY sort_order ASC");
    $hl_stmt->execute([$trek_id]);
    $trek_highlights = $hl_stmt->fetchAll();

    // Fetch dynamic Notes
    $notes_stmt = $db->prepare("SELECT * FROM trek_notes WHERE trek_id = ? ORDER BY sort_order ASC");
    $notes_stmt->execute([$trek_id]);
    $trek_notes = $notes_stmt->fetchAll();

    // Fetch dynamic Additional Notes
    $additional_notes_stmt = $db->prepare("SELECT * FROM trek_additional_notes WHERE trek_id = ? ORDER BY sort_order ASC");
    $additional_notes_stmt->execute([$trek_id]);
    $trek_additional_notes = $additional_notes_stmt->fetchAll();

    // Fetch dynamic FAQs
    $faqs_stmt = $db->prepare("SELECT * FROM trek_faqs WHERE trek_id = ? ORDER BY sort_order ASC");
    $faqs_stmt->execute([$trek_id]);
    $trek_faqs = $faqs_stmt->fetchAll();

    // 5. Fetch Avg Rating
    $avg_rating = 5.0;
    if (count($reviews) > 0) {
        $ratings_sum = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($ratings_sum / count($reviews), 1);
    }
    // Fetch Gallery
    $gallery_stmt = $db->prepare("SELECT * FROM trek_gallery WHERE trek_id = ? ORDER BY sort_order ASC");
    $gallery_stmt->execute([$trek_id]);
    $gallery = $gallery_stmt->fetchAll();

    // Fetch Videos
    $video_stmt = $db->prepare("SELECT * FROM trek_videos WHERE trek_id = ? ORDER BY sort_order ASC");
    $video_stmt->execute([$trek_id]);
    $videos = $video_stmt->fetchAll();

    // Override main image if featured image exists
    $featured_image = null;
    foreach ($gallery as $img) {
        if ($img['is_featured'] == 1) {
            $featured_image = $img['image_path'];
            break;
        }
    }
    if ($featured_image) {
        $trek['image'] = $featured_image;
    }

    // Decode itinerary JSON
    $itinerary = json_decode($trek['itinerary'], true);
    if (!is_array($itinerary)) {
        // Fallback if not valid JSON
        $itinerary = [];
    }

    // 6. Fetch Related Treks (max 3)
    $related_stmt = $db->prepare("SELECT t.*, c.category_name as category_name, (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image FROM treks t LEFT JOIN trek_categories c ON t.category_id = c.id WHERE t.category_id = ? AND t.id != ? AND t.status = 'Active' LIMIT 3");
    $related_stmt->execute([$trek['category_id'], $trek_id]);
    $related_treks = $related_stmt->fetchAll();
    
    // If fewer than 3, fetch others to fill
    if (count($related_treks) < 3) {
        $needed = 3 - count($related_treks);
        $exclude_ids = array_merge([$trek_id], array_column($related_treks, 'id'));
        $in_clause = implode(',', array_fill(0, count($exclude_ids), '?'));
        
        $stmt_fill = $db->prepare("SELECT t.*, c.category_name as category_name, (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image FROM treks t LEFT JOIN trek_categories c ON t.category_id = c.id WHERE t.id NOT IN ($in_clause) AND t.status = 'Active' LIMIT $needed");
        $stmt_fill->execute($exclude_ids);
        $related_treks = array_merge($related_treks, $stmt_fill->fetchAll());
    }

} catch (PDOException $e) {
    echo "System error: " . $e->getMessage();
    exit();
}

$page_title = $trek['title'];
$meta_title = !empty($trek['meta_title']) ? $trek['meta_title'] : $trek['title'];
$meta_desc = !empty($trek['meta_description']) ? $trek['meta_description'] : (!empty($trek['excerpt']) ? $trek['excerpt'] : substr(strip_tags($trek['description']), 0, 155));
$meta_keywords = !empty($trek['tags']) ? $trek['tags'] : (!empty($trek['focus_keyphrase']) ? $trek['focus_keyphrase'] : null);
$og_image = !empty($trek['image']) ? (SITE_URL . '/' . $trek['image']) : null;
$og_image_alt = !empty($trek['image_alt']) ? $trek['image_alt'] : $trek['title'];

// Add extra js for booking flow
$extra_js = ['assets/js/booking.js'];

$extra_head = '';
if (!empty($trek_faqs)) {
    $schema_questions = [];
    foreach ($trek_faqs as $faq) {
        $schema_questions[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }
    $faq_json = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $schema_questions
    ];
    $extra_head .= "\n    " . '<script type="application/ld+json">' . json_encode($faq_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

if (!function_exists('get_initials')) {
    function get_initials($name) {
        $parts = explode(' ', trim($name));
        $initials = '';
        if (count($parts) > 0) {
            $initials .= strtoupper(substr($parts[0], 0, 1));
            if (count($parts) > 1) {
                $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
            }
        }
        return $initials ?: 'U';
    }
}

$use_glightbox = true;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Custom Calendar Picker Styling */
.calendar-trigger-container {
    position: relative;
}
#calendar_trigger {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: 0.95rem;
    font-weight: 500;
    color: #495057;
    background-color: #fff;
    cursor: pointer;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
#calendar_trigger:focus, #calendar_trigger.active {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    outline: 0;
}
.calendar-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    margin-top: 8px;
    padding: 16px;
    width: 100%;
    min-width: 280px;
    user-select: none;
}
.calendar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1040;
    backdrop-filter: blur(2px);
}
@media (max-width: 991.98px) {
    .calendar-picker-dropdown {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        width: 90% !important;
        max-width: 340px !important;
        z-index: 1050 !important;
        margin-top: 0;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
}
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.calendar-header-title {
    font-weight: 700;
    font-size: 1rem;
    color: #1a202c;
    display: flex;
    align-items: center;
    gap: 4px;
}
.calendar-nav-btn {
    background: #f7fafc;
    border: 1px solid #edf2f7;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    color: #4a5568;
    transition: all 0.2s;
}
.calendar-nav-btn:hover {
    background: #edf2f7;
    color: #1a202c;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
}
.calendar-grid-header {
    font-size: 0.75rem;
    font-weight: 700;
    color: #718096;
    text-transform: uppercase;
    text-align: center;
    padding-bottom: 6px;
}
.calendar-cell {
    aspect-ratio: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 50%;
    cursor: default;
    transition: all 0.2s;
    position: relative;
}
/* Faint circle background for non-active dates */
.calendar-cell.inactive-date-cell {
    background-color: #f7fafc;
    color: #cbd5e0;
}
/* Highlighted active dates in green */
.calendar-cell.active-date-cell {
    background-color: #52db71;
    color: #1a202c;
    font-weight: 700;
    cursor: pointer;
}
.calendar-cell.active-date-cell:hover {
    background-color: #198754;
    color: #fff;
    box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
}
.calendar-cell.selected-date-cell {
    background-color: #198754 !important;
    color: #fff !important;
    box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
}
.calendar-cell.special-date-cell::after {
    content: '';
    position: absolute;
    bottom: 4px;
    width: 5px;
    height: 5px;
    background-color: #ff9f43;
    border-radius: 50%;
}
.calendar-cell.selected-date-cell.special-date-cell::after {
    background-color: #fff;
}
/* Tooltip styles */
.calendar-tooltip {
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: #2d3748;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
    z-index: 1005;
    pointer-events: none;
    opacity: 0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: opacity 0.15s ease-in-out;
}
.calendar-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 5px;
    border-style: solid;
    border-color: #2d3748 transparent transparent transparent;
}
.calendar-cell.active-date-cell:hover .calendar-tooltip {
    opacity: 1;
}
body.calendar-open {
    overflow: hidden !important;
}
body.calendar-open .mobile-bottom-cta {
    transform: translateY(100%) !important;
    pointer-events: none !important;
}
@media (max-width: 991.98px) {
    body.calendar-open .booking-widget-card-premium {
        display: none !important;
    }
}
.package-card-option {
    transition: all 0.2s ease-in-out;
    position: relative;
    border-width: 2px !important;
    cursor: pointer;
}
.package-card-option:hover {
    border-color: #28a745 !important;
}
</style>

<!-- SECTION 1: HERO AREA (Gallery) -->
<?php
$featured_img = !empty($trek['image']) ? $trek['image'] : 'assets/images/default-trek.jpg';
$gallery_items = [];
if (!empty($gallery)) {
    foreach ($gallery as $img) {
        $gallery_items[] = $img['image_path'];
    }
}
if (empty($gallery_items)) {
    $gallery_items[] = $featured_img;
}

// Prepare gallery data as JSON for JS auto-rotation
$gallery_json = json_encode(array_map(function($path) {
    return SITE_URL . '/' . $path;
}, $gallery_items));
$total_images = count($gallery_items);
?>
<!-- SECTION 1: PREMIUM MASONRY HERO GALLERY -->
<!-- Desktop Masonry Grid (hidden on mobile) -->
<div class="hero-masonry-gallery d-none d-md-block" data-gallery-images='<?php echo htmlspecialchars($gallery_json); ?>'>
    <div class="container">
        <div class="hero-masonry-grid hero-masonry-count-<?php echo min($total_images, 5); ?>">
            <?php
            $visible_count = min($total_images, 5);
            for ($i = 0; $i < $visible_count; $i++):
                $img_path = $gallery_items[$i];
                $slot_class = ($i === 0) ? 'hero-masonry-large' : 'hero-masonry-small hero-masonry-small-' . $i;
                $lazy = ($i < 5) ? '' : ' loading="lazy"';
            ?>
                <div class="<?php echo $slot_class; ?>">
                    <a href="<?php echo SITE_URL . '/' . $img_path; ?>" class="glightbox" data-gallery="trek-main-gallery">
                        <img src="<?php echo SITE_URL . '/' . $img_path; ?>" alt="<?php echo htmlspecialchars($trek['title']); ?> - Image <?php echo $i + 1; ?>"<?php echo $lazy; ?>>
                    </a>
                </div>
            <?php endfor; ?>
            <!-- Difficulty Badge Overlay -->
            <?php if (!empty($trek['difficulty'])): ?>
                <div class="hero-masonry-badges">
                    <span class="badge badge-difficulty <?php echo 'difficulty-' . strtolower($trek['difficulty']); ?>"><?php echo htmlspecialchars($trek['difficulty']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile Swipeable Gallery (hidden on desktop) -->
<div class="hero-mobile-gallery d-md-none">
    <div class="hero-mobile-gallery-track">
        <?php foreach ($gallery_items as $index => $img_path): ?>
            <div class="hero-mobile-slide">
                <a href="<?php echo SITE_URL . '/' . $img_path; ?>" class="glightbox" data-gallery="trek-main-gallery">
                    <img src="<?php echo SITE_URL . '/' . $img_path; ?>" alt="<?php echo htmlspecialchars($trek['title']); ?> - Image <?php echo $index + 1; ?>"<?php echo $index >= 3 ? ' loading="lazy"' : ''; ?>>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Difficulty Badge Overlay -->
    <?php if (!empty($trek['difficulty'])): ?>
        <div class="hero-masonry-badges">
            <span class="badge badge-difficulty <?php echo 'difficulty-' . strtolower($trek['difficulty']); ?>"><?php echo htmlspecialchars($trek['difficulty']); ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- Gallery Preview Thumbnails Strip -->
<?php if (count($gallery_items) > 1): ?>
<div class="gallery-thumbnails-wrapper container mb-3">
    <div class="gallery-thumbnails-strip">
        <?php foreach ($gallery_items as $index => $img_path): ?>
            <button class="gallery-thumb-btn<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>" data-src="<?php echo SITE_URL . '/' . $img_path; ?>" aria-label="View image <?php echo $index + 1; ?>">
                <img src="<?php echo SITE_URL . '/' . $img_path; ?>" alt="Thumbnail <?php echo $index + 1; ?>" loading="lazy">
            </button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


<!-- SECTION 2: TREK HEADER -->
<div class="trek-header-section container mt-4 mb-3">
    <?php if (!empty($trek['location'])): ?>
        <div class="trek-header-location text-muted fw-bold small text-uppercase mb-1">
            <i class="fas fa-map-marker-alt text-success me-1"></i> <?php echo htmlspecialchars($trek['location']); ?>
        </div>
    <?php endif; ?>
    <h1 class="trek-header-title display-6 fw-bold mb-2 text-dark"><?php echo htmlspecialchars($trek['title']); ?></h1>
    <div class="trek-header-rating d-flex align-items-center gap-2">
        <div class="rating-stars text-warning fs-6">
            <?php echo render_rating_stars(round($avg_rating)); ?>
        </div>
        <span class="rating-value fw-bold text-dark fs-5 mt-0.5"><?php echo $avg_rating; ?></span>
        <span class="review-count text-muted fs-6 mt-0.5">(<?php echo count($reviews); ?> Reviews)</span>
    </div>
</div>

<!-- SECTION 3: QUICK FACTS BAR -->
<?php
$quick_facts_items = [
    [
        'icon' => 'far fa-clock',
        'label' => 'Duration',
        'value' => $trek['duration']
    ]
];

if (!empty($trek['difficulty'])) {
    $quick_facts_items[] = [
        'icon' => 'fas fa-hiking',
        'label' => 'Difficulty',
        'value' => $trek['difficulty']
    ];
}

if (!empty($trek['trek_distance']) && $trek['trek_distance'] > 0) {
    $quick_facts_items[] = [
        'icon' => 'fas fa-route',
        'label' => 'Distance',
        'value' => $trek['trek_distance'] . ' km'
    ];
}

if (!empty($trek['best_season'])) {
    $quick_facts_items[] = [
        'icon' => 'fas fa-cloud-sun',
        'label' => 'Best Season',
        'value' => $trek['best_season']
    ];
}
?>
<div class="container mb-4">
    <div class="details-quick-facts-grid">
        <?php foreach ($quick_facts_items as $item): ?>
            <div class="details-quick-fact-card">
                <div class="details-quick-fact-icon"><i class="<?php echo htmlspecialchars($item['icon']); ?>"></i></div>
                <div class="details-quick-fact-info">
                    <span class="details-quick-fact-label"><?php echo htmlspecialchars($item['label']); ?></span>
                    <h5 class="details-quick-fact-value"><?php echo htmlspecialchars($item['value']); ?></h5>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Mobile Booking Form Placeholder -->
<div id="mobile-booking-placeholder" class="d-lg-none container mb-4"></div>

<!-- Experience Highlights Strip -->
<?php require_once __DIR__ . '/../includes/experience-strip.php'; ?>

<!-- Dynamic Trek Highlights Section -->
<?php if (!empty($trek_highlights)): ?>
<div class="container mb-5 mt-4">
    <h4 class="fw-bold text-success mb-4 text-center"><i class="fas fa-star me-2"></i>Trek Highlights</h4>
    <div class="trek-highlights-scroll-wrapper">
        <div class="trek-highlights-grid">
            <?php foreach ($trek_highlights as $hl): ?>
                <div class="highlight-card">
                    <div class="highlight-icon-wrapper">
                        <i class="<?php echo htmlspecialchars($hl['icon'] ?: 'fas fa-check-circle'); ?>"></i>
                    </div>
                    <div class="highlight-text"><?php echo htmlspecialchars($hl['highlight']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>



<!-- Main Details -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4 row-booking-sticky">
            <!-- Left Side Details -->
            <div class="col-lg-8">

                <!-- 4. Overview -->
                <div class="mb-5" id="section-overview">
                    <h4 class="fw-bold text-success mb-3"><i class="fas fa-info-circle me-2"></i>Overview</h4>
                    <p class="text-muted leading-relaxed"><?php echo nl2br(htmlspecialchars($trek['description'])); ?></p>
                </div>

                <!-- Important Notes Section -->
                <?php if (!empty($trek_notes)): ?>
                <div class="mb-3" id="section-important-notes">
                    <div class="important-notes-banner p-4 rounded-3 border-start border-warning border-4 shadow-sm" style="background-color: #FFFDF0;">
                        <h5 class="fw-bold text-warning-emphasis mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h5>
                        <div class="important-notes-content d-flex flex-column gap-2 text-muted leading-relaxed">
                            <?php foreach ($trek_notes as $n): ?>
                                <div class="important-note-item">
                                    <?php echo format_rich_text($n['note']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Important Notes Section -->
                <?php if (!empty($trek_additional_notes)): ?>
                <div class="mb-5" id="section-additional-notes">
                    <div class="important-notes-banner p-4 rounded-3 border-start border-warning border-4 shadow-sm" style="background-color: #FFFDF0;">
                        <h5 class="fw-bold text-warning-emphasis mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Additional Important Notes</h5>
                        <ul class="important-notes-content mb-0 d-flex flex-column gap-2 text-muted leading-relaxed" style="padding-left: 1rem;">
                            <?php foreach ($trek_additional_notes as $n): ?>
                                <li class="important-note-item">
                                    <?php echo format_rich_text($n['note']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 5. Itinerary -->
                <div class="mb-5" id="section-itinerary">
                    <h4 class="fw-bold text-success mb-3"><i class="fas fa-list-ol me-2"></i>Detailed Itinerary</h4>
                    <div class="itinerary-timeline">
                        <?php if (!empty($itinerary)): ?>
                            <?php foreach ($itinerary as $index => $day): ?>
                                <div class="itinerary-day-node">
                                    <div class="itinerary-day-header" role="button" aria-expanded="false" tabindex="0">
                                        <h5 class="fw-bold text-dark mb-0">
                                            <i class="fas fa-chevron-right itinerary-chevron me-2"></i><span class="itinerary-day-label">Day <?php echo $index; ?></span>: <?php echo htmlspecialchars($day['title'] ?? 'Overview'); ?>
                                        </h5>
                                    </div>
                                    <div class="itinerary-day-content">
                                        <?php if (!empty($day['desc'])): ?>
                                            <ul class="itinerary-points">
                                                <?php 
                                                $desc_lines = preg_split('/\r\n|\r|\n/', $day['desc']);
                                                foreach ($desc_lines as $line):
                                                    $line = trim($line);
                                                    if ($line === '') continue;
                                                    // Strip leading bullets/dashes/asterisks/checkmarks if present to avoid duplicates
                                                    $line = preg_replace('/^\s*[-*•✓]\s*/u', '', $line);
                                                    if ($line === '') continue;
                                                ?>
                                                    <li><?php echo htmlspecialchars($line); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Detailed itinerary will be uploaded shortly.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Trek Videos -->
                <?php if (!empty($videos)): ?>
                <div class="mb-5">
                    <h4 class="fw-bold text-success mb-3"><i class="fab fa-youtube me-2 text-danger"></i>Trek Videos</h4>
                    <div class="row g-3">
                        <?php foreach ($videos as $vid): 
                            $vid_id = '';
                            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $vid['youtube_url'], $match)) {
                                $vid_id = $match[1];
                            }
                            if ($vid_id):
                        ?>
                        <div class="col-md-6">
                            <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
                                <iframe src="https://www.youtube.com/embed/<?php echo $vid_id; ?>" title="<?php echo htmlspecialchars($vid['title'] ?? ''); ?>" allowfullscreen></iframe>
                            </div>
                            <?php if (!empty($vid['title'])): ?>
                                <p class="text-muted mt-2 small fw-bold text-center"><?php echo htmlspecialchars($vid['title']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 8: INCLUSIONS & EXCLUSIONS -->
                <div class="mb-5" id="section-inclusions-exclusions">
                    <h4 class="fw-bold text-success mb-4"><i class="fas fa-clipboard-list me-2"></i>Inclusions & Exclusions</h4>
                    
                    <?php if ($transport_enabled): ?>
                        <div class="package-inclusions-exclusions mb-5">
                            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-bus text-success me-2"></i>WITH TRANSPORT</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="inclusions-card-premium h-100">
                                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-2"></i>What's Included</h6>
                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                            <?php 
                                            $inc_items = !empty($trek['with_transport_inclusions']) ? explode("\n", $trek['with_transport_inclusions']) : [];
                                            if (empty($inc_items) && !empty($trek['with_transport_inclusions'])) {
                                                $inc_items = explode(",", $trek['with_transport_inclusions']);
                                            }
                                            if (!empty($inc_items)): 
                                                foreach ($inc_items as $item):
                                                    $clean = trim($item, " \t\n\r\0\x0B-*•✓");
                                                    if (empty($clean)) continue;
                                            ?>
                                                <li class="d-flex align-items-start text-muted">
                                                    <i class="fas fa-check text-success me-2 mt-1 small"></i>
                                                    <span><?php echo htmlspecialchars($clean); ?></span>
                                                </li>
                                            <?php 
                                                endforeach; 
                                            else: 
                                            ?>
                                                <li class="text-muted">Inclusions details will be loaded shortly.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="exclusions-card-premium h-100">
                                        <h6 class="fw-bold text-danger mb-3"><i class="fas fa-times-circle me-2"></i>What's Excluded</h6>
                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                            <?php 
                                            $exc_items = !empty($trek['with_transport_exclusions']) ? explode("\n", $trek['with_transport_exclusions']) : [];
                                            if (empty($exc_items) && !empty($trek['with_transport_exclusions'])) {
                                                $exc_items = explode(",", $trek['with_transport_exclusions']);
                                            }
                                            if (!empty($exc_items)): 
                                                foreach ($exc_items as $item):
                                                    $clean = trim($item, " \t\n\r\0\x0B-*•✕x");
                                                    if (empty($clean)) continue;
                                            ?>
                                                <li class="d-flex align-items-start text-muted">
                                                    <i class="fas fa-times text-danger me-2 mt-1 small"></i>
                                                    <span><?php echo htmlspecialchars($clean); ?></span>
                                                </li>
                                            <?php 
                                                endforeach; 
                                            else: 
                                            ?>
                                                <li class="text-muted">Exclusions details will be loaded shortly.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($own_transport_enabled): ?>
                        <div class="package-inclusions-exclusions">
                            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-car text-success me-2"></i>WITHOUT TRANSPORT</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="inclusions-card-premium h-100">
                                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-2"></i>What's Included</h6>
                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                            <?php 
                                            $inc_items = !empty($trek['own_transport_inclusions']) ? explode("\n", $trek['own_transport_inclusions']) : [];
                                            if (empty($inc_items) && !empty($trek['own_transport_inclusions'])) {
                                                $inc_items = explode(",", $trek['own_transport_inclusions']);
                                            }
                                            if (!empty($inc_items)): 
                                                foreach ($inc_items as $item):
                                                    $clean = trim($item, " \t\n\r\0\x0B-*•✓");
                                                    if (empty($clean)) continue;
                                            ?>
                                                <li class="d-flex align-items-start text-muted">
                                                    <i class="fas fa-check text-success me-2 mt-1 small"></i>
                                                    <span><?php echo htmlspecialchars($clean); ?></span>
                                                </li>
                                            <?php 
                                                endforeach; 
                                            else: 
                                            ?>
                                                <li class="text-muted">Inclusions details will be loaded shortly.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="exclusions-card-premium h-100">
                                        <h6 class="fw-bold text-danger mb-3"><i class="fas fa-times-circle me-2"></i>What's Excluded</h6>
                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                            <?php 
                                            $exc_items = !empty($trek['own_transport_exclusions']) ? explode("\n", $trek['own_transport_exclusions']) : [];
                                            if (empty($exc_items) && !empty($trek['own_transport_exclusions'])) {
                                                $exc_items = explode(",", $trek['own_transport_exclusions']);
                                            }
                                            if (!empty($exc_items)): 
                                                foreach ($exc_items as $item):
                                                    $clean = trim($item, " \t\n\r\0\x0B-*•✕x");
                                                    if (empty($clean)) continue;
                                            ?>
                                                <li class="d-flex align-items-start text-muted">
                                                    <i class="fas fa-times text-danger me-2 mt-1 small"></i>
                                                    <span><?php echo htmlspecialchars($clean); ?></span>
                                                </li>
                                            <?php 
                                                endforeach; 
                                            else: 
                                            ?>
                                                <li class="text-muted">Exclusions details will be loaded shortly.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 8. Things to Carry -->
                <div class="mb-5" id="section-carry">
                    <h4 class="fw-bold text-warning mb-3"><i class="fas fa-backpack me-2"></i>Things to Carry</h4>
                    <?php 
                    $carry_items = !empty($trek['things_to_carry']) ? explode("\n", $trek['things_to_carry']) : [];
                    $clean_carry = [];
                    foreach ($carry_items as $item) {
                        $clean = trim($item, " \t\n\r\0\x0B-*•✓");
                        if (!empty($clean)) {
                            $clean_carry[] = $clean;
                        }
                    }
                    if (!empty($clean_carry)): 
                    ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($clean_carry as $item): ?>
                                <div class="carry-chip">
                                    <i class="fas fa-check text-warning"></i>
                                    <span><?php echo htmlspecialchars($item); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 bg-light p-4 rounded-3">
                            <p class="text-muted leading-relaxed mb-0">Things to carry details will be shared closer to the trek date.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 9. Pickup Points -->
                <div class="mb-5" id="section-pickup">
                    <h4 class="fw-bold text-success mb-3"><i class="fas fa-map-marker-alt me-2"></i>Pickup Points</h4>
                    <?php if (!empty($trek['pickup_points_txt'])): ?>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($trek['pickup_points_txt']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pickup_points)): ?>
                        <div class="pickup-metro-timeline">
                            <?php foreach ($pickup_points as $point): ?>
                                <div class="pickup-timeline-item">
                                    <div class="pickup-timeline-node">
                                        <i class="fas fa-bus-alt"></i>
                                    </div>
                                    <div class="pickup-timeline-content">
                                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($point['location']); ?></h6>
                                        <span class="badge pickup-time-badge mb-1"><?php echo date('h:i A', strtotime($point['time'])); ?></span>
                                        <?php if (!empty($point['landmark'])): ?>
                                            <div class="text-muted small mt-1">
                                                <strong>Landmark:</strong> <?php echo htmlspecialchars($point['landmark']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 bg-light p-4 rounded-3">
                            <p class="text-muted mb-0">Pickup points will be coordinated closer to the trek date.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dynamic Trek FAQs Section -->
                <?php if (!empty($trek_faqs)): ?>
                <div class="mb-5" id="section-faqs">
                    <h4 class="fw-bold text-success mb-4"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h4>
                    <div class="accordion" id="trekFaqAccordion">
                        <?php foreach ($trek_faqs as $index => $faq): ?>
                            <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm bg-white">
                                <h2 class="accordion-header" id="headingFaq<?php echo $index; ?>">
                                    <button class="accordion-button collapsed fw-bold py-3 text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq<?php echo $index; ?>" aria-expanded="false" aria-controls="collapseFaq<?php echo $index; ?>" style="min-height: 48px; font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($faq['question']); ?>
                                    </button>
                                </h2>
                                <div id="collapseFaq<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="headingFaq<?php echo $index; ?>" data-bs-parent="#trekFaqAccordion">
                                    <div class="accordion-body text-muted leading-relaxed border-top bg-light">
                                        <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>
                </div>
                <?php endif; ?>

                <!-- 10. Reviews -->
                <div class="mb-5" id="section-reviews">
                    <h4 class="fw-bold text-success mb-4"><i class="far fa-star me-2"></i>Reviews (<?php echo count($reviews); ?>)</h4>
                    
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-slider-container">
                            <div class="reviews-grid-premium">
                                <?php foreach ($reviews as $rev): ?>
                                    <div class="review-card-premium">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="review-avatar"><?php echo get_initials($rev['name']); ?></div>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($rev['name']); ?></h6>
                                                <div class="rating-stars text-warning" style="font-size: 0.8rem;">
                                                    <?php echo render_rating_stars($rev['rating']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-body flex-grow-1">
                                            <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                                        </div>
                                        <div class="review-footer mt-3 pt-2 border-top">
                                            <small class="text-muted"><?php echo format_date($rev['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No reviews approved yet. Be the first to share your experience after trekking!</p>
                    <?php endif; ?>
                    
                    <!-- Flash Message Feedback inside Reviews block -->
                    <div class="mt-3">
                        <?php echo get_flash_message(); ?>
                    </div>

                    <!-- Verified review submission block -->
                    <?php if ($is_verified_trekker): ?>
                        <div class="card border-0 shadow-sm p-4 mt-4 bg-light rounded-3">
                            <h5 class="fw-bold text-success mb-3"><i class="fas fa-pen-fancy me-2"></i>Write a Review</h5>
                            <p class="text-muted small">You are marked as a verified trekker for this trip. Your review will help future trekkers!</p>
                            <form action="?slug=<?php echo $slug; ?>&action=add_review" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small">Rating *</label>
                                    <select name="rating" class="form-select" required>
                                        <option value="5">5 Stars - Excellent</option>
                                        <option value="4">4 Stars - Very Good</option>
                                        <option value="3">3 Stars - Good</option>
                                        <option value="2">2 Stars - Fair</option>
                                        <option value="1">1 Star - Poor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small">Your Review *</label>
                                    <textarea name="comment" class="form-control" rows="4" required placeholder="Share your experience on this trail (homestay, guide, challenges)..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-3">Submit Review</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 11. Related Treks -->
                <div class="mb-5" id="section-related">
                    <h4 class="fw-bold text-success mb-4"><i class="fas fa-hiking me-2"></i>Related Treks</h4>
                    <?php if (!empty($related_treks)): ?>
                        <div class="scroll-row-container">
                             <?php 
                             $backup_trek = $trek;
                             foreach ($related_treks as $rel): 
                                 $trek = $rel; // Pass the current iteration's data
                             ?>
                                <div class="scroll-row-card">
                                    <?php include __DIR__ . '/../includes/trek-card.php'; ?>
                                </div>
                            <?php 
                             endforeach; 
                             $trek = $backup_trek; // Restore main trek
                             ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No related treks available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side Sticky Booking Sidebar -->
            <div class="col-lg-4 booking-sidebar-col">
                <div class="booking-widget-card-premium booking-sidebar" id="booking-form-anchor">
                    <div class="text-center mb-2.5 pb-2 border-bottom">
                        <span class="text-muted text-uppercase small d-block" style="font-size: 0.75rem;">Starting from</span>
                        <div class="d-flex align-items-baseline justify-content-center gap-2">
                            <h3 class="fw-extrabold text-success mb-0">
                                <span id="widget_active_price"><?php echo format_price($active_price); ?></span>
                                <span class="fs-6 text-muted font-normal">/ trekker</span>
                            </h3>
                            <?php 
                            $default_orig = $transport_enabled ? $with_transport_price : $without_transport_price;
                            $default_offer = $transport_enabled ? $with_transport_offer_price : $without_transport_offer_price;
                            if ($default_offer > 0 && $default_orig > 0): 
                            ?>
                                <del class="text-danger small"><?php echo format_price($default_orig); ?></del>
                                <span class="badge bg-warning text-dark">Save <?php echo round((($default_orig - $default_offer) / $default_orig) * 100); ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <div id="weekend_trip_form_section">
                        <form action="<?php echo SITE_URL; ?>/booking/create.php" method="POST">
                            <input type="hidden" name="trek_id" value="<?php echo $trek_id; ?>">
                            <input type="hidden" id="trek_price" value="<?php echo $active_price; ?>">
                            <input type="hidden" id="trek_base_price" value="<?php echo $active_price; ?>">
                            
                            <!-- Base Package Prices for JS -->
                            <input type="hidden" id="with_transport_base" value="<?php echo $with_transport_offer_price > 0 ? $with_transport_offer_price : $with_transport_price; ?>">
                            <input type="hidden" id="without_transport_base" value="<?php echo $without_transport_offer_price > 0 ? $without_transport_offer_price : $without_transport_price; ?>">

                            <!-- Package Selection -->
                            <?php if ($transport_enabled && $own_transport_enabled): ?>
                                <div class="mb-2.5" id="package_selection_container">
                                    <label class="form-label fw-bold small text-muted mb-1">Select Package *</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="package-card-option w-100 p-2 rounded border text-center cursor-pointer d-block" id="pkg_card_with_transport" for="pkg_type_with_transport" style="<?php echo $default_package === 'with_transport' ? 'border: 2px solid #28a745; background-color: #f4fff6;' : 'border: 1px solid #ced4da; opacity: 0.85;'; ?>">
                                                <input type="radio" name="package_type" id="pkg_type_with_transport" value="with_transport" <?php echo $default_package === 'with_transport' ? 'checked' : ''; ?> class="visually-hidden">
                                                <span class="d-block fw-bold text-dark" style="font-size: 0.82rem;">With Transport</span>
                                                <span class="d-block text-success fw-bold" style="font-size: 0.92rem; margin-top: 1px;"><?php echo format_price($with_transport_offer_price > 0 ? $with_transport_offer_price : $with_transport_price); ?></span>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <label class="package-card-option w-100 p-2 rounded border text-center cursor-pointer d-block" id="pkg_card_without_transport" for="pkg_type_without_transport" style="<?php echo $default_package === 'without_transport' ? 'border: 2px solid #28a745; background-color: #f4fff6;' : 'border: 1px solid #ced4da; opacity: 0.85;'; ?>">
                                                <input type="radio" name="package_type" id="pkg_type_without_transport" value="without_transport" <?php echo $default_package === 'without_transport' ? 'checked' : ''; ?> class="visually-hidden">
                                                <span class="d-block fw-bold text-dark" style="font-size: 0.82rem;">Own Transport</span>
                                                <span class="d-block text-success fw-bold" style="font-size: 0.92rem; margin-top: 1px;"><?php echo format_price($without_transport_offer_price > 0 ? $without_transport_offer_price : $without_transport_price); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="package_type" id="package_type_hidden" value="<?php echo $default_package; ?>">
                            <?php endif; ?>

                            <!-- Date Selector -->
                            <div class="mb-2.5 position-relative">
                                <label class="form-label fw-bold small text-muted mb-1">Select Date *</label>
                                <div class="position-relative">
                                    <input type="text" id="calendar_trigger" class="form-control bg-white cursor-pointer" readonly placeholder="Select a date" style="padding-right: 40px;" <?php echo empty($trek_dates) ? 'disabled' : ''; ?> required>
                                    <i class="far fa-calendar-alt position-absolute end-0 top-50 translate-middle-y me-3 text-success"></i>
                                </div>
                                <select name="trek_date_id" id="trek_date_id" class="form-select d-none" required>
                                    <option value="">-- Choose Date --</option>
                                    <?php foreach ($trek_dates as $date): 
                                        $batch_price = (!empty($date['price']) && $date['price'] > 0) ? $date['price'] : $active_price;
                                        $option_label = format_date($date['start_date']);
                                        if (!empty($date['label'])) {
                                            $option_label .= ' - ' . $date['label'];
                                        }
                                    ?>
                                        <option value="<?php echo $date['id']; ?>" data-price="<?php echo $batch_price; ?>">
                                            <?php echo htmlspecialchars($option_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="calendar_picker_container" class="calendar-picker-dropdown shadow border rounded-3 bg-white p-3 d-none"></div>
                                <?php if (empty($trek_dates)): ?>
                                    <small class="text-danger d-block mt-1">No schedules available currently.</small>
                                  <?php endif; ?>
                            </div>

                            <!-- Trekkers Count -->
                            <div class="mb-2.5">
                                <label class="form-label fw-bold small text-muted mb-1">Number of Trekkers *</label>
                                <input type="number" name="num_trekkers" id="num_trekkers" class="form-control" value="1" min="1" max="20" required>
                            </div>

                            <!-- Pickup Point -->
                            <div class="mb-2.5 <?php echo $default_package === 'without_transport' ? 'd-none' : ''; ?>" id="pickup_location_wrapper">
                                <label class="form-label fw-bold small text-muted mb-1">Pickup Location *</label>
                                <select name="pickup_point_id" id="pickup_point_id" class="form-select" required>
                                    <option value="">-- Select Pickup Point --</option>
                                    <?php foreach ($pickup_points as $point): ?>
                                        <option value="<?php echo $point['id']; ?>" data-date-id="<?php echo $point['trek_date_id'] ?: ''; ?>">
                                            <?php echo htmlspecialchars($point['location']); ?> (<?php echo date('h:i A', strtotime($point['time'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Own Transport Selected Badge -->
                            <div id="own_transport_info_wrapper" class="<?php echo $default_package === 'without_transport' ? 'mb-2' : 'd-none mb-2'; ?>">
                                <div class="alert alert-info py-1.5 px-2.5 mb-1.5 rounded border-info d-flex align-items-center" style="background-color: #e3f2fd; font-size: 0.8rem; color: #0d47a1; border-width: 1px !important;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span><strong>Own Transport:</strong> Pickup point not required.</span>
                                </div>
                                <?php if (!empty($own_transport_note)): ?>
                                    <div class="p-2 rounded border border-warning shadow-sm" style="background-color: #FFFDF0; font-size: 0.78rem; max-height: 80px; overflow-y: auto;">
                                        <div class="fw-bold text-warning-emphasis mb-0.5"><i class="fas fa-exclamation-triangle me-1"></i>Instructions:</div>
                                        <div class="text-muted leading-tight"><?php echo nl2br(htmlspecialchars($own_transport_note)); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- With Transport Booking Notes -->
                            <div id="with_transport_info_wrapper" class="<?php echo $default_package === 'with_transport' ? 'mb-2' : 'd-none mb-2'; ?>">
                                <?php if (!empty($with_transport_note)): ?>
                                    <div class="p-2 rounded border border-warning shadow-sm" style="background-color: #FFFDF0; font-size: 0.78rem; max-height: 80px; overflow-y: auto;">
                                        <div class="fw-bold text-warning-emphasis mb-0.5"><i class="fas fa-exclamation-triangle me-1"></i>Booking Notes:</div>
                                        <div class="text-muted leading-tight"><?php echo nl2br(htmlspecialchars($with_transport_note)); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Calculation summary -->
                            <div class="border-top pt-2.5 mb-3">
                                <div class="d-flex justify-content-between text-muted small mb-1">
                                    <span>Subtotal:</span>
                                    <span id="summary_subtotal" class="fw-semibold"><?php echo format_price($active_price); ?></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold text-dark fs-5">
                                    <span>Payable Amount:</span>
                                    <span id="summary_payable" class="text-success"><?php echo format_price($active_price); ?></span>
                                </div>
                            </div>

                            <!-- BOOK NOW CTA BUTTON -->
                            <button type="submit" id="btn_submit_booking" class="btn btn-warning btn-lg w-100 py-3 fw-bold text-white text-uppercase d-flex align-items-center justify-content-center gap-2 shadow-lg <?php echo empty($trek_dates) ? 'disabled' : ''; ?>" style="background: linear-gradient(135deg, #FF6B00 0%, #E65100 100%); border: none; font-size: 1.15rem; letter-spacing: 0.5px; border-radius: 12px; box-shadow: 0 6px 18px rgba(230, 81, 0, 0.45);">
                                <i class="fas fa-bolt text-white"></i>
                                <span>Book Now</span>
                                <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Fixed Bottom Booking CTA Bar (Fixed across all screens) -->
<div class="fixed-bottom-booking-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-block">
                <div class="fw-bold text-dark text-truncate" style="max-width: 380px; font-size: 1.05rem;">
                    <?php echo htmlspecialchars($trek['title']); ?>
                </div>
                <div class="small text-muted d-flex align-items-center gap-2">
                    <span class="text-success"><i class="fas fa-shield-alt me-1"></i>Verified Lead Guides</span>
                    <span>•</span>
                    <span class="text-primary"><i class="fas fa-bolt me-1"></i>Instant Booking</span>
                </div>
            </div>
            <div class="price-display">
                <span class="small d-block text-muted" style="font-size: 0.72rem; line-height: 1;">Starting from</span>
                <span id="mobile_active_price" class="text-success fw-bold fs-4"><?php echo format_price($active_price); ?></span>
                <span class="small text-muted d-none d-sm-inline">/ trekker</span>
            </div>
        </div>
        <div>
            <a href="#booking-form-anchor" class="btn btn-warning btn-lg px-4 py-2.5 fw-bold text-white text-uppercase rounded-pill shadow-sm d-flex align-items-center gap-2" id="mobile-book-btn" style="background: linear-gradient(135deg, #FF6B00 0%, #E65100 100%); border: none; font-size: 1.05rem; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(230, 81, 0, 0.4);">
                <i class="fas fa-bolt text-white"></i>
                <span>Book Now</span>
                <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Structured Data (JSON-LD) for Trek Product SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?php echo htmlspecialchars($trek['title']); ?>",
  "description": "<?php echo htmlspecialchars($meta_desc); ?>",
  "image": "<?php echo !empty($trek['image']) ? SITE_URL . '/' . $trek['image'] : SITE_URL . '/assets/images/hero-bg.jpg'; ?>",
  "offers": {
    "@type": "Offer",
    "price": "<?php echo $active_price; ?>",
    "priceCurrency": "INR",
    "availability": "https://schema.org/InStock",
    "url": "<?php echo htmlspecialchars($canonical_url); ?>"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $avg_rating; ?>",
    "reviewCount": "<?php echo max(1, count($reviews)); ?>"
  }
}
</script>


<script>
// Ensure mobile gallery carousel NEVER auto-slides
document.addEventListener('DOMContentLoaded', function () {
    var galleryEl = document.getElementById('mobileTrekGallery');
    if (galleryEl) {
        var carousel = bootstrap.Carousel.getOrCreateInstance(galleryEl, {
            interval: false,
            ride: false,
            wrap: true,
            touch: true
        });
        carousel.pause();
    }

    // Single booking form setup
    $('#weekend_trip_form_section form').find('input, select').prop('disabled', false);

    // Relocate Booking Form based on viewport width
    function handleBookingFormRelocation() {
        const bookingForm = document.getElementById('booking-form-anchor');
        const desktopParent = document.querySelector('.booking-sidebar-col');
        const mobileParent = document.getElementById('mobile-booking-placeholder');
        
        if (!bookingForm || !desktopParent || !mobileParent) return;
        
        if (window.innerWidth < 992) {
            if (mobileParent.firstElementChild !== bookingForm) {
                mobileParent.appendChild(bookingForm);
            }
        } else {
            if (desktopParent.firstElementChild !== bookingForm) {
                desktopParent.appendChild(bookingForm);
            }
        }
    }
    
    // Listen to resize and initialize immediately
    window.addEventListener('resize', handleBookingFormRelocation);
    handleBookingFormRelocation();

    // Mobile CTA Book Now click handler to scroll to form
    const mobileBookBtn = document.getElementById('mobile-book-btn');
    if (mobileBookBtn) {
        mobileBookBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById('booking-form-anchor');
            if (target) {
                const headerOffset = 90;
                const elementPosition = target.getBoundingClientRect().top + window.scrollY;
                const offsetPosition = elementPosition - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    }
});
</script>

<?php
$dates_json = [];
foreach ($trek_dates as $d) {
    $batch_price = (!empty($d['price']) && $d['price'] > 0) ? $d['price'] : $active_price;
    $dates_json[$d['start_date']] = [
        'id' => $d['id'],
        'price' => (float)$batch_price,
        'label' => $d['label'] ? $d['label'] : ''
    ];
}
?>
<script>
(function() {
    const activeDatesMap = <?php echo json_encode($dates_json); ?>;
    const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const monthNamesFull = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    let currentYear, currentMonth;
    const activeDatesKeys = Object.keys(activeDatesMap);
    
    if (activeDatesKeys.length > 0) {
        activeDatesKeys.sort();
        const firstDateParts = activeDatesKeys[0].split('-');
        currentYear = parseInt(firstDateParts[0]);
        currentMonth = parseInt(firstDateParts[1]) - 1;
    } else {
        const today = new Date();
        currentYear = today.getFullYear();
        currentMonth = today.getMonth();
    }
    
    function renderCalendar(year, month) {
        const container = document.getElementById('calendar_picker_container');
        if (!container) return;
        
        container.innerHTML = '';
        
        // Header row
        const header = document.createElement('div');
        header.className = 'calendar-header';
        
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'calendar-nav-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            changeMonth(-1);
        });
        
        const title = document.createElement('div');
        title.className = 'calendar-header-title';
        title.textContent = monthNamesFull[month] + ' ' + year;
        
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'calendar-nav-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            changeMonth(1);
        });
        
        header.appendChild(prevBtn);
        header.appendChild(title);
        header.appendChild(nextBtn);
        container.appendChild(header);
        
        // Days grid
        const grid = document.createElement('div');
        grid.className = 'calendar-grid';
        
        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        weekdays.forEach(day => {
            const dCell = document.createElement('div');
            dCell.className = 'calendar-grid-header';
            dCell.textContent = day;
            grid.appendChild(dCell);
        });
        
        const firstDayIndex = new Date(year, month, 1).getDay();
        const numDays = new Date(year, month + 1, 0).getDate();
        
        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            grid.appendChild(emptyCell);
        }
        
        const currentSelectedDateId = document.getElementById('trek_date_id').value;
        
        for (let d = 1; d <= numDays; d++) {
            const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-cell';
            dayCell.textContent = d;
            
            if (activeDatesMap[dateStr]) {
                const dateInfo = activeDatesMap[dateStr];
                dayCell.classList.add('active-date-cell');
                
                if (String(dateInfo.id) === String(currentSelectedDateId)) {
                    dayCell.classList.add('selected-date-cell');
                }
                
                if (dateInfo.label) {
                    dayCell.classList.add('special-date-cell');
                    const tooltip = document.createElement('div');
                    tooltip.className = 'calendar-tooltip';
                    tooltip.textContent = dateInfo.label;
                    dayCell.appendChild(tooltip);
                }
                
                dayCell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectDate(dateStr, dateInfo);
                });
            } else {
                dayCell.classList.add('inactive-date-cell');
            }
            
            grid.appendChild(dayCell);
        }
        
        container.appendChild(grid);
    }
    
    function changeMonth(direction) {
        currentMonth += direction;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentYear, currentMonth);
    }
    
    function selectDate(dateStr, dateInfo) {
        const hiddenSelect = document.getElementById('trek_date_id');
        const triggerInput = document.getElementById('calendar_trigger');
        
        hiddenSelect.value = dateInfo.id;
        
        const parts = dateStr.split('-');
        const day = parseInt(parts[2]);
        const monthIndex = parseInt(parts[1]) - 1;
        const year = parts[0];
        let displayVal = day + ' ' + monthNamesShort[monthIndex] + ' ' + year;
        if (dateInfo.label) {
            displayVal += ' - ' + dateInfo.label;
        }
        
        triggerInput.value = displayVal;
        
        // Trigger select change event for legacy validation and calculations
        $(hiddenSelect).trigger('change');
        
        closeCalendar();
    }
    
    function openCalendar() {
        const container = document.getElementById('calendar_picker_container');
        const trigger = document.getElementById('calendar_trigger');
        if (!container) return;
        
        if (container.classList.contains('d-none')) {
            renderCalendar(currentYear, currentMonth);
            container.classList.remove('d-none');
            trigger.classList.add('active');
            
            if (window.innerWidth < 992) {
                document.body.classList.add('calendar-open');
                // Move calendar container to body to prevent stacking context/clipping issues
                document.body.appendChild(container);
                
                let backdrop = document.querySelector('.calendar-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'calendar-backdrop';
                    document.body.appendChild(backdrop);
                    backdrop.addEventListener('click', closeCalendar);
                }
                const widget = document.querySelector('.booking-widget-card-premium');
                if (widget) {
                    widget.style.zIndex = '1050';
                }
            }
        }
    }
    
    function closeCalendar() {
        const container = document.getElementById('calendar_picker_container');
        const trigger = document.getElementById('calendar_trigger');
        
        if (container) {
            container.classList.add('d-none');
            // Move container back to its original location in the form
            const originalParent = document.getElementById('trek_date_id').parentElement;
            if (originalParent && container.parentElement !== originalParent) {
                originalParent.appendChild(container);
            }
        }
        if (trigger) {
            trigger.classList.remove('active');
        }
        
        document.body.classList.remove('calendar-open');
        
        const backdrop = document.querySelector('.calendar-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        const widget = document.querySelector('.booking-widget-card-premium');
        if (widget) {
            widget.style.zIndex = '';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('calendar_trigger');
        if (trigger) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                if (document.getElementById('calendar_picker_container').classList.contains('d-none')) {
                    openCalendar();
                } else {
                    closeCalendar();
                }
            });
        }
        
        document.addEventListener('click', function(e) {
            const container = document.getElementById('calendar_picker_container');
            const trigger = document.getElementById('calendar_trigger');
            if (container && !container.classList.contains('d-none')) {
                if (!container.contains(e.target) && e.target !== trigger && !trigger.contains(e.target)) {
                    closeCalendar();
                }
            }
        });
        
        // Support programmatic change propagation back to calendar trigger text
        $('#trek_date_id').on('change', function() {
            const selectedVal = $(this).val();
            if (!selectedVal) {
                $('#calendar_trigger').val('');
                return;
            }
            
            // Sync input trigger label if selected via other methods (e.g. initial loads/resets)
            let matchedDateStr = null;
            let matchedInfo = null;
            for (const key in activeDatesMap) {
                if (String(activeDatesMap[key].id) === String(selectedVal)) {
                    matchedDateStr = key;
                    matchedInfo = activeDatesMap[key];
                    break;
                }
            }
            if (matchedDateStr) {
                const parts = matchedDateStr.split('-');
                const day = parseInt(parts[2]);
                const monthIndex = parseInt(parts[1]) - 1;
                const year = parts[0];
                let val = day + ' ' + monthNamesShort[monthIndex] + ' ' + year;
                if (matchedInfo.label) {
                    val += ' - ' + matchedInfo.label;
                }
                $('#calendar_trigger').val(val);
            }
        });
        
        // Initial setup
        const initialSelected = $('#trek_date_id').val();
        if (initialSelected) {
            $('#trek_date_id').trigger('change');
        }
    });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headers = document.querySelectorAll('.itinerary-day-header');
    
    headers.forEach(header => {
        const content = header.nextElementSibling;
        
        header.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                // Animate collapse by setting from scrollHeight to 0px
                content.style.maxHeight = content.scrollHeight + 'px';
                content.offsetHeight; // force reflow
                
                this.setAttribute('aria-expanded', 'false');
                content.style.maxHeight = '0px';
                content.style.opacity = '0';
            } else {
                this.setAttribute('aria-expanded', 'true');
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.opacity = '1';
            }
        });

        // Reset max-height to none after expansion for fluid responsive resizing
        content.addEventListener('transitionend', function() {
            if (header.getAttribute('aria-expanded') === 'true') {
                content.style.maxHeight = 'none';
            }
        });

        // Accessible keyboard trigger (Enter/Space key)
        header.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
});
</script>

<script>
/* ============================================================
   Masonry Gallery — Auto-Rotation for 6+ Images
   ============================================================ */
document.addEventListener('DOMContentLoaded', function() {
    const galleryEl = document.querySelector('.hero-masonry-gallery');
    if (!galleryEl) return;

    let allImages = [];
    try {
        allImages = JSON.parse(galleryEl.dataset.galleryImages || '[]');
    } catch(e) { return; }

    // Only auto-rotate if we have more than 5 images
    if (allImages.length <= 5) return;

    // Preload extra images (index 5+)
    const preloaded = [];
    for (let i = 5; i < allImages.length; i++) {
        const img = new Image();
        img.src = allImages[i];
        preloaded.push(img);
    }

    // Get the 4 small image slots (indices 1-4 in the grid)
    const smallSlots = galleryEl.querySelectorAll('.hero-masonry-small img');
    if (smallSlots.length === 0) return;

    let extraIndex = 0; // tracks which extra image to show next
    let slotIndex = 0;  // tracks which small slot to replace next
    const ROTATE_INTERVAL = 5000; // 5 seconds

    function rotateImage() {
        const targetImg = smallSlots[slotIndex];
        const nextSrc = allImages[5 + extraIndex];
        const nextLink = targetImg.closest('a');

        // Fade out
        targetImg.classList.add('masonry-fade-out');

        setTimeout(function() {
            // Swap image source
            targetImg.src = nextSrc;
            targetImg.alt = 'Gallery Image ' + (6 + extraIndex);
            if (nextLink) {
                nextLink.href = nextSrc;
            }

            // Fade in
            targetImg.classList.remove('masonry-fade-out');
        }, 500); // matches the CSS transition duration

        // Advance indices
        extraIndex = (extraIndex + 1) % (allImages.length - 5);
        slotIndex = (slotIndex + 1) % smallSlots.length;
    }

    setInterval(rotateImage, ROTATE_INTERVAL);
});
</script>

<script>
/* ============================================================
   Masonry Gallery — Thumbnail Preview Controller
   ============================================================ */
document.addEventListener('DOMContentLoaded', function() {
    const thumbBtns = document.querySelectorAll('.gallery-thumb-btn');
    const largeMainImg = document.querySelector('.hero-masonry-large img');
    const largeMainLink = document.querySelector('.hero-masonry-large a');
    const mobileTrack = document.querySelector('.hero-mobile-gallery-track');

    if (!thumbBtns.length) return;

    // Helper to update active thumbnail styling
    function updateActiveThumb(index) {
        thumbBtns.forEach(btn => {
            if (parseInt(btn.dataset.index) === index) {
                btn.classList.add('active');
                // Scroll thumbnail into view if needed
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                btn.classList.remove('active');
            }
        });
    }

    thumbBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            const newSrc = this.dataset.src;

            // Update Desktop Large Main Image
            if (largeMainImg) {
                // Smooth fade transition
                largeMainImg.classList.add('masonry-fade-out');
                setTimeout(() => {
                    largeMainImg.src = newSrc;
                    largeMainImg.alt = 'Gallery Image ' + (index + 1);
                    if (largeMainLink) {
                        largeMainLink.href = newSrc;
                    }
                    largeMainImg.classList.remove('masonry-fade-out');
                }, 500); // 500ms to allow fade out to complete before swapping src
            }

            // Update Mobile Swipeable position
            if (mobileTrack) {
                const slideWidth = mobileTrack.clientWidth;
                mobileTrack.scrollTo({
                    left: slideWidth * index,
                    behavior: 'smooth'
                });
            }

            // Update active state
            updateActiveThumb(index);
        });
    });

    // Sync mobile swipe gallery scroll with thumbnail preview strip active state
    if (mobileTrack) {
        let scrollTimeout;
        mobileTrack.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const slideWidth = mobileTrack.clientWidth;
                if (slideWidth > 0) {
                    const activeIndex = Math.round(mobileTrack.scrollLeft / slideWidth);
                    updateActiveThumb(activeIndex);
                }
            }, 100); // Debounce scroll event
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

