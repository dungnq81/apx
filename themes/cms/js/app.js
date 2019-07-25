// main script
(function ($) {
    'use strict';

    $(document).foundation();

    // Start the main app logic.
    $(function () {

        var html = $("html");
        var body = $("body");
        var main = $("main");
        var footer = $("footer");

        apx.admin_theme_js = apx.admin_theme_url + 'js/';
        apx.title = html.find('title').text();

        // datetime picker
        var fdatepicker = $(".fdatepicker");
        if (fdatepicker) {

            // Load a JavaScript file from the server using a GET HTTP request, then execute it.
            $.getScript(BASE_URL + 'addons/js/datepicker/foundation-datepicker.min.js', function (data, textStatus, jqxhr) {

                $.fn.fdatepicker.dates['vi'] = {
                    days: ["Chủ Nhật", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7", "Chủ Nhật"],
                    daysShort: ["CN", "T2", "T3", "T4", "T5", "T6", "T7", "CN"],
                    daysMin: ["CN", "T2", "T3", "T4", "T5", "T6", "T7", "CN"],
                    months: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
                    monthsShort: ["Thg1", "Thg2", "Thg3", "Thg4", "Thg5", "Thg6", "Thg7", "Thg8", "Thg9", "Thg10", "Thg11", "Thg12"],
                    today: "Hôm nay",
                };

                // input select searched
                if (fdatepicker.is(".pick_time")) {
                    fdatepicker.fdatepicker({
                        leftArrow: '<i class="fa fa-angle-left" aria-hidden="true"></i>',
                        rightArrow: '<i class="fa fa-angle-right" aria-hidden="true"></i>',
                        format: 'yyyy-mm-dd hh:ii',
                        language: (LANG == 'vi') ? LANG : 'en',
                        todayHighlight: true,
                        pickTime: true,
                        onRender: function (d) {},
                    });
                }
            });
        }

        // frm_wrapper
        var frm_wrapper = $(".frm-wrapper");
        var title_placeholder = frm_wrapper.find("#meta-title-placeholder");
        var title_offset = frm_wrapper.find("#meta-title-offset");

        var meta_append_name = frm_wrapper.find('input[name="meta_append_name"]');
        if (meta_append_name.is(':checked')) {
            title_placeholder.show();
        }

        // toogle append the site-name
        meta_append_name.change(function () {
            title_placeholder.hide();
            if ($(this).is(':checked')) {
                title_placeholder.show();
            }
        });

        //
        var meta_title = frm_wrapper.find('input[name="meta_title"]');
        title_placeholder.on('click', function () {
            meta_title.focus();
        });

        //
        title_placeholder.css('left', title_offset.width() + 16 + 'px');
        updateCounter(frm_wrapper.find(".meta-title-input-wrap .meta-char-counter .chars"), unescapeString(meta_title.val()));
        meta_title.on('input', function () {
            title_offset.html($(this).val());
            title_placeholder.css('left', title_offset.width() + 16 + 'px');

            var el = $(this).parent().parent().find(".meta-char-counter .chars");
            updateCounter(el, unescapeString(title_offset.html()));
        });

        //
        var meta_description = frm_wrapper.find('textarea[name="meta_description"]');
        updateCounter(frm_wrapper.find(".meta-description-input-wrap .meta-char-counter .chars"), unescapeString(meta_description.val()), 45, 320);
        meta_description.on('input', function () {

            var el = $(this).parent().parent().find(".meta-char-counter .chars");
            updateCounter(el, unescapeString($(this).val()), 45, 320);
        });

        //
        var form_abide = frm_wrapper.find("form[data-abide]");
        form_abide.on("forminvalid.zf.abide", function(ev, frm) { // form validation failed

            var invalidFields = $(this).find('[data-invalid]');
            if (invalidFields) {
                $(window).delay(250).scrollTo(invalidFields, 600, {offset: -50, interrupt: true});
            }
        });

        //
        // onload
        $(window).on("load", function () {

            var _action = $.query.get('_action');
            if(_action) {
                //var split_url = window.location.href.split(/[?#]/)[0];
                var split_url = window.location.href.split("?")[0] + $.query.REMOVE("_action");
                pushState({}, apx.title, split_url);
            }
        });
    });

    /**
     *
     * @param page
     * @param title
     * @param url
     */
    function pushState(page, title, url) {
        if ("undefined" !== typeof history.pushState) {
            history.pushState({page: page}, title, url);
        } else {
            window.location.assign(url);
        }
    }

    /**
     *
     * @param el
     * @param text
     * @param min
     * @param max
     */
    function updateCounter(el, text, min, max) {

        var test = {
            'e': el,
            'text': text,
        };
        _updateCharacterCounter(test, min, max);
    }

    /**
     * Updates character counter.
     *
     * @param test
     * @param min
     * @param max
     */
    function _updateCharacterCounter(test, min, max) {

        var el = test.e, text = test.text;
        var testLength = getStringLength(text), newClass = '', exclaimer = '';
        var classes = {
            empty: 'count-empty',
            bad: 'count-bad',
            good: 'count-good',
        };

        if (!min) min = 25;
        if (!max) max = 75;

        if (!testLength) {
            newClass = classes.empty;
            exclaimer = 'Empty';
        } else if (testLength < min) {
            newClass = classes.bad;
            exclaimer = 'Too short';
        } else if (testLength > max) {
            newClass = classes.bad;
            exclaimer = 'Too long';
        } else {
            //= between min and max.
            newClass = classes.good;
            exclaimer = 'Good';
        }

        exclaimer = testLength.toString() + ' - ' + exclaimer;
        el.html(exclaimer);

        //= IE11 compat... great. Spread syntax please :)
        for (var _c in classes) {
            el.removeClass(classes[_c]);
        }
        el.addClass(newClass);
    }

})(jQuery);
