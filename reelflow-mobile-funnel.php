<?php
/**
 * Plugin Name:  ReelFlow 9:16 Reels Landing Page
 * Plugin URI:   https://reelflow.com/plugin
 * Description:  All-in-one isolated 9:16 vertical reels landing page with advanced Facebook/Instagram
 *               dual-layer cloaking engine, dynamic image shuffling, infinite scroll, Publytics
 *               integration, automatic 540x960 WebP conversion, and a modern dark-mode admin dashboard.
 *               100% self-contained — no custom theme or external pages required.
 * Version:      2.0.0
 * Author:       ReelFlow Engine
 * Author URI:   https://reelflow.com
 * Text Domain:  reelflow-lp
 * License:      GPL-2.0+
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.5
 * Requires PHP: 7.4
 */

// ============================================================
// SECURITY: Block direct file access
// ============================================================
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================
// PLUGIN CONSTANTS
// ============================================================
define( 'REELFLOW_VERSION',     '2.0.0' );
define( 'REELFLOW_PLUGIN_FILE', __FILE__ );
define( 'REELFLOW_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'REELFLOW_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Returns the absolute filesystem path to the plugin's dedicated upload folder.
 * We use a function (not a constant) because wp_upload_dir() may not be
 * available this early in some WordPress environments.
 *
 * @return string  e.g. /var/www/html/wp-content/uploads/reelflow/
 */
function reelflow_uploads_dir() {
	return wp_upload_dir()['basedir'] . '/reelflow/';
}

/**
 * Returns the public URL to the plugin's dedicated upload folder.
 *
 * @return string  e.g. https://example.com/wp-content/uploads/reelflow/
 */
function reelflow_uploads_url() {
	return wp_upload_dir()['baseurl'] . '/reelflow/';
}

/**
 * Returns default sample reel images if user has not uploaded custom images yet.
 *
 * @return array
 */
function reelflow_get_default_images() {
	return array(
		array(
			'id'         => 'sample_1',
			'url'        => REELFLOW_PLUGIN_URL . 'assets/images/sample-1.webp',
			'path'       => REELFLOW_PLUGIN_DIR . 'assets/images/sample-1.webp',
			'target_url' => 'https://google.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_2',
			'url'        => REELFLOW_PLUGIN_URL . 'assets/images/sample-2.webp',
			'path'       => REELFLOW_PLUGIN_DIR . 'assets/images/sample-2.webp',
			'target_url' => 'https://bing.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_3',
			'url'        => REELFLOW_PLUGIN_URL . 'assets/images/sample-3.webp',
			'path'       => REELFLOW_PLUGIN_DIR . 'assets/images/sample-3.webp',
			'target_url' => 'https://yahoo.com',
			'timer'      => 0,
			'created_at' => time()
		),
	);
}

// ============================================================
// LOAD CORE CLASSES (order matters)
// ============================================================
require_once REELFLOW_PLUGIN_DIR . 'includes/class-reelflow-cloaking.php';
require_once REELFLOW_PLUGIN_DIR . 'includes/class-reelflow-image-processor.php';
require_once REELFLOW_PLUGIN_DIR . 'includes/class-reelflow-frontend.php';
require_once REELFLOW_PLUGIN_DIR . 'includes/class-reelflow-admin.php';

// ============================================================
// MAIN PLUGIN SINGLETON
// ============================================================

/**
 * Class ReelFlow_Landing_Page
 *
 * Root singleton that bootstraps every component.
 */
final class ReelFlow_Landing_Page {

	/** @var ReelFlow_Landing_Page|null  Singleton instance */
	private static $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return ReelFlow_Landing_Page
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance() */
	private function __construct() {
		$this->register_lifecycle_hooks();
		$this->boot_components();
	}

	/**
	 * Register activation / deactivation hooks.
	 * These must be called with the MAIN plugin file path.
	 */
	private function register_lifecycle_hooks() {
		register_activation_hook( REELFLOW_PLUGIN_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( REELFLOW_PLUGIN_FILE, array( $this, 'on_deactivate' ) );
	}

	/**
	 * Instantiate every component class.
	 * Each class registers its own WordPress hooks internally.
	 */
	private function boot_components() {
		new ReelFlow_Frontend();
		new ReelFlow_Admin();
		// ReelFlow_Cloaking & ReelFlow_Image_Processor are static-utility classes
		// — they don't need instantiation; they're called by Frontend & Admin.
	}

	// -----------------------------------------------------------
	// ACTIVATION
	// -----------------------------------------------------------

	/**
	 * Runs once when the plugin is activated.
	 *  - Creates the /uploads/reelflow/ directory
	 *  - Seeds default option values (only if they don't exist yet)
	 *  - Registers rewrite rules & flushes so /v/ works immediately
	 */
	public function on_activate() {
		// Create dedicated upload folder
		$upload_dir = reelflow_uploads_dir();
		if ( ! file_exists( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );

			// Drop an .htaccess that allows webp serving (Apache)
			$htaccess = $upload_dir . '.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				file_put_contents( $htaccess, "Options -Indexes\n" );
			}
		}

		// Seed default options (won't overwrite existing values)
		$defaults = array(
			'reelflow_slug'             => 'v',
			'reelflow_tab_title'        => 'Exclusive Video Content',
			'reelflow_fallback_url'     => 'https://google.com',
			'reelflow_tracking_script'  => '',
			'reelflow_test_mode'        => '0',
			'reelflow_images'           => reelflow_get_default_images(),
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		// Register rewrite rules THEN flush so the slug works immediately
		ReelFlow_Frontend::register_rewrite_rules();
		flush_rewrite_rules();
	}

	// -----------------------------------------------------------
	// DEACTIVATION
	// -----------------------------------------------------------

	/**
	 * Runs once when the plugin is deactivated.
	 * Flushes rewrite rules so WordPress removes the custom slug.
	 * Note: We intentionally do NOT delete uploaded images or options
	 * on deactivation — only on uninstall (which would use uninstall.php).
	 */
	public function on_deactivate() {
		flush_rewrite_rules();
	}
}

// ============================================================
// BOOTSTRAP — Start the plugin on 'plugins_loaded'
// ============================================================
add_action( 'plugins_loaded', array( 'ReelFlow_Landing_Page', 'get_instance' ) );
