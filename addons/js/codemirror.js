(function($) {
    'use strict';

    $(function() {

        // codemirror
        var codemirror = $(".codemirror");
        if(codemirror.length) {

            var options = {
                mode: 'htmlmixed',
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                autoCloseBrackets: true,
                autoCloseTags: true,
                tabSize: 2,
                indentUnit: 2,
                indentWithTabs: true,
                autoRefresh: true,
            };
            $.each(codemirror, function( index, value ) {
                CodeMirror.fromTextArea(value, options);
            });
        }
    });
})(jQuery);
