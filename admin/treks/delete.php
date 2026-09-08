<?php
/**
 * Admin - Delete Trek Handler
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db = Database::connect();
        
        // Execute delete
        $stmt = $db->prepare("DELETE FROM treks WHERE id = ?");
        $stmt->execute([$id]);
        
        set_flash_message('success', 'Trek has been deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Failed to delete trek. It may have active booking records associated with it.');
    }
}

header('Location: ' . SITE_URL . '/admin/treks/manage.php');
exit();
?>
