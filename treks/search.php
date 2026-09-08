<?php
/**
 * Karnataka Trekkers - Search Result Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::connect();

// Inputs
$query = sanitize_input($_GET['query'] ?? '');
$cat_slug = sanitize_input($_GET['category'] ?? '');
$difficulty = sanitize_input($_GET['difficulty'] ?? '');

$sql = "SELECT t.*, c.category_name as category_name,
        (SELECT image_path FROM trek_gallery WHERE trek_id = t.id AND is_featured = 1 LIMIT 1) as gallery_featured_image
        FROM treks t 
        LEFT JOIN trek_categories c ON t.category_id = c.id 
        WHERE t.status = 'Active'";
$params = [];

if (!empty($query)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
}

if (!empty($cat_slug)) {
    $sql .= " AND c.slug = ?";
    $params[] = $cat_slug;
}

if (!empty($difficulty)) {
    $sql .= " AND t.difficulty = ?";
    $params[] = $difficulty;
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

$page_title = "Search Results for '" . $query . "'";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-2">Search Results</h1>
        <p class="text-white-50">Found <?php echo count($treks); ?> adventure packages matching "<?php echo htmlspecialchars($query); ?>"</p>
    </div>
</section>

<!-- Search Grid -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <?php if (!empty($treks)): ?>
                <?php foreach ($treks as $trek): 
                    $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
                    $active_card_price = get_starting_price($trek);
                ?>
                    <div class="col-lg-4 col-md-6">
                        <?php include __DIR__ . '/../includes/trek-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search-minus text-muted fs-1 mb-3"></i>
                    <p class="text-muted">We couldn't find any treks matching your keyword. Try checking for spelling errors or searching a different destination.</p>
                    <a href="<?php echo SITE_URL; ?>/treks/index.php" class="btn btn-primary-custom mt-3">Browse All Treks</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
