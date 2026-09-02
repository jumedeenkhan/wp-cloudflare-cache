<?php
/**
 * View: Settings page wrapper — header, tab nav, and the two-column layout.
 * Included from SFCFC_Settings::sfcfc_settings_page() (class-sfcfc-settings.php), so $this is
 * that instance. Tab content lives in views/; the sidebar lives alongside this file.
 *
 * @package SuperFlare
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sfcfc_views_dir = __DIR__ . '/views/';
?>

<div class="sfcfc-wrap">
	<div id="sfcfc-toast" class="sfcfc-toast" role="status" aria-live="polite"></div>
	<div class="sfcfc-header">
		<span class="dashicons dashicons-cloud"></span>
		<div>
			<h2 class="sfcfc-header-heading">SuperFlare Cache Settings</h2>
			<p class="sfcfc-description"><?php esc_html_e( 'Serve your site from Cloudflare\'s edge, purged automatically on every update.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>
	<div class="sfcfc-main">
		<div class="sfcfc-nav-tab-wrapper">
			<a class="sfcfc-nav-tab" href="#sfcfc-tab-general">General</a>
			<a class="sfcfc-nav-tab" href="#sfcfc-tab-cache-settings">Cache Settings</a>
			<a class="sfcfc-nav-tab" href="#sfcfc-tab-cache-purge">Purge Cache</a>
			<a class="sfcfc-nav-tab" href="#sfcfc-tab-optimization">Optimization</a>
			<a class="sfcfc-nav-tab" href="#sfcfc-tab-advanced">Advanced</a>
		</div>
		<div class="sfcfc-layout">
			<div class="sfcfc-content">
				<form method="post" action="options.php" id="sfcfc-settings-form">
					<div class="sfcfc-general sfcfc-tab-panel" id="sfcfc-tab-general">
						<?php include $sfcfc_views_dir . 'sfcfc-general.php'; ?>
					</div>
					<div class="sfcfc-cache sfcfc-tab-panel" id="sfcfc-tab-cache-settings">
						<?php include $sfcfc_views_dir . 'sfcfc-cache-settings.php'; ?>
					</div>
					<div class="sfcfc-purge sfcfc-tab-panel" id="sfcfc-tab-cache-purge">
						<?php include $sfcfc_views_dir . 'sfcfc-cache-purge.php'; ?>
					</div>
					<div class="sfcfc-optimization sfcfc-tab-panel" id="sfcfc-tab-optimization">
						<?php include $sfcfc_views_dir . 'sfcfc-optimization.php'; ?>
					</div>
					<div class="sfcfc-advanced sfcfc-tab-panel" id="sfcfc-tab-advanced">
						<?php include $sfcfc_views_dir . 'sfcfc-advanced.php'; ?>
					</div>
					<?php settings_fields( 'sfcfc_settings' ); ?>
					<div class="sfcfc-save-bar" id="sfcfc-save-bar">
						<button type="submit" id="sfcfc-save-settings" class="sfcfc-btn sfcfc-btn-primary">Save Changes</button>
						<button type="button" id="sfcfc-discard-changes" class="sfcfc-btn sfcfc-btn-secondary">Discard Changes</button>
					</div>
				</form>
			</div>
			<div class="sfcfc-sidebar">
				<?php include __DIR__ . '/sfcfc-sidebar.php'; ?>
			</div>
		</div>
	</div>
	<div class="sfcfc-modal-backdrop" id="sfcfc-discard-modal" hidden>
		<div class="sfcfc-modal" role="alertdialog" aria-modal="true" aria-labelledby="sfcfc-discard-modal-title">
			<button type="button" class="sfcfc-modal-close" id="sfcfc-discard-modal-close" aria-label="Close">
				<svg class="sfcfc-modal-close-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
			</button>
			<div class="sfcfc-modal-icon">
				<svg class="sfcfc-modal-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
			</div>
			<h3 class="sfcfc-modal-title" id="sfcfc-discard-modal-title">Discard all changes</h3>
			<p class="sfcfc-modal-text">You are about to discard all unsaved changes. All of your settings will be reset to the point where you last saved. Are you sure you want to do this?</p>
			<div class="sfcfc-modal-actions">
				<button type="button" class="sfcfc-btn sfcfc-btn-secondary" id="sfcfc-discard-cancel">No, continue editing</button>
				<button type="button" class="sfcfc-btn sfcfc-btn-danger" id="sfcfc-discard-confirm">Yes, discard changes</button>
			</div>
		</div>
	</div>
</div>
