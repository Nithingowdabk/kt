<?php
/**
 * Karnataka Trekkers - Logout Handler
 */
require_once __DIR__ . '/includes/config.php';

// Clear sessions
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to home
header('Location: ' . SITE_URL . '/index.php');
exit();
?>
