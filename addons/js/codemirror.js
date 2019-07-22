(function($) {
    'use strict';

    $(function() {

        // codemirror
        var codemirror = $(".codemirror");
        if(codemirror.length) {

            var option = {
                mode: 'htmlmixed',
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                autoCloseBrackets: true,
                autoCloseTags: true,
                tabSize: 2,
            };
            $.each(codemirror, function( index, value ) {
                CodeMirror.fromTextArea(value, option);
            });
        }

    });
})(jQuery);
