$(function () {

    // select languages
    var select_lang = $(".select-language");
    select_lang.find('select').on('change', function(e, params) {

        $.post(BASE_URL + "admin/pages/change_language", {code: e.target.value}, function (data) {
            var results = $.parseJSON(data);
            select_lang.find("img.flag").attr("src", results.flag);
            pushState({}, apx.title, window.location.href.split(/[?#]/)[0] + $.query.SET('lang', results.code));
        });
    });
});
