<?php
/**
 * class-rocketslide-cloaking.php
 *
 * ADVANCED DUAL-LAYER TRAFFIC FILTERING & BOT CLOAKING ENGINE
 * ============================================================
 *
 * Layer 1 — PHP Server-Side (this file):
 *   • Identifies known social-media bots/crawlers -> let them see clean OG tags
 *   • Detects genuine Facebook/Instagram human traffic -> show 9:16 landing page
 *   • Everything else (direct browser visits, search, non-social) -> redirect to Fallback URL
 *
 * Layer 2 — JS Client-Side (see assets/js/frontend.js):
 *   • Provides a second-pass check based on navigator.userAgent, document.referrer,
 *     and URLSearchParams for environments where PHP headers may not be reliable.
 *
 * @package RocketSlide_Landing_Page
 * @since   2.0.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RocketSlide_Cloaking {

	// -----------------------------------------------------------
	// BOT / CRAWLER USER-AGENT SIGNATURES
	// -----------------------------------------------------------

	/**
	 * Known social-media bots & generic crawlers whose requests should
	 * NOT be redirected — instead they receive clean OpenGraph meta tags
	 * so that Facebook link previews, WhatsApp previews, and web
	 * debuggers work properly.
	 *
	 * @var string[]
	 */
	private static $bot_signatures = array(
		'facebookexternalhit',   // Facebook link-preview crawler
		'facebot',               // Facebook bot (older variant)
		'whatsapp',              // WhatsApp link preview
		'telegrambot',           // Telegram instant-view bot
		'twitterbot',            // Twitter card crawler
		'googlebot',             // Google Search crawler
		'bingbot',               // Bing Search crawler
		'ia_archiver',           // Internet Archive / Wayback Machine
		'linkedinbot',           // LinkedIn preview bot
		'slackbot',              // Slack link-unfurl bot
		'discordbot',            // Discord embed bot
		'applebot',              // Apple Siri / Spotlight
		'semrushbot',            // SEMrush crawler
		'ahrefsbot',             // Ahrefs crawler
		'mj12bot',               // Majestic crawler
		'petalbot',              // Huawei PetalSearch crawler
	);

	// -----------------------------------------------------------
	// FACEBOOK / INSTAGRAM TRAFFIC SIGNALS
	// -----------------------------------------------------------

	/**
	 * Referrer domains that indicate genuine Facebook / Instagram traffic.
	 *
	 * @var string[]
	 */
	private static $fb_referrers = array(
		'facebook.com',
		'l.facebook.com',
		'lm.facebook.com',
		'm.facebook.com',
		'fb.me',
		'fb.com',
		'instagram.com',
		'l.instagram.com',
		'fb.gg',
	);

	/**
	 * URL query-parameter prefixes that indicate Facebook-tracked traffic.
	 *
	 * @var string[]
	 */
	private static $fb_query_params = array(
		'fbclid',     // Facebook Click ID (most common)
		'fb_',        // Prefix: fb_ref, fb_source, fb_action_ids, …
		'fb_ref',
		'fb_source',
	);

	/**
	 * User-agent substrings that identify Facebook / Instagram in-app browsers (IAB).
	 *
	 * @var string[]
	 */
	private static $fb_ua_keywords = array(
		'FBAN',       // Facebook App — Android
		'FBAV',       // Facebook App version
		'FB_IAB',     // Facebook In-App Browser generic
		'FBIOS',      // Facebook App — iOS
		'FB4A',       // Facebook for Android
		'Instagram',  // Instagram In-App Browser
	);

	// -----------------------------------------------------------
	// PUBLIC API
	// -----------------------------------------------------------

	/**
	 * Determine whether the current request is from a known bot/crawler.
	 *
	 * @return bool TRUE if request is from a bot
	 */
	public static function is_bot() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( empty( $user_agent ) ) {
			return false;
		}

		foreach ( self::$bot_signatures as $signature ) {
			if ( false !== strpos( $user_agent, strtolower( $signature ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether the current request originates from a genuine
	 * Facebook or Instagram human visitor (not a bot/crawler).
	 *
	 * Checks three independent signals — any single match is sufficient:
	 *  1. HTTP_REFERER contains a known FB/IG domain
	 *  2. A GET query parameter indicates FB-tracked traffic (fbclid, etc.)
	 *  3. The User-Agent string contains a FB/IG in-app browser token
	 *
	 * @return bool TRUE if traffic is from FB/IG
	 */
	public static function is_facebook_traffic() {
		// ——— Signal 1: Referrer Validation ———
		$referer = isset( $_SERVER['HTTP_REFERER'] )
			? strtolower( trim( $_SERVER['HTTP_REFERER'] ) )
			: '';

		if ( ! empty( $referer ) ) {
			foreach ( self::$fb_referrers as $fb_domain ) {
				if ( false !== strpos( $referer, strtolower( $fb_domain ) ) ) {
					return true;
				}
			}
		}

		// ——— Signal 2: Query Parameter Detection ———
		foreach ( $_GET as $key => $val ) {
			$key_lower = strtolower( $key );
			foreach ( self::$fb_query_params as $fb_param ) {
				$fb_param_lower = strtolower( $fb_param );
				if ( $key_lower === $fb_param_lower || 0 === strpos( $key_lower, $fb_param_lower ) ) {
					return true;
				}
			}
		}

		// ——— Signal 3: User-Agent / In-App Browser Detection ———
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? trim( $_SERVER['HTTP_USER_AGENT'] )
			: '';

		if ( ! empty( $user_agent ) ) {
			foreach ( self::$fb_ua_keywords as $keyword ) {
				if ( false !== strpos( $user_agent, $keyword ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Master routing decision: should this request be silently redirected
	 * to the Custom Fallback URL instead of showing the 9:16 landing page?
	 *
	 * Decision tree:
	 *   • Test Mode Enabled in Admin (1) -> FALSE (show landing page)
	 *   • ?test_mode=1 in URL            -> FALSE (show landing page)
	 *   • Social Bot/Crawler             -> FALSE (show OG tags, do NOT redirect)
	 *   • FB/IG Human Visitor            -> FALSE (show 9:16 landing page)
	 *   • Direct Visit / Normal Browser  -> TRUE  (REDIRECT TO FALLBACK URL!)
	 *
	 * @return bool
	 */
	public static function should_redirect_to_fallback() {
		// 1. Test Mode setting enabled in admin panel
		if ( '1' === (string) get_option( 'rocketslide_test_mode', '0' ) ) {
			return false;
		}

		// 2. URL ?test_mode=1 or ?test=1 explicitly passed
		if ( isset( $_GET['test_mode'] ) && in_array( (string) $_GET['test_mode'], array( '1', 'true', 'yes' ), true ) ) {
			return false;
		}
		if ( isset( $_GET['test'] ) && in_array( (string) $_GET['test'], array( '1', 'true', 'yes' ), true ) ) {
			return false;
		}

		// 3. Bots must never be redirected — they need to see OG tags
		if ( self::is_bot() ) {
			return false;
		}

		// 4. Genuine Facebook / Instagram visitors see the landing page
		if ( self::is_facebook_traffic() ) {
			return false;
		}

		// All other traffic (direct visits, typing URL in browser, etc.) -> REDIRECT TO FALLBACK URL
		return true;
	}

	/**
	 * Build the JS-side cloaking config array to be JSON-encoded
	 *
	 * @return array
	 */
	public static function get_js_cloak_config() {
		return array(
			'fb_referrers'    => self::$fb_referrers,
			'fb_query_params' => self::$fb_query_params,
			'fb_ua_keywords'  => self::$fb_ua_keywords,
			'bot_signatures'  => self::$bot_signatures,
		);
	}
}
