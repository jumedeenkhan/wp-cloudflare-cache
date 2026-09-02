<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CFCA_Migration
 *
 * One-time carry-over from the pre-1.5 plugin (option names wpcc_options/wpcc_config, Global API Key only,
 * Cloudflare Page Rules) into the current cfca_ plugin. Also removes the old "Cache Everything" Page Rule,
 * since this version uses a Cache Rule instead. Runs silently when it succeeds; the only user-facing sign
 * is a warning notice, shown only if the carried-over credentials couldn't be verified against Cloudflare.
 * Future one-time upgrades can follow the same maybe_migrate() pattern.
 *
 * @package CloudflareCache
 */
class CFCA_Migration {

	const NEEDS_ATTENTION_OPTION = 'cfca_migration_needs_attention';

	/**
	 * Registers the (conditional) admin notice. Safe to call on every request.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render_notice' ) );
	}

	/**
	 * Runs once: only when the old plugin's options exist and the new ones don't yet.
	 * @param CFCA_Cache $instance
	 */
	public static function maybe_migrate( $instance ) {
		if ( false !== get_option( 'cfca_options', false ) || false === get_option( 'wpcc_options', false ) ) {
			return;
		}

		self::migrate_options( $instance );
		self::cleanup_legacy_page_rule( $instance );
	}

	/**
	 * Carries over email, Global API Key, and the settings that map cleanly to this version, sanitized the
	 * same way a manual save would be (the old plugin never sanitized its stored values at all).
	 */
	private static function migrate_options( $instance ) {
		$old = get_option( 'wpcc_options', array() );

		$migrated = array(
			'cf_auth_method'     => 'key', // the old plugin only ever supported the Global API Key.
			'cf_email'           => sanitize_email( $old['cf_email'] ?? '' ),
			'cf_api_key'         => sanitize_text_field( $old['cf_api_key'] ?? '' ),
			'cf_api_token'       => '',
			'cf_maxage'          => absint( $old['cf_maxage'] ?? 604800 ),
			'cf_browser_maxage'  => 650,
			'cf_browser_ttl'     => 300,
			'cf_edge_ttl'        => '',
			'purge_urls'         => sanitize_textarea_field( $old['purge_urls'] ?? '' ),
			'cache_exclude_urls' => '',
			'purge_homepage'     => ( 'on' === ( $old['purge_homepage'] ?? '' ) ) ? 'on' : '',
			'purge_on_comment'   => ( 'on' === ( $old['purge_on_comment'] ?? '' ) ) ? 'on' : '',
			'bypass_sitemap'     => 'on',
			'bypass_robots'      => 'on',
		);

		update_option( 'cfca_options', $migrated );

		$old_config = get_option( 'wpcc_config', array() );

		if ( ! empty( $old_config['cf_zone_id'] ) ) {
			$instance->set_single_config( 'cf_zone_id', $old_config['cf_zone_id'] );
			$instance->update_config();
		}
	}

	/**
	 * Best-effort: verifies the migrated credentials by fetching the zones list (also warms the Settings
	 * page's domain dropdown), then deletes the old Page Rule if one was recorded. A Global API Key has
	 * full account access, so this only fails when the key itself is no longer valid — in which case we
	 * leave the old Page Rule alone and flag the site for the admin notice instead.
	 */
	private static function cleanup_legacy_page_rule( $instance ) {
		$purge = $instance->cachepurge;
		$zones = $purge->refresh_zones_cache();

		if ( empty( $zones ) ) {
			update_option( self::NEEDS_ATTENTION_OPTION, 1 );
			return;
		}

		$old_config   = get_option( 'wpcc_config', array() );
		$page_rule_id = $old_config['cf_page_rule_id'] ?? '';

		if ( '' === $page_rule_id ) {
			return;
		}

		$zone_id = $instance->get_single_config( 'cf_zone_id', '' );

		if ( empty( $zone_id ) ) {
			$zone_id = array_search( $instance->get_only_domain(), $zones, true );
		}

		if ( $zone_id ) {
			wp_remote_request(
				"https://api.cloudflare.com/client/v4/zones/$zone_id/pagerules/$page_rule_id",
				array(
					'method'  => 'DELETE',
					'headers' => array(
						'X-Auth-Email' => $instance->get( 'cf_email' ),
						'X-Auth-Key'   => $instance->get( 'cf_api_key' ),
						'Content-Type' => 'application/json',
					),
				)
			);
		}
	}

	/**
	 * The only user-facing part of this whole process: shown if the migrated credentials couldn't be
	 * verified, until the site owner saves working ones.
	 */
	public static function maybe_render_notice() {
		if ( ! get_option( self::NEEDS_ATTENTION_OPTION ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Cloudflare Cache — Setup Needed', 'wp-cloudflare-cache' ); ?></strong><br>
				<?php esc_html_e( "We couldn't verify your saved Cloudflare credentials during the update (they may have changed or expired). Please re-enter your details in Settings — a scoped API Token is now recommended instead of the Global API Key.", 'wp-cloudflare-cache' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=cfca-settings' ) ); ?>"><?php esc_html_e( 'Go to Settings', 'wp-cloudflare-cache' ); ?></a>
			</p>
		</div>
		<?php
	}
}
