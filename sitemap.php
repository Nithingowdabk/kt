<?php
/**
 * Karnataka Trekkers - Dynamic XML Sitemap Generator
 * Automatically indexes all static pages, active treks, categories, and blogs.
 */
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::connect();
$today = date('Y-m-d');

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- 1. Main Static Pages -->
    <url>
        <loc><?php echo SITE_URL; ?>/</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/treks</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/blogs</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/gallery</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/about</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/contact</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/faq</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/terms</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/privacy</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/cancellation</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- 2. Dynamic Active Treks -->
    <?php
    try {
        $trek_stmt = $db->query("SELECT slug, title, image, updated_at, created_at FROM treks WHERE status = 'Active' ORDER BY id DESC");
        while ($trek = $trek_stmt->fetch()):
            $lastmod = !empty($trek['updated_at']) ? date('Y-m-d', strtotime($trek['updated_at'])) : (!empty($trek['created_at']) ? date('Y-m-d', strtotime($trek['created_at'])) : $today);
            $img_url = !empty($trek['image']) ? (SITE_URL . '/' . $trek['image']) : '';
    ?>
    <url>
        <loc><?php echo SITE_URL; ?>/treks/<?php echo htmlspecialchars($trek['slug']); ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
        <?php if (!empty($img_url)): ?>
        <image:image>
            <image:loc><?php echo htmlspecialchars($img_url); ?></image:loc>
            <image:title><?php echo htmlspecialchars($trek['title']); ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php 
        endwhile;
    } catch (PDOException $e) {
        // Continue if error
    }
    ?>

    <!-- 3. Dynamic Trek Categories -->
    <?php
    try {
        $cat_stmt = $db->query("SELECT slug, name, updated_at, created_at FROM trek_categories WHERE status = 'Active' ORDER BY id DESC");
        while ($cat = $cat_stmt->fetch()):
            $cat_lastmod = !empty($cat['updated_at']) ? date('Y-m-d', strtotime($cat['updated_at'])) : $today;
    ?>
    <url>
        <loc><?php echo SITE_URL; ?>/category/<?php echo htmlspecialchars($cat['slug']); ?></loc>
        <lastmod><?php echo $cat_lastmod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php 
        endwhile;
    } catch (PDOException $e) {
        // Continue if error
    }
    ?>

    <!-- 4. Dynamic Active Blogs -->
    <?php
    try {
        $blog_stmt = $db->query("SELECT slug, title, image, updated_at, created_at FROM blogs WHERE status = 'Active' ORDER BY id DESC");
        while ($blog = $blog_stmt->fetch()):
            $blog_lastmod = !empty($blog['updated_at']) ? date('Y-m-d', strtotime($blog['updated_at'])) : (!empty($blog['created_at']) ? date('Y-m-d', strtotime($blog['created_at'])) : $today);
            $blog_img_url = !empty($blog['image']) ? (SITE_URL . '/' . $blog['image']) : '';
    ?>
    <url>
        <loc><?php echo SITE_URL; ?>/blogs/<?php echo htmlspecialchars($blog['slug']); ?></loc>
        <lastmod><?php echo $blog_lastmod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <?php if (!empty($blog_img_url)): ?>
        <image:image>
            <image:loc><?php echo htmlspecialchars($blog_img_url); ?></image:loc>
            <image:title><?php echo htmlspecialchars($blog['title']); ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php 
        endwhile;
    } catch (PDOException $e) {
        // Continue if error
    }
    ?>

</urlset>
