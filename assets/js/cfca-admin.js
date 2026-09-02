(function ($) {
	var $toast = null;
	var toastTimer = null;

	// Anchors the one shared toast next to whichever button triggered it: to the right of the save
	// button, or just below a purge/test/reset button. Falls back to viewport clamping either way.
	function positionToastNear($btn, placement) {
		if (!$toast || !$btn || !$btn.length) {
			return;
		}
		var rect = $btn[0].getBoundingClientRect();
		var w    = $toast.outerWidth();
		var h    = $toast.outerHeight();
		var top, left;

		if ('right' === placement) {
			top  = rect.top + (rect.height / 2) - (h / 2);
			left = rect.right + 12;
		} else {
			top  = rect.bottom + 10;
			left = rect.left;
		}

		top  = Math.min(Math.max(8, top), $(window).height() - h - 8);
		left = Math.min(Math.max(8, left), $(window).width() - w - 8);

		$toast.css({ top: top + 'px', left: left + 'px', right: 'auto', bottom: 'auto' });
	}

	function showToast($btn, placement, type, message) {
		if (!$toast || !message) {
			return;
		}
		clearTimeout(toastTimer);
		$toast.attr('class', 'cfca-toast cfca-toast-' + type).text(message);
		positionToastNear($btn, placement);
		$toast.addClass('is-visible');
		toastTimer = setTimeout(function () {
			$toast.removeClass('is-visible');
		}, 5000);
	}

	// Busy state never changes the button's own design: same classes stay on, only text + aria-busy change.
	function runAjaxButton($btn, data, busyText, placement) {
		var original = $btn.text();
		$btn.prop('disabled', true).attr('aria-busy', 'true').text(busyText);

		return $.post(ajaxurl, data)
			.done(function (response) {
				var payload = (response && response.data) || {};
				var success = !!(response && response.success);
				showToast($btn, placement, payload.type || (success ? 'success' : 'error'), payload.message || (success ? 'Done!' : 'Something went wrong.'));
			})
			.fail(function () {
				showToast($btn, placement, 'error', 'Request failed. Please try again.');
			})
			.always(function () {
				$btn.prop('disabled', false).removeAttr('aria-busy').text(original);
			});
	}

	$(document).ready(function () {
		$toast = $('#cfca-toast');

		// Self-contained: unlike core's password-toggle.js, this doesn't require the input's id/name to be "pwd".
		$('.wp-pwd .pwd-toggle').removeClass('hide-if-no-js').on('click', function (e) {
			e.preventDefault();
			var $btn    = $(this);
			var $input  = $btn.siblings('input[type="password"], input[type="text"]').first();
			var showing = $input.attr('type') === 'text';

			$input.attr('type', showing ? 'password' : 'text');
			$btn.find('.dashicons').toggleClass('dashicons-visibility', showing).toggleClass('dashicons-hidden', !showing);
			$btn.find('.text').text(showing ? 'Show' : 'Hide');
			$btn.attr('aria-label', showing ? 'Show password' : 'Hide password');
		});

		$(document).on('change', '.cfca-auth-toggle', function () {
			var method = $(this).val();
			$('.cfca-auth-key-row').toggleClass('cfca-hidden-row', method === 'token');
			$('.cfca-auth-token-row').toggleClass('cfca-hidden-row', method !== 'token');
		});

		function cfcaActivateTab(hash) {
			var $tabs  = $('.cfca-nav-tab-wrapper .cfca-nav-tab');
			var target = ( hash && $(hash).hasClass('cfca-tab-panel') ) ? hash : '#cfca-tab-settings';

			$('.cfca-tab-panel').hide();
			$(target).show();

			$tabs.removeClass('cfca-nav-tab-active');
			$tabs.filter('[href="' + target + '"]').addClass('cfca-nav-tab-active');
		}

		cfcaActivateTab(window.location.hash);

		// Delegated so any link to a tab hash works, not just the nav tabs themselves — e.g. the
		// "See Installation Guide" link inside the API Token field description.
		$(document).on('click', 'a[href^="#cfca-tab-"]', function (e) {
			e.preventDefault();
			var hash = $(this).attr('href');
			history.replaceState(null, '', hash);
			cfcaActivateTab(hash);
		});

		$('#cfca-settings-form').on('submit', function (e) {
			e.preventDefault();
			var $form = $(this);
			var $btn  = $('#cfca-save-settings');
			var data  = $form.serialize() + '&action=cfca_save_settings&nonce=' + cfca_ajax.nonce;

			runAjaxButton($btn, data, 'Updating\u2026', 'right').done(function (response) {
				var payload = (response && response.data) || {};
				if (payload.statusHtml) {
					$('#cfca-connection-status').replaceWith(payload.statusHtml);
				}
				if (payload.domainHtml) {
					$('#cfca-zone-domain-field').replaceWith(payload.domainHtml);
				}
			});
		});

		$('#cfca-purge-everything').on('click', function (e) {
			e.preventDefault();
			runAjaxButton($(this), { action: 'cfca_purge_everything', nonce: cfca_ajax.nonce }, 'Purging\u2026', 'below');
		});

		// The admin bar button can appear on any wp-admin page, not just Settings, so there's no #cfca-toast
		// nearby to anchor a popup to — a plain confirm/alert is the only feedback available everywhere.
		$('li#wp-admin-bar-cfca-purge-button .ab-item').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Purge the entire Cloudflare cache for this site?')) {
				return;
			}
			$.post(ajaxurl, { action: 'cfca_purge_everything', nonce: cfca_ajax.nonce })
				.done(function (response) {
					var payload = (response && response.data) || {};
					window.alert(payload.message || ((response && response.success) ? 'Cache purged.' : 'Something went wrong.'));
				})
				.fail(function () {
					window.alert('Request failed. Please try again.');
				});
		});

		$('#cfca-purge-testing').on('click', function (e) {
			e.preventDefault();
			runAjaxButton($(this), { action: 'cfca_purge_test_config', nonce: cfca_ajax.nonce }, 'Testing\u2026', 'below');
		});

		$('#cfca-reset-settings').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Reset all Cloudflare Cache settings to their defaults? Your credentials and domain will be kept.')) {
				return;
			}
			runAjaxButton($(this), { action: 'cfca_reset_settings', nonce: cfca_ajax.nonce }, 'Resetting\u2026', 'below').done(function (response) {
				if (response && response.success) {
					setTimeout(function () { window.location.reload(); }, 900);
				}
			});
		});
	});
})(jQuery);
