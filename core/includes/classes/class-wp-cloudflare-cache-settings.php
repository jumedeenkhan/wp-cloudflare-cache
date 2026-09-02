<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WP_Cloudflare_Cache_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		WPCC
 * @subpackage	Classes/WP_Cloudflare_Cache_Settings
 * @author		Jumedeen Khan
 * @since		1.2
 */
class WP_Cloudflare_Cache_Settings {

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.2
	 */
	private $plugin_name;

	/**
	 * Our Wp_Cloudflare_Cache_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.2
	 */
	function __construct(){
		$this->init();
		$this->plugin_name = WPCC_NAME;
	}
	
	/*
	 * Fire plugin actions
	 */
	private function init(){
		
        //* WPCC Hooks
        add_action( 'admin_menu', array( $this, 'wpcc_add_options_page' ) );
        add_action( 'admin_init', array( $this, 'wpcc_register_settings' ) );
        add_filter( 'plugin_action_links_' . WPCC_PLUGIN_BASE, array( $this, 'wpcc_links' ) );
		add_action( 'admin_bar_menu', array( $this, 'wpcc_admin_bar_menu_button'), 100 );
		add_action( 'admin_footer', array( $this, 'wpcc_style_script_in_footer'), PHP_INT_MAX );	
				   
	}
	
    public function wpcc_add_options_page() {
        add_options_page(
			'WP Cloudflare Cache',
			'WP Cloudflare Cache',
			'manage_options',
			'wp-cloudflare-cache',
			array( $this, 'wpcc_settings_page' )
		);
    }
	
    public function wpcc_register_settings() {
		
		// Register glossary page settings
		register_setting(
			'wpcc_settings',
			'wpcc_options',
			array( $this, 'sanitize_wpcc_options' )
		);

		add_settings_section(
			'wpcc_settings',
			'Settings',
			array( $this, 'wpcc_section_info' ),
			'wpcc-admin'
		);

		add_settings_field(
			'wpcc_cf_email_value',
			'E-mail Address',
			array( $this, 'wpcc_cloudflare_email_value' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_cf_key_value',
			'Global API Key',
			array( $this, 'wpcc_cloudflare_key_value' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_cf_maxage_value',
			'Cache-Control max-age',
			array( $this, 'wpcc_cloudflare_maxage_value' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_cf_purge_purl_cache',
			'Custom Purge URL',
			array( $this, 'wpcc_cloudflare_purge_url_cache' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_purge_homepage',
			'Purge Homepage on post or page update',
			array( $this, 'wpcc_purge_homepage_on_update' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_purge_on_comment',
			'Purge On Comments',
			array( $this, 'wpcc_purge_on_comments' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_cf_purge_everything',
			'Purge Entire Cache',
			array( $this, 'wpcc_cloudflare_purge_everything' ),
			'wpcc-admin',
			'wpcc_settings'
		);

		add_settings_field(
			'wpcc_test_purge_config',
			'Test your config',
			array( $this, 'wpcc_cloudflare_test_config' ),
			'wpcc-admin',
			'wpcc_settings'
		);
    }
	
    public function wpcc_links( $links ) {

            $settings_link = array(
                '<a href="' . admin_url( 'options-general.php?page=wp-cloudflare-cache' ) . '">'.__( 'Settings', 'wp-cloudflare-cache' ).'</a>',
            );

            return array_merge( $links, $settings_link );
    }
	
    public function wpcc_settings_page() {
		
        // check if user has permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.' ) );
		}
		
        // allow only for edit permission users
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
       ?>

       <div class="wpcc wrap">
		   <h2>WP CloudFlare Cache Settings</h2>
				<div class="nav-tab-wrapper">
					<?php if ( isset( $_GET['tab'] ) ) { $active_tab = $_GET['tab']; } else { $active_tab = 'none'; } ?>
					<a class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>" href="?page=wp-cloudflare-cache&tab=general">General</a>
					<a class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>" href="?page=wp-cloudflare-cache&tab=support">Installation Guide</a>
				</div>
			<?php if ( $active_tab == 'none' || $active_tab == 'general' ) : ?>
		   <div class="postbox wpcc-general" style="max-width:800px;padding:20px 50px">
            <p style="font-size:16px;color:green"><strong>You need to enter your Cloudflare's API key and Email. don't know? visit <a href="?page=wp-cloudflare-cache&tab=support">Installation Guide</a> section.</strong></p>
			   <form method="post" action="options.php">
				   <div class="inside">
					   <?php
			                settings_fields( 'wpcc_settings' );
					        do_settings_sections( 'wpcc-admin' );
					        submit_button('Update settings');
			            ?>
				   </div>
			   </form>
		   </div>
			<?php endif; ?>
			<?php if ( $active_tab == 'support' ) : ?>
			<div class="postbox wpcc-support" style="max-width:800px;padding:20px 50px;font-size:16px">
				<p style="font-size:16px;color:green"><strong>You need to do some setup. don't know? don't worry.</strong></p>
				<h3><strong>Follow these simple steps:</strong></h3>
			   <ol>
				   <li>First Login your <a href="https://dash.cloudflare.com/login" target="_blank">Cloudflare account</a> and click on My Profile.</li>
				   <li>Click on API tokens, scroll down and click on View Global API Key.</li>
				   <li>Enter your Cloudflare login password and copy CF API key value.</li>
				   <li>Enter both API key and e-mail address and click on Update settings</li>
			   </ol>
				<h3>Follow these 2 more steps:</h3>
				<p style="font-size:16px">Step 1: You need to create "Cache everything" page rule.</p>
				<p class="description"><a id="wpcc-create-page-rule" class="button button-primary" style="margin-bottom: 15px">Create Page Rule</a></p>
				<p style="font-size:16px">Step 2: You need to set Browser Cache TTL to Respect Existing Headers.</p>
				<p class="description"><a id="wpcc-set-browser-ttl" class="button button-primary" style="margin-bottom: 15px">Change Browser TTL</a></p>
				<p>You can also set both settings manually via your cloudflare account.<br>After all, please clear server and plugin cache, like wp super cache or w3 total cache etc. Enjoy!</p>
					<div class="more-help">
						<h3><strong>Need more help?</strong></h3>
						<strong>Submit your Support Request</strong>
						<p>Please click on the button to visit the WordPress.org forum and to submit your support request. </p>
						<p><a href="https://wordpress.org/support/plugin/wp-cloudflare-cache/" target="_blank" class="button button-primary">Open WordPress.org Support Forum</a></p>
						<hr>
						<h4>Share your Appreciation</h4>
						<p>Please consider sharing your experience by leaving a review. It helps us to continue our efforts in promoting this plugin.</p>
						<a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/wp-cloudflare-cache/">
							<div class="btn button">
								<span class="dashicons dashicons-share-alt2"></span><span>Submit a review to WordPress.org</span>
							</div>
						</a>
						<br>
						<br>
						<hr>
						<p>Learn more with our detailed guide, visit on our <a target="_blank" href="https://www.mozedia.com/cloudflare-cache-everything-for-wordpress/">WP Cloudflare Cache Setup Guide</a>.</p>
					</div>
		   </div>
			<?php endif; ?>
</div>
<?php
        }
    }
	
	public function wpcc_cloudflare_email_value() {
		$options = get_option('wpcc_options'); ?>
         <input class="regular-text code" type="text" id="email" name="wpcc_options[cf_email]" value="<?php echo ( isset($options['cf_email']) ? $options['cf_email'] : ''); ?>" required>
     <?php
	}
	
	public function wpcc_cloudflare_key_value() {
		$options = get_option('wpcc_options'); ?>
         <input class="regular-text code" type="password" id="api_key" name="wpcc_options[cf_api_key]" value="<?php echo ( isset($options['cf_api_key']) ? $options['cf_api_key'] : ''); ?>" required>
    <button id="visibility_toggle" type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" data-label="Show">
                <span class="dashicons dashicons-visibility"></span>
                <span class="text">Show</span>
            </button>
    <?php
	}
	
	public function wpcc_cloudflare_maxage_value() {
		$options = get_option('wpcc_options'); ?>
                <input class="regular-text code" type="number" id="wpcc-expire" name="wpcc_options[cf_maxage]" min="300" value="<?php echo ( isset($options['cf_maxage']) ? $options['cf_maxage'] : '604800'); ?>" required>
                <p class="description" id="tagline-description"><?php esc_html_e('Value will be in seconds. Example:- 7 days = 604800 seconds', 'wp-cloudflare-cache'); ?></p>
    <?php
	}
	
	public function wpcc_cloudflare_purge_url_cache() {
		$options = get_option('wpcc_options'); ?>
                <textarea type="textarea" rows="5" cols="80" class="wpcc-purge-url" name="wpcc_options[purge_urls]" id="purge_urls"><?php echo ( isset($options['purge_urls']) ? $options['purge_urls'] : ''); ?></textarea>
        <p class="description">Add one URL per line. URL should not contain domain name. (limit: 30)<br>Example: To purge <strong><i>http://example.com/sample-page/</i></strong> add <strong><i>/sample-page/</i></strong>.</p>
          <p style="color:green"><strong>Tips:</strong> you can use it for purge category, tags and blog page on post page update.</p>
    <?php
	}
	
	public function wpcc_purge_homepage_on_update() {
		$options = get_option('wpcc_options'); ?>
         <input class="regular-text code" type="checkbox" id="purge_homepage" name="wpcc_options[purge_homepage]" value="on" <?php echo ( isset($options['purge_homepage']) ? ' checked' : ''); ?>>
                <p class="description"><?php esc_html_e('Check it, if you want to purge homerpage cache automatically on post or page update', 'wp-cloudflare-cache'); ?></p>
		<?php	
	}
	
	public function wpcc_purge_on_comments() {
		$options = get_option('wpcc_options'); ?>
         <input class="regular-text code" type="checkbox" id="purge_on_comment" name="wpcc_options[purge_on_comment]" value="on" <?php echo ( isset($options['purge_on_comment']) ? ' checked' : ''); ?>>
                <p class="description"><?php esc_html_e('Check it, if you want to purge post cache on comment added, approved or deleted.', 'wp-cloudflare-cache'); ?></p>
		<?php	
	}
	
	public function wpcc_cloudflare_purge_everything() {
		$options = get_option('wpcc_options'); ?>
         <p class="description"><a id="wpcc_purge_everything" class="button button-primary" style="margin-bottom: 15px">Purge All Cache</a></p>
    <?php
	}
	
	public function wpcc_cloudflare_test_config() {
		?>
       <p class="description"><a id="wpcc-purge-testing" class="button button-primary" style="margin-bottom: 15px">Purge Latest Post Cache</a></p>
		<?php
	}
	
	public function wpcc_admin_bar_menu_button( $admin_bar ) {
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			global $pagenow;
			$admin_bar->add_menu([
				'id'    => 'wpcc-purge-button',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">Purge CF Cache</span>',
			]);
		}
	}
	

	// Validate and sanitize the values
	public function sanitize_wpcc_options( $value ) {
		return $value;
	}

	public function wpcc_section_info() {
	
	}
	
	public function wpcc_style_script_in_footer() {
		wp_enqueue_style( $this->plugin_name, WPCC_PLUGIN_URL . 'assets/css/wpcc-admin.css', array(), WPCC_VERSION, 'all' );
		wp_enqueue_script( $this->plugin_name, WPCC_PLUGIN_URL . 'assets/js/wpcc-admin.js', array( 'jquery' ), WPCC_VERSION, false );
		
	}		   

	/**
	 * Return the plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'WPCC/settings/get_plugin_name', $this->plugin_name );
	}
	
}
add_filter('use_block_editor_for_post', '__return_false');