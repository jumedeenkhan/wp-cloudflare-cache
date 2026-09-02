/**
 * Kept independent of sfcfc-admin.js, which is only loaded on the plugin's own admin pages.
 */
(function ($) {
	'use strict';

	$(function () {
		$('li#wp-admin-bar-sfcfc-purge-button .ab-item').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Purge the entire Cloudflare cache for this site?')) {
				return;
			}
			$.post(sfcfc_ajax.ajaxUrl, { action: 'sfcfc_purge_everything', nonce: sfcfc_ajax.nonce })
				.done(function (response) {
					var payload = (response && response.data) || {};
					window.alert(payload.message || ((response && response.success) ? 'Cache purged.' : 'Something went wrong.'));
				})
				.fail(function () {
					window.alert('Request failed. Please try again.');
				});
		});
	});
})(jQuery);
