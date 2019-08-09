$(document).foundation();

// Start the main app logic.
$(function () {

    //
    // datetime picker
    //
    var fdatepicker = $(".fdatepicker");
    if (fdatepicker) {

        // Load a JavaScript file from the server using a GET HTTP request, then execute it.
        $.getScript(BASE_URL + 'addons/js/datepicker/foundation-datepicker.min.js', function (data, textStatus, jqxhr) {
            lang = ADMIN_LANG;
            $.getScript(BASE_URL + 'addons/js/datepicker/locales/foundation-datepicker.' + lang + '.js').done(function() {

                // input select searched
                if (fdatepicker.is(".pick_time")) {
                    fdatepicker.fdatepicker({
                        leftArrow: '<i class="fa fa-angle-left" aria-hidden="true"></i>',
                        rightArrow: '<i class="fa fa-angle-right" aria-hidden="true"></i>',
                        format: 'yyyy-mm-dd hh:ii',
                        language: lang,
                        todayHighlight: true,
                        pickTime: true,
                        onRender: function (d) {},
                    });
                }
            });
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
    meta_append_name.on('change', function () {
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
        var invalid = $(this).find('[data-invalid]');
        if (invalid) {
            $(window).delay(250).scrollTo(invalid, 600, {offset: -50, interrupt: true});
        }
    });

    //
    // file input wrap
    //
    var thumbnail_input = $(".thumbnail-input");
    thumbnail_input.find('input').on('change', function (evt, params) {

        var thumbnails = thumbnail_input.find(".thumbnails");
        if(evt.target.value.length > 0) {

            // FileList object, single file upload
            var f = evt.target.files[0];

            // Only process image files.
            if (f.type.match('image.*')) {
                var reader = new FileReader();

                // Closure to capture the file information.
                reader.onload = (function(file) {
                    return function(e) {

                        // Render thumbnail.
                        var span = $("<span/>", {"class": 'res res-1y1'}).html(['<img src="', e.target.result, '" title="', escapeString(file.name), '"/>'].join(''));
                        thumbnails.children('figure').remove();
                        thumbnails.append($("<figure/>").html(span));
                    };
                })(f);

                // Read in the image file as a data URL.
                reader.readAsDataURL(f);
            }
        }
        else
            thumbnails.children('figure').remove();
    });
});

//
// onload
//
$(window).on("load", function () {

    var _action = $.query.get('_action');
    if(_action) {
        pushState({}, document.title, window.location.href.split(/[?#]/)[0] + $.query.REMOVE("_action"));
    }
});
