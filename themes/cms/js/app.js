$(document).foundation();

// Start the main app logic.
$(function () {

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
                    language: (LANG === 'vi') ? 'vi' : 'en',
                    todayHighlight: true,
                    pickTime: true,
                    onRender: function (d) {},
                });
            }
        });
    }

    //
    // frm_wrapper
    //
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

    var meta_title = frm_wrapper.find('input[name="meta_title"]');
    title_placeholder.on('click', function () {
        meta_title.focus();
    });

    title_placeholder.css('left', title_offset.width() + 16 + 'px');
    updateCounter(frm_wrapper.find(".meta-title-input-wrap .meta-char-counter .chars"), unescapeString(meta_title.val()));
    meta_title.on('input', function () {
        title_offset.html($(this).val());
        title_placeholder.css('left', title_offset.width() + 16 + 'px');

        var el = $(this).parent().parent().find(".meta-char-counter .chars");
        updateCounter(el, unescapeString(title_offset.html()));
    });

    var meta_description = frm_wrapper.find('textarea[name="meta_description"]');
    updateCounter(frm_wrapper.find(".meta-description-input-wrap .meta-char-counter .chars"), unescapeString(meta_description.val()), 45, 320);
    meta_description.on('input', function () {

        var el = $(this).parent().parent().find(".meta-char-counter .chars");
        updateCounter(el, unescapeString($(this).val()), 45, 320);
    });

    //
    // abide form validation failed
    //
    var form_abide = frm_wrapper.find("form[data-abide]");
    form_abide.on("forminvalid.zf.abide", function(ev, frm) {

        var invalidFields = $(this).find('[data-invalid]');
        if (invalidFields) {
            $(window).delay(250).scrollTo(invalidFields, 600, {offset: -50, interrupt: true});
        }
    });
});

//
// onload
//
$(window).on("load", function () {

    var _action = $.query.get('_action');
    if(_action) {
        var split_url = window.location.href.split(/[?#]/)[0] + $.query.REMOVE("_action");
        pushState({}, apx.title, split_url);
    }
});
