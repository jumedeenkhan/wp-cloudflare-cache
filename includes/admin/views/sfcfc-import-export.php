<?php
/**
 * View: Import/Export page — download/restore settings as JSON, Reset Settings, and a summary
 * of what the export file does and doesn't contain. Included from
 * SFCFC_Settings::sfcfc_import_export_page() (class-sfcfc-settings.php), which prepares
 * $sfcfc_export_url and $sfcfc_import_result before this include.
 *
 * @package SuperFlare
 * @var string $sfcfc_export_url
 * @var string $sfcfc_import_result
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="sfcfc-wrap">
	<div id="sfcfc-toast" class="sfcfc-toast" role="status" aria-live="polite"></div>
	<div class="sfcfc-header">
		<span class="dashicons dashicons-cloud"></span>
		<div>
			<h2 class="sfcfc-header-heading">Import/Export</h2>
			<p class="sfcfc-description"><?php esc_html_e( 'Export your settings to move them to another site, or import a saved file.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>

	<?php if ( 'success' === $sfcfc_import_result ) : ?>
		<div class="sfcfc-notice sfcfc-notice-success">
			<span class="sfcfc-notice-icon dashicons dashicons-yes-alt"></span>
			<div class="sfcfc-notice-body">
				<p class="sfcfc-notice-text"><?php esc_html_e( 'Settings imported successfully. Your Cloudflare credentials and connected zone were kept.', 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
	<?php elseif ( 'error' === $sfcfc_import_result ) : ?>
		<div class="sfcfc-notice sfcfc-notice-error">
			<span class="sfcfc-notice-icon dashicons dashicons-warning"></span>
			<div class="sfcfc-notice-body">
				<p class="sfcfc-notice-text"><?php esc_html_e( "Couldn't import that file. Make sure it's a SuperFlare settings JSON export under 1MB, then try again.", 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
	<?php endif; ?>

	<div class="sfcfc-layout">
		<div class="sfcfc-content sfcfc-content-vertical">

			<div class="sfcfc-io-row">
				<div class="sfcfc-io-row-copy">
					<div class="sfcfc-connect-header">
						<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-download"></span></span>
						<div>
							<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Export Settings', 'wp-cloudflare-cache' ); ?></h3>
							<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Download your current SuperFlare configuration as a JSON file.', 'wp-cloudflare-cache' ); ?></p>
						</div>
					</div>
					<p class="sfcfc-field-hint"><?php esc_html_e( 'Use this to back up your setup before making changes, or to copy the same cache, purge, and advanced preferences to another site running SuperFlare Cache.', 'wp-cloudflare-cache' ); ?></p>
				</div>
				<div class="sfcfc-io-row-action">
					<a href="<?php echo esc_url( $sfcfc_export_url ); ?>" class="sfcfc-btn sfcfc-btn-primary"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Settings', 'wp-cloudflare-cache' ); ?></a>
				</div>
			</div>
			
			<div class="sfcfc-notice sfcfc-notice-info">
				<span class="sfcfc-notice-icon dashicons dashicons-shield-alt"></span>
				<div class="sfcfc-notice-body">
					<p class="sfcfc-notice-text">
						<strong><?php esc_html_e( 'Secure export: ', 'wp-cloudflare-cache' ); ?></strong>
						<?php esc_html_e( 'All sensitive information, such as your Cloudflare API credentials and connected zone, is excluded. Only this plugin\'s settings and configurations are included in the export.', 'wp-cloudflare-cache' ); ?>
					</p>
				</div>
			</div>

			<div class="sfcfc-io-row sfcfc-section-header">
				<div class="sfcfc-io-row-copy">
					<div class="sfcfc-connect-header">
						<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-upload"></span></span>
						<div>
							<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Import Settings', 'wp-cloudflare-cache' ); ?></h3>
							<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Restore settings from a previously exported JSON file.', 'wp-cloudflare-cache' ); ?></p>
						</div>
					</div>
					<p class="sfcfc-field-hint"><?php esc_html_e( 'Only recognized SuperFlare settings are read from the file; anything else is ignored. Your Cloudflare credentials and connected zone stay exactly as they are on this site.', 'wp-cloudflare-cache' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="sfcfc-import-form" class="sfcfc-import-form">
						<input type="hidden" name="action" value="sfcfc_import_settings">
						<?php wp_nonce_field( 'sfcfc_import_settings' ); ?>
						<div class="sfcfc-file-picker">
							<label for="sfcfc-import-file" class="sfcfc-btn sfcfc-btn-secondary sfcfc-file-picker-btn"><span class="dashicons dashicons-media-default"></span> <?php esc_html_e( 'Choose File', 'wp-cloudflare-cache' ); ?></label>
							<span class="sfcfc-file-picker-name" id="sfcfc-file-picker-name"><?php esc_html_e( 'No file selected', 'wp-cloudflare-cache' ); ?></span>
							<input type="file" id="sfcfc-import-file" name="sfcfc_import_file" accept="application/json,.json" class="sfcfc-file-input-hidden">
						</div>
					</form>
				</div>
				<div class="sfcfc-io-row-action">
					<button type="submit" form="sfcfc-import-form" class="sfcfc-btn sfcfc-btn-primary"><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import Settings', 'wp-cloudflare-cache' ); ?></button>
				</div>
			</div>
			
			<div class="sfcfc-notice sfcfc-notice-warning">
				<span class="sfcfc-notice-icon dashicons dashicons-warning"></span>
				<div class="sfcfc-notice-body">
					<p class="sfcfc-notice-text">
						<strong><?php esc_html_e( 'Important: ', 'wp-cloudflare-cache' ); ?></strong>
						<?php esc_html_e( 'Importing will overwrite your current settings. We recommend exporting your current configuration first to create a backup.', 'wp-cloudflare-cache' ); ?>
					</p>
				</div>
			</div>

			<div class="sfcfc-connect-header sfcfc-section-header">
				<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-image-rotate"></span></span>
				<div>
					<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Reset Settings', 'wp-cloudflare-cache' ); ?></h3>
					<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Looking to start over completely instead of importing a file? Every cache, purge, and advanced preference can be put back to its default.', 'wp-cloudflare-cache' ); ?></p>
				</div>
			</div>
			<p class="sfcfc-field-hint"><?php esc_html_e( 'You can do this from the ', 'wp-cloudflare-cache' ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare-settings#sfcfc-tab-advanced' ) ); ?>"><?php esc_html_e( 'Advanced settings tab', 'wp-cloudflare-cache' ); ?></a><?php esc_html_e( ', under Reset Settings.', 'wp-cloudflare-cache' ); ?></p>

			<div class="sfcfc-connect-header sfcfc-section-header">
				<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-info"></span></span>
				<div>
					<h3 class="sfcfc-connect-title"><?php esc_html_e( "What's Included in Export", 'wp-cloudflare-cache' ); ?></h3>
					<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'A plain-language look at what does, and does not, leave your site in the JSON file.', 'wp-cloudflare-cache' ); ?></p>
				</div>
			</div>
			<div class="sfcfc-export-scope">
				<div class="sfcfc-export-scope-col">
					<h4 class="sfcfc-export-scope-heading is-included"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Included', 'wp-cloudflare-cache' ); ?></h4>
					<ul class="sfcfc-export-scope-list">
						<li><?php esc_html_e( 'Cache Settings — edge/browser TTLs, caching level, sitemap/robots bypass', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Cache Exclusions — excluded page types, URLs, and cookies', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Purge Options — purge triggers, feeds, AMP, and custom purge URLs', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Preloader — enabled state, sitemap paths, cron preference', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Advanced — speed optimizations, roles, and behavior toggles', 'wp-cloudflare-cache' ); ?></li>
					</ul>
				</div>
				<div class="sfcfc-export-scope-col">
					<h4 class="sfcfc-export-scope-heading is-excluded"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Never Included', 'wp-cloudflare-cache' ); ?></h4>
					<ul class="sfcfc-export-scope-list">
						<li><?php esc_html_e( 'Cloudflare API credentials (email, API key, API token)', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Your connected zone / domain', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'Activity log and pending purge queue', 'wp-cloudflare-cache' ); ?></li>
						<li><?php esc_html_e( 'The preloader cron secret key', 'wp-cloudflare-cache' ); ?></li>
					</ul>
				</div>
			</div>
			<p class="sfcfc-field-hint"><?php esc_html_e( 'That means an exported file is safe to store, share with support, or commit to a private repo — it never contains anything that grants access to your Cloudflare account.', 'wp-cloudflare-cache' ); ?></p>

		</div>
		<div class="sfcfc-sidebar">
			<?php include __DIR__ . '/../sfcfc-sidebar.php'; ?>
		</div>
	</div>
</div>
