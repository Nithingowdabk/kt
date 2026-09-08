<?php
/**
 * Karnataka Trekkers - Treks by Category
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::connect();
$slug = sanitize_input($_GET['slug'] ?? '');

// 301 Canonical Redirect for legacy query URLs: /treks/category.php?slug=xxx -> /category/xxx
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'category.php') !== false) {
    if (!empty($slug)) {
        header("Location: " . SITE_URL . "/category/" . $slug, true, 301);
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
    // Fetch Category details
    $cat_stmt = $db->prepare("SELECT * FROM trek_categories WHERE slug = ? AND status = 'Active' LIMIT 1");
    $cat_stmt->execute([$slug]);
    $category = $cat_stmt->fetch();

    if (!$category) {
        set_flash_message('danger', 'Category not found.');
        header('Location: ' . SITE_URL . '/treks/index.php');
        exit();
    }

    $category_id = $category['id'];

    // Fetch treks in this category
    $trek_stmt = $db->prepare("SELECT *, (SELECT image_path FROM trek_gallery WHERE trek_id = treks.id AND is_featured = 1 LIMIT 1) as gallery_featured_image FROM treks WHERE category_id = ? AND status = 'Active' ORDER BY featured DESC, id DESC");
    $trek_stmt->execute([$category_id]);
    $treks = $trek_stmt->fetchAll();

    // Ratings helper
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
    $category = null;
    $treks = [];
    $ratings = [];
}

$page_title = $category['category_name'] . " Trails";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-size: cover !important; background-position: center !important; background-repeat: no-repeat !important; background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($category['category_name']); ?></h1>
        <p class="text-white-50"><?php echo htmlspecialchars($category['description']); ?></p>
    </div>
</section>

<!-- Treks List -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <?php if (!empty($treks)): ?>
                <?php foreach ($treks as $trek): 
                    $trek['category_name'] = $category['category_name'];
                    $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
                    $active_card_price = get_starting_price($trek);
                ?>
                    <div class="col-lg-4 col-md-6">
                        <?php include __DIR__ . '/../includes/trek-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-route text-muted fs-1 mb-3"></i>
                    <p class="text-muted">No trekking packages available under this category currently.</p>
                    <a href="<?php echo SITE_URL; ?>/treks/index.php" class="btn btn-primary-custom mt-3">See All Treks</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
