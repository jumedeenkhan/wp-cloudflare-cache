<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CFCA_Settings
 *
 * Renders and saves the plugin's admin settings page.
 *
 * @package		CloudflareCache
 * @subpackage	Classes/CFCA_Settings
 * @author		Jumedeen Khan
 * @since		1.2
 */
class CFCA_Settings {

	private $plugin_name;
	private $instance;

	function __construct( $instance = null ){
		$this->init();
		$this->plugin_name = CFCA_NAME;
		$this->instance     = $instance;
	}
	
	private function init(){
		
        add_action( 'admin_menu', array( $this, 'cfca_add_options_page' ) );
        add_action( 'admin_init', array( $this, 'cfca_register_settings' ) );
        add_filter( 'plugin_action_links_' . CFCA_PLUGIN_BASE, array( $this, 'cfca_links' ) );
		add_action( 'admin_bar_menu', array( $this, 'cfca_admin_bar_menu_button'), 100 );
		add_action( 'wp_ajax_cfca_reset_settings', array( $this, 'cfca_reset_settings' ) );
		add_action( 'wp_ajax_cfca_save_settings', array( $this, 'cfca_save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cfca_enqueue_admin_assets' ) );	
				   
	}
	
    public function cfca_add_options_page() {
        add_options_page(
			'WP Cloudflare Cache',
			'Cloudflare Cache',
			'manage_options',
			'cfca-settings',
			array( $this, 'cfca_settings_page' )
		);
    }
	
    public function cfca_register_settings() {
		
		register_setting(
			'cfca_settings',
			'cfca_options',
			array( $this, 'sanitize_cfca_options' )
		);

		add_settings_section( 'cfca_section_auth', 'Cloudflare Authentication', array( $this, 'cfca_section_auth_info' ), 'cfca-admin' );
		add_settings_section( 'cfca_section_cache_control', 'Cache Control', array( $this, 'cfca_section_cache_control_info' ), 'cfca-admin' );
		add_settings_section( 'cfca_section_cache', 'Cloudflare Cache', array( $this, 'cfca_section_info' ), 'cfca-admin' );
		add_settings_section( 'cfca_section_actions', 'Quick Actions', array( $this, 'cfca_section_info' ), 'cfca-admin' );

		$auth_method   = get_option( 'cfca_options' )['cf_auth_method'] ?? 'token';
		$key_row_class   = 'cfca-auth-key-row' . ( 'token' === $auth_method ? ' cfca-hidden-row' : '' );
		$token_row_class = 'cfca-auth-token-row' . ( 'token' === $auth_method ? '' : ' cfca-hidden-row' );

		add_settings_field( 'cfca_cf_auth_method', 'Authentication Method', array( $this, 'cfca_cloudflare_auth_method' ), 'cfca-admin', 'cfca_section_auth' );
		add_settings_field( 'cfca_cf_email_value', 'E-mail Address', array( $this, 'cfca_cloudflare_email_value' ), 'cfca-admin', 'cfca_section_auth', array( 'class' => $key_row_class ) );
		add_settings_field( 'cfca_cf_key_value', 'Global API Key', array( $this, 'cfca_cloudflare_key_value' ), 'cfca-admin', 'cfca_section_auth', array( 'class' => $key_row_class ) );
		add_settings_field( 'cfca_cf_token_value', 'API Token', array( $this, 'cfca_cloudflare_token_value' ), 'cfca-admin', 'cfca_section_auth', array( 'class' => $token_row_class ) );
		add_settings_field( 'cfca_zone_domain', 'Cloudflare Domain', array( $this, 'cfca_cloudflare_zone_domain' ), 'cfca-admin', 'cfca_section_auth' );

		add_settings_field( 'cfca_cf_maxage_value', 'Edge Cache-Control max-age', array( $this, 'cfca_cloudflare_maxage_value' ), 'cfca-admin', 'cfca_section_cache_control' );
		add_settings_field( 'cfca_cf_browser_maxage_value', 'Browser Cache max-age', array( $this, 'cfca_cloudflare_browser_maxage_value' ), 'cfca-admin', 'cfca_section_cache_control' );

		add_settings_field( 'cfca_cf_browser_ttl', 'Cloudflare Browser Cache TTL', array( $this, 'cfca_cloudflare_browser_ttl' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_cf_edge_ttl', 'Cloudflare Edge Cache TTL', array( $this, 'cfca_cloudflare_edge_ttl' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_cf_purge_purl_cache', 'Custom Purge URL', array( $this, 'cfca_cloudflare_purge_url_cache' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_cache_exclude_urls', 'Cache Exclude URLs', array( $this, 'cfca_cloudflare_exclude_urls' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_purge_homepage', 'Purge Homepage on post or page update', array( $this, 'cfca_purge_homepage_on_update' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_purge_on_comment', 'Purge On Comments', array( $this, 'cfca_purge_on_comments' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_bypass_sitemap', 'Bypass Sitemap', array( $this, 'cfca_bypass_sitemap' ), 'cfca-admin', 'cfca_section_cache' );
		add_settings_field( 'cfca_bypass_robots', 'Bypass robots.txt', array( $this, 'cfca_bypass_robots' ), 'cfca-admin', 'cfca_section_cache' );

		add_settings_field( 'cfca_cf_purge_everything', 'Purge Entire Cache', array( $this, 'cfca_cloudflare_purge_everything' ), 'cfca-admin', 'cfca_section_actions' );
		add_settings_field( 'cfca_test_purge_config', 'Test Your Config', array( $this, 'cfca_cloudflare_test_config' ), 'cfca-admin', 'cfca_section_actions' );
		add_settings_field( 'cfca_reset_settings', 'Reset Settings', array( $this, 'cfca_cloudflare_reset_settings' ), 'cfca-admin', 'cfca_section_actions' );
    }
	
    public function cfca_links( $links ) {

            $settings_link = array(
                '<a href="' . admin_url( 'options-general.php?page=cfca-settings' ) . '">'.__( 'Settings', 'wp-cloudflare-cache' ).'</a>',
            );

            return array_merge( $settings_link, $links );
    }
	
    public function cfca_settings_page() {
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'wp-cloudflare-cache' ) );
		}
		
       ?>

       <div class="cfca-wrap">
		   <div id="cfca-toast" class="cfca-toast" role="status" aria-live="polite"></div>
		   <div class="cfca-header">
			   <span class="dashicons dashicons-cloud"></span>
			   <div>
				   <h2 class="cfca-header-heading">WP Cloudflare Cache</h2>
				   <p class="cfca-description"><?php esc_html_e( 'Serve your site from Cloudflare\'s edge, purged automatically on every update.', 'wp-cloudflare-cache' ); ?></p>
			   </div>
		   </div>
				<div class="cfca-nav-tab-wrapper">
					<a class="cfca-nav-tab" href="#cfca-tab-settings">Cloudflare Settings</a>
					<a class="cfca-nav-tab" href="#cfca-tab-guide">Installation Guide</a>
				</div>
		   <div class="cfca-general cfca-tab-panel" id="cfca-tab-settings">
			   <form method="post" action="options.php" id="cfca-settings-form">
				   <div class="inside">
					   <?php
			                settings_fields( 'cfca_settings' );
					        do_settings_sections( 'cfca-admin' );
			            ?>
				   <button type="submit" id="cfca-save-settings" class="cfca-btn cfca-btn-primary">Update settings</button>
				   </div>
			   </form>
		   </div>
			<div class="cfca-support cfca-tab-panel" id="cfca-tab-guide">
				<p class="cfca-guide-intro"><strong>You need to do some setup. Don't worry — it only takes a minute.</strong></p>
				<h3 class="cfca-guide-heading">Follow these simple steps:</h3>
			   <?php $cfca_cf_tokens_url = 'https://dash.cloudflare.com/profile/api-tokens'; ?>
			   <div class="cfca-steps">
				   <div class="cfca-step">
					   <span class="cfca-step-number">1</span>
					   <div class="cfca-step-body">
						   <h4>Open your Cloudflare API Tokens page</h4>
						   <p>Log in to Cloudflare and go to your <a href="<?php echo esc_url( $cfca_cf_tokens_url ); ?>" target="_blank">API Tokens page</a>.</p>
					   </div>
				   </div>
				   <div class="cfca-step">
					   <span class="cfca-step-number">2</span>
					   <div class="cfca-step-body">
						   <h4>Create a token with the right permissions</h4>
						   <p>Click on <strong>Create Token</strong>, then grant these edit permissions for your site's domain:</p>
						   <div class="cfca-scope-list">
							   <span class="cfca-scope">Zone &gt; Cache Purge</span>
							   <span class="cfca-scope">Zone &gt; Cache Rules</span>
							   <span class="cfca-scope">Zone &gt; Zone Settings</span>
						   </div>
						   <br>
						   <p>And copy the generated token.</p>
					   </div>
				   </div>
				   <div class="cfca-step">
					   <span class="cfca-step-number">3</span>
					   <div class="cfca-step-body">
						   <h4>Connect it to your site</h4>
						   <p>Now paste token into <strong>API Token</strong> field, and choose your <strong>Domain Name</strong>.</p>
					   </div>
				   </div>
				   <div class="cfca-step">
					   <span class="cfca-step-number">4</span>
					   <div class="cfca-step-body">
						   <h4>Save Settings</h4>
						   <p>Now click on <strong>Update settings</strong>.</p>
					   </div>
				   </div>
			   </div>
				<p class="cfca-guide-success"><strong>That's it.</strong> Cloudflare caching starts working automatically.</p>
					<div class="more-help">
						<h3><strong>Need more help?</strong></h3>
						<strong>Submit your Support Request</strong>
						<p>Please click on the button to visit the WordPress.org forum and to submit your support request. </p>
						<p><a href="https://wordpress.org/support/plugin/wp-cloudflare-cache/" target="_blank" class="cfca-btn cfca-btn-primary">Open WordPress.org Support Forum</a></p>
						<hr>
						<h4>Share your Appreciation</h4>
						<p>Please consider sharing your experience by leaving a review. It helps us to continue our efforts to improve this plugin.</p>
						<a target="_blank" href="https://wordpress.org/support/plugin/wp-cloudflare-cache/reviews/#new-post" class="cfca-review-btn">
							<span class="cfca-review-btn-label">Submit a honest review</span>
							<span class="cfca-review-stars" aria-hidden="true">
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
							</span>
						</a>
						<br>
						<br>
						<hr>
						<p>Learn more with our detailed guide, visit on our <a target="_blank" href="https://www.mozedia.com/">Official Website</a>.</p>
					</div>
		   </div>
</div>
<?php
    }
	
	public function cfca_section_auth_info() {
		?>
		<p class="cfca-description"><?php esc_html_e( 'Use either your Global API Key or a scoped API Token.', 'wp-cloudflare-cache' ); ?></p>
		<?php
		$this->cfca_connection_status_html();
	}

	// Renders only #cfca-connection-status, so the AJAX save handler can refresh it without duplicating the static description above.
	public function cfca_connection_status_html( $zone_id_override = null ) {
		$zone_id         = null !== $zone_id_override ? $zone_id_override : ( $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '' );
		$has_credentials = $this->instance && $this->instance->cachepurge && $this->instance->cachepurge->has_credentials();
		?>
		<div id="cfca-connection-status">
		<?php if ( $has_credentials && $zone_id ) : ?>
			<p class="cfca-status cfca-status-connected"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Connected to Cloudflare.', 'wp-cloudflare-cache' ); ?></p>
			<?php $this->cfca_dns_proxy_notice( $zone_id ); ?>
		<?php elseif ( $has_credentials ) : ?>
			<p class="cfca-status cfca-status-pending"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Not connected yet. Please select your domain below.', 'wp-cloudflare-cache' ); ?></p>
		<?php else : ?>
			<p class="cfca-status cfca-status-pending"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Please enter your Cloudflare API credentials below.', 'wp-cloudflare-cache' ); ?></p>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Warns when this site's hostname isn't proxied through Cloudflare (orange cloud), since caching can't
	 * work at all otherwise. Skipped on localhost, where there's no real DNS record to check.
	 * @param string $zone_id
	 */
	private function cfca_dns_proxy_notice( $zone_id ) {
		if ( ! $this->instance || $this->instance->is_localhost() || ! $this->instance->cachepurge ) {
			return;
		}

		$hostname = wp_parse_url( home_url(), PHP_URL_HOST );
		$proxied  = $this->instance->cachepurge->is_dns_proxied( $zone_id, $hostname );

		if ( false === $proxied ) {
			?>
			<p class="cfca-status cfca-status-pending">
				<span class="dashicons dashicons-warning"></span>
				<?php
				printf(
					/* translators: %s: site hostname */
					esc_html__( '%s is not proxied by Cloudflare. Please enable this (orange cloud) via your Cloudflare dashboard.', 'wp-cloudflare-cache' ),
					'<strong>' . esc_html( $hostname ) . '</strong>'
				);
				?>
			</p>
			<?php
		}
	}

	public function cfca_cloudflare_auth_method() {
		$options = get_option( 'cfca_options' );
		$method  = $options['cf_auth_method'] ?? 'token';
		?>
		<select name="cfca_options[cf_auth_method]" id="cfca-cf-auth-method" class="cfca-auth-toggle">
			<option value="token" <?php selected( $method, 'token' ); ?>><?php esc_html_e( 'API Token (recommended)', 'wp-cloudflare-cache' ); ?></option>
			<option value="key" <?php selected( $method, 'key' ); ?>><?php esc_html_e( 'Global API Key', 'wp-cloudflare-cache' ); ?></option>
		</select>
		<?php
	}

	public function cfca_cloudflare_token_value() {
		$options = get_option( 'cfca_options' ); ?>
		<div class="wp-pwd">
			<input type="password" name="cfca_options[cf_api_token]" id="cfca-cf-api-token" class="cfca-input cfca-input-mono" value="<?php echo esc_attr( $options['cf_api_token'] ?? '' ); ?>">
			<button type="button" class="cfca-btn cfca-btn-icon pwd-toggle hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Show password', 'wp-cloudflare-cache' ); ?>">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<span class="text screen-reader-text"><?php esc_html_e( 'Show', 'wp-cloudflare-cache' ); ?></span>
			</button>
		</div>
        <p class="cfca-description">
			<?php esc_html_e( 'Create a token with Zone. See ', 'wp-cloudflare-cache' ); ?>

			<a href="#cfca-tab-guide">
				<?php esc_html_e( 'Installation Guide', 'wp-cloudflare-cache' ); ?>
			</a>
		</p>
		<?php
	}

	public function cfca_cloudflare_exclude_urls() {
		$options = get_option( 'cfca_options' ); ?>
		<textarea rows="4" cols="80" class="cfca-purge-url" name="cfca_options[cache_exclude_urls]" id="cfca-cache-exclude-urls"><?php echo esc_textarea( $options['cache_exclude_urls'] ?? '' ); ?></textarea>
		<p class="cfca-description"><?php esc_html_e( 'Never cache these URL paths (one per line), e.g. /cart/ or /checkout/', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function cfca_cloudflare_zone_domain() {
		$saved_zone_id = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';
		$expected      = $this->instance ? $this->instance->get_only_domain() : '';
		$zones         = ( $this->instance && $this->instance->cachepurge ) ? $this->instance->cachepurge->get_cached_zones() : array();
		?>
		<div id="cfca-zone-domain-field">
		<?php if ( empty( $zones ) ) : ?>
			<select disabled>
				<option><?php esc_html_e( 'Save your credentials above, then choose domains.', 'wp-cloudflare-cache' ); ?></option>
			</select>
		<?php else : ?>
			<select name="cfca_options[zone_domain]" id="cfca-zone-domain">
				<option value=""><?php esc_html_e( '— Select the domain name —', 'wp-cloudflare-cache' ); ?></option>
				<?php foreach ( $zones as $id => $name ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_zone_id, $id ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="cfca-description">
				<?php esc_html_e( 'Select the domain matching this site.', 'wp-cloudflare-cache' ); ?>
			</p>
		<?php endif; ?>
		</div>
		<?php
	}

	public function cfca_bypass_sitemap() {
		$options = get_option( 'cfca_options' ); ?>
		<label for="cfca-bypass-sitemap">
			<input type="checkbox" id="cfca-bypass-sitemap" name="cfca_options[bypass_sitemap]" value="on" <?php checked( $options['bypass_sitemap'] ?? 'on', 'on' ); ?>>
			<?php esc_html_e( 'Never cache sitemap.xml / sitemap_index.xml.', 'wp-cloudflare-cache' ); ?>
		</label>
		<?php
	}

	public function cfca_bypass_robots() {
		$options = get_option( 'cfca_options' ); ?>
		<label for="cfca-bypass-robots">
			<input type="checkbox" id="cfca-bypass-robots" name="cfca_options[bypass_robots]" value="on" <?php checked( $options['bypass_robots'] ?? 'on', 'on' ); ?>>
			<?php esc_html_e( 'Never cache robots.txt.', 'wp-cloudflare-cache' ); ?>
		</label>
		<?php
	}

	public function cfca_cloudflare_email_value() {
		$options = get_option('cfca_options'); ?>
         <input class="cfca-input cfca-input-mono" type="text" id="cfca-cf-email" name="cfca_options[cf_email]" value="<?php echo esc_attr( $options['cf_email'] ?? '' ); ?>">
     <?php
	}
	
	public function cfca_cloudflare_key_value() {
		$options = get_option('cfca_options'); ?>
		<div class="wp-pwd">
			<input type="password" name="cfca_options[cf_api_key]" id="cfca-cf-api-key" class="cfca-input cfca-input-mono" value="<?php echo esc_attr( $options['cf_api_key'] ?? '' ); ?>">
			<button type="button" class="cfca-btn cfca-btn-icon pwd-toggle hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Show password', 'wp-cloudflare-cache' ); ?>">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<span class="text screen-reader-text"><?php esc_html_e( 'Show', 'wp-cloudflare-cache' ); ?></span>
			</button>
		</div>
    <?php
	}
	
	public function cfca_cloudflare_maxage_value() {
		$options = get_option('cfca_options'); ?>
                <input class="cfca-input cfca-input-mono" type="number" id="cfca-expire" name="cfca_options[cf_maxage]" min="300" value="<?php echo esc_attr( $options['cf_maxage'] ?? '604800' ); ?>" required>
                <p class="cfca-description"><?php esc_html_e('Sent as s-maxage — how long Cloudflare\'s edge keeps the page. In seconds, e.g. 7 days = 604800.', 'wp-cloudflare-cache'); ?></p>
    <?php
	}

	public function cfca_cloudflare_browser_maxage_value() {
		$options = get_option('cfca_options'); ?>
                <input class="cfca-input cfca-input-mono" type="number" id="cfca-browser-expire" name="cfca_options[cf_browser_maxage]" min="0" value="<?php echo esc_attr( $options['cf_browser_maxage'] ?? '650' ); ?>">
                <p class="cfca-description"><?php esc_html_e('Sent as max-age — how long the visitor\'s own browser keeps the page. In seconds.', 'wp-cloudflare-cache'); ?></p>
    <?php
	}

	public function cfca_cloudflare_browser_ttl() {
		$options = get_option('cfca_options');
		$value   = $options['cf_browser_ttl'] ?? '300'; ?>
		<select name="cfca_options[cf_browser_ttl]" id="cfca-cf-browser-ttl">
			<?php foreach ( CFCA_Cache::get_browser_ttl_options() as $seconds => $label ) : ?>
				<option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( $value, $seconds ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="cfca-description"><?php esc_html_e( 'How long visitor browsers cache the page, enforced by Cloudflare. Recommended: 1-10 minutes.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function cfca_cloudflare_edge_ttl() {
		$options = get_option('cfca_options');
		$value   = $options['cf_edge_ttl'] ?? ''; ?>
		<select name="cfca_options[cf_edge_ttl]" id="cfca-cf-edge-ttl">
			<?php foreach ( CFCA_Cache::get_edge_ttl_options() as $seconds => $label ) : ?>
				<option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( $value, $seconds ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="cfca-description"><?php esc_html_e( 'How long Cloudflare\'s own edge cache keeps the page before checking your server again.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}
	
	public function cfca_cloudflare_purge_url_cache() {
		$options = get_option('cfca_options'); ?>
                <textarea type="textarea" rows="5" cols="80" class="cfca-purge-url" name="cfca_options[purge_urls]" id="cfca-purge-urls"><?php echo esc_textarea( $options['purge_urls'] ?? '' ); ?></textarea>
        <p class="cfca-description">Add one URL per line. URL should not contain domain name. (limit: 30)<br>Example: To purge <strong><i>http://example.com/sample-page/</i></strong> add <strong><i>/sample-page/</i></strong>.</p>
          <p style="color:green"><strong>Tips:</strong> you can use it for purge category, tags and blog page on post page update.</p>
    <?php
	}
	
	public function cfca_purge_homepage_on_update() {
		$options = get_option('cfca_options'); ?>
         <label for="cfca-purge-homepage">
			<input type="checkbox" id="cfca-purge-homepage" name="cfca_options[purge_homepage]" value="on" <?php checked( $options['purge_homepage'] ?? '', 'on' ); ?>>
			<?php esc_html_e('Purge the homepage automatically on post or page update', 'wp-cloudflare-cache'); ?>
		</label>
		<?php	
	}
	
	public function cfca_purge_on_comments() {
		$options = get_option('cfca_options'); ?>
         <label for="cfca-purge-on-comment">
			<input type="checkbox" id="cfca-purge-on-comment" name="cfca_options[purge_on_comment]" value="on" <?php checked( $options['purge_on_comment'] ?? '', 'on' ); ?>>
			<?php esc_html_e('Purge the post cache when a comment is added, approved, or deleted', 'wp-cloudflare-cache'); ?>
		</label>
		<?php	
	}
	
	public function cfca_cloudflare_purge_everything() {
		?>
         <p class="cfca-description">
           <a id="cfca-purge-everything" class="cfca-btn cfca-btn-primary">Purge All Cache</a>
         </p>
    <?php
	}
	
	public function cfca_cloudflare_test_config() {
		?>
       <p class="cfca-description">
         <a id="cfca-purge-testing" class="cfca-btn cfca-btn-primary">Purge Latest Post Cache</a>
       </p>
		<?php
	}

	/**
	 * Restores every setting to its default, except the saved Cloudflare credentials and domain selection.
	 * Going through update_option() re-runs sanitize_cfca_options(), which also re-syncs the live Cloudflare
	 * rule/TTLs to match the restored defaults.
	 */
	public function cfca_reset_settings() {
		check_ajax_referer( 'cfca_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You cannot edit posts.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		$current = get_option( 'cfca_options', array() );

		$reset = array(
			'cf_auth_method'     => $current['cf_auth_method'] ?? 'token',
			'cf_email'           => $current['cf_email'] ?? '',
			'cf_api_key'         => $current['cf_api_key'] ?? '',
			'cf_api_token'       => $current['cf_api_token'] ?? '',
			'zone_domain'        => $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '',
			'cf_maxage'          => 604800,
			'cf_browser_maxage'  => 300,
			'cf_browser_ttl'     => '',
			'cf_edge_ttl'        => 7200,
			'purge_urls'         => '',
			'cache_exclude_urls' => '',
			'purge_homepage'     => '',
			'purge_on_comment'   => '',
			'bypass_sitemap'     => 'on',
			'bypass_robots'      => 'on',
		);

		update_option( 'cfca_options', $reset );

		wp_send_json_success( array(
			'message' => __( 'Settings reset to defaults. Your Cloudflare credentials and domain were kept.', 'wp-cloudflare-cache' ),
			'type'    => 'success',
		) );
	}

	public function cfca_save_settings() {
		check_ajax_referer( 'cfca_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		// Goes through sanitize_cfca_options() directly: this AJAX action bypasses options.php, so
		// register_setting()'s sanitize callback never runs on its own — without this call, the domain
		// selection is never persisted and the Cloudflare Cache Rule/TTL sync never fires.
		$posted = isset( $_POST['cfca_options'] ) ? wp_unslash( $_POST['cfca_options'] ) : array();
		$clean  = $this->sanitize_cfca_options( $posted );
		update_option( 'cfca_options', $clean );

		// Highest-priority settings_errors() type wins: error > warning > success.
		$type    = 'success';
		$message = __( 'Settings saved.', 'wp-cloudflare-cache' );

		foreach ( get_settings_errors( 'cfca_options' ) as $error ) {
			$message = $error['message'];
			if ( 'error' === $error['type'] ) {
				$type = 'error';
				break;
			}
			if ( 'warning' === $error['type'] && 'error' !== $type ) {
				$type = 'warning';
			}
		}

		ob_start();
		$this->cfca_connection_status_html();
		$status_html = ob_get_clean();

		ob_start();
		$this->cfca_cloudflare_zone_domain();
		$domain_html = ob_get_clean();

		$payload = array(
			'message'    => $message,
			'type'       => $type,
			'statusHtml' => $status_html,
			'domainHtml' => $domain_html,
		);

		if ( 'error' === $type ) {
			wp_send_json_error( $payload );
		}

		wp_send_json_success( $payload );
	}

	public function cfca_cloudflare_reset_settings() {
		?>
       <p class="cfca-description">
         <a id="cfca-reset-settings" class="cfca-btn cfca-btn-secondary">Reset Settings</a>
       </p>
		<?php
	}
	
	public function cfca_admin_bar_menu_button( $admin_bar ) {
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			$admin_bar->add_menu([
				'id'    => 'cfca-purge-button',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">Purge CF Cache</span>',
				'href'  => '#',
			]);
		}
	}
	

	/**
	 * Sanitizes all plugin options. The selected Cloudflare domain is stored as-is — picking the right
	 * domain from the dropdown is the site owner's call, not something this plugin second-guesses.
	 * @param array $value Raw posted option values.
	 * @return array
	 */
	public function sanitize_cfca_options( $value ) {
		$clean = array();

		$clean['cf_auth_method']    = ( ( $value['cf_auth_method'] ?? 'token' ) === 'key' ) ? 'key' : 'token';
		$clean['cf_email']          = sanitize_email( $value['cf_email'] ?? '' );
		$clean['cf_api_key']        = sanitize_text_field( $value['cf_api_key'] ?? '' );
		$clean['cf_api_token']      = sanitize_text_field( $value['cf_api_token'] ?? '' );
		$clean['cf_maxage']         = absint( $value['cf_maxage'] ?? 604800 );
		$clean['cf_browser_maxage'] = absint( $value['cf_browser_maxage'] ?? 650 );
		$clean['cf_browser_ttl']    = absint( $value['cf_browser_ttl'] ?? 300 );
		$clean['cf_edge_ttl']       = absint( $value['cf_edge_ttl'] ?? '' );
		$clean['purge_urls']        = sanitize_textarea_field( $value['purge_urls'] ?? '' );
		$clean['cache_exclude_urls'] = sanitize_textarea_field( $value['cache_exclude_urls'] ?? '' );
		$clean['purge_homepage']   = ( ( $value['purge_homepage'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['purge_on_comment'] = ( ( $value['purge_on_comment'] ?? '' ) === 'on' ) ? 'on' : '';

		$clean['bypass_sitemap'] = ( ( $value['bypass_sitemap'] ?? '' ) === 'on' ) ? 'on' : '';
		$clean['bypass_robots']  = ( ( $value['bypass_robots'] ?? '' ) === 'on' ) ? 'on' : '';

		$selected_zone_id = sanitize_text_field( $value['zone_domain'] ?? '' );

		if ( $this->instance && $selected_zone_id ) {
			$this->instance->set_single_config( 'cf_zone_id', $selected_zone_id );
			$this->instance->update_config();
		}

		// $clean holds the values being saved right now — get_option( 'cfca_options' ) still returns the old,
		// pre-save data until after this callback returns, so every method below takes $clean as an explicit
		// override instead of re-reading (possibly stale) credentials from the database mid-save.
		$purge           = $this->instance ? $this->instance->cachepurge : null;
		$has_credentials = $purge && $purge->has_credentials( $clean );
		$zone_id         = $selected_zone_id ?: ( $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '' );

		if ( $has_credentials ) {
			// Refresh the cached zones list so the domain dropdown never has to call the API on page load.
			$purge->refresh_zones_cache( $clean );
			
			// Valid credentials were saved manually, so any migration notice about unverified legacy credentials no longer applies.
			delete_option( 'cfca_migration_needs_attention' );
		}

		// The "enter your credentials" / "select your domain" states are already shown inline right under the
		// Authentication section (see cfca_section_auth_info()), so only a real Cloudflare API failure needs a
		// top-of-page notice here.
		if ( $purge && $has_credentials && $zone_id ) {
			// Keep the live Cloudflare Cache Rule and TTLs in sync with these settings every time they're saved.
			$sync_result = $purge->setup_cache_rules( $zone_id, $clean );

			if ( ! is_wp_error( $sync_result ) ) {
				$sync_result = $purge->set_browser_cache_ttl( $zone_id, $clean['cf_browser_ttl'], $clean );
			}

			if ( is_wp_error( $sync_result ) ) {
				// 'warning', not the add_settings_error() default of 'error': the settings themselves saved fine,
				// only the live Cloudflare sync failed, so cfca_save_settings() must not report this as a failed save.
				add_settings_error( 'cfca_options', 'cfca_cache_rule_sync_failed', sprintf(
					/* translators: %s: error message */
					__( 'Settings saved, but Cloudflare could not be updated: %s', 'wp-cloudflare-cache' ),
					$sync_result->get_error_message()
				), 'warning' );
			}
		}

		return $clean;
	}

	public function cfca_section_cache_control_info() {
		?>
		<p class="cfca-description"><?php esc_html_e( 'The Cache-Control header this site sends with every page.', 'wp-cloudflare-cache' ); ?></p>
		<?php
	}

	public function cfca_section_info() {
	
	}
	
	/**
	 * Loads the plugin's admin CSS in the head and its JS in the footer, only on wp-admin pages
	 * that actually need it (our settings page, plus every page for the toolbar purge button).
	 * @param string $hook Current admin page hook suffix.
	 */
	public function cfca_enqueue_admin_assets( $hook ) {
		$css = CFCA_PLUGIN_DIR . 'assets/css/cfca-admin.css';
		wp_enqueue_style( $this->plugin_name, CFCA_PLUGIN_URL . 'assets/css/cfca-admin.css', array(), (string) filemtime( $css ), 'all' );
		
		if ( 'settings_page_cfca-settings' !== $hook && ! is_admin_bar_showing() ) {
			return;
		}
		
		// No dependency on core's password-toggle script: it looks up the input via id/name "pwd",
		// which our fields don't use (each has its own unique id) — cfca-admin.js handles the toggle itself.
		$js = CFCA_PLUGIN_DIR . 'assets/js/cfca-admin.js';
		wp_enqueue_script( $this->plugin_name, CFCA_PLUGIN_URL . 'assets/js/cfca-admin.js', array( 'jquery' ), (string) filemtime( $js ), true );
		wp_localize_script( $this->plugin_name, 'cfca_ajax', array(
			'nonce' => wp_create_nonce( 'cfca_ajax_nonce' ),
		) );
	}		   

	public function get_plugin_name(){
		return apply_filters( 'cfca/settings/get_plugin_name', $this->plugin_name );
	}
	
}
