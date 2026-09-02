<?php
/**
 * View: Sidebar content — always-visible support links, separate from the setup guide
 * (which lives in the General tab; see sfcfc-general.php).
 *
 * @package SuperFlare
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sfcfc_purge_host = wp_parse_url( home_url(), PHP_URL_HOST );
?>
<div class="sfcfc-widget">
	<div class="sfcfc-quick-actions">
		<h3><strong>Quick Actions</strong></h3>
		<p><a id="sfcfc-purge-testing" class="sfcfc-btn sfcfc-btn-secondary">Purge Latest Post</a></p>
		<p><a id="sfcfc-open-custom-purge" class="sfcfc-btn sfcfc-btn-secondary">Custom Purge</a></p>
		<p><a id="sfcfc-purge-everything" class="sfcfc-btn sfcfc-btn-primary">Purge Entire Cache</a></p>
	</div>

<div class="sfcfc-modal-backdrop" id="sfcfc-custom-purge-modal" hidden>
	<div class="sfcfc-modal sfcfc-modal-wide" role="dialog" aria-modal="true" aria-labelledby="sfcfc-custom-purge-title">
		<button type="button" class="sfcfc-modal-close" id="sfcfc-custom-purge-close" aria-label="Close">
			<svg class="sfcfc-modal-close-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
		</button>
		<h3 class="sfcfc-modal-title" id="sfcfc-custom-purge-title">Custom Purge</h3>

		<p class="sfcfc-field-label">Purge by</p>
		<div class="sfcfc-purge-type-options" role="radiogroup" aria-label="Purge by">
			<button type="button" class="sfcfc-purge-type-option is-active" data-purge-type="url" aria-pressed="true">
				<span class="sfcfc-purge-type-copy">
					<strong>URL</strong>
					<small>Purges assets in the Cloudflare cache that match the URL(s) exactly.</small>
				</span>
				<span class="sfcfc-purge-type-radio" aria-hidden="true"></span>
			</button>
			<button type="button" class="sfcfc-purge-type-option" data-purge-type="hostname" aria-pressed="false">
				<span class="sfcfc-purge-type-copy">
					<strong>Hostname</strong>
					<small>Purges every cached asset whose URL host matches one of the provided values.</small>
				</span>
				<span class="sfcfc-purge-type-radio" aria-hidden="true"></span>
			</button>
		</div>

		<div class="sfcfc-purge-type-panel" data-purge-panel="url">
			<p class="sfcfc-field-hint">You will need to specify the full path to the file. Wildcards are not supported with single URL purge. Any number of URLs is supported — they are sent to Cloudflare in batches of 30.</p>
			<p class="sfcfc-field-hint">Separate URL(s) one per line.<br><strong>Example:</strong><br><?php echo esc_html( home_url( '/' ) ); ?><br><?php echo esc_html( home_url( '/sample-page/' ) ); ?></p>
			<textarea id="sfcfc-custom-purge-urls" class="sfcfc-purge-url" rows="4" placeholder="<?php echo esc_attr( home_url( '/sample-page/' ) ); ?>"></textarea>
		</div>

		<div class="sfcfc-purge-type-panel sfcfc-hidden-row" data-purge-panel="hostname">
			<p class="sfcfc-field-hint">Any number of hostnames is supported — they are sent to Cloudflare in batches of 100.</p>
			<p class="sfcfc-field-hint">Separate hostname(s) with commas, or one per line.<br><strong>Example:</strong> <?php echo esc_html( $sfcfc_purge_host ); ?>, blog.<?php echo esc_html( $sfcfc_purge_host ); ?></p>
			<textarea id="sfcfc-custom-purge-hostnames" class="sfcfc-purge-url" rows="4" placeholder="<?php echo esc_attr( $sfcfc_purge_host ); ?>"></textarea>
		</div>

		<div class="sfcfc-modal-actions">
			<button type="button" class="sfcfc-btn sfcfc-btn-secondary" id="sfcfc-custom-purge-cancel">Cancel</button>
			<button type="button" class="sfcfc-btn sfcfc-btn-primary" id="sfcfc-custom-purge-submit">Purge</button>
		</div>
	</div>
</div>
	<div class="sfcfc-cache-status sfcfc-widget-section">
		<h3><strong>Cache Status</strong></h3>
		<ul class="sfcfc-status-list">
			<?php foreach ( $this->sfcfc_get_cache_status_items() as $sfcfc_status_item ) : ?>
				<li>
					<span class="sfcfc-status-label"><?php echo esc_html( $sfcfc_status_item['label'] ); ?></span>
					<span class="sfcfc-status-value is-<?php echo esc_attr( $sfcfc_status_item['state'] ); ?>"><?php echo esc_html( $sfcfc_status_item['value'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
<div class="sfcfc-widget sfcfc-pro-upsell">
	<div class="sfcfc-pro-header">
		<span class="sfcfc-pro-icon"><span class="dashicons dashicons-cloud"></span></span>
		<h3><?php esc_html_e( 'Unlock SuperFlare PRO', 'wp-cloudflare-cache' ); ?></h3>
	</div>
	<p><?php esc_html_e( 'Unlock advanced caching system, and deeper analytics, right here in these same settings.', 'wp-cloudflare-cache' ); ?></p>
	<ul class="sfcfc-pro-feature-list">
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span> Multi-zone &amp; multisite management</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span> Real-time cache analytics</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span>Advanced caching system</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span> Advanced purge rules</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span>Smart auto-purge cache</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span>WooCommerce &amp; EDD compatibility</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span>24/7 Standard Support</li>
		<li><span class="sfcfc-pro-feature-check"><span class="dashicons dashicons-yes"></span></span>Ignore Marketing Parameters</li>
	</ul>
	<div class="sfcfc-pro-actions">
		<a href="https://superflare.pro/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-pro"><?php esc_html_e( 'Buy Now', 'wp-cloudflare-cache' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare-upgrade' ) ); ?>" class="sfcfc-btn sfcfc-btn-pro-outline"><?php esc_html_e( 'Learn More', 'wp-cloudflare-cache' ); ?></a>
	</div>
</div>
<div class="sfcfc-widget">
	<div class="sfcfc-connect-header">
		<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-star-filled"></span></span>
		<div>
			<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Share Your Feedback', 'wp-cloudflare-cache' ); ?></h3>
			<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Please consider sharing your experience — it helps us keep improving this plugin.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>
	<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wp-cloudflare-cache/reviews/#new-post" class="sfcfc-review-btn">
		<span class="sfcfc-review-btn-label"><?php esc_html_e( 'Leave a Honest Review', 'wp-cloudflare-cache' ); ?></span>
		<span class="sfcfc-review-stars" aria-hidden="true">
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
		</span>
	</a>
</div>
