<?php
/**
 * Shared Navbar Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Helper function to check active page
function is_active_nav($page_name) {
    $current_uri = $_SERVER['REQUEST_URI'];
    return (strpos($current_uri, $page_name) !== false) ? 'active' : '';
}
?>
<?php
// Retrieve announcement settings
$ann_enabled = false;
if (function_exists('get_setting')) {
    $ann_enabled = get_setting('announcement_enabled', '0') === '1';
    $ann_messages_raw = get_setting('announcement_messages', '');
    $ann_display_type = get_setting('announcement_display_type', 'Auto Scroll');
    $ann_link = get_setting('announcement_link', '');
    $ann_bg = get_setting('announcement_bg_color', '#198754');
    $ann_color = get_setting('announcement_text_color', '#ffffff');
}

// Split messages by newlines
$ann_messages = [];
if ($ann_enabled && !empty($ann_messages_raw)) {
    $lines = preg_split('/\r\n|\r|\n/', $ann_messages_raw);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '') {
            $ann_messages[] = $trimmed;
        }
    }
}

if ($ann_enabled && !empty($ann_messages)):
    $ann_container_style = "background-color: " . htmlspecialchars($ann_bg) . "; color: " . htmlspecialchars($ann_color) . ";";
?>
<div class="announcement-bar-wrapper" style="<?php echo $ann_container_style; ?>">
    <div class="container-fluid px-4 py-2 text-center position-relative">
        <?php if (!empty($ann_link)): ?>
            <a href="<?php echo htmlspecialchars($ann_link); ?>" class="announcement-bar-link" style="color: <?php echo htmlspecialchars($ann_color); ?>;">
        <?php endif; ?>
        
        <div class="announcement-bar-content announcement-type-<?php echo strtolower(str_replace(' ', '-', $ann_display_type)); ?>">
            <?php if ($ann_display_type === 'Marquee'): ?>
                <div class="announcement-marquee-track">
                    <span class="announcement-marquee-text"><?php echo htmlspecialchars(implode('    |    ', $ann_messages)); ?></span>
                </div>
            <?php elseif ($ann_display_type === 'Static'): ?>
                <div class="announcement-message active">
                    <?php echo htmlspecialchars($ann_messages[0]); ?>
                </div>
            <?php else: // Auto Scroll or Slider ?>
                <div class="announcement-messages-slider">
                    <?php foreach ($ann_messages as $idx => $msg): ?>
                        <div class="announcement-message <?php echo $idx === 0 ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($ann_link)): ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-nowrap">
        <a class="navbar-brand text-truncate" href="<?php echo SITE_URL; ?>/">
            <picture>
                <source srcset="<?php echo SITE_URL; ?>/assets/images/LOGO.webp" type="image/webp">
                <img src="<?php echo SITE_URL; ?>/assets/images/LOGO.png" alt="Karnataka Trekkers Logo" width="40" height="40" class="d-inline-block align-middle me-2 flex-shrink-0" style="width: 40px; height: 40px; object-fit: contain;">
            </picture>
            <span class="text-nowrap">Karnataka Trekkers</span>
        </a>
        
        <!-- Mobile Actions (Call & Hamburger in one flex container) -->
        <div class="d-flex align-items-center gap-2 d-lg-none flex-shrink-0 ms-auto">
            <a class="navbar-call-btn" href="tel:<?php echo SITE_PHONE; ?>" aria-label="Call Karnataka Trekkers" title="Call Us&#10;+91 89512 21179">
                <i class="fas fa-phone-alt"></i>
            </a>
            <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNavbarOffcanvas" aria-controls="mainNavbarOffcanvas" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        
        <!-- Desktop Navigation Menu (Visible only on Large screens) -->
        <div class="collapse navbar-collapse d-none d-lg-flex" id="desktopNavbar">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_nav('/treks') && basename($_SERVER['PHP_SELF']) != 'index.php' && strpos($_SERVER['REQUEST_URI'], 'filter=upcoming') === false ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/treks">Treks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'gallery') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/gallery">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'contact') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact">Contact</a>
                </li>
                
                <!-- Socials Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" id="socialsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-share-alt text-success me-1"></i>Socials
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" aria-labelledby="socialsDropdown" style="min-width: 200px; border-radius: 12px;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://www.instagram.com/karnataka__trekkers?igsh=ejN1YTA1bTJneDJ5&utm_source=qr" target="_blank" rel="noopener noreferrer">
                                <span class="d-inline-flex text-danger" style="width: 22px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></span>
                                <span class="fw-semibold">Instagram</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer">
                                <span class="d-inline-flex text-danger" style="width: 22px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></span>
                                <span class="fw-semibold">YouTube</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
                                <span class="d-inline-flex text-primary" style="width: 22px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></span>
                                <span class="fw-semibold">Facebook</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://wa.me/918951221179" target="_blank" rel="noopener noreferrer">
                                <span class="d-inline-flex text-success" style="width: 22px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44s-.56-1.35-.77-1.85c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.78 2.72 4.31 3.81.6.26 1.07.42 1.44.54.61.19 1.16.17 1.6.1.49-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.07-.11-.22-.17-.47-.3"/></svg></span>
                                <span class="fw-semibold">WhatsApp</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <?php if (is_user_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'wishlist') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/user/wishlist">Wishlist</a>
                    </li>
                <?php endif; ?>
                
                <?php if (is_admin_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="<?php echo SITE_URL; ?>/admin/dashboard.php" style="white-space: nowrap;">
                            <i class="fas fa-user-shield me-1"></i>Admin Dashboard
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Desktop Call Button -->
                <li class="nav-item ms-lg-2 me-lg-2">
                    <a class="navbar-call-btn" href="tel:<?php echo SITE_PHONE; ?>" aria-label="Call Karnataka Trekkers" title="Call Us&#10;+91 89512 21179">
                        <i class="fas fa-phone-alt"></i>
                    </a>
                </li>

                <?php if (is_user_logged_in()): 
                    $full_user_name = trim($_SESSION['user_name'] ?? 'Trekker');
                    $first_user_name = explode(' ', $full_user_name)[0];
                    if (mb_strlen($first_user_name) > 12) {
                        $short_user_name = mb_substr($first_user_name, 0, 10) . '...';
                    } else {
                        $short_user_name = $first_user_name;
                    }
                ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle btn btn-outline-custom py-1 px-3 d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars($full_user_name); ?>">
                            <i class="fas fa-user-circle flex-shrink-0"></i> 
                            <span class="user-name-truncate"><?php echo htmlspecialchars($short_user_name); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDropdown" style="min-width: 220px;">
                            <li class="px-3 py-2 border-bottom bg-light">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Signed in as</small>
                                <strong class="text-dark d-block text-truncate" title="<?php echo htmlspecialchars($full_user_name); ?>"><?php echo htmlspecialchars($full_user_name); ?></strong>
                            </li>
                            <li><a class="dropdown-menu-item dropdown-item mt-1" href="<?php echo SITE_URL; ?>/user/dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-menu-item dropdown-item" href="<?php echo SITE_URL; ?>/user/bookings"><i class="fas fa-hiking me-2"></i>My Bookings</a></li>
                            <li><a class="dropdown-menu-item dropdown-item" href="<?php echo SITE_URL; ?>/user/profile"><i class="fas fa-id-card me-2"></i>Profile Details</a></li>
                            <li><a class="dropdown-menu-item dropdown-item" href="<?php echo SITE_URL; ?>/user/wishlist"><i class="fas fa-heart me-2"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-menu-item dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-custom py-1 px-3" href="<?php echo SITE_URL; ?>/login">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Offcanvas Drawer (Placed outside the navbar to prevent backdrop issues) -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mainNavbarOffcanvas" aria-labelledby="mainNavbarOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-success fw-bold d-flex align-items-center" id="mainNavbarOffcanvasLabel">
            <picture>
                <source srcset="<?php echo SITE_URL; ?>/assets/images/LOGO.webp" type="image/webp">
                <img src="<?php echo SITE_URL; ?>/assets/images/LOGO.png" alt="Karnataka Trekkers Logo" width="30" height="30" class="me-2" style="width: 30px; height: 30px; object-fit: contain;">
            </picture>
            <span>Karnataka Trekkers</span>
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between p-0">
        <ul class="navbar-nav align-items-stretch gap-1 px-3 py-2 w-100">
            <li class="nav-item">
                <a class="nav-link w-100-mobile-nav <?php echo is_active_nav('/treks') && basename($_SERVER['PHP_SELF']) != 'index.php' && strpos($_SERVER['REQUEST_URI'], 'filter=upcoming') === false ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/treks">Treks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link w-100-mobile-nav <?php echo (strpos($_SERVER['REQUEST_URI'], 'gallery') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/gallery">Gallery</a>
            </li>
            <li class="nav-item">
                <a class="nav-link w-100-mobile-nav <?php echo (strpos($_SERVER['REQUEST_URI'], 'contact') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact">Contact</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle w-100-mobile-nav d-flex align-items-center justify-content-between" href="#" id="mobileSocialsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><i class="fas fa-share-alt text-success me-2"></i>Socials</span>
                </a>
                <ul class="dropdown-menu border-0 shadow-sm ps-3 bg-light w-100 mt-1" aria-labelledby="mobileSocialsDropdown">
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://www.instagram.com/karnataka__trekkers?igsh=ejN1YTA1bTJneDJ5&utm_source=qr" target="_blank" rel="noopener noreferrer">
                            <span class="d-inline-flex text-danger" style="width: 20px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></span> Instagram
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer">
                            <span class="d-inline-flex text-danger" style="width: 20px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></span> YouTube
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
                            <span class="d-inline-flex text-primary" style="width: 20px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></span> Facebook
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://wa.me/918951221179" target="_blank" rel="noopener noreferrer">
                            <span class="d-inline-flex text-success" style="width: 20px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44s-.56-1.35-.77-1.85c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.78 2.72 4.31 3.81.6.26 1.07.42 1.44.54.61.19 1.16.17 1.6.1.49-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.07-.11-.22-.17-.47-.3"/></svg></span> WhatsApp
                        </a>
                    </li>
                </ul>
            </li>
            <?php if (is_admin_logged_in()): ?>
                <li class="nav-item">
                    <a class="nav-link w-100-mobile-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Mobile-Only bottom Profile/Authentication Section -->
        <div class="mt-auto p-4 border-top bg-light w-100">
            <?php if (is_user_logged_in() || is_admin_logged_in()): ?>
                <div class="user-profile-mobile">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-user-circle text-success fs-4"></i>
                        <span class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Trekker'); ?>"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Trekker'); ?></span>
                    </div>
                    <div class="row g-2">
                        <?php if (is_admin_logged_in()): ?>
                            <div class="col-12">
                                <a class="btn btn-warning btn-sm w-100 fw-bold py-2 rounded-3" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                    <i class="fas fa-user-shield me-1"></i>Admin Dashboard
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (is_user_logged_in()): ?>
                            <div class="col-6">
                                <a class="btn btn-outline-success btn-sm w-100 py-2 rounded-3" href="<?php echo SITE_URL; ?>/user/dashboard">Dashboard</a>
                            </div>
                            <div class="col-6">
                                <a class="btn btn-outline-success btn-sm w-100 py-2 rounded-3" href="<?php echo SITE_URL; ?>/user/bookings">My Bookings</a>
                            </div>
                            <div class="col-12 mt-2">
                                <a class="btn btn-outline-success btn-sm w-100 py-2 rounded-3" href="<?php echo SITE_URL; ?>/user/wishlist">
                                    <i class="fas fa-heart me-1"></i>Wishlist
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 mt-2">
                            <a class="btn btn-outline-danger btn-sm w-100 py-2 rounded-3" href="<?php echo SITE_URL; ?>/logout">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <a class="btn btn-outline-custom w-100 py-2.5 fw-semibold rounded-3 text-center" href="<?php echo SITE_URL; ?>/login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sticky navbar: spacer retired -->
