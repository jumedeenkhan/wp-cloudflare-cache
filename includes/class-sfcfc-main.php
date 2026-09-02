<?php
/**
 * Bootstraps the plugin: defines its path/URL constants and boots the core SFCFC_Cache class.
 *
 * @package SuperFlare
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Defines the plugin's path/URL constants and boots SFCFC_Cache.
 */
class SFCFC_Main {

	/**
	 * @param string $plugin_file Path to the main plugin file, passed explicitly since
	 *                            __FILE__ here would resolve to this file instead.
	 */
	public static function run( $plugin_file ) {
		define( 'SFCFC_PLUGIN_FILE', $plugin_file );
		define( 'SFCFC_PLUGIN_BASE', plugin_basename( SFCFC_PLUGIN_FILE ) );
		define( 'SFCFC_PLUGIN_DIR', plugin_dir_path( SFCFC_PLUGIN_FILE ) );
		define( 'SFCFC_PLUGIN_URL', plugin_dir_url( SFCFC_PLUGIN_FILE ) );

		new SFCFC_Cache();
	}
}
