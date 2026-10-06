<?php
/**
 * class-rocketslide-cloaking.php
 *
 * ADVANCED DUAL-LAYER TRAFFIC FILTERING & ADX BOT SHIELD ENGINE
 * ==============================================================
 * Engineered for Google AdX monetization protection & high-speed traffic filtering.
 *
 * Defenses:
 *   1. Social Crawler OpenGraph Preservation (Facebook/Twitter/WhatsApp preview bots)
 *   2. Cloud & Datacenter ASN / IP Shield (AWS, Hetzner, DO, Google Cloud, Azure, etc.)
 *   3. Headless Browser & Automation Shield (Puppeteer, Selenium, Playwright, HeadlessChrome)
 *   4. Fake Traffic Generators & Spam Bot Scraper Shield (Trafficbot, Hitleap, curl, python, etc.)
 *   5. Passive Browser Header Integrity (Detects missing Accept-Language, spoofed platforms)
 *   6. Facebook Sub-Source Classification & Control (Profiles, Groups, Pages, Stories, Automated)
 *   7. Geo-Firewall & Country Blocking (Bypass/Block low-CPM or high-risk countries like PK, IN, BD)
 *   8. Sliding-Window Rate Limiter & Click Flooding Guard (Anti-Spike Protection)
 *
 * @package RocketSlide_Landing_Page
 * @since   3.8.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RocketSlide_Cloaking' ) ) {
class RocketSlide_Cloaking {

	// -----------------------------------------------------------
	// 1. KNOWN SOCIAL CRAWLERS / PREVIEW BOTS
	// -----------------------------------------------------------

	/**
	 * Legitimate crawlers that should receive clean OpenGraph HTML meta tags
	 * so link previews render cleanly without being redirected to Fallback.
	 *
	 * @var string[]
	 */
	private static $social_crawlers = array(
		'facebookexternalhit',   // Facebook link preview crawler
		'facebot',               // Facebook bot
		'facebookcatalog',       // Facebook catalog scraper
		'facebookplatform',      // Facebook platform agent
		'meta-externalagent',    // Meta external scraper
		'meta-externalfetcher',  // Meta fetcher
		'meta-webfetcher',       // Meta web fetcher
		'meta-pico-fetcher',     // Meta micro fetcher
		'whatsapp',              // WhatsApp link preview
		'telegrambot',           // Telegram instant view bot
		'twitterbot',            // Twitter card crawler
		'linkedinbot',           // LinkedIn preview bot
		'slackbot',              // Slack link unfurl bot
		'discordbot',            // Discord embed bot
		'googlebot',             // Google Search crawler
		'bingbot',               // Bing Search crawler
		'applebot',              // Apple Siri / Spotlight
		'ia_archiver',           // Internet Archive / Wayback Machine
		'semrushbot',            // SEMrush crawler
		'ahrefsbot',             // Ahrefs crawler
		'mj12bot',               // Majestic crawler
		'petalbot',              // Huawei PetalSearch crawler
	);

	// -----------------------------------------------------------
	// 2. DATACENTER & CLOUD HOSTING ISP REGEX
	// -----------------------------------------------------------

	/**
	 * Regex matching cloud providers, datacenters, VPN exit nodes, and hosting ASNs.
	 * Real human Facebook visitors never browse from AWS, Hetzner, or DigitalOcean servers.
	 *
	 * @var string
	 */
	private static $datacenter_isp_regex = '/amazon|aws|meta platforms|facebook|google|microsoft|azure|oracle|digitalocean|hetzner|ovh|linode|vultr|leaseweb|reliablesite|servers tech|m247|choopa|hostinger|contabo|akamai|cloudflare|fastly|alibaba|tencent|ucloud|scaleway|datawire|cogent|cogentco|colocrossing|quadranet|servermania|zenlayer|psychz|tierpoint|inap|tzulo|fdcservers|packethub/i';

	// -----------------------------------------------------------
	// 3. SPAM BOTS, CLI LIBRARIES & FAKE TRAFFIC GENERATORS
	// -----------------------------------------------------------

	/**
	 * Automated HTTP client libraries, click bots, and scrapers.
	 *
	 * @var string[]
	 */
	private static $spam_bot_tokens = array(
		// CLI & HTTP Client libraries
		'curl/', 'curl', 'wget/', 'wget', 'python-requests', 'python-urllib', 'python/',
		'aiohttp', 'httpx', 'axios', 'node-fetch', 'got/', 'undici', 'httpclient',
		'apache-httpclient', 'okhttp', 'winhttp', 'go-http-client', 'java/', 'libwww',
		'rest-client', 'guzzle', 'symfony', 'postman', 'insomnia', 'ruby', 'perl',
		// Headless browsers & automation frameworks
		'headlesschrome', 'phantomjs', 'selenium', 'puppeteer', 'playwright',
		'webdriver', 'casperjs', 'nightwatch', 'cypress', 'electron', 'browserless',
		// Known fake traffic generators & click farms
		'trafficbot', 'hitleap', 'otohits', 'sparktraffic', 'somiibo', 'diabolic',
		'trafficsprit', 'babartraffic', 'traffic-generator', 'fake-traffic',
		'trafficcreator', 'simple-traffic',
		// Scrapers & vulnerability scanners
		'scrapy', 'zgrab', 'masscan', 'nmap', 'dotbot', 'rogerbot', 'exabot',
		'screaming frog', 'siteexplorer', 'megaindex', 'bytespider', 'yisouspider', 'censys'
	);

	// -----------------------------------------------------------
	// 4. FACEBOOK / INSTAGRAM TRAFFIC SIGNALS
	// -----------------------------------------------------------

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
		'messenger.com',
	);

	private static $fb_query_params = array(
		'fbclid',
		'fb_',
		'fb_ref',
		'fb_source',
	);

	private static $fb_ua_keywords = array(
		'FBAN',       // Facebook App — Android
		'FBAV',       // Facebook App version
		'FB_IAB',     // Facebook In-App Browser generic
		'FBIOS',      // Facebook App — iOS
		'FB4A',       // Facebook for Android
		'Instagram',  // Instagram In-App Browser
		'Messenger',  // Messenger App
		'TikTok',     // TikTok In-App Browser
	);

	// -----------------------------------------------------------
	// CLIENT IP RESOLUTION
	// -----------------------------------------------------------

	/**
	 * Accurately resolve real client IP across Cloudflare, proxies, and web servers.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ips = explode( ',', $_SERVER[ $header ] );
				$ip  = trim( $ips[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
	}

	// -----------------------------------------------------------
	// 1. SOCIAL CRAWLER CHECK
	// -----------------------------------------------------------

	/**
	 * Check if current request is from a legitimate social media preview crawler.
	 *
	 * @return bool
	 */
	public static function is_social_crawler() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $ua ) ) {
			return false;
		}

		foreach ( self::$social_crawlers as $crawler ) {
			if ( false !== strpos( $ua, $crawler ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Backward compatibility alias for is_social_crawler().
	 *
	 * @return bool
	 */
	public static function is_bot() {
		return self::is_social_crawler();
	}

	// -----------------------------------------------------------
	// 2. DATACENTER & CLOUD IP SHIELD
	// -----------------------------------------------------------

	/**
	 * Check if the visitor IP belongs to a cloud hosting provider, datacenter, or VPN.
	 * Real human Facebook visitors never browse from AWS, Hetzner, or DigitalOcean servers.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public static function is_datacenter_ip( $ip = null ) {
		if ( '1' !== (string) get_option( 'rocketslide_datacenter_shield', '1' ) ) {
			return false;
		}

		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		// Local / private IP bypass
		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip || strpos( $ip, '192.168.' ) === 0 || strpos( $ip, '10.' ) === 0 ) {
			return false;
		}

		// Check fast transient cache
		$cache_key = 'rs_dc_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return '1' === (string) $cached;
		}

		// Check reverse DNS hostname
		$host = @gethostbyaddr( $ip );
		if ( $host && $host !== $ip ) {
			if ( preg_match( self::$datacenter_isp_regex, $host ) ) {
				set_transient( $cache_key, '1', 43200 ); // 12 hours cache
				return true;
			}
		}

		// Quick cached GeoIP / ASN lookup with short 1.2s timeout
		$url      = 'http://ip-api.com/json/' . urlencode( $ip ) . '?fields=status,isp,org,as,hosting,proxy';
		$response = wp_remote_get( $url, array( 'timeout' => 1.2 ) );
		$is_dc    = false;

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && isset( $data['status'] ) && 'success' === $data['status'] ) {
				if ( ! empty( $data['hosting'] ) || ! empty( $data['proxy'] ) ) {
					$is_dc = true;
				} else {
					$isp_str = ( $data['isp'] ?? '' ) . ' ' . ( $data['org'] ?? '' ) . ' ' . ( $data['as'] ?? '' );
					if ( preg_match( self::$datacenter_isp_regex, $isp_str ) ) {
						$is_dc = true;
					}
				}
			}
		}

		set_transient( $cache_key, $is_dc ? '1' : '0', 43200 ); // 12 hours cache
		return $is_dc;
	}

	// -----------------------------------------------------------
	// 3. SPAM BOT & CLICK GENERATOR SHIELD
	// -----------------------------------------------------------

	/**
	 * Detect automated HTTP client libraries, click bots, and scrapers.
	 *
	 * @return bool
	 */
	public static function is_spam_bot() {
		if ( '1' !== (string) get_option( 'rocketslide_bot_protection', '1' ) ) {
			return false;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $ua ) ) {
			return true; // Empty User-Agent is always a bot
		}

		// Legitimate social preview crawlers get OG tags
		if ( self::is_social_crawler() ) {
			return false;
		}

		foreach ( self::$spam_bot_tokens as $token ) {
			if ( false !== strpos( $ua, strtolower( $token ) ) ) {
				return true;
			}
		}

		if ( preg_match( '/\b(bot|crawler|spider|scrape|crawl)\b/i', $ua ) ) {
			return true;
		}

		return false;
	}

	// -----------------------------------------------------------
	// 4. HEADLESS BROWSER & AUTOMATION SHIELD
	// -----------------------------------------------------------

	/**
	 * Detect HeadlessChrome, Puppeteer, Selenium, Playwright, or automation flags.
	 *
	 * @return bool
	 */
	public static function is_headless_browser() {
		if ( '1' !== (string) get_option( 'rocketslide_headless_shield', '1' ) ) {
			return false;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$headless_tokens = array(
			'headlesschrome', 'phantomjs', 'selenium', 'puppeteer', 'playwright',
			'webdriver', 'casperjs', 'nightwatch', 'cypress', 'electron', 'browserless',
		);

		foreach ( $headless_tokens as $token ) {
			if ( false !== strpos( $ua, $token ) ) {
				return true;
			}
		}

		// Client Hints & Automation Headers
		if ( ! empty( $_SERVER['HTTP_SEC_CH_UA'] ) && false !== strpos( strtolower( $_SERVER['HTTP_SEC_CH_UA'] ), 'webdriver' ) ) {
			return true;
		}

		if ( isset( $_SERVER['HTTP_X_WEBDRIVER'] ) || isset( $_SERVER['HTTP_WEBDRIVER'] ) || isset( $_SERVER['HTTP_X_PUPPETEER'] ) || isset( $_SERVER['HTTP_X_PLAYWRIGHT'] ) ) {
			return true;
		}

		return false;
	}

	// -----------------------------------------------------------
	// 5. PASSIVE BROWSER HEADER INTEGRITY
	// -----------------------------------------------------------

	/**
	 * Evaluates HTTP header consistency to catch fake browsers and spoofed requests.
	 * Genuine Facebook In-App mobile WebViews are exempted to prevent false positives.
	 *
	 * @return bool TRUE if headers are valid and natural, FALSE if suspicious
	 */
	public static function evaluate_browser_integrity() {
		if ( '1' !== (string) get_option( 'rocketslide_browser_integrity', '1' ) ) {
			return true;
		}

		if ( self::is_social_crawler() ) {
			return true;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? trim( $_SERVER['HTTP_USER_AGENT'] ) : '';

		// Exempt verified social in-app WebViews (FBAN, FB4A, FBIOS, Instagram, TikTok)
		foreach ( self::$fb_ua_keywords as $kw ) {
			if ( false !== strpos( $ua, $kw ) ) {
				return true;
			}
		}

		// Check 1: Accept-Language
		// Real browsers always send Accept-Language; headless scripts almost never send it.
		if ( empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return false;
		}

		// Check 2: Accept-Encoding
		if ( empty( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
			return false;
		}

		// Check 3: Accept Header
		if ( empty( $_SERVER['HTTP_ACCEPT'] ) || '*/*' === trim( $_SERVER['HTTP_ACCEPT'] ) ) {
			return false;
		}

		// Check 4: Chrome v100+ without Client Hints
		if ( preg_match( '/chrome\/(\d+)/i', $ua, $m ) ) {
			$ver = (int) $m[1];
			if ( $ver >= 100 && empty( $_SERVER['HTTP_SEC_CH_UA'] ) ) {
				return false; // Spoofed Chrome UA
			}
		}

		// Check 5: Platform mismatch
		if ( ! empty( $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ) ) {
			$plat     = strtolower( $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] );
			$ua_lower = strtolower( $ua );
			if ( false !== strpos( $plat, 'android' ) && false === strpos( $ua_lower, 'android' ) ) {
				return false;
			}
			if ( false !== strpos( $plat, 'windows' ) && ( false !== strpos( $ua_lower, 'android' ) || false !== strpos( $ua_lower, 'iphone' ) ) ) {
				return false;
			}
		}

		return true;
	}

	// -----------------------------------------------------------
	// 6. GEO FIREWALL / COUNTRY BLOCKING
	// -----------------------------------------------------------

	/**
	 * Get visitor country code (2-letter ISO, e.g. 'US', 'PK', 'IN').
	 *
	 * @param string|null $ip
	 * @return string
	 */
	public static function get_visitor_country( $ip = null ) {
		// 1. Cloudflare header
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) && 2 === strlen( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
		}

		// 2. Server GeoIP headers
		if ( ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) && 2 === strlen( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['GEOIP_COUNTRY_CODE'] ) );
		}
		if ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) && 2 === strlen( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['HTTP_X_COUNTRY_CODE'] ) );
		}

		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip ) {
			return 'DEV';
		}

		// 3. Fast transient cache
		$cache_key = 'rs_geo_c_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$country  = 'XX';
		$response = wp_remote_get( 'http://ip-api.com/json/' . urlencode( $ip ) . '?fields=status,countryCode', array( 'timeout' => 1.2 ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['countryCode'] ) ) {
				$country = strtoupper( $data['countryCode'] );
			}
		}

		set_transient( $cache_key, $country, 43200 ); // 12 hours cache
		return $country;
	}

	/**
	 * Determine whether the visitor's country is blocked by the Geo Firewall.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_country_blocked( $ip = null ) {
		if ( '1' !== (string) get_option( 'rocketslide_country_block_enabled', '0' ) ) {
			return false;
		}

		$blocked_raw = get_option( 'rocketslide_blocked_countries', '' );
		if ( empty( $blocked_raw ) ) {
			return false;
		}

		$blocked_list = array_map( 'trim', explode( ',', strtoupper( $blocked_raw ) ) );
		$country      = self::get_visitor_country( $ip );

		return in_array( $country, $blocked_list, true );
	}

	// -----------------------------------------------------------
	// 7. RATE LIMITING & CLICK FLOODING GUARD
	// -----------------------------------------------------------

	/**
	 * Check if visitor IP has exceeded maximum request rate (anti-click flood).
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_rate_limited( $ip = null ) {
		if ( '1' !== (string) get_option( 'rocketslide_rate_limit', '1' ) ) {
			return false;
		}

		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip ) {
			return false;
		}

		$transient_key = 'rs_rl_' . md5( $ip );
		$count         = (int) get_transient( $transient_key );

		if ( $count >= 30 ) {
			return true; // Exceeded 30 hits in 60s
		}

		set_transient( $transient_key, $count + 1, 60 );
		return false;
	}

	// -----------------------------------------------------------
	// 8. COMMERCIAL VPN & PROXY EXIT NODE SHIELD
	// -----------------------------------------------------------

	/**
	 * Detect commercial VPNs, Tor exit nodes, and anonymous proxies.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_vpn_or_proxy( $ip = null ) {
		if ( '1' !== (string) get_option( 'rocketslide_vpn_shield', '1' ) ) {
			return false;
		}

		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip ) {
			return false;
		}

		// Proxy headers check
		if ( ! empty( $_SERVER['HTTP_VIA'] ) || ! empty( $_SERVER['HTTP_X_FORWARDED_FOR_ORIG'] ) || ! empty( $_SERVER['HTTP_PROXY_CONNECTION'] ) ) {
			return true;
		}

		$cache_key = 'rs_vpn_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return '1' === (string) $cached;
		}

		$url      = 'http://ip-api.com/json/' . urlencode( $ip ) . '?fields=status,proxy,hosting';
		$response = wp_remote_get( $url, array( 'timeout' => 1.2 ) );
		$is_vpn   = false;

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && ( ! empty( $data['proxy'] ) || ! empty( $data['hosting'] ) ) ) {
				$is_vpn = true;
			}
		}

		set_transient( $cache_key, $is_vpn ? '1' : '0', 43200 );
		return $is_vpn;
	}

	// -----------------------------------------------------------
	// 9. MALICIOUS QUERY & VULNERABILITY PROBE SHIELD
	// -----------------------------------------------------------

	/**
	 * Detect malicious query parameters, SQL injection, XSS, or vulnerability probes.
	 *
	 * @return bool
	 */
	public static function is_malicious_probe() {
		if ( '1' !== (string) get_option( 'rocketslide_probe_shield', '1' ) ) {
			return false;
		}

		$req_uri  = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( $_SERVER['REQUEST_URI'] ) : '';
		$query    = isset( $_SERVER['QUERY_STRING'] ) ? strtolower( $_SERVER['QUERY_STRING'] ) : '';
		$combined = $req_uri . ' ' . $query;

		$patterns = array(
			'eval(', 'base64_decode', '<script', 'union+select', 'select+from',
			'benchmark(', 'sleep(', 'etc/passwd', 'wp-config', '.env',
			'phpinfo', 'concat(', 'load_file', 'shell_exec', 'system(',
		);

		foreach ( $patterns as $pat ) {
			if ( false !== strpos( $combined, $pat ) ) {
				return true;
			}
		}

		return false;
	}

	// -----------------------------------------------------------
	// 10. IP ACCESS & HONEYPOT CONTROLS
	// -----------------------------------------------------------

	/**
	 * Check if visitor IP is explicitly on the trusted allowlist.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_ip_allowlisted( $ip = null ) {
		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		if ( empty( $ip ) ) {
			return false;
		}

		$allowlist_raw = get_option( 'rocketslide_ip_allowlist', '' );
		if ( empty( $allowlist_raw ) ) {
			return false;
		}

		$list = array_map( 'trim', explode( ',', $allowlist_raw ) );
		return in_array( $ip, $list, true );
	}

	/**
	 * Check if visitor IP is manually blacklisted or triggered the crawler honeypot.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_ip_manually_blocked( $ip = null ) {
		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip ) {
			return false;
		}

		// Check Honeypot soft-block
		if ( false !== get_transient( 'rs_hp_block_' . md5( $ip ) ) ) {
			return true;
		}

		$blocklist_raw = get_option( 'rocketslide_manual_blocked_ips', '' );
		if ( empty( $blocklist_raw ) ) {
			return false;
		}

		$list = array_map( 'trim', explode( ',', $blocklist_raw ) );
		return in_array( $ip, $list, true );
	}

	/**
	 * Check if request triggered the invisible crawler honeypot link.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function is_honeypot_triggered( $ip = null ) {
		if ( isset( $_GET['rs_trap'] ) || isset( $_GET['honeypot'] ) ) {
			if ( null === $ip ) {
				$ip = self::get_client_ip();
			}
			if ( ! empty( $ip ) && '127.0.0.1' !== $ip && '::1' !== $ip ) {
				// Soft-block for 24 hours (86400s)
				set_transient( 'rs_hp_block_' . md5( $ip ), '1', 86400 );
			}
			return true;
		}
		return false;
	}

	// -----------------------------------------------------------
	// 11. FACEBOOK SUB-SOURCE CLASSIFICATION
	// -----------------------------------------------------------

	/**
	 * Classify Facebook traffic into specific sub-categories:
	 * profile, group, page, story, automated, or general.
	 *
	 * @return array
	 */
	public static function classify_facebook_traffic() {
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? strtolower( trim( $_SERVER['HTTP_REFERER'] ) ) : '';
		$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$has_fb_ref = false;
		foreach ( self::$fb_referrers as $domain ) {
			if ( false !== strpos( $ref, $domain ) ) {
				$has_fb_ref = true;
				break;
			}
		}

		$has_fb_ua = false;
		foreach ( self::$fb_ua_keywords as $kw ) {
			if ( false !== strpos( $ua, strtolower( $kw ) ) ) {
				$has_fb_ua = true;
				break;
			}
		}

		$has_fbclid = isset( $_GET['fbclid'] ) || isset( $_GET['fb_ref'] ) || isset( $_GET['fb_source'] );

		if ( ! $has_fb_ref && ! $has_fb_ua && ! $has_fbclid ) {
			return array( 'is_fb' => false, 'category' => 'none' );
		}

		// 1. Check if traffic claims FB origin but comes from Datacenter or Spam Bot (Automated FB)
		if ( self::is_datacenter_ip() || self::is_spam_bot() || self::is_headless_browser() ) {
			return array( 'is_fb' => true, 'category' => 'automated' );
		}

		$query_keys = array_map( 'strtolower', array_keys( $_GET ) );
		$src_val    = strtolower( (string) ( $_GET['src'] ?? $_GET['source'] ?? $_GET['sub'] ?? '' ) );
		$mibextid   = strtolower( (string) ( $_GET['mibextid'] ?? '' ) );

		// 2. Comments & Post Replies
		if ( in_array( 'comment_id', $query_keys, true ) || in_array( 'reply_comment_id', $query_keys, true ) || in_array( 'comment', $query_keys, true ) || in_array( 'comments', $query_keys, true ) || in_array( 'c', $query_keys, true ) || in_array( 'comm', $query_keys, true ) || in_array( 'cid', $query_keys, true ) || in_array( 'reply_id', $query_keys, true ) || in_array( 'fb_comment', $query_keys, true ) || in_array( 'fbc', $query_keys, true ) || in_array( 'comment_tracking', $query_keys, true ) || false !== strpos( $ref, 'comment_id' ) || false !== strpos( $ref, 'reply_comment_id' ) || false !== strpos( $ref, '/comments/' ) || false !== strpos( $ref, 'ufi' ) || false !== strpos( $src_val, 'comment' ) || false !== strpos( $src_val, 'reply' ) || 'fbc' === $src_val || 'comm' === $src_val || false !== strpos( $mibextid, 'comment' ) || false !== strpos( $mibextid, 'reply' ) ) {
			return array( 'is_fb' => true, 'category' => 'comment' );
		}

		// 3. Reels & Short Videos
		if ( in_array( 'reel', $query_keys, true ) || in_array( 'reels', $query_keys, true ) || in_array( 'reel_id', $query_keys, true ) || in_array( 'fb_reel', $query_keys, true ) || in_array( 'fbr', $query_keys, true ) || in_array( 'watch', $query_keys, true ) || in_array( 'video_id', $query_keys, true ) || false !== strpos( $ref, '/reel/' ) || false !== strpos( $ref, '/reels/' ) || false !== strpos( $ref, '/watch/' ) || false !== strpos( $ref, 'fb.watch' ) || false !== strpos( $src_val, 'reel' ) || false !== strpos( $src_val, 'watch' ) || false !== strpos( $mibextid, 'reel' ) ) {
			return array( 'is_fb' => true, 'category' => 'reel' );
		}

		// 4. Stories
		if ( in_array( 'sfnsn', $query_keys, true ) || in_array( 'story_fbid', $query_keys, true ) || in_array( 'story_id', $query_keys, true ) || false !== strpos( $ref, '/stories/' ) || false !== strpos( $ref, 'story.php' ) || false !== strpos( $src_val, 'story' ) || preg_match( '/^(79poqg|rsxxw9|gt977p|uo1d2h|st_)/i', $mibextid ) || false !== strpos( $mibextid, 'story' ) ) {
			return array( 'is_fb' => true, 'category' => 'story' );
		}

		// 5. Events
		if ( in_array( 'event', $query_keys, true ) || in_array( 'events', $query_keys, true ) || in_array( 'event_id', $query_keys, true ) || in_array( 'eid', $query_keys, true ) || in_array( 'fbe', $query_keys, true ) || in_array( 'fb_event', $query_keys, true ) || in_array( 'event_permalink', $query_keys, true ) || false !== strpos( $ref, '/events/' ) || false !== strpos( $ref, '/event/' ) || false !== strpos( $ref, 'event.php' ) || false !== strpos( $src_val, 'event' ) || 'fbe' === $src_val || false !== strpos( $mibextid, 'event' ) ) {
			return array( 'is_fb' => true, 'category' => 'event' );
		}

		// 6. Groups
		if ( in_array( 'group_id', $query_keys, true ) || in_array( 'gid', $query_keys, true ) || in_array( 'fb_group', $query_keys, true ) || in_array( 'group', $query_keys, true ) || in_array( 'groups', $query_keys, true ) || false !== strpos( $ref, '/groups/' ) || false !== strpos( $ref, '/g/' ) || false !== strpos( $src_val, 'group' ) || 'grp' === $src_val || 'fbg' === $src_val || preg_match( '/^(k35xfp|6aamw6|w9rl1r|c7yyfp|f85l)/i', $mibextid ) || false !== strpos( $mibextid, 'group' ) ) {
			return array( 'is_fb' => true, 'category' => 'group' );
		}

		// 7. Pages
		if ( in_array( 'paipv', $query_keys, true ) || in_array( 'eav', $query_keys, true ) || in_array( 'page_id', $query_keys, true ) || in_array( 'fb_page', $query_keys, true ) || in_array( 'page', $query_keys, true ) || in_array( 'pages', $query_keys, true ) || false !== strpos( $ref, '/pages/' ) || false !== strpos( $ref, '/p/' ) || false !== strpos( $src_val, 'page' ) || 'pg' === $src_val || 'fbp' === $src_val || preg_match( '/^(ofdknk|zbwkwl|s66gvf|w0j83f|zxp24b|a888h7)/i', $mibextid ) || false !== strpos( $mibextid, 'page' ) ) {
			return array( 'is_fb' => true, 'category' => 'page' );
		}

		// 8. Profiles / Timelines / Direct Feeds
		if ( in_array( 'profile_id', $query_keys, true ) || in_array( 'fb_profile', $query_keys, true ) || in_array( 'profile', $query_keys, true ) || in_array( 'timeline', $query_keys, true ) || false !== strpos( $ref, 'profile.php' ) || false !== strpos( $ref, '/profile/' ) || false !== strpos( $src_val, 'profile' ) || 'prof' === $src_val || preg_match( '/^(awkd5v|j7k90b|p40984)/i', $mibextid ) || false !== strpos( $mibextid, 'profile' ) ) {
			return array( 'is_fb' => true, 'category' => 'profile' );
		}

		return array( 'is_fb' => true, 'category' => 'profile' ); // Default organic FB mobile traffic
	}

	// -----------------------------------------------------------
	// 9. GENERAL FACEBOOK TRAFFIC VERIFICATION
	// -----------------------------------------------------------

	/**
	 * Determine whether the current request originates from Facebook/Instagram.
	 *
	 * @return bool
	 */
	public static function is_facebook_traffic() {
		$classification = self::classify_facebook_traffic();
		return $classification['is_fb'];
	}

	// -----------------------------------------------------------
	// 10. MASTER REDIRECTION DECISION TREE
	// -----------------------------------------------------------

	/**
	 * Master decision engine: should this visitor be instantly redirected
	 * to the Custom Fallback URL (e.g. Google) to protect Google AdX?
	 *
	 * Decision Tree:
	 *   1. Test Mode Active (?test_mode=1)                   -> FALSE (Allow Reels)
	 *   2. Social Preview Bot (Facebook/Twitter/WhatsApp)   -> FALSE (Render Clean OG Tags)
	 *   3. Rate Limit Exceeded (>30 req/min)                 -> TRUE  (REDIRECT TO FALLBACK)
	 *   4. Spam Bot / CLI Scraper detected                   -> TRUE  (REDIRECT TO FALLBACK)
	 *   5. Headless Browser / Automation detected            -> TRUE  (REDIRECT TO FALLBACK)
	 *   6. Datacenter / Cloud IP detected (AWS, Hetzner, etc)-> TRUE  (REDIRECT TO FALLBACK)
	 *   7. Browser Header Integrity Failed                   -> TRUE  (REDIRECT TO FALLBACK)
	 *   8. Geo Firewall: Visitor Country Blocked             -> TRUE  (REDIRECT TO FALLBACK)
	 *   9. Facebook Sub-Source Checks:
	 *        • Automated FB Traffic (claims FB from cloud)   -> TRUE  (REDIRECT TO FALLBACK)
	 *        • FB Group traffic & Groups blocked             -> TRUE  (REDIRECT TO FALLBACK)
	 *        • FB Page traffic & Pages blocked               -> TRUE  (REDIRECT TO FALLBACK)
	 *        • FB Story traffic & Stories blocked            -> TRUE  (REDIRECT TO FALLBACK)
	 *        • FB Profile traffic & Profiles blocked         -> TRUE  (REDIRECT TO FALLBACK)
	 *  10. Non-Social / Direct visit                         -> TRUE  (REDIRECT TO FALLBACK)
	 *  11. Verified Organic Human Facebook Visitor           -> FALSE (SHOW 9:16 LANDING PAGE!)
	 *
	 * @return bool
	 */
	public static function should_redirect_to_fallback() {
		// 1. Test Mode setting enabled in admin panel or URL
		if ( '1' === (string) get_option( 'rocketslide_test_mode', '0' ) ) {
			return false;
		}
		if ( isset( $_GET['test_mode'] ) && in_array( (string) $_GET['test_mode'], array( '1', 'true', 'yes' ), true ) ) {
			return false;
		}
		if ( isset( $_GET['test'] ) && in_array( (string) $_GET['test'], array( '1', 'true', 'yes' ), true ) ) {
			return false;
		}

		// 2. Known Social Preview Crawlers must NEVER be redirected — they need to see OG tags
		if ( self::is_social_crawler() ) {
			return false;
		}

		$client_ip = self::get_client_ip();

		// 3. Admin Trusted IP Allowlist (Instant Full Bypass for Publisher)
		if ( self::is_ip_allowlisted( $client_ip ) ) {
			return false;
		}

		// 4. Honeypot Trap Trigger Check
		if ( self::is_honeypot_triggered( $client_ip ) ) {
			return true;
		}

		// 5. Permanent & Temporary Manual IP Blocks
		if ( self::is_ip_manually_blocked( $client_ip ) ) {
			return true;
		}

		// 6. Malicious Query & Vulnerability Probing (SQLi, XSS, Path Traversal)
		if ( self::is_malicious_probe() ) {
			return true;
		}

		// 7. Rate Limiting Check (Anti-Click Flood)
		if ( self::is_rate_limited( $client_ip ) ) {
			return true;
		}

		// 8. Spam Bots & Scrapers
		if ( self::is_spam_bot() ) {
			return true;
		}

		// 9. Headless Browsers & Automation
		if ( self::is_headless_browser() ) {
			return true;
		}

		// 10. Datacenter / Cloud IP Shield (AWS, Hetzner, DigitalOcean, etc.)
		if ( self::is_datacenter_ip( $client_ip ) ) {
			return true;
		}

		// 11. Commercial VPN & Proxy Exit Node Shield
		if ( self::is_vpn_or_proxy( $client_ip ) ) {
			return true;
		}

		// 12. Passive Browser Header Integrity (Missing Accept-Language, etc.)
		if ( ! self::evaluate_browser_integrity() ) {
			return true;
		}

		// 13. Geo Firewall / Country Block
		if ( self::is_country_blocked( $client_ip ) ) {
			return true;
		}

		// 14. Facebook Traffic & Sub-Source Filters
		$fb = self::classify_facebook_traffic();

		// If not Facebook traffic at all (direct visit, organic Google search, desktop browser) -> Redirect to Fallback
		if ( ! $fb['is_fb'] ) {
			return true;
		}

		// Check Automated / Fake FB Traffic
		if ( 'automated' === $fb['category'] && '1' === (string) get_option( 'rocketslide_block_fb_automated', '1' ) ) {
			return true;
		}

		// Check Reels Filter
		if ( 'reel' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_reels', '1' ) ) {
			return true;
		}

		// Check Events Filter
		if ( 'event' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_events', '1' ) ) {
			return true;
		}

		// Check Comments Filter
		if ( 'comment' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_comments', '1' ) ) {
			return true;
		}

		// Check Groups Filter
		if ( 'group' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_groups', '1' ) ) {
			return true;
		}

		// Check Pages Filter
		if ( 'page' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_pages', '1' ) ) {
			return true;
		}

		// Check Stories Filter
		if ( 'story' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_stories', '1' ) ) {
			return true;
		}

		// Check Profiles Filter
		if ( 'profile' === $fb['category'] && '0' === (string) get_option( 'rocketslide_allow_fb_profiles', '1' ) ) {
			return true;
		}

		// Verified clean, real organic human visitor -> Show landing page!
		return false;
	}

	/**
	 * Build client-side configuration array for secondary JS verification.
	 *
	 * @return array
	 */
	public static function get_js_cloak_config() {
		return array(
			'fb_referrers'    => self::$fb_referrers,
			'fb_query_params' => self::$fb_query_params,
			'fb_ua_keywords'  => self::$fb_ua_keywords,
			'bot_signatures'  => self::$social_crawlers,
		);
	}

	/**
	 * Detect social platform origin from referrer, user agent, or query parameters.
	 *
	 * @return string 'facebook'|'instagram'|'tiktok'|'twitter'|'youtube'
	 */
	public static function detect_social_platform() {
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? strtolower( trim( $_SERVER['HTTP_REFERER'] ) ) : '';
		$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( trim( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( false !== strpos( $ref, 'tiktok.com' ) || false !== strpos( $ua, 'musical_ly' ) || false !== strpos( $ua, 'bytedance' ) || isset( $_GET['ttclid'] ) ) {
			return 'tiktok';
		}

		if ( false !== strpos( $ref, 'instagram.com' ) || false !== strpos( $ua, 'instagram' ) || isset( $_GET['igshid'] ) ) {
			return 'instagram';
		}

		if ( false !== strpos( $ref, 't.co' ) || false !== strpos( $ref, 'twitter.com' ) || false !== strpos( $ref, 'x.com' ) || false !== strpos( $ua, 'twitter' ) ) {
			return 'twitter';
		}

		if ( false !== strpos( $ref, 'youtube.com' ) || false !== strpos( $ref, 'youtu.be' ) ) {
			return 'youtube';
		}

		return 'facebook';
	}

	/**
	 * Get configuration for Social Referrer Masking & Handover engine.
	 *
	 * @return array
	 */
	public static function get_social_handover_config() {
		$pref_platform = get_option( 'rocketslide_social_network_source', 'auto' );
		if ( 'auto' === $pref_platform || empty( $pref_platform ) ) {
			$detected = self::detect_social_platform();
		} else {
			$detected = sanitize_key( $pref_platform );
		}

		return array(
			'mask_referrer'     => '1' === (string) get_option( 'rocketslide_mask_referrer', '1' ),
			'inject_social_utm' => '1' === (string) get_option( 'rocketslide_inject_social_utm', '1' ),
			'social_platform'   => $detected,
			'auto_fbclid'       => '1' === (string) get_option( 'rocketslide_auto_fbclid', '1' ),
		);
	}

	/**
	 * Return summary stats for admin dashboard display.
	 *
	 * @return array
	 */
	public static function get_defense_stats() {
		$active_shields = 0;
		if ( '1' === (string) get_option( 'rocketslide_bot_protection', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_datacenter_shield', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_vpn_shield', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_headless_shield', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_probe_shield', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_browser_integrity', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_rate_limit', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_block_fb_automated', '1' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_country_block_enabled', '0' ) ) $active_shields++;
		if ( '1' === (string) get_option( 'rocketslide_mask_referrer', '1' ) ) $active_shields++;

		return array(
			'active_shields' => $active_shields,
			'total_shields'  => 10,
		);
	}
}
}
