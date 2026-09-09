<?php
/**
 * Karnataka Trekkers Homepage
 */
$page_title = "Karnataka's Premium Trekking & Adventure Organizers";
require_once __DIR__ . '/includes/header.php';

// Fetch featured treks and categories
try {
    $db = Database::connect();
    
    // Fetch all active categories
    $cat_stmt = $db->query("SELECT * FROM trek_categories WHERE status = 'Active'");
    $all_active_cats = $cat_stmt->fetchAll();
    
    // Retrieve the custom order from settings
    $order_setting = '';
    try {
        $order_stmt = $db->prepare("SELECT value_data FROM settings WHERE key_name = ? LIMIT 1");
        $order_stmt->execute(['category_order']);
        $order_setting = $order_stmt->fetchColumn();
    } catch (PDOException $e) {
        // Skip custom sorting if table queries fail
    }

    $ordered_ids = [];
    if (!empty($order_setting)) {
        $ordered_ids = json_decode($order_setting, true);
    }
    if (!is_array($ordered_ids)) {
        $ordered_ids = [];
    }

    $category_map = [];
    foreach ($all_active_cats as $cat) {
        $category_map[$cat['id']] = $cat;
    }

    // Sort active categories by category_order IDs, then append any remaining
    $categories = [];
    foreach ($ordered_ids as $id) {
        if (isset($category_map[$id])) {
            $categories[] = $category_map[$id];
            unset($category_map[$id]);
        }
    }
    foreach ($category_map as $cat) {
        $categories[] = $cat;
    }
    
    // Fetch featured/active treks (based on is_featured column)
    $trek_stmt = $db->query("SELECT t.*, c.category_name as category_name,
                             (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image
                             FROM treks t 
                             LEFT JOIN trek_categories c ON t.category_id = c.id 
                             WHERE t.status = 'Active' AND t.featured = 1 ORDER BY t.title ASC");
    $featured_treks = $trek_stmt->fetchAll();

    // Fetch all active treks for category sections
    $all_treks_stmt = $db->query("SELECT t.*, c.category_name as category_name,
                                  (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image
                                  FROM treks t 
                                  LEFT JOIN trek_categories c ON t.category_id = c.id 
                                  WHERE t.status = 'Active' ORDER BY t.title ASC");
    $all_treks = $all_treks_stmt->fetchAll();

    // Map treks to their categories
    $category_treks = [];
    foreach ($all_treks as $t) {
        if (!empty($t['category_id'])) {
            $category_treks[$t['category_id']][] = $t;
        }
    }

    // Fetch latest blogs
    $blog_stmt = $db->query("SELECT * FROM blogs WHERE status = 'Active' ORDER BY created_at DESC LIMIT 6");
    $blogs = $blog_stmt->fetchAll();

    // Fetch average ratings for treks
    $rating_stmt = $db->query("SELECT trek_id, AVG(rating) as avg_rating, COUNT(id) as total_reviews 
                               FROM reviews WHERE status = 'Approved' GROUP BY trek_id");
    $ratings_data = [];
    while ($row = $rating_stmt->fetch()) {
        $ratings_data[$row['trek_id']] = [
            'avg' => round($row['avg_rating'], 1),
            'count' => $row['total_reviews']
        ];
    }
} catch (PDOException $e) {
    $categories = [];
    $featured_treks = [];
    $category_treks = [];
    $blogs = [];
    $ratings_data = [];
}
?>

<!-- Hero Section -->
<section class="hero-slider-section">
    <div class="hero-bg-wrapper">
        <picture>
            <source media="(max-width: 768px)" srcset="<?php echo SITE_URL; ?>/assets/images/hero-bg-mobile.webp" type="image/webp">
            <source srcset="<?php echo SITE_URL; ?>/assets/images/hero-bg.webp" type="image/webp">
            <img src="<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg" alt="Explore Karnataka Treks" width="1024" height="1024" fetchpriority="high" decoding="sync" class="hero-bg-media">
        </picture>
    </div>
    <div class="container hero-content position-relative">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <span class="badge bg-success mb-3 px-3 py-2 text-uppercase fs-7" style="letter-spacing: 2px;">Nature awaits your footsteps</span>
                <h1>Explore the Wild Beauty of Karnataka</h1>
                <p class="mb-4">From the misty peak of Kudremukh to stunning sunrises above the clouds, join Karnataka's safest trekking community.</p>
                
                <!-- Search Box Widget -->
                <div class="hero-search-box">
                    <form action="<?php echo SITE_URL; ?>/treks/search.php" method="GET" class="row g-2">
                        <div class="col-md-5">
                            <div class="input-group position-relative">
                                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-success"></i></span>
                                <input type="text" id="hero-search-input" name="query" autocomplete="off" class="form-control border-0 py-2" placeholder="Where do you want to trek? (e.g. Kudremukh)" aria-label="Where do you want to trek? (e.g. Kudremukh)" data-base-url="<?php echo SITE_URL; ?>">
                                <div id="autocomplete-results" class="dropdown-results"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="category" id="hero-category-select" class="form-select border-0 py-2" aria-label="Select Trek Category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="difficulty" id="hero-difficulty-select" class="form-select border-0 py-2" aria-label="Select Trek Difficulty">
                                <option value="">Difficulty</option>
                                <option value="Easy">Easy</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Difficult">Difficult</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-accent w-100 py-2" aria-label="Search Treks"><i class="fas fa-search me-1"></i>Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Premium Auto-Scrolling Experience Highlights Strip -->
<?php require_once __DIR__ . '/includes/experience-strip.php'; ?>


<style>
/* Horizontal Scroll Containers & Row Layouts */
.scroll-row-container {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    gap: 1.5rem;
    padding: 10px 5px 20px;
}
/* Hide default scrollbars and style customized thin scrollbar */
.scroll-row-container::-webkit-scrollbar {
    height: 6px;
}
.scroll-row-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.scroll-row-container::-webkit-scrollbar-thumb {
    background: rgba(25, 135, 84, 0.4);
    border-radius: 10px;
}
.scroll-row-container::-webkit-scrollbar-thumb:hover {
    background: rgba(25, 135, 84, 0.8);
}
.scroll-row-card {
    flex: 0 0 calc(25% - 1.125rem); /* Desktop: 4 cards visible */
    min-width: 280px;
    margin-bottom: 5px;
}
@media (max-width: 991.98px) {
    .scroll-row-card {
        flex: 0 0 calc(33.333% - 1rem); /* Tablet: 3 cards visible */
        min-width: 250px;
    }
}
@media (max-width: 767.98px) {
    .scroll-row-container {
        scroll-snap-type: x mandatory;
    }
    .scroll-row-card {
        flex: 0 0 260px !important;
        width: 260px !important;
        scroll-snap-align: start;
    }
}

/* Compact Category Grid & Pills Styles */
.compact-category-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}
.category-pill-wrapper {
    flex: 0 0 calc(50% - 4px); /* 2 columns minimum on mobile */
    display: flex;
}
@media (min-width: 768px) {
    .category-pill-wrapper {
        flex: 0 0 auto; /* auto size and wraps on desktop */
    }
}
.compact-category-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff !important;
    border: 1px solid #198754 !important;
    color: #198754 !important;
    border-radius: 50px !important;
    padding: 6px 14px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    height: 40px !important;
    text-decoration: none !important;
    transition: all 0.2s ease-in-out;
    box-shadow: none !important;
    width: 100%;
}
.compact-category-pill:hover {
    background-color: #198754 !important;
    color: #ffffff !important;
}
.compact-category-pill span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.compact-category-pill i {
    font-size: 0.85rem;
}
</style>

<!-- SECTION 1: Featured Treks -->
<section class="section-padding">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-2 mb-4">
            <div class="section-title mb-0 text-start">
                <span>Customer Favorites</span>
                <h2 class="mb-0">Featured Treks</h2>
            </div>
            <a href="<?php echo SITE_URL; ?>/treks/index.php" class="text-success fw-bold text-decoration-none hover-underline" aria-label="View all featured treks">
                View All Featured Treks <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="scroll-row-container">
            <?php if (!empty($featured_treks)): ?>
                <?php foreach ($featured_treks as $trek): 
                    $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
                    $active_card_price = get_starting_price($trek);
                ?>
                    <div class="scroll-row-card">
                        <?php include __DIR__ . '/includes/trek-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-100 text-center py-4">
                    <p class="text-muted">No featured treks available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Browse By Category Section -->
<section class="section-padding bg-light categories-section">
    <div class="container">
        <div class="section-title text-start mb-4">
            <span>Explore Options</span>
            <h2 class="mb-0">Browse By Category</h2>
        </div>
        
        <div class="categories-container">
            <?php 
            $shown_categories = 0;
            foreach ($categories as $cat): 
                $cat_id = $cat['id'];
                $trek_count = isset($category_treks[$cat_id]) ? count($category_treks[$cat_id]) : 0;
                
                // Hide category completely if trek_count is 0
                if ($trek_count === 0) {
                    continue;
                }
                $shown_categories++;
            ?>
                <a href="<?php echo SITE_URL; ?>/treks/category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>" class="category-card-link text-decoration-none" aria-label="Browse <?php echo htmlspecialchars($cat['category_name']); ?> Category (<?php echo $trek_count; ?> Treks)">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <i class="<?php echo htmlspecialchars($cat['icon'] ?: 'fas fa-tags'); ?>"></i>
                        </div>
                        <h3 class="category-title"><?php echo htmlspecialchars($cat['category_name']); ?></h3>
                        <span class="category-count"><?php echo $trek_count; ?> <?php echo $trek_count === 1 ? 'Trek' : 'Treks'; ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
            
            <?php if ($shown_categories === 0): ?>
                <div class="w-100 text-center py-4">
                    <p class="text-muted">No active categories with trekking packages available currently.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- SECTION 2: Category Wise Trek Sections -->
<section class="section-padding">
    <div class="container">
        <?php foreach ($categories as $cat): 
            $cat_id = $cat['id'];
            $cat_treks = $category_treks[$cat_id] ?? [];
            if (empty($cat_treks)) continue;
        ?>
            <div class="mb-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-2 mb-4 border-bottom pb-2">
                    <div class="section-title mb-0 text-start">
                        <h2 class="mb-0 fs-3 fw-bold text-dark"><?php echo htmlspecialchars($cat['category_name']); ?></h2>
                    </div>
                    <?php if (count($cat_treks) > 6): ?>
                        <a href="<?php echo SITE_URL; ?>/treks/category.php?slug=<?php echo $cat['slug']; ?>" class="text-success fw-bold text-decoration-none hover-underline" aria-label="View all <?php echo htmlspecialchars($cat['category_name']); ?> treks">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="scroll-row-container">
                    <?php 
                    $displayed_count = 0;
                    foreach ($cat_treks as $trek): 
                        if ($displayed_count >= 6) break;
                        $displayed_count++;
                        $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
                        $active_card_price = get_starting_price($trek);
                    ?>
                        <div class="scroll-row-card">
                            <?php include __DIR__ . '/includes/trek-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Safety & Trust Section -->
<section class="section-padding bg-light trust-section">
    <div class="container">
        <div class="trust-grid-container">
            <div class="trust-title-area">
                <span class="text-success fw-bold text-uppercase" style="letter-spacing: 1px;">Safety First Standards</span>
                <h2 class="trust-section-title fw-bold mt-2 mb-4">Why Trek with Karnataka Trekkers?</h2>
                <p class="trust-section-desc">We pride ourselves on offering the most secure, organized, and memorable outdoor adventure journeys in Southern India. Safety, customer delight, and preservation of nature form our core pillars.</p>
            </div>
            
            <div class="trust-image-area">
                <!-- Placeholder helper for landscape/guides picture -->
                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" class="img-fluid trust-section-img shadow" alt="Trek Guides Karnataka" width="600" height="400" loading="lazy">
            </div>

            <div class="trust-features-area">
                <div class="trust-features-list">
                    <div class="trust-feature-card d-flex align-items-start gap-3 mb-3">
                        <div class="trust-feature-icon-wrapper bg-success text-white p-2 rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="trust-feature-title fw-bold mb-1">BMC Certified Guides</h3>
                            <p class="trust-feature-desc text-muted mb-0">Our trek leaders are certified mountaineers trained in wilderness first aid and rescue procedures.</p>
                        </div>
                    </div>
                    <div class="trust-feature-card d-flex align-items-start gap-3 mb-3">
                        <div class="trust-feature-icon-wrapper bg-success text-white p-2 rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div>
                            <h3 class="trust-feature-title fw-bold mb-1">Leave No Trace Policy</h3>
                            <p class="trust-feature-desc text-muted mb-0">We strictly practice eco-conservation. We collect and bring back all plastic waste generated on treks.</p>
                        </div>
                    </div>
                    <div class="trust-feature-card d-flex align-items-start gap-3">
                        <div class="trust-feature-icon-wrapper bg-success text-white p-2 rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h3 class="trust-feature-title fw-bold mb-1">24/7 Operations Room</h3>
                            <p class="trust-feature-desc text-muted mb-0">Our office team monitors the weather and maintains real-time satellite updates for high peak expeditions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Blogs Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <span>Explore Guides</span>
            <h2>Travel Tips & Blogs</h2>
        </div>
        <div class="blog-carousel-wrapper">
            <button class="carousel-nav-btn prev-btn" id="blog-carousel-prev" aria-label="Previous posts">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="blog-carousel-container" id="blog-carousel-container">
                <?php if (!empty($blogs)): ?>
                    <?php foreach ($blogs as $blog): ?>
                        <div class="blog-carousel-card">
                            <div class="card h-100 border-light shadow-sm">
                                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($blog['title']); ?>" width="400" height="200" style="height: 200px; object-fit: cover;" loading="lazy">
                                <div class="card-body">
                                    <small class="text-success fw-bold d-block mb-2"><?php echo format_date($blog['created_at']); ?> | By <?php echo htmlspecialchars($blog['author']); ?></small>
                                    <h3 class="card-title fw-bold">
                                        <a href="<?php echo SITE_URL; ?>/blogs/<?php echo $blog['slug']; ?>" class="text-decoration-none text-dark hover-success"><?php echo htmlspecialchars($blog['title']); ?></a>
                                    </h3>
                                    <p class="card-text text-muted" style="font-size: 0.9rem;">
                                        <?php echo strip_tags(substr($blog['content'], 0, 100)) . '...'; ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 p-3 pt-0">
                                    <a href="<?php echo SITE_URL; ?>/blogs/<?php echo $blog['slug']; ?>" class="btn btn-link text-success fw-bold p-0 text-decoration-none" aria-label="Read Full Article: <?php echo htmlspecialchars($blog['title']); ?>">Read Full Article <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="w-100 text-center py-4">
                        <p class="text-muted">No travel tips/articles published yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="carousel-nav-btn next-btn" id="blog-carousel-next" aria-label="Next posts">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
