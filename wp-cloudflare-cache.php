<?php
/**
 * Plugin Name:   SuperFlare Cache
 * Plugin URI:    https://superflare.pro/
 * Description:   Speed up your WordPress website with Cloudflare full-page cache - bypass logged-in users, and admin requests.
 * Version:       2.0.0
 * Author:        Jumedeen Khan
 * Author URI:    https://superflare.pro/about
 * Text Domain:   wp-cloudflare-cache
 * Domain Path:   /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SuperFlare
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SFCFC_NAME', 'SuperFlare Cache' );
define( 'SFCFC_VERSION', '2.0.0' );

require_once __DIR__ . '/vendor/autoload.php';

SFCFC_Main::run( __FILE__ );
