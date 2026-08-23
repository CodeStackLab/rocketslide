<?php
/**
 * Plugin Name:  Infucar 9:16 Reels Landing Page
 * Plugin URI:   https://infucar.com/plugin
 * Description:  All-in-one isolated 9:16 vertical reels landing page with advanced Facebook/Instagram
 *               dual-layer cloaking engine, dynamic image shuffling, infinite scroll, Publytics
 *               integration, automatic 540x960 WebP conversion, and a modern dark-mode admin dashboard.
 *               100% self-contained — no custom theme or external pages required.
 * Version:      2.0.0
 * Author:       Infucar Engine
 * Author URI:   https://infucar.com
 * Text Domain:  infucar-lp
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
define( 'INFUCAR_VERSION',     '2.0.0' );
define( 'INFUCAR_PLUGIN_FILE', __FILE__ );
define( 'INFUCAR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'INFUCAR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Returns the absolute filesystem path to the plugin's dedicated upload folder.
 * We use a function (not a constant) because wp_upload_dir() may not be
 * available this early in some WordPress environments.
 *
 * @return string  e.g. /var/www/html/wp-content/uploads/infucar/
 */
function infucar_uploads_dir() {
	return wp_upload_dir()['basedir'] . '/infucar/';
}

/**
 * Returns the public URL to the plugin's dedicated upload folder.
 *
 * @return string  e.g. https://example.com/wp-content/uploads/infucar/
 */
function infucar_uploads_url() {
	return wp_upload_dir()['baseurl'] . '/infucar/';
}

/**
 * Returns default sample reel images if user has not uploaded custom images yet.
 *
 * @return array
 */
function infucar_get_default_images() {
	return array(
		array(
			'id'         => 'sample_1',
			'url'        => INFUCAR_PLUGIN_URL . 'assets/images/sample-1.webp',
			'path'       => INFUCAR_PLUGIN_DIR . 'assets/images/sample-1.webp',
			'target_url' => 'https://google.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_2',
			'url'        => INFUCAR_PLUGIN_URL . 'assets/images/sample-2.webp',
			'path'       => INFUCAR_PLUGIN_DIR . 'assets/images/sample-2.webp',
			'target_url' => 'https://bing.com',
			'timer'      => 0,
			'created_at' => time()
		),
		array(
			'id'         => 'sample_3',
			'url'        => INFUCAR_PLUGIN_URL . 'assets/images/sample-3.webp',
			'path'       => INFUCAR_PLUGIN_DIR . 'assets/images/sample-3.webp',
			'target_url' => 'https://yahoo.com',
			'timer'      => 0,
			'created_at' => time()
		),
	);
}

// ============================================================
// LOAD CORE CLASSES (order matters)
// ============================================================
require_once INFUCAR_PLUGIN_DIR . 'includes/class-infucar-cloaking.php';
require_once INFUCAR_PLUGIN_DIR . 'includes/class-infucar-image-processor.php';
require_once INFUCAR_PLUGIN_DIR . 'includes/class-infucar-frontend.php';
require_once INFUCAR_PLUGIN_DIR . 'includes/class-infucar-admin.php';

// ============================================================
// MAIN PLUGIN SINGLETON
// ============================================================

/**
 * Class Infucar_Landing_Page
 *
 * Root singleton that bootstraps every component.
 */
final class Infucar_Landing_Page {

	/** @var Infucar_Landing_Page|null  Singleton instance */
	private static $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return Infucar_Landing_Page
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
		register_activation_hook( INFUCAR_PLUGIN_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( INFUCAR_PLUGIN_FILE, array( $this, 'on_deactivate' ) );
	}

	/**
	 * Instantiate every component class.
	 * Each class registers its own WordPress hooks internally.
	 */
	private function boot_components() {
		new Infucar_Frontend();
		new Infucar_Admin();
		// Infucar_Cloaking & Infucar_Image_Processor are static-utility classes
		// — they don't need instantiation; they're called by Frontend & Admin.
	}

	// -----------------------------------------------------------
	// ACTIVATION
	// -----------------------------------------------------------

	/**
	 * Runs once when the plugin is activated.
	 *  - Creates the /uploads/infucar/ directory
	 *  - Seeds default option values (only if they don't exist yet)
	 *  - Registers rewrite rules & flushes so /v/ works immediately
	 */
	public function on_activate() {
		// Create dedicated upload folder
		$upload_dir = infucar_uploads_dir();
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
			'infucar_slug'             => 'v',
			'infucar_tab_title'        => 'Exclusive Video Content',
			'infucar_fallback_url'     => 'https://google.com',
			'infucar_tracking_script'  => '',
			'infucar_test_mode'        => '0',
			'infucar_images'           => infucar_get_default_images(),
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		// Register rewrite rules THEN flush so the slug works immediately
		Infucar_Frontend::register_rewrite_rules();
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
add_action( 'plugins_loaded', array( 'Infucar_Landing_Page', 'get_instance' ) );
