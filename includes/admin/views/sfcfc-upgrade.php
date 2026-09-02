<?php
/**
 * View: Upgrade to PRO page. Marketing/landing content for the paid SuperFlare Cache PRO
 * add-on, sold separately at https://superflare.pro/. No sidebar on this page (see
 * SFCFC_Settings::sfcfc_settings_page() page routing / templates that include sfcfc-sidebar.php).
 *
 * @package SuperFlare
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="sfcfc-wrap">
	<div class="sfcfc-header">
		<span class="dashicons dashicons-cloud"></span>
		<div>
			<h2 class="sfcfc-header-heading">Upgrade to PRO</h2>
			<p class="sfcfc-description"><?php esc_html_e( 'Unlock advanced Cloudflare controls built for high-traffic, multi-site, and agency workflows.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>

	<div class="sfcfc-pro-hero">
		<span class="sfcfc-pro-badge sfcfc-pro-badge-lg"><?php esc_html_e( 'SUPERFLARE PRO', 'wp-cloudflare-cache' ); ?></span>
		<h1 class="sfcfc-pro-hero-title"><?php esc_html_e( 'Everything in SuperFlare Cache, plus the power to run it at scale.', 'wp-cloudflare-cache' ); ?></h1>
		<p class="sfcfc-pro-hero-subtitle"><?php esc_html_e( 'Manage multiple zones, build custom purge and Page Rules, and see exactly how much Cloudflare is saving you — all from wp-admin.', 'wp-cloudflare-cache' ); ?></p>
		<div class="sfcfc-pro-hero-actions">
			<a href="https://superflare.pro/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-pro sfcfc-btn-lg"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Get SuperFlare PRO', 'wp-cloudflare-cache' ); ?></a>
			<a href="https://superflare.pro/pricing/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-secondary sfcfc-btn-lg"><?php esc_html_e( 'View Pricing', 'wp-cloudflare-cache' ); ?></a>
		</div>
	</div>

	<div class="sfcfc-pro-features">
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-networking"></span></span>
			<h3><?php esc_html_e( 'Multi-Zone & Multisite Management', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Connect every Cloudflare zone across a WordPress multisite network and manage them all from one dashboard.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-filter"></span></span>
			<h3><?php esc_html_e( 'Advanced Purge Rules', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Build granular auto-purge rules by post type, taxonomy, template, or custom REST route — not just the whole site.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-chart-line"></span></span>
			<h3><?php esc_html_e( 'Real-Time Cache Analytics', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'See hit/miss ratio, bandwidth saved, and your top cached URLs with visual reports updated live.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-clock"></span></span>
			<h3><?php esc_html_e( 'Scheduled, Prioritized Preloading', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Queue preloads on a custom schedule and mark your most important pages as top priority.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-admin-page"></span></span>
			<h3><?php esc_html_e( 'Cloudflare Page Rules Builder', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Create, edit, and sync Cloudflare Page Rules directly from wp-admin — no dashboard hopping required.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-sos"></span></span>
			<h3><?php esc_html_e( 'Priority Support & Onboarding', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Get 1-on-1 setup help and priority ticket response from the SuperFlare team whenever you need it.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-database"></span></span>
			<h3><?php esc_html_e( 'Local Disk Page Cache', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Adds a second full-page cache layer straight on your server, so pages stay fast even on requests Cloudflare passes through to your origin.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-performance"></span></span>
			<h3><?php esc_html_e( 'Advanced Preloader', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Discovers URLs from your sitemap, menus, and recent content, then preloads them after a purge or on a schedule.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-format-image"></span></span>
			<h3><?php esc_html_e( 'Automatic Image Optimization', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Mirage & Polish controls, and serves JPEG/PNG images as WebP automatically.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-cart"></span></span>
			<h3><?php esc_html_e( 'WooCommerce & EDD Support', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Cart, checkout, account, shop, product, and category pages, each get their own toggle.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-randomize"></span></span>
			<h3><?php esc_html_e( 'Sync With Other Caches', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Works alongside 11 other caching plugins and your host\'s own cache.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-feature">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-tag"></span></span>
			<h3><?php esc_html_e( 'Ignore Marketing Parameters', 'wp-cloudflare-cache' ); ?></h3>
			<p><?php esc_html_e( 'Like utm_*, gclid, and fbclid, so ad traffic stops fragmenting your cache.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>

	<div class="sfcfc-pro-cta">
		<div>
			<h3 class="sfcfc-pro-cta-title"><?php esc_html_e( 'Ready to move up to PRO?', 'wp-cloudflare-cache' ); ?></h3>
			<p class="sfcfc-pro-cta-text"><?php esc_html_e( 'Plans work on top of your existing free setup — no reconfiguration needed.', 'wp-cloudflare-cache' ); ?></p>
		</div>
		<div class="sfcfc-pro-cta-actions">
			<a href="https://superflare.pro/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-pro sfcfc-btn-lg"><?php esc_html_e( 'Get SuperFlare PRO', 'wp-cloudflare-cache' ); ?></a>
			<a href="https://superflare.pro/contact/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn-link"><?php esc_html_e( 'Have questions? Contact us', 'wp-cloudflare-cache' ); ?></a>
		</div>
	</div>

</div>
