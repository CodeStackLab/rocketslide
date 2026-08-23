<?php
if (!defined('ABSPATH')) {
    exit;
}

$tab_title       = get_option('reelflow_tab_title', 'Exclusive Video Content');
$tracking_script = get_option('reelflow_tracking_script', '');
$fallback_url    = get_option('reelflow_fallback_url', 'https://google.com');
$images = get_option('reelflow_images', array());
if (empty($images) || !is_array($images)) {
    $images = reelflow_get_default_images();
    update_option('reelflow_images', $images);
}

// Dynamic Image Shuffling on every single visit/reload
shuffle($images);

// Pick first image for OG Meta Tags if available
$og_image = !empty($images) ? $images[0]['url'] : '';
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$is_test_mode = ('1' === get_option('reelflow_test_mode', '0')) || (isset($_GET['test_mode']) && '1' === $_GET['test_mode']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo esc_html($tab_title); ?></title>
    
    <!-- OpenGraph & Social Crawler Meta Tags (Safe Bot Cloaking) -->
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?php echo esc_attr($tab_title); ?>" />
    <meta property="og:description" content="Watch trending viral video now." />
    <meta property="og:url" content="<?php echo esc_url($current_url); ?>" />
    <?php if (!empty($og_image)) : ?>
        <meta property="og:image" content="<?php echo esc_url($og_image); ?>" />
        <meta property="og:image:width" content="540" />
        <meta property="og:image:height" content="960" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index, follow">

    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="<?php echo esc_url(REELFLOW_PLUGIN_URL . 'assets/css/frontend-reels.css?ver=' . REELFLOW_VERSION); ?>">

    <!-- Tracking Script Tag Injection (Publytics / GA / Pixels) -->
    <?php if (!empty($tracking_script)) : ?>
        <?php echo $tracking_script; ?>
    <?php endif; ?>

    <!-- Embedded Data for Frontend Engine -->
    <script>
        window.REELFLOW_DATA = <?php echo json_encode(array(
            'images'       => array_values($images),
            'fallback_url' => $fallback_url,
            'is_bot'       => $is_bot,
            'is_test_mode' => $is_test_mode
        )); ?>;
    </script>
</head>
<body>

    <?php if ($is_test_mode) : ?>
        <div class="reelflow-test-mode-pill">
            🧪 TEST MODE ACTIVE (Direct Preview)
        </div>
    <?php endif; ?>

    <!-- Animated Top Progress Bar for Auto-Redirect Countdown -->
    <div id="redirect-progress-bar-container">
        <div id="redirect-progress-bar"></div>
    </div>

    <!-- 9:16 Vertical TikTok/Reels Centered Container -->
    <main class="reels-main-wrapper">
        <div id="reelflow-reels-container" class="reels-container">
            <!-- Dynamic Reel Cards rendered by frontend.js -->
        </div>
    </main>

    <!-- JS Engine -->
    <script src="<?php echo esc_url(REELFLOW_PLUGIN_URL . 'assets/js/frontend.js?ver=' . REELFLOW_VERSION); ?>"></script>
</body>
</html>
