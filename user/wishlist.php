<?php
/**
 * User Wishlist Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login
require_user_login();

$user_id = get_logged_in_user_id();
$db = Database::connect();

// 1. Handle add/remove actions
$action = sanitize_input($_GET['action'] ?? '');
if ($action === 'add' && isset($_GET['trek_id'])) {
    $trek_id = (int)$_GET['trek_id'];
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO wishlist (user_id, trek_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $trek_id]);
        set_flash_message('success', 'Trek added to your wishlist!');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error adding to wishlist.');
    }
    header('Location: ' . SITE_URL . '/user/wishlist.php');
    exit();
}

if ($action === 'remove') {
    try {
        if (isset($_GET['id'])) {
            $wishlist_id = (int)$_GET['id'];
            $stmt = $db->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
            $stmt->execute([$wishlist_id, $user_id]);
        } elseif (isset($_GET['trek_id'])) {
            $trek_id = (int)$_GET['trek_id'];
            $stmt = $db->prepare("DELETE FROM wishlist WHERE trek_id = ? AND user_id = ?");
            $stmt->execute([$trek_id, $user_id]);
        }
        set_flash_message('success', 'Trek removed from your wishlist.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error removing from wishlist.');
    }
    header('Location: ' . SITE_URL . '/user/wishlist.php');
    exit();
}

// 2. Fetch Wishlist Items
try {
    $stmt = $db->prepare("SELECT w.id as wishlist_id, t.* FROM wishlist w 
                          INNER JOIN treks t ON w.trek_id = t.id 
                          WHERE w.user_id = ? AND t.status = 'Active'");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

$page_title = "My Wishlist";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="row g-4">
        
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center user-sidebar-header mb-4 pb-3 border-bottom">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="far fa-user"></i>
                    </div>
                    <div class="user-sidebar-info overflow-hidden" style="min-width: 0;">
                        <h5 class="fw-bold mb-0 text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                        <small class="text-muted d-block text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_email']); ?>"><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/user/bookings.php" class="list-group-item list-group-item-action"><i class="fas fa-hiking me-2"></i>My Bookings</a>
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action"><i class="fas fa-id-card me-2"></i>Profile Details</a>
                    <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="list-group-item list-group-item-action active"><i class="fas fa-heart me-2"></i>Wishlist</a>
                    <a href="<?php echo SITE_URL; ?>/user/settings.php" class="list-group-item list-group-item-action"><i class="fas fa-cogs me-2"></i>Settings</a>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>

        <!-- Wishlist List -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-4 text-success border-bottom pb-2"><i class="fas fa-heart me-2"></i>Saved Treks Wishlist</h4>
                
                <?php echo get_flash_message(); ?>

                <div class="row g-3">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-6">
                                <div class="card border border-light shadow-sm h-100 d-flex flex-row overflow-hidden">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" style="width: 120px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <span class="text-success small fw-bold d-block mb-1">
                                                <?php echo format_price(get_starting_price($item)); ?>
                                            </span>
                                            <small class="text-muted d-block mb-2"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars($item['duration']); ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="<?php echo SITE_URL; ?>/treks/<?php echo $item['slug']; ?>" class="btn btn-sm btn-success py-1">Book Now</a>
                                            <a href="<?php echo SITE_URL; ?>/user/wishlist.php?action=remove&id=<?php echo $item['wishlist_id']; ?>" class="text-danger small text-decoration-none">
                                                <i class="fas fa-trash-alt me-1"></i>Remove
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="far fa-heart display-4 text-muted mb-3 d-block"></i>
                            <p class="text-muted">Your wishlist is empty. Discover new routes and save them here!</p>
                            <a href="<?php echo SITE_URL; ?>/treks/index.php" class="btn btn-primary-custom btn-sm mt-3">Explore Treks</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
