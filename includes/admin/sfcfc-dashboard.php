<?php
/**
 * View: Dashboard page — the Activity Log.
 * Included from SFCFC_Settings::sfcfc_dashboard_page() (class-sfcfc-settings.php), which
 * prepares $log before this include.
 *
 * @package SuperFlare
 * @var array $log
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="sfcfc-wrap">
	<div id="sfcfc-toast" class="sfcfc-toast" role="status" aria-live="polite"></div>
	<div class="sfcfc-header">
		<span class="dashicons dashicons-cloud"></span>
		<div>
			<h2 class="sfcfc-header-heading">Dashboard</h2>
			<p class="sfcfc-description"><?php esc_html_e( 'Your Cloudflare traffic at a glance, plus a record of the most recent cache purges.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>
	<?php if ( ! empty( $sfcfc_analytics_available ) ) : ?>
		<?php include __DIR__ . '/views/sfcfc-analytics-stats.php'; ?>
	<?php endif; ?>
	<div class="sfcfc-layout">
		<div class="sfcfc-content">
			<?php if ( ! empty( $sfcfc_analytics_available ) ) : ?>
				<?php include __DIR__ . '/views/sfcfc-analytics-chart.php'; ?>
			<?php endif; ?>
			<h2 class="sfcfc-section-heading"><?php esc_html_e( 'Activity Log', 'wp-cloudflare-cache' ); ?></h2>
			<?php include __DIR__ . '/views/sfcfc-activity-log.php'; ?>
		</div>
		<div class="sfcfc-sidebar">
			<?php include __DIR__ . '/sfcfc-sidebar.php'; ?>
		</div>
	</div>
</div>
