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
                                <i class="fab fa-instagram text-danger fs-5" style="width: 22px;"></i>
                                <span class="fw-semibold">Instagram</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-youtube text-danger fs-5" style="width: 22px;"></i>
                                <span class="fw-semibold">YouTube</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-facebook text-primary fs-5" style="width: 22px;"></i>
                                <span class="fw-semibold">Facebook</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2" href="https://wa.me/918951221179" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-whatsapp text-success fs-5" style="width: 22px;"></i>
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
                            <i class="fab fa-instagram text-danger" style="width: 20px;"></i> Instagram
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-youtube text-danger" style="width: 20px;"></i> YouTube
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-facebook text-primary" style="width: 20px;"></i> Facebook
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="https://wa.me/918951221179" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp text-success" style="width: 20px;"></i> WhatsApp
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
