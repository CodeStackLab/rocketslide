<?php
/**
 * Plugin Name:  RocketSlide - 9:16 Mobile Landing Page
 * Plugin URI:   https://rocketslide.com/plugin
 * Description:  All-in-one isolated 9:16 vertical reels landing page with advanced Facebook/Instagram
 *               dual-layer cloaking engine, dynamic image shuffling, infinite scroll, Publytics
 *               integration, automatic 540x960 WebP conversion, and a modern dark-mode admin dashboard.
 *               100% self-contained — no custom theme or external pages required.
 * Version:      2.4.0
 * Author:       RocketSlide Engine
 * Author URI:   https://rocketslide.com
 * Text Domain:  rocketslide-lp
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
define( 'ROCKETSLIDE_VERSION',     '2.4.0' );
define( 'ROCKETSLIDE_PLUGIN_FILE', __FILE__ );
define( 'ROCKETSLIDE_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ROCKETSLIDE_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Returns the absolute filesystem path to the plugin's dedicated upload folder.
 * We use a function (not a constant) because wp_upload_dir() may not be
 * available this early in some WordPress environments.
 *
 * @return string  e.g. /var/www/html/wp-content/uploads/rocketslide/
 */
function rocketslide_uploads_dir() {
	return wp_upload_dir()['basedir'] . '/rocketslide/';
}

/**
 * Returns the public URL to the plugin's dedicated upload folder.
 *
 * @return string  e.g. https://example.com/wp-content/uploads/rocketslide/
 */
function rocketslide_uploads_url() {
	return wp_upload_dir()['baseurl'] . '/rocketslide/';
}

/**
 * Returns default sample reel images if user has not uploaded custom images yet.
 *
 * @return array
 */
function rocketslide_get_default_images() {
	return array(
		array(
			'id'         => 'sample_1',
			'url'        => ROCKETSLIDE_PLUGIN_URL . 'assets/images/sample-1.webp',
			'path'       => ROCKETSLIDE_PLUGIN_DIR . 'assets/images/sample-1.webp',
			'target_url' => 'https://google.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_2',
			'url'        => ROCKETSLIDE_PLUGIN_URL . 'assets/images/sample-2.webp',
			'path'       => ROCKETSLIDE_PLUGIN_DIR . 'assets/images/sample-2.webp',
			'target_url' => 'https://bing.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_3',
			'url'        => ROCKETSLIDE_PLUGIN_URL . 'assets/images/sample-3.webp',
			'path'       => ROCKETSLIDE_PLUGIN_DIR . 'assets/images/sample-3.webp',
			'target_url' => 'https://yahoo.com',
			'timer'      => 0,
			'created_at' => time()
		),
	);
}

// ============================================================
// LOAD CORE CLASSES (order matters)
// ============================================================
require_once ROCKETSLIDE_PLUGIN_DIR . 'includes/class-rocketslide-cloaking.php';
require_once ROCKETSLIDE_PLUGIN_DIR . 'includes/class-rocketslide-image-processor.php';
require_once ROCKETSLIDE_PLUGIN_DIR . 'includes/class-rocketslide-frontend.php';
require_once ROCKETSLIDE_PLUGIN_DIR . 'includes/class-rocketslide-admin.php';

// ============================================================
// MAIN PLUGIN SINGLETON
// ============================================================

/**
 * Class RocketSlide_Landing_Page
 *
 * Root singleton that bootstraps every component.
 */
final class RocketSlide_Landing_Page {

	/** @var RocketSlide_Landing_Page|null  Singleton instance */
	private static $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return RocketSlide_Landing_Page
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
		register_activation_hook( ROCKETSLIDE_PLUGIN_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( ROCKETSLIDE_PLUGIN_FILE, array( $this, 'on_deactivate' ) );
	}

	/**
	 * Instantiate every component class.
	 * Each class registers its own WordPress hooks internally.
	 */
	private function boot_components() {
		new RocketSlide_Frontend();
		new RocketSlide_Admin();
		// RocketSlide_Cloaking & RocketSlide_Image_Processor are static-utility classes
		// — they don't need instantiation; they're called by Frontend & Admin.
	}

	// -----------------------------------------------------------
	// ACTIVATION
	// -----------------------------------------------------------

	/**
	 * Runs once when the plugin is activated.
	 *  - Creates the /uploads/rocketslide/ directory
	 *  - Seeds default option values (only if they don't exist yet)
	 *  - Registers rewrite rules & flushes so /v/ works immediately
	 */
	public function on_activate() {
		// Create dedicated upload folder
		$upload_dir = rocketslide_uploads_dir();
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
			'rocketslide_slug'             => 'v',
			'rocketslide_tab_title'        => 'Exclusive Video Content',
			'rocketslide_fallback_url'     => 'https://google.com',
			'rocketslide_tracking_script'  => '',
			'rocketslide_test_mode'        => '0',
			'rocketslide_images'           => rocketslide_get_default_images(),
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		// Register rewrite rules THEN flush so the slug works immediately
		RocketSlide_Frontend::register_rewrite_rules();
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
add_action( 'plugins_loaded', array( 'RocketSlide_Landing_Page', 'get_instance' ) );
