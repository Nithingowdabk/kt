<?php
/**
 * Karnataka Trekkers - Media Gallery
 */
$page_title = "Scenic Trekking Gallery";
require_once __DIR__ . '/includes/header.php';

try {
    $db = Database::connect();
    
    // Auto-ensure gallery_categories table & slug column
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `gallery_categories` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `category_name` VARCHAR(150) NOT NULL,
          `slug` VARCHAR(150) DEFAULT NULL,
          `description` TEXT DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $cols = $db->query("SHOW COLUMNS FROM gallery_categories LIKE 'slug'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE gallery_categories ADD COLUMN slug VARCHAR(150) DEFAULT NULL AFTER category_name");
            $db->exec("UPDATE gallery_categories SET slug = LOWER(REPLACE(category_name, ' ', '-')) WHERE slug IS NULL OR slug = ''");
        }

        $cols_tg = $db->query("SHOW COLUMNS FROM trek_gallery LIKE 'category_id'")->fetchAll();
        if (empty($cols_tg)) {
            $db->exec("ALTER TABLE trek_gallery ADD COLUMN category_id INT DEFAULT NULL AFTER trek_id");
        }
    } catch (PDOException $e) {}

    // Fetch all active gallery items from trek_gallery
    $raw_stmt = $db->query("
        SELECT tg.image_path, tg.thumbnail_path, tg.trek_id, tg.category_id, tg.image_title as caption, 
               gc.category_name as gc_name, gc.slug as gc_slug,
               t.title as trek_title, t.slug as trek_slug
        FROM trek_gallery tg 
        LEFT JOIN gallery_categories gc ON tg.category_id = gc.id
        LEFT JOIN treks t ON tg.trek_id = t.id 
        ORDER BY tg.created_at DESC
    ");
    $raw_items = $raw_stmt->fetchAll();

    $items = [];
    $category_counts = [];
    $categories_map = [];

    foreach ($raw_items as $item) {
        $cat_name = !empty($item['gc_name']) ? $item['gc_name'] : (!empty($item['trek_title']) ? $item['trek_title'] : 'General');
        $raw_slug_src = !empty($item['gc_slug']) ? $item['gc_slug'] : (!empty($item['trek_slug']) ? $item['trek_slug'] : $cat_name);
        $cat_slug = create_slug($raw_slug_src);

        if (empty($cat_slug)) {
            $cat_slug = 'general';
        }

        $item['computed_slug'] = $cat_slug;
        $item['computed_title'] = $cat_name;
        $items[] = $item;

        $category_counts[$cat_slug] = ($category_counts[$cat_slug] ?? 0) + 1;

        if (!isset($categories_map[$cat_slug])) {
            $categories_map[$cat_slug] = [
                'slug'  => $cat_slug,
                'title' => $cat_name,
                'count' => 0
            ];
        }
    }

    foreach ($categories_map as $s => &$c_info) {
        $c_info['count'] = $category_counts[$s] ?? 0;
    }
    unset($c_info);

    $filters = array_values($categories_map);

} catch (PDOException $e) {
    $items = [];
    $filters = [];
}
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">Adventure Gallery</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Gallery</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Gallery Section -->
<section class="section-padding gallery-page">
    <div class="container">
        <div class="section-title">
            <span>Visual Glimpses</span>
            <h2>Moments from the Mountains</h2>
        </div>
        
        <div class="gallery-layout">
            <!-- Left Sidebar: Trek Categories -->
            <aside class="gallery-sidebar" id="gallery_sidebar">
                <div class="gallery-sidebar-header">
                    <i class="fas fa-layer-group me-2"></i>Trek Categories
                </div>
                <ul class="gallery-sidebar-list">
                    <li>
                        <a href="#" class="gallery-sidebar-link active" data-filter="all">
                            <span class="gallery-sidebar-text">
                                <i class="fas fa-images me-2"></i>All Photos
                            </span>
                            <span class="gallery-sidebar-meta">
                                <span class="gallery-sidebar-count"><?php echo count($items); ?></span>
                                <i class="fas fa-chevron-right gallery-sidebar-arrow"></i>
                            </span>
                        </a>
                    </li>
                    <?php foreach ($filters as $f): ?>
                        <li>
                            <a href="#" class="gallery-sidebar-link" data-filter="<?php echo htmlspecialchars($f['slug']); ?>">
                                <span class="gallery-sidebar-text"><?php echo htmlspecialchars($f['title']); ?></span>
                                <span class="gallery-sidebar-meta">
                                    <span class="gallery-sidebar-count"><?php echo $f['count']; ?></span>
                                    <i class="fas fa-chevron-right gallery-sidebar-arrow"></i>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <!-- Mobile Category Dropdown (visible only on mobile) -->
            <div class="gallery-mobile-dropdown" id="gallery_mobile_dropdown">
                <div class="gallery-custom-dropdown" id="gallery_custom_dropdown">
                    <button class="gallery-dropdown-trigger" id="gallery_dropdown_trigger" type="button">
                        <span class="gallery-dropdown-icon"><i class="fas fa-layer-group"></i></span>
                        <span class="gallery-dropdown-selected" id="gallery_dropdown_text">All Photos</span>
                        <span class="gallery-dropdown-badge" id="gallery_dropdown_badge"><?php echo count($items); ?></span>
                        <span class="gallery-dropdown-chevron"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="gallery-dropdown-panel" id="gallery_dropdown_panel">
                        <div class="gallery-dropdown-option active" data-value="all">
                            <i class="fas fa-images gallery-dropdown-opt-icon"></i>
                            <span class="gallery-dropdown-opt-text">All Photos</span>
                            <span class="gallery-dropdown-opt-count"><?php echo count($items); ?></span>
                        </div>
                        <?php foreach ($filters as $f): ?>
                            <div class="gallery-dropdown-option" data-value="<?php echo htmlspecialchars($f['slug']); ?>">
                                <i class="fas fa-mountain gallery-dropdown-opt-icon"></i>
                                <span class="gallery-dropdown-opt-text"><?php echo htmlspecialchars($f['title']); ?></span>
                                <span class="gallery-dropdown-opt-count"><?php echo $f['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Content: Gallery Grid -->
            <div class="gallery-content">
                <div class="gallery-content-header" id="gallery_content_header">
                    <h4 id="gallery_active_title"><i class="fas fa-images me-2"></i>All Photos</h4>
                    <span class="gallery-content-count" id="gallery_visible_count"><?php echo count($items); ?> photos</span>
                </div>

                <div class="row g-3 gallery-swipe-container" id="gallery_grid">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $index => $item): 
                            $category_slug = $item['computed_slug'] ?? 'general';
                        ?>
                            <div class="col-6 col-md-4 col-xl-4 gallery-col" data-category="<?php echo htmlspecialchars($category_slug); ?>">
                                <div class="gallery-grid-item" style="animation-delay: <?php echo ($index % 12) * 0.05; ?>s">
                                    <img src="<?php echo !empty($item['image_path']) ? (SITE_URL . '/' . $item['image_path']) : (SITE_URL . '/assets/images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['caption']); ?>" loading="lazy">
                                    <div class="gallery-item-overlay">
                                        <div class="gallery-item-info">
                                            <h5><?php echo htmlspecialchars($item['caption'] ?? 'Scenic View'); ?></h5>
                                            <?php if ($item['trek_title']): ?>
                                                <span class="gallery-item-trek"><i class="fas fa-mountain me-1"></i><?php echo htmlspecialchars($item['trek_title']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="gallery-item-zoom"><i class="fas fa-expand"></i></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Static Fallback / Mock Images -->
                        <?php 
                        $mock_gallery = [
                            ['title' => 'kudremukh-trek', 'caption' => 'Mist-covered Kudremukh valley', 'sub' => 'Kudremukh Trek'],
                            ['title' => 'kumara-parvatha-trek', 'caption' => 'The steep ridge of Pushpagiri', 'sub' => 'Kumara Parvatha Trek'],
                            ['title' => 'skandagiri-sunrise-trek', 'caption' => 'Skandagiri above the clouds at dawn', 'sub' => 'Skandagiri Sunrise Trek'],
                            ['title' => 'kudremukh-trek', 'caption' => 'Shola forests and stream crossings', 'sub' => 'Kudremukh Trek'],
                            ['title' => 'kumara-parvatha-trek', 'caption' => 'Bheema\'s rock campsite view', 'sub' => 'Kumara Parvatha Trek'],
                            ['title' => 'skandagiri-sunrise-trek', 'caption' => 'Fort ruins at Skandagiri peak', 'sub' => 'Skandagiri Sunrise Trek'],
                        ];
                        
                        foreach ($mock_gallery as $index => $mock): ?>
                            <div class="col-6 col-md-4 col-xl-4 gallery-col" data-category="<?php echo $mock['title']; ?>">
                                <div class="gallery-grid-item" style="animation-delay: <?php echo $index * 0.05; ?>s">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="<?php echo htmlspecialchars($mock['caption']); ?>" loading="lazy">
                                    <div class="gallery-item-overlay">
                                        <div class="gallery-item-info">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($mock['caption']); ?></h5>
                                            <span class="gallery-item-trek"><i class="fas fa-mountain me-1"></i><?php echo htmlspecialchars($mock['sub']); ?></span>
                                        </div>
                                        <span class="gallery-item-zoom"><i class="fas fa-expand"></i></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- No results message -->
                <div class="gallery-no-results" id="gallery_no_results" style="display: none;">
                    <i class="fas fa-camera-retro"></i>
                    <h4>No photos found</h4>
                    <p>Try selecting a different category from the sidebar</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
