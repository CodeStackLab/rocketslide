<?php
/**
 * class-rocketslide-admin.php
 *
 * MODERN DARK-MODE ADMIN DASHBOARD & PLUGIN SETTINGS MANAGER
 * ==========================================================
 *
 * Provides a responsive, simple, and clean Settings Manager UI for:
 *   1. 9:16 Reel Image Upload & WebP 540x960 Converter
 *   2. Individual Target Redirect URLs & Timer Settings
 *   3. Publytics & Custom Script Tracking Verification Engine
 *   4. Advanced Dual-Layer FB/IG Traffic Cloaking & Fallback Destination Manager
 *   5. Dynamic Browser Tab Title & Custom URL Slug Configuration
 *
 * @package RocketSlide_Landing_Page
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RocketSlide_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Direct Settings Link on Plugins Page
        add_filter('plugin_action_links_' . plugin_basename(ROCKETSLIDE_PLUGIN_FILE), array($this, 'add_action_links'));
        add_action('admin_head-plugins.php', array($this, 'fix_plugins_page_action_links_css'));

        // Quick Settings Link in Admin Bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 90);

        // AJAX Handlers
        add_action('wp_ajax_rocketslide_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_rocketslide_verify_publytics', array($this, 'ajax_verify_publytics'));
        add_action('wp_ajax_rocketslide_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_rocketslide_update_image', array($this, 'ajax_update_image'));
        add_action('wp_ajax_rocketslide_delete_image', array($this, 'ajax_delete_image'));
    }

    /**
     * Fix CSS for plugins page row actions to keep Settings and Deactivate side-by-side horizontally
     */
    public function fix_plugins_page_action_links_css() {
        ?>
        <style id="rocketslide-plugins-actions-fix">
            .plugins .row-actions {
                display: block !important;
                margin-top: 4px !important;
            }
            .plugins .row-actions span {
                display: inline !important;
                white-space: nowrap !important;
                float: none !important;
            }
            .plugins .row-actions span.settings a {
                font-weight: 600;
            }
        </style>
        <?php
    }

    /**
     * Add direct "Settings" link on Plugins list page
     */
    public function add_action_links($links) {
        $settings_link = array(
            'settings' => '<a href="' . esc_url(admin_url('admin.php?page=rocketslide')) . '">' . __('Settings', 'rocketslide-lp') . '</a>'
        );
        return array_merge($settings_link, $links);
    }

    /**
     * Add Quick Settings link to WordPress Admin Bar
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'rocketslide-settings',
            'title' => '📱 RocketSlide 9:16 Settings',
            'href'  => admin_url('admin.php?page=rocketslide'),
        ));
    }

    /**
     * Add admin menu item with smartphone icon
     */
    public function add_admin_menu() {
        add_menu_page(
            'RocketSlide 9:16 Manager',
            'RocketSlide 9:16',
            'manage_options',
            'rocketslide',
            array($this, 'render_admin_page'),
            'dashicons-smartphone',
            30
        );
    }

    /**
     * Enqueue Admin Styles and Scripts
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_rocketslide') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'rocketslide-admin-dark-css',
            ROCKETSLIDE_PLUGIN_URL . 'assets/css/admin-dark.css',
            array(),
            ROCKETSLIDE_VERSION
        );

        wp_enqueue_script(
            'rocketslide-admin-js',
            ROCKETSLIDE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ROCKETSLIDE_VERSION,
            true
        );

        wp_localize_script('rocketslide-admin-js', 'rocketslide_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('rocketslide_admin_nonce')
        ));
    }

    /**
     * Render Main Admin Dashboard & Settings UI
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $fallback_url    = get_option('rocketslide_fallback_url', 'https://google.com');
        $tab_title       = get_option('rocketslide_tab_title', 'Exclusive Video Content');
        $slug            = get_option('rocketslide_slug', 'v');
        $tracking_script = get_option('rocketslide_tracking_script', '');
        $test_mode       = get_option('rocketslide_test_mode', '0');
        $images = get_option('rocketslide_images', array());
        if (empty($images) || !is_array($images)) {
            $images = rocketslide_get_default_images();
            update_option('rocketslide_images', $images);
        }

        $landing_page_url = home_url('/' . trim($slug, '/') . '/');
        $test_preview_url = add_query_arg('test_mode', '1', $landing_page_url);
        ?>
        <div class="rocketslide-wrap">
            <!-- Header Banner -->
            <div class="rocketslide-header">
                <div class="rocketslide-header-brand">
                    <div class="rocketslide-header-icon">📱</div>
                    <div class="rocketslide-header-text">
                        <h1>ROCKETSLIDE 9:16 ENGINE</h1>
                        <p>High-Performance Vertical Landing Page & Social Traffic Cloaker</p>
                    </div>
                </div>
                <div class="rocketslide-header-meta">
                    <div class="rocketslide-live-url">
                        <span>🔗 Target Slug:</span>
                        <code>/<?php echo esc_html(trim($slug, '/')); ?>/</code>
                    </div>
                    <a href="<?php echo esc_url($test_preview_url); ?>" target="_blank" class="rocketslide-btn rocketslide-btn-secondary">
                        🧪 Test Preview Page
                    </a>
                    <a href="<?php echo esc_url($landing_page_url); ?>" target="_blank" class="rocketslide-btn rocketslide-btn-live">
                        ⚡ Live Landing Page 🚀
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="rocketslide-stats-row">
                <div class="rocketslide-stat-card">
                    <span class="stat-icon">🎬</span>
                    <span class="stat-value" id="rocketslide-stat-images-count"><?php echo count($images); ?></span>
                    <span class="stat-label">Active Reels</span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon"><?php echo ($test_mode === '1') ? '🧪' : '🛡️'; ?></span>
                    <span class="stat-value" style="color: <?php echo ($test_mode === '1') ? '#d29922' : '#3fb950'; ?>;">
                        <?php echo ($test_mode === '1') ? 'Testing' : 'Active'; ?>
                    </span>
                    <span class="stat-label"><?php echo ($test_mode === '1') ? 'Bypass Active' : 'FB/IG Cloaking'; ?></span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon">📊</span>
                    <span class="stat-value"><?php echo !empty($tracking_script) ? 'Connected' : 'None'; ?></span>
                    <span class="stat-label">Tracking Engine</span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon">⚡</span>
                    <span class="stat-value">540x960</span>
                    <span class="stat-label">WebP Auto-Crop</span>
                </div>
            </div>

            <!-- Desktop Navigation Tabs -->
            <nav class="rocketslide-tabs-nav">
                <button class="rocketslide-tab-btn active" data-tab="tab-images">🖼️ Reels & Link Manager</button>
                <button class="rocketslide-tab-btn" data-tab="tab-tracking">📊 Publytics & Analytics</button>
                <button class="rocketslide-tab-btn" data-tab="tab-fallback">🛡️ Dual-Layer Cloaking</button>
                <button class="rocketslide-tab-btn" data-tab="tab-settings">⚙️ Plugin Settings</button>
            </nav>

            <!-- Tab 1: Image & Target Link Manager -->
            <div class="rocketslide-tab-panel active" id="tab-images">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title">✨ Add New 9:16 Reel Image</h3>
                    <p class="rocketslide-card-subtitle">Upload any image file or choose from WordPress Media Library. It will be cropped to 540x960 resolution and converted to 75% quality WebP format automatically.</p>
                    
                    <form id="rocketslide-add-image-form">
                        <div class="rocketslide-add-form-container">
                            <!-- Row 1: Image Selector -->
                            <div class="rocketslide-field">
                                <label class="rocketslide-label">Image File (WebP 540×960 Auto-Crop) <span class="req">*</span></label>
                                <div class="rocketslide-file-picker">
                                    <input type="file" id="rocketslide-file-input" accept="image/*" class="rocketslide-file-hidden">
                                    <input type="hidden" id="rocketslide-media-id" value="">
                                    <button type="button" id="rocketslide-select-media-btn" class="rocketslide-btn rocketslide-btn-secondary">📁 Choose Image / WP Media Library</button>
                                    <span id="rocketslide-file-name" class="rocketslide-file-name">No file selected</span>
                                </div>
                            </div>

                            <!-- Row 2: Target Link + Timer + Submit -->
                            <div class="rocketslide-add-form-row">
                                <div class="rocketslide-field">
                                    <label class="rocketslide-label">Target Redirect URL <span class="req">*</span></label>
                                    <input type="url" id="rocketslide-new-target-url" placeholder="https://your-affiliate-offer.com" required class="rocketslide-input">
                                </div>

                                <div class="rocketslide-field">
                                    <label class="rocketslide-label">Timer (Seconds)</label>
                                    <input type="number" id="rocketslide-new-timer" value="0" min="0" placeholder="0" class="rocketslide-input">
                                </div>

                                <div class="rocketslide-field rocketslide-field-submit">
                                    <button type="submit" id="rocketslide-add-image-btn" class="rocketslide-btn rocketslide-btn-primary">
                                        🚀 Upload & Add Reel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Grid List of Uploaded Images -->
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title">🎬 Managed Reel Cards (<span id="rocketslide-images-count"><?php echo count($images); ?></span>)</h3>
                    <p class="rocketslide-card-subtitle">Every visit randomly shuffles the card order. First 2 load eagerly, remaining lazy load on scroll.</p>

                    <div class="rocketslide-img-grid" id="rocketslide-images-container">
                        <?php if (empty($images)) : ?>
                            <div class="rocketslide-empty-state" id="rocketslide-empty-state">
                                <div class="empty-icon">🖼️</div>
                                <p>No 9:16 reel images added yet. Upload your first image above to launch your landing page!</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($images as $index => $img) : ?>
                                <div class="rocketslide-img-card" data-id="<?php echo esc_attr($img['id']); ?>">
                                    <div class="rocketslide-img-thumb">
                                        <img src="<?php echo esc_url($img['url']); ?>" alt="Reel Card">
                                        <span class="rocketslide-img-index">#<?php echo $index + 1; ?></span>
                                        <span class="rocketslide-img-format-badge">WebP</span>
                                    </div>
                                    <div class="rocketslide-img-body">
                                        <div>
                                            <label class="rocketslide-label">Target URL:</label>
                                            <input type="url" class="rocketslide-input rocketslide-card-target" value="<?php echo esc_url($img['target_url']); ?>">
                                        </div>
                                        <div>
                                            <label class="rocketslide-label">Redirect Timer (s):</label>
                                            <input type="number" class="rocketslide-input rocketslide-card-timer" value="<?php echo esc_attr($img['timer']); ?>" min="0">
                                        </div>
                                        <div class="rocketslide-img-card-actions">
                                            <button type="button" class="rocketslide-btn rocketslide-btn-success rocketslide-save-card-btn">💾 Save</button>
                                            <button type="button" class="rocketslide-btn rocketslide-btn-danger rocketslide-delete-card-btn">🗑️ Delete</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                    </div>

                    <!-- Pagination Controls Bar -->
                    <div class="rocketslide-pagination-wrapper" id="rocketslide-pagination-wrapper">
                        <div class="rocketslide-pagination-info" id="rocketslide-pagination-info">
                            Showing cards
                        </div>
                        <div class="rocketslide-pagination-controls" id="rocketslide-pagination-controls">
                            <!-- Generated by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Publytics & Tracking -->
            <div class="rocketslide-tab-panel" id="tab-tracking">
                <div class="rocketslide-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                        <h3 class="rocketslide-card-title" style="margin:0;">📊 Publytics & Analytics Integration</h3>
                        <div id="rocketslide-publytics-status" class="rocketslide-badge checking">
                            🟡 Checking Status...
                        </div>
                    </div>
                    <p class="rocketslide-card-subtitle">Paste custom tracking snippet (Publytics, GA4, TikTok Pixel, Facebook Pixel). It injects directly into the <code>&lt;head&gt;</code> of the 9:16 template.</p>

                    <div class="rocketslide-field">
                        <label class="rocketslide-label">Tracking Code Snippet Tag:</label>
                        <textarea id="rocketslide-tracking-script" class="rocketslide-textarea" rows="6" placeholder="<script defer data-domain=&quot;yourdomain.com&quot; src=&quot;https://api.publytics.net/js/script.js&quot;></script>"><?php echo esc_textarea($tracking_script); ?></textarea>
                        <span class="rocketslide-input-hint">Make sure to include full <code>&lt;script&gt;...&lt;/script&gt;</code> tags.</span>
                    </div>

                    <div class="rocketslide-actions">
                        <button type="button" id="rocketslide-save-tracking-btn" class="rocketslide-btn rocketslide-btn-primary">
                            💾 Save Tracking Code
                        </button>
                        <button type="button" id="rocketslide-verify-tracking-btn" class="rocketslide-btn rocketslide-btn-secondary">
                            🔍 Test & Verify Connection
                        </button>
                        <button type="button" id="rocketslide-test-traffic-btn" class="rocketslide-btn rocketslide-btn-accent">
                            🚀 Fire Live Test Event
                        </button>
                    </div>

                    <div id="rocketslide-verification-output" class="rocketslide-output-box" style="display:none;"></div>
                </div>
            </div>

            <!-- Tab 3: Cloaking & Fallback -->
            <div class="rocketslide-tab-panel" id="tab-fallback">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title">🛡️ Advanced Dual-Layer Traffic Cloaking Engine</h3>
                    <p class="rocketslide-card-subtitle">Genuine Facebook & Instagram traffic displays the 9:16 Reels landing page. Non-social visitors (direct visits, search engines) are instantly redirected to the Fallback URL. Social media crawlers receive clean OpenGraph meta tags.</p>

                    <div class="rocketslide-field" style="background:var(--surface-2); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:20px;">
                        <label class="rocketslide-label" style="font-size:14px; font-weight:700; color:var(--text);">🧪 Test / Preview Mode (Bypass Cloaking for Direct Testing)</label>
                        <select id="rocketslide-test-mode" class="rocketslide-select" style="max-width:550px;">
                            <option value="0" <?php selected($test_mode, '0'); ?>>🔴 Disabled (Live Production Mode — Only Facebook/Instagram Traffic Sees Landing Page)</option>
                            <option value="1" <?php selected($test_mode, '1'); ?>>🟢 Enabled (Testing Mode — Open Landing Page Directly Without FB Post)</option>
                        </select>
                        <span class="rocketslide-input-hint" style="margin-top:8px;">
                            • <strong>Enabled (Testing):</strong> Anyone can open <code>/<?php echo esc_html(trim($slug, '/')); ?>/</code> directly in browser to test the landing page without posting on Facebook.<br>
                            • <strong>Disabled (Live):</strong> Live protection mode. Direct visits are redirected to your Fallback URL, and only FB/IG clicks see the landing page.
                        </span>
                    </div>

                    <div class="rocketslide-field">
                        <label class="rocketslide-label">Custom Fallback Redirect URL <span class="req">*</span></label>
                        <input type="url" id="rocketslide-fallback-url" class="rocketslide-input" value="<?php echo esc_url($fallback_url); ?>" placeholder="https://google.com or https://news.com" required>
                        <span class="rocketslide-input-hint">Target URL for non-social visitors. All incoming URL parameters (<code>utm_*</code>, <code>fbclid</code>) are preserved and forwarded.</span>
                    </div>

                    <div class="rocketslide-cloaking-box">
                        <h4>Active Filter Signals:</h4>
                        <ul>
                            <li><span class="check">✓</span> Referrer Verification: <code>facebook.com</code>, <code>fb.me</code>, <code>instagram.com</code>, <code>fb.gg</code></li>
                            <li><span class="check">✓</span> URL Tracking Signals: <code>fbclid</code>, <code>fb_ref</code>, <code>fb_source</code></li>
                            <li><span class="check">✓</span> In-App Browser User-Agents: <code>FBAN</code>, <code>FBAV</code>, <code>FB_IAB</code>, <code>FBIOS</code>, <code>FB4A</code>, <code>Instagram</code></li>
                            <li><span class="check">✓</span> Crawler OpenGraph Bypass: <code>facebookexternalhit</code>, <code>facebot</code>, <code>whatsapp</code>, <code>telegrambot</code>, <code>twitterbot</code>, <code>googlebot</code></li>
                        </ul>
                    </div>

                    <div class="rocketslide-actions">
                        <button type="button" id="rocketslide-save-fallback-btn" class="rocketslide-btn rocketslide-btn-primary">
                            💾 Save Cloaking & Test Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Site Settings -->
            <div class="rocketslide-tab-panel" id="tab-settings">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title">⚙️ General Landing Page Configuration</h3>
                    <p class="rocketslide-card-subtitle">Customize the landing page permalink route and browser title.</p>
                    
                    <div class="rocketslide-field">
                        <label class="rocketslide-label">Browser Tab Title</label>
                        <input type="text" id="rocketslide-tab-title" class="rocketslide-input" value="<?php echo esc_attr($tab_title); ?>" placeholder="Exclusive Video Content">
                        <span class="rocketslide-input-hint">Title shown in the browser title bar when visitors view the 9:16 reels template.</span>
                    </div>

                    <div class="rocketslide-field">
                        <label class="rocketslide-label">Custom Landing Page Slug / Route</label>
                        <div class="rocketslide-slug-row">
                            <span class="rocketslide-slug-prefix"><?php echo esc_url(home_url('/')); ?></span>
                            <input type="text" id="rocketslide-slug" class="rocketslide-input" value="<?php echo esc_attr($slug); ?>" placeholder="v">
                            <span class="rocketslide-slug-suffix">/</span>
                        </div>
                        <span class="rocketslide-input-hint">Default slug is <code>v</code> (e.g., <code>yoursite.com/v/</code>). Changing this automatically updates WordPress rewrite rules.</span>
                    </div>

                    <div class="rocketslide-actions">
                        <button type="button" id="rocketslide-save-settings-btn" class="rocketslide-btn rocketslide-btn-primary">
                            💾 Save All Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Bottom Navigation (<768px) -->
            <nav class="rocketslide-mobile-nav">
                <button class="rocketslide-mobile-nav-item active" data-tab="tab-images">
                    <span class="nav-icon">🖼️</span>
                    <span>Reels</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-tracking">
                    <span class="nav-icon">📊</span>
                    <span>Tracking</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-fallback">
                    <span class="nav-icon">🛡️</span>
                    <span>Cloaking</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-settings">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </button>
                <a href="<?php echo esc_url($test_preview_url); ?>" target="_blank" class="rocketslide-mobile-nav-item">
                    <span class="nav-icon">🧪</span>
                    <span>Test</span>
                </a>
            </nav>

        </div>
        <?php
    }

    /**
     * AJAX Save Plugin Settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        if (isset($_POST['fallback_url'])) {
            update_option('rocketslide_fallback_url', esc_url_raw($_POST['fallback_url']));
        }
        if (isset($_POST['test_mode'])) {
            update_option('rocketslide_test_mode', sanitize_text_field($_POST['test_mode']));
        }
        if (isset($_POST['tab_title'])) {
            update_option('rocketslide_tab_title', sanitize_text_field($_POST['tab_title']));
        }
        if (isset($_POST['slug'])) {
            $new_slug = sanitize_title($_POST['slug']);
            if (!empty($new_slug)) {
                update_option('rocketslide_slug', $new_slug);
                RocketSlide_Frontend::register_rewrite_rules();
                flush_rewrite_rules();
            }
        }
        if (isset($_POST['tracking_script'])) {
            update_option('rocketslide_tracking_script', wp_unslash($_POST['tracking_script']));
        }

        wp_send_json_success(array('message' => 'Settings saved successfully!'));
    }

    /**
     * AJAX Verification Engine for Publytics
     */
    public function ajax_verify_publytics() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $tracking_code = get_option('rocketslide_tracking_script', '');
        if (empty($tracking_code)) {
            wp_send_json_error('No tracking script found. Please paste your script tag and save first.');
        }

        preg_match('/src=["\']([^"\']+)["\']/', $tracking_code, $matches);
        $script_url = isset($matches[1]) ? $matches[1] : '';

        preg_match('/data-domain=["\']([^"\']+)["\']/', $tracking_code, $domain_matches);
        $data_domain = isset($domain_matches[1]) ? $domain_matches[1] : '';

        if (empty($script_url)) {
            wp_send_json_error('Could not find a valid src="..." script URL in the tracking code snippet.');
        }

        $response = wp_remote_get($script_url, array('timeout' => 10, 'sslverify' => false));

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            wp_send_json_success(array(
                'status'      => 'verified',
                'status_code' => $status_code,
                'script_url'  => $script_url,
                'domain'      => $data_domain,
                'message'     => '🟢 Publytics tracking script reachability confirmed (HTTP 200 OK).'
            ));
        } else {
            wp_send_json_error('Script URL returned HTTP Status Code: ' . $status_code);
        }
    }

    /**
     * AJAX Upload Image & Convert to WebP 540x960
     */
    public function ajax_upload_image() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';
        $timer      = isset($_POST['timer']) ? intval($_POST['timer']) : 0;
        $media_id   = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;

        $processed_result = null;

        if ($media_id > 0) {
            $processed_result = RocketSlide_Image_Processor::process_from_attachment_id($media_id);
        } elseif (!empty($_FILES['image_file']['tmp_name'])) {
            $tmp_path = $_FILES['image_file']['tmp_name'];
            $processed_result = RocketSlide_Image_Processor::process_image($tmp_path);
        }

        if (!$processed_result || is_wp_error($processed_result)) {
            $err_msg = is_wp_error($processed_result) ? $processed_result->get_error_message() : 'Please select an image file or choose from Media Library.';
            wp_send_json_error($err_msg);
        }

        $images = get_option('rocketslide_images', array());
        if (!is_array($images)) {
            $images = array();
        }

        $new_item = array(
            'id'         => uniqid('img_'),
            'url'        => $processed_result['url'],
            'path'       => $processed_result['path'],
            'target_url' => $target_url,
            'timer'      => $timer,
            'created_at' => time()
        );

        array_unshift($images, $new_item);
        update_option('rocketslide_images', $images);

        wp_send_json_success(array(
            'message' => 'Image processed (540x960 WebP) and added successfully!',
            'image'   => $new_item,
            'total'   => count($images)
        ));
    }

    /**
     * AJAX Update Existing Image Details
     */
    public function ajax_update_image() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id         = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';
        $timer      = isset($_POST['timer']) ? intval($_POST['timer']) : 0;

        if (empty($id)) {
            wp_send_json_error('Invalid image ID.');
        }

        $images = get_option('rocketslide_images', array());
        $updated = false;

        foreach ($images as &$img) {
            if ($img['id'] === $id) {
                $img['target_url'] = $target_url;
                $img['timer']      = $timer;
                $updated           = true;
                break;
            }
        }

        if ($updated) {
            update_option('rocketslide_images', $images);
            wp_send_json_success(array('message' => 'Image details updated successfully!'));
        } else {
            wp_send_json_error('Image not found.');
        }
    }

    /**
     * AJAX Delete Image
     */
    public function ajax_delete_image() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        if (empty($id)) {
            wp_send_json_error('Invalid image ID.');
        }

        $images = get_option('rocketslide_images', array());
        $filtered = array();

        foreach ($images as $img) {
            if ($img['id'] === $id) {
                if (!empty($img['path'])) {
                    RocketSlide_Image_Processor::delete_image($img['path']);
                }
            } else {
                $filtered[] = $img;
            }
        }

        update_option('rocketslide_images', $filtered);

        wp_send_json_success(array(
            'message' => 'Image deleted successfully!',
            'total'   => count($filtered)
        ));
    }
}
