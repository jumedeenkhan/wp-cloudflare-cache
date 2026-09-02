<?php
/**
 * Autoloader: maps SFCFC_* class names to their includes/class-*.php files, so plugin
 * classes load on first use instead of via manual require_once calls.
 *
 * @package SuperFlare
 */

if ( ! defined( 'ABSPATH' ) ) exit;

spl_autoload_register( function ( $class_name ) {
	if ( 0 !== strpos( $class_name, 'SFCFC_' ) ) {
		return;
	}

	$file = dirname( __DIR__ ) . '/includes/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );
