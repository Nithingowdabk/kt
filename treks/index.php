<?php
/**
 * Karnataka Trekkers - Treks Catalog with Filters
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::connect();

// Filters Input
$cat_slug = sanitize_input($_GET['category'] ?? '');
$difficulty = sanitize_input($_GET['difficulty'] ?? '');
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);

// Fetch categories for sidebar filter
try {
    $categories = $db->query("SELECT * FROM trek_categories WHERE status = 'Active'")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Build SQL query based on filters
$sql = "SELECT t.*, c.category_name as category_name, c.slug as category_slug,
        (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image
        FROM treks t 
        LEFT JOIN trek_categories c ON t.category_id = c.id 
        WHERE t.status = 'Active'";
$params = [];

if (!empty($cat_slug)) {
    $sql .= " AND c.slug = ?";
    $params[] = $cat_slug;
}
if (!empty($difficulty)) {
    $sql .= " AND t.difficulty = ?";
    $params[] = $difficulty;
}
if ($max_price > 0) {
    $sql .= " AND (
        CASE 
            WHEN t.own_transport_enabled = 1 THEN 
                (CASE WHEN t.without_transport_offer_price > 0 THEN t.without_transport_offer_price ELSE t.without_transport_price END)
            ELSE 
                (CASE WHEN t.offer_price > 0 THEN t.offer_price ELSE t.price END)
        END <= ?
    )";
    $params[] = $max_price;
}

$sql .= " ORDER BY t.featured DESC, t.id DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $treks = $stmt->fetchAll();
    
    // Ratings aggregates helper
    $rating_stmt = $db->query("SELECT trek_id, AVG(rating) as avg_rating, COUNT(id) as total_reviews 
                               FROM reviews WHERE status = 'Approved' GROUP BY trek_id");
    $ratings = [];
    while ($row = $rating_stmt->fetch()) {
        $ratings[$row['trek_id']] = [
            'avg' => round($row['avg_rating'], 1),
            'count' => $row['total_reviews']
        ];
    }
} catch (PDOException $e) {
    $treks = [];
    $ratings = [];
}

$page_title = "Trekking Packages & Trips";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Trek Packages</h1>
        <p class="text-white-50">Discover handpicked trails, beautiful peaks, and weekend getaways</p>
    </div>
</section>

<!-- Filter catalog container -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <button class="btn btn-success d-lg-none w-100 mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                    <i class="fas fa-filter me-2"></i> Toggle Filter Options
                </button>
                <div class="collapse d-lg-block" id="filterCollapse">
                    <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 100px; z-index: 10;">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <h5 class="fw-bold mb-0 text-success"><i class="fas fa-filter me-2"></i>Filters</h5>
                            <a href="<?php echo SITE_URL; ?>/treks/index.php" class="text-muted small text-decoration-none"><i class="fas fa-redo me-1"></i>Reset</a>
                        </div>
                        
                        <form action="" method="GET">
                            <!-- Category filter -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-2">Category</label>
                                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo $cat_slug === $cat['slug'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Difficulty filter -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-2">Difficulty</label>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="difficulty" id="diff_all" value="" <?php echo empty($difficulty) ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <label class="form-check-label text-muted small" for="diff_all">All Levels</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="difficulty" id="diff_easy" value="Easy" <?php echo $difficulty === 'Easy' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <label class="form-check-label text-muted small" for="diff_easy">Easy</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="difficulty" id="diff_mod" value="Moderate" <?php echo $difficulty === 'Moderate' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <label class="form-check-label text-muted small" for="diff_mod">Moderate</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="difficulty" id="diff_diff" value="Difficult" <?php echo $difficulty === 'Difficult' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <label class="form-check-label text-muted small" for="diff_diff">Difficult</label>
                                </div>
                            </div>

                            <!-- Price filter -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-2">Max Price</label>
                                <input type="range" name="max_price" class="form-range" min="1000" max="6000" step="500" value="<?php echo $max_price > 0 ? $max_price : 6000; ?>" onchange="this.form.submit()">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>₹1,000</span>
                                    <span class="text-success fw-bold">₹<?php echo number_format($max_price > 0 ? $max_price : 6000); ?></span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Treks Grid -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php if (!empty($treks)): ?>
                        <?php foreach ($treks as $trek): 
                            $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
                            $active_card_price = get_starting_price($trek);
                        ?>
                            <div class="col-md-6">
                                <?php include __DIR__ . '/../includes/trek-card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-map-signs text-muted fs-1 mb-3"></i>
                            <p class="text-muted">No treks match your selected filters. Please try resetting or adjusting options.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
