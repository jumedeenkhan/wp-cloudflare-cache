<?php
/**
 * Fires only when the plugin is deleted from the Plugins screen (not on deactivation), so it's safe to
 * remove everything this plugin ever stored. The live Cloudflare Cache Rule itself is already cleaned up
 * by the deactivation hook in includes/class-cfca-purge.php, which runs before a delete is even possible.
 *
 * @package CloudflareCache
 */

// Exit if accessed directly, or if WordPress isn't the one calling this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cfca_options' );
delete_option( 'cfca_config' );
delete_option( 'cfca_migration_needs_attention' );

/**
 * Legacy option names from before the cfca_ rename, in case this site was never reactivated since.
 */
delete_option( 'wpcc_options' );
delete_option( 'wpcc_config' );

wp_clear_scheduled_hook( 'cfca_purge_cache' );
