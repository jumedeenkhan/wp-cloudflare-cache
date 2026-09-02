<?php
/**
 * Cache-serving logic: sends Cache-Control headers and stores the plugin's Cloudflare zone config.
 *
 * @package SuperFlare
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class: boots the other classes and owns the sfcfc_config option.
 */
class SFCFC_Cache {

	public $settings;
	public $cachepurge;
	public $sanitize;
	private $config = false;

	/**
	 * Per-request cache for get(), keyed by option name — avoids re-running get_option()
	 * (and its option_{$name} filters) on every field lookup within the same request; the
	 * option can't change under us mid-request since nothing here re-reads after a save.
	 */
	private $options_cache = array();

	/**
	 * Boots hooks, sub-classes, and default config.
	 */
	public function __construct() {
		$this->base_hooks();
		$this->includes();

		$this->sanitize   = new SFCFC_Sanitize( $this );
		$this->settings   = new SFCFC_Settings( $this );
		$this->cachepurge = new SFCFC_Purge( $this );

		SFCFC_Assets::init();

		if ( ! $this->init_config() ) {
			$this->config = $this->get_default_config();
			$this->update_config();
		}

		SFCFC_Migration::init();
		SFCFC_Migration::maybe_migrate( $this );

		/**
		 * Seeds sfcfc_options with defaults on a genuinely fresh install. Must run after
		 * maybe_migrate(): add_option() is a no-op once the row exists, so doing this first
		 * would make maybe_migrate() treat a legacy CFCA/WPCC install as already configured.
		 */
		if ( false === get_option( 'sfcfc_options', false ) ) {
			add_option( 'sfcfc_options', sfcfc_get_default_options(), '', 'yes' );
		}
	}

	/**
	 * Loads the plugin's procedural helper files. Its own and related classes (SFCFC_Assets,
	 * SFCFC_Sanitize, SFCFC_Settings, SFCFC_Purge, SFCFC_Migration) load on first use via the
	 * autoloader registered in vendor/autoload.php.
	 */
	private function includes() {
		require_once SFCFC_PLUGIN_DIR . 'includes/sfcfc-defaults.php';
		require_once SFCFC_PLUGIN_DIR . 'includes/sfcfc-functions.php';
	}

	/**
	 * Registers the plugin's core hooks.
	 */
	private function base_hooks() {
		add_action( 'send_headers', array( $this, 'sfcfc_custom_headers' ), PHP_INT_MAX );

		if ( 'on' === $this->get( 'bypass_rest_api', 'on' ) ) {
			add_filter( 'rest_send_nocache_headers', '__return_true' );
		}
	}

	/**
	 * Sends max-age (browser) and s-maxage (edge), forcing no-cache when required.
	 */
	public function sfcfc_custom_headers() {
		if ( $this->sfcfc_is_short_ttl_url() ) {
			header_remove( 'Cache-Control' );
			header( 'Cache-Control: public, max-age=30' );
			return;
		}

		if ( 'on' !== $this->get( 'cf_cache_enabled', 'on' ) ) {
			header_remove( 'Cache-Control' );
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			return;
		}

		$s_maxage    = (int) $this->get( 'cf_maxage', 604800 );
		$browser_age = (int) $this->get( 'cf_browser_maxage', 650 );

		header_remove( 'Cache-Control' );
		header( "Cache-Control: public, max-age=$browser_age, s-maxage=$s_maxage" );

		$status = $this->sfcfc_classify_cache_status();

		if ( 'MISS' !== $status ) {
			header_remove( 'Cache-Control' );
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		}
	}

	/**
	 * Origin-side verdict for this request: BYPASS (identity/security), DYNAMIC (structurally
	 * uncacheable page type or non-GET), or MISS (eligible — Cloudflare's edge then decides
	 * HIT/MISS/EXPIRED/REVALIDATED, see the cf-cache-status response header).
	 *
	 * @return string
	 */
	private function sfcfc_classify_cache_status() {
		if (
			is_admin()
			|| is_user_logged_in()
			|| post_password_required()
			|| ( 'on' === $this->get( 'bypass_ajax', 'on' ) && self::sfcfc_is_ajax_request() )
			|| ( 'on' === $this->get( 'bypass_rest_api', 'on' ) && self::sfcfc_is_rest_request() )
			|| $this->sfcfc_is_excluded_url()
			|| $this->sfcfc_is_excluded_cookie()
			|| $this->sfcfc_is_excluded_query_param()
		) {
			return 'BYPASS';
		}

		if (
			$this->sfcfc_is_bypassed_page_type()
			|| ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true )
			|| ( function_exists( 'http_response_code' ) && http_response_code() && http_response_code() >= 300 )
		) {
			return 'DYNAMIC';
		}

		return 'MISS';
	}

	/**
	 * Whether Bypass Sitemap / Bypass robots.txt applies to the current request URI.
	 *
	 * @return bool
	 */
	private function sfcfc_is_short_ttl_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		/**
		 * Matches common sitemap URL patterns, e.g. sitemap.xml, sitemap_index.xml, wp-sitemap.xml.
		 */
		if ( 'on' === $this->get( 'bypass_sitemap', 'on' ) && preg_match( '/sitemap[^\/]*\.xml$/i', $request_uri ) ) {
			return true;
		}

		return 'on' === $this->get( 'bypass_robots', 'on' ) && 0 === strcasecmp( $request_uri, '/robots.txt' );
	}

	/**
	 * Conditional-tag checks available under the "Don't Cache For" setting. Run at send_headers,
	 * same as is_404()/is_search() above, by which point the main query is already resolved.
	 *
	 * @return array<string, array{0:string,1:callable,2:string,3:string}> Setting key => [label, check callback, inline label, longer description].
	 */
	public static function get_dont_cache_for_tags() {
		return array(
			'front_page' => array( 'Front Page', 'is_front_page', 'Never cache the site\'s front page.', 'Recommended if your homepage shows frequently changing content, like a live feed or personalized widgets, that shouldn\'t be served from cache.' ),
			'posts_page' => array( 'Posts Page', array( __CLASS__, 'sfcfc_is_posts_page' ), 'Never cache the blog posts page.', 'Keeps your blog listing page always current, useful if it shows recent posts, sticky posts, or widgets that change often.' ),
			'archive'    => array( 'Archives', 'is_archive', 'Never cache date, category, tag, author, and post type archive pages.', 'Useful when these listing pages need to instantly reflect new posts or reordering.' ),
			'feed'       => array( 'Feeds', 'is_feed', 'Never cache RSS/Atom feed pages.', 'Keeps RSS readers and podcast apps seeing your latest published content immediately.' ),
			'search'     => array( 'Search Pages', 'is_search', 'Never cache search results pages.', 'Results vary per visitor and query, so caching them gives little benefit and can quickly fill the cache with one-off pages.' ),
			'404'        => array( '404 Pages', 'is_404', 'Never cache the 404 not-found page.', 'Protects against Cloudflare caching a genuine page as an error before it fully renders.' ),
			'amp'        => array( 'AMP Pages', array( __CLASS__, 'sfcfc_is_amp_request' ), 'Never cache AMP pages.', 'Keeps your separate AMP templates uncached so mobile visitors always get the latest version.' ),
			'query_var'  => array( 'Pages with Query Parameters', array( __CLASS__, 'sfcfc_has_query_args' ), 'Never cache URLs that include query string parameters.', '' ),
		);
	}

	/**
	 * @return bool
	 */
	public static function sfcfc_is_posts_page() {
		return is_home() && ! is_front_page();
	}

	/**
	 * @return bool
	 */
	public static function sfcfc_is_ajax_request() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || wp_doing_ajax();
	}

	/**
	 * @return bool
	 */
	public static function sfcfc_has_query_args() {
		return isset( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'];
	}

	/**
	 * @return bool
	 */
	public static function sfcfc_is_amp_request() {
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return true;
		}
		return isset( $_SERVER['REQUEST_URI'] ) && (bool) preg_match( '#(/amp/?$|[?&]amp(=|$|&))#i', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	}

	/**
	 * @return bool
	 */
	public static function sfcfc_is_rest_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		return isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), rest_get_url_prefix() );
	}

	/**
	 * Whether the current request matches any page type enabled under "Don't Cache For".
	 *
	 * @return bool
	 */
	private function sfcfc_is_bypassed_page_type() {
		$enabled = (array) $this->get( 'dont_cache_for', array() );

		foreach ( self::get_dont_cache_for_tags() as $key => $tag ) {
			if ( 'on' === ( $enabled[ $key ] ?? '' ) && is_callable( $tag[1] ) && call_user_func( $tag[1] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks the current request's cookies against the Skip Caching for These Cookies setting.
	 *
	 * @return bool
	 */
	private function sfcfc_is_excluded_cookie() {
		$patterns = trim( (string) $this->get( 'cache_exclude_cookies' ) );

		if ( '' === $patterns || empty( $_COOKIE ) ) {
			return false;
		}

		foreach ( preg_split( '/\r\n|\n|\r/', $patterns ) as $pattern ) {
			$pattern = trim( $pattern );

			if ( '' === $pattern ) {
				continue;
			}

			foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
				if ( self::sfcfc_wildcard_match( $pattern, $cookie_name ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks the current request's query string against the Custom URL Parameters setting.
	 *
	 * @return bool
	 */
	private function sfcfc_is_excluded_query_param() {
		$patterns = trim( (string) $this->get( 'cache_exclude_query_params' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only: only the query param names are inspected to decide caching, nothing is processed or stored.
		if ( '' === $patterns || empty( $_GET ) ) {
			return false;
		}

		foreach ( preg_split( '/\r\n|\n|\r/', $patterns ) as $pattern ) {
			$pattern = trim( $pattern );

			if ( '' === $pattern ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- same read-only check as above.
			foreach ( array_keys( $_GET ) as $param_name ) {
				if ( self::sfcfc_wildcard_match( $pattern, $param_name ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Matches $subject against $pattern, where * in $pattern expands to any characters. Without a
	 * *, falls back to a 'contains', 'prefix', or 'exact' match on $subject.
	 *
	 * @param string $pattern
	 * @param string $subject
	 * @param string $fallback 'contains', 'prefix', or 'exact'.
	 * @return bool
	 */
	private static function sfcfc_wildcard_match( $pattern, $subject, $fallback = 'contains' ) {
		if ( false === strpos( $pattern, '*' ) ) {
			if ( 'prefix' === $fallback ) {
				return 0 === strpos( $subject, $pattern );
			}

			if ( 'exact' === $fallback ) {
				return 0 === strcasecmp( $pattern, $subject );
			}

			return false !== strpos( $subject, $pattern );
		}

		$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';

		return (bool) preg_match( $regex, $subject );
	}

	/**
	 * Whether the current user is allowed to purge the cache: always true for administrators,
	 * otherwise only for roles picked in the Select user roles allowed to purge the cache setting.
	 *
	 * @return bool
	 */
	public function can_current_user_purge_cache() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$allowed_roles = (array) $this->get( 'purge_roles', array() );

		if ( empty( $allowed_roles ) ) {
			return false;
		}

		$user = wp_get_current_user();

		foreach ( (array) $user->roles as $role ) {
			if ( 'on' === ( $allowed_roles[ $role ] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks the current URL against the Cache Exclude URLs setting.
	 *
	 * @return bool
	 */
	private function sfcfc_is_excluded_url() {
		$exclude_urls = trim( (string) $this->get( 'cache_exclude_urls' ) );

		if ( '' === $exclude_urls || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		foreach ( preg_split( '/\r\n|\n|\r/', $exclude_urls ) as $pattern ) {
			$pattern = trim( $pattern );
			if ( '' !== $pattern && self::sfcfc_wildcard_match( $pattern, $request_uri, 'prefix' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array Default sfcfc_config values.
	 */
	public function get_default_config() {
		return array( 'cf_zone_id' => '' );
	}

	/**
	 * @param string $name    Config key.
	 * @param mixed  $default Value to return if the key isn't set.
	 * @return mixed
	 */
	public function get_single_config( $name, $default = false ) {
		if ( ! is_array( $this->config ) || ! isset( $this->config[ $name ] ) ) {
			return $default;
		}

		if ( is_array( $this->config[ $name ] ) ) {
			return $this->config[ $name ];
		}

		return trim( $this->config[ $name ] );
	}

	/**
	 * @param string $name  Config key.
	 * @param mixed  $value Value to store.
	 */
	public function set_single_config( $name, $value ) {
		if ( ! is_array( $this->config ) ) {
			$this->config = array();
		}

		$this->config[ trim( $name ) ] = is_array( $value ) ? $value : trim( $value );
	}

	/**
	 * Persists the in-memory config to the sfcfc_config option.
	 */
	public function update_config() {
		update_option( 'sfcfc_config', $this->config );
	}

	/**
	 * Loads the saved config into memory.
	 *
	 * @return bool False when there's no saved config yet.
	 */
	public function init_config() {
		$this->config = get_option( 'sfcfc_config', false );

		return (bool) $this->config;
	}

	/**
	 * @param array $config Config to replace the in-memory config with.
	 */
	public function set_config( $config ) {
		$this->config = $config;
	}

	/**
	 * Apex domain derived from home_url() (not site_url()), used to find the matching Cloudflare zone.
	 *
	 * @return string
	 */
	public function get_only_domain() {
		$site_hostname = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( is_null( $site_hostname ) ) {
			return '';
		}

		$site_hostname = str_replace( '/', '', $site_hostname );

		/**
		 * Removes the subdomain but keeps the apex domain intact. Not a full public-suffix-list lookup,
		 * but covers the common two-part TLDs (co.uk, com.au) that dropping only the first label would
		 * otherwise mangle, e.g. turning "example.co.uk" into just "co.uk".
		 */
		$two_part_tlds = array( 'co.uk', 'org.uk', 'gov.uk', 'ac.uk', 'co.in', 'co.jp', 'co.nz', 'co.za', 'com.au', 'com.br', 'com.mx', 'co.kr', 'com.sg' );
		$parts         = explode( '.', $site_hostname );
		$suffix_len    = 1;

		if ( count( $parts ) >= 2 && in_array( implode( '.', array_slice( $parts, -2 ) ), $two_part_tlds, true ) ) {
			$suffix_len = 2;
		}

		if ( count( $parts ) > $suffix_len + 1 ) {
			$site_hostname = implode( '.', array_slice( $parts, -( $suffix_len + 1 ) ) );
		}

		return trim( $site_hostname );
	}

	/**
	 * True on "localhost", where home_url() can never match a real Cloudflare zone name.
	 *
	 * @return bool
	 */
	public function is_localhost() {
		return 'localhost' === wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Browser Cache TTL choices: how long the visitor's own browser holds a page before revalidating.
	 *
	 * @return array
	 */
	public static function get_browser_ttl_options() {
		return array(
			''      => __( 'Respect Existing Headers', 'wp-cloudflare-cache' ),
			'60'    => __( '1 minute', 'wp-cloudflare-cache' ),
			'120'   => __( '2 minutes', 'wp-cloudflare-cache' ),
			'300'   => __( '5 minutes', 'wp-cloudflare-cache' ),
			'600'   => __( '10 minutes', 'wp-cloudflare-cache' ),
			'1200'  => __( '20 minutes', 'wp-cloudflare-cache' ),
			'1800'  => __( '30 minutes', 'wp-cloudflare-cache' ),
			'3600'  => __( '1 hour', 'wp-cloudflare-cache' ),
			'7200'  => __( '2 hours', 'wp-cloudflare-cache' ),
			'18000' => __( '5 hours', 'wp-cloudflare-cache' ),
			'43200' => __( '12 hours', 'wp-cloudflare-cache' ),
			'86400' => __( '1 day', 'wp-cloudflare-cache' ),
			'172800' => __( '2 days', 'wp-cloudflare-cache' ),
		);
	}

	/**
	 * Edge Cache TTL choices: how long Cloudflare's own edge holds a page before revalidating.
	 *
	 * @return array
	 */
	public static function get_edge_ttl_options() {
		return array(
			''        => __( 'Respect Existing Headers', 'wp-cloudflare-cache' ),
			'7200'    => __( '2 hours', 'wp-cloudflare-cache' ),
			'18000'   => __( '5 hours', 'wp-cloudflare-cache' ),
			'43200'   => __( '12 hours', 'wp-cloudflare-cache' ),
			'86400'   => __( '24 hours', 'wp-cloudflare-cache' ),
			'172800'  => __( '2 days', 'wp-cloudflare-cache' ),
			'604800'  => __( '7 days', 'wp-cloudflare-cache' ),
			'1296000' => __( '15 days', 'wp-cloudflare-cache' ),
			'2592000' => __( '30 days', 'wp-cloudflare-cache' ),
		);
	}

	/**
	 * @param string $option_name  Field to read.
	 * @param mixed  $default      Value to return if the field isn't set.
	 * @param string $section_name Option name the field lives under.
	 * @return mixed
	 */
	public function get( $option_name, $default = '', $section_name = 'sfcfc_options' ) {
		if ( ! array_key_exists( $section_name, $this->options_cache ) ) {
			$this->options_cache[ $section_name ] = get_option( $section_name );
		}

		$section_fields = $this->options_cache[ $section_name ];
		return isset( $section_fields[ $option_name ] ) ? $section_fields[ $option_name ] : $default;
	}

	/**
	 * @param string|null $section_name Clears just that section's cache, or the whole cache when omitted.
	 */
	public function clear_options_cache( $section_name = null ) {
		if ( null === $section_name ) {
			$this->options_cache = array();
			return;
		}
		unset( $this->options_cache[ $section_name ] );
	}
}
