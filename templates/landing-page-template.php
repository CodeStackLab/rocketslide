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

    <!-- Inline Critical CSS: Force Instant Play Button Removal & Fast Smooth Layout -->
    <style>
        .reel-play-overlay,
        [class*="play-overlay"],
        [class*="play-btn"],
        #redirect-progress-bar-container,
        #redirect-progress-bar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
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
