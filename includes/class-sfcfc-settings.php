<?php
/**
 * Defines SFCFC_Settings: renders and saves the plugin's admin settings page.
 * Its view (the page wrapper, tabs, and layout) lives in includes/admin/sfcfc-content.php.
 *
 * @package SuperFlare
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders and saves the plugin's admin settings page.
 */
class SFCFC_Settings {

	private $plugin_name;
	private $instance;

	/**
	 * @param SFCFC_Cache|null $instance
	 */
	function __construct( $instance = null ){
		$this->init();
		$this->plugin_name = SFCFC_NAME;
		$this->instance     = $instance;
	}
	
	/**
	 * Whether Cloudflare is fully connected (valid API credentials and a zone selected). Used to
	 * gate settings sections/fields that only make sense once setup is complete, e.g. the Zone
	 * Settings section on the General tab.
	 *
	 * @return bool
	 */
	private function sfcfc_is_cloudflare_connected() {
		$has_credentials = $this->instance && $this->instance->cachepurge && $this->instance->cachepurge->has_credentials();
		$zone_id         = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';
		return (bool) ( $has_credentials && $zone_id );
	}

	/**
	 * Registers this class's hooks.
	 */
	private function init(){
		
        add_action( 'admin_menu', array( $this, 'sfcfc_add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'sfcfc_register_settings' ) );
        add_filter( 'plugin_action_links_' . SFCFC_PLUGIN_BASE, array( $this, 'sfcfc_links' ) );
		add_action( 'admin_bar_menu', array( $this, 'sfcfc_admin_bar_menu_button'), 100 );
		add_action( 'wp_ajax_sfcfc_reset_settings', array( $this, 'sfcfc_reset_settings' ) );
		add_action( 'wp_ajax_sfcfc_save_settings', array( $this, 'sfcfc_save_settings' ) );
		add_action( 'wp_ajax_sfcfc_disconnect', array( $this, 'sfcfc_disconnect' ) );
		add_action( 'wp_ajax_sfcfc_connect', array( $this, 'sfcfc_connect_cloudflare' ) );
		add_action( 'wp_ajax_sfcfc_confirm_zone', array( $this, 'sfcfc_confirm_zone' ) );
		add_action( 'wp_ajax_sfcfc_check_dns_proxy', array( $this, 'ajax_check_dns_proxy' ) );
		add_action( 'admin_post_sfcfc_export_settings', array( $this, 'sfcfc_export_settings' ) );
		add_action( 'admin_post_sfcfc_import_settings', array( $this, 'sfcfc_import_settings' ) );
	}

    /**
     * Adds the top-level SuperFlare admin menu and its submenus.
     */
    public function sfcfc_add_admin_menu() {
        add_menu_page(
			'SuperFlare',
			'SuperFlare',
			'manage_options',
			'superflare',
			array( $this, 'sfcfc_dashboard_page' ),
			'dashicons-cloud'
		);

		/**
		 * Same slug as the parent: WordPress uses this to relabel the auto-added first submenu
		 * item, instead of it duplicating the top-level "SuperFlare" label.
		 */
		add_submenu_page( 'superflare', 'Dashboard', 'Dashboard', 'manage_options', 'superflare', array( $this, 'sfcfc_dashboard_page' ) );
		add_submenu_page( 'superflare', 'Settings', 'Settings', 'manage_options', 'superflare-settings', array( $this, 'sfcfc_settings_page' ) );
		add_submenu_page( 'superflare', 'Setup Wizard', 'Setup Wizard', 'manage_options', 'superflare-setup-wizard', array( $this, 'sfcfc_setup_wizard_page' ) );
		add_submenu_page( 'superflare', 'Import/Export', 'Import/Export', 'manage_options', 'superflare-import-export', array( $this, 'sfcfc_import_export_page' ) );
		add_submenu_page( 'superflare', 'Support', 'Support', 'manage_options', 'superflare-support', array( $this, 'sfcfc_support_page' ) );
		add_submenu_page( 'superflare', 'Upgrade to PRO', 'Upgrade to PRO', 'manage_options', 'superflare-upgrade', array( $this, 'sfcfc_upgrade_page' ) );

		$this->sfcfc_highlight_upgrade_menu_item();
    }

	/**
	 * Wraps the "Upgrade to PRO" submenu label in a span so CSS can turn it into a green highlighted
	 * row, like the upsell menu item most freemium plugins show.
	 */
	private function sfcfc_highlight_upgrade_menu_item() {
		global $submenu;

		if ( empty( $submenu['superflare'] ) ) {
			return;
		}

		foreach ( $submenu['superflare'] as $key => $item ) {
			if ( 'superflare-upgrade' === $item[2] ) {
				$submenu['superflare'][ $key ][0] = '<span class="sfcfc-upgrade-menu-label">' . $item[0] . '</span>';
			}
		}
	}
	
    /**
     * Registers the sfcfc_options setting and its sections/fields.
     */
    public function sfcfc_register_settings() {
		
		register_setting(
			'sfcfc_settings',
			'sfcfc_options',
			array( $this->instance->sanitize, 'sanitize_sfcfc_options' )
		);

		/**
		 * No title/intro: the whole Cloudflare Connection card (header, notices, fields, and button)
		 * is rendered by the single sfcfc_cf_connection field below, so it can be re-rendered as one
		 * unit after AJAX save/disconnect.
		 */
		add_settings_section( 'sfcfc_section_caching', 'SuperFlare Cache', array( $this, 'sfcfc_section_info' ), 'sfcfc-admin' );
		add_settings_section( 'sfcfc_section_global_exclusions', 'Cache Exclusions', array( $this, 'sfcfc_section_global_exclusions_info' ), 'sfcfc-admin' );

		add_settings_section( 'sfcfc_section_cache_control', 'Cache Control', array( $this, 'sfcfc_section_cache_control_info' ), 'sfcfc-cache-settings' );
		add_settings_section( 'sfcfc_section_cache', 'Cloudflare Cache', array( $this, 'sfcfc_section_info' ), 'sfcfc-cache-settings' );
		add_settings_section( 'sfcfc_section_cache_exclusions', 'Exclude by Page Type', array( $this, 'sfcfc_section_cache_exclusions_info' ), 'sfcfc-cache-settings' );
		add_settings_section( 'sfcfc_section_purge_options', 'Purge Options', array( $this, 'sfcfc_section_info' ), 'sfcfc-cache-purge' );
		add_settings_section( 'sfcfc_section_preloader', 'Preloader', array( $this, 'sfcfc_section_info' ), 'sfcfc-cache-purge' );
		add_settings_section( 'sfcfc_section_preload_operation', 'Preload Operation', array( $this, 'sfcfc_section_info' ), 'sfcfc-cache-purge' );
		add_settings_section( 'sfcfc_section_cloudflare_speed', 'Performance', array( $this, 'sfcfc_section_info' ), 'sfcfc-optimization' );
		add_settings_section( 'sfcfc_section_cloudflare_protocols', 'Protocols', array( $this, 'sfcfc_section_info' ), 'sfcfc-optimization' );
		add_settings_section( 'sfcfc_section_cloudflare_security', 'Security & Delivery', array( $this, 'sfcfc_section_info' ), 'sfcfc-optimization' );
		add_settings_section( 'sfcfc_section_permissions', 'Permissions', array( $this, 'sfcfc_section_info' ), 'sfcfc-advanced' );
		add_settings_section( 'sfcfc_section_tools', '', array( $this, 'sfcfc_section_info' ), 'sfcfc-advanced' );
		add_settings_section( 'sfcfc_section_general_toggles', '', array( $this, 'sfcfc_section_info' ), 'sfcfc-advanced' );
		add_settings_section( 'sfcfc_section_logging', 'Logging & Data Control', array( $this, 'sfcfc_section_info' ), 'sfcfc-advanced' );
		add_settings_section( 'sfcfc_section_data_control', '', array( $this, 'sfcfc_section_info' ), 'sfcfc-advanced' );

		add_settings_field( 'sfcfc_cache_enabled', 'Full-Page Caching', array( $this, 'sfcfc_cache_enabled_field' ), 'sfcfc-admin', 'sfcfc_section_caching' );

		add_settings_field( 'sfcfc_bypass_ajax', 'Bypass AJAX Requests', array( $this, 'sfcfc_bypass_ajax' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_bypass_rest_api', 'Bypass REST API Requests', array( $this, 'sfcfc_bypass_rest_api' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_bypass_sitemap', 'Bypass Sitemap', array( $this, 'sfcfc_bypass_sitemap' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_bypass_robots', 'Bypass robots.txt', array( $this, 'sfcfc_bypass_robots' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_bypass_query_string', 'Bypass pages with any query string', array( $this, 'sfcfc_bypass_query_string_field' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_cache_exclude_cookies', 'Exclude Cookies', array( $this, 'sfcfc_cache_exclude_cookies' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );
		add_settings_field( 'sfcfc_cache_exclude_query_params', 'Custom URL Parameters', array( $this, 'sfcfc_cache_exclude_query_params' ), 'sfcfc-admin', 'sfcfc_section_global_exclusions' );

		add_settings_field( 'sfcfc_cf_maxage_value', 'Edge Cache-Control max-age', array( $this, 'sfcfc_cloudflare_maxage_value' ), 'sfcfc-cache-settings', 'sfcfc_section_cache_control' );
		add_settings_field( 'sfcfc_cf_browser_maxage_value', 'Browser Cache-Control max-age', array( $this, 'sfcfc_cloudflare_browser_maxage_value' ), 'sfcfc-cache-settings', 'sfcfc_section_cache_control' );
		add_settings_field( 'sfcfc_cf_browser_ttl', 'Cloudflare Browser Cache TTL', array( $this, 'sfcfc_cloudflare_browser_ttl' ), 'sfcfc-cache-settings', 'sfcfc_section_cache' );
		add_settings_field( 'sfcfc_cf_edge_ttl', 'Cloudflare Edge Cache TTL', array( $this, 'sfcfc_cloudflare_edge_ttl' ), 'sfcfc-cache-settings', 'sfcfc_section_cache' );
		add_settings_field( 'sfcfc_cf_cache_level', 'Caching Level', array( $this, 'sfcfc_cf_cache_level_field' ), 'sfcfc-cache-settings', 'sfcfc_section_cache' );

		foreach ( SFCFC_Cache::get_dont_cache_for_tags() as $sfcfc_tag_key => $sfcfc_tag ) {
			if ( 'query_var' === $sfcfc_tag_key ) {
				continue;
			}
			add_settings_field( 'sfcfc_dont_cache_for_' . $sfcfc_tag_key, $sfcfc_tag[0], array( $this, 'sfcfc_dont_cache_for_field' ), 'sfcfc-cache-settings', 'sfcfc_section_cache_exclusions', array( 'tag_key' => $sfcfc_tag_key ) );
		}
		add_settings_field( 'sfcfc_cache_exclude_urls', 'Exclude URLs', array( $this, 'sfcfc_cloudflare_exclude_urls' ), 'sfcfc-cache-settings', 'sfcfc_section_cache_exclusions' );
		add_settings_field( 'sfcfc_purge_mode', 'Purge on Post/Page Update', array( $this, 'sfcfc_purge_mode_field' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_homepage', 'Purge Homepage', array( $this, 'sfcfc_purge_homepage_on_update' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_archives', 'Purge Archives', array( $this, 'sfcfc_purge_archives_field' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_on_comment', 'Purge On Comments', array( $this, 'sfcfc_purge_on_comments' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_feeds', 'Purge Feeds', array( $this, 'sfcfc_purge_feeds_field' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_amp_urls', 'Purge AMP URL', array( $this, 'sfcfc_purge_amp_urls_field' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_purge_on_theme_plugin_update', 'Purge on Theme/Plugin Update', array( $this, 'sfcfc_purge_on_theme_plugin_update_field' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );
		add_settings_field( 'sfcfc_cf_purge_purl_cache', 'Custom Purge URLs', array( $this, 'sfcfc_cloudflare_purge_url_cache' ), 'sfcfc-cache-purge', 'sfcfc_section_purge_options' );

		add_settings_field( 'sfcfc_enable_preloader', 'Enable Preloader', array( $this, 'sfcfc_enable_preloader' ), 'sfcfc-cache-purge', 'sfcfc_section_preloader' );
		add_settings_field( 'sfcfc_preloader_start_on_purge', 'Preload Purged Posts/Pages', array( $this, 'sfcfc_preloader_start_on_purge' ), 'sfcfc-cache-purge', 'sfcfc_section_preloader', array( 'class' => 'sfcfc-depends-enable_preloader' ) );

		/**
		 * Master toggle (Enable Cron Preloading) registered first: the two fields below it only take
		 * effect for cron-triggered preloading, so they visually gray out with it via sfcfc-depends-*.
		 */
		add_settings_field( 'sfcfc_preloader_cronjob', 'Enable Cron Preloading', array( $this, 'sfcfc_preloader_cronjob' ), 'sfcfc-cache-purge', 'sfcfc_section_preload_operation' );
		add_settings_field( 'sfcfc_preload_latest_posts', 'Preload Latest Posts', array( $this, 'sfcfc_preload_latest_posts' ), 'sfcfc-cache-purge', 'sfcfc_section_preload_operation', array( 'class' => 'sfcfc-depends-preloader_cronjob_enabled' ) );
		add_settings_field( 'sfcfc_preload_sitemap', 'Preload Sitemap URLs', array( $this, 'sfcfc_preload_sitemap' ), 'sfcfc-cache-purge', 'sfcfc_section_preload_operation', array( 'class' => 'sfcfc-depends-preloader_cronjob_enabled' ) );
		add_settings_field( 'sfcfc_cron_preload_url', 'Cron Preload URL', array( $this, 'sfcfc_cron_preload_url_field' ), 'sfcfc-cache-purge', 'sfcfc_section_preload_operation', array( 'class' => 'sfcfc-depends-preloader_cronjob_enabled' ) );
		add_settings_field( 'sfcfc_run_preloader_now', '', array( $this, 'sfcfc_run_preloader_now' ), 'sfcfc-cache-purge', 'sfcfc_section_preload_operation' );

		add_settings_field( 'sfcfc_cf_always_online', 'Always Online', array( $this, 'sfcfc_cf_always_online_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_speed' );
		add_settings_field( 'sfcfc_cf_early_hints', 'Early Hints', array( $this, 'sfcfc_cf_early_hints_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_speed' );
		add_settings_field( 'sfcfc_cf_crawler_hints', 'Crawler Hints', array( $this, 'sfcfc_cf_crawler_hints_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_speed' );
		add_settings_field( 'sfcfc_cf_rocket_loader', 'Rocket Loader', array( $this, 'sfcfc_cf_rocket_loader_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_speed' );

		add_settings_field( 'sfcfc_cf_ipv6', 'IPv6 Compatibility', array( $this, 'sfcfc_cf_ipv6_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_protocols' );
		add_settings_field( 'sfcfc_cf_tls_1_3', 'TLS 1.3', array( $this, 'sfcfc_cf_tls_1_3_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_protocols' );
		add_settings_field( 'sfcfc_cf_http3', 'HTTP/3 (with QUIC)', array( $this, 'sfcfc_cf_http3_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_protocols' );
		add_settings_field( 'sfcfc_cf_zero_rtt', '0-RTT Connection Resumption', array( $this, 'sfcfc_cf_zero_rtt_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_protocols' );

		add_settings_field( 'sfcfc_cf_browser_check', 'Browser Integrity Check', array( $this, 'sfcfc_cf_browser_check_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_security' );
		add_settings_field( 'sfcfc_cf_ip_geolocation', 'IP Geolocation', array( $this, 'sfcfc_cf_ip_geolocation_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_security' );
		add_settings_field( 'sfcfc_cf_hotlink_protection', 'Hotlink Protection', array( $this, 'sfcfc_cf_hotlink_protection_field' ), 'sfcfc-optimization', 'sfcfc_section_cloudflare_security' );

		add_settings_field( 'sfcfc_purge_roles', 'Minimum Role to Purge Cache', array( $this, 'sfcfc_purge_roles' ), 'sfcfc-advanced', 'sfcfc_section_permissions' );
		add_settings_field( 'sfcfc_remove_purge_toolbar', 'Remove purge option from toolbar', array( $this, 'sfcfc_remove_purge_toolbar' ), 'sfcfc-advanced', 'sfcfc_section_permissions' );

		add_settings_field( 'sfcfc_enable_activity_log', 'Activity Log', array( $this, 'sfcfc_enable_activity_log_field' ), 'sfcfc-advanced', 'sfcfc_section_logging' );
		add_settings_field( 'sfcfc_debug_logging', 'Debug/Logging Mode', array( $this, 'sfcfc_debug_logging_field' ), 'sfcfc-advanced', 'sfcfc_section_logging' );
		add_settings_field( 'sfcfc_data_control', 'Data on Uninstall', array( $this, 'sfcfc_data_control' ), 'sfcfc-advanced', 'sfcfc_section_data_control' );

		add_settings_field( 'sfcfc_remote_purge_url', 'Remote Purge URL', array( $this, 'sfcfc_remote_purge_url' ), 'sfcfc-advanced', 'sfcfc_section_tools' );
		add_settings_field( 'sfcfc_reset_settings', 'Reset Settings', array( $this, 'sfcfc_reset_settings_field' ), 'sfcfc-advanced', 'sfcfc_section_tools' );

		add_settings_field( 'sfcfc_cf_development_mode', 'Development Mode', array( $this, 'sfcfc_cf_development_mode_field' ), 'sfcfc-advanced', 'sfcfc_section_general_toggles' );
    }
	
    /**
     * Adds a Settings link to the plugin's row on the Plugins screen.
     *
     * @param array $links
     * @return array
     */
    public function sfcfc_links( $links ) {

            $settings_link = array(
                '<a href="' . admin_url( 'admin.php?page=superflare-settings' ) . '">'.__( 'Settings', 'wp-cloudflare-cache' ).'</a>',
            );

            return array_merge( $settings_link, $links );
    }
	
    /**
     * Renders the Settings page markup by including the view template.
     */
    public function sfcfc_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		include SFCFC_PLUGIN_DIR . 'includes/admin/sfcfc-content.php';
    }

	public function sfcfc_setup_wizard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		include SFCFC_PLUGIN_DIR . 'includes/admin/views/sfcfc-setup-wizard.php';
	}

	/**
	 * Renders the Dashboard page: the Activity Log only.
	 */
	public function sfcfc_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		$log = ( $this->instance && $this->instance->cachepurge ) ? $this->instance->cachepurge->get_activity_log() : array();
		$sfcfc_activity_log_enabled = 'on' === $this->instance->get( 'enable_activity_log', 'on' );
		$sfcfc_analytics_available  = ( $this->instance && $this->instance->cachepurge )
			? ( $this->instance->cachepurge->has_credentials() && $this->instance->get_single_config( 'cf_zone_id', '' ) )
			: false;
		include SFCFC_PLUGIN_DIR . 'includes/admin/sfcfc-dashboard.php';
	}

	/**
	 * Renders the Import/Export page: download/restore settings as JSON, plus Reset Settings —
	 * all three are "whole settings" actions grouped together.
	 */
	public function sfcfc_import_export_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		$sfcfc_export_url    = wp_nonce_url( admin_url( 'admin-post.php?action=sfcfc_export_settings' ), 'sfcfc_export_settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect flag for which notice to display; the import itself is nonce-verified in sfcfc_import_settings().
		$sfcfc_import_result = isset( $_GET['sfcfc_import'] ) ? sanitize_key( wp_unslash( $_GET['sfcfc_import'] ) ) : '';

		include SFCFC_PLUGIN_DIR . 'includes/admin/views/sfcfc-import-export.php';
	}

	/**
	 * Renders the Support page: get-in-touch options, a copyable system info snapshot for
	 * support tickets, FAQs, and the review CTA.
	 */
	public function sfcfc_support_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		$system_info = $this->sfcfc_get_system_info_items();
		include SFCFC_PLUGIN_DIR . 'includes/admin/views/sfcfc-support.php';
	}

	/**
	 * Snapshot of environment + connection facts worth including in a support ticket.
	 *
	 * @return array<int, array{label:string,value:string,state:string}>
	 */
	private function sfcfc_get_system_info_items() {
		$cache           = $this->instance;
		$purge           = $cache ? $cache->cachepurge : null;
		$has_credentials = $purge && $purge->has_credentials();
		$options         = get_option( 'sfcfc_options', array() );
		$theme           = wp_get_theme();

		return array(
			array( 'label' => __( 'SuperFlare Cache Version', 'wp-cloudflare-cache' ), 'value' => SFCFC_VERSION, 'state' => 'neutral' ),
			array( 'label' => __( 'WordPress Version', 'wp-cloudflare-cache' ), 'value' => get_bloginfo( 'version' ), 'state' => 'neutral' ),
			array( 'label' => __( 'PHP Version', 'wp-cloudflare-cache' ), 'value' => PHP_VERSION, 'state' => 'neutral' ),
			array( 'label' => __( 'Active Theme', 'wp-cloudflare-cache' ), 'value' => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ), 'state' => 'neutral' ),
			array( 'label' => __( 'Authentication Mode', 'wp-cloudflare-cache' ), 'value' => 'key' === ( $options['cf_auth_method'] ?? 'token' ) ? __( 'API Key', 'wp-cloudflare-cache' ) : __( 'API Token', 'wp-cloudflare-cache' ), 'state' => 'neutral' ),
			array( 'label' => __( 'Cloudflare Connection', 'wp-cloudflare-cache' ), 'value' => $has_credentials ? __( 'Connected', 'wp-cloudflare-cache' ) : __( 'Not Connected', 'wp-cloudflare-cache' ), 'state' => $has_credentials ? 'good' : 'bad' ),
			array( 'label' => __( 'Site URL', 'wp-cloudflare-cache' ), 'value' => home_url( '/' ), 'state' => 'neutral' ),
		);
	}

	/**
	 * Renders the Upgrade to PRO page.
	 */
	public function sfcfc_upgrade_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}

		include SFCFC_PLUGIN_DIR . 'includes/admin/views/sfcfc-upgrade.php';
	}
	
	
	/**
	 * Renders the whole Cloudflare Connection section: header, then either the setup form
	 * (auth mode + credentials + Connect button) or the connected state (status + domain picker).
	 * Kept as one method so a single AJAX response can re-render it in place.
	 *
	 * @param string $error   Optional inline error to show above the fields (a failed connect/sync).
	 * @param array  $prefill Optional just-submitted (not yet saved) credential values to redisplay
	 *                        after a failed connect attempt, so the user doesn't have to retype them.
	 */
	public function sfcfc_render_connection_card( $error = '', $prefill = array(), $minimal = false, $show_connect_form = true ) {
		$options         = get_option( 'sfcfc_options' );
		$auth_method     = $prefill['cf_auth_method'] ?? ( $options['cf_auth_method'] ?? 'token' );
		$zone_id         = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';
		$has_credentials = $this->instance && $this->instance->cachepurge && $this->instance->cachepurge->has_credentials();
		$is_connected    = $has_credentials && $zone_id;
		$zones           = ( $this->instance && $this->instance->cachepurge ) ? $this->instance->cachepurge->get_cached_zones() : array();
		$active_domain   = $zones[ $zone_id ] ?? '';
		?>
		<div id="sfcfc-connection-card" data-minimal="<?php echo $minimal ? '1' : '0'; ?>" data-show-connect-form="<?php echo $show_connect_form ? '1' : '0'; ?>">
			<div class="sfcfc-connect-header">
				<span class="sfcfc-connect-icon is-cloud">
					<span class="dashicons dashicons-cloud"></span>
				</span>
				<div>
					<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Cloudflare Connection', 'wp-cloudflare-cache' ); ?></h3>
					<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Connect your Cloudflare account to enable this plugin\'s features.', 'wp-cloudflare-cache' ); ?></p>
				</div>
			</div>
			<?php if ( $error ) : ?>
				<div class="sfcfc-notice sfcfc-notice-error">
					<span class="sfcfc-notice-icon dashicons dashicons-warning"></span>
					<div class="sfcfc-notice-body">
						<p class="sfcfc-notice-text"><?php echo esc_html( $error ); ?></p>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( $has_credentials ) : ?>
				<?php if ( $is_connected ) : ?>
					<div class="sfcfc-success-box">
						<div class="sfcfc-success-left">
							<span class="dashicons dashicons-yes-alt"></span>
							<div>
								<p class="sfcfc-success-title"><?php esc_html_e( 'Successfully Connected', 'wp-cloudflare-cache' ); ?></p>
								<p class="sfcfc-success-text"><?php echo esc_html( 'key' === $auth_method ? __( 'Using Global API Key', 'wp-cloudflare-cache' ) : __( 'Using API Token', 'wp-cloudflare-cache' ) ); ?></p>
							</div>
						</div>
						<button type="button" id="sfcfc-disconnect" class="sfcfc-btn sfcfc-btn-link-danger"><?php esc_html_e( 'Disconnect', 'wp-cloudflare-cache' ); ?></button>
					</div>
					<div class="sfcfc-field" id="sfcfc-domain-display">
						<label class="sfcfc-auth-title"><?php esc_html_e( 'Active domain', 'wp-cloudflare-cache' ); ?></label>
						<div class="sfcfc-domain-active">
							<span class="sfcfc-domain-active-left"><span class="dashicons dashicons-admin-site-alt3"></span> <strong><?php echo esc_html( $active_domain ); ?></strong></span>
							<button type="button" id="sfcfc-change-domain" class="sfcfc-btn-link"><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Change Domain', 'wp-cloudflare-cache' ); ?></button>
						</div>
						<p class="sfcfc-field-hint"><?php esc_html_e( 'This domain is currently connected with Cloudflare.', 'wp-cloudflare-cache' ); ?></p>
					</div>
					<div class="sfcfc-field sfcfc-hidden-row" id="sfcfc-domain-edit">
						<label class="sfcfc-field-label" for="sfcfc-zone-domain"><?php esc_html_e( 'Select Domain', 'wp-cloudflare-cache' ); ?></label>
						<?php $this->sfcfc_cloudflare_zone_domain(); ?>
						<p class="sfcfc-field-hint"><?php esc_html_e( 'Choose the domain you want to optimize with Cloudflare.', 'wp-cloudflare-cache' ); ?></p>
						<div class="sfcfc-inline-actions">
							<button type="button" id="sfcfc-save-domain" class="sfcfc-btn sfcfc-btn-primary">
								<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Finish Setup', 'wp-cloudflare-cache' ); ?>
							</button>
							<button type="button" id="sfcfc-cancel-domain" class="sfcfc-btn sfcfc-btn-secondary"><?php esc_html_e( 'Cancel', 'wp-cloudflare-cache' ); ?></button>
						</div>
					</div>
					<?php /* Re-checked asynchronously (see sfcfc-admin.js) so the page never waits on a live Cloudflare API call. */ ?>
					<div id="sfcfc-dns-proxy-check" data-zone-id="<?php echo esc_attr( $zone_id ); ?>"></div>
				<?php else : ?>
					<div class="sfcfc-field" id="sfcfc-domain-edit">
						<label class="sfcfc-field-label" for="sfcfc-zone-domain"><?php esc_html_e( 'Select Domain', 'wp-cloudflare-cache' ); ?></label>
						<?php $this->sfcfc_cloudflare_zone_domain(); ?>
						<p class="sfcfc-field-hint"><?php esc_html_e( 'Choose the domain you want to optimize with Cloudflare.', 'wp-cloudflare-cache' ); ?></p>
					</div>
					<button type="button" id="sfcfc-save-domain" class="sfcfc-btn sfcfc-btn-primary">
						<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Finish Setup', 'wp-cloudflare-cache' ); ?>
					</button>
				<?php endif; ?>
			<?php elseif ( ! $show_connect_form ) : ?>
				<div class="sfcfc-field">
					<p class="sfcfc-description"><?php esc_html_e( 'Not connected yet.', 'wp-cloudflare-cache' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare-setup-wizard' ) ); ?>" class="sfcfc-btn sfcfc-btn-primary"><?php esc_html_e( 'Open Setup Wizard', 'wp-cloudflare-cache' ); ?></a>
				</div>
			<?php else : ?>
				<div class="sfcfc-register">
					<div class="sfcfc-notice">
					<p class="sfcfc-notice-title"><?php esc_html_e( "Don't have a Cloudflare account?", 'wp-cloudflare-cache' ); ?>
						<a href="https://dash.cloudflare.com/sign-up" target="_blank" rel="noopener">
							<?php esc_html_e( 'Sign up for free', 'wp-cloudflare-cache' ); ?>
						</a>
					</p>
					</div>
				</div>
				<div class="sfcfc-field">
					<label class="sfcfc-auth-title" for="sfcfc-cf-auth-method"><?php esc_html_e( 'Authentication Mode', 'wp-cloudflare-cache' ); ?></label>
					<?php $this->sfcfc_cloudflare_auth_method( $auth_method ); ?>
					<p class="sfcfc-field-hint"><?php esc_html_e( 'API Tokens are more secure and limit access to only what this plugin needs.', 'wp-cloudflare-cache' ); ?></p>
				</div>
				<div class="sfcfc-field sfcfc-auth-token-row<?php echo 'token' === $auth_method ? '' : ' sfcfc-hidden-row'; ?>">
					<label class="sfcfc-field-label" for="sfcfc-cf-api-token"><?php esc_html_e( 'API Token', 'wp-cloudflare-cache' ); ?></label>
					<?php $this->sfcfc_cloudflare_token_value( $prefill['cf_api_token'] ?? null ); ?>
				</div>
				<div class="sfcfc-field sfcfc-auth-key-row<?php echo 'token' === $auth_method ? ' sfcfc-hidden-row' : ''; ?>">
					<label class="sfcfc-field-label" for="sfcfc-cf-email"><?php esc_html_e( 'Cloudflare Email', 'wp-cloudflare-cache' ); ?></label>
					<?php $this->sfcfc_cloudflare_email_value( $prefill['cf_email'] ?? null ); ?>
					<p class="sfcfc-field-hint"><?php esc_html_e( 'The email address you use to log in to Cloudflare.', 'wp-cloudflare-cache' ); ?></p>
					<label class="sfcfc-field-label" style="margin-top:14px;" for="sfcfc-cf-api-key"><?php esc_html_e( 'Global API Key', 'wp-cloudflare-cache' ); ?></label>
					<?php $this->sfcfc_cloudflare_key_value( $prefill['cf_api_key'] ?? null ); ?>
					<p class="sfcfc-description"><?php esc_html_e( 'Found in your Cloudflare account under My Profile.', 'wp-cloudflare-cache' ); ?></p>
					<button type="button" class="sfcfc-btn-link sfcfc-toggle-guide"><?php esc_html_e( 'See the Installation Guide', 'wp-cloudflare-cache' ); ?></button>
				</div>
				<button type="button" id="sfcfc-connect-btn" class="sfcfc-btn sfcfc-btn-primary">
					<?php esc_html_e( 'Connect to Cloudflare', 'wp-cloudflare-cache' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param string $error
	 * @param array  $prefill
	 * @return string
	 */
	private function get_connection_card_html( $error = '', $prefill = array(), $minimal = false, $show_connect_form = true ) {
		ob_start();
		$this->sfcfc_render_connection_card( $error, $prefill, $minimal, $show_connect_form );
		return ob_get_clean();
	}

	public function sfcfc_cache_enabled_field() {
		$options         = get_option( 'sfcfc_options' );
		$has_credentials = $this->instance && $this->instance->cachepurge && $this->instance->cachepurge->has_credentials();
		$zone_id         = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';

		if ( ! $has_credentials || ! $zone_id ) {
			?>
			<p class="sfcfc-description"><?php esc_html_e( 'Connect your Cloudflare account to turn this on.', 'wp-cloudflare-cache' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare-setup-wizard' ) ); ?>" class="sfcfc-btn sfcfc-btn-primary"><?php esc_html_e( 'Open Setup Wizard', 'wp-cloudflare-cache' ); ?></a>
			<?php
			return;
		}

		$zones         = $this->instance->cachepurge->get_cached_zones();
		$active_domain = $zones[ $zone_id ] ?? '';
		$cache_enabled = 'on' === ( $options['cf_cache_enabled'] ?? 'on' );
		$proxy_blocked = '0' === $this->instance->get_single_config( 'cf_dns_proxied', '' );
		?>
		<div id="sfcfc-dns-proxy-check" data-zone-id="<?php echo esc_attr( $zone_id ); ?>"></div>
		<div id="sfcfc-cache-enabled-field"<?php echo $proxy_blocked ? ' class="sfcfc-hidden-row"' : ''; ?>>
			<?php $this->render_toggle_row( 'cf_cache_enabled', $cache_enabled ? 'on' : '', __( 'Enable Cloudflare Full-Page Caching', 'wp-cloudflare-cache' ) ); ?>
			<p class="sfcfc-description"><?php esc_html_e( "Turning this on also caches your site's full HTML pages at Cloudflare's edge — the biggest speed boost this plugin offers.", 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-field<?php echo $proxy_blocked ? '' : ' sfcfc-hidden-row'; ?>" id="sfcfc-proxy-blocked-notice">
			<div class="sfcfc-notice sfcfc-notice-warning">
				<span class="sfcfc-notice-icon dashicons dashicons-warning"></span>
				<div class="sfcfc-notice-body">
					<p class="sfcfc-notice-text">
						<strong><?php esc_html_e( 'Full-page caching cannot turn on yet.', 'wp-cloudflare-cache' ); ?></strong>
						<?php
						printf(
							/* translators: %s: site hostname */
							esc_html__( ' %s is not proxied through Cloudflare (grey cloud), so traffic never reaches Cloudflare\'s edge to be cached. Enable the orange cloud for this domain in your Cloudflare DNS settings, then recheck below.', 'wp-cloudflare-cache' ),
							'<strong>' . esc_html( $active_domain ?: wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</strong>'
						);
						?>
					</p>
				</div>
				<button type="button" id="sfcfc-recheck-proxy" class="sfcfc-btn sfcfc-btn-secondary sfcfc-btn-sm"><?php esc_html_e( 'Recheck', 'wp-cloudflare-cache' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Writes sfcfc_options as-is, bypassing sanitize_sfcfc_options(). That callback is registered
	 * against every update_option( 'sfcfc_options', ... ) call (not just the Settings API form), and
	 * it deliberately re-preserves the existing credential fields — which would silently undo the
	 * very credential changes Connect/Disconnect are trying to make here.
	 *
	 * @param array $options
	 */
	private function update_sfcfc_options_directly( $options ) {
		$sanitize_callback = array( $this->instance->sanitize, 'sanitize_sfcfc_options' );
		remove_filter( 'sanitize_option_sfcfc_options', $sanitize_callback );
		update_option( 'sfcfc_options', $options, 'yes' );
		add_filter( 'sanitize_option_sfcfc_options', $sanitize_callback );

		if ( $this->instance ) {
			$this->instance->clear_options_cache( 'sfcfc_options' );
		}
	}

	/**
	 * Checks (and caches, on cf_dns_proxied) whether this site's hostname is proxied through
	 * Cloudflare. Skipped on localhost — there's no real Cloudflare DNS to check there, and treating
	 * it as "blocked" would make local dev/testing impossible. Shared by the Finish Setup handler and
	 * the async recheck below, so both write the same cached value the same way.
	 *
	 * @param string $zone_id
	 * @return bool|null True/false when known, null when it couldn't be determined (including
	 *                    localhost, where it's simply not checked).
	 */
	private function sfcfc_check_and_cache_dns_proxied( $zone_id ) {
		if ( ! $this->instance || $this->instance->is_localhost() || ! $this->instance->cachepurge ) {
			return null;
		}

		$hostname = wp_parse_url( home_url(), PHP_URL_HOST );
		$proxied  = $this->instance->cachepurge->is_dns_proxied( $zone_id, $hostname );

		if ( null !== $proxied ) {
			$this->instance->set_single_config( 'cf_dns_proxied', $proxied ? '1' : '0' );
			$this->instance->update_config();
		}

		return $proxied;
	}

	/**
	 * AJAX handler: re-runs the DNS-proxied check in the background (see sfcfc-admin.js's
	 * sfcfcRunDnsProxyCheck()), so the page never blocks on this live Cloudflare API call while
	 * rendering. Returns a tri-state so the caller can toggle the Full-Page Caching field vs its
	 * "not proxied" warning without a full reload.
	 */
	public function ajax_check_dns_proxy() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$zone_id = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';

		if ( ! $zone_id ) {
			wp_send_json_success( array( 'proxied' => null ) );
		}

		wp_send_json_success( array( 'proxied' => $this->sfcfc_check_and_cache_dns_proxied( $zone_id ) ) );
	}

	/**
	 * Renders the Authentication Method field.
	 */
	public function sfcfc_cloudflare_auth_method( $override = null ) {
		$options = get_option( 'sfcfc_options' );
		$method  = $override ?? ( $options['cf_auth_method'] ?? 'token' );
		?>
		<select name="sfcfc_options[cf_auth_method]" id="sfcfc-cf-auth-method" class="sfcfc-select sfcfc-auth-toggle">
			<option value="token" <?php selected( $method, 'token' ); ?>><?php esc_html_e( 'API Token (recommended)', 'wp-cloudflare-cache' ); ?></option>
			<option value="key" <?php selected( $method, 'key' ); ?>><?php esc_html_e( 'Global API Key', 'wp-cloudflare-cache' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Renders the API Token field.
	 */
	public function sfcfc_cloudflare_token_value( $override = null ) {
		$options = get_option( 'sfcfc_options' );
		$value   = $override ?? ( $options['cf_api_token'] ?? '' ); ?>
		<div class="wp-pwd">
			<input type="password" name="sfcfc_options[cf_api_token]" id="sfcfc-cf-api-token" class="sfcfc-input sfcfc-input-mono" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="sfcfc-btn sfcfc-btn-icon pwd-toggle" data-toggle="0" aria-label="<?php esc_attr_e( 'Show password', 'wp-cloudflare-cache' ); ?>">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<span class="text screen-reader-text"><?php esc_html_e( 'Show', 'wp-cloudflare-cache' ); ?></span>
			</button>
		</div>
        <p class="sfcfc-description"><?php esc_html_e( 'Found in your Cloudflare account under My Profile.', 'wp-cloudflare-cache' ); ?></p>
		<button type="button" class="sfcfc-btn-link sfcfc-toggle-guide"><?php esc_html_e( 'See the Installation Guide', 'wp-cloudflare-cache' ); ?></button>
		<?php
	}

	/**
	 * Renders the Cache Exclude URLs field.
	 */
	public function sfcfc_cloudflare_exclude_urls() {
		$options = get_option( 'sfcfc_options' ); ?>
		<textarea rows="4" cols="80" class="sfcfc-purge-url" name="sfcfc_options[cache_exclude_urls]" id="sfcfc-cache-exclude-urls"><?php echo esc_textarea( $options['cache_exclude_urls'] ?? '' ); ?></textarea>
		<p class="sfcfc-description"><?php esc_html_e( 'Never cache these URL paths (one per line). Supports * as a wildcard, e.g. /cart/ or /product/*/reviews', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Exclude Cookies field.
	 */
	public function sfcfc_cache_exclude_cookies() {
		$options = get_option( 'sfcfc_options' ); ?>
		<textarea rows="4" cols="80" class="sfcfc-purge-url" name="sfcfc_options[cache_exclude_cookies]" id="sfcfc-cache-exclude-cookies"><?php echo esc_textarea( $options['cache_exclude_cookies'] ?? '' ); ?></textarea>
		<p class="sfcfc-description"><?php esc_html_e( 'Never cache a request carrying any of these cookie names (one per line). Supports * as a wildcard, e.g. wordpress_logged_in_* or woocommerce_*', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Custom URL Parameters field.
	 */
	public function sfcfc_cache_exclude_query_params() {
		$options = get_option( 'sfcfc_options' ); ?>
		<textarea rows="4" cols="80" class="sfcfc-purge-url" name="sfcfc_options[cache_exclude_query_params]" id="sfcfc-cache-exclude-query-params"><?php echo esc_textarea( $options['cache_exclude_query_params'] ?? '' ); ?></textarea>
		<p class="sfcfc-description"><?php esc_html_e( 'Never cache a URL whose query string carries any of these parameter names (one per line), e.g. add-to-cart or utm_source. Supports * as a wildcard, e.g. utm_*', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Cloudflare Domain field.
	 */
	public function sfcfc_cloudflare_zone_domain() {
		$saved_zone_id = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';
		$zones         = ( $this->instance && $this->instance->cachepurge ) ? $this->instance->cachepurge->get_cached_zones() : array();
		?>
		<?php if ( empty( $zones ) ) : ?>
			<select class="sfcfc-select" disabled>
				<option><?php esc_html_e( 'Save your credentials above, then choose domains.', 'wp-cloudflare-cache' ); ?></option>
			</select>
		<?php else : ?>
			<select class="sfcfc-select" name="sfcfc_options[zone_domain]" id="sfcfc-zone-domain">
				<option value=""><?php esc_html_e( '— Select the domain name —', 'wp-cloudflare-cache' ); ?></option>
				<?php foreach ( $zones as $id => $name ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_zone_id, $id ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the Bypass Sitemap field.
	 */
	public function sfcfc_bypass_sitemap() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'bypass_sitemap', $options['bypass_sitemap'] ?? 'on', __( 'Never cache sitemap.xml / sitemap_index.xml.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Ensures search engines always see your latest sitemap instead of a cached, possibly outdated one.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Bypass robots.txt field.
	 */
	public function sfcfc_bypass_robots() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'bypass_robots', $options['bypass_robots'] ?? 'on', __( 'Never cache robots.txt.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Keeps crawler rules instantly up to date whenever you change them.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Bypass AJAX Requests field.
	 */
	public function sfcfc_bypass_ajax() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'bypass_ajax', $options['bypass_ajax'] ?? 'on', __( 'Never cache admin-ajax.php requests.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Prevents live, dynamic AJAX responses, like cart counts or live search, from being served stale out of the cache.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Bypass REST API Requests field.
	 */
	public function sfcfc_bypass_rest_api() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'bypass_rest_api', $options['bypass_rest_api'] ?? 'on', __( 'Never cache WordPress REST API (wp-json) requests.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Keeps REST API responses always fresh, which matters for the block editor, mobile apps, and any script reading live data from your site.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the E-mail Address field.
	 */
	public function sfcfc_cloudflare_email_value( $override = null ) {
		$options = get_option('sfcfc_options');
		$value   = $override ?? ( $options['cf_email'] ?? '' ); ?>
         <input class="sfcfc-input sfcfc-input-mono" type="text" id="sfcfc-cf-email" name="sfcfc_options[cf_email]" placeholder="user@example.com" value="<?php echo esc_attr( $value ); ?>">
     <?php
	}
	
	/**
	 * Renders the Global API Key field.
	 */
	public function sfcfc_cloudflare_key_value( $override = null ) {
		$options = get_option('sfcfc_options');
		$value   = $override ?? ( $options['cf_api_key'] ?? '' ); ?>
		<div class="wp-pwd">
			<input type="password" name="sfcfc_options[cf_api_key]" id="sfcfc-cf-api-key" class="sfcfc-input sfcfc-input-mono" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="sfcfc-btn sfcfc-btn-icon pwd-toggle" data-toggle="0" aria-label="<?php esc_attr_e( 'Show password', 'wp-cloudflare-cache' ); ?>">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<span class="text screen-reader-text"><?php esc_html_e( 'Show', 'wp-cloudflare-cache' ); ?></span>
			</button>
		</div>
    <?php
	}
	
	/**
	 * Renders the Edge Cache-Control max-age field.
	 */
	public function sfcfc_cloudflare_maxage_value() {
		$options = get_option('sfcfc_options'); ?>
                <input class="sfcfc-input sfcfc-input-mono" type="number" id="sfcfc-expire" name="sfcfc_options[cf_maxage]" value="<?php echo esc_attr( $options['cf_maxage'] ?? '604800' ); ?>">
                <p class="sfcfc-description"><?php esc_html_e('Sent as s-maxage — how long Cloudflare\'s edge keeps the page. In seconds, minimum 300 (5 minutes), e.g. 7 days = 604800.', 'wp-cloudflare-cache'); ?></p>
    <?php
	}

	/**
	 * Renders the Browser Cache max-age field.
	 */
	public function sfcfc_cloudflare_browser_maxage_value() {
		$options = get_option('sfcfc_options'); ?>
                <input class="sfcfc-input sfcfc-input-mono" type="number" id="sfcfc-browser-expire" name="sfcfc_options[cf_browser_maxage]" min="0" value="<?php echo esc_attr( $options['cf_browser_maxage'] ?? '650' ); ?>">
                <p class="sfcfc-description"><?php esc_html_e('Sent as max-age — how long the visitor\'s own browser keeps the page. In seconds. Recommended: 60-600 seconds.', 'wp-cloudflare-cache'); ?></p>
    <?php
	}

	/**
	 * Renders the Cloudflare Browser Cache TTL field.
	 */
	public function sfcfc_cloudflare_browser_ttl() {
		$options = get_option('sfcfc_options');
		$value   = $options['cf_browser_ttl'] ?? '300'; ?>
		<select name="sfcfc_options[cf_browser_ttl]" id="sfcfc-cf-browser-ttl" class="sfcfc-select">
			<?php foreach ( SFCFC_Cache::get_browser_ttl_options() as $seconds => $label ) : ?>
				<option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( $value, $seconds ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="sfcfc-description"><?php esc_html_e( 'How long visitor browsers cache the page, enforced by Cloudflare. Recommended: 1-10 minutes. This overrides the header above.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Cloudflare Edge Cache TTL field.
	 */
	public function sfcfc_cloudflare_edge_ttl() {
		$options = get_option('sfcfc_options');
		$value   = $options['cf_edge_ttl'] ?? ''; ?>
		<select name="sfcfc_options[cf_edge_ttl]" id="sfcfc-cf-edge-ttl" class="sfcfc-select">
			<?php foreach ( SFCFC_Cache::get_edge_ttl_options() as $seconds => $label ) : ?>
				<option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( $value, $seconds ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="sfcfc-description"><?php esc_html_e( 'How long Cloudflare\'s own edge cache keeps the page before checking your server again. This overrides the header above.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Caching Level field.
	 */
	public function sfcfc_cf_cache_level_field() {
		$options = get_option( 'sfcfc_options' );
		$value   = $options['cf_cache_level'] ?? 'aggressive';
		?>
		<select name="sfcfc_options[cf_cache_level]" class="sfcfc-select">
			<option value="aggressive" <?php selected( $value, 'aggressive' ); ?>><?php esc_html_e( 'Standard (Recommended)', 'wp-cloudflare-cache' ); ?></option>
			<option value="basic" <?php selected( $value, 'basic' ); ?>><?php esc_html_e( 'No Query String', 'wp-cloudflare-cache' ); ?></option>
			<option value="simplified" <?php selected( $value, 'simplified' ); ?>><?php esc_html_e( 'Ignore Query String', 'wp-cloudflare-cache' ); ?></option>
		</select>
		<p class="sfcfc-description"><?php esc_html_e( 'Standard caches each query string combination separately. The other options tell Cloudflare\'s edge to ignore query strings when matching cached files.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Custom Purge URLs field.
	 */
	public function sfcfc_cloudflare_purge_url_cache() {
		$options = get_option('sfcfc_options'); ?>
                <textarea type="textarea" rows="5" cols="80" class="sfcfc-purge-url" name="sfcfc_options[purge_urls]" id="sfcfc-purge-urls"><?php echo esc_textarea( $options['purge_urls'] ?? '' ); ?></textarea>
        <p class="sfcfc-description"><?php esc_html_e( 'Add one URL path per line, up to 30 URLs.', 'wp-cloudflare-cache' ); ?><br><?php esc_html_e( 'Example: To purge https://example.com/sample-page/, add /sample-page/.', 'wp-cloudflare-cache' ); ?></p>
       <p class="sfcfc-description"><?php esc_html_e( "Wildcards are not supported. Cloudflare's purge API only accepts exact URLs.", 'wp-cloudflare-cache' ); ?></p>
    <?php
	}
	
	/**
	 * Renders the Purge Homepage field.
	 */
	public function sfcfc_purge_homepage_on_update() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_homepage_on_update', $options['purge_homepage_on_update'] ?? '', __( 'When a post or page is updated or added.', 'wp-cloudflare-cache' ) );
		$this->render_toggle_row( 'purge_homepage_on_trash', $options['purge_homepage_on_trash'] ?? '', __( 'When a published post or page is trashed.', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Purge Archives field.
	 */
	public function sfcfc_purge_archives_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_archives_on_update', $options['purge_archives_on_update'] ?? '', __( 'When a post is updated or added.', 'wp-cloudflare-cache' ) );
		$this->render_toggle_row( 'purge_archives_on_trash', $options['purge_archives_on_trash'] ?? '', __( 'When a published post is trashed.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Also purges the post\'s date, category, tag, and author archive pages.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}
	
	/**
	 * Renders the Purge On Comments field.
	 */
	public function sfcfc_purge_on_comments() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_on_comment_approved', $options['purge_on_comment_approved'] ?? '', __( 'When a comment is approved or published.', 'wp-cloudflare-cache' ) );
		$this->render_toggle_row( 'purge_on_comment_deleted', $options['purge_on_comment_deleted'] ?? '', __( 'When a comment is unapproved or deleted.', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Purge Feeds field.
	 */
	public function sfcfc_purge_feeds_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_feeds', $options['purge_feeds'] ?? '', __( 'Also purge feed URLs along with the post.', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Purge AMP URL field.
	 */
	public function sfcfc_purge_amp_urls_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_amp_urls', $options['purge_amp_urls'] ?? '', __( 'Purge AMP URLs along with posts & pages.', 'wp-cloudflare-cache' ) );
	}

	public function sfcfc_purge_on_theme_plugin_update_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'purge_on_theme_plugin_update', $options['purge_on_theme_plugin_update'] ?? '', __( 'Automatically purge the entire cache after a theme or plugin update.', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Purge on Post/Page Update field.
	 */
	public function sfcfc_purge_mode_field() {
		$options = get_option( 'sfcfc_options' );
		$mode    = ( 'on' === ( $options['auto_purge_whole'] ?? '' ) ) ? 'whole' : 'page';
		?>
		<select name="sfcfc_options[auto_purge_whole]" id="sfcfc-purge-mode" class="sfcfc-select">
			<option value="" <?php selected( $mode, 'page' ); ?>><?php esc_html_e( 'Purge only the updated page (Recommended)', 'wp-cloudflare-cache' ); ?></option>
			<option value="on" <?php selected( $mode, 'whole' ); ?>><?php esc_html_e( 'Purge the entire cache', 'wp-cloudflare-cache' ); ?></option>
		</select>
		<p class="sfcfc-description"><?php esc_html_e( 'How much to clear from Cloudflare when a post or page is saved.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	private function render_toggle_row( $field_name, $current_value, $label, $radio_group = '' ) {
		sfcfc_render_toggle_row( $field_name, $current_value, $label, $radio_group );
	}

	/**
	 * Renders the Enable Preloader field.
	 */
	public function sfcfc_enable_preloader() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'enable_preloader', $options['enable_preloader'] ?? '', __( 'Master switch for the preloader below. Turn this off and none of its options run.', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Preload Purged Posts/Pages field.
	 */
	public function sfcfc_preloader_start_on_purge() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'preloader_start_on_purge', $options['preloader_start_on_purge'] ?? 'on', __( "Automatically re-fetch URLs after they are purged, so visitors don't encounter a cold cache.", 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Preload Latest Posts field.
	 */
	public function sfcfc_preload_latest_posts() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'preload_latest_posts', $options['preload_latest_posts'] ?? '', __( 'Preload latest 20 published posts', 'wp-cloudflare-cache' ) );
	}

	/**
	 * Renders the Preload Sitemap URLs field.
	 */
	public function sfcfc_preload_sitemap() {
		$options       = get_option( 'sfcfc_options' );
		$sitemap_paths = $options['preload_sitemap_paths'] ?? '';
		$this->render_toggle_row( 'preload_sitemap', $options['preload_sitemap'] ?? '', __( 'Preload the following sitemaps', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'One sitemap per line.', 'wp-cloudflare-cache' ); ?></p>
		<textarea rows="3" cols="80" class="sfcfc-purge-url" name="sfcfc_options[preload_sitemap_paths]" placeholder="/wp-sitemap.xml"><?php echo esc_textarea( $sitemap_paths ); ?></textarea>
		<p class="sfcfc-description"><?php esc_html_e( 'Example: /post-sitemap.xml, /page-sitemap.xml. Leave blank to use /wp-sitemap.xml, the sitemap WordPress generates on its own.', 'wp-cloudflare-cache' ); ?></p>
		<p class="sfcfc-description sfcfc-inline-note"><strong><?php esc_html_e( 'Note:', 'wp-cloudflare-cache' ); ?></strong> <?php esc_html_e( 'These sitemap URLs are used for both the manual preload runs and the cron-triggered preloading.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Enable Cron Preloading field.
	 */
	public function sfcfc_preloader_cronjob() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'preloader_cronjob_enabled', $options['preloader_cronjob_enabled'] ?? '', __( 'Start the preloader via Cronjob', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Run the preloader automatically using a system cron without logging in.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Cron Preload URL field: the copyable URL a system cron hits, plus its
	 * regenerate-key control. Split out from sfcfc_preloader_cronjob() into its own row so it
	 * gets its own left-column label instead of being buried under "Enable Cron Preloading".
	 */
	public function sfcfc_cron_preload_url_field() {
		$purge = $this->instance ? $this->instance->cachepurge : null;
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Use a system cron to hit this URL and start the preloader automatically.', 'wp-cloudflare-cache' ); ?></p>
		<p class="sfcfc-description">
			<input type="text" class="sfcfc-input" id="sfcfc-preload-url" readonly value="<?php echo esc_url( $purge ? $purge->build_preload_url() : '' ); ?>">
			<a id="sfcfc-regenerate-preload-key" class="sfcfc-btn sfcfc-btn-secondary"><?php esc_html_e( 'Regenerate Key', 'wp-cloudflare-cache' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Renders the standalone Run Preloader Now button.
	 */
	public function sfcfc_run_preloader_now() {
		?>
		<a id="sfcfc-run-preloader" class="sfcfc-btn sfcfc-btn-primary"><?php esc_html_e( 'Run Preloader Now', 'wp-cloudflare-cache' ); ?></a>
		<?php
	}
	
	/**
	 * AJAX handler: resets settings to defaults, keeping the saved Cloudflare credentials/domain.
	 */
	public function sfcfc_reset_settings() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );
		/**
		 * manage_options, not edit_posts: this changes plugin configuration (same as Save Settings,
		 * and same as the page-level gate on sfcfc_settings_page()), unlike a mere cache purge.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		$current = get_option( 'sfcfc_options', array() );

		$reset = array_merge( sfcfc_get_default_options(), array(
			'cf_auth_method' => $current['cf_auth_method'] ?? 'token',
			'cf_email'       => $current['cf_email'] ?? '',
			'cf_api_key'     => $current['cf_api_key'] ?? '',
			'cf_api_token'   => $current['cf_api_token'] ?? '',
		) );

		update_option( 'sfcfc_options', $reset, 'yes' );

		wp_send_json_success( array(
			'message' => __( 'Settings reset to defaults. Your Cloudflare credentials and domain were kept.', 'wp-cloudflare-cache' ),
			'type'    => 'success',
		) );
	}

	public function sfcfc_export_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'sfcfc_export_settings', '_wpnonce', false ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-cloudflare-cache' ) );
		}

		$settings = get_option( 'sfcfc_options', array() );
		foreach ( sfcfc_credential_option_keys() as $sfcfc_key ) {
			unset( $settings[ $sfcfc_key ] );
		}

		$payload = array(
			'plugin'      => SFCFC_NAME,
			'version'     => SFCFC_VERSION,
			'exported_at' => gmdate( 'c' ),
			'settings'    => $settings,
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="superflare-cache-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * admin-post handler: restores settings from an uploaded JSON file (as produced by
	 * sfcfc_export_settings()). Cloudflare credentials and the connected zone are never read from
	 * the file — they always carry over from the current site, same as a normal Save/Reset.
	 */
	public function sfcfc_import_settings() {
		$redirect = admin_url( 'admin.php?page=superflare-import-export' );

		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'sfcfc_import_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-cloudflare-cache' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated below (upload error, size, JSON shape) and passed through sfcfc_sanitize_imported_settings() before storage.
		$sfcfc_upload = $_FILES['sfcfc_import_file'] ?? null;

		if ( ! $sfcfc_upload || UPLOAD_ERR_OK !== $sfcfc_upload['error'] || empty( $sfcfc_upload['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'sfcfc_import', 'error', $redirect ) );
			exit;
		}

		/**
		 * 1MB is generous for a settings file that's a handful of scalars and two small arrays.
		 */
		if ( $sfcfc_upload['size'] > MB_IN_BYTES ) {
			wp_safe_redirect( add_query_arg( 'sfcfc_import', 'error', $redirect ) );
			exit;
		}

		$sfcfc_contents = file_get_contents( $sfcfc_upload['tmp_name'] );
		$sfcfc_decoded  = json_decode( (string) $sfcfc_contents, true );
		$sfcfc_data     = is_array( $sfcfc_decoded ) && is_array( $sfcfc_decoded['settings'] ?? null ) ? $sfcfc_decoded['settings'] : $sfcfc_decoded;

		if ( ! is_array( $sfcfc_data ) ) {
			wp_safe_redirect( add_query_arg( 'sfcfc_import', 'error', $redirect ) );
			exit;
		}

		update_option( 'sfcfc_options', $this->instance->sanitize->sfcfc_sanitize_imported_settings( $sfcfc_data ), 'yes' );

		wp_safe_redirect( add_query_arg( 'sfcfc_import', 'success', $redirect ) );
		exit;
	}

	public function sfcfc_save_settings() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		$was_cache_enabled = 'on' === ( get_option( 'sfcfc_options', array() )['cf_cache_enabled'] ?? 'on' );

		/**
		 * update_option() already runs sanitize_sfcfc_options() via the sanitize_option_sfcfc_options
		 * filter that register_setting() sets up; calling it again here would double every
		 * Cloudflare API call.
		 */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_sfcfc_options() via the sanitize_option_sfcfc_options filter inside update_option().
		$posted = isset( $_POST['sfcfc_options'] ) ? wp_unslash( $_POST['sfcfc_options'] ) : array();
		/** Explicit 'yes': read on every front-end request, so it must stay autoloaded. */
		update_option( 'sfcfc_options', $posted, 'yes' );

		$is_cache_enabled = 'on' === ( get_option( 'sfcfc_options', array() )['cf_cache_enabled'] ?? 'on' );

		/**
		 * Highest-priority settings_errors() type wins: error, then warning, then success.
		 */
		$type    = 'success';
		$message = __( 'Great! Settings saved Successfully.', 'wp-cloudflare-cache' );

		if ( $is_cache_enabled !== $was_cache_enabled ) {
			$message = $is_cache_enabled
				? __( 'Cloudflare Full-Page Caching enabled.', 'wp-cloudflare-cache' )
				: __( 'Cloudflare Full-Page Caching disabled.', 'wp-cloudflare-cache' );
		}

		foreach ( get_settings_errors( 'sfcfc_options' ) as $error ) {
			$message = $error['message'];
			if ( 'error' === $error['type'] ) {
				$type = 'error';
				break;
			}
			if ( 'warning' === $error['type'] && 'error' !== $type ) {
				$type = 'warning';
			}
		}

		$payload = array(
			'message' => $message,
			'type'    => $type,
		);

		if ( 'error' === $type ) {
			wp_send_json_error( $payload );
		}

		wp_send_json_success( $payload );
	}

	/**
	 * AJAX handler: clears the saved Cloudflare credentials, zone selection, and cached zones list.
	 */
	public function sfcfc_disconnect() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ),
			) );
		}

		$options     = get_option( 'sfcfc_options', array() );
		$purge       = $this->instance ? $this->instance->cachepurge : null;
		$old_zone_id = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';

		$cleanup = null;

		if ( $purge && $old_zone_id && $purge->has_credentials() ) {
			$cleanup = $purge->remove_cache_rules( $old_zone_id );
		}

		$options['cf_api_token'] = '';
		$options['cf_api_key']   = '';
		$options['cf_email']     = '';
		$this->update_sfcfc_options_directly( $options );

		if ( $this->instance ) {
			$this->instance->set_single_config( 'cf_zone_id', '' );
			$this->instance->set_single_config( 'cf_zones_cache', array() );
			$this->instance->update_config();
		}

		$message = __( 'Disconnected from Cloudflare.', 'wp-cloudflare-cache' );
		$type    = 'success';

		if ( is_wp_error( $cleanup ) ) {
			$type    = 'warning';
			$message = sprintf(
				/* translators: %s: error message */
				__( 'Disconnected from Cloudflare, but the Cache Rule could not be removed from the domain: %s', 'wp-cloudflare-cache' ),
				$cleanup->get_error_message()
			);
		}

		wp_send_json_success( array(
			'message'  => $message,
			'type'     => $type,
			'cardHtml' => $this->get_connection_card_html( '', array(), ! empty( $_POST['minimal'] ), ! isset( $_POST['show_connect_form'] ) || ! empty( $_POST['show_connect_form'] ) ),
		) );
	}

	/**
	 * AJAX handler: validates and saves Cloudflare credentials, then fetches the zone list.
	 * Credentials are only written to sfcfc_options once Cloudflare actually accepts them, so a
	 * failed attempt never overwrites a previously working connection.
	 */
	public function sfcfc_connect_cloudflare() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ) ) );
		}

		$attempt = array(
			'cf_auth_method' => ( sanitize_key( wp_unslash( $_POST['cf_auth_method'] ?? 'token' ) ) === 'key' ) ? 'key' : 'token',
			'cf_email'       => sanitize_email( wp_unslash( $_POST['cf_email'] ?? '' ) ),
			'cf_api_key'     => sanitize_text_field( wp_unslash( $_POST['cf_api_key'] ?? '' ) ),
			'cf_api_token'   => sanitize_text_field( wp_unslash( $_POST['cf_api_token'] ?? '' ) ),
		);

		$minimal = ! empty( $_POST['minimal'] );
		$purge   = $this->instance ? $this->instance->cachepurge : null;

		if ( ! $purge || ! $purge->has_credentials( $attempt ) ) {
			wp_send_json_error( array(
				'cardHtml' => $this->get_connection_card_html( __( 'Please enter your Cloudflare credentials.', 'wp-cloudflare-cache' ), $attempt, $minimal ),
			) );
		}

		$zones = $purge->refresh_zones_cache( $attempt );

		if ( empty( $zones ) ) {
			wp_send_json_error( array(
				'cardHtml' => $this->get_connection_card_html( __( 'Could not connect to Cloudflare. Please check your credentials and try again.', 'wp-cloudflare-cache' ), $attempt, $minimal ),
			) );
		}

		$this->update_sfcfc_options_directly( array_merge( get_option( 'sfcfc_options', array() ), $attempt ) );
		delete_option( 'sfcfc_migration_needs_attention' );

		wp_send_json_success( array(
			'cardHtml' => $this->get_connection_card_html( '', array(), $minimal ),
		) );
	}

	/**
	 * AJAX handler: saves the chosen zone/domain and pushes the Cache Rule + Browser TTL to it.
	 */
	public function sfcfc_confirm_zone() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ) ) );
		}

		$purge   = $this->instance ? $this->instance->cachepurge : null;
		$zones   = $purge ? $purge->get_cached_zones() : array();
		$zone_id = sanitize_text_field( wp_unslash( $_POST['zone_id'] ?? '' ) );

		if ( ! $zone_id || ! isset( $zones[ $zone_id ] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please choose a valid domain.', 'wp-cloudflare-cache' ),
			) );
		}

		$old_zone_id      = $this->instance->get_single_config( 'cf_zone_id', '' );
		$old_zone_cleanup  = null;

		if ( $purge && $old_zone_id && $old_zone_id !== $zone_id ) {
			$old_zone_cleanup = $purge->remove_cache_rules( $old_zone_id );
		}

		$this->instance->set_single_config( 'cf_zone_id', $zone_id );
		$this->instance->update_config();

		$type    = 'success';
		$message = __( 'Domain saved. Cloudflare is now protecting your site.', 'wp-cloudflare-cache' );

		if ( $purge ) {
			$options     = get_option( 'sfcfc_options', array() );
			$sync_result = $purge->sync_cache_rule_state( $zone_id, $options );

			if ( is_wp_error( $sync_result ) ) {
				/**
				 * 'warning', not 'error': the domain choice itself was saved fine, only the live
				 * Cloudflare sync failed.
				 */
				$type    = 'warning';
				$message = sprintf(
					/* translators: %s: error message */
					__( 'Domain saved, but Cloudflare could not be updated: %s', 'wp-cloudflare-cache' ),
					$sync_result->get_error_message()
				);
			} elseif ( is_wp_error( $old_zone_cleanup ) ) {
				$type    = 'warning';
				$message = sprintf(
					/* translators: %s: error message */
					__( 'Domain saved, but the previous domain\'s Cache Rule could not be removed: %s', 'wp-cloudflare-cache' ),
					$old_zone_cleanup->get_error_message()
				);
			} else {
				/**
				 * A Cache Rule with no proxied DNS record behind it never actually caches anything —
				 * traffic bypasses Cloudflare entirely on a grey-clouded record. Check this now, while
				 * we already have the zone in hand, instead of letting the success message imply
				 * caching is live when it isn't. Also feeds cf_dns_proxied (skipped on localhost), which
				 * the connection card reads to gate the Full-Page Caching toggle itself.
				 */
				$proxied = $this->sfcfc_check_and_cache_dns_proxied( $zone_id );

				if ( false === $proxied ) {
					$hostname = wp_parse_url( home_url(), PHP_URL_HOST );
					/**
					 * This message reaches the page as plain text (the toast uses .text(), not .html()),
					 * so no markup here.
					 */
					$type    = 'warning';
					$message = sprintf(
						/* translators: %s: site hostname */
						__( 'Domain saved and the Cache Rule was created, but %s is not proxied through Cloudflare yet (grey cloud). Enable the orange cloud for this domain in your Cloudflare DNS settings, or caching will not take effect.', 'wp-cloudflare-cache' ),
						$hostname
					);
				}
			}
		}

		wp_send_json_success( array(
			'message'  => $message,
			'type'     => $type,
			'cardHtml' => $this->get_connection_card_html( '', array(), ! empty( $_POST['minimal'] ) ),
		) );
	}

	
	/**
	 * Adds the "Purge SuperFlare Cache" admin bar button.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function sfcfc_admin_bar_menu_button( $admin_bar ) {
		$options = get_option( 'sfcfc_options' );

		if ( 'on' === ( $options['remove_purge_toolbar'] ?? '' ) ) {
			return;
		}

		if ( $this->instance && $this->instance->can_current_user_purge_cache() ) {
			$admin_bar->add_menu([
				'id'    => 'sfcfc-purge-button',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">Purge SuperFlare Cache</span>',
				'href'  => '#',
			]);
		}
	}
	

	/**
	 * Renders the Cache Control section intro.
	 */
	public function sfcfc_section_cache_control_info() {
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'The Cache-Control header this site sends with every page.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Cache Exclusions section intro.
	 */
	public function sfcfc_section_cache_exclusions_info() {
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Choose which types of pages should never be cached. Needs Edge/Browser Cache TTL set to "Respect Existing Headers" to actually take effect — a fixed TTL override on either can make Cloudflare ignore them.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Empty section callback for sections that need no intro text.
	 */
	public function sfcfc_section_info() {}


	/**
	 * Renders one Don't Cache For toggle plus its description. $args['tag_key'] identifies which
	 * conditional tag; the field's own title (rendered as the row's <th> by the Settings API) already
	 * names the page type, so the row itself just needs the toggle and what it does.
	 *
	 * @param array $args
	 */
	public function sfcfc_dont_cache_for_field( $args ) {
		$key         = $args['tag_key'];
		$options     = get_option( 'sfcfc_options' );
		$saved       = (array) ( $options['dont_cache_for'] ?? array() );
		$tag         = SFCFC_Cache::get_dont_cache_for_tags()[ $key ] ?? array();
		$description = $tag[2] ?? '';
		$more        = $tag[3] ?? '';
		$this->render_toggle_row( "dont_cache_for][$key", $saved[ $key ] ?? '', $description );
		if ( '' !== $more ) {
			?>
			<p class="sfcfc-description"><?php echo esc_html( $more ); ?></p>
			<?php
		}
	}

	public function sfcfc_section_global_exclusions_info() {
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Always excluded: Logged-in users, admin requests, preview pages, and password-protected posts and pages are never cached. AJAX and REST API requests are controlled separately below.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_bypass_query_string_field() {
		$options = get_option( 'sfcfc_options' );
		$saved   = (array) ( $options['dont_cache_for'] ?? array() );
		$this->render_toggle_row( 'dont_cache_for][query_var', $saved['query_var'] ?? '', __( 'Skips caching for any URL with a query string, like ?ref=123.', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Useful if referral or tracking parameters are creating countless cache variations of the same page.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Crawler Hints field.
	 */
	public function sfcfc_cf_crawler_hints_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_crawler_hints', $options['cf_crawler_hints'] ?? '', __( 'Enable Crawler Hints', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Tells search engines how often your pages actually change, so they crawl on a schedule that matches your content instead of guessing.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Early Hints field.
	 */
	public function sfcfc_cf_early_hints_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_early_hints', $options['cf_early_hints'] ?? '', __( 'Enable Early Hints', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Cloudflare sends an early 103 response with preload hints while your server is still preparing the full page, so browsers start fetching assets sooner.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Rocket Loader field.
	 */
	public function sfcfc_cf_rocket_loader_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_rocket_loader', $options['cf_rocket_loader'] ?? '', __( 'Enable Rocket Loader', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Defers JavaScript execution until after the page renders, improving perceived load time. Test your site after enabling — it can occasionally conflict with scripts that expect to run immediately.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_http3_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_http3', $options['cf_http3'] ?? '', __( 'Enable HTTP/3 (with QUIC)', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Lets supporting browsers connect over the newer, generally faster HTTP/3 protocol.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_zero_rtt_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_zero_rtt', $options['cf_zero_rtt'] ?? '', __( 'Enable 0-RTT Connection Resumption', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Lets returning visitors skip a round-trip of the TLS handshake for a faster reconnect.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_ip_geolocation_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_ip_geolocation', $options['cf_ip_geolocation'] ?? 'on', __( 'Enable IP Geolocation', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Passes each visitor\'s country to your origin in the CF-IPCountry header.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_tls_1_3_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_tls_1_3', $options['cf_tls_1_3'] ?? '', __( 'Enable TLS 1.3', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Lets supporting browsers negotiate the newest, fastest TLS handshake.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_browser_check_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_browser_check', $options['cf_browser_check'] ?? 'on', __( 'Enable Browser Integrity Check', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Blocks requests from browsers with malformed or outdated headers, before they reach your server.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_hotlink_protection_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_hotlink_protection', $options['cf_hotlink_protection'] ?? '', __( 'Enable Hotlink Protection', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Stops other sites from embedding your images directly, saving your bandwidth.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_cf_ipv6_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_ipv6', $options['cf_ipv6'] ?? 'on', __( 'Enable IPv6 Compatibility', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Lets visitors reach your site over IPv6, in addition to IPv4.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Remove purge option from toolbar field.
	 */
	public function sfcfc_remove_purge_toolbar() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'remove_purge_toolbar', $options['remove_purge_toolbar'] ?? '', __( 'Remove Purge Button from Admin Bar', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Hides the "Purge SuperFlare Cache" admin bar button for everyone.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_purge_roles() {
		$options  = get_option( 'sfcfc_options' );
		$selected = $options['purge_min_role'] ?? 'administrator';
		$roles    = wp_roles()->roles;
		?>
		<select class="sfcfc-select" name="sfcfc_options[purge_min_role]">
			<?php foreach ( sfcfc_get_purge_role_order() as $role_key ) : ?>
				<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $selected, $role_key ); ?>><?php echo esc_html( translate_user_role( $roles[ $role_key ]['name'] ?? $role_key ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="sfcfc-description"><?php esc_html_e( 'This role and every more senior role can purge the cache. Administrator is recommended.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Data Control field.
	 */
	public function sfcfc_data_control() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'keep_data_on_uninstall', $options['keep_data_on_uninstall'] ?? '', __( 'Keep plugin data when uninstalling', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Uninstalling deletes all plugin settings and stored data, including your Cloudflare credentials. Enable this to keep everything for next time.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_debug_logging_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'debug_logging', $options['debug_logging'] ?? '', __( 'Enable Debug/Logging Mode', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Writes detailed Cloudflare API and purge activity to the PHP error log for troubleshooting.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_enable_activity_log_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'enable_activity_log', $options['enable_activity_log'] ?? 'on', __( 'Enable Activity Log', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Records cache purges and preloads on the Dashboard\'s Activity Log.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Remote Purge URL field.
	 */
	public function sfcfc_remote_purge_url() {
		$purge   = $this->instance ? $this->instance->cachepurge : null;
		$options = get_option( 'sfcfc_options' );
		$enabled = ( $options['remote_purge_url_enabled'] ?? 'on' ) === 'on';
		$this->render_toggle_row( 'remote_purge_url_enabled', $options['remote_purge_url_enabled'] ?? 'on', __( 'Enable Purge via Cron URL', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Hit this from a real system cron, deploy script, or webhook to trigger a full purge.', 'wp-cloudflare-cache' ); ?></p>
		<p class="sfcfc-description sfcfc-reveal-on-toggle<?php echo $enabled ? '' : ' sfcfc-hidden-row'; ?>" data-reveal-toggle="remote_purge_url_enabled">
			<input type="text" class="sfcfc-input" id="sfcfc-purge-url" readonly value="<?php echo esc_url( $purge ? $purge->build_purge_url() : '' ); ?>">
			<a id="sfcfc-regenerate-key" class="sfcfc-btn sfcfc-btn-secondary"><?php esc_html_e( 'Regenerate Key', 'wp-cloudflare-cache' ); ?></a>
		</p>
		<p class="sfcfc-description sfcfc-reveal-on-toggle-off<?php echo $enabled ? ' sfcfc-hidden-row' : ''; ?>" data-reveal-toggle="remote_purge_url_enabled"><?php esc_html_e( 'Turned off — the cron purge URL will not work even with a valid key until this is enabled.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function sfcfc_reset_settings_field() {
		?>
		<a id="sfcfc-reset-settings" class="sfcfc-btn sfcfc-btn-danger"><span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Reset Settings', 'wp-cloudflare-cache' ); ?></a>
		<p class="sfcfc-description"><?php esc_html_e( 'Start over with every cache, purge, and advanced preference back at its default.', 'wp-cloudflare-cache' ); ?></p>
		<div class="sfcfc-notice sfcfc-notice-warning">
			<span class="sfcfc-notice-icon dashicons dashicons-warning"></span>
			<div class="sfcfc-notice-body">
				<p class="sfcfc-notice-text"><?php esc_html_e( 'This cannot be undone. Your Cloudflare credentials and connected domain are kept.', 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the Always Online field. Same title-left/toggle-row-right layout as the Performance
	 * fields below (Rocket Loader, Early Hints, Crawler Hints) — a real settings-table row via
	 * add_settings_field()'s own title column, not its own boxed card.
	 */
	public function sfcfc_cf_always_online_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_always_online', $options['cf_always_online'] ?? '', __( 'Enable Always Online', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'If your server goes down, Cloudflare serves visitors a cached, read-only version of your site from its archive.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Renders the Development Mode field.
	 */
	public function sfcfc_cf_development_mode_field() {
		$options = get_option( 'sfcfc_options' );
		$this->render_toggle_row( 'cf_development_mode', $options['cf_development_mode'] ?? '', __( 'Enable Development Mode', 'wp-cloudflare-cache' ) );
		?>
		<p class="sfcfc-description"><?php esc_html_e( 'Temporarily bypass Cloudflare cache allowing you to see changes to your origin server in realtime. Cloudflare automatically turns this back off after 3 hours.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	/**
	 * Live snapshot for the sidebar's Cache Status widget.
	 *
	 * @return array<int, array{label:string,value:string,state:string}>
	 */
	public function sfcfc_get_cache_status_items() {
		$cache   = $this->instance;
		$purge   = $cache ? $cache->cachepurge : null;
		$options = get_option( 'sfcfc_options', array() );

		$has_credentials = $purge && $purge->has_credentials();
		$zone_id         = $cache ? $cache->get_single_config( 'cf_zone_id', '' ) : '';
		$active_domain   = $purge ? ( $purge->get_cached_zones()[ $zone_id ] ?? '' ) : '';
		$cache_enabled   = 'on' === ( $options['cf_cache_enabled'] ?? 'on' );

		/**
		 * Read-only: reflects the last live DNS-proxied check (run on Finish Setup or the async
		 * General-tab check), never a fresh API call here —
		 * this widget renders on every admin page load and can't afford to wait on Cloudflare.
		 * Unset/unknown is treated as "not blocking" so a site that's never been checked yet doesn't
		 * show a false failure.
		 */
		$dns_proxied  = $cache ? $cache->get_single_config( 'cf_dns_proxied', '' ) : '';
		$dns_blocked  = '0' === $dns_proxied;
		$rule_active  = $has_credentials && $zone_id && $cache_enabled && ! $dns_blocked;
		$rule_value   = __( 'Not Set', 'wp-cloudflare-cache' );

		$blocked_by_proxy = $dns_blocked && $has_credentials && $zone_id && $cache_enabled;

		if ( $rule_active ) {
			$rule_value = __( 'Active', 'wp-cloudflare-cache' );
		} elseif ( $blocked_by_proxy ) {
			$rule_value = __( 'Blocked — DNS not proxied', 'wp-cloudflare-cache' );
		}

		/**
		 * The caching toggle itself may still be stored "on" from before the domain's proxy got turned
		 * off in Cloudflare (the plugin doesn't silently flip it back) — showing "Enabled" here would be
		 * exactly the false claim this whole check exists to prevent, so it reads "Blocked" too.
		 */
		$full_page_value = __( 'Disabled', 'wp-cloudflare-cache' );
		$full_page_state = 'neutral';

		if ( $cache_enabled ) {
			if ( $blocked_by_proxy ) {
				$full_page_value = __( 'Blocked — DNS not proxied', 'wp-cloudflare-cache' );
				$full_page_state = 'bad';
			} else {
				$full_page_value = __( 'Enabled', 'wp-cloudflare-cache' );
				$full_page_state = 'good';
			}
		}

		return array(
			array( 'label' => __( 'Cloudflare Connection', 'wp-cloudflare-cache' ), 'value' => $has_credentials ? __( 'Connected', 'wp-cloudflare-cache' ) : __( 'Not Connected', 'wp-cloudflare-cache' ), 'state' => $has_credentials ? 'good' : 'bad' ),
			array( 'label' => __( 'Active Zone', 'wp-cloudflare-cache' ), 'value' => $active_domain ? $active_domain : __( 'None', 'wp-cloudflare-cache' ), 'state' => $active_domain ? 'good' : 'neutral' ),
			array( 'label' => __( 'Full-Page Cache', 'wp-cloudflare-cache' ), 'value' => $full_page_value, 'state' => $full_page_state ),
			array( 'label' => __( 'Cache Rule', 'wp-cloudflare-cache' ), 'value' => $rule_value, 'state' => $rule_active ? 'good' : 'bad' ),
		);
	}

	public function get_plugin_name(){
		return apply_filters( 'sfcfc/settings/get_plugin_name', $this->plugin_name );
	}
	
}
