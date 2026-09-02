<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SFCFC_Assets {

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'sfcfc_enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'sfcfc_enqueue_admin_assets' ) );
		add_action( 'admin_head', array( __CLASS__, 'sfcfc_admin_menu_styles' ) );
	}

	public static function sfcfc_admin_menu_styles() {
		?>
		<style>
			#toplevel_page_superflare .sfcfc-upgrade-menu-label {
				display: block;
				margin: 4px 0;
				padding: 4px 10px;
				background: #00a32a;
				color: #fff !important;
				border-radius: 3px;
			}
		</style>
		<?php
	}

	/**
	 * On the plugin's own admin pages, enqueues the full settings-page bundle. Everywhere else,
	 * enqueues only the tiny toolbar button script/style (skipped entirely if the admin bar,
	 * the button's only possible home, isn't showing) — so unrelated admin screens and front-end
	 * page loads never ship the full ~55KB settings bundle just to wire up one button.
	 *
	 * @param string $hook
	 */
	public static function sfcfc_enqueue_admin_assets( $hook = '' ) {
		$our_page_hooks = array(
			'toplevel_page_superflare',
			'superflare_page_superflare-settings',
			'superflare_page_superflare-setup-wizard',
			'superflare_page_superflare-import-export',
			'superflare_page_superflare-support',
			'superflare_page_superflare-upgrade',
		);

		if ( ! in_array( $hook, $our_page_hooks, true ) ) {
			if ( is_admin_bar_showing() ) {
				self::sfcfc_enqueue_toolbar_assets();
			}
			return;
		}

		self::sfcfc_enqueue_full_bundle( $hook );
	}

	/**
	 * The full settings-page CSS/JS bundle: only needed on the plugin's own admin pages.
	 *
	 * @param string $hook
	 */
	private static function sfcfc_enqueue_full_bundle( $hook ) {
		$css = SFCFC_PLUGIN_DIR . 'assets/css/sfcfc-admin.css';
		wp_enqueue_style( 'sfcfc-admin', SFCFC_PLUGIN_URL . 'assets/css/sfcfc-admin.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : SFCFC_VERSION, 'all' );

		$js = SFCFC_PLUGIN_DIR . 'assets/js/sfcfc-admin.js';
		wp_enqueue_script( 'sfcfc-admin', SFCFC_PLUGIN_URL . 'assets/js/sfcfc-admin.js', array( 'jquery' ), file_exists( $js ) ? (string) filemtime( $js ) : SFCFC_VERSION, true );
		self::sfcfc_localize( 'sfcfc-admin' );

		if ( 'toplevel_page_superflare' === $hook ) {
			$chart_js = SFCFC_PLUGIN_DIR . 'vendor/chartjs/chart.umd.min.js';
			wp_enqueue_script( 'sfcfc-chartjs', SFCFC_PLUGIN_URL . 'vendor/chartjs/chart.umd.min.js', array(), file_exists( $chart_js ) ? (string) filemtime( $chart_js ) : SFCFC_VERSION, true );

			$analytics_js = SFCFC_PLUGIN_DIR . 'assets/js/sfcfc-analytics.js';
			wp_enqueue_script( 'sfcfc-analytics', SFCFC_PLUGIN_URL . 'assets/js/sfcfc-analytics.js', array( 'jquery', 'sfcfc-chartjs' ), file_exists( $analytics_js ) ? (string) filemtime( $analytics_js ) : SFCFC_VERSION, true );
		}
	}

	/**
	 * The admin bar "Purge SuperFlare Cache" button's own tiny script/style, loaded wherever
	 * the admin bar shows (any wp-admin screen, and the front end for logged-in users) since
	 * that button doesn't live only on the plugin's own pages.
	 */
	private static function sfcfc_enqueue_toolbar_assets() {
		$css = SFCFC_PLUGIN_DIR . 'assets/css/sfcfc-toolbar.css';
		wp_enqueue_style( 'sfcfc-toolbar', SFCFC_PLUGIN_URL . 'assets/css/sfcfc-toolbar.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : SFCFC_VERSION, 'all' );

		$js = SFCFC_PLUGIN_DIR . 'assets/js/sfcfc-toolbar.js';
		wp_enqueue_script( 'sfcfc-toolbar', SFCFC_PLUGIN_URL . 'assets/js/sfcfc-toolbar.js', array( 'jquery' ), file_exists( $js ) ? (string) filemtime( $js ) : SFCFC_VERSION, true );
		self::sfcfc_localize( 'sfcfc-toolbar' );
	}

	/**
	 * @param string $handle Script handle to attach the sfcfc_ajax object to.
	 */
	private static function sfcfc_localize( $handle ) {
		wp_localize_script( $handle, 'sfcfc_ajax', array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'sfcfc_ajax_nonce' ),
			'settingsUrl' => admin_url( 'admin.php?page=superflare-settings' ),
		) );
	}
}
