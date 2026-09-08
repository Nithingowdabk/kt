<?php
/**
 * Authentication and Session Helper Functions
 */

require_once __DIR__ . '/config.php';

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a regular user is logged in
 */
function is_user_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Restrict page access to logged in users only
 */
function require_user_login() {
    if (!is_user_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        set_flash_message('danger', 'Please login to access this page.');
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

/**
 * Get current logged in user ID
 */
function get_logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if an admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Restrict page access to logged in admins only
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
}

/**
 * Get current logged in admin ID or details
 */
function get_logged_in_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get logged in user name or default
 */
function get_logged_in_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Check if a trek leader is logged in
 */
function is_leader_logged_in() {
    return isset($_SESSION['leader_id']) && !empty($_SESSION['leader_id']);
}

/**
 * Restrict page access to logged in trek leaders only
 */
function require_leader_login() {
    if (!is_leader_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        set_flash_message('danger', 'Please login to access the Trek Leader Portal.');
        header('Location: ' . SITE_URL . '/admin/trek-leaders/login.php');
        exit();
    }
}

/**
 * Get current logged in trek leader ID
 */
function get_logged_in_leader_id() {
    return $_SESSION['leader_id'] ?? null;
}

/**
 * Sanitize a redirect URL to prevent open redirect vulnerabilities
 */
function sanitize_redirect_url($url) {
    if (empty($url)) {
        return SITE_URL . '/user/dashboard.php';
    }
    
    // Check if it's an absolute URL
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $parsed = parse_url($url);
        $parsed_site = parse_url(SITE_URL);
        
        // Host and port must match SITE_URL
        if (isset($parsed['host']) && isset($parsed_site['host']) && $parsed['host'] === $parsed_site['host']) {
            return $url;
        }
        return SITE_URL . '/user/dashboard.php';
    }
    
    // If it's a relative path starting with /
    if (strpos($url, '/') === 0) {
        // Prevent protocol-relative redirects (starting with //)
        if (strpos($url, '//') === 0) {
            return SITE_URL . '/user/dashboard.php';
        }
        // It's a safe relative path
        return $url;
    }
    
    return SITE_URL . '/user/dashboard.php';
}


