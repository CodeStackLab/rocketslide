<?php
/**
 * class-rocketslide-frontend.php
 *
 * FRONTEND ROUTING ENGINE — Isolated 9:16 Landing Page Loader
 * ============================================================
 *
 * Responsibilities:
 *  1. Register a custom WordPress rewrite rule so the slug /v/ (configurable)
 *     maps to our standalone template — completely bypassing the active theme.
 *  2. Multi-point bulletproof interception: hooks into 'init', 'parse_request',
 *     and 'template_redirect' at priority 1 to guarantee 100% compatibility
 *     across any domain, server setup (Nginx, Apache, LiteSpeed), cPanel,
 *     Cloudflare, reverse proxies, and subdomains (e.g. go.infucar.com).
 *  3. Run the dual-layer cloaking check:
 *       • Bot       -> Render template with clean OG meta tags (no redirect)
 *       • FB/IG     -> Render 9:16 landing page template
 *       • Other     -> HTTP 302 redirect to Fallback URL (with param forwarding)
 *  4. Serve the fully isolated HTML template (no wp_head / wp_footer calls).
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

		// Tell WP to recognise our custom query var
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );

		// Early Interception Point 1: 'init' (priority 1)
		add_action( 'init', array( $this, 'intercept_request_early' ), 1 );

		// Early Interception Point 2: 'parse_request' (priority 1)
		add_action( 'parse_request', array( $this, 'intercept_request' ), 1 );

		// Standard Interception Point 3: 'template_redirect' (priority 1)
		add_action( 'template_redirect', array( $this, 'intercept_request' ), 1 );
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
	// REQUEST INTERCEPTION
	// -----------------------------------------------------------

	/**
	 * Early check during 'init' for direct URI path matches
	 */
	public function intercept_request_early() {
		if ( self::is_landing_request() ) {
			$this->intercept_request();
		}
	}

	/**
	 * Intercept the request and decide what to serve.
	 */
	public function intercept_request() {
		if ( ! self::is_landing_request() ) {
			return; // Not our page — let WordPress handle it normally
		}

		// ——— DUAL-LAYER CLOAKING CHECK ———

		// BRANCH A: Known bot/crawler
		//   -> Render the landing page template (which includes OG tags)
		//   -> Do NOT redirect — bots need to see clean OG meta for link previews
		if ( class_exists( 'RocketSlide_Cloaking' ) && RocketSlide_Cloaking::is_bot() ) {
			$this->render_landing_page();
			exit;
		}

		// BRANCH B: Non-Facebook/Instagram traffic (direct, Google, other)
		//   -> Immediately 302-redirect to the Custom Fallback URL
		//   -> Preserve & forward all incoming query parameters (fbclid, utm_*, gclid...)
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
		$slug = self::get_slug();

		// 1. Via WP query var
		if ( function_exists( 'get_query_var' ) && get_query_var( self::QUERY_VAR ) === '1' ) {
			return true;
		}

		// 2. Via explicit GET parameter
		if ( isset( $_GET[ self::QUERY_VAR ] ) && '1' === (string) $_GET[ self::QUERY_VAR ] ) {
			return true;
		}

		// 3. Via direct slug query param (e.g. ?v=1)
		if ( isset( $_GET[ $slug ] ) && '1' === (string) $_GET[ $slug ] ) {
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
		$home_path = trim( parse_url( $home_url, PHP_URL_PATH ), '/' );
		if ( ! empty( $home_path ) && 0 === strpos( $request_path, $home_path ) ) {
			$request_path = trim( substr( $request_path, strlen( $home_path ) ), '/' );
		}

		// Strip 'index.php/' if PATHINFO permalinks are active
		if ( 0 === strpos( $request_path, 'index.php' ) ) {
			$request_path = trim( substr( $request_path, 9 ), '/' );
		}

		// Normalize to lowercase for case-insensitive matching
		$request_path = strtolower( trim( $request_path, '/' ) );
		$slug_lower   = strtolower( trim( $slug, '/' ) );

		if ( $request_path === $slug_lower ) {
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
