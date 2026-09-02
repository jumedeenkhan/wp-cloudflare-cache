<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WP_Cloudflare_Cache' ) ) :

	/**
	 * Main WP_Cloudflare_Cache Class.
	 *
	 * @package		WPCC
	 * @subpackage	Classes/WP_Cloudflare_Cache
	 * @since		1.2
	 * @author		Jumedeen Khan
	 */
	class WP_Cloudflare_Cache {

		public $settings;
		public $cachepurge;
		private $config = false;
		
		/*
		 * Fire actions
		 */				
		public function __construct() {
			$this->base_hooks();
			$this->includes();
			
			/*
			 * Fire plugin other classes
			 */
			$this->settings = new WP_Cloudflare_Cache_Settings();
			$this->cachepurge  = new WP_Cloudflare_Cache_Purge(
				$this->get_cf_email(),
				$this->get_cf_api_key(),
				$this);

			/*
			 * Config settings
			 */
            if( ! $this->init_config() ) {
                $this->config = $this->get_default_config();
                $this->update_config();
           }
		}

		/**
		 * Include required files.
		 */
		private function includes() {
			require_once WPCC_PLUGIN_DIR . 'core/includes/classes/class-wp-cloudflare-cache-settings.php';
			require_once WPCC_PLUGIN_DIR . 'core/includes/classes/class-wp-cloudflare-cache-purge.php';
		}

		/**
		 * Add base hooks for the core functionality
		 */
		private function base_hooks() {
			add_action( 'init', array( $this, 'wpcc_exclude_sitemap'), PHP_INT_MAX );		
			add_action( 'send_headers', array( $this, 'wpcc_custom_headers'), PHP_INT_MAX );
		}
		
		/*
		* Enable Browser Cache for HTML Pages
		*/
		public function wpcc_custom_headers() {
			
			// Get header value
			$wpcc_maxage = $this->get( 'cf_maxage' );
			
			If ( $wpcc_maxage > 0 ) {
				header_remove("Cache-Control");
				header("Cache-Control: public, max-age=$wpcc_maxage");
			} else {
				header_remove("Cache-Control");
				header("Cache-Control: public, max-age=650");
			}
			
			if ( is_admin() || is_user_logged_in() || is_feed() || is_404() | is_search() ) {
				header_remove("Cache-Control");
				header("Cache-Control: no-cache, must-revalidate, max-age=0");
			}
		}
		
		public function wpcc_exclude_sitemap() {
			
			// Set minimum cache ttl for sitemap
			if (strcasecmp($_SERVER['REQUEST_URI'], "/sitemap_index.xml") == 0 || preg_match("/[a-zA-Z0-9]-sitemap.xml$/", $_SERVER['REQUEST_URI'])) {
				header("Cache-Control: max-age=30");
			}
		}	

		/*
		 * Plugin functions
		 */
        function get_default_config() {

            $config = array();
			
            // Cloudflare config
            $config['cf_zone_id']           = '';
            $config['cf_page_rule_id']      = '';
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

            update_option( 'wpcc_config', $this->config );

        }

        function init_config() {

            $this->config = get_option( 'wpcc_config', false );

            if( !$this->config ) {
                return false;
			}

            // If the option exists, return true
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
		
		function get_only_domain() {
			
			$site_url = get_site_url();
			
			$site_hostname = parse_url( $site_url, PHP_URL_HOST );
			
			if ( is_null( $site_hostname ) ) {
				return '';
			}
			
			// Remove any slashes
			$site_hostname = str_replace('/', '', $site_hostname);
			
			// Remove subdomain
			$site_hostname  = explode('.', $site_hostname, 2);
			
			$site_hostname  = trim( $site_hostname[1] );
			
			if ( is_null( $site_hostname ) ) {
				return '';
			}
			return $site_hostname;
		}
		
		public function get( $option_name, $default = '', $section_name = 'wpcc_options' ) {
			
			$section_fields = get_option( $section_name );
			return isset( $section_fields[ $option_name ] ) ? $section_fields[ $option_name ] : $default;
		}

	}

endif; // End if class_exists check.