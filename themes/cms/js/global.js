/*
 It may already be defined in metadata partial
 */
if (typeof(apx) == 'undefined') {
    var apx = {'lang': {}};
}

(function ($) {
    /*!
     * jQuery serializeObject - v0.2
     * http://benalman.com/projects/jquery-misc-plugins/
     */
    $.fn.serializeObject = function () {
        var obj = {};
        $.each(this.serializeArray(), function (i, o) {
            var n = o.name,
                v = o.value;

            obj[n] = obj[n] === undefined ? v
                : $.isArray(obj[n]) ? obj[n].concat(v)
                    : [obj[n], v];
        });
        return obj;
    };

    /*!
     * jQuery viewportOffset - v0.3
     * http://benalman.com/projects/jquery-misc-plugins/
     *
     * Like the built-in jQuery .offset() method, but calculates left and top from
     * the element's position relative to the viewport, not the document.
     */
    $.fn.viewportOffset = function () {
        var offset = $(this).offset();
        return {
            left: offset.left - $(window).scrollLeft(),
            top: offset.top - $(window).scrollTop()
        };
    };

    /**
     * https://github.com/daneden/animate.css
     */
    $.fn.animateCss = function (animationName, callback) {
        var animationEnd = (function (el) {
            var animations = {
                animation: 'animationend',
                OAnimation: 'oAnimationEnd',
                MozAnimation: 'mozAnimationEnd',
                WebkitAnimation: 'webkitAnimationEnd',
            };
            for (var t in animations) {
                if (el.style[t] !== undefined) {
                    return animations[t];
                }
            }
        })(document.createElement('div'));

        this.addClass('animated ' + animationName).one(animationEnd, function () {
            $(this).removeClass('animated ' + animationName);

            if (typeof callback === 'function') callback();
        });
        return this;
    };
})(jQuery);

/**
 * @param c
 * @param d
 * @param t
 * @returns {string}
 */
Number.prototype.formatMoney = function (c, d, t) {
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
        j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

$(function () {
    'use strict';

    var html = $("html");
    var body = $("body");
    apx.admin_theme_js = apx.admin_theme_url + 'js/';

    /**
     * Overload the json converter to avoid error when json is null or empty.
     */
    $.ajaxSetup({
        converters: {
            'text json': function (text) {
                var json = $.parseJSON(text);
                if (!$.ajaxSettings.allowEmpty && (json == null || $.isEmptyObject(json))) {
                    $.error('The server is not responding correctly, please try again later.');
                }
                return json;
            }
        },
        data: {
            '_csrf_token': $.cookie(apx.csrf_cookie_name)
        }
    });

    // css var
    if (!browser_CssVariables()) {
        body.prepend("<p class=\"browserupgrade\">You are using an <strong>outdated</strong> browser. Please <a href=\"http://browsehappy.com/\" style=\"color:#E62117\" target=\"_blank\">upgrade your browser</a> to improve your experience.</p>");
    }

    // Hide all elements with .hideOnSubmit class when parent form is submit
    $('form').submit(function () {
        $(this).find('.hideOnSubmit').hide();
    });

    // attribute target="_blank" is not W3C compliant
    $('a._blank, a.blank, a.js-new-window').attr('target', '_blank');
});

/**
 * browser_CssVariables
 * @returns {*}
 */
function browser_CssVariables() {
    return window.CSS && window.CSS.supports && window.CSS.supports('--fake-var', 0);
}

/**
 *
 * @param str
 * @returns {*}
 */
function escapeRegExp(str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

/**
 *
 * @param $email
 * @returns {boolean}
 */
function valid_email($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,6})?$/;
    //return emailReg.test($email);
    return ($email.length > 0 && emailReg.test($email));
}

/**
 * redirect
 *
 * @param url
 */
function redirect(url) {
    if (url === null || url === '' || $.type(url) === "undefined") {
        if (!window.location.hash)
            window.location.href = window.location.href;
        else
            window.location.reload();

    } else {
        url = url.replace(/\s+/g, '');
        var ua = navigator.userAgent.toLowerCase(),
            isIE = ua.indexOf('msie') !== -1,
            version = parseInt(ua.substr(4, 2), 10);

        // Internet Explorer 8 and lower
        if (isIE && version < 9) {
            var link = document.createElement('a');
            link.href = url;
            document.body.appendChild(link);
            link.click();
        } else {
            window.location.replace(url);
            window.location.href = url;
        }
    }
}

/**
 * Get multi scripts
 *
 * @param scripts
 * @param callback
 */
function getScripts(scripts, callback) {
    var progress = 0;
    scripts.forEach(function (script) {
        $.getScript(script, function () {
            if (++progress == scripts.length) callback();
        });
    });
}

/**
 * Function : print_r()
 * Arguments: The data  - array,hash(associative array),object
 *            The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 */
function print_r(arr, level) {
    var dumped_text = "";
    if (!level)
        level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for (var j = 0; j < level + 1; j++)
        level_padding += "    ";

    if (typeof (arr) === 'object') { //Array/Hashes/Objects
        for (var item in arr) {
            var value = arr[item];
            if (typeof (value) === 'object') { //If it is an array,
                dumped_text += level_padding + "'" + item + "' ...\n";
                dumped_text += print_r(value, level + 1);
            } else {
                dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
            }
        }
    } else { //Strings/Chars/Numbers etc.
        dumped_text = "===>" + arr + "<===(" + typeof (arr) + ")";
    }
    return dumped_text;
}

/**
 *
 * @param filename
 * @returns {string}
 */
function getExtension(filename) {
    return filename.split('.').pop().toLowerCase();
}

/**
 * Verify if value is in the array
 *
 * @param value
 * @param array
 * @returns {boolean}
 */
function in_array(value, array) {
    for (var i in array)
        if ((array[i] + '') === (value + ''))
            return true;
    return false;
}

/**
 *
 * @param length_str
 * @returns {string}
 */
function random_string(length_str) {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < length_str; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}

/**
 *
 * @param input
 * @returns {*}
 */
function sum(input) {
    if (toString.call(input) !== "[object Array]")
        return false;

    var total = 0;
    for (var i = 0; i < input.length; i++) {
        if (isNaN(input[i])) {
            continue;
        }
        total += Number(input[i]);
    }
    return total;
}

/**
 * Remove the formatting to get integer data for summation
 *
 * @param i
 * @returns {number}
 */
function intVal(i) {
    return typeof i === 'string' ?
        i.replace(/[$,]/g, '') * 1 :
        typeof i === 'number' ?
            i : 0;
}

/**
 *
 * @param cname
 * @param cvalue
 * @param exdays
 */
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

/**
 *
 * @param cname
 * @returns {string}
 */
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

/**
 * stripTags
 *
 * @param str
 * @returns {*}
 */
function stripTags(str) {
    return str.replace(/(<([^>]+)?>?)/ig, '');
}

/**
 * Escapes input string.
 *
 * @source <https://stackoverflow.com/a/4835406>
 * @param {string} str
 * @return {string}
 */
function escapeString(str) {
    if ($.type(str) === "undefined" || !str.length) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
        "\\\\": '&#92;',
        "\\": '',
    };

    return str.replace(/[&<>"']|\\\\|\\/g, m => map[m]);
}

/**
 * Undoes what escapeString has done.
 *
 * @param {string} str The escaped str via escapeString
 * @return {string}
 */
function unescapeString(str) {
    if ($.type(str) === "undefined" || !str.length) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
        "\\": '&#92;',
    };
    var values = {};
    if (!!navigator.userAgent.match(/Trident\/7\./)) {
        //= IE11 replacement for Object.prototype.values. <https://stackoverflow.com/a/42830295>
        values = Object.keys(map).map(e => map[e]);
    } else {
        values = Object.values(map);
    }

    var regex = new RegExp(
        values.map(
            v => v.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&')
        ).join('|'),
        'g'
    );

    if (!!navigator.userAgent.match(/Trident\/7\./)) {
        //= IE11 inline-replacement for Object.prototype.find.
        return str.replace(regex, m => {
            for (var k in map) {
                if (map[k] === m) return k;
            }
            return m;
        });
    } else {
        return str.replace(regex,
            m => Object.keys(map).find(
                k => map[k] === m
            )
        );
    }
}

/**
 * Removes duplicated spaces in strings.
 *
 * @function
 * @param {string} str
 * @return {string}
 */
function sDoubleSpace(str) {
    return str.replace(/\s\s+/g, ' ');
}

/**
 * Gets string length.
 *
 * @param {string} str
 * @return {number}
 */
function getStringLength(str) {
    if ($.type(str) === "undefined") return '';
    var e, length = 0;
    if (str.length) {
        e = document.createElement('span');
        e.innerHTML = escapeString(str).trim(); // Trimming can lead to empty child nodes.
        if ('undefined' !== typeof e.childNodes[0])
            length = e.childNodes[0].nodeValue.length;
    }
    return +length;
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
    updateCharacterCounter(test, min, max);
}

/**
 * Updates character counter.
 *
 * @param test
 * @param min
 * @param max
 */
function updateCharacterCounter(test, min, max) {

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

/**
 * Tries to convert JSON response to values if not already set.
 *
 * @param {(object|string|undefined)} response
 * @return {(object|undefined)}
 */
function convertJSONResponse(response) {
    var testJSON = response && response.json || void 0, isJSON = 1 === testJSON;
    if (!isJSON) {
        var _response = response;
        try {
            response = JSON.parse(response);
            isJSON = true;
        } catch (error) {
            isJSON = false;
        }
        if (!isJSON) {
            // Reset response.
            response = _response;
        }
    }
    return response;
}

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
