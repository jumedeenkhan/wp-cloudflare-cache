<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SFCFC_Sanitize {

	private $instance;

	public function __construct( $instance = null ) {
		$this->instance = $instance;
	}

	public function sfcfc_sanitize_imported_settings( $value ) {
		$existing = get_option( 'sfcfc_options', array() );
		$clean    = array(
			'cf_auth_method' => $existing['cf_auth_method'] ?? 'token',
			'cf_email'       => $existing['cf_email'] ?? '',
			'cf_api_key'     => $existing['cf_api_key'] ?? '',
			'cf_api_token'   => $existing['cf_api_token'] ?? '',
		);

		$on_off_keys = array(
			'cf_cache_enabled', 'cf_always_online', 'cf_development_mode', 'cf_crawler_hints',
			'cf_early_hints', 'cf_rocket_loader', 'cf_http3', 'cf_zero_rtt',
			'cf_ip_geolocation', 'cf_tls_1_3', 'cf_browser_check', 'cf_hotlink_protection', 'cf_ipv6',
			'purge_homepage_on_update', 'purge_homepage_on_trash',
			'purge_archives_on_update', 'purge_archives_on_trash', 'purge_on_comment_approved',
			'purge_on_comment_deleted', 'purge_feeds', 'purge_amp_urls', 'auto_purge_whole',
			'bypass_sitemap', 'bypass_robots', 'bypass_ajax', 'bypass_rest_api', 'enable_preloader', 'preloader_start_on_purge',
			'preload_latest_posts', 'preload_sitemap', 'preloader_cronjob_enabled',
			'remove_purge_toolbar', 'keep_data_on_uninstall', 'purge_on_theme_plugin_update',
			'debug_logging', 'enable_activity_log', 'remote_purge_url_enabled',
		);
		foreach ( $on_off_keys as $sfcfc_key ) {
			$clean[ $sfcfc_key ] = ( ( $value[ $sfcfc_key ] ?? '' ) === 'on' ) ? 'on' : '';
		}
		$clean['auto_purge_html_only'] = ( 'on' === $clean['auto_purge_whole'] ) ? '' : 'on';

		$clean['cf_cache_level']    = in_array( $value['cf_cache_level'] ?? '', array( 'aggressive', 'basic', 'simplified' ), true ) ? $value['cf_cache_level'] : 'aggressive';
		/**
		 * 300s floor enforced here server-side (not just via the field's now-removed native `min`
		 * attribute), so a JS-disabled or forged request can never push edge caching below a sane TTL.
		 */
		$clean['cf_maxage']         = max( 300, absint( $value['cf_maxage'] ?? 604800 ) );
		$clean['cf_browser_maxage'] = absint( $value['cf_browser_maxage'] ?? 650 );
		$clean['cf_browser_ttl']    = absint( $value['cf_browser_ttl'] ?? 300 );
		$clean['cf_edge_ttl']       = absint( $value['cf_edge_ttl'] ?? 0 );

		$clean['purge_urls']            = sanitize_textarea_field( $value['purge_urls'] ?? '' );
		$clean['cache_exclude_urls']    = sanitize_textarea_field( $value['cache_exclude_urls'] ?? '' );
		$clean['cache_exclude_cookies'] = sanitize_textarea_field( $value['cache_exclude_cookies'] ?? '' );
		$clean['cache_exclude_query_params'] = sanitize_textarea_field( $value['cache_exclude_query_params'] ?? '' );
		$clean['preload_sitemap_paths'] = sanitize_textarea_field( $value['preload_sitemap_paths'] ?? '' );

		$clean['dont_cache_for'] = array();
		foreach ( array_keys( SFCFC_Cache::get_dont_cache_for_tags() ) as $sfcfc_tag_key ) {
			$clean['dont_cache_for'][ $sfcfc_tag_key ] = ( ( ( $value['dont_cache_for'] ?? array() )[ $sfcfc_tag_key ] ?? '' ) === 'on' ) ? 'on' : '';
		}

		$role_order = sfcfc_get_purge_role_order();
		$min_role   = sanitize_text_field( $value['purge_min_role'] ?? 'administrator' );
		$clean['purge_min_role'] = in_array( $min_role, $role_order, true ) ? $min_role : 'administrator';

		$cutoff = array_search( $clean['purge_min_role'], $role_order, true );
		$clean['purge_roles'] = array();
		foreach ( $role_order as $sfcfc_index => $sfcfc_role_key ) {
			$clean['purge_roles'][ $sfcfc_role_key ] = ( $sfcfc_index <= $cutoff ) ? 'on' : '';
		}

		return $clean;
	}

	public function sanitize_sfcfc_options( $value ) {
		$clean = array();

		$existing = get_option( 'sfcfc_options', array() );
		$clean['cf_auth_method']  = $existing['cf_auth_method'] ?? 'token';
		$clean['cf_email']        = $existing['cf_email'] ?? '';
		$clean['cf_api_key']      = $existing['cf_api_key'] ?? '';
		$clean['cf_api_token']    = $existing['cf_api_token'] ?? '';

		$clean['cf_cache_enabled']  = ( ( $value['cf_cache_enabled'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_always_online']    = ( ( $value['cf_always_online'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_development_mode'] = ( ( $value['cf_development_mode'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_crawler_hints']    = ( ( $value['cf_crawler_hints'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_early_hints']      = ( ( $value['cf_early_hints'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_rocket_loader']    = ( ( $value['cf_rocket_loader'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_http3']            = ( ( $value['cf_http3'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_zero_rtt']         = ( ( $value['cf_zero_rtt'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_ip_geolocation']   = ( ( $value['cf_ip_geolocation'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_tls_1_3']          = ( ( $value['cf_tls_1_3'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_browser_check']    = ( ( $value['cf_browser_check'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_hotlink_protection'] = ( ( $value['cf_hotlink_protection'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_ipv6']             = ( ( $value['cf_ipv6'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['cf_cache_level']      = in_array( $value['cf_cache_level'] ?? '', array( 'aggressive', 'basic', 'simplified' ), true ) ? $value['cf_cache_level'] : 'aggressive';
		/**
		 * 300s floor enforced here server-side (not just via the field's now-removed native `min`
		 * attribute), so a JS-disabled or forged request can never push edge caching below a sane TTL.
		 */
		$clean['cf_maxage']         = max( 300, absint( $value['cf_maxage'] ?? 604800 ) );
		$clean['cf_browser_maxage'] = absint( $value['cf_browser_maxage'] ?? 650 );
		$clean['cf_browser_ttl']    = absint( $value['cf_browser_ttl'] ?? 300 );
		$clean['cf_edge_ttl']       = absint( $value['cf_edge_ttl'] ?? '' );
		$clean['purge_urls']        = sanitize_textarea_field( $value['purge_urls'] ?? '' );
		$clean['cache_exclude_urls'] = sanitize_textarea_field( $value['cache_exclude_urls'] ?? '' );
		$clean['cache_exclude_cookies'] = sanitize_textarea_field( $value['cache_exclude_cookies'] ?? '' );
		$clean['cache_exclude_query_params'] = sanitize_textarea_field( $value['cache_exclude_query_params'] ?? '' );
		$clean['purge_homepage_on_update'] = ( ( $value['purge_homepage_on_update'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_homepage_on_trash']  = ( ( $value['purge_homepage_on_trash'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_archives_on_update'] = ( ( $value['purge_archives_on_update'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_archives_on_trash']  = ( ( $value['purge_archives_on_trash'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_on_comment_approved'] = ( ( $value['purge_on_comment_approved'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_on_comment_deleted']  = ( ( $value['purge_on_comment_deleted'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_feeds'] = ( ( $value['purge_feeds'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_amp_urls'] = ( ( $value['purge_amp_urls'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['auto_purge_whole']     = ( ( $value['auto_purge_whole'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['auto_purge_html_only'] = ( 'on' === $clean['auto_purge_whole'] ) ? '' : 'on';

		$clean['bypass_sitemap']  = ( ( $value['bypass_sitemap'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['bypass_robots']   = ( ( $value['bypass_robots'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['bypass_ajax']     = ( ( $value['bypass_ajax'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['bypass_rest_api'] = ( ( $value['bypass_rest_api'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['enable_preloader'] = ( ( $value['enable_preloader'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['preloader_start_on_purge'] = ( ( $value['preloader_start_on_purge'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['preload_latest_posts']     = ( ( $value['preload_latest_posts'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['preload_sitemap']          = ( ( $value['preload_sitemap'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['preload_sitemap_paths']    = sanitize_textarea_field( $value['preload_sitemap_paths'] ?? '' );
		$clean['preloader_cronjob_enabled'] = ( ( $value['preloader_cronjob_enabled'] ?? '' ) === 'on' ) ? 'on' : '';

		$dont_cache_for_input = (array) ( $value['dont_cache_for'] ?? array() );
		$clean['dont_cache_for'] = array();
		foreach ( array_keys( SFCFC_Cache::get_dont_cache_for_tags() ) as $tag_key ) {
			$clean['dont_cache_for'][ $tag_key ] = ( ( $dont_cache_for_input[ $tag_key ] ?? '' ) === 'on' ) ? 'on' : '';
		}

		$clean['remove_purge_toolbar'] = ( ( $value['remove_purge_toolbar'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['keep_data_on_uninstall'] = ( ( $value['keep_data_on_uninstall'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_on_theme_plugin_update'] = ( ( $value['purge_on_theme_plugin_update'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['debug_logging'] = ( ( $value['debug_logging'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['enable_activity_log'] = ( ( $value['enable_activity_log'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['remote_purge_url_enabled'] = ( ( $value['remote_purge_url_enabled'] ?? '' ) === 'on' ) ? 'on' : '';

		$role_order = sfcfc_get_purge_role_order();
		$min_role   = sanitize_text_field( $value['purge_min_role'] ?? 'administrator' );

		$clean['purge_min_role'] = in_array( $min_role, $role_order, true ) ? $min_role : 'administrator';

		$cutoff = array_search( $clean['purge_min_role'], $role_order, true );
		$clean['purge_roles'] = array();
		foreach ( $role_order as $index => $role_key ) {
			$clean['purge_roles'][ $role_key ] = ( $index <= $cutoff ) ? 'on' : '';
		}

		$purge           = $this->instance ? $this->instance->cachepurge : null;
		$has_credentials = $purge && $purge->has_credentials( $clean );
		$zone_id         = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';

		if ( $purge && $has_credentials && $zone_id ) {
			$sync_result = $purge->sync_cache_rule_state( $zone_id, $clean );

			if ( is_wp_error( $sync_result ) ) {
				add_settings_error( 'sfcfc_options', 'sfcfc_cache_rule_sync_failed', sprintf(
					/* translators: %s: error message */
					__( 'Settings saved, but Cloudflare could not be updated: %s', 'wp-cloudflare-cache' ),
					$sync_result->get_error_message()
				), 'warning' );
			}

			/**
			 * cf_crawler_hints is intentionally not pushed here: Cloudflare's generic
			 * /zones/{id}/settings/{id} endpoint rejects it as an unrecognized setting name
			 * (confirmed live), so it would fail on every save. The toggle is still saved
			 * locally; it is not mirrored to a Cloudflare zone setting.
			 */
			$zone_settings = array(
				'always_online'    => 'on' === $clean['cf_always_online'] ? 'on' : 'off',
				'development_mode' => 'on' === $clean['cf_development_mode'] ? 'on' : 'off',
				'early_hints'      => 'on' === $clean['cf_early_hints'] ? 'on' : 'off',
				'rocket_loader'    => 'on' === $clean['cf_rocket_loader'] ? 'on' : 'off',
				'http3'            => 'on' === $clean['cf_http3'] ? 'on' : 'off',
				'0rtt'             => 'on' === $clean['cf_zero_rtt'] ? 'on' : 'off',
				'ip_geolocation'   => 'on' === $clean['cf_ip_geolocation'] ? 'on' : 'off',
				'tls_1_3'          => 'on' === $clean['cf_tls_1_3'] ? 'on' : 'off',
				'browser_check'    => 'on' === $clean['cf_browser_check'] ? 'on' : 'off',
				'hotlink_protection' => 'on' === $clean['cf_hotlink_protection'] ? 'on' : 'off',
				'ipv6'             => 'on' === $clean['cf_ipv6'] ? 'on' : 'off',
				'cache_level'      => $clean['cf_cache_level'],
			);
			/**
			 * One bulk API call instead of one request per setting — this loop used to fire a
			 * dozen-plus sequential blocking Cloudflare requests on every single settings save.
			 */
			$zone_settings_result = $purge->set_zone_settings_bulk( $zone_id, $zone_settings, $clean );

			if ( ! is_wp_error( $sync_result ) && is_wp_error( $zone_settings_result ) ) {
				add_settings_error( 'sfcfc_options', 'sfcfc_zone_settings_sync_failed', sprintf(
					/* translators: %s: error message */
					__( 'Settings saved, but some Cloudflare zone settings could not be updated: %s', 'wp-cloudflare-cache' ),
					$zone_settings_result->get_error_message()
				), 'warning' );
			}
		}

		return $clean;
	}
}
