<?php
/**
 * View: Support page — get-in-touch options, a copyable system info snapshot for support
 * tickets, FAQs, and the review CTA.
 * Included from SFCFC_Settings::sfcfc_support_page() (class-sfcfc-settings.php), which
 * prepares $system_info before this include.
 *
 * @package SuperFlare
 * @var array $system_info
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sfcfc_system_info_text = '';
foreach ( $system_info as $sfcfc_info_item ) {
	$sfcfc_system_info_text .= $sfcfc_info_item['label'] . ': ' . $sfcfc_info_item['value'] . "\n";
}
?>

<div class="sfcfc-wrap">
	<div id="sfcfc-toast" class="sfcfc-toast" role="status" aria-live="polite"></div>
	<div class="sfcfc-header">
		<span class="dashicons dashicons-cloud"></span>
		<div>
			<h2 class="sfcfc-header-heading">Support</h2>
			<p class="sfcfc-description"><?php esc_html_e( 'Get help, ask a question, or report a bug.', 'wp-cloudflare-cache' ); ?></p>
		</div>
	</div>
	<div class="sfcfc-layout">
	<div class="sfcfc-content">

		<div class="sfcfc-connect-header">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-sos"></span></span>
			<div>
				<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Get in touch', 'wp-cloudflare-cache' ); ?></h3>
				<p class="sfcfc-connect-subtitle"><?php esc_html_e( "Stuck on setup, hit a bug, or something isn't caching the way you expect? We're happy to help.", 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
		<p class="sfcfc-field-hint"><?php esc_html_e( "Before posting, grab the System Information below — it's the first thing we'll ask for, and including it upfront gets you a faster answer.", 'wp-cloudflare-cache' ); ?></p>
		<div class="sfcfc-domain-active">
			<span class="sfcfc-domain-active-left"><span class="dashicons dashicons-wordpress-alt"></span> <strong><?php esc_html_e( 'WordPress.org Support Forum', 'wp-cloudflare-cache' ); ?></strong></span>
			<a href="https://wordpress.org/support/plugin/wp-cloudflare-cache/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-primary"><?php esc_html_e( 'Open a Support Thread', 'wp-cloudflare-cache' ); ?></a>
		</div>
		<p class="sfcfc-field-hint"><?php esc_html_e( 'Our free, community-driven channel. Search existing threads first — your question may already be answered.', 'wp-cloudflare-cache' ); ?></p>

		<div class="sfcfc-domain-active">
			<span class="sfcfc-domain-active-left"><span class="dashicons dashicons-email"></span> <strong><?php esc_html_e( 'SuperFlare Team', 'wp-cloudflare-cache' ); ?></strong></span>
			<a href="https://superflare.pro/contact/" target="_blank" rel="noopener noreferrer" class="sfcfc-btn sfcfc-btn-secondary"><?php esc_html_e( 'Contact Us', 'wp-cloudflare-cache' ); ?></a>
		</div>
		<p class="sfcfc-field-hint"><?php esc_html_e( 'Reach us directly for account, billing, or PRO licensing questions.', 'wp-cloudflare-cache' ); ?></p>

		<div class="sfcfc-connect-header sfcfc-section-header">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-info"></span></span>
			<div>
				<h3 class="sfcfc-connect-title"><?php esc_html_e( 'System Information', 'wp-cloudflare-cache' ); ?></h3>
				<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Copy this into your support request so we can diagnose the issue without back-and-forth.', 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
		<ul class="sfcfc-status-list">
			<?php foreach ( $system_info as $sfcfc_info_item ) : ?>
				<li>
					<span class="sfcfc-status-label"><?php echo esc_html( $sfcfc_info_item['label'] ); ?></span>
					<span class="sfcfc-status-value is-<?php echo esc_attr( $sfcfc_info_item['state'] ); ?>"><?php echo esc_html( $sfcfc_info_item['value'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<textarea id="sfcfc-system-info-text" class="sfcfc-hidden-row" readonly><?php echo esc_textarea( $sfcfc_system_info_text ); ?></textarea>
		<p class="sfcfc-field"><a id="sfcfc-copy-system-info" class="sfcfc-btn sfcfc-btn-secondary"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy for Support Ticket', 'wp-cloudflare-cache' ); ?></a></p>

		<div class="sfcfc-connect-header sfcfc-section-header">
			<span class="sfcfc-connect-icon is-accent"><span class="dashicons dashicons-editor-help"></span></span>
			<div>
				<h3 class="sfcfc-connect-title"><?php esc_html_e( 'Frequently Asked Questions', 'wp-cloudflare-cache' ); ?></h3>
				<p class="sfcfc-connect-subtitle"><?php esc_html_e( 'Quick answers to the questions we see most often.', 'wp-cloudflare-cache' ); ?></p>
			</div>
		</div>
		<div class="sfcfc-faq">
			<details class="sfcfc-faq-item">
				<summary><?php esc_html_e( 'My changes aren\'t showing up on the live site. What do I do?', 'wp-cloudflare-cache' ); ?></summary>
				<p><?php esc_html_e( 'Cloudflare serves cached pages at the edge, so an update can take a moment to appear. Use Quick Actions on the Dashboard or Settings page to purge the specific page, or purge the entire cache. If it still doesn\'t update, check General → Cache Status to confirm the Cache Rule is Active.', 'wp-cloudflare-cache' ); ?></p>
			</details>
			<details class="sfcfc-faq-item">
				<summary><?php esc_html_e( 'Where do I get a Cloudflare API Token?', 'wp-cloudflare-cache' ); ?></summary>
				<p><?php esc_html_e( 'Go to the General tab and start connecting your account — it walks you through creating a token on Cloudflare\'s API Tokens page with exactly the permissions this plugin needs.', 'wp-cloudflare-cache' ); ?></p>
			</details>
			<details class="sfcfc-faq-item">
				<summary><?php esc_html_e( 'Does this work on Cloudflare\'s Free plan?', 'wp-cloudflare-cache' ); ?></summary>
				<p><?php esc_html_e( 'Yes. Full-page caching, purging, and most Zone Settings work on the Free plan. A small number of PRO features rely on paid Cloudflare capabilities and are called out where they appear.', 'wp-cloudflare-cache' ); ?></p>
			</details>
			<details class="sfcfc-faq-item">
				<summary><?php esc_html_e( 'I disconnected by accident — will I lose my settings?', 'wp-cloudflare-cache' ); ?></summary>
				<p><?php esc_html_e( 'No. Disconnecting only removes your saved Cloudflare credentials and zone. Your Cache Settings, Purge Options, and Advanced preferences are untouched, and reconnecting picks up right where you left off.', 'wp-cloudflare-cache' ); ?></p>
			</details>
			<details class="sfcfc-faq-item">
				<summary><?php esc_html_e( 'How do I report a bug?', 'wp-cloudflare-cache' ); ?></summary>
				<p><?php esc_html_e( 'Open a thread on the WordPress.org support forum above with the System Information from this page, the steps to reproduce the issue, and what you expected to happen instead.', 'wp-cloudflare-cache' ); ?></p>
			</details>
		</div>

		<p class="sfcfc-field-hint"><?php esc_html_e( 'Want to learn more first?', 'wp-cloudflare-cache' ); ?> <a target="_blank" rel="noopener noreferrer" href="https://superflare.pro/"><?php esc_html_e( 'Visit our official website', 'wp-cloudflare-cache' ); ?></a>.</p>

	</div>
	<div class="sfcfc-sidebar">
		<?php include __DIR__ . '/../sfcfc-sidebar.php'; ?>
	</div>
	</div>
</div>
