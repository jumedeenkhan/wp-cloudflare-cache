<?php
/**
 * Removes all stored plugin data when deleted from the Plugins screen.
 *
 * @package SuperFlare
 */

// Exit if accessed directly, or if WordPress isn't the one calling this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clears both current and legacy hook names.
 */
wp_clear_scheduled_hook( 'sfcfc_purge_cache' );
wp_clear_scheduled_hook( 'sfcfc_process_purge_queue' );
wp_clear_scheduled_hook( 'cfca_purge_cache' );
wp_clear_scheduled_hook( 'cfca_process_purge_queue' );

$sfcfc_options = get_option( 'sfcfc_options', array() );

if ( 'on' === ( $sfcfc_options['keep_data_on_uninstall'] ?? '' ) ) {
	return;
}

delete_option( 'sfcfc_options' );
delete_option( 'sfcfc_config' );
delete_option( 'sfcfc_migration_needs_attention' );
delete_option( 'sfcfc_purge_queue' );
delete_option( 'sfcfc_activity_log' );

/**
 * Legacy option names kept for backward compatibility.
 */
delete_option( 'cfca_options' );
delete_option( 'cfca_config' );
delete_option( 'cfca_migration_needs_attention' );
delete_option( 'cfca_purge_queue' );
delete_option( 'cfca_activity_log' );
delete_option( 'wpcc_options' );
delete_option( 'wpcc_config' );
