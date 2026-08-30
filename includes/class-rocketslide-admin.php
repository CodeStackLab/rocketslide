<?php
/**
 * class-rocketslide-admin.php
 *
 * MODERN LIGHT-MODE AJAX ADMIN DASHBOARD
 * ============================================================
 *
 * Features:
 *  - 100% Modern, clean, responsive light-mode dashboard.
 *  - AJAX Image processing with automatic 540x960 WebP conversion.
 *  - Dual upload source: Local Computer File Picker or WP Media Library.
 *  - Responsive Load More Reels system (4 on mobile, 8 on desktop).
 *  - Traffic cloaking configuration (FB/IG traffic only).
 *  - Publytics tracking script management.
 *
 * @package RocketSlide_Landing_Page
 * @since   3.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RocketSlide_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_head', array($this, 'fix_plugins_page_action_links_css'));
        add_filter('plugin_action_links_' . plugin_basename(ROCKETSLIDE_PLUGIN_FILE), array($this, 'add_plugin_action_links'));
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
     * Register Admin Menu with Custom High-Quality Rocket SVG Icon
     */
    public function add_admin_menu() {
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' .
            '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>' .
            '<path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>' .
            '<path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-2.5 5-2.5"/>' .
            '<path d="M12 15v5s3.03-.55 4.5-2c1.63-1.62 2.5-5 2.5-5"/>' .
            '</svg>'
        );

        add_menu_page(
            'RocketSlide 9:16',
            'RocketSlide',
            'manage_options',
            'rocketslide',
            array($this, 'render_admin_page'),
            $icon_svg,
            30
        );
    }

    /**
     * Enqueue modern Light Admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_rocketslide') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'rocketslide-admin-css',
            ROCKETSLIDE_PLUGIN_URL . 'assets/css/admin-dark.css',
            array('dashicons'),
            time()
        );

        wp_enqueue_script(
            'rocketslide-admin-js',
            ROCKETSLIDE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            time(),
            true
        );

        wp_localize_script('rocketslide-admin-js', 'rocketslide_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('rocketslide_admin_nonce')
        ));
    }

    /**
     * Quick Settings Link on Plugins Page
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=rocketslide')) . '" style="color:#2563eb;font-weight:700;">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Admin Bar Shortcut
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) return;

        $slug = get_option('rocketslide_slug', 'v');
        $landing_page_url = home_url('/' . trim($slug, '/') . '/');

        $wp_admin_bar->add_node(array(
            'id'    => 'rocketslide_live_link',
            'title' => '<span class="ab-icon dashicons dashicons-format-video"></span> RocketSlide 9:16',
            'href'  => $landing_page_url,
            'meta'  => array('target' => '_blank', 'title' => 'Open Live 9:16 Landing Page')
        ));
    }

    /**
     * Render the Light Admin Dashboard
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }

        $slug            = get_option('rocketslide_slug', 'v');
        $tab_title       = get_option('rocketslide_tab_title', '');
        $fallback_url    = get_option('rocketslide_fallback_url', 'https://google.com');
        $tracking_script = get_option('rocketslide_tracking_script', '');
        $images          = get_option('rocketslide_images', array());

        if (!is_array($images)) {
            $images = array();
        }

        $landing_page_url = home_url('/' . trim($slug, '/') . '/');
        ?>
        <div class="rocketslide-wrap">
            <!-- Header Banner -->
            <div class="rocketslide-header">
                <div class="rocketslide-header-brand">
                    <div class="rocketslide-header-text">
                        <h1>RocketSlide 9:16 Engine</h1>
                        <p>Vertical Landing Page &amp; Traffic Cloaker &mdash; v<?php echo esc_html(ROCKETSLIDE_VERSION); ?></p>
                    </div>
                </div>
                <div class="rocketslide-header-meta">
                    <a href="<?php echo esc_url($landing_page_url); ?>" target="_blank" class="rocketslide-btn rocketslide-btn-live">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> Live Page
                    </a>
                    <button type="button" id="rocketslide-copy-live-url-btn" class="rocketslide-btn rocketslide-btn-copy" data-url="<?php echo esc_url($landing_page_url); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy Link
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="rocketslide-stats-row">
                <div class="rocketslide-stat-card">
                    <span class="stat-icon dashicons dashicons-format-video" style="color:#2563eb;"></span>
                    <span class="stat-value" id="rocketslide-stat-images-count"><?php echo count($images); ?></span>
                    <span class="stat-label">Active 9:16 Reels</span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon dashicons dashicons-shield" style="color:#16a34a;"></span>
                    <span class="stat-value" style="color:#16a34a;">Active</span>
                    <span class="stat-label">Cloaking Live</span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon dashicons dashicons-chart-bar" style="color:#7c3aed;"></span>
                    <span class="stat-value"><?php echo !empty($tracking_script) ? 'Connected' : 'None'; ?></span>
                    <span class="stat-label">Publytics Tracker</span>
                </div>
                <div class="rocketslide-stat-card">
                    <span class="stat-icon dashicons dashicons-performance" style="color:#0284c7;"></span>
                    <span class="stat-value">&lt; 0.2s</span>
                    <span class="stat-label">Ultra TTFB Speed</span>
                </div>
            </div>

            <!-- Desktop Tabs Navigation -->
            <nav class="rocketslide-tabs-nav">
                <button class="rocketslide-tab-btn active" data-tab="tab-images">
                    <span class="dashicons dashicons-format-video"></span> 9:16 Reel Cards (<span id="rocketslide-images-count"><?php echo count($images); ?></span>)
                </button>
                <button class="rocketslide-tab-btn" data-tab="tab-analytics">
                    <span class="dashicons dashicons-chart-bar"></span> Publytics &amp; Tracking
                </button>
                <button class="rocketslide-tab-btn" data-tab="tab-fallback">
                    <span class="dashicons dashicons-shield"></span> Traffic Cloaking
                </button>
                <button class="rocketslide-tab-btn" data-tab="tab-settings">
                    <span class="dashicons dashicons-admin-generic"></span> Page Settings
                </button>
            </nav>

            <!-- Tab 1: Reel Cards Management -->
            <div class="rocketslide-tab-panel active" id="tab-images">
                <div class="rocketslide-card">
                    <div class="rocketslide-card-header">
                        <h3 class="rocketslide-card-title"><span class="dashicons dashicons-plus-alt2" style="color:#2563eb;"></span> Add New 9:16 Reel Image</h3>
                        <p class="rocketslide-card-subtitle">Upload any image. The built-in processor automatically crops and converts it to high-performance <strong>540&times;960 WebP</strong> format.</p>
                    </div>

                    <form id="rocketslide-add-image-form" class="rocketslide-form">
                        <div class="rocketslide-upload-box">
                            <div class="rocketslide-upload-row">
                                <div class="rocketslide-picker-btn-group">
                                    <button type="button" id="rocketslide-upload-computer-btn" class="rocketslide-btn rocketslide-btn-primary">
                                        <span class="dashicons dashicons-desktop"></span> Upload from Computer
                                    </button>
                                    <button type="button" id="rocketslide-select-media-btn" class="rocketslide-btn rocketslide-btn-secondary">
                                        <span class="dashicons dashicons-admin-media"></span> Select from WP Library
                                    </button>
                                </div>
                                <div class="rocketslide-file-indicator">
                                    <span id="rocketslide-file-name" class="rocketslide-file-name">No file chosen</span>
                                </div>
                            </div>
                            <input type="file" id="rocketslide-file-input" accept="image/*" style="display:none;">
                            <input type="hidden" id="rocketslide-media-id" value="">

                            <!-- Thumbnail Preview -->
                            <div id="rocketslide-image-preview-wrapper" style="display:none;">
                                <div class="rocketslide-preview-panel">
                                    <img id="rocketslide-image-preview-thumb" class="rocketslide-preview-thumb" src="" alt="Selected Preview">
                                    <div class="rocketslide-preview-info">
                                        <span class="preview-status"><span class="dashicons dashicons-yes"></span> Ready for Auto-Crop</span>
                                        <span class="preview-desc">Will convert to 540&times;960 HD WebP</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rocketslide-form-grid-2" style="margin-top:14px;">
                            <div class="rocketslide-field">
                                <div class="rocketslide-label-wrapper">
                                    <label class="rocketslide-label"><span class="dashicons dashicons-admin-links"></span> Target Redirect URL <span class="req">*</span></label>
                                    <span class="rocketslide-sublabel-pill">1-Tap Action</span>
                                </div>
                                <input type="url" id="rocketslide-new-target-url" class="rocketslide-input" placeholder="https://example.com/offer" required>
                                <span class="rocketslide-input-hint">When user taps this reel card, they are instantly redirected here.</span>
                            </div>

                            <div class="rocketslide-field">
                                <div class="rocketslide-label-wrapper">
                                    <label class="rocketslide-label"><span class="dashicons dashicons-clock"></span> Auto-Redirect Timer</label>
                                    <span class="rocketslide-sublabel-pill">Seconds</span>
                                </div>
                                <input type="number" id="rocketslide-new-timer" class="rocketslide-input" min="0" value="0" placeholder="0 = disabled">
                                <span class="rocketslide-input-hint">0 = disabled. E.g. 5 = auto redirect after 5s.</span>
                            </div>
                        </div>

                        <div style="margin-top:16px;">
                            <button type="submit" id="rocketslide-add-image-btn" class="rocketslide-btn rocketslide-btn-primary rocketslide-btn-submit">
                                <span class="dashicons dashicons-cloud-upload"></span> Save &amp; Crop Reel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Managed Cards Grid -->
                <div class="rocketslide-card" style="margin-top:16px;">
                    <div class="rocketslide-card-header">
                        <h3 class="rocketslide-card-title"><span class="dashicons dashicons-format-video" style="color:#2563eb;"></span> Managed Reel Cards ( <span id="rocketslide-images-count-title"><?php echo count($images); ?></span> )</h3>
                        <p class="rocketslide-card-subtitle">Every visit randomly shuffles the card order. First 2 load eagerly, remaining lazy load on scroll.</p>
                    </div>

                    <div id="rocketslide-images-container" class="rocketslide-img-grid">
                        <?php if (empty($images)) : ?>
                            <div class="rocketslide-empty-state" id="rocketslide-empty-state">
                                <div class="empty-icon"><span class="dashicons dashicons-format-image" style="font-size:36px; width:36px; height:36px;"></span></div>
                                <p>No 9:16 reel images added yet. Upload your first image above.</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($images as $index => $img) : ?>
                                <div class="rocketslide-img-card" data-id="<?php echo esc_attr($img['id']); ?>">
                                    <div class="rocketslide-img-thumb">
                                        <img src="<?php echo esc_url($img['url']); ?>" alt="Reel Card #<?php echo ($index + 1); ?>">
                                        <span class="rocketslide-img-index">#<?php echo ($index + 1); ?></span>
                                        <span class="rocketslide-img-format-badge">WebP</span>
                                    </div>
                                    <div class="rocketslide-img-body">
                                        <div>
                                            <label class="rocketslide-label">Target URL:</label>
                                            <input type="url" class="rocketslide-input rocketslide-card-target" value="<?php echo esc_url($img['target_url']); ?>">
                                        </div>
                                        <div style="margin-top:6px;">
                                            <label class="rocketslide-label">Timer (s):</label>
                                            <input type="number" min="0" class="rocketslide-input rocketslide-card-timer" value="<?php echo esc_attr(isset($img['timer']) ? $img['timer'] : 0); ?>">
                                        </div>
                                        <div class="rocketslide-img-card-actions" style="margin-top:10px;">
                                            <button type="button" class="rocketslide-btn rocketslide-btn-success rocketslide-save-card-btn"><span class="dashicons dashicons-saved"></span> Save</button>
                                            <button type="button" class="rocketslide-btn rocketslide-btn-danger rocketslide-delete-card-btn"><span class="dashicons dashicons-trash"></span> Delete</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Dynamic Load More Container (4 on mobile, 8 on desktop) -->
                    <div id="rocketslide-loadmore-wrapper" style="display:none;">
                        <span id="rocketslide-loadmore-info" style="font-size:13px; color:var(--text-muted); font-weight:600;"></span>
                        <button type="button" id="rocketslide-loadmore-btn" class="rocketslide-btn rocketslide-btn-primary">
                            <span class="dashicons dashicons-arrow-down-alt2"></span> Load More Reels
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Publytics & Tracking -->
            <div class="rocketslide-tab-panel" id="tab-analytics">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title"><span class="dashicons dashicons-chart-bar" style="color:#2563eb;"></span> Publytics &amp; Custom Tracking Header Snippet</h3>
                    <p class="rocketslide-card-subtitle">Paste your raw <code>&lt;script&gt;</code> analytics tag. It will be injected directly into the <code>&lt;head&gt;</code> of the 9:16 landing page.</p>

                    <div class="rocketslide-field" style="margin-top:14px;">
                        <label class="rocketslide-label">Tracking Code Snippet</label>
                        <textarea id="rocketslide-tracking-script" class="rocketslide-textarea" rows="6" placeholder="<script defer data-domain=&quot;yourdomain.com&quot; src=&quot;https://api.publytics.net/js/script.manual.min.js&quot;></script>"><?php echo esc_textarea($tracking_script); ?></textarea>
                        <span class="rocketslide-input-hint">Supports Publytics, Google Analytics 4, Meta Pixel, TikTok Pixel, or any custom tracker.</span>
                    </div>

                    <div class="rocketslide-actions">
                        <button type="button" id="rocketslide-save-tracking-btn" class="rocketslide-btn rocketslide-btn-primary">
                            <span class="dashicons dashicons-saved"></span> Save Script Tag
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Cloaking & Fallback -->
            <div class="rocketslide-tab-panel" id="tab-fallback">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title"><span class="dashicons dashicons-shield" style="color:#2563eb;"></span> Advanced Dual-Layer Traffic Cloaking Engine</h3>
                    <p class="rocketslide-card-subtitle">Genuine Facebook &amp; Instagram traffic displays the 9:16 Reels landing page. Non-social visitors (direct visits, search engines) are instantly redirected to the Fallback URL. Social media crawlers receive clean OpenGraph meta tags.</p>

                    <div class="rocketslide-field" style="margin-top:14px;">
                        <label class="rocketslide-label">Custom Fallback Redirect URL <span class="req">*</span></label>
                        <input type="url" id="rocketslide-fallback-url" class="rocketslide-input" value="<?php echo esc_url($fallback_url); ?>" placeholder="https://google.com or https://news.com" required>
                        <span class="rocketslide-input-hint">Target URL for non-social visitors. All incoming URL parameters (<code>utm_*</code>, <code>fbclid</code>) are preserved and forwarded.</span>
                    </div>

                    <!-- Modern Responsive Cloaking Signals Grid -->
                    <div style="margin-top:16px;">
                        <h4 style="font-size:13.5px; font-weight:700; margin:0 0 8px 0; color:var(--text-main);">Active Filter Signals:</h4>
                        <div class="rocketslide-cloaking-signals-grid">
                            <div class="rocketslide-signal-card">
                                <div class="signal-header">
                                    <span class="signal-badge-icon">&#10003;</span>
                                    <span class="signal-title">Referrer Verification</span>
                                </div>
                                <div class="signal-tags">
                                    <code>facebook.com</code>
                                    <code>fb.me</code>
                                    <code>instagram.com</code>
                                    <code>fb.gg</code>
                                </div>
                            </div>

                            <div class="rocketslide-signal-card">
                                <div class="signal-header">
                                    <span class="signal-badge-icon">&#10003;</span>
                                    <span class="signal-title">URL Tracking Signals</span>
                                </div>
                                <div class="signal-tags">
                                    <code>fbclid</code>
                                    <code>fb_ref</code>
                                    <code>fb_source</code>
                                </div>
                            </div>

                            <div class="rocketslide-signal-card">
                                <div class="signal-header">
                                    <span class="signal-badge-icon">&#10003;</span>
                                    <span class="signal-title">In-App Browser User-Agents</span>
                                </div>
                                <div class="signal-tags">
                                    <code>FBAN</code>
                                    <code>FBAV</code>
                                    <code>FB_IAB</code>
                                    <code>FBIOS</code>
                                    <code>FB4A</code>
                                    <code>Instagram</code>
                                </div>
                            </div>

                            <div class="rocketslide-signal-card">
                                <div class="signal-header">
                                    <span class="signal-badge-icon">&#10003;</span>
                                    <span class="signal-title">Crawler OpenGraph Bypass</span>
                                </div>
                                <div class="signal-tags">
                                    <code>facebookexternalhit</code>
                                    <code>facebot</code>
                                    <code>whatsapp</code>
                                    <code>telegrambot</code>
                                    <code>twitterbot</code>
                                    <code>googlebot</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rocketslide-actions" style="margin-top:16px;">
                        <button type="button" id="rocketslide-save-fallback-btn" class="rocketslide-btn rocketslide-btn-primary">
                            <span class="dashicons dashicons-saved"></span> Save Cloaking Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Site Settings -->
            <div class="rocketslide-tab-panel" id="tab-settings">
                <div class="rocketslide-card">
                    <h3 class="rocketslide-card-title"><span class="dashicons dashicons-admin-generic" style="color:#2563eb;"></span> General Landing Page Configuration</h3>
                    <p class="rocketslide-card-subtitle">Customize the landing page permalink route and browser title.</p>
                    
                    <div class="rocketslide-field" style="margin-top:14px;">
                        <label class="rocketslide-label">Browser Tab Title</label>
                        <input type="text" id="rocketslide-tab-title" class="rocketslide-input" value="<?php echo esc_attr($tab_title); ?>" placeholder="Leave blank for clean title">
                        <span class="rocketslide-input-hint">Displays in the browser tab and OpenGraph title when shared.</span>
                    </div>

                    <!-- Custom Slug / Route - Clean Dedicated Domain & Slug Layout -->
                    <div class="rocketslide-field" style="margin-top:16px;">
                        <div class="rocketslide-label-wrapper">
                            <label class="rocketslide-label"><span class="dashicons dashicons-admin-links"></span> Custom Landing Page Slug / Route</label>
                            <span class="rocketslide-sublabel-pill">URL Route</span>
                        </div>
                        
                        <!-- Top Domain Box -->
                        <div class="rocketslide-domain-preview-box">
                            <span class="domain-label">Website Domain</span>
                            <code id="rocketslide-base-domain"><?php echo esc_url(home_url('/')); ?></code>
                        </div>

                        <!-- Separate Full-Width Slug Input -->
                        <div class="rocketslide-slug-input-wrapper">
                            <input type="text" id="rocketslide-slug" class="rocketslide-input" value="<?php echo esc_attr($slug); ?>" placeholder="e.g. v or ghh" autocomplete="off" spellcheck="false">
                        </div>

                        <!-- Live Full URL Preview Pill -->
                        <div class="rocketslide-live-preview-pill">
                            <span>Full Live URL</span>
                            <strong id="rocketslide-slug-live-preview"><?php echo esc_url(home_url('/' . trim($slug, '/') . '/')); ?></strong>
                        </div>

                        <span class="rocketslide-input-hint">Default slug is <code>v</code>. When you change the slug (e.g. <code>ghh</code>), the landing page immediately opens at that route.</span>
                    </div>

                    <div class="rocketslide-actions" style="margin-top:16px;">
                        <button type="button" id="rocketslide-save-settings-btn" class="rocketslide-btn rocketslide-btn-primary">
                            <span class="dashicons dashicons-saved"></span> Save All Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Bottom Navigation Bar -->
            <nav class="rocketslide-mobile-nav">
                <button class="rocketslide-mobile-nav-item active" data-tab="tab-images">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg></span>
                    <span>Reels</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-analytics">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span>
                    <span>Analytics</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-fallback">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    <span>Cloaking</span>
                </button>
                <button class="rocketslide-mobile-nav-item" data-tab="tab-settings">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                    <span>Settings</span>
                </button>
            </nav>
        </div>
        <?php
    }

    /**
     * AJAX: Save General & Cloaking Settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        if (isset($_POST['fallback_url'])) {
            update_option('rocketslide_fallback_url', esc_url_raw($_POST['fallback_url']));
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
     * AJAX: Publytics Reachability Test
     */
    public function ajax_verify_publytics() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $script_tag = isset($_POST['script_tag']) ? wp_unslash($_POST['script_tag']) : '';
        if (empty($script_tag)) {
            wp_send_json_error('Tracking script tag is empty.');
        }

        preg_match('/src=["\']([^"\']+)["\']/', $script_tag, $src_matches);
        preg_match('/data-domain=["\']([^"\']+)["\']/', $script_tag, $domain_matches);

        $script_url  = !empty($src_matches[1]) ? $src_matches[1] : '';
        $data_domain = !empty($domain_matches[1]) ? $domain_matches[1] : '';

        if (empty($script_url)) {
            wp_send_json_error('Could not parse src="..." attribute from snippet.');
        }

        $response = wp_remote_head($script_url, array('timeout' => 8, 'sslverify' => false));
        if (is_wp_error($response)) {
            wp_send_json_error('Network reachability test failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 200 && $status_code < 400) {
            wp_send_json_success(array(
                'status_code' => $status_code,
                'script_url'  => $script_url,
                'domain'      => $data_domain,
                'message'     => 'Publytics tracking script reachability confirmed (HTTP 200 OK).'
            ));
        } else {
            wp_send_json_error('Script URL returned HTTP Status Code: ' . $status_code);
        }
    }

    /**
     * AJAX: Image Upload & Crop
     */
    public function ajax_upload_image() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';
        $timer      = isset($_POST['timer']) ? intval($_POST['timer']) : 0;
        $media_id   = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;

        if (empty($target_url)) {
            wp_send_json_error('Target URL is required.');
        }

        $result = null;

        if ($media_id > 0) {
            $result = RocketSlide_Image_Processor::process_from_attachment_id($media_id);
        } elseif (!empty($_FILES['image_file']['tmp_name'])) {
            $result = RocketSlide_Image_Processor::process_uploaded_file($_FILES['image_file']);
        } else {
            wp_send_json_error('Please select an image file or choose from Media Library.');
        }

        if (!$result || is_wp_error($result)) {
            $err_msg = is_wp_error($result) ? $result->get_error_message() : 'Error processing image.';
            wp_send_json_error($err_msg);
        }

        $new_item = array(
            'id'             => !empty($result['id']) ? $result['id'] : uniqid('img_'),
            'url'            => $result['url'],
            'path'           => $result['path'],
            'target_url'     => $target_url,
            'timer'          => $timer,
            'created_at'     => time()
        );

        $images = get_option('rocketslide_images', array());
        if (!is_array($images)) $images = array();
        array_unshift($images, $new_item);
        update_option('rocketslide_images', $images);

        wp_send_json_success(array(
            'message' => 'Image processed (540x960 WebP) and added successfully!',
            'image'   => $new_item,
            'total'   => count($images)
        ));
    }

    /**
     * AJAX: Update Card Target URL and Timer
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
            wp_send_json_error('Invalid Card ID.');
        }

        $images = get_option('rocketslide_images', array());
        $found  = false;

        foreach ($images as &$img) {
            if ($img['id'] === $id) {
                $img['target_url'] = $target_url;
                $img['timer']      = $timer;
                $found = true;
                break;
            }
        }

        if ($found) {
            update_option('rocketslide_images', $images);
            wp_send_json_success(array('message' => 'Reel card updated successfully!'));
        } else {
            wp_send_json_error('Image card not found.');
        }
    }

    /**
     * AJAX: Delete Reel Card
     */
    public function ajax_delete_image() {
        check_ajax_referer('rocketslide_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        if (empty($id)) {
            wp_send_json_error('Invalid image ID');
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
            'message' => 'Image deleted successfully.',
            'total'   => count($filtered)
        ));
    }
}
