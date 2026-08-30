<?php
/**
 * landing-page-template.php
 *
 * ISOLATED HIGH-SPEED 9:16 VERTICAL REELS LANDING PAGE TEMPLATE
 * ============================================================
 *
 * 0ms Theme Bypass: Completely isolated from WordPress core theme headers,
 * footers, sidebars, and unnecessary scripts. Delivers 0ms instantaneous TTFB
 * and 100% mobile-optimized 9:16 reels experience.
 *
 * @package RocketSlide_Landing_Page
 * @since   3.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$tab_title       = get_option('rocketslide_tab_title', '');
$tracking_script = get_option('rocketslide_tracking_script', '');
$fallback_url    = get_option('rocketslide_fallback_url', 'https://google.com');
$images          = get_option('rocketslide_images', array());

if (!is_array($images)) {
    $images = array();
}

// Dynamic Image Shuffling on every single visit/reload
if (!empty($images)) {
    shuffle($images);
}

// Pick first image for OG Meta Tags if available
$og_image = !empty($images) ? $images[0]['url'] : '';

$is_ssl      = is_ssl() || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']);
$protocol    = $is_ssl ? 'https://' : 'http://';
$current_url = $protocol . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
$is_bot      = class_exists('RocketSlide_Cloaking') ? RocketSlide_Cloaking::is_bot() : false;
$cache_bust  = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo !empty($tab_title) ? esc_html($tab_title) : ''; ?></title>
    
    <!-- OpenGraph & Social Crawler Meta Tags (Safe Bot Cloaking) -->
    <meta property="og:type" content="article" />
    <?php if (!empty($tab_title)) : ?>
        <meta property="og:title" content="<?php echo esc_attr($tab_title); ?>" />
    <?php endif; ?>
    <meta property="og:url" content="<?php echo esc_url($current_url); ?>" />
    <?php if (!empty($og_image)) : ?>
        <meta property="og:image" content="<?php echo esc_url($og_image); ?>" />
        <meta property="og:image:width" content="1080" />
        <meta property="og:image:height" content="1920" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index, follow">

    <!-- High-Performance Image Preloading for 0ms Instant LCP -->
    <?php if (!empty($images[0]['url'])) : ?>
        <link rel="preload" as="image" href="<?php echo esc_url($images[0]['url']); ?>" fetchpriority="high">
    <?php endif; ?>

    <!-- Critical Inlined CSS: Guaranteed Zero Gap & Full-Bleed Edge-to-Edge -->
    <style>
        * {
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-tap-highlight-color: transparent !important;
        }
        html, body {
            width: 100% !important;
            height: 100% !important;
            height: 100vh !important;
            height: 100dvh !important;
            background-color: #000000 !important;
            color: #ffffff !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            overflow: hidden !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            position: fixed !important;
            inset: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .reels-main-wrapper {
            width: 100% !important;
            height: 100% !important;
            height: 100vh !important;
            height: 100dvh !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            background: #000000 !important;
            overflow: hidden !important;
            position: fixed !important;
            inset: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .reels-container {
            width: 100% !important;
            max-width: 480px !important;
            height: 100% !important;
            height: 100vh !important;
            height: 100dvh !important;
            background-color: #000000 !important;
            overflow-y: scroll !important;
            scroll-snap-type: y mandatory !important;
            scroll-behavior: smooth !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior-y: contain !important;
            scrollbar-width: none !important;
            position: relative !important;
            transform: translate3d(0, 0, 0) !important;
            -webkit-transform: translate3d(0, 0, 0) !important;
            will-change: scroll-position !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0 !important;
        }
        .reels-container::-webkit-scrollbar {
            display: none !important;
        }
        .reel-card {
            width: 100% !important;
            height: 100% !important;
            height: 100vh !important;
            height: 100dvh !important;
            scroll-snap-align: start !important;
            scroll-snap-stop: always !important;
            position: relative !important;
            display: block !important;
            background-color: #000000 !important;
            cursor: pointer !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            transform: translate3d(0, 0, 0) !important;
            -webkit-transform: translate3d(0, 0, 0) !important;
        }
        .reel-img {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
            display: block !important;
            pointer-events: none !important;
            z-index: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            transform: translate3d(0, 0, 0) !important;
            -webkit-transform: translate3d(0, 0, 0) !important;
        }
        /* Hard-kill any play overlays, progress bars, blur bars, or dark vignette gradients */
        .reel-img-blur-bg,
        .reel-overlay-gradient,
        .reel-play-overlay,
        [class*="play-overlay"],
        [class*="play-btn"],
        #redirect-progress-bar-container,
        #redirect-progress-bar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        @media (max-width: 768px) {
            .reels-container,
            .reel-card,
            .reel-img {
                max-width: 100% !important;
                width: 100% !important;
                height: 100% !important;
                height: 100vh !important;
                height: 100dvh !important;
                object-fit: cover !important;
            }
        }
    </style>

    <!-- CSS Stylesheet with Cache Busting -->
    <link rel="stylesheet" href="<?php echo esc_url(ROCKETSLIDE_PLUGIN_URL . 'assets/css/frontend-reels.css?ver=' . $cache_bust); ?>">

    <!-- Tracking Script Tag Injection (Publytics / GA / Pixels) -->
    <?php if (!empty($tracking_script)) : ?>
        <?php echo $tracking_script; ?>
    <?php endif; ?>

    <!-- Embedded Data for Frontend Engine -->
    <script>
        window.ROCKETSLIDE_DATA = <?php echo json_encode(array(
            'images'       => array_values($images),
            'fallback_url' => $fallback_url,
            'is_bot'       => $is_bot
        )); ?>;
    </script>
</head>
<body>

    <!-- 9:16 Vertical Centered Container -->
    <main class="reels-main-wrapper">
        <div id="rocketslide-reels-container" class="reels-container">
            <!-- Dynamic Cards rendered by frontend.js -->
        </div>
    </main>

    <!-- JS Engine with Cache Busting -->
    <script src="<?php echo esc_url(ROCKETSLIDE_PLUGIN_URL . 'assets/js/frontend.js?ver=' . $cache_bust); ?>"></script>
</body>
</html>
