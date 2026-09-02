<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="sfcfc-analytics-chart">
	<div class="sfcfc-card__header">
		<span><?php esc_html_e( 'Requests Over Time', 'wp-cloudflare-cache' ); ?></span>
		<span class="sfcfc-analytics-card__range"><?php esc_html_e( 'Previous 24 hours', 'wp-cloudflare-cache' ); ?></span>
	</div>
	<div class="sfcfc-chart-row">
		<canvas id="sfcfc-requests-chart" height="90"></canvas>
	</div>
</div>
