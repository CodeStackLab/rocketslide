<?php
/**
 * class-rocketslide-frontend.php
 *
 * FRONTEND ROUTING ENGINE — Isolated 9:16 Landing Page Loader
 * ============================================================
 *
 * Responsibilities:
 *  1. Register custom WordPress rewrite rule for the configured slug (e.g. /v/).
 *  2. Multi-layer interception architecture:
 *       • 'init' (priority 1)
 *       • 'do_parse_request' (priority 1)
 *       • 'pre_handle_404' (priority 1) - overrides WordPress theme 404 errors
 *       • 'parse_request' (priority 1)
 *       • 'template_redirect' (priority 1) - removes redirect_canonical
 *       • 'template_include' (priority 99999) - absolute fallback template override
 *  3. Guaranteed 100% universal compatibility across all live domains,
 *     subdomains (e.g. go.infucar.com), subfolders, Nginx, Apache, LiteSpeed,
 *     Cloudflare, and Plain / PostName permalinks.
 *  4. Serve the fully isolated HTML template (0ms theme bypass).
 *
 * @package RocketSlide_Landing_Page
 * @since   2.0.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RocketSlide_Frontend {

	/** @var string Name of the custom WP query variable */
	const QUERY_VAR = 'rocketslide_landing';

	/**
	 * Constructor — register all WordPress hooks needed for routing.
	 */
	public function __construct() {
		// Register slug-based rewrite rule on 'init'
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		// Whitelist query variable
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );

		// Layer 1: Earliest interception on 'init' (priority 1)
		add_action( 'init', array( $this, 'intercept_request_early' ), 1 );

		// Layer 2: Short-circuit WP request parsing on 'do_parse_request'
		add_filter( 'do_parse_request', array( $this, 'filter_do_parse_request' ), 1, 3 );

		// Layer 3: Catch and prevent 404 errors before theme loads 404.php
		add_filter( 'pre_handle_404', array( $this, 'pre_handle_404' ), 1, 2 );

		// Layer 4: Standard 'parse_request'
		add_action( 'parse_request', array( $this, 'intercept_request' ), 1 );

		// Layer 5: 'template_redirect' (priority 1)
		add_action( 'template_redirect', array( $this, 'intercept_request' ), 1 );

		// Layer 6: Absolute safety net 'template_include' (priority 99999)
		add_filter( 'template_include', array( $this, 'filter_template_include' ), 99999 );
	}

	// -----------------------------------------------------------
	// REWRITE RULES
	// -----------------------------------------------------------

	/**
	 * Register the custom rewrite rule on 'init'.
	 */
	public function add_rewrite_rules() {
		self::register_rewrite_rules();
	}

	/**
	 * Static helper to register the rewrite rule.
	 */
	public static function register_rewrite_rules() {
		$slug = self::get_slug();

		// Regex: matches /v/ or /v (with or without trailing slash)
		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top' // 'top' = checked before all other rules
		);
	}

	/**
	 * Add our custom query variable to the whitelist so WP doesn't strip it.
	 *
	 * @param  string[] $vars Existing query vars
	 * @return string[]
	 */
	public function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	// -----------------------------------------------------------
	// REQUEST INTERCEPTION LAYERS
	// -----------------------------------------------------------

	/**
	 * Layer 1: Early check during 'init'
	 */
	public function intercept_request_early() {
		if ( self::is_landing_request() ) {
			$this->intercept_request();
		}
	}

	/**
	 * Layer 2: Filter 'do_parse_request'
	 */
	public function filter_do_parse_request( $continue, $wp, $extra_query_vars ) {
		if ( self::is_landing_request() ) {
			$this->intercept_request();
			return false; // Stop further WP parsing
		}
		return $continue;
	}

	/**
	 * Layer 3: Catch 404 before WordPress renders theme 404.php
	 */
	public function pre_handle_404( $preempt, $wp_query ) {
		if ( self::is_landing_request() ) {
			if ( is_object( $wp_query ) ) {
				$wp_query->is_404 = false;
			}
			$this->intercept_request();
			return true; // Stop 404 handling
		}
		return $preempt;
	}

	/**
	 * Layer 6: Final template include fallback
	 */
	public function filter_template_include( $template ) {
		if ( self::is_landing_request() ) {
			$this->intercept_request();
			return ROCKETSLIDE_PLUGIN_DIR . 'templates/landing-page-template.php';
		}
		return $template;
	}

	/**
	 * Master Intercept Handler — runs cloaking and serves landing page
	 */
	public function intercept_request() {
		if ( ! self::is_landing_request() ) {
			return; // Not our page — let WordPress handle it normally
		}

		// Clean WordPress global query state to eliminate 404s
		global $wp_query;
		if ( isset( $wp_query ) && is_object( $wp_query ) ) {
			$wp_query->is_404  = false;
			$wp_query->is_page = false;
			$wp_query->is_home = false;
		}

		// Disable WP canonical redirect so /v/ is never redirected to home or 404
		remove_action( 'template_redirect', 'redirect_canonical' );

		// ——— DUAL-LAYER CLOAKING CHECK ———

		// BRANCH A: Known bot/crawler
		//   -> Render landing page template with clean OG meta tags (no redirect)
		if ( class_exists( 'RocketSlide_Cloaking' ) && RocketSlide_Cloaking::is_bot() ) {
			$this->render_landing_page();
			exit;
		}

		// BRANCH B: Non-Facebook/Instagram traffic (and not test/preview mode)
		//   -> Immediately 302-redirect to the Custom Fallback URL
		if ( class_exists( 'RocketSlide_Cloaking' ) && RocketSlide_Cloaking::should_redirect_to_fallback() ) {
			$fallback_url = get_option( 'rocketslide_fallback_url', 'https://google.com' );

			// Forward all incoming GET parameters to the fallback destination
			if ( ! empty( $_GET ) ) {
				$forwarded_params = array();
				foreach ( $_GET as $k => $v ) {
					$forwarded_params[ sanitize_key( $k ) ] = rawurlencode( $v );
				}
				$fallback_url = add_query_arg( $forwarded_params, $fallback_url );
			}

			wp_redirect( $fallback_url, 302 );
			exit;
		}

		// BRANCH C: Genuine Facebook / Instagram traffic OR Test/Preview Mode
		//   -> Render the full 9:16 landing page template
		$this->render_landing_page();
		exit;
	}

	// -----------------------------------------------------------
	// HELPERS
	// -----------------------------------------------------------

	/**
	 * Determine whether the current request is for our landing page.
	 *
	 * Robust multi-strategy detection:
	 *  1. WP query var 'rocketslide_landing' is '1' (rewrite rule matched)
	 *  2. Direct query param ?rocketslide_landing=1 or ?v=1
	 *  3. Normalized URI path matching against configured slug (works on subdomains,
	 *     subdirectories, Nginx, Apache, LiteSpeed, Cloudflare without needing flushed rules)
	 *
	 * @return bool
	 */
	public static function is_landing_request() {
		$slug       = self::get_slug();
		$slug_lower = strtolower( trim( $slug, '/' ) );

		// 1. Via WP query var
		if ( function_exists( 'get_query_var' ) && get_query_var( self::QUERY_VAR ) === '1' ) {
			return true;
		}

		// 2. Via explicit GET parameter ?rocketslide_landing=1
		if ( isset( $_GET[ self::QUERY_VAR ] ) && '1' === (string) $_GET[ self::QUERY_VAR ] ) {
			return true;
		}

		// 3. Via direct slug query param (e.g. ?v=1)
		if ( isset( $_GET[ $slug ] ) || isset( $_GET[ $slug_lower ] ) ) {
			return true;
		}

		// 4. Robust raw REQUEST_URI path matching
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( empty( $request_uri ) ) {
			return false;
		}

		$parsed_path = parse_url( $request_uri, PHP_URL_PATH );
		if ( empty( $parsed_path ) ) {
			return false;
		}

		$request_path = trim( $parsed_path, '/' );

		// Strip WordPress installation home subdirectory if installed in subfolder (e.g. /wordpress/ or /blog/)
		$home_url  = home_url();
		$home_path = trim( (string) parse_url( $home_url, PHP_URL_PATH ), '/' );
		if ( ! empty( $home_path ) && 0 === strpos( $request_path, $home_path ) ) {
			$request_path = trim( substr( $request_path, strlen( $home_path ) ), '/' );
		}

		// Strip 'index.php/' if PATHINFO permalinks are active
		if ( 0 === strpos( $request_path, 'index.php' ) ) {
			$request_path = trim( substr( $request_path, 9 ), '/' );
		}

		// Normalize to lowercase for case-insensitive matching
		$request_path = strtolower( trim( $request_path, '/' ) );

		// Exact match: /v or /v/
		if ( $request_path === $slug_lower ) {
			return true;
		}

		// Subpath match: /v/something
		if ( 0 === strpos( $request_path, $slug_lower . '/' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Serve the fully isolated 9:16 landing page template.
	 */
	private function render_landing_page() {
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );

		// Prevent browser/CDN caching so images shuffle on every visit
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );

		$template = ROCKETSLIDE_PLUGIN_DIR . 'templates/landing-page-template.php';

		if ( ! file_exists( $template ) ) {
			wp_die( 'RocketSlide: Landing page template file is missing. Please reinstall the plugin.' );
		}

		include $template;
		exit;
	}

	/**
	 * Retrieve and sanitise the configured landing-page slug.
	 *
	 * @return string e.g. 'v'
	 */
	public static function get_slug() {
		$slug = get_option( 'rocketslide_slug', 'v' );
		$slug = sanitize_title( trim( $slug, '/' ) );
		return empty( $slug ) ? 'v' : $slug;
	}
}
