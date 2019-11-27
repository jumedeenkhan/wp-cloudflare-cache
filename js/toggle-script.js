/**
 * Created by Jumedeen khan
 */

(function ($) {
    $(document).ready(function () {

        var visibility_toggle = $('#visibility_toggle');
        visibility_toggle.click(function () {
            if ($(this).attr('data-label') == "Show") {
                $('input#API_key').attr('type', 'text');
                $(this).attr('data-label', "Hide");
                $(this).find('.text').html('Hide');
            } else if ($(this).attr('data-label') == "Hide") {
                $('input#API_key').attr('type', 'password');
                $(this).attr('data-label', "Show");
                $(this).find('.text').html('Hide');
            }
        });});

})(jQuery);
