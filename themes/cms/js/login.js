/**js**/
(function ($) {

    $(document).foundation();
    $(function () {

        apx.title = $("html").find('title').text();

        // onload
        $(window).on("load", function () {

            var _action = $.query.get('_action');
            if(_action) {
                var split_url = window.location.href.split(/[?#]/)[0];
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

})(jQuery);
