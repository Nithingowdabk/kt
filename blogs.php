<?php
/**
 * Karnataka Trekkers - Blogs Catalog & Detail Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::connect();
$slug = sanitize_input($_GET['slug'] ?? '');

// 301 Canonical Redirect for legacy query URLs: /blogs.php?slug=xxx -> /blogs/xxx
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'blogs.php') !== false) {
    if (!empty($slug)) {
        header("Location: " . SITE_URL . "/blogs/" . $slug, true, 301);
    } else {
        header("Location: " . SITE_URL . "/blogs", true, 301);
    }
    exit();
}

if (!empty($slug)) {
    // Detail View
    try {
        $stmt = $db->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'Active'");
        $stmt->execute([$slug]);
        $blog = $stmt->fetch();
        
        if (!$blog) {
            // Blog not found redirection
            header('Location: ' . SITE_URL . '/blogs');
            exit();
        }
        
        $page_title = $blog['title'];
        $meta_title = !empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title'];
        $meta_desc = !empty($blog['meta_description']) ? $blog['meta_description'] : (!empty($blog['excerpt']) ? $blog['excerpt'] : substr(strip_tags($blog['content']), 0, 155));
        $meta_keywords = !empty($blog['tags']) ? $blog['tags'] : (!empty($blog['focus_keyphrase']) ? $blog['focus_keyphrase'] : null);
        $og_image = !empty($blog['image']) ? (SITE_URL . '/' . $blog['image']) : null;
        $og_image_alt = !empty($blog['image_alt']) ? $blog['image_alt'] : $blog['title'];
        
    } catch (PDOException $e) {
        $blog = null;
    }
} else {
    // List View
    $page_title = "Adventure Guides & Blogs";
    try {
        $stmt = $db->query("SELECT * FROM blogs WHERE status = 'Active' ORDER BY created_at DESC");
        $blogs = $stmt->fetchAll();
    } catch (PDOException $e) {
        $blogs = [];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3"><?php echo htmlspecialchars($page_title); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/" class="text-white-50 text-decoration-none">Home</a></li>
                <?php if (!empty($slug)): ?>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/blogs" class="text-white-50 text-decoration-none">Blogs</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Details</li>
                <?php else: ?>
                    <li class="breadcrumb-item text-white active" aria-current="page">Blogs</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
</section>

<!-- Main Container -->
<section class="section-padding">
    <div class="container">
        <?php if (!empty($slug) && isset($blog)): ?>
            <!-- BLOG DETAIL MODE -->
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <img src="<?php echo !empty($blog['image']) ? (SITE_URL . '/' . htmlspecialchars($blog['image'])) : (SITE_URL . '/assets/images/placeholder.jpg'); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($blog['image_alt'] ?? $blog['title']); ?>" style="aspect-ratio: 16 / 9; width: 100%; object-fit: cover;">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center gap-3 mb-4 text-muted" style="font-size: 0.9rem;">
                                <span><i class="far fa-user text-success me-1"></i><?php echo htmlspecialchars($blog['author']); ?></span>
                                <span><i class="far fa-calendar-alt text-success me-1"></i><?php echo format_date($blog['created_at']); ?></span>
                            </div>
                            
                            <h2 class="fw-bold mb-4"><?php echo htmlspecialchars($blog['title']); ?></h2>
                            
                            <!-- Content -->
                            <div class="blog-html-content">
                                <?php echo $blog['content']; ?>
                            </div>
                            
                            <!-- Tags Section -->
                            <?php if (!empty($blog['tags'])): 
                                $tagList = array_map('trim', explode(',', $blog['tags']));
                            ?>
                                <div class="mt-4 pt-3 border-top d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-muted small fw-bold"><i class="fas fa-tags text-success me-1"></i>Tags:</span>
                                    <?php foreach ($tagList as $tg): if (!empty($tg)): ?>
                                        <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-hashtag text-muted me-1 small"></i><?php echo htmlspecialchars($tg); ?></span>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?php echo SITE_URL; ?>/blogs" class="btn btn-outline-custom"><i class="fas fa-chevron-left me-2"></i>Back to Blogs</a>
                                <div class="share-icons d-flex align-items-center gap-2">
                                    <span class="text-muted small">Share: </span>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fab fa-whatsapp"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- BLOG LIST MODE -->
            <div class="row g-4">
                <?php if (!empty($blogs)): ?>
                    <?php foreach ($blogs as $post): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 border-light shadow-sm">
                                <img src="<?php echo !empty($post['image']) ? (SITE_URL . '/' . htmlspecialchars($post['image'])) : (SITE_URL . '/assets/images/placeholder.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['image_alt'] ?? $post['title']); ?>" style="aspect-ratio: 16 / 9; width: 100%; object-fit: cover;">
                                <div class="card-body">
                                    <small class="text-success fw-bold d-block mb-2"><?php echo format_date($post['created_at']); ?> | By <?php echo htmlspecialchars($post['author']); ?></small>
                                    <h5 class="card-title fw-bold">
                                        <a href="<?php echo SITE_URL; ?>/blogs/<?php echo $post['slug']; ?>" class="text-decoration-none text-dark hover-success"><?php echo htmlspecialchars($post['title']); ?></a>
                                    </h5>
                                    <p class="card-text text-muted" style="font-size: 0.9rem;">
                                        <?php 
                                            if (!empty($post['excerpt'])) {
                                                echo htmlspecialchars($post['excerpt']);
                                            } else {
                                                echo strip_tags(substr($post['content'], 0, 140)) . '...';
                                            }
                                        ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 p-3 pt-0">
                                    <a href="<?php echo SITE_URL; ?>/blogs/<?php echo $post['slug']; ?>" class="btn btn-link text-success fw-bold p-0 text-decoration-none">Read Full Article <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="far fa-folder-open text-muted fs-1 mb-3"></i>
                        <p class="text-muted">No blog articles are published yet. Stay tuned for guides!</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
