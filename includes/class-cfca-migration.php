<?php
/**
 * One-time carry-over from the pre-1.5 plugin's options and Page Rule into the current cfca_ plugin.
 *
 * @package CloudflareCache
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * One-time carry-over from the pre-1.5 plugin (wpcc_options/wpcc_config, Global API Key, Page Rules)
 * into the current cfca_ plugin, and cleanup of the old Page Rule now that a Cache Rule is used instead.
 */
class CFCA_Migration {

	const NEEDS_ATTENTION_OPTION = 'cfca_migration_needs_attention';

	/**
	 * Registers the admin notice shown when migrated credentials couldn't be verified.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render_notice' ) );
	}

	/**
	 * Runs once: only when the old plugin's options exist and the new ones don't yet.
	 *
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
	 * Carries over the old plugin's options into the new option names, sanitized.
	 *
	 * @param CFCA_Cache $instance
	 */
	private static function migrate_options( $instance ) {
		$old = get_option( 'wpcc_options', array() );

		$migrated = array(
			'cf_auth_method'     => 'key', // old plugin used Key only.
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
	 * Verifies the migrated credentials via the zones list, then deletes the old Page Rule if recorded.
	 *
	 * @param CFCA_Cache $instance
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
	 * Shown if the migrated credentials couldn't be verified, until working ones are saved.
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
