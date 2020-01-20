/* eslint-disable no-undef */
$(document).foundation();

// onload
$(window).on("load", function () {

    var _action = $.query.get('_action');
    if(_action) {
        pushState({}, document.title, window.location.href.split(/[?#]/)[0]);
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
