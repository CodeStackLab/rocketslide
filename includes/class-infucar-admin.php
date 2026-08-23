<?php
/**
 * class-infucar-admin.php
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
 * @package Infucar_Landing_Page
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Infucar_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Direct Settings Link on Plugins Page
        add_filter('plugin_action_links_' . plugin_basename(INFUCAR_PLUGIN_FILE), array($this, 'add_action_links'));
        add_action('admin_head-plugins.php', array($this, 'fix_plugins_page_action_links_css'));

        // Quick Settings Link in Admin Bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 90);

        // AJAX Handlers
        add_action('wp_ajax_infucar_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_infucar_verify_publytics', array($this, 'ajax_verify_publytics'));
        add_action('wp_ajax_infucar_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_infucar_update_image', array($this, 'ajax_update_image'));
        add_action('wp_ajax_infucar_delete_image', array($this, 'ajax_delete_image'));
    }

    /**
     * Fix CSS for plugins page row actions to keep Settings and Deactivate side-by-side horizontally
     */
    public function fix_plugins_page_action_links_css() {
        ?>
        <style id="infucar-plugins-actions-fix">
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
            'settings' => '<a href="' . esc_url(admin_url('admin.php?page=infucar-landing-page')) . '">' . __('Settings', 'infucar-lp') . '</a>'
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
            'id'    => 'infucar-settings',
            'title' => '📱 Infucar 9:16 Settings',
            'href'  => admin_url('admin.php?page=infucar-landing-page'),
        ));
    }

    /**
     * Add admin menu item with smartphone icon
     */
    public function add_admin_menu() {
        add_menu_page(
            'Infucar 9:16 Manager',
            'Infucar 9:16',
            'manage_options',
            'infucar-landing-page',
            array($this, 'render_admin_page'),
            'dashicons-smartphone',
            30
        );
    }

    /**
     * Enqueue Admin Styles and Scripts
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_infucar-landing-page') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'infucar-admin-dark-css',
            INFUCAR_PLUGIN_URL . 'assets/css/admin-dark.css',
            array(),
            INFUCAR_VERSION
        );

        wp_enqueue_script(
            'infucar-admin-js',
            INFUCAR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            INFUCAR_VERSION,
            true
        );

        wp_localize_script('infucar-admin-js', 'infucar_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('infucar_admin_nonce')
        ));
    }

    /**
     * Render Main Admin Dashboard & Settings UI
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $fallback_url    = get_option('infucar_fallback_url', 'https://google.com');
        $tab_title       = get_option('infucar_tab_title', 'Exclusive Video Content');
        $slug            = get_option('infucar_slug', 'v');
        $tracking_script = get_option('infucar_tracking_script', '');
        $test_mode       = get_option('infucar_test_mode', '0');
        $images = get_option('infucar_images', array());
        if (empty($images) || !is_array($images)) {
            $images = infucar_get_default_images();
            update_option('infucar_images', $images);
        }

        $landing_page_url = home_url('/' . trim($slug, '/') . '/');
        $test_preview_url = add_query_arg('test_mode', '1', $landing_page_url);
        ?>
        <div class="infucar-wrap">
            <!-- Header Banner -->
            <div class="infucar-header">
                <div class="infucar-header-brand">
                    <div class="infucar-header-icon">📱</div>
                    <div class="infucar-header-text">
                        <h1>INFUCAR 9:16 REELS ENGINE</h1>
                        <p>High-Performance Vertical Landing Page & Social Traffic Cloaker</p>
                    </div>
                </div>
                <div class="infucar-header-meta">
                    <div class="infucar-live-url">
                        <span>🔗 Target Slug:</span>
                        <code>/<?php echo esc_html(trim($slug, '/')); ?>/</code>
                    </div>
                    <a href="<?php echo esc_url($test_preview_url); ?>" target="_blank" class="infucar-btn infucar-btn-secondary">
                        🧪 Test Preview Page
                    </a>
                    <a href="<?php echo esc_url($landing_page_url); ?>" target="_blank" class="infucar-btn infucar-btn-live">
                        ⚡ Live Landing Page 🚀
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="infucar-stats-row">
                <div class="infucar-stat-card">
                    <span class="stat-icon">🎬</span>
                    <span class="stat-value" id="infucar-stat-images-count"><?php echo count($images); ?></span>
                    <span class="stat-label">Active Reels</span>
                </div>
                <div class="infucar-stat-card">
                    <span class="stat-icon"><?php echo ($test_mode === '1') ? '🧪' : '🛡️'; ?></span>
                    <span class="stat-value" style="color: <?php echo ($test_mode === '1') ? '#d29922' : '#3fb950'; ?>;">
                        <?php echo ($test_mode === '1') ? 'Testing' : 'Active'; ?>
                    </span>
                    <span class="stat-label"><?php echo ($test_mode === '1') ? 'Bypass Active' : 'FB/IG Cloaking'; ?></span>
                </div>
                <div class="infucar-stat-card">
                    <span class="stat-icon">📊</span>
                    <span class="stat-value"><?php echo !empty($tracking_script) ? 'Connected' : 'None'; ?></span>
                    <span class="stat-label">Tracking Engine</span>
                </div>
                <div class="infucar-stat-card">
                    <span class="stat-icon">⚡</span>
                    <span class="stat-value">540x960</span>
                    <span class="stat-label">WebP Auto-Crop</span>
                </div>
            </div>

            <!-- Desktop Navigation Tabs -->
            <nav class="infucar-tabs-nav">
                <button class="infucar-tab-btn active" data-tab="tab-images">🖼️ Reels & Link Manager</button>
                <button class="infucar-tab-btn" data-tab="tab-tracking">📊 Publytics & Analytics</button>
                <button class="infucar-tab-btn" data-tab="tab-fallback">🛡️ Dual-Layer Cloaking</button>
                <button class="infucar-tab-btn" data-tab="tab-settings">⚙️ Plugin Settings</button>
            </nav>

            <!-- Tab 1: Image & Target Link Manager -->
            <div class="infucar-tab-panel active" id="tab-images">
                <div class="infucar-card">
                    <h3 class="infucar-card-title">✨ Add New 9:16 Reel Image</h3>
                    <p class="infucar-card-subtitle">Upload any image file or choose from WordPress Media Library. It will be cropped to 540x960 resolution and converted to 75% quality WebP format automatically.</p>
                    
                    <form id="infucar-add-image-form">
                        <div class="infucar-add-form-grid">
                            <div class="infucar-field">
                                <label class="infucar-label">Image Source <span class="req">*</span></label>
                                <div class="infucar-file-picker">
                                    <input type="file" id="infucar-file-input" accept="image/*" class="infucar-file-hidden">
                                    <input type="hidden" id="infucar-media-id" value="">
                                    <button type="button" id="infucar-select-media-btn" class="infucar-btn infucar-btn-secondary">📁 Upload File / Media Library</button>
                                    <span id="infucar-file-name" class="infucar-file-name">No file selected</span>
                                </div>
                            </div>

                            <div class="infucar-field">
                                <label class="infucar-label">Target Redirect URL <span class="req">*</span></label>
                                <input type="url" id="infucar-new-target-url" placeholder="https://your-affiliate-offer.com" required class="infucar-input">
                            </div>

                            <div class="infucar-field">
                                <label class="infucar-label">Timer (Seconds)</label>
                                <input type="number" id="infucar-new-timer" value="0" min="0" placeholder="0" class="infucar-input">
                            </div>

                            <div class="infucar-field">
                                <button type="submit" id="infucar-add-image-btn" class="infucar-btn infucar-btn-primary">
                                    🚀 Upload & Add
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Grid List of Uploaded Images -->
                <div class="infucar-card">
                    <h3 class="infucar-card-title">🎬 Managed Reel Cards (<span id="infucar-images-count"><?php echo count($images); ?></span>)</h3>
                    <p class="infucar-card-subtitle">Every visit randomly shuffles the card order. First 2 load eagerly, remaining lazy load on scroll.</p>

                    <div class="infucar-img-grid" id="infucar-images-container">
                        <?php if (empty($images)) : ?>
                            <div class="infucar-empty-state" id="infucar-empty-state">
                                <div class="empty-icon">🖼️</div>
                                <p>No 9:16 reel images added yet. Upload your first image above to launch your landing page!</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($images as $index => $img) : ?>
                                <div class="infucar-img-card" data-id="<?php echo esc_attr($img['id']); ?>">
                                    <div class="infucar-img-thumb">
                                        <img src="<?php echo esc_url($img['url']); ?>" alt="Reel Card">
                                        <span class="infucar-img-index">#<?php echo $index + 1; ?></span>
                                        <span class="infucar-img-format-badge">WebP</span>
                                    </div>
                                    <div class="infucar-img-body">
                                        <div>
                                            <label class="infucar-label">Target URL:</label>
                                            <input type="url" class="infucar-input infucar-card-target" value="<?php echo esc_url($img['target_url']); ?>">
                                        </div>
                                        <div>
                                            <label class="infucar-label">Redirect Timer (s):</label>
                                            <input type="number" class="infucar-input infucar-card-timer" value="<?php echo esc_attr($img['timer']); ?>" min="0">
                                        </div>
                                        <div class="infucar-img-card-actions">
                                            <button type="button" class="infucar-btn infucar-btn-success infucar-save-card-btn">💾 Save</button>
                                            <button type="button" class="infucar-btn infucar-btn-danger infucar-delete-card-btn">🗑️ Delete</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Publytics & Tracking -->
            <div class="infucar-tab-panel" id="tab-tracking">
                <div class="infucar-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                        <h3 class="infucar-card-title" style="margin:0;">📊 Publytics & Analytics Integration</h3>
                        <div id="infucar-publytics-status" class="infucar-badge checking">
                            🟡 Checking Status...
                        </div>
                    </div>
                    <p class="infucar-card-subtitle">Paste custom tracking snippet (Publytics, GA4, TikTok Pixel, Facebook Pixel). It injects directly into the <code>&lt;head&gt;</code> of the 9:16 template.</p>

                    <div class="infucar-field">
                        <label class="infucar-label">Tracking Code Snippet Tag:</label>
                        <textarea id="infucar-tracking-script" class="infucar-textarea" rows="6" placeholder="<script defer data-domain=&quot;yourdomain.com&quot; src=&quot;https://api.publytics.net/js/script.js&quot;></script>"><?php echo esc_textarea($tracking_script); ?></textarea>
                        <span class="infucar-input-hint">Make sure to include full <code>&lt;script&gt;...&lt;/script&gt;</code> tags.</span>
                    </div>

                    <div class="infucar-actions">
                        <button type="button" id="infucar-save-tracking-btn" class="infucar-btn infucar-btn-primary">
                            💾 Save Tracking Code
                        </button>
                        <button type="button" id="infucar-verify-tracking-btn" class="infucar-btn infucar-btn-secondary">
                            🔍 Test & Verify Connection
                        </button>
                        <button type="button" id="infucar-test-traffic-btn" class="infucar-btn infucar-btn-accent">
                            🚀 Fire Live Test Event
                        </button>
                    </div>

                    <div id="infucar-verification-output" class="infucar-output-box" style="display:none;"></div>
                </div>
            </div>

            <!-- Tab 3: Cloaking & Fallback -->
            <div class="infucar-tab-panel" id="tab-fallback">
                <div class="infucar-card">
                    <h3 class="infucar-card-title">🛡️ Advanced Dual-Layer Traffic Cloaking Engine</h3>
                    <p class="infucar-card-subtitle">Genuine Facebook & Instagram traffic displays the 9:16 Reels landing page. Non-social visitors (direct visits, search engines) are instantly redirected to the Fallback URL. Social media crawlers receive clean OpenGraph meta tags.</p>

                    <div class="infucar-field" style="background:var(--surface-2); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:20px;">
                        <label class="infucar-label" style="font-size:14px; font-weight:700; color:var(--text);">🧪 Test / Preview Mode (Bypass Cloaking for Direct Testing)</label>
                        <select id="infucar-test-mode" class="infucar-select" style="max-width:550px;">
                            <option value="0" <?php selected($test_mode, '0'); ?>>🔴 Disabled (Live Production Mode — Only Facebook/Instagram Traffic Sees Landing Page)</option>
                            <option value="1" <?php selected($test_mode, '1'); ?>>🟢 Enabled (Testing Mode — Open Landing Page Directly Without FB Post)</option>
                        </select>
                        <span class="infucar-input-hint" style="margin-top:8px;">
                            • <strong>Enabled (Testing):</strong> Anyone can open <code>/<?php echo esc_html(trim($slug, '/')); ?>/</code> directly in browser to test the landing page without posting on Facebook.<br>
                            • <strong>Disabled (Live):</strong> Live protection mode. Direct visits are redirected to your Fallback URL, and only FB/IG clicks see the landing page.
                        </span>
                    </div>

                    <div class="infucar-field">
                        <label class="infucar-label">Custom Fallback Redirect URL <span class="req">*</span></label>
                        <input type="url" id="infucar-fallback-url" class="infucar-input" value="<?php echo esc_url($fallback_url); ?>" placeholder="https://google.com or https://news.com" required>
                        <span class="infucar-input-hint">Target URL for non-social visitors. All incoming URL parameters (<code>utm_*</code>, <code>fbclid</code>) are preserved and forwarded.</span>
                    </div>

                    <div class="infucar-cloaking-box">
                        <h4>Active Filter Signals:</h4>
                        <ul>
                            <li><span class="check">✓</span> Referrer Verification: <code>facebook.com</code>, <code>fb.me</code>, <code>instagram.com</code>, <code>fb.gg</code></li>
                            <li><span class="check">✓</span> URL Tracking Signals: <code>fbclid</code>, <code>fb_ref</code>, <code>fb_source</code></li>
                            <li><span class="check">✓</span> In-App Browser User-Agents: <code>FBAN</code>, <code>FBAV</code>, <code>FB_IAB</code>, <code>FBIOS</code>, <code>FB4A</code>, <code>Instagram</code></li>
                            <li><span class="check">✓</span> Crawler OpenGraph Bypass: <code>facebookexternalhit</code>, <code>facebot</code>, <code>whatsapp</code>, <code>telegrambot</code>, <code>twitterbot</code>, <code>googlebot</code></li>
                        </ul>
                    </div>

                    <div class="infucar-actions">
                        <button type="button" id="infucar-save-fallback-btn" class="infucar-btn infucar-btn-primary">
                            💾 Save Cloaking & Test Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Site Settings -->
            <div class="infucar-tab-panel" id="tab-settings">
                <div class="infucar-card">
                    <h3 class="infucar-card-title">⚙️ General Landing Page Configuration</h3>
                    <p class="infucar-card-subtitle">Customize the landing page permalink route and browser title.</p>
                    
                    <div class="infucar-field">
                        <label class="infucar-label">Browser Tab Title</label>
                        <input type="text" id="infucar-tab-title" class="infucar-input" value="<?php echo esc_attr($tab_title); ?>" placeholder="Exclusive Video Content">
                        <span class="infucar-input-hint">Title shown in the browser title bar when visitors view the 9:16 reels template.</span>
                    </div>

                    <div class="infucar-field">
                        <label class="infucar-label">Custom Landing Page Slug / Route</label>
                        <div class="infucar-slug-row">
                            <span class="infucar-slug-prefix"><?php echo esc_url(home_url('/')); ?></span>
                            <input type="text" id="infucar-slug" class="infucar-input" value="<?php echo esc_attr($slug); ?>" placeholder="v">
                            <span class="infucar-slug-suffix">/</span>
                        </div>
                        <span class="infucar-input-hint">Default slug is <code>v</code> (e.g., <code>yoursite.com/v/</code>). Changing this automatically updates WordPress rewrite rules.</span>
                    </div>

                    <div class="infucar-actions">
                        <button type="button" id="infucar-save-settings-btn" class="infucar-btn infucar-btn-primary">
                            💾 Save All Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Bottom Navigation (<768px) -->
            <nav class="infucar-mobile-nav">
                <button class="infucar-mobile-nav-item active" data-tab="tab-images">
                    <span class="nav-icon">🖼️</span>
                    <span>Reels</span>
                </button>
                <button class="infucar-mobile-nav-item" data-tab="tab-tracking">
                    <span class="nav-icon">📊</span>
                    <span>Tracking</span>
                </button>
                <button class="infucar-mobile-nav-item" data-tab="tab-fallback">
                    <span class="nav-icon">🛡️</span>
                    <span>Cloaking</span>
                </button>
                <button class="infucar-mobile-nav-item" data-tab="tab-settings">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </button>
                <a href="<?php echo esc_url($test_preview_url); ?>" target="_blank" class="infucar-mobile-nav-item">
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
        check_ajax_referer('infucar_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        if (isset($_POST['fallback_url'])) {
            update_option('infucar_fallback_url', esc_url_raw($_POST['fallback_url']));
        }
        if (isset($_POST['test_mode'])) {
            update_option('infucar_test_mode', sanitize_text_field($_POST['test_mode']));
        }
        if (isset($_POST['tab_title'])) {
            update_option('infucar_tab_title', sanitize_text_field($_POST['tab_title']));
        }
        if (isset($_POST['slug'])) {
            $new_slug = sanitize_title($_POST['slug']);
            if (!empty($new_slug)) {
                update_option('infucar_slug', $new_slug);
                Infucar_Frontend::register_rewrite_rules();
                flush_rewrite_rules();
            }
        }
        if (isset($_POST['tracking_script'])) {
            update_option('infucar_tracking_script', wp_unslash($_POST['tracking_script']));
        }

        wp_send_json_success(array('message' => 'Settings saved successfully!'));
    }

    /**
     * AJAX Verification Engine for Publytics
     */
    public function ajax_verify_publytics() {
        check_ajax_referer('infucar_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $tracking_code = get_option('infucar_tracking_script', '');
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
        check_ajax_referer('infucar_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';
        $timer      = isset($_POST['timer']) ? intval($_POST['timer']) : 0;
        $media_id   = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;

        $processed_result = null;

        if ($media_id > 0) {
            $processed_result = Infucar_Image_Processor::process_from_attachment_id($media_id);
        } elseif (!empty($_FILES['image_file']['tmp_name'])) {
            $tmp_path = $_FILES['image_file']['tmp_name'];
            $processed_result = Infucar_Image_Processor::process_image($tmp_path);
        }

        if (!$processed_result || is_wp_error($processed_result)) {
            $err_msg = is_wp_error($processed_result) ? $processed_result->get_error_message() : 'Please select an image file or choose from Media Library.';
            wp_send_json_error($err_msg);
        }

        $images = get_option('infucar_images', array());
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
        update_option('infucar_images', $images);

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
        check_ajax_referer('infucar_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id         = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';
        $timer      = isset($_POST['timer']) ? intval($_POST['timer']) : 0;

        if (empty($id)) {
            wp_send_json_error('Invalid image ID.');
        }

        $images = get_option('infucar_images', array());
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
            update_option('infucar_images', $images);
            wp_send_json_success(array('message' => 'Image details updated successfully!'));
        } else {
            wp_send_json_error('Image not found.');
        }
    }

    /**
     * AJAX Delete Image
     */
    public function ajax_delete_image() {
        check_ajax_referer('infucar_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        if (empty($id)) {
            wp_send_json_error('Invalid image ID.');
        }

        $images = get_option('infucar_images', array());
        $filtered = array();

        foreach ($images as $img) {
            if ($img['id'] === $id) {
                if (!empty($img['path'])) {
                    Infucar_Image_Processor::delete_image($img['path']);
                }
            } else {
                $filtered[] = $img;
            }
        }

        update_option('infucar_images', $filtered);

        wp_send_json_success(array(
            'message' => 'Image deleted successfully!',
            'total'   => count($filtered)
        ));
    }
}
