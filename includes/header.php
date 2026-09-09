<?php
/**
 * Shared Header Layout Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Load dynamic default settings from database
$default_title = get_setting('seo_default_title', DEFAULT_META_TITLE);
$default_desc = get_setting('seo_default_desc', DEFAULT_META_DESC);
$default_keywords = get_setting('seo_default_keywords', DEFAULT_META_KEYWORDS);
$site_name = get_setting('site_name', SITE_NAME);

// Fallback SEO Meta Tags
$site_title = isset($page_title) ? $page_title . ' | ' . $site_name : $default_title;
$meta_title = $meta_title ?? $default_title;
$meta_desc = $meta_desc ?? $default_desc;
$meta_keywords = $meta_keywords ?? $default_keywords;

// Build canonical URL
$current_url = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$canonical_url = $canonical_url ?? $current_url;
$og_image = $og_image ?? (SITE_URL . '/assets/images/hero-bg.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#2D5A27">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="QvgotIYffLzTAAXQZ6YdBi_5A6Zk4aHWttaVeZSSYfg">
    <meta name="google-site-verification" content="sbI_34xPrr4TztluniYs6PzBYMceQjfz-zvbONkh4FI">

    <!-- Primary Meta Tags -->
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <meta name="title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($og_image_alt ?? $site_title); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="twitter:image:alt" content="<?php echo htmlspecialchars($og_image_alt ?? $site_title); ?>">
    
    <!-- Favicon & Search Engine Site Icons (Google Favicon Spec) -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="48x48" href="<?php echo SITE_URL; ?>/assets/images/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo SITE_URL; ?>/assets/images/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo SITE_URL; ?>/assets/images/favicon-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png">

    <!-- Schema.org Organization Structured Data for Google Search -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo addslashes($site_name); ?>",
      "url": "<?php echo SITE_URL; ?>",
      "logo": "<?php echo SITE_URL; ?>/assets/images/LOGO.png",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "<?php echo CONTACT_PHONE; ?>",
        "contactType": "customer service"
      }
    }
    </script>
    
    <!-- Preconnect to external asset CDNs -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>

    <!-- Preload Critical Brand Font -->
    <link rel="preload" href="<?php echo SITE_URL; ?>/assets/fonts/outfit.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Preload Critical Hero LCP Image for Homepage -->
    <?php 
    $current_script = basename($_SERVER['PHP_SELF']);
    $req_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $is_homepage = ($current_script === 'index.php' || $req_uri === '/' || strpos($req_uri, '/index.php') !== false || parse_url($req_uri, PHP_URL_PATH) === '/' || parse_url($req_uri, PHP_URL_PATH) === '/KarnatakaTrekkers/');
    if ($is_homepage): 
    ?>
    <link rel="preload" as="image" href="<?php echo SITE_URL; ?>/assets/images/hero-bg-mobile.webp" media="(max-width: 768px)" type="image/webp" fetchpriority="high">
    <link rel="preload" as="image" href="<?php echo SITE_URL; ?>/assets/images/hero-bg.webp" media="(min-width: 769px)" type="image/webp" fetchpriority="high">
    <?php endif; ?>

    <!-- Inlined Critical Above-the-Fold CSS -->
    <style id="critical-css">
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
            src: url('<?php echo SITE_URL; ?>/assets/fonts/outfit.woff2') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        :root {
            --primary-color: #2D5A27;
            --primary-hover: #1E3F1A;
            --secondary-color: #8EB77F;
            --accent-color: #B84E00;
            --accent-hover: #963E00;
            --dark-color: #112211;
            --light-color: #F4F7F4;
            --white: #FFFFFF;
            --text-color: #2C3E50;
            --border-color: #E2E8F0;
            --card-shadow: 0 10px 30px rgba(17, 34, 17, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --font-heading: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-body: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        *, ::after, ::before { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-body);
            color: var(--text-color);
            background-color: #FCFDFD;
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
        }
        /* Grid System Essentials */
        .container, .container-fluid {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -7.5px;
            margin-left: -7.5px;
        }
        .g-2 {
            --bs-gutter-x: 0.5rem;
            --bs-gutter-y: 0.5rem;
            margin-right: calc(-0.5 * var(--bs-gutter-x));
            margin-left: calc(-0.5 * var(--bs-gutter-x));
        }
        .g-2 > * {
            padding-right: calc(var(--bs-gutter-x) * 0.5);
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            margin-top: var(--bs-gutter-y);
        }
        .justify-content-center { justify-content: center !important; }
        .position-relative { position: relative !important; }
        .w-100 { width: 100% !important; }
        .d-flex { display: flex !important; }
        .align-items-center { align-items: center !important; }
        .text-uppercase { text-transform: uppercase !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }
        .me-1 { margin-right: 0.25rem !important; }
        .px-3 { padding-left: 1rem !important; padding-right: 1rem !important; }
        .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }
        .bg-success { background-color: #198754 !important; }
        .fs-7 { font-size: 0.75rem !important; }

        /* Columns */
        .col-12 { flex: 0 0 auto; width: 100%; }
        @media (min-width: 768px) {
            .col-md-2 { flex: 0 0 auto; width: 16.66666667%; }
            .col-md-3 { flex: 0 0 auto; width: 25%; }
            .col-md-5 { flex: 0 0 auto; width: 41.66666667%; }
        }
        @media (min-width: 992px) {
            .col-lg-10 { flex: 0 0 auto; width: 83.33333333%; }
        }

        /* Display Utilities to Prevent CLS */
        .d-none { display: none !important; }
        .collapse:not(.show) { display: none !important; }

        /* Navbar */
        .navbar-custom {
            position: sticky !important;
            top: 0;
            height: 72px;
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            z-index: 1030 !important;
            padding: 0 !important;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .navbar-custom .container-fluid {
            max-width: 1400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 40px;
            text-decoration: none;
        }
        .navbar-brand img {
            width: 40px;
            height: 40px;
            max-height: 40px;
            aspect-ratio: 1 / 1;
            object-fit: contain;
        }
        @media (max-width: 991.98px) {
            .navbar-custom {
                height: 64px !important;
                padding: 0 !important;
            }
            .navbar-custom .container-fluid {
                flex-wrap: nowrap !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
            }
            .navbar-brand {
                font-size: 1.25rem !important;
                height: 38px !important;
                gap: 6px !important;
            }
            .navbar-brand img {
                max-height: 36px !important;
                width: 36px !important;
                height: 36px !important;
            }
            .navbar-collapse,
            .d-lg-flex {
                display: none !important;
            }
        }
        @media (min-width: 992px) {
            .d-lg-none { display: none !important; }
            .d-lg-flex { display: flex !important; }
            .navbar-collapse { display: flex !important; }
        }
        .navbar-call-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(45, 90, 39, 0.1);
            color: var(--primary-color) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        /* Hero Section */
        .hero-slider-section {
            position: relative;
            height: 90vh;
            min-height: 600px;
            background-color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-align: center;
            overflow: hidden;
        }
        .hero-bg-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .hero-bg-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(17, 34, 17, 0.45) 0%, rgba(17, 34, 17, 0.65) 100%);
            z-index: 1;
            pointer-events: none;
        }
        .hero-bg-wrapper picture,
        .hero-bg-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .hero-slider-section .hero-content {
            position: relative;
            z-index: 1;
            max-width: 960px;
            margin: 0 auto;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            line-height: 1.15;
            font-family: var(--font-heading);
        }
        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 35px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .hero-search-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.25);
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            width: 100%;
        }
        .input-group-text {
            display: flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            text-align: center;
            white-space: nowrap;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 0.375rem 0 0 0.375rem;
        }
        .form-control, .form-select {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        .form-select {
            padding-right: 2.25rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            appearance: none;
        }
        .border-0 { border: 0 !important; }
        .bg-white { background-color: #fff !important; }
        .text-success { color: #198754 !important; }
        .btn {
            display: inline-block;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.375rem;
        }
        .btn-accent {
            background-color: var(--accent-color);
            color: var(--white);
            font-weight: 700;
            border-radius: 8px;
            border: none;
        }
        @media (max-width: 768px) {
            .navbar-custom { height: 64px !important; }
            .hero-slider-section {
                height: auto;
                min-height: 480px;
                padding: 90px 0 50px;
            }
            .hero-content h1 {
                font-size: 1.85rem;
                line-height: 1.3;
                margin-bottom: 15px;
            }
            .hero-content p {
                font-size: 1rem;
                margin-bottom: 20px;
            }
            .hero-search-box {
                padding: 15px;
                border-radius: 10px;
            }
            .hero-search-box .row > div {
                margin-bottom: 8px;
            }
            .hero-search-box .col-md-5,
            .hero-search-box .col-md-3,
            .hero-search-box .col-md-2 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        @font-face {
            font-family: 'Font Awesome 6 Free';
            font-style: normal;
            font-weight: 900;
            font-display: swap;
            src: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Font Awesome 6 Brands';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2') format('woff2');
        }
        @media (max-width: 575px) {
            .hero-content h1 { font-size: 1.6rem !important; line-height: 1.25; }
            .hero-content p { font-size: 0.9rem; }
        }
    </style>

    <!-- Core Application Stylesheets (Pre-parsed in Head for Zero CLS) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css?v=5.3.3">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=2.1.2">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css?v=2.1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    </noscript>

    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>
<?php include_once __DIR__ . '/navbar.php'; ?>
<main id="main-content">
