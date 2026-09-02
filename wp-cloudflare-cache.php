<?php
/**
 * Plugin Name:   Cloudflare Page Cache for WordPress
 * Plugin URI:    https://www.mozedia.com/
 * Description:   WP Cloudflare Cache built for cache HTML Pages on Cloudflare free plan and purge cache only when post or page updated.
 * Version:       1.2.1
 * Author:        Jumedeen Khan
 * Author URI:    https://www.mozedia.com/about
 * Text Domain:   wp-cloudflare-cache
 * Domain Path:   /languages
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
	
// Plugin name
define( 'WPCC_NAME', 'WP Cloudflare Cache' );

// Plugin version
define( 'WPCC_VERSION',		'1.2.1' );

// Plugin Root File
define( 'WPCC_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'WPCC_PLUGIN_BASE',	plugin_basename( WPCC_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'WPCC_PLUGIN_DIR',	plugin_dir_path( WPCC_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'WPCC_PLUGIN_URL',	plugin_dir_url( WPCC_PLUGIN_FILE ) );


/*
 * Load the main class for the core functionality
 */
require_once WPCC_PLUGIN_DIR . 'core/class-wp-cloudflare-cache.php';

/**
 * Run the main function
 */

function wpcc_cloudflare_cache_run() {
	
	$wpcc_cache = new WP_Cloudflare_Cache();
}

wpcc_cloudflare_cache_run();
