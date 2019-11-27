<?php
/*
Plugin Name:  WP Cloudflare Cache
Plugin URI:   https://www.mozedia.com/cloudflare-cache-everything/
Description:  Cache HTML page with Cloudflare CDN when used cache everything.
Version:      1.0
Author:       Jumedeen Khan
Author URI:   https://www.mozedia.com/
License:      GPLv2 or later
License URI:  http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wp-cloudflare-cache
Domain Path:  /languages
*/

//* Quit files
defined('ABSPATH') || exit;

define('WPCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCC_PLUGIN_BASENAME', plugin_basename(__FILE__));

//* Load WP Cloudflare Cache plugin class
require_once(WPCC_PLUGIN_DIR . '/lib/class-wp-cloudflare-cache.php');

// Load plugin script and style css
add_action('admin_enqueue_scripts', 'wpcc_register_scripts_styles');
function wpcc_register_scripts_styles() {
    wp_enqueue_style('toggle-script', plugins_url('/css/wpcc-style.css', __FILE__));
    wp_enqueue_script('toggle-script', plugins_url('/js/toggle-script.js', __FILE__));
}
