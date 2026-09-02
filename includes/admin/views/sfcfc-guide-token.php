<?php
/**
 * View: API Token setup guide, shown on the Setup Wizard when Authentication Mode is API Token.
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
			<p>Log in to Cloudflare dashboard and go to your <a href="<?php echo esc_url( $sfcfc_cf_tokens_url ); ?>" target="_blank">API Tokens page</a>.</p>
		</div>
	</div>
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">2</span>
		<div class="sfcfc-step-body">
			<h4>Create a token with the right permissions</h4>
			<p>Click on <strong>Create Token</strong>, then grant these permissions for your site's domain:</p>
			<div class="sfcfc-scope-list">
				<span class="sfcfc-scope">Zone &gt; Zone &gt; [Read]</span>
				<span class="sfcfc-scope">Zone &gt; Zone Settings &gt; [Edit]</span>
				<span class="sfcfc-scope">Zone &gt; Cache Rules &gt; [Edit]</span>
				<span class="sfcfc-scope">Zone &gt; Cache Purge &gt; [Purge]</span>
				<span class="sfcfc-scope">Zone &gt; Analytics &gt; [Read]</span>
			</div>
			<br>
			<p>Don't forget to copy the generated token.</p>
		</div>
	</div>
	<div class="sfcfc-step">
		<span class="sfcfc-step-number">3</span>
		<div class="sfcfc-step-body">
			<h4>Connect it to your site</h4>
			<p>Paste the token into the <strong>API Token</strong> field, then click <strong>Connect to Cloudflare</strong>.</p>
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
