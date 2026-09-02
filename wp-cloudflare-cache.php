<?php
/**
 * Plugin Name:   WP Cloudflare Cache
 * Plugin URI:    https://www.mozedia.com/
 * Description:   WP Cloudflare Cache built for cache HTML Pages on Cloudflare free plan and purge cache only when post or page updated.
 * Version:       1.8.0
 * Author:        Jumedeen Khan
 * Author URI:    https://www.mozedia.com/about
 * Text Domain:   wp-cloudflare-cache
 * Domain Path:   /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CloudflareCache
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CFCA_NAME', 'WP Cloudflare Cache' );
define( 'CFCA_VERSION',		'1.8.0' );
define( 'CFCA_PLUGIN_FILE',	__FILE__ );
define( 'CFCA_PLUGIN_BASE',	plugin_basename( CFCA_PLUGIN_FILE ) );
define( 'CFCA_PLUGIN_DIR',	plugin_dir_path( CFCA_PLUGIN_FILE ) );
define( 'CFCA_PLUGIN_URL',	plugin_dir_url( CFCA_PLUGIN_FILE ) );

require_once CFCA_PLUGIN_DIR . 'includes/class-cfca-cache.php';

/**
 * Initializes the plugin.
 */
function cfca_cloudflare_cache_run() {
	$cfca_cache = new CFCA_Cache();
}

cfca_cloudflare_cache_run();
