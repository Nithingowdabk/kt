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
    $webp_320 = $img_info['dirname'] . '/' . $img_info['filename'] . '_320.webp';
    $webp_480 = $img_info['dirname'] . '/' . $img_info['filename'] . '_480.webp';
    $webp_full = $img_info['dirname'] . '/' . $img_info['filename'] . '.webp';

    $srcset_entries = [];
    if (file_exists(__DIR__ . '/../' . $webp_320)) {
        $srcset_entries[] = SITE_URL . '/' . $webp_320 . ' 320w';
    }
    if (file_exists(__DIR__ . '/../' . $webp_480)) {
        $srcset_entries[] = SITE_URL . '/' . $webp_480 . ' 480w';
    }
    if (file_exists(__DIR__ . '/../' . $webp_full)) {
        $srcset_entries[] = SITE_URL . '/' . $webp_full . ' 800w';
    }
    $webp_srcset = !empty($srcset_entries) ? implode(', ', $srcset_entries) : null;
    $webp_fallback = !empty($srcset_entries) ? (file_exists(__DIR__ . '/../' . $webp_320) ? $webp_320 : ($webp_480 ?? $webp_full)) : null;
    
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
                    <?php if ($webp_srcset): ?>
                        <source type="image/webp" 
                                srcset="<?php echo $webp_srcset; ?>" 
                                sizes="(max-width: 576px) 258px, (max-width: 992px) 40vw, 452px">
                    <?php endif; ?>
                    <img src="<?php echo SITE_URL . '/' . ($webp_fallback ?: $featured_img); ?>" alt="<?php echo htmlspecialchars(!empty($trek['image_alt']) ? $trek['image_alt'] : ($trek['title'] ?? '')); ?>" width="452" height="226" loading="lazy" decoding="async">
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
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/user/wishlist.php?action=add&trek_id=<?php echo $trek['id']; ?>" class="wishlist-btn" title="Add to Wishlist" aria-label="Add <?php echo htmlspecialchars($trek['title'] ?? 'trek'); ?> to Wishlist" data-trek-id="<?php echo $trek['id']; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/login.php" class="wishlist-btn" title="Login to save Trek" aria-label="Login to save <?php echo htmlspecialchars($trek['title'] ?? 'trek'); ?> to wishlist">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
