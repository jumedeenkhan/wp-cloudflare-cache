<?php
/**
 * View: Global API Key setup guide, shown on the Setup Wizard when Authentication Mode is Global API Key.
 *
 * @package SuperFlare
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<h3 class="sfcfc-connect-title">Follow these simple steps:</h3>
<p class="sfcfc-connect-subtitle">You need to set up your Cloudflare credentials. Don&rsquo;t worry &mdash; it only takes a few minutes.</p>
<div class="sfcfc-steps">
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">1</span>
		<div class="sfcfc-step-body">
			<h4>Open your Cloudflare API Tokens page</h4>
			<p>Log in to Cloudflare dashboard, go to your <a href="<?php echo esc_url( $sfcfc_cf_tokens_url ); ?>" target="_blank">API Tokens page</a>, then scroll down to <strong>Global API Key</strong> and click <strong>View</strong>.</p>
		</div>
	</div>
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">2</span>
		<div class="sfcfc-step-body">
			<h4>Reveal your Global API Key</h4>
			<p>Enter your Cloudflare password when prompted, then click <strong>View</strong> again and copy the key.</p>
		</div>
	</div>
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">3</span>
		<div class="sfcfc-step-body">
			<h4>Connect it to your site</h4>
			<p>Paste your Cloudflare email into the <strong>Cloudflare Email</strong> field and the key into the <strong>Global API Key</strong> field, then click <strong>Connect to Cloudflare</strong>.</p>
		</div>
	</div>
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">4</span>
		<div class="sfcfc-step-body">
			<h4>Choose your domain</h4>
			<p>Select your domain from the list and click <strong>Finish Setup</strong>.</p>
		</div>
	</div>
</div>
<p class="sfcfc-guide-success"><strong>That's it.</strong> Cloudflare caching starts working now automatically.</p>
