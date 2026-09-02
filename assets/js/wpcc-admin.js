jQuery('li#wp-admin-bar-wpcc-purge-button .ab-item').on('click', PurgeEverything);
jQuery('#wpcc_purge_everything').on('click', PurgeEverything);
function PurgeEverything() {
	jQuery.post(ajaxurl, { action: 'wpcc_purge_everything' }, function(response) {
		purgeAlert(response)
	});
}

jQuery('#wpcc-purge-testing').on('click', function () {
	jQuery.post(ajaxurl, { action: 'wpcc_purge_test_config' }, function(response) {
		purgeAlert(response)
	});
});

jQuery('#wpcc-create-page-rule').on('click', function () {
	jQuery.post(ajaxurl, { action: 'wpcc_create_page_rule' }, function(response) {
		purgeAlert(response)
	});
});

jQuery('#wpcc-set-browser-ttl').on('click', function () {
	jQuery.post(ajaxurl, { action: 'wpcc_set_browser_ttl' }, function(response) {
		purgeAlert(response)
	});
});
		  
function purgeAlert(response) { alert( response?.message || (response?.errors?.rest_forbidden && response?.errors?.rest_forbidden[0]) || (response?.errors?.cloudflare_error && response.errors.cloudflare_error[0]) || JSON.stringify(response.errors) );
}
		  
(function ($) {
    $(document).ready(function () {
        var visibility_toggle = $('#visibility_toggle');
        visibility_toggle.click(function () {
            if ($(this).attr('data-label') == "Show") {
                $('input#api_key').attr('type', 'text');
                $(this).attr('data-label', "Hide");
                $(this).find('.text').html('Hide');
            } else if ($(this).attr('data-label') == "Hide") {
                $('input#api_key').attr('type', 'password');
                $(this).attr('data-label', "Show");
                $(this).find('.text').html('Hide');
            }
        });});

})(jQuery);
									  