<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$sfcfc_auth_method     = get_option( 'sfcfc_options' )['cf_auth_method'] ?? 'token';
$sfcfc_has_credentials = $this->instance && $this->instance->cachepurge && $this->instance->cachepurge->has_credentials();
$sfcfc_zone_id         = $this->instance ? $this->instance->get_single_config( 'cf_zone_id', '' ) : '';
$sfcfc_setup_complete  = $sfcfc_has_credentials && $sfcfc_zone_id;
$sfcfc_cf_tokens_url   = 'https://dash.cloudflare.com/profile/api-tokens';
?>
<div class="sfcfc-wrap">
	<div id="sfcfc-toast" class="sfcfc-toast" role="status" aria-live="polite"></div>
	<div class="sfcfc-content sfcfc-wizard-narrow" id="sfcfc-wizard-content">
		<?php $this->sfcfc_render_connection_card( '', array(), true ); ?>

		<?php if ( $sfcfc_setup_complete ) : ?>
			<div class="sfcfc-field">
				<label class="sfcfc-auth-title"><?php esc_html_e( "Let's Get Started", 'wp-cloudflare-cache' ); ?></label>
				<div class="sfcfc-wizard-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare-settings' ) ); ?>" class="sfcfc-btn sfcfc-btn-secondary">
						<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Configure Settings', 'wp-cloudflare-cache' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=superflare' ) ); ?>" class="sfcfc-btn sfcfc-btn-secondary">
						<span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'See Analytics', 'wp-cloudflare-cache' ); ?>
					</a>
				</div>
			</div>
		<?php else : ?>
			<div id="sfcfc-guides-wrap" class="sfcfc-hidden-row">
				<div class="sfcfc-help-section sfcfc-guide-token<?php echo 'token' === $sfcfc_auth_method ? '' : ' sfcfc-hidden-row'; ?>">
					<?php include __DIR__ . '/sfcfc-guide-token.php'; ?>
				</div>
				<div class="sfcfc-help-section sfcfc-guide-key<?php echo 'key' === $sfcfc_auth_method ? '' : ' sfcfc-hidden-row'; ?>">
					<?php include __DIR__ . '/sfcfc-guide-key.php'; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
