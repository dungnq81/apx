$(document).foundation();
$(function () {
    $.ajaxSetup({
        data: {'_csrf_token': $.cookie(apx.csrf_cookie_name)}
    });

    apx.title = $("html").find('title').text();
});

// onload
$(window).on("load", function () {

    var _action = $.query.get('_action');
    if(_action) {
        pushState({}, apx.title, window.location.href.split(/[?#]/)[0]);
    }
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
