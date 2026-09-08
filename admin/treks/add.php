<?php
/**
 * Admin - Add New Trek
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

$error = '';

// Fetch categories for dropdown
try {
    $categories = $db->query("SELECT id, category_name FROM trek_categories WHERE status = 'Active'")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $duration = sanitize_input($_POST['duration'] ?? '');
    $difficulty = !empty($_POST['difficulty']) ? sanitize_input($_POST['difficulty']) : null;
    $distance = (float)($_POST['trek_distance'] ?? 0);
    $altitude = 0; // Altitude removed from UI
    
    // New Package configurations
    $with_transport_price = (float)($_POST['with_transport_price'] ?? 0);
    $with_transport_offer_price = !empty($_POST['with_transport_offer_price']) ? (float)$_POST['with_transport_offer_price'] : null;
    $without_transport_price = (float)($_POST['without_transport_price'] ?? 0);
    $without_transport_offer_price = !empty($_POST['without_transport_offer_price']) ? (float)$_POST['without_transport_offer_price'] : null;
    $transport_enabled = isset($_POST['transport_enabled']) ? 1 : 0;
    $own_transport_enabled = isset($_POST['own_transport_enabled']) ? 1 : 0;
    $own_transport_note = sanitize_input($_POST['own_transport_note'] ?? '');
    $with_transport_note = sanitize_input($_POST['with_transport_note'] ?? '');

    $description = sanitize_input($_POST['description'] ?? '');
    $with_transport_inclusions = sanitize_input($_POST['with_transport_inclusions'] ?? '');
    $with_transport_exclusions = sanitize_input($_POST['with_transport_exclusions'] ?? '');
    $own_transport_inclusions = sanitize_input($_POST['own_transport_inclusions'] ?? '');
    $own_transport_exclusions = sanitize_input($_POST['own_transport_exclusions'] ?? '');
    $things_to_carry = sanitize_input($_POST['things_to_carry'] ?? '');
    $pickup_points_txt = sanitize_input($_POST['pickup_points_txt'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Active');
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    $meta_title = sanitize_input($_POST['meta_title'] ?? '');
    $meta_description = sanitize_input($_POST['meta_description'] ?? '');
    $focus_keyphrase = sanitize_input($_POST['focus_keyphrase'] ?? '');
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $image_alt = sanitize_input($_POST['image_alt'] ?? '');
    $tags = sanitize_input($_POST['tags'] ?? '');

    // Convert Itinerary inputs into JSON array
    $itinerary_titles = $_POST['itinerary_title'] ?? [];
    $itinerary_descs = $_POST['itinerary_desc'] ?? [];
    $itinerary_days = $_POST['itinerary_day'] ?? [];
    $itinerary_arr = [];

    for ($i = 0; $i < count($itinerary_titles); $i++) {
        if (!empty($itinerary_titles[$i])) {
            $itinerary_arr[] = [
                'day' => sanitize_input($itinerary_days[$i] ?? ('Day ' . ($i + 1))),
                'title' => sanitize_input($itinerary_titles[$i]),
                'desc' => sanitize_input($itinerary_descs[$i])
            ];
        }
    }
    $itinerary_json = json_encode($itinerary_arr);

    // Validation checks
    if (empty($title) || empty($duration)) {
        $error = "Please fill in all core required fields (Title, Duration).";
    } elseif (!$transport_enabled && !$own_transport_enabled) {
        $error = "At least one transport package type must be enabled.";
    } elseif ($transport_enabled && $with_transport_price <= 0) {
        $error = "Please enter a valid price for the Transportation Package.";
    } elseif ($own_transport_enabled && $without_transport_price <= 0) {
        $error = "Please enter a valid price for the Own Transport Package.";
    } else {
        if (empty($slug)) {
            $slug = create_slug($title);
        } else {
            $slug = create_slug($slug);
        }

        try {
            // Check if slug is unique
            $slug_check = $db->prepare("SELECT id FROM treks WHERE slug = ? LIMIT 1");
            $slug_check->execute([$slug]);
            if ($slug_check->fetch()) {
                $slug .= '-' . rand(10, 99);
            }

            // Handle image upload
            $image = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
                $uploaded = secure_image_upload($_FILES['image_file'], 'treks');
                if ($uploaded !== false) {
                    $image = $uploaded;
                } else {
                    $error = "Invalid trek cover image. Ensure it is a valid JPG/PNG/WEBP/GIF image under 2MB.";
                }
            }

            if (empty($error)) {
                // Insert Trek
                $stmt = $db->prepare("INSERT INTO treks (
                    category_id, title, slug, duration, difficulty, trek_distance, altitude, 
                    price, offer_price, with_transport_price, with_transport_offer_price,
                    without_transport_price, without_transport_offer_price,
                    transport_enabled, own_transport_enabled, own_transport_note, with_transport_note,
                    description, itinerary, inclusions, exclusions, 
                    with_transport_inclusions, with_transport_exclusions,
                    own_transport_inclusions, own_transport_exclusions,
                    things_to_carry, pickup_points_txt, status, featured, 
                    meta_title, meta_description, focus_keyphrase, excerpt, image_alt, tags, image
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $category_id > 0 ? $category_id : null,
                    $title,
                    $slug,
                    $duration,
                    $difficulty,
                    $distance,
                    $altitude,
                    $with_transport_price, // fallback legacy
                    $with_transport_offer_price, // fallback legacy
                    $with_transport_price,
                    $with_transport_offer_price,
                    $without_transport_price,
                    $without_transport_offer_price,
                    $transport_enabled,
                    $own_transport_enabled,
                    $own_transport_note,
                    $with_transport_note,
                    $description,
                    $itinerary_json,
                    $with_transport_inclusions, // legacy inclusions fallback
                    $with_transport_exclusions, // legacy exclusions fallback
                    $with_transport_inclusions,
                    $with_transport_exclusions,
                    $own_transport_inclusions,
                    $own_transport_exclusions,
                    $things_to_carry,
                    $pickup_points_txt,
                    $status,
                    $featured,
                    $meta_title,
                    $meta_description,
                    $focus_keyphrase,
                    $excerpt,
                    $image_alt,
                    $tags,
                    $image
                ]);
                
                $new_trek_id = $db->lastInsertId();
                $session_token = $_POST['session_token'] ?? '';
                
                if (!empty($session_token)) {
                    // Link temporary gallery & videos to the newly created trek
                    $db->prepare("UPDATE trek_gallery SET trek_id = ?, session_token = NULL WHERE session_token = ?")->execute([$new_trek_id, $session_token]);
                    $db->prepare("UPDATE trek_videos SET trek_id = ?, session_token = NULL WHERE session_token = ?")->execute([$new_trek_id, $session_token]);
                }

                // Insert dynamic Highlights
                $hl_texts = $_POST['highlights'] ?? [];
                $hl_icons = $_POST['highlight_icons'] ?? [];
                $hl_order = 0;
                for ($i = 0; $i < count($hl_texts); $i++) {
                    $hl_text = sanitize_input($hl_texts[$i]);
                    $hl_icon = sanitize_input($hl_icons[$i] ?? 'fas fa-check-circle');
                    if (!empty($hl_text)) {
                        $db->prepare("INSERT INTO trek_highlights (trek_id, highlight, icon, sort_order) VALUES (?, ?, ?, ?)")
                           ->execute([$new_trek_id, $hl_text, $hl_icon, $hl_order++]);
                    }
                }

                // Insert dynamic Notes
                $note_texts = $_POST['notes'] ?? [];
                $note_order = 0;
                for ($i = 0; $i < count($note_texts); $i++) {
                    $note_text = sanitize_input($note_texts[$i]);
                    if (!empty($note_text)) {
                        $db->prepare("INSERT INTO trek_notes (trek_id, note, sort_order) VALUES (?, ?, ?)")
                           ->execute([$new_trek_id, $note_text, $note_order++]);
                    }
                }

                // Insert dynamic Additional Notes
                $additional_note_texts = $_POST['additional_notes'] ?? [];
                $additional_note_order = 0;
                for ($i = 0; $i < count($additional_note_texts); $i++) {
                    $add_note_text = sanitize_input($additional_note_texts[$i]);
                    if (!empty($add_note_text)) {
                        $db->prepare("INSERT INTO trek_additional_notes (trek_id, note, sort_order) VALUES (?, ?, ?)")
                           ->execute([$new_trek_id, $add_note_text, $additional_note_order++]);
                    }
                }

                // Insert dynamic FAQs
                $faq_qs = $_POST['faq_question'] ?? [];
                $faq_as = $_POST['faq_answer'] ?? [];
                $faq_order = 0;
                for ($i = 0; $i < count($faq_qs); $i++) {
                    $faq_q = sanitize_input($faq_qs[$i]);
                    $faq_a = sanitize_input($faq_as[$i]);
                    if (!empty($faq_q) && !empty($faq_a)) {
                        $db->prepare("INSERT INTO trek_faqs (trek_id, question, answer, sort_order) VALUES (?, ?, ?, ?)")
                           ->execute([$new_trek_id, $faq_q, $faq_a, $faq_order++]);
                    }
                }

                set_flash_message('success', 'Trek details added successfully!');
                header('Location: ' . SITE_URL . '/admin/treks/manage.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = "DB Insert failed: " . $e->getMessage();
        }
    }
}

// Generate new session token for this upload session
$session_token = bin2hex(random_bytes(16));

// Fetch gallery categories for drop-down
$gallery_categories = [];
try {
    $gallery_categories = $db->query("SELECT * FROM gallery_categories")->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Trek | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto">
                    <span class="text-muted small">Add Trek details</span>
                </div>
            </div>
        </nav>

        <!-- Main Form -->
        <div class="container-fluid p-4" style="max-width: 1000px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Add New Trek</h3>
                <a href="<?php echo SITE_URL; ?>/admin/treks/manage.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Catalog
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="session_token" value="<?php echo htmlspecialchars($session_token); ?>">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">1. Core Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Trek Title *</span>
                            </label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Kudremukh Peak Trek">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>URL Slug <small class="text-muted">(leave blank to auto-generate)</small></span>
                                <span id="trek_slug_counter" class="badge bg-light text-muted border">30–50 chars</span>
                            </label>
                            <input type="text" name="slug" id="trek_slug" class="form-control" placeholder="e.g. kudremukh-peak-trek-karnataka-adventure" data-seo-counter="trek_slug_counter" data-seo-min="30" data-seo-max="50">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration *</label>
                            <input type="text" name="duration" class="form-control" required placeholder="e.g. 2 Days / 1 Night">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Difficulty</label>
                            <select name="difficulty" class="form-select">
                                <option value="">-- Not Specified --</option>
                                <option value="Easy">Easy</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Difficult">Difficult</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Trek Distance (km) *</label>
                            <input type="number" step="0.1" name="trek_distance" class="form-control" required placeholder="e.g. 22">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4 pt-2">
                                <input type="checkbox" name="featured" class="form-check-input" id="featured_chk">
                                <label class="form-check-label fw-bold" for="featured_chk">Featured Trek</label>
                            </div>
                        </div>

                        <!-- Trek Excerpt / Short Summary -->
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Trek Excerpt / Summary <small class="text-muted">(Short description shown on trek cards and search snippets)</small></span>
                                <span id="trek_excerpt_counter" class="badge bg-light text-muted border">120–200 chars</span>
                            </label>
                            <textarea name="excerpt" id="trek_excerpt" class="form-control" rows="2" placeholder="Experience the picturesque green hills, gushing streams, and thrilling misty ridge trails of Kudremukh on this guided weekend trekking expedition from Bangalore." data-seo-counter="trek_excerpt_counter" data-seo-min="120" data-seo-max="200"></textarea>
                        </div>

                        <!-- Packages and Pricing Section -->
                        <div class="col-12 mt-3 mb-2 border-bottom pb-2">
                            <h6 class="fw-bold text-success"><i class="fas fa-box-open me-2"></i>Trek Package Configurations</h6>
                        </div>

                        <!-- With Transportation Configuration -->
                        <div class="col-md-6 border-end">
                            <div class="form-check mb-3">
                                <input type="checkbox" name="transport_enabled" class="form-check-input" id="transport_enabled_chk" value="1" checked>
                                <label class="form-check-label fw-bold text-dark" for="transport_enabled_chk">Enable Transportation Package</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Original Price (INR) *</label>
                                <input type="number" name="with_transport_price" id="with_transport_price" class="form-control form-control-sm" placeholder="e.g. 3499">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Offer Price (optional)</label>
                                <input type="number" name="with_transport_offer_price" id="with_transport_offer_price" class="form-control form-control-sm" placeholder="e.g. 2999">
                            </div>
                        </div>

                        <!-- Without Transportation Configuration -->
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" name="own_transport_enabled" class="form-check-input" id="own_transport_enabled_chk" value="1" checked>
                                <label class="form-check-label fw-bold text-dark" for="own_transport_enabled_chk">Enable Own Transport Package</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Original Price (Without Transport) (INR) *</label>
                                <input type="number" name="without_transport_price" id="without_transport_price" class="form-control form-control-sm" placeholder="e.g. 1999">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Offer Price (Without Transport) (optional)</label>
                                <input type="number" name="without_transport_offer_price" id="without_transport_offer_price" class="form-control form-control-sm" placeholder="e.g. 1499">
                            </div>
                        </div>

                        <!-- With Transport Note -->
                        <div class="col-md-12 mt-2 mb-3">
                            <label class="form-label fw-bold small text-muted">With Transport Booking Notes</label>
                            <textarea name="with_transport_note" class="form-control" rows="3" placeholder="Booking notes/instructions shown when they book with transport (e.g. previous day date selector, reporting instructions)"></textarea>
                        </div>

                        <!-- Own Transport Note -->
                        <div class="col-md-12 mt-2 mb-3">
                            <label class="form-label fw-bold small text-muted">Own Transport Booking Notes</label>
                            <textarea name="own_transport_note" class="form-control" rows="3" placeholder="Instructions shown when they book without transport (e.g. reporting time, base point instructions)"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Trek Cover Image</label>
                            <input type="file" name="image_file" class="form-control">
                            <small class="text-muted">Upload a high quality cover image (JPG, PNG, WEBP, max 2MB).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Cover Image Alt Text</span>
                                <span id="trek_image_alt_counter" class="badge bg-light text-muted border">50–125 chars</span>
                            </label>
                            <input type="text" name="image_alt" id="trek_image_alt" class="form-control" placeholder="e.g. Trekkers ascending the lush misty ridge of Kudremukh mountain peak in Karnataka" data-seo-counter="trek_image_alt_counter" data-seo-min="50" data-seo-max="125">
                            <small class="text-muted">Descriptive alt text for image accessibility and Google Image SEO.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Main Description / Overview *</label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Write a detailed introduction for the trek..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Itinerary builder card -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-success mb-0">2. Itinerary Builder</h5>
                        <button type="button" id="btn_add_itinerary_day" class="btn btn-sm btn-success px-2 py-1">
                            <i class="fas fa-plus me-1"></i> Add Day
                        </button>
                    </div>

                    <div id="itinerary_days_container">
                        <!-- Days are added dynamically by admin.js -->
                    </div>
                </div>

                <!-- 2a. Trek Highlights Repeater -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-success mb-0">2a. Trek Highlights</h5>
                        <button type="button" id="btn_add_highlight" class="btn btn-sm btn-success px-2 py-1">
                            <i class="fas fa-plus me-1"></i> Add Highlight
                        </button>
                    </div>
                    <div id="highlights_container" class="sortable-list d-flex flex-column gap-2">
                        <!-- Added dynamically by JS -->
                    </div>
                </div>

                <!-- 2b. Important Notes Repeater -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-success mb-0">2b. Important Notes</h5>
                        <button type="button" id="btn_add_note" class="btn btn-sm btn-success px-2 py-1">
                            <i class="fas fa-plus me-1"></i> Add Note
                        </button>
                    </div>
                    <div id="notes_container" class="sortable-list d-flex flex-column gap-2">
                        <!-- Added dynamically by JS -->
                    </div>
                </div>

                <!-- 2d. Additional Important Notes Repeater -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-success mb-0">2d. Additional Important Notes</h5>
                        <button type="button" id="btn_add_additional_note" class="btn btn-sm btn-success px-2 py-1">
                            <i class="fas fa-plus me-1"></i> Add Additional Note
                        </button>
                    </div>
                    <div id="additional_notes_container" class="sortable-list d-flex flex-column gap-2">
                        <!-- Added dynamically by JS -->
                    </div>
                </div>

                <!-- 2c. Trek FAQs Repeater -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-success mb-0">2c. Trek FAQs</h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="faq_search" class="form-control form-control-sm" placeholder="Search FAQs..." style="max-width: 200px;">
                            <button type="button" id="btn_add_faq" class="btn btn-sm btn-success px-2 py-1">
                                <i class="fas fa-plus me-1"></i> Add FAQ
                            </button>
                        </div>
                    </div>
                    <div id="faqs_container" class="sortable-list d-flex flex-column gap-3">
                        <!-- Added dynamically by JS -->
                    </div>
                </div>

                <!-- Extras details -->
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">3. Inclusions, Exclusions & Instructions</h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-success"><i class="fas fa-bus me-2"></i>With Transport Package Details</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">With Transport Inclusions</label>
                            <textarea name="with_transport_inclusions" class="form-control" rows="3" placeholder="Enter line-separated items included with transport package..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">With Transport Exclusions</label>
                            <textarea name="with_transport_exclusions" class="form-control" rows="3" placeholder="Enter line-separated items excluded from transport package..."></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 border-top pt-3">
                        <div class="col-12">
                            <h6 class="fw-bold text-success"><i class="fas fa-car-side me-2"></i>Without Transport (Own Transport) Package Details</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Own Transport Inclusions</label>
                            <textarea name="own_transport_inclusions" class="form-control" rows="3" placeholder="Enter line-separated items included with own transport package..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Own Transport Exclusions</label>
                            <textarea name="own_transport_exclusions" class="form-control" rows="3" placeholder="Enter line-separated items excluded from own transport package..."></textarea>
                        </div>
                    </div>
                        <div class="col-md-12">
                            <label class="form-label">Things to Carry</label>
                            <textarea name="things_to_carry" class="form-control" rows="3" placeholder="backpack, shoes, raincoat..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">General Pick-up Description</label>
                            <input type="text" name="pickup_points_txt" class="form-control" placeholder="e.g. Pickup from Majestic Metro at 9:00 PM">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Trek Photo Gallery -->
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">4. Trek Photo Gallery (<span id="gallery-count">0</span> Images)</h5>
                    <div id="gallery-dropzone" class="border rounded p-4 text-center bg-light mb-3" style="border-style: dashed !important; border-width: 2px !important;">
                        <i class="fas fa-images fa-3x text-success mb-2"></i>
                        <h6 class="fw-bold">Drag & Drop multiple photos here</h6>
                        <small class="text-muted d-block mb-3">JPG, PNG, WEBP (Max 2MB per image)<br>Recommended sizes: 1920&times;1080, 1600&times;900, 1280&times;720 (16:9 landscape ratio)</small>
                        <button type="button" class="btn btn-outline-success px-4" onclick="document.getElementById('gallery-file-input').click();">
                            <i class="fas fa-folder-open me-2"></i> Select Multiple Photos
                        </button>
                        <input type="file" id="gallery-file-input" multiple accept="image/jpeg, image/png, image/webp" style="position: absolute; left: -9999px; opacity: 0;">
                    </div>

                    
                    <div id="gallery-grid" class="row g-3" style="min-height: 100px;">
                        <!-- Gallery items loaded via JS -->
                    </div>
                </div>

                <!-- Trek Videos -->
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2">5. Trek Videos (YouTube)</h5>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" id="video-url-input" class="form-control" placeholder="Paste YouTube URL here...">
                        </div>
                        <div class="col-md-5">
                            <input type="text" id="video-title-input" class="form-control" placeholder="Video Title (optional)">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success w-100" type="button" id="btn-add-video">Add</button>
                        </div>
                    </div>
                    <div id="video-list" class="list-group">
                        <!-- Videos loaded via JS -->
                    </div>
                </div>

                <!-- SEO details -->
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-search me-2"></i>6. SEO Optimization & Search Engine Indexing</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-key me-1 text-success"></i> Focus Keyphrase <small class="text-muted">(Primary search keyword/phrase target)</small></span>
                                <span id="trek_keyphrase_counter" class="badge bg-light text-muted border">10–40 chars</span>
                            </label>
                            <input type="text" name="focus_keyphrase" id="trek_focus_keyphrase" class="form-control" placeholder="e.g. Kudremukh Trek from Bangalore" data-seo-counter="trek_keyphrase_counter" data-seo-min="10" data-seo-max="40">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>SEO Title (Meta Title)</span>
                                <span id="trek_meta_title_counter" class="badge bg-light text-muted border">50–60 chars</span>
                            </label>
                            <input type="text" name="meta_title" id="trek_meta_title" class="form-control" placeholder="e.g. Kudremukh Trek Bangalore - 2 Day Western Ghats Trip" data-seo-counter="trek_meta_title_counter" data-seo-min="50" data-seo-max="60">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Meta Description</span>
                                <span id="trek_meta_desc_counter" class="badge bg-light text-muted border">150–155 chars</span>
                            </label>
                            <textarea name="meta_description" id="trek_meta_description" class="form-control" rows="2" placeholder="Book the scenic Kudremukh peak trek in Chikmagalur with Karnataka Trekkers. Includes meals, certified guides, tent stays, and bus transfers from Bangalore." data-seo-counter="trek_meta_desc_counter" data-seo-min="150" data-seo-max="155"></textarea>
                        </div>

                        <!-- 10-12 Tags Manager -->
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tags me-1 text-success"></i> Trek Tags & Keywords <small class="text-muted">(Recommended: 10–12 tags)</small></span>
                                <span class="tag-count-badge badge bg-light text-muted border">0 tags</span>
                            </label>
                            <div class="tag-manager-wrapper border rounded p-3 bg-light">
                                <input type="hidden" name="tags" class="tag-hidden-input" value="">
                                <div class="tag-chips-container mb-2 min-h-30"></div>
                                <div class="input-group">
                                    <input type="text" class="form-control tag-add-input" placeholder="Type a tag and press Enter or comma (e.g. Kudremukh Trek, Chikmagalur, Monsoon Treks, Weekend Trips)...">
                                    <button class="btn btn-outline-success btn-add-tag" type="button"><i class="fas fa-plus me-1"></i> Add Tag</button>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i> Press Enter or comma to add each tag. Aim for 10 to 12 descriptive tags to boost search visibility.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-5 text-end d-flex justify-content-end gap-2">
                    <button type="button" id="btn_preview_trek" class="btn btn-outline-success px-4 py-3 shadow fs-6 fw-bold">
                        <i class="fas fa-eye me-1"></i> Live Preview
                    </button>
                    <button type="submit" class="btn btn-success px-5 py-3 shadow fs-6 fw-bold">
                        <i class="fas fa-save me-1"></i> Save Trek Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Live Preview Modal -->
<div class="modal fade" id="trekPreviewModal" tabindex="-1" aria-labelledby="trekPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold" id="trekPreviewModalLabel"><i class="fas fa-eye me-2"></i>Trek Page Content Live Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light" style="max-height: 70vh; overflow-y: auto;">
        
        <!-- Highlights Preview -->
        <div class="preview-section mb-4">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fas fa-star me-2"></i>Highlights Preview</h6>
            <div id="preview_highlights_container" class="row g-2 justify-content-center">
                <!-- Dynamic Highlights go here -->
            </div>
            <div id="preview_highlights_empty" class="text-muted small text-center p-3 border rounded bg-white d-none">
                No highlights added. This section will be hidden on the trek details page.
            </div>
        </div>
        
        <!-- Important Notes Preview -->
        <div class="preview-section mb-4">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Important Notes Preview</h6>
            <div id="preview_notes_container">
                <!-- Dynamic Notes go here -->
            </div>
            <div id="preview_notes_empty" class="text-muted small text-center p-3 border rounded bg-white d-none">
                No important notes added. This section will be hidden on the trek details page.
            </div>
        </div>
        
        <!-- FAQs Preview -->
        <div class="preview-section">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fas fa-question-circle me-2"></i>FAQs Preview</h6>
            <div id="preview_faqs_container" class="accordion">
                <!-- Dynamic FAQs go here -->
            </div>
            <div id="preview_faqs_empty" class="text-muted small text-center p-3 border rounded bg-white d-none">
                No FAQs added. This section will be hidden on the trek details page.
            </div>
        </div>
        
      </div>
      <div class="modal-footer bg-white">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Preview</button>
      </div>
    </div>
  </div>
</div>


<!-- Lightbox Modal -->
<div class="modal fade" id="galleryLightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-header border-0 pb-0 text-end d-block">
        <button type="button" class="btn-close btn-close-white fs-4" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-0">
        <img id="lightbox-image" src="" class="img-fluid rounded shadow" style="max-height: 85vh;" alt="Preview">
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script>
    const JS_SITE_URL = '<?php echo SITE_URL; ?>';
    const TREK_ID = 0; // 0 indicates new trek temporary session
    const SESSION_TOKEN = '<?php echo $session_token; ?>';
    const GALLERY_CATEGORIES = <?php echo json_encode($gallery_categories); ?>;
</script>
<script src="<?php echo SITE_URL; ?>/assets/js/gallery_admin.js?v=<?php echo time(); ?>"></script>

</body>
</html>
