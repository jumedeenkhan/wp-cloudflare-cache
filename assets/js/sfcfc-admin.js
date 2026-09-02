(function ($) {
	var $toast = null;
	var toastTimer = null;

	var SFCFC_TABS_WITHOUT_SAVE_BAR = [];
	var SFCFC_TRACKED_FIELDS_SELECTOR = '#sfcfc-tab-general :input, #sfcfc-tab-cache-settings :input, #sfcfc-tab-cache-purge :input, #sfcfc-tab-optimization :input, #sfcfc-tab-advanced :input';
	var sfcfcIsDirty      = false;
	var sfcfcActiveTab    = '#sfcfc-tab-general';
	var sfcfcBaselineState = '';

	/**
	 * Compares against the last-saved snapshot, not just "did anything change", so
	 * reverting a field to its original value hides the save bar again too.
	 */
	function sfcfcRefreshDirtyState() {
		sfcfcIsDirty = $(SFCFC_TRACKED_FIELDS_SELECTOR).serialize() !== sfcfcBaselineState;
		sfcfcUpdateSaveBar();
	}

	function sfcfcCaptureBaseline() {
		sfcfcBaselineState = $(SFCFC_TRACKED_FIELDS_SELECTOR).serialize();
	}

	function sfcfcUpdateSaveBar() {
		var onExemptTab = SFCFC_TABS_WITHOUT_SAVE_BAR.indexOf(sfcfcActiveTab) !== -1;
		$('#sfcfc-save-bar').toggleClass('is-visible', sfcfcIsDirty && !onExemptTab);
	}

	/**
	 * One sticky popup handles every success/error/warning message, instead of
	 * anchoring to whichever button triggered it.
	 */
	function showToast(type, message) {
		if (!$toast || !message) {
			return;
		}
		clearTimeout(toastTimer);
		$toast.attr('class', 'sfcfc-toast sfcfc-toast-' + type).text(message).addClass('is-visible');
		toastTimer = setTimeout(function () {
			$toast.removeClass('is-visible');
		}, 5000);
	}

	/**
	 * Keeps the button's own classes; only its content and aria-busy change. Markup is
	 * restored via .html(), not .text(), so an icon span inside the button survives.
	 */
	function runAjaxButton($btn, data, busyText) {
		var original = $btn.html();
		$btn.prop('disabled', true).attr('aria-busy', 'true').html('<span class="sfcfc-spinner"></span> ' + busyText);

		return $.post(sfcfc_ajax.ajaxUrl, data)
			.done(function (response) {
				var payload = (response && response.data) || {};
				var success = !!(response && response.success);
				showToast(payload.type || (success ? 'success' : 'error'), payload.message || (success ? 'Done!' : 'Something went wrong.'));
			})
			.fail(function () {
				showToast('error', 'Request failed. Please try again.');
			})
			.always(function () {
				$btn.prop('disabled', false).removeAttr('aria-busy').html(original);
			});
	}

	$(document).ready(function () {
		$toast = $('#sfcfc-toast');

		/**
		 * Delegated, not bound directly, so it still works on fields inside
		 * #sfcfc-connection-card after it's replaced wholesale by an AJAX response.
		 */
		$(document).on('click', '.wp-pwd .pwd-toggle', function (e) {
			e.preventDefault();
			var $btn    = $(this);
			var $input  = $btn.siblings('input[type="password"], input[type="text"]').first();
			var showing = $input.attr('type') === 'text';

			$input.attr('type', showing ? 'password' : 'text');
			$btn.find('.dashicons').toggleClass('dashicons-visibility', showing).toggleClass('dashicons-hidden', !showing);
			$btn.find('.text').text(showing ? 'Show' : 'Hide');
			$btn.attr('aria-label', showing ? 'Show password' : 'Hide password');
		});

		/**
		 * Toggles #sfcfc-cache-enabled-field vs #sfcfc-proxy-blocked-notice based on whether
		 * the domain is actually proxied through Cloudflare, so the caching toggle never shows
		 * when traffic can't reach Cloudflare. Named (not inline) since it also runs after
		 * #sfcfc-connection-card is replaced wholesale (Connect / Finish Setup). A null/unknown
		 * result leaves the current state alone rather than guessing.
		 */
		function sfcfcApplyProxyGate(proxied) {
			if (proxied === true) {
				$('#sfcfc-cache-enabled-field').removeClass('sfcfc-hidden-row');
				$('#sfcfc-proxy-blocked-notice').addClass('sfcfc-hidden-row');
			} else if (proxied === false) {
				$('#sfcfc-cache-enabled-field').addClass('sfcfc-hidden-row');
				$('#sfcfc-proxy-blocked-notice').removeClass('sfcfc-hidden-row');
			}
		}

		function sfcfcRunDnsProxyCheck($btn) {
			if (!$('#sfcfc-dns-proxy-check').length) {
				return;
			}
			if ($btn) {
				$btn.prop('disabled', true).attr('aria-busy', 'true');
			}
			$.post(sfcfc_ajax.ajaxUrl, { action: 'sfcfc_check_dns_proxy', nonce: sfcfc_ajax.nonce }).done(function (response) {
				if (response && response.success && response.data) {
					sfcfcApplyProxyGate(response.data.proxied);
				}
			}).always(function () {
				if ($btn) {
					$btn.prop('disabled', false).removeAttr('aria-busy');
				}
			});
		}
		sfcfcRunDnsProxyCheck();

		$(document).on('click', '#sfcfc-recheck-proxy', function (e) {
			e.preventDefault();
			sfcfcRunDnsProxyCheck($(this));
		});

		/**
		 * Pill switch: flips its own hidden field (matched via data-toggle-group), so the
		 * form's normal serialize()/submit/dirty-tracking treats it like a native checkbox.
		 */
		function sfcfcApplyToggleState($btn, isOn) {
			var group = $btn.data('toggle-group');
			$btn.toggleClass('is-on', isOn).attr('aria-checked', isOn ? 'true' : 'false');
			$('input[type="hidden"][name="' + group + '"]').val(isOn ? 'on' : '');
		}

		/**
		 * Dims + locks rows tagged sfcfc-depends-{field}, based on that master toggle's live state,
		 * so a dependent setting never reads as independently active while its master is off.
		 */
		function sfcfcApplyDependentRows() {
			$('[class*="sfcfc-depends-"]').each(function () {
				var $row  = $(this);
				var match = ( $row.attr('class') || '' ).match(/sfcfc-depends-(\S+)/);
				if (!match) {
					return;
				}
				var masterOn = $('#sfcfc-toggle-' + match[1]).hasClass('is-on');
				$row.toggleClass('sfcfc-row-disabled', !masterOn);
				$row.find('.sfcfc-toggle').toggleClass('sfcfc-toggle--disabled', !masterOn);
			});

			$('[data-reveal-toggle]').each(function () {
				var $el  = $(this);
				var isOn = $('#sfcfc-toggle-' + $el.data('reveal-toggle')).hasClass('is-on');
				var show = $el.hasClass('sfcfc-reveal-on-toggle-off') ? !isOn : isOn;
				$el.toggleClass('sfcfc-hidden-row', !show);
			});
		}
		sfcfcApplyDependentRows();

		$(document).on('click', '.sfcfc-toggle', function () {
			var $btn = $(this);
			if ($btn.hasClass('sfcfc-toggle--disabled')) {
				return;
			}

			var radio = $btn.data('toggle-radio');
			if (radio && $btn.hasClass('is-on')) {
				return;
			}

			sfcfcApplyToggleState($btn, !$btn.hasClass('is-on'));

			if (radio) {
				$('.sfcfc-toggle[data-toggle-radio="' + radio + '"]').not($btn).each(function () {
					sfcfcApplyToggleState($(this), false);
				});
			}

			sfcfcApplyDependentRows();
			sfcfcRefreshDirtyState();
		});

		$(document).on('change', '.sfcfc-auth-toggle', function () {
			var method = $(this).val();
			$('.sfcfc-auth-key-row').toggleClass('sfcfc-hidden-row', method === 'token');
			$('.sfcfc-auth-token-row').toggleClass('sfcfc-hidden-row', method !== 'token');
			$('.sfcfc-guide-key').toggleClass('sfcfc-hidden-row', method === 'token');
			$('.sfcfc-guide-token').toggleClass('sfcfc-hidden-row', method !== 'token');
		});

		function sfcfcActivateTab(hash) {
			var $tabs  = $('.sfcfc-nav-tab-wrapper .sfcfc-nav-tab');
			var target = ( hash && $(hash).hasClass('sfcfc-tab-panel') ) ? hash : '#sfcfc-tab-general';

			$('.sfcfc-tab-panel').hide();
			$(target).show();

			$tabs.removeClass('sfcfc-nav-tab-active');
			$tabs.filter('[href="' + target + '"]').addClass('sfcfc-nav-tab-active');

			sfcfcActiveTab = target;
			sfcfcUpdateSaveBar();
		}

		$(SFCFC_TRACKED_FIELDS_SELECTOR).on('input change', sfcfcRefreshDirtyState);

		sfcfcCaptureBaseline();
		sfcfcActivateTab(window.location.hash);

		/**
		 * Delegated so any link to a tab hash works, not just the nav tabs themselves — e.g. the
		 * "See Installation Guide" link inside the API Token field description.
		 */
		$(document).on('click', 'a[href^="#sfcfc-tab-"]', function (e) {
			e.preventDefault();
			var hash = $(this).attr('href');
			history.replaceState(null, '', hash);
			sfcfcActivateTab(hash);
		});

		/**
		 * Friendly replacement for the field's old native "required min=300" bubble: focuses the
		 * field and shows a toast instead of a browser popup. The real safety floor lives
		 * server-side in SFCFC_Sanitize (this is UX only, not the source of truth).
		 */
		function sfcfcValidateSettingsForm() {
			var $maxage = $('#sfcfc-expire');
			if ($maxage.length && (parseInt($maxage.val(), 10) || 0) < 300) {
				showToast('error', 'Edge Cache-Control max-age must be at least 300 seconds.');
				$maxage.trigger('focus');
				return false;
			}
			return true;
		}

		$('#sfcfc-settings-form').on('submit', function (e) {
			e.preventDefault();
			if (!sfcfcValidateSettingsForm()) {
				return;
			}
			var $form = $(this);
			var $btn  = $('#sfcfc-save-settings');
			var data  = $form.serialize() + '&action=sfcfc_save_settings&nonce=' + sfcfc_ajax.nonce;

			runAjaxButton($btn, data, 'Saving\u2026').done(function (response) {
				if (response && response.success) {
					sfcfcCaptureBaseline();
					sfcfcRefreshDirtyState();
				}
			});
		});

		/**
		 * Scrolls the just-rendered inline error notice into view, so a failed Connect attempt is
		 * never missed if the card sits below the fold. Instant when reduced motion is preferred.
		 */
		function sfcfcScrollToCardError() {
			var $error = $('#sfcfc-connection-card .sfcfc-notice-error').first();
			if (!$error.length) {
				return;
			}
			var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			$error[0].scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
		}

		/**
		 * Each response re-renders #sfcfc-connection-card, so the new state is the only
		 * feedback needed — no toast.
		 */
		function sfcfcConnectionAction($btn, action, data, busyText) {
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).attr('aria-busy', 'true').html('<span class="sfcfc-spinner"></span> ' + busyText);

			$.post(sfcfc_ajax.ajaxUrl, $.extend({ action: action, nonce: sfcfc_ajax.nonce }, data))
				.done(function (response) {
					var payload = (response && response.data) || {};
					if (payload.cardHtml) {
						$('#sfcfc-connection-card').replaceWith(payload.cardHtml);
						sfcfcScrollToCardError();
						/** Re-snapshots since the card's own toggle is a tracked field. */
						sfcfcCaptureBaseline();
						sfcfcRefreshDirtyState();
						sfcfcRunDnsProxyCheck();
					}
				})
				.fail(function () {
					$btn.prop('disabled', false).removeAttr('aria-busy').html(originalHtml);
					showToast('error', 'Request failed. Please try again.');
				});
		}

		function sfcfcCardMinimal() {
			return $('#sfcfc-connection-card').data('minimal') ? 1 : 0;
		}

		function sfcfcCardShowConnectForm() {
			return $('#sfcfc-connection-card').data('show-connect-form') === 0 ? 0 : 1;
		}

		$(document).on('click', '.sfcfc-toggle-guide', function (e) {
			e.preventDefault();
			var $wrap = $('#sfcfc-guides-wrap');
			var nowHidden = $wrap.toggleClass('sfcfc-hidden-row').hasClass('sfcfc-hidden-row');
			$('.sfcfc-toggle-guide').text(nowHidden ? 'See the Installation Guide' : 'Hide the Installation Guide');
		});

		$(document).on('click', '#sfcfc-connect-btn', function (e) {
			e.preventDefault();
			var authMethod = $('#sfcfc-cf-auth-method').val();
			var data       = { cf_auth_method: authMethod, minimal: sfcfcCardMinimal() };

			if ('key' === authMethod) {
				data.cf_email   = $('#sfcfc-cf-email').val();
				data.cf_api_key = $('#sfcfc-cf-api-key').val();
			} else {
				data.cf_api_token = $('#sfcfc-cf-api-token').val();
			}

			sfcfcConnectionAction($(this), 'sfcfc_connect', data, 'Connecting\u2026');
		});

		/** Finish Setup keeps the normal toast feedback, unlike Connect above. */
		$(document).on('click', '#sfcfc-save-domain', function (e) {
			e.preventDefault();
			var isMinimal = sfcfcCardMinimal();
			var data = { action: 'sfcfc_confirm_zone', nonce: sfcfc_ajax.nonce, zone_id: $('#sfcfc-zone-domain').val(), minimal: isMinimal };

			runAjaxButton($(this), data, 'Finishing Setup\u2026').done(function (response) {
				if (response && response.success && isMinimal) {
					window.location.href = sfcfc_ajax.settingsUrl;
					return;
				}
				var payload = (response && response.data) || {};
				if (payload.cardHtml) {
					$('#sfcfc-connection-card').replaceWith(payload.cardHtml);
					sfcfcCaptureBaseline();
					sfcfcRefreshDirtyState();
					sfcfcRunDnsProxyCheck();
				}
				if (response && response.success) {
					$('.sfcfc-guide-token, .sfcfc-guide-key').addClass('sfcfc-hidden-row');
				}
			});
		});

		/** Just reveals the already-rendered (hidden) select; no request yet. */
		$(document).on('click', '#sfcfc-change-domain', function (e) {
			e.preventDefault();
			$('#sfcfc-domain-display').addClass('sfcfc-hidden-row');
			$('#sfcfc-domain-edit').removeClass('sfcfc-hidden-row');
		});

		$(document).on('click', '#sfcfc-cancel-domain', function (e) {
			e.preventDefault();
			$('#sfcfc-domain-edit').addClass('sfcfc-hidden-row');
			$('#sfcfc-domain-display').removeClass('sfcfc-hidden-row');
		});

		/** Delegated: lives inside #sfcfc-connection-card, replaced wholesale after every save. */
		$(document).on('click', '#sfcfc-disconnect', function (e) {
			e.preventDefault();
			if (!window.confirm('Disconnect this site from Cloudflare? You can reconnect at any time.')) {
				return;
			}
			runAjaxButton($(this), { action: 'sfcfc_disconnect', nonce: sfcfc_ajax.nonce, minimal: sfcfcCardMinimal(), show_connect_form: sfcfcCardShowConnectForm() }, 'Disconnecting\u2026').done(function (response) {
				var payload = (response && response.data) || {};
				if (payload.cardHtml) {
					$('#sfcfc-connection-card').replaceWith(payload.cardHtml);
				}
			});
		});

		$('#sfcfc-purge-everything').on('click', function (e) {
			e.preventDefault();
			runAjaxButton($(this), { action: 'sfcfc_purge_everything', nonce: sfcfc_ajax.nonce }, 'Purging\u2026');
		});

		/**
		 * The admin bar button's own click handler lives in sfcfc-toolbar.js, loaded on every
		 * screen — not here, since this bundle only loads on the plugin's own admin pages.
		 */

		$('#sfcfc-purge-testing').on('click', function (e) {
			e.preventDefault();
			runAjaxButton($(this), { action: 'sfcfc_purge_test_config', nonce: sfcfc_ajax.nonce }, 'Purging\u2026');
		});

		$('#sfcfc-reset-settings').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Reset all Cloudflare Cache settings to their defaults? Your credentials and domain will be kept.')) {
				return;
			}
			runAjaxButton($(this), { action: 'sfcfc_reset_settings', nonce: sfcfc_ajax.nonce }, 'Resetting\u2026').done(function (response) {
				if (response && response.success) {
					setTimeout(function () { window.location.reload(); }, 900);
				}
			});
		});

		$('#sfcfc-clear-log').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Clear the activity log?')) {
				return;
			}
			runAjaxButton($(this), { action: 'sfcfc_clear_activity_log', nonce: sfcfc_ajax.nonce }, 'Clearing\u2026').done(function (response) {
				if (response && response.success) {
					setTimeout(function () { window.location.reload(); }, 900);
				}
			});
		});

		$('#sfcfc-regenerate-key').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Regenerate the purge key? The old URL will stop working.')) {
				return;
			}
			runAjaxButton($(this), { action: 'sfcfc_regenerate_purge_key', nonce: sfcfc_ajax.nonce }, 'Regenerating\u2026').done(function (response) {
				if (response && response.success && response.data && response.data.url) {
					$('#sfcfc-purge-url').val(response.data.url);
				}
			});
		});

		$('#sfcfc-regenerate-preload-key').on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Regenerate the preloader cronjob key? The old URL will stop working.')) {
				return;
			}
			runAjaxButton($(this), { action: 'sfcfc_regenerate_preload_key', nonce: sfcfc_ajax.nonce }, 'Regenerating\u2026').done(function (response) {
				if (response && response.success && response.data && response.data.url) {
					$('#sfcfc-preload-url').val(response.data.url);
				}
			});
		});

		$('#sfcfc-run-preloader').on('click', function (e) {
			e.preventDefault();
			runAjaxButton($(this), { action: 'sfcfc_run_preloader', nonce: sfcfc_ajax.nonce }, 'Preloading\u2026');
		});

		var $discardModal = $('#sfcfc-discard-modal');

		function sfcfcOpenDiscardModal() {
			$discardModal.prop('hidden', false);
		}

		function sfcfcCloseDiscardModal() {
			$discardModal.prop('hidden', true);
		}

		$('#sfcfc-discard-changes').on('click', function (e) {
			e.preventDefault();
			sfcfcOpenDiscardModal();
		});

		$('#sfcfc-discard-modal-close, #sfcfc-discard-cancel').on('click', function (e) {
			e.preventDefault();
			sfcfcCloseDiscardModal();
		});

		/** Clicking the backdrop itself, not the dialog card, also cancels. */
		$discardModal.on('click', function (e) {
			if (e.target === this) {
				sfcfcCloseDiscardModal();
			}
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && !$discardModal.prop('hidden')) {
				sfcfcCloseDiscardModal();
			}
		});

		$('#sfcfc-discard-confirm').on('click', function (e) {
			e.preventDefault();

			$('#sfcfc-settings-form')[0].reset();
			$('.sfcfc-auth-toggle').trigger('change');

			/**
			 * Native reset() restores each hidden field to its last-saved value but doesn't
			 * touch the pill button's own class/aria, so it's resynced here.
			 */
			$('.sfcfc-toggle').each(function () {
				var $btn   = $(this);
				var group  = $btn.data('toggle-group');
				var $field = $('input[type="hidden"][name="' + group + '"]');
				if ($field.length) {
					sfcfcApplyToggleState($btn, $field.val() === 'on');
				}
			});

			sfcfcApplyDependentRows();
			sfcfcRefreshDirtyState();
			sfcfcCloseDiscardModal();
			showToast('success', 'Changes discarded.');
		});

		var $customPurgeModal    = $('#sfcfc-custom-purge-modal');
		var sfcfcActivePurgeType = 'url';

		function sfcfcOpenCustomPurgeModal() {
			$customPurgeModal.prop('hidden', false);
		}

		function sfcfcCloseCustomPurgeModal() {
			$customPurgeModal.prop('hidden', true);
		}

		$('#sfcfc-open-custom-purge').on('click', function (e) {
			e.preventDefault();
			sfcfcOpenCustomPurgeModal();
		});

		$('#sfcfc-custom-purge-close, #sfcfc-custom-purge-cancel').on('click', function (e) {
			e.preventDefault();
			sfcfcCloseCustomPurgeModal();
		});

		$customPurgeModal.on('click', function (e) {
			if (e.target === this) {
				sfcfcCloseCustomPurgeModal();
			}
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && !$customPurgeModal.prop('hidden')) {
				sfcfcCloseCustomPurgeModal();
			}
		});

		$('.sfcfc-purge-type-option').on('click', function () {
			sfcfcActivePurgeType = $(this).data('purge-type');
			$('.sfcfc-purge-type-option').removeClass('is-active').attr('aria-pressed', 'false');
			$(this).addClass('is-active').attr('aria-pressed', 'true');
			$('.sfcfc-purge-type-panel').addClass('sfcfc-hidden-row');
			$('.sfcfc-purge-type-panel[data-purge-panel="' + sfcfcActivePurgeType + '"]').removeClass('sfcfc-hidden-row');
		});

		$('#sfcfc-custom-purge-submit').on('click', function (e) {
			e.preventDefault();
			var $items = sfcfcActivePurgeType === 'hostname' ? $('#sfcfc-custom-purge-hostnames') : $('#sfcfc-custom-purge-urls');
			var value  = $.trim($items.val());

			if (!value) {
				showToast('error', 'Add at least one ' + (sfcfcActivePurgeType === 'hostname' ? 'hostname' : 'URL') + ' to purge.');
				return;
			}

			runAjaxButton($(this), {
				action: 'sfcfc_custom_purge',
				nonce: sfcfc_ajax.nonce,
				purge_type: sfcfcActivePurgeType,
				items: value
			}, 'Purging\u2026').done(function (response) {
				if (response && response.success) {
					$items.val('');
					sfcfcCloseCustomPurgeModal();
				}
			});
		});

		/**
		 * Copies the System Information list as plain text. Falls back to an error toast
		 * on browsers without the Clipboard API.
		 */
		$('#sfcfc-copy-system-info').on('click', function (e) {
			e.preventDefault();
			var text = $('#sfcfc-system-info-text').val();

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					showToast('success', 'Copied! Paste it into your support ticket or forum post.');
				}, function () {
					showToast('error', 'Could not copy automatically. Please select and copy manually.');
				});
			} else {
				showToast('error', 'Could not copy automatically. Please select and copy manually.');
			}
		});

		/** Shows the chosen file's name; the native file input itself is visually hidden. */
		$('#sfcfc-import-file').on('change', function () {
			var files = this.files;
			var label = files && files.length ? files[0].name : 'No file selected';
			$('#sfcfc-file-picker-name').text(label);
		});

		/**
		 * Guards the real (non-AJAX) file upload submit with a friendly toast instead of relying
		 * on the native "required" bubble, which the browser can't anchor to this input since it's
		 * visually hidden — that silently blocks submission with no visible feedback at all.
		 */
		$('#sfcfc-import-form').on('submit', function (e) {
			var files = $('#sfcfc-import-file')[0].files;
			if (!files || !files.length) {
				e.preventDefault();
				showToast('error', 'Please choose a file to import.');
			}
		});
	});
})(jQuery);
