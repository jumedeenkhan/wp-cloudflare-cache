<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'CFCA_Cache' ) ) :

	/**
	 * Main CFCA_Cache Class.
	 *
	 * @package		CloudflareCache
	 * @subpackage	Classes/CFCA_Cache
	 * @since		1.2
	 * @author		Jumedeen Khan
	 */
	class CFCA_Cache {

		public $settings;
		public $cachepurge;
		private $config = false;
		
		public function __construct() {
			$this->base_hooks();
			$this->includes();
			
			$this->settings = new CFCA_Settings( $this );
			$this->cachepurge  = new CFCA_Purge(
				$this->get_cf_email(),
				$this->get_cf_api_key(),
				$this);

            if( ! $this->init_config() ) {
                $this->config = $this->get_default_config();
                $this->update_config();
           }

			CFCA_Migration::init();
			CFCA_Migration::maybe_migrate( $this );
		}
		
		private function includes() {
			require_once CFCA_PLUGIN_DIR . 'includes/class-cfca-settings.php';
			require_once CFCA_PLUGIN_DIR . 'includes/class-cfca-purge.php';
			require_once CFCA_PLUGIN_DIR . 'includes/class-cfca-migration.php';
		}

		private function base_hooks() {
			add_action( 'init', array( $this, 'cfca_exclude_sitemap'), PHP_INT_MAX );		
			add_action( 'send_headers', array( $this, 'cfca_custom_headers'), PHP_INT_MAX );
		}
		
		/**
		 * Sends Cache-Control with both max-age (respected by the visitor's browser) and s-maxage
		 * (respected by Cloudflare's shared edge cache), then overrides both to no-cache for requests
		 * that must never be cached.
		 */
		public function cfca_custom_headers() {
			
			$s_maxage    = (int) $this->get( 'cf_maxage', 604800 );
			$browser_age = (int) $this->get( 'cf_browser_maxage', 650 );
			
			header_remove("Cache-Control");
			header("Cache-Control: public, max-age=$browser_age, s-maxage=$s_maxage");
			
			if ( is_admin() || is_user_logged_in() || is_feed() || is_404() || is_search() || $this->cfca_is_excluded_url() ) {
				header_remove("Cache-Control");
				header("Cache-Control: no-cache, must-revalidate, max-age=0");
			}
		}
		
		/**
		 * Checks the current request against the user-defined "Cache Exclude URLs" list.
		 * @return bool True if the current URL starts with one of the excluded path prefixes (e.g. /cart/, /checkout/).
		 */
		private function cfca_is_excluded_url() {
			$exclude_urls = trim( (string) $this->get( 'cache_exclude_urls' ) );
			
			if ( '' === $exclude_urls || ! isset( $_SERVER['REQUEST_URI'] ) ) {
				return false;
			}
			
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			
			foreach ( preg_split( '/\r\n|\n|\r/', $exclude_urls ) as $pattern ) {
				$pattern = trim( str_replace( array( '/*', '*' ), '', trim( $pattern ) ) );
				if ( '' !== $pattern && strpos( $request_uri, $pattern ) === 0 ) {
					return true;
				}
			}
			
			return false;
		}
		
		public function cfca_exclude_sitemap() {
			
			if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
				return;
			}
			
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			
			// Matches Yoast-style (post-sitemap.xml, sitemap_index.xml) and WP core's own (wp-sitemap.xml,
			// wp-sitemap-posts-post-1.xml) sitemap URLs alike — anything ending in …sitemap….xml.
			if ( $this->get( 'bypass_sitemap', 'on' ) === 'on' && preg_match( '/sitemap[^\/]*\.xml$/i', $request_uri ) ) {
				header("Cache-Control: max-age=30");
			}
			
			if ( $this->get( 'bypass_robots', 'on' ) === 'on' && strcasecmp( $request_uri, "/robots.txt" ) === 0 ) {
				header("Cache-Control: max-age=30");
			}
		}	

        function get_default_config() {

            $config = array();
			
            $config['cf_zone_id']           = '';
            $config['cf_browser_ttl']       = '';
			
			 return $config;
		}

        function get_single_config($name, $default = false) {

            if( !is_array($this->config) || !isset($this->config[$name]) )
                return $default;

            if( is_array($this->config[$name]))
                return $this->config[$name];

            return trim($this->config[$name]);

        }

        function set_single_config($name, $value) {

            if( !is_array($this->config) ) {
                $this->config = array();
			}

            if( is_array($value) ) {
                $this->config[trim($name)] = $value;
			} else {
                $this->config[trim($name)] = trim($value);
			}
        }

        function update_config() {

            update_option( 'cfca_config', $this->config );

        }

        function init_config() {

            $this->config = get_option( 'cfca_config', false );

            if( !$this->config ) {
                return false;
			}

            return true;

        }

        function set_config( $config ) {
            $this->config = $config;
        }

        function get_config() {
            return $this->config;
        }

        function get_cf_email() {
			
			return $this->get('cf_email');
        }

        function get_cf_api_key() {
			
			return $this->get('cf_api_key');
        }
		
		/**
		 * The domain this site's Cloudflare zone must match, always derived from the public-facing home_url()
		 * (not site_url(), which can point at a different install path).
		 * @return string
		 */
		function get_only_domain() {
			
			$site_url = home_url();
			
			$site_hostname = wp_parse_url( $site_url, PHP_URL_HOST );
			
			if ( is_null( $site_hostname ) ) {
				return '';
			}
			
			$site_hostname = str_replace('/', '', $site_hostname);
			
			// Remove subdomain, but keep the apex domain intact. Not a full public-suffix-list lookup, but
			// covers the common two-part TLDs (co.uk, com.au, …) that a plain "drop the first label" rule
			// would otherwise mangle, e.g. turning "example.co.uk" into just "co.uk".
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
		 * True when this site is running on "localhost", where home_url() can never match a real Cloudflare
		 * zone name — so the domain the user picks from the dropdown is used as-is.
		 * @return bool
		 */
		function is_localhost() {
			return 'localhost' === wp_parse_url( home_url(), PHP_URL_HOST );
		}
		
		/**
		 * Browser Cache TTL choices — kept short (recommended 60-600s) since this only controls how long the
		 * visitor's own browser holds a page before revalidating.
		 * @return array Seconds (as string) => label.
		 */
		public static function get_browser_ttl_options() {
			return array(
				''    => __( 'Respect Existing Headers', 'wp-cloudflare-cache' ),
				'60'  => __( '1 minute', 'wp-cloudflare-cache' ),
				'120' => __( '2 minutes', 'wp-cloudflare-cache' ),
				'300' => __( '5 minutes', 'wp-cloudflare-cache' ),
				'600' => __( '10 minutes', 'wp-cloudflare-cache' ),
				'1200' => __( '20 minutes', 'wp-cloudflare-cache' ),
				'1800' => __( '30 minutes', 'wp-cloudflare-cache' ),
				'3600' => __( '1 hours', 'wp-cloudflare-cache' ),
				'7200' => __( '2 hours', 'wp-cloudflare-cache' ),
				'1800' => __( '5 hours', 'wp-cloudflare-cache' ),
				'43200' => __( '12 hours', 'wp-cloudflare-cache' ),
				'86400' => __( '1 Day', 'wp-cloudflare-cache' ),
				'172800' => __( '1 Days', 'wp-cloudflare-cache' ),
			);
		}
		
		/**
		 * Edge Cache TTL choices — how long Cloudflare's own edge holds a page, kept between 2 hours and
		 * 30 days since shorter/longer values rarely make sense for a full-page cache.
		 * @return array Seconds (as string) => label.
		 */
		public static function get_edge_ttl_options() {
			return array(
				''         => __( 'Respect Existing Headers', 'wp-cloudflare-cache' ),
				'7200'     => __( '2 hours', 'wp-cloudflare-cache' ),
				'18000'    => __( '5 hours', 'wp-cloudflare-cache' ),
				'43200'    => __( '12 hours', 'wp-cloudflare-cache' ),
				'86400'    => __( '24 hours', 'wp-cloudflare-cache' ),
				'172800'   => __( '2 days', 'wp-cloudflare-cache' ),
				'604800'   => __( '7 days', 'wp-cloudflare-cache' ),
				'1296000'  => __( '15 days', 'wp-cloudflare-cache' ),
				'2592000'  => __( '30 days', 'wp-cloudflare-cache' ),
			);
		}
		
		public function get( $option_name, $default = '', $section_name = 'cfca_options' ) {
			
			$section_fields = get_option( $section_name );
			return isset( $section_fields[ $option_name ] ) ? $section_fields[ $option_name ] : $default;
		}

	}

endif; // End if class_exists check.
