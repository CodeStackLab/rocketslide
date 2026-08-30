<?php
/**
 * Plugin Name:  RocketSlide - 9:16 Vertical Landing Page & Traffic Cloaker
 * Plugin URI:   https://rocketslide.com
 * Description:  Ultra-fast, fully isolated 9:16 mobile-first vertical reels landing page with
 *               dual-layer cloaking engine, dynamic image shuffling, infinite scroll, Publytics
 *               integration, automatic 540x960 WebP conversion, and a modern light-mode admin dashboard.
 *               100% self-contained — no custom theme or external pages required.
 * Version:      3.7.0
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
define( 'ROCKETSLIDE_VERSION',     '3.7.0' );
define( 'ROCKETSLIDE_PLUGIN_FILE', __FILE__ );
define( 'ROCKETSLIDE_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ROCKETSLIDE_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Returns the absolute filesystem path to the plugin's dedicated upload folder.
 *
 * @return string e.g. /var/www/html/wp-content/uploads/rocketslide/
 */
function rocketslide_uploads_dir() {
	return wp_upload_dir()['basedir'] . '/rocketslide/';
}

/**
 * Returns the public URL to the plugin's dedicated upload folder.
 *
 * @return string e.g. https://example.com/wp-content/uploads/rocketslide/
 */
function rocketslide_uploads_url() {
	return wp_upload_dir()['baseurl'] . '/rocketslide/';
}

/**
 * Returns default images array (empty by default so user has full control).
 *
 * @return array
 */
function rocketslide_get_default_images() {
	return array();
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

	/** @var RocketSlide_Landing_Page|null Singleton instance */
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
		$this->self_heal_defaults();
		$this->boot_components();
	}

	/**
	 * Register activation / deactivation hooks.
	 */
	private function register_lifecycle_hooks() {
		register_activation_hook( ROCKETSLIDE_PLUGIN_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( ROCKETSLIDE_PLUGIN_FILE, array( $this, 'on_deactivate' ) );
	}

	/**
	 * Ensure upload directory and default options exist on any site automatically.
	 */
	private function self_heal_defaults() {
		$installed_ver = get_option( 'rocketslide_version' );
		if ( $installed_ver !== ROCKETSLIDE_VERSION ) {
			update_option( 'rocketslide_version', ROCKETSLIDE_VERSION );

			$upload_dir = rocketslide_uploads_dir();
			if ( ! file_exists( $upload_dir ) ) {
				wp_mkdir_p( $upload_dir );
			}

			$defaults = array(
				'rocketslide_slug'             => 'v',
				'rocketslide_tab_title'        => '',
				'rocketslide_fallback_url'     => 'https://google.com',
				'rocketslide_tracking_script'  => '',
				'rocketslide_images'           => array(),
			);

			foreach ( $defaults as $key => $value ) {
				if ( false === get_option( $key ) ) {
					add_option( $key, $value );
				}
			}
		}
	}

	/**
	 * Instantiate every component class.
	 */
	private function boot_components() {
		new RocketSlide_Frontend();
		new RocketSlide_Admin();
	}

	// -----------------------------------------------------------
	// ACTIVATION
	// -----------------------------------------------------------

	/**
	 * Runs once when the plugin is activated.
	 */
	public function on_activate() {
		$upload_dir = rocketslide_uploads_dir();
		if ( ! file_exists( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );

			$htaccess = $upload_dir . '.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				file_put_contents( $htaccess, "Options -Indexes\n" );
			}
		}

		$defaults = array(
			'rocketslide_slug'             => 'v',
			'rocketslide_tab_title'        => '',
			'rocketslide_fallback_url'     => 'https://google.com',
			'rocketslide_tracking_script'  => '',
			'rocketslide_images'           => array(),
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		RocketSlide_Frontend::register_rewrite_rules();
		flush_rewrite_rules();
	}

	// -----------------------------------------------------------
	// DEACTIVATION
	// -----------------------------------------------------------

	public function on_deactivate() {
		flush_rewrite_rules();
	}
}

// ============================================================
// BOOTSTRAP — Start the plugin on 'plugins_loaded'
// ============================================================
add_action( 'plugins_loaded', array( 'RocketSlide_Landing_Page', 'get_instance' ) );
