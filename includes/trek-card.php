<?php
/**
 * Reusable Trek Card Component - Single Source of Truth
 * Expected variables:
 * - $trek (array): The trek data row.
 */
if (isset($trek)):
    $difficulty_class = 'difficulty-' . strtolower($trek['difficulty'] ?? 'easy');
    $active_card_price = (float)get_starting_price($trek);
    
    // Fallback image handling
    // Fallback image handling and WebP detection
    $featured_img = 'assets/images/default-trek.jpg';
    if (!empty($trek['gallery_featured_image'])) {
        $featured_img = $trek['gallery_featured_image'];
    } elseif (!empty($trek['image'])) {
        $featured_img = $trek['image'];
    }
    
    // Check for optimized WebP variants
    $img_info = pathinfo($featured_img);
    $webp_card = $img_info['dirname'] . '/' . $img_info['filename'] . '_480.webp';
    $webp_full = $img_info['dirname'] . '/' . $img_info['filename'] . '.webp';
    $webp_src = null;
    if (file_exists(__DIR__ . '/../' . $webp_card)) {
        $webp_src = $webp_card;
    } elseif (file_exists(__DIR__ . '/../' . $webp_full)) {
        $webp_src = $webp_full;
    }
    
    $card_url = SITE_URL . '/treks/' . ($trek['slug'] ?? '');
    
    // Metadata labels
    $category_label = !empty($trek['category_name']) ? $trek['category_name'] : 'General';
    $duration_label = !empty($trek['duration']) ? $trek['duration'] : '';
    $meta_label = !empty($duration_label) ? $category_label . ' • ' . $duration_label : $category_label;
    // Check wishlist status for currently logged-in user
    $is_in_wishlist = false;
    $wishlist_id = null;
    if (is_user_logged_in()) {
        try {
            $db = Database::connect();
            $wish_stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND trek_id = ? LIMIT 1");
            $wish_stmt->execute([get_logged_in_user_id(), $trek['id']]);
            $wish_row = $wish_stmt->fetch();
            if ($wish_row) {
                $is_in_wishlist = true;
                $wishlist_id = $wish_row['id'];
            }
        } catch (PDOException $e) {
            // Silence DB error
        }
    }
?>
<div class="trek-card-wrapper">
    <a href="<?php echo $card_url; ?>" class="trek-card-link">
        <div class="trek-card">
            <div class="trek-card-image">
                <picture>
                    <?php if ($webp_src): ?>
                        <source srcset="<?php echo SITE_URL . '/' . $webp_src; ?>" type="image/webp">
                    <?php endif; ?>
                    <img src="<?php echo SITE_URL . '/' . $featured_img; ?>" alt="<?php echo htmlspecialchars(!empty($trek['image_alt']) ? $trek['image_alt'] : ($trek['title'] ?? '')); ?>" width="452" height="226" loading="lazy" decoding="async">
                </picture>
                <?php if (!empty($trek['difficulty'])): ?>
                    <span class="trek-badge-difficulty <?php echo 'difficulty-' . strtolower($trek['difficulty']); ?>"><?php echo htmlspecialchars($trek['difficulty']); ?></span>
                <?php endif; ?>
            </div>
            <div class="trek-card-body">
                <h3 class="trek-card-title fw-bold">
                    <?php echo htmlspecialchars($trek['title'] ?? ''); ?>
                </h3>
                <div class="trek-card-meta">
                    <?php echo htmlspecialchars($meta_label); ?>
                </div>
                
                <?php if ($active_card_price > 0): ?>
                    <div class="trek-card-price-wrapper">
                        <div class="trek-card-price-label">Starting from</div>
                        <div class="trek-card-price"><?php echo format_price($active_card_price); ?></div>
                    </div>
                <?php endif; ?>
                
                <span class="trek-card-button mt-3">View Details</span>
            </div>
        </div>
    </a>
    
    <!-- Wishlist Heart Overlay Button -->
    <div class="wishlist-overlay-wrapper">
        <?php if (is_user_logged_in()): ?>
            <?php if ($is_in_wishlist): ?>
                <a href="<?php echo SITE_URL; ?>/user/wishlist.php?action=remove&trek_id=<?php echo $trek['id']; ?>" class="wishlist-btn active" title="Remove from Wishlist" aria-label="Remove <?php echo htmlspecialchars($trek['title'] ?? 'trek'); ?> from Wishlist" data-trek-id="<?php echo $trek['id']; ?>">
                    <i class="fas fa-heart"></i>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/user/wishlist.php?action=add&trek_id=<?php echo $trek['id']; ?>" class="wishlist-btn" title="Add to Wishlist" aria-label="Add <?php echo htmlspecialchars($trek['title'] ?? 'trek'); ?> to Wishlist" data-trek-id="<?php echo $trek['id']; ?>">
                    <i class="far fa-heart"></i>
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/login.php" class="wishlist-btn" title="Login to save Trek" aria-label="Login to save <?php echo htmlspecialchars($trek['title'] ?? 'trek'); ?> to wishlist">
                <i class="far fa-heart"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
