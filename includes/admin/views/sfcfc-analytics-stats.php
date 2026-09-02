<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sfcfc-analytics">
	<div class="sfcfc-stats" id="sfcfc-insights">
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="requests">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Total Requests', 'wp-cloudflare-cache' ); ?></span>
		</div>
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="cached_requests">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Cached Requests', 'wp-cloudflare-cache' ); ?></span>
		</div>
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="cached_pct">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Served from Cache', 'wp-cloudflare-cache' ); ?></span>
		</div>
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="bandwidth">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Bandwidth Saved', 'wp-cloudflare-cache' ); ?></span>
		</div>
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="visitors">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Unique Visitors', 'wp-cloudflare-cache' ); ?></span>
		</div>
		<div class="sfcfc-stat">
			<span class="sfcfc-stat__value" data-field="threats">–</span>
			<span class="sfcfc-stat__label"><?php esc_html_e( 'Threats Blocked', 'wp-cloudflare-cache' ); ?></span>
		</div>
	</div>
	<p class="sfcfc-description"><?php esc_html_e( 'Last 24 hours, reported by Cloudflare. Updates every 30 minutes.', 'wp-cloudflare-cache' ); ?></p>
</div>
