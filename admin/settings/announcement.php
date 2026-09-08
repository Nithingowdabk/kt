<?php
/**
 * Admin - Header Announcement Bar Settings
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement_enabled = isset($_POST['announcement_enabled']) ? '1' : '0';
    $announcement_messages = sanitize_input($_POST['announcement_messages'] ?? '');
    $announcement_display_type = sanitize_input($_POST['announcement_display_type'] ?? 'Auto Scroll');
    $announcement_link = sanitize_input($_POST['announcement_link'] ?? '');
    $announcement_bg_color = sanitize_input($_POST['announcement_bg_color'] ?? '#198754');
    $announcement_text_color = sanitize_input($_POST['announcement_text_color'] ?? '#ffffff');

    try {
        $stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");
        
        $stmt->execute(['announcement_enabled', $announcement_enabled, $announcement_enabled]);
        $stmt->execute(['announcement_messages', $announcement_messages, $announcement_messages]);
        $stmt->execute(['announcement_display_type', $announcement_display_type, $announcement_display_type]);
        $stmt->execute(['announcement_link', $announcement_link, $announcement_link]);
        $stmt->execute(['announcement_bg_color', $announcement_bg_color, $announcement_bg_color]);
        $stmt->execute(['announcement_text_color', $announcement_text_color, $announcement_text_color]);
        
        log_activity('settings_update_announcement', [
            'announcement_enabled' => $announcement_enabled,
            'announcement_display_type' => $announcement_display_type
        ]);
        
        $success = "Header announcement configurations updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to save announcement settings: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Announcement Settings | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto">
                    <span class="text-muted small">Configure website header announcement strip</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4" style="max-width: 800px;">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h3 class="fw-bold mb-0">System Configurations</h3>
                
                <div class="btn-group btn-group-sm flex-wrap">
                    <a href="<?php echo SITE_URL; ?>/admin/settings/general.php" class="btn btn-outline-success">General</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/payment.php" class="btn btn-outline-success">Payments</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/seo.php" class="btn btn-outline-success">SEO tags</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/announcement.php" class="btn btn-success active">Announcement Bar</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/pages.php" class="btn btn-outline-success"><i class="fas fa-file-alt me-1"></i>Footer Pages & FAQs</a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="admin-card">
                    <h5 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="fas fa-bullhorn me-2"></i>Header Announcement Bar</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="announcement_enabled" id="announcement_enabled" value="1" <?php echo get_setting('announcement_enabled', '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold text-dark" for="announcement_enabled">Enable Top Announcement Bar</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Strip Text (Enter one message per line)</label>
                            <textarea name="announcement_messages" class="form-control" rows="5" placeholder="e.g.&#10;🚍 Free Transport Available&#10;🌄 Sunrise Treks Every Friday & Saturday&#10;🔥 Limited Seats Available"><?php echo htmlspecialchars(get_setting('announcement_messages', '')); ?></textarea>
                            <small class="text-muted d-block mt-1">If multiple messages are provided, they will rotate dynamically.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Display Type</label>
                            <select name="announcement_display_type" class="form-select">
                                <?php $disp = get_setting('announcement_display_type', 'Auto Scroll'); ?>
                                <option value="Static" <?php echo $disp === 'Static' ? 'selected' : ''; ?>>Static (Shows first message only)</option>
                                <option value="Auto Scroll" <?php echo $disp === 'Auto Scroll' ? 'selected' : ''; ?>>Auto Scroll (Fade/Slide loop)</option>
                                <option value="Marquee" <?php echo $disp === 'Marquee' ? 'selected' : ''; ?>>Marquee (Continuous scrolling text)</option>
                                <option value="Slider" <?php echo $disp === 'Slider' ? 'selected' : ''; ?>>Slider (Side-to-side transition)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Strip Link (Optional)</label>
                            <input type="url" name="announcement_link" class="form-control" value="<?php echo htmlspecialchars(get_setting('announcement_link', '')); ?>" placeholder="e.g. https://karnatakatrekkers.com/treks">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Background Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" style="max-width: 50px; padding: 6px;" name="announcement_bg_color_picker" id="announcement_bg_color_picker" value="<?php echo htmlspecialchars(get_setting('announcement_bg_color', '#198754')); ?>" oninput="document.getElementById('announcement_bg_color').value = this.value">
                                <input type="text" name="announcement_bg_color" id="announcement_bg_color" class="form-control" value="<?php echo htmlspecialchars(get_setting('announcement_bg_color', '#198754')); ?>" oninput="document.getElementById('announcement_bg_color_picker').value = this.value">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Text Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" style="max-width: 50px; padding: 6px;" name="announcement_text_color_picker" id="announcement_text_color_picker" value="<?php echo htmlspecialchars(get_setting('announcement_text_color', '#ffffff')); ?>" oninput="document.getElementById('announcement_text_color').value = this.value">
                                <input type="text" name="announcement_text_color" id="announcement_text_color" class="form-control" value="<?php echo htmlspecialchars(get_setting('announcement_text_color', '#ffffff')); ?>" oninput="document.getElementById('announcement_text_color_picker').value = this.value">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">Save Announcement Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

</body>
</html>
