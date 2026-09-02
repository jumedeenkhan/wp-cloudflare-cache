<?php
/**
 * View: Activity Log tab content.
 *
 * @package SuperFlare
 * @var array $log Prepared by sfcfc-dashboard.php before this include.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php if ( empty( $sfcfc_activity_log_enabled ) ) : ?>
	<div class="sfcfc-notice sfcfc-notice-warning">
		<span class="sfcfc-notice-icon dashicons dashicons-info"></span>
		<div class="sfcfc-notice-body">
			<p class="sfcfc-notice-text"><?php esc_html_e( 'Activity Log is turned off. Enable it from the Activity Log setting under Advanced settings to start recording cache purges and preloads here.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>
<?php elseif ( empty( $log ) ) : ?>
	<p class="sfcfc-description"><?php esc_html_e( 'No activity yet.', 'wp-cloudflare-cache' ); ?></p>
<?php else : ?>
	<table class="sfcfc-log-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'wp-cloudflare-cache' ); ?></th>
				<th><?php esc_html_e( 'Type', 'wp-cloudflare-cache' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-cloudflare-cache' ); ?></th>
				<th><?php esc_html_e( 'Message', 'wp-cloudflare-cache' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $log as $sfcfc_entry ) : ?>
			<tr>
				<td><?php echo esc_html( wp_date( 'M j, Y g:i a', $sfcfc_entry['time'] ?? 0 ) ); ?></td>
				<td><?php echo esc_html( $sfcfc_entry['type'] ?? '' ); ?></td>
				<td>
					<span class="sfcfc-status <?php echo ! empty( $sfcfc_entry['success'] ) ? 'sfcfc-status-connected' : 'sfcfc-status-pending'; ?>">
						<?php echo ! empty( $sfcfc_entry['success'] ) ? esc_html__( 'Success', 'wp-cloudflare-cache' ) : esc_html__( 'Failed', 'wp-cloudflare-cache' ); ?>
					</span>
				</td>
				<td><?php echo esc_html( $sfcfc_entry['message'] ?? '' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="sfcfc-description">
		<a id="sfcfc-clear-log" class="sfcfc-btn sfcfc-btn-secondary">Clear Log</a>
	</p>
<?php endif; ?>
