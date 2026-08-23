<?php
/**
 * class-infucar-frontend.php
 *
 * FRONTEND ROUTING ENGINE — Isolated 9:16 Landing Page Loader
 * ============================================================
 *
 * Responsibilities:
 *  1. Register a custom WordPress rewrite rule so the slug  /v/  (configurable)
 *     maps to our standalone template — completely bypassing the active theme.
 *  2. Intercept requests matching the slug at 'template_redirect' priority 1.
 *  3. Run the dual-layer cloaking check:
 *       • Bot      → Render template with clean OG meta tags (no redirect)
 *       • FB/IG    → Render 9:16 landing page template
 *       • Other    → HTTP 302 redirect to Fallback URL (with param forwarding)
 *  4. Serve the fully isolated HTML template (no wp_head / wp_footer calls).
 *
 * @package Infucar_Landing_Page
 * @since   2.0.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Infucar_Frontend {

	/** @var string  Name of the custom WP query variable */
	const QUERY_VAR = 'infucar_landing';

	/**
	 * Constructor — register all WordPress hooks needed for routing.
	 */
	public function __construct() {
		// Register slug-based rewrite rule on 'init'
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		// Tell WP to recognise our custom query var
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );

		// Intercept at the earliest possible point before any theme output
		add_action( 'template_redirect', array( $this, 'intercept_request' ), 1 );
	}

	// -----------------------------------------------------------
	// REWRITE RULES
	// -----------------------------------------------------------

	/**
	 * Register the custom rewrite rule.
	 * Called on 'init' every page load (WP caches this internally via the
	 * rewrite rules option — no performance impact).
	 *
	 * Also called statically from the main plugin file on activation
	 * (before flush_rewrite_rules()).
	 */
	public function add_rewrite_rules() {
		self::register_rewrite_rules();
	}

	/**
	 * Static helper so the main plugin file can call this before WordPress
	 * fires 'init' during the activation hook.
	 */
	public static function register_rewrite_rules() {
		$slug = self::get_slug();

		// Regex: matches /v/ or /v (with or without trailing slash)
		// Maps to: ?infucar_landing=1
		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'  // 'top' = checked before all other rules
		);
	}

	/**
	 * Add our custom query variable to the whitelist so WP doesn't strip it.
	 *
	 * @param  string[]  $vars  Existing query vars
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
	 * Intercept the request and decide what to serve.
	 *
	 * Hooked to 'template_redirect' at priority 1 (runs before any theme
	 * template selection logic, before wp_head, before any output at all).
	 */
	public function intercept_request() {
		if ( ! $this->is_landing_request() ) {
			return; // Not our page — let WordPress handle it normally
		}

		// — — — DUAL-LAYER CLOAKING CHECK — — —

		// BRANCH A: Known bot/crawler
		//   → Render the landing page template (which includes OG tags)
		//   → Do NOT redirect — bots need to see clean OG meta for link previews
		if ( Infucar_Cloaking::is_bot() ) {
			$this->render_landing_page();
			exit;
		}

		// BRANCH B: Non-Facebook/Instagram traffic (direct, Google, other)
		//   → Immediately 302-redirect to the Custom Fallback URL
		//   → Preserve & forward all incoming query parameters (fbclid, utm_*, gclid…)
		if ( Infucar_Cloaking::should_redirect_to_fallback() ) {
			$fallback_url = get_option( 'infucar_fallback_url', 'https://google.com' );

			// Forward all incoming GET parameters to the fallback destination
			if ( ! empty( $_GET ) ) {
				// Sanitise only for URL safety — preserve param values as-is
				$forwarded_params = array();
				foreach ( $_GET as $k => $v ) {
					$forwarded_params[ sanitize_key( $k ) ] = rawurlencode( $v );
				}
				$fallback_url = add_query_arg( $forwarded_params, $fallback_url );
			}

			wp_redirect( $fallback_url, 302 );
			exit;
		}

		// BRANCH C: Genuine Facebook / Instagram traffic
		//   → Render the full 9:16 landing page template
		$this->render_landing_page();
		exit;
	}

	// -----------------------------------------------------------
	// HELPERS
	// -----------------------------------------------------------

	/**
	 * Determine whether the current request is for our landing page.
	 *
	 * Two matching strategies (belt-and-suspenders):
	 *  1. WP query var 'infucar_landing' is set to '1'  (rewrite rule matched)
	 *  2. Raw REQUEST_URI path exactly equals our slug  (safety fallback)
	 *
	 * @return bool
	 */
	private function is_landing_request() {
		// Primary: via WP rewrite rule
		if ( get_query_var( self::QUERY_VAR ) === '1' ) {
			return true;
		}

		// Secondary: match raw URI path directly
		$slug         = self::get_slug();
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$request_path = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );

		return $request_path === $slug;
	}

	/**
	 * Serve the fully isolated 9:16 landing page template.
	 *
	 * Sets HTTP status 200 and Content-Type header, then includes the template
	 * file directly from within the plugin. No theme files are loaded.
	 */
	private function render_landing_page() {
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );

		// Prevent caching of the landing page so images shuffle on every visit
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		$template = INFUCAR_PLUGIN_DIR . 'templates/landing-page-template.php';

		if ( ! file_exists( $template ) ) {
			wp_die( 'Infucar: Landing page template file is missing. Please reinstall the plugin.' );
		}

		include $template;
	}

	/**
	 * Retrieve and sanitise the configured landing-page slug.
	 *
	 * @return string  e.g. 'v'
	 */
	public static function get_slug() {
		$slug = get_option( 'infucar_slug', 'v' );
		$slug = sanitize_title( trim( $slug, '/' ) );
		return empty( $slug ) ? 'v' : $slug;
	}
}
