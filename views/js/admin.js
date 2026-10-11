/**
 * Header/Footer Code Injector - back office
 *
 * @author    DataFirefly
 * @copyright DataFirefly Limited
 * @license   Commercial
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        // Show the page fields only when a targeting mode needs them.
        var mode = document.querySelector('select[name="pages_mode"]');
        if (mode) {
            var groups = document.querySelectorAll('.dfci-pages-group');
            var toggle = function () {
                var show = mode.value !== 'all';
                for (var i = 0; i < groups.length; i++) {
                    groups[i].style.display = show ? '' : 'none';
                }
            };
            mode.addEventListener('change', toggle);
            if (window.jQuery) {
                window.jQuery(mode).on('change', toggle); // chosen / jQuery triggered changes
            }
            toggle();
        }

        // Code editor: Tab inserts 4 spaces, Escape then Tab leaves the field.
        var code = document.querySelector('textarea[name="code"]');
        if (code) {
            var escaped = false;
            code.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    escaped = true;
                    return;
                }
                if (e.key === 'Tab' && !escaped && !e.shiftKey && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    e.preventDefault();
                    // insertText keeps Ctrl+Z working; direct assignment is the fallback.
                    if (!document.execCommand || !document.execCommand('insertText', false, '    ')) {
                        var start = code.selectionStart;
                        var end = code.selectionEnd;
                        code.value = code.value.substring(0, start) + '    ' + code.value.substring(end);
                        code.selectionStart = code.selectionEnd = start + 4;
                    }
                    return;
                }
                escaped = false;
            });
        }
    });
})();
