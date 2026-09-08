<?php
/**
 * Shared Admin Sidebar Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Check path active for sidebar highlights
function is_admin_active($path) {
    $current_uri = $_SERVER['REQUEST_URI'];
    return (strpos($current_uri, $path) !== false) ? 'active' : '';
}
?>
<div class="border-end" id="sidebar-wrapper">
    <div class="sidebar-heading">
        <i class="fas fa-hiking text-success me-2"></i>KT <span>Admin</span>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/admin/dashboard.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        
        <a href="<?php echo SITE_URL; ?>/admin/treks/manage.php" class="list-group-item list-group-item-action <?php echo (is_admin_active('/admin/treks/') && strpos($_SERVER['REQUEST_URI'], 'categories.php') === false) ? 'active' : ''; ?>">
            <i class="fas fa-route"></i> Manage Treks
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/treks/categories.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/treks/categories.php') ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Trek Categories
        </a>
        
        <a href="<?php echo SITE_URL; ?>/admin/bookings/manage.php" class="list-group-item list-group-item-action <?php echo (is_admin_active('/admin/bookings/') && strpos($_SERVER['REQUEST_URI'], 'callback-requests.php') === false) ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i> Bookings
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/bookings/callback-requests.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/bookings/callback-requests.php') ? 'active' : ''; ?>">
            <i class="fas fa-phone-volume"></i> Callback Requests
        </a>
        
        <a href="<?php echo SITE_URL; ?>/admin/users/manage.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/users/') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        
        <div class="list-group-item list-group-item-action text-muted d-flex justify-content-between align-items-center" style="opacity: 0.65; cursor: not-allowed; background-color: #fafafa;" title="Paid Tool (PRO Feature)">
            <span><i class="fas fa-percentage me-2"></i>Coupons</span>
            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;"><i class="fas fa-lock me-1"></i>PRO</span>
        </div>

        <a href="<?php echo SITE_URL; ?>/admin/blogs/manage.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/blogs/') ? 'active' : ''; ?>">
            <i class="fas fa-pen-nib"></i> Blogs
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/reviews/manage.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/reviews/') ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Reviews & Ratings
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/gallery/manage.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/gallery/') ? 'active' : ''; ?>">
            <i class="fas fa-images"></i> Media Gallery
        </a>

        <!-- Settings Group Collapsed/Direct Links -->
        <a href="<?php echo SITE_URL; ?>/admin/settings/general.php" class="list-group-item list-group-item-action <?php echo is_admin_active('/admin/settings/') ? 'active' : ''; ?>">
            <i class="fas fa-cogs"></i> Website Settings
        </a>
        
        <a href="<?php echo SITE_URL; ?>/index.php" class="list-group-item list-group-item-action text-info" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Website
        </a>

        <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger mt-5">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</div>
